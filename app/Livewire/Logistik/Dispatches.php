<?php

namespace App\Livewire\Logistik;

use App\Models\Material;
use App\Models\MaterialToolRequest;
use App\Models\MaterialUsage;
use App\Models\Tool;
use App\Models\ToolUsage;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Dispatches extends Component
{
    use WithPagination;

    public $filterStatus = 'all';

    public function dispatchRequest($requestId)
    {
        $req = MaterialToolRequest::findOrFail($requestId);

        if ($req->status !== 'pending') {
            session()->flash('error', 'Permintaan ini sudah diproses.');
            return;
        }

        // Check stock availability before dispatching
        if ($req->type === 'material') {
            if ($req->material->stock < $req->quantity) {
                session()->flash('error', 'Stok material tidak cukup (tersedia: ' . $req->material->stock . ').');
                return;
            }
        } else {
            if ($req->tool->available_qty < $req->quantity) {
                session()->flash('error', 'Stok alat tidak cukup (tersedia: ' . $req->tool->available_qty . ').');
                return;
            }
        }

        $req->update([
            'status' => 'dispatched',
            'dispatcher_id' => auth()->id(),
            'dispatched_at' => now(),
        ]);

        session()->flash('success', 'Paket pengiriman ' . $req->request_code . ' berhasil dikirim ke lokasi.');
    }

    public function approveRequest($requestId)
    {
        $req = MaterialToolRequest::findOrFail($requestId);

        if ($req->status !== 'arrived') {
            session()->flash('error', 'Hanya pengiriman yang sudah tiba di lapangan (dengan foto bukti) yang dapat diapprove.');
            return;
        }

        try {
            DB::transaction(function () use ($req) {
                if ($req->type === 'material') {
                    $mat = Material::findOrFail($req->material_id);
                    if ($mat->stock < $req->quantity) {
                        throw new \Exception('Stok material tidak cukup saat approval.');
                    }

                    // Deduct material stock
                    $mat->decrement('stock', $req->quantity);
                    $totalCost = $req->quantity * $mat->unit_price;

                    // Log material usage to house
                    MaterialUsage::create([
                        'house_id' => $req->house_id,
                        'material_id' => $req->material_id,
                        'user_id' => $req->requester_id,
                        'quantity' => $req->quantity,
                        'unit_price_at_usage' => $mat->unit_price,
                        'total_cost' => $totalCost,
                        'usage_date' => now()->format('Y-m-d'),
                        'notes' => $req->notes,
                    ]);
                } else {
                    $tool = Tool::findOrFail($req->tool_id);
                    if ($tool->available_qty < $req->quantity) {
                        throw new \Exception('Stok alat tidak cukup saat approval.');
                    }

                    // Deduct available tool qty
                    $tool->decrement('available_qty', $req->quantity);

                    // Log tool usage to house
                    ToolUsage::create([
                        'house_id' => $req->house_id,
                        'tool_id' => $req->tool_id,
                        'user_id' => $req->requester_id,
                        'quantity' => $req->quantity,
                        'checkout_date' => now()->format('Y-m-d'),
                        'notes' => $req->notes,
                    ]);
                }

                $req->update([
                    'status' => 'approved',
                    'approver_id' => auth()->id(),
                    'approved_at' => now(),
                ]);
            });

            session()->flash('success', 'Transaksi ' . $req->request_code . ' telah disetujui, stok dipotong, dan biaya dicatat.');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function rejectRequest($requestId)
    {
        $req = MaterialToolRequest::findOrFail($requestId);
        $req->update([
            'status' => 'rejected',
            'approver_id' => auth()->id(),
        ]);
        session()->flash('success', 'Permintaan ' . $req->request_code . ' ditolak.');
    }

    public function render()
    {
        $requests = MaterialToolRequest::with(['requester', 'dispatcher', 'approver', 'house', 'material', 'tool'])
            ->when($this->filterStatus !== 'all', fn ($q) => $q->where('status', $this->filterStatus))
            ->latest()
            ->paginate(15);

        return view('livewire.logistik.dispatches', compact('requests'))
            ->layout('layouts.app', ['title' => 'Antrean Pengiriman & Approval Logistik']);
    }
}

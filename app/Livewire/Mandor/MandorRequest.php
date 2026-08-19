<?php

namespace App\Livewire\Mandor;

use App\Models\House;
use App\Models\Material;
use App\Models\MaterialToolRequest;
use App\Models\Tool;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class MandorRequest extends Component
{
    use WithPagination, WithFileUploads;

    public $type = 'material'; // material or tool
    public $house_id = '';
    public $material_id = '';
    public $tool_id = '';
    public $quantity = 1;
    public $notes = '';

    // Arrival proof upload modal state
    public $showProofModal = false;
    public $selectedRequestId = null;
    public $arrivalProofImage = null;

    protected function rules()
    {
        return [
            'house_id' => 'required|exists:houses,id',
            'type' => 'required|in:material,tool',
            'material_id' => 'required_if:type,material|nullable|exists:materials,id',
            'tool_id' => 'required_if:type,tool|nullable|exists:tools,id',
            'quantity' => 'required|numeric|min:0.01',
            'notes' => 'required|string|max:500',
        ];
    }

    public function submitRequest()
    {
        $this->validate();

        $requestCode = 'REQ-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

        MaterialToolRequest::create([
            'request_code' => $requestCode,
            'requester_id' => auth()->id(),
            'house_id' => $this->house_id,
            'type' => $this->type,
            'material_id' => $this->type === 'material' ? $this->material_id : null,
            'tool_id' => $this->type === 'tool' ? $this->tool_id : null,
            'quantity' => $this->quantity,
            'notes' => $this->notes,
            'status' => 'pending',
        ]);

        $this->reset(['material_id', 'tool_id', 'quantity', 'notes']);
        $this->quantity = 1;

        session()->flash('success', 'Permintaan material/alat berhasil diajukan ke Logistik.');
    }

    public function openProofModal($requestId)
    {
        $this->selectedRequestId = $requestId;
        $this->arrivalProofImage = null;
        $this->resetValidation();
        $this->showProofModal = true;
    }

    public function submitArrivalProof()
    {
        $this->validate([
            'selectedRequestId' => 'required|exists:material_tool_requests,id',
            'arrivalProofImage' => 'required|image|max:5120',
        ]);

        $req = MaterialToolRequest::findOrFail($this->selectedRequestId);

        if ($req->status !== 'dispatched') {
            session()->flash('error', 'Status permintaan ini tidak dapat dikonfirmasi.');
            return;
        }

        $path = $this->arrivalProofImage->store('arrival-proofs', 'public');

        $req->update([
            'arrival_proof_image' => $path,
            'status' => 'arrived',
            'arrived_at' => now(),
        ]);

        $this->showProofModal = false;
        session()->flash('success', 'Foto bukti penerimaan barang berhasil diunggah. Menunggu verifikasi Logistik.');
    }

    public function render()
    {
        $houses = House::orderBy('name')->get();
        $materials = Material::orderBy('name')->get();
        $tools = Tool::orderBy('name')->get();

        $myRequests = MaterialToolRequest::with(['house', 'material', 'tool', 'dispatcher', 'approver'])
            ->where('requester_id', auth()->id())
            ->latest()
            ->paginate(10);

        return view('livewire.mandor.mandor-request', compact('houses', 'materials', 'tools', 'myRequests'))
            ->layout('layouts.app', ['title' => 'Permintaan Mandor']);
    }
}

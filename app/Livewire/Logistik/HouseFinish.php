<?php

namespace App\Livewire\Logistik;

use App\Models\House;
use App\Models\Material;
use App\Models\MaterialToolRequest;
use App\Models\MaterialUsage;
use App\Models\Tool;
use App\Models\ToolReturnLog;
use App\Models\ToolUsage;
use App\Models\Warehouse;
use App\Support\ToolInventory;
use App\Traits\WithTableSorting;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class HouseFinish extends Component
{
    use WithPagination, WithTableSorting;

    public House $house;

    public $sort = 'material_asc';

    // Array of [tool_usage_id => ['action' => 'normal|broken|lost', 'notes' => '', 'replacement_cost' => '']]
    public array $toolSelections = [];

    // Confirmation Modal State
    public $showConfirmation = false;

    public $confirmingAction = '';

    public $confirmingId = null;

    public $confirmTitle = '';

    public $confirmMessage = '';

    public string $warrantyStartsOn = '';

    public string $warrantyEndsOn = '';

    public string $completionToast = '';

    public int $completionToastSequence = 0;

    public function confirm($action, $id = null, $title = '', $message = '')
    {
        if ($action === 'processCompletion' && ($error = $this->toolReturnError())) {
            $this->completionToast = $error;
            $this->completionToastSequence++;
            $this->dispatch('house-finish-invalid');

            return;
        }

        if ($action === 'processCompletion') {
            $warrantyStartsAt = now();
            $warrantyEndsAt = $warrantyStartsAt->copy()->addYear();
            $this->warrantyStartsOn = $warrantyStartsAt->format('d/m/Y');
            $this->warrantyEndsOn = $warrantyEndsAt->format('d/m/Y');
            $message = '';
        }

        $this->completionToast = '';
        $this->confirmingAction = $action;
        $this->confirmingId = $id;
        $this->confirmTitle = $title;
        $this->confirmMessage = $message;
        $this->showConfirmation = true;
    }

    private function toolReturnError($activeUsages = null): ?string
    {
        $activeUsages ??= ToolUsage::with('tool')
            ->where('house_id', $this->house->id)
            ->whereNull('return_date')
            ->whereNull('voided_at')
            ->get();

        foreach ($activeUsages as $usage) {
            $selection = $this->toolSelections[$usage->id] ?? [];
            $good = max(0, (int) ($selection['qty_good'] ?? 0));
            $broken = max(0, (int) ($selection['qty_broken'] ?? 0));
            $lost = max(0, (int) ($selection['qty_lost'] ?? 0));
            $quantity = (int) $usage->quantity;

            if ($good + $broken + $lost !== $quantity) {
                return "Lengkapi pengembalian {$usage->tool->name}: jumlah baik, rusak, dan hilang harus berjumlah {$quantity} unit.";
            }

            if (($good + $broken) > 0 && ! Warehouse::whereKey((int) ($selection['receiving_warehouse_id'] ?? 0))->exists()) {
                return "Pilih gudang penerima untuk alat {$usage->tool->name} yang dikembalikan.";
            }
        }

        return null;
    }

    public function executeConfirmedAction()
    {
        match ($this->confirmingAction) {
            'processCompletion' => $this->processCompletion(),
            default => null,
        };

        $this->showConfirmation = false;
        $this->confirmingAction = '';
        $this->confirmingId = null;
    }

    public function mount(House $house)
    {
        abort_unless($house->isAccessibleBy(auth()->user()), 403);
        $this->house = $house;

        // Redirect if already selesai
        if ($house->status === 'selesai') {
            session()->flash('error', 'Rumah ini sudah ditandai selesai.');
            $this->redirect(route('logistik.house-detail', $house));

            return;
        }

        // Pre-populate toolSelections with defaults
        $activeUsages = ToolUsage::where('house_id', $house->id)
            ->whereNull('return_date')
            ->whereNull('voided_at')
            ->get();

        foreach ($activeUsages as $usage) {
            $this->toolSelections[$usage->id] = [
                'qty_good' => 0,
                'qty_broken' => 0,
                'qty_lost' => 0,
                'receiving_warehouse_id' => ($usage->warehouse_source_recorded ? $usage->warehouse_id : null) ?? $usage->tool->warehouse_id,
                'notes' => '',
            ];
        }
    }

    public function processCompletion()
    {
        try {
            DB::transaction(function () {
                $house = House::forUser(auth()->user())->lockForUpdate()->findOrFail($this->house->id);
                if ($house->status === 'selesai') {
                    throw new \Exception('Rumah ini sudah ditandai selesai.');
                }

                $openRequests = MaterialToolRequest::where('house_id', $house->id)
                    ->whereIn('status', ['pending', 'dispatched', 'partially_arrived', 'arrived', 'resolved', 'rejected'])
                    ->lockForUpdate()
                    ->get()
                    ->filter(fn (MaterialToolRequest $request) => $request->blocksHouseCompletion());
                if ($openRequests->isNotEmpty()) {
                    throw new \Exception('Selesaikan atau batalkan permintaan material/alat yang masih terbuka sebelum rumah ditandai selesai.');
                }

                // Pre-flight check: ensure all active usages are included in selections
                $activeUsageIds = ToolUsage::where('house_id', $this->house->id)
                    ->whereNull('return_date')
                    ->whereNull('voided_at')
                    ->pluck('id')
                    ->sort()
                    ->values();

                $selectionIds = collect($this->toolSelections)->keys()->sort()->values();

                if ($activeUsageIds->diff($selectionIds)->isNotEmpty()) {
                    throw new \Exception('Semua alat yang dipinjam harus dipertanggungjawabkan sebelum menyelesaikan proyek.');
                }
                if ($selectionIds->diff($activeUsageIds)->isNotEmpty()) {
                    throw new \Exception('Pilihan alat tidak cocok dengan peminjaman aktif rumah ini. Muat ulang halaman lalu coba lagi.');
                }

                foreach ($this->toolSelections as $usageId => $sel) {
                    $usage = ToolUsage::where('house_id', $house->id)->lockForUpdate()->findOrFail($usageId);

                    // Skip if already returned (safety guard)
                    if (! is_null($usage->return_date)) {
                        continue;
                    }

                    $tool = Tool::lockForUpdate()->findOrFail($usage->tool_id);
                    $qty = $usage->quantity;
                    // Keep the old component payload readable for in-flight Livewire sessions.
                    if (isset($sel['action']) && ! array_key_exists('qty_good', $sel)) {
                        $sel['qty_good'] = ($sel['action'] === 'normal') ? $qty : 0;
                        $sel['qty_broken'] = ($sel['action'] === 'broken') ? $qty : 0;
                        $sel['qty_lost'] = ($sel['action'] === 'lost') ? $qty : 0;
                    $sel['receiving_warehouse_id'] = ($usage->warehouse_source_recorded ? $usage->warehouse_id : null) ?? $tool->warehouse_id;
                    }
                    $qtyGood = max(0, (int) ($sel['qty_good'] ?? 0));
                    $qtyBroken = max(0, (int) ($sel['qty_broken'] ?? 0));
                    $qtyLost = max(0, (int) ($sel['qty_lost'] ?? 0));
                    if ($qtyGood + $qtyBroken + $qtyLost !== (int) $qty) {
                        throw new \Exception("Jumlah kondisi {$tool->name} harus sama dengan {$qty} unit yang masih dipinjam.");
                    }

                    $receivingWarehouseId = (int) ($sel['receiving_warehouse_id'] ?? 0);
                    if (($qtyGood + $qtyBroken) > 0 && ! Warehouse::whereKey($receivingWarehouseId)->exists()) {
                        throw new \Exception("Pilih gudang penerima untuk alat {$tool->name} yang dikembalikan.");
                    }

                    // Mark usage as returned today
                    $usage->update(['return_date' => now()->format('Y-m-d')]);

                    if ($qtyGood + $qtyBroken > 0) {
                        ToolInventory::receive($tool, $receivingWarehouseId, $qtyGood, $qtyBroken);
                        if ($qtyBroken > 0) {
                            $tool->condition = 'rusak';
                        }
                    }
                    if ($qtyLost > 0) {
                        if ($tool->total_qty < $qtyLost) {
                            throw new \RuntimeException("Jumlah alat hilang {$tool->name} melebihi total alat tercatat.");
                        }
                        $tool->total_qty -= $qtyLost;
                    }
                    $tool->save();

                    if ($qtyGood > 0) {
                        ToolReturnLog::create([
                            'tool_id' => $tool->id,
                            'house_id' => $house->id,
                            'tool_usage_id' => $usage->id,
                            'reported_by' => auth()->id(),
                            'receiving_warehouse_id' => $receivingWarehouseId,
                            'received_at' => now(),
                            'received_by_id' => auth()->id(),
                            'quantity' => $qtyGood,
                            'report_type' => 'normal',
                            'status' => 'fixed',
                            'notes' => $sel['notes'] ?? null,
                        ]);
                    }
                    if ($qtyBroken > 0) {
                        ToolReturnLog::create([
                            'tool_id' => $tool->id,
                            'house_id' => $house->id,
                            'tool_usage_id' => $usage->id,
                            'reported_by' => auth()->id(),
                            'receiving_warehouse_id' => $receivingWarehouseId,
                            'received_at' => now(),
                            'received_by_id' => auth()->id(),
                            'quantity' => $qtyBroken,
                            'report_type' => 'broken',
                            'status' => 'received',
                            'notes' => $sel['notes'] ?? null,
                        ]);
                    }
                    if ($qtyLost > 0) {
                        ToolReturnLog::create([
                            'tool_id' => $tool->id,
                            'house_id' => $house->id,
                            'tool_usage_id' => $usage->id,
                            'reported_by' => auth()->id(),
                            'quantity' => $qtyLost,
                            'report_type' => 'lost',
                            'status' => 'discarded',
                            'resolved_by_id' => auth()->id(),
                            'resolved_at' => now(),
                            'notes' => $sel['notes'] ?? null,
                        ]);
                    }
                }

                // Lock the house as selesai
                $completedAt = now();
                $house->update([
                    'status' => 'selesai',
                    'completed_at' => $completedAt,
                    'warranty_expires_at' => $completedAt->copy()->addYear(),
                    'completed_by_id' => auth()->id(),
                ]);
            });

            session()->flash('success', 'Proyek berhasil diselesaikan. Semua alat telah dipertanggungjawabkan.');
            $this->redirect(route('logistik.house-detail', $this->house));

        } catch (\Exception $e) {
            $this->addError('completion', $e->getMessage());
        }
    }

    protected function sortableColumns(): array
    {
        return [
            'material' => fn ($query, $direction) => $query->orderBy(
                Material::select('name')->whereColumn('materials.id', 'material_usages.material_id'),
                $direction
            ),
            'quantity' => 'material_usages.quantity',
            'total' => 'material_usages.total_cost',
        ];
    }

    public function render()
    {
        $this->house = House::forUser(auth()->user())->findOrFail($this->house->id);
        $materialUsages = MaterialUsage::with('material')
            ->where('house_id', $this->house->id)
            ->tap(fn ($query) => $this->applyTableSort($query))
            ->orderByDesc('id')
            ->paginate(15);

        $totalMaterialCost = MaterialUsage::where('house_id', $this->house->id)
            ->whereNull('voided_at')
            ->sum('total_cost');

        $activeToolUsages = ToolUsage::with(['tool', 'warehouse'])
            ->where('house_id', $this->house->id)
            ->whereNull('return_date')
            ->whereNull('voided_at')
            ->get();
        $completionBlocked = $this->toolReturnError($activeToolUsages) !== null;

        return view('livewire.logistik.house-finish', [
            'materialUsages' => $materialUsages,
            'totalMaterialCost' => $totalMaterialCost,
            'activeToolUsages' => $activeToolUsages,
            'completionBlocked' => $completionBlocked,
            'warehouses' => Warehouse::orderBy('name')->get(['id', 'name']),
        ])->layout('layouts.app', ['title' => 'Selesaikan Proyek: '.$this->house->name]);
    }
}

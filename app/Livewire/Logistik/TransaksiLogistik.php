<?php

namespace App\Livewire\Logistik;

use App\Models\MaterialUsage as MaterialUsageModel;
use App\Models\ToolUsage as ToolUsageModel;
use App\Models\ToolReturnLog;
use App\Models\Material;
use App\Models\Tool;
use App\Models\House;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class TransaksiLogistik extends Component
{
    // Shared state
    public $house_ids = [];
    public bool $housePickerOpen = false;

    // Material section state
    public $material_id = '';
    public $material_quantity = 1;
    public $usage_date = '';
    public $material_notes = '';
    public bool $showMaterialConfirmation = false;
    public array $materialConfirmationData = [];
    public bool $materialPickerOpen = false;

    // Tool section state
    public $tool_id = '';
    public $tool_quantity = 1;
    public $checkout_date = '';
    public $tool_notes = '';
    public bool $showToolConfirmation = false;
    public array $toolConfirmationData = [];
    public bool $toolPickerOpen = false;

    // Save idempotency guard (C) — blocks re-entrant double-submit
    public bool $saving = false;

    // Return tab state
    public string $activeTab = 'material';
    public array $returnSelections = []; // Keyed by tool_usage_id
    public bool $showReturnConfirmation = false;
    public array $returnConfirmationData = [];

    use \Livewire\WithFileUploads;

    // Proof photo state
    public $proof_image;

    protected function materialRules()
    {
        return [
            'house_ids' => 'required|array|min:1',
            'house_ids.*' => 'exists:houses,id',
            'material_id' => 'required|exists:materials,id',
            'material_quantity' => 'required|numeric|min:0.01',
            'usage_date' => 'required|date',
            'material_notes' => 'required|string|max:500',
            'proof_image' => 'nullable|image|max:5120',
        ];
    }

    protected function toolRules()
    {
        return [
            'house_ids' => 'required|array|min:1',
            'house_ids.*' => 'exists:houses,id',
            'tool_id' => 'required|exists:tools,id',
            'tool_quantity' => 'required|integer|min:1',
            'checkout_date' => 'required|date',
            'tool_notes' => 'required|string|max:500',
            'proof_image' => 'nullable|image|max:5120',
        ];
    }

    public function mount()
    {
        $this->usage_date = now()->format('Y-m-d\TH:i');
        $this->checkout_date = now()->format('Y-m-d\TH:i');
    }

    public function showMaterialConfirmationModal()
    {
        $this->validate($this->materialRules());

        $material = Material::findOrFail($this->material_id);
        $houses = House::whereIn('id', $this->house_ids)->get();
        $totalQuantity = $this->material_quantity * count($this->house_ids);
        $totalCost = $totalQuantity * $material->unit_price;

        if ($material->stock < $totalQuantity) {
            $this->addError('material_quantity', 'Stok material tidak mencukupi. Total dibutuhkan: ' . $totalQuantity . ' ' . $material->unit . '. Tersedia: ' . $material->stock . ' ' . $material->unit);
            return;
        }

        $completedHouses = $houses->where('status', 'selesai');
        if ($completedHouses->isNotEmpty()) {
            $names = $completedHouses->pluck('name')->join(', ');
            $this->addError('house_ids', "Rumah berikut sudah selesai dan tidak dapat ditambahkan material: {$names}");
            return;
        }

        $this->materialConfirmationData = [
            'houses' => $houses->pluck('name')->join(', '),
            'houseCount' => count($this->house_ids),
            'materialName' => $material->name,
            'materialUnit' => $material->unit,
            'quantityPerHouse' => $this->material_quantity,
            'totalQuantity' => $totalQuantity,
            'unitPrice' => $material->unit_price,
            'totalCost' => $totalCost,
            'usageDate' => $this->usage_date,
            'notes' => $this->material_notes,
        ];

        $this->showMaterialConfirmation = true;
    }

    public function saveMaterial()
    {
        if ($this->saving) {
            return;
        }
        $this->saving = true;

        try {
            DB::transaction(function () {
                $material = Material::lockForUpdate()->findOrFail($this->material_id);
                $totalQuantityRequired = $this->material_quantity * count($this->house_ids);

                if ($material->stock < $totalQuantityRequired) {
                    throw new \Exception('Stok material tidak mencukupi. Tersedia: ' . $material->stock . ' ' . $material->unit);
                }

                $proofPath = null;
                if ($this->proof_image) {
                    $proofPath = $this->proof_image->store('proofs', 'public');
                }

                foreach ($this->house_ids as $h_id) {
                    MaterialUsageModel::create([
                        'house_id' => $h_id,
                        'material_id' => $this->material_id,
                        'user_id' => auth()->id(),
                        'quantity' => $this->material_quantity,
                        'unit_price_at_usage' => $material->unit_price,
                        'total_cost' => $this->material_quantity * $material->unit_price,
                        'usage_date' => $this->usage_date,
                        'notes' => $this->material_notes,
                        'proof_image' => $proofPath,
                    ]);
                }

                $material->decrement('stock', $totalQuantityRequired);

                session()->flash('success', 'Penggunaan material berhasil dicatat untuk ' . count($this->house_ids) . ' rumah.');
            });

            $this->showMaterialConfirmation = false;
            $this->resetMaterialForm();
            $this->usage_date = now()->format('Y-m-d\TH:i');
        } catch (\Exception $e) {
            $this->addError('material_quantity', $e->getMessage());
        } finally {
            $this->saving = false;
        }
    }

    public function showToolConfirmationModal()
    {
        $this->validate($this->toolRules());

        $tool = Tool::findOrFail($this->tool_id);
        // D2: condition is display-only now — available_qty is the single source of truth
        $houses = House::whereIn('id', $this->house_ids)->get();
        $totalQuantity = $this->tool_quantity * count($this->house_ids);

        if ($tool->available_qty < $totalQuantity) {
            $this->addError('tool_quantity', 'Jumlah alat tersedia tidak mencukupi. Total dibutuhkan: ' . $totalQuantity . '. Tersedia: ' . $tool->available_qty);
            return;
        }

        $completedHouses = $houses->where('status', 'selesai');
        if ($completedHouses->isNotEmpty()) {
            $names = $completedHouses->pluck('name')->join(', ');
            $this->addError('house_ids', "Rumah berikut sudah selesai dan tidak dapat dipinjamkan alat: {$names}");
            return;
        }

        $this->toolConfirmationData = [
            'houses' => $houses->pluck('name')->join(', '),
            'houseCount' => count($this->house_ids),
            'toolName' => $tool->name,
            'toolCode' => $tool->code ?? '-',
            'quantityPerHouse' => $this->tool_quantity,
            'totalQuantity' => $totalQuantity,
            'availableQty' => $tool->available_qty,
            'availableAfter' => $tool->available_qty - $totalQuantity,
            'checkoutDate' => $this->checkout_date,
            'notes' => $this->tool_notes,
        ];

        $this->showToolConfirmation = true;
    }

    public function saveTool()
    {
        if ($this->saving) {
            return;
        }
        $this->saving = true;

        try {
            DB::transaction(function () {
                $tool = Tool::lockForUpdate()->findOrFail($this->tool_id);
                $totalQuantityRequired = $this->tool_quantity * count($this->house_ids);

                if ($tool->available_qty < $totalQuantityRequired) {
                    throw new \Exception('Jumlah alat tersedia tidak mencukupi. Tersedia: ' . $tool->available_qty);
                }

                // C: reject duplicate active checkout for same tool + house (D5 void filter included)
                $existing = ToolUsageModel::where('tool_id', $this->tool_id)
                    ->whereIn('house_id', $this->house_ids)
                    ->whereNull('return_date')
                    ->whereNull('voided_at')
                    ->pluck('house_id');
                if ($existing->isNotEmpty()) {
                    throw new \Exception('Alat ini masih dipinjam oleh rumah: ' . House::whereIn('id', $existing)->pluck('name')->join(', '));
                }

                $proofPath = null;
                if ($this->proof_image) {
                    $proofPath = $this->proof_image->store('proofs', 'public');
                }

                foreach ($this->house_ids as $h_id) {
                    ToolUsageModel::create([
                        'house_id' => $h_id,
                        'tool_id' => $this->tool_id,
                        'user_id' => auth()->id(),
                        'quantity' => $this->tool_quantity,
                        'checkout_date' => $this->checkout_date,
                        'notes' => $this->tool_notes,
                        'proof_image' => $proofPath,
                    ]);
                }

                // A3: always reserve — no return_date escape hatch
                $tool->decrement('available_qty', $totalQuantityRequired);

                session()->flash('success', 'Penggunaan alat berhasil dicatat untuk ' . count($this->house_ids) . ' rumah.');
            });

            $this->showToolConfirmation = false;
            $this->resetToolForm();
            $this->checkout_date = now()->format('Y-m-d\TH:i');
        } catch (\Exception $e) {
            $this->addError('tool_quantity', $e->getMessage());
        } finally {
            $this->saving = false;
        }
    }

    public function resetMaterialForm()
    {
        $this->material_id = '';
        $this->material_quantity = 1;
        $this->usage_date = now()->format('Y-m-d\TH:i');
        $this->material_notes = '';
        $this->proof_image = null;
        $this->materialPickerOpen = false;
        $this->showMaterialConfirmation = false;
        $this->materialConfirmationData = [];
        $this->resetValidation();
    }

    public function resetToolForm()
    {
        $this->tool_id = '';
        $this->tool_quantity = 1;
        $this->checkout_date = now()->format('Y-m-d\TH:i');
        $this->tool_notes = '';
        $this->proof_image = null;
        $this->showToolConfirmation = false;
        $this->toolConfirmationData = [];
        $this->toolPickerOpen = false;
        $this->resetValidation();
    }

    public function getActiveToolUsages()
    {
        if (empty($this->house_ids)) {
            return collect();
        }

        return ToolUsageModel::with(['house', 'tool'])
            ->whereIn('house_id', $this->house_ids)
            ->whereNull('return_date')
            ->whereNull('voided_at')
            ->get();
    }

    public function showReturnConfirmationModal()
    {
        $activeUsages = $this->getActiveToolUsages();
        $selectedItems = [];

        foreach ($this->returnSelections as $usageId => $sel) {
            if (empty($sel['selected'])) {
                continue;
            }

            $usage = $activeUsages->firstWhere('id', $usageId);
            if (!$usage) {
                continue;
            }

            $qtyNormal = intval($sel['qty_normal'] ?? 0);
            $qtyBroken = intval($sel['qty_broken'] ?? 0);
            $qtyLost   = intval($sel['qty_lost'] ?? 0);
            $total = $qtyNormal + $qtyBroken + $qtyLost;

            if ($total <= 0) {
                continue; // Nothing to return for this selection
            }

            if ($total > $usage->quantity) {
                $this->addError('returnSelections', 'Jumlah kondisi untuk ' . $usage->tool->name . ' melebihi jumlah dipinjam (' . $usage->quantity . ').');
                return;
            }

            if ($qtyNormal < 0 || $qtyBroken < 0 || $qtyLost < 0) {
                $this->addError('returnSelections', 'Jumlah kondisi tidak boleh negatif untuk ' . $usage->tool->name . '.');
                return;
            }

            $selectedItems[] = [
                'usage_id' => $usage->id,
                'house_name' => $usage->house->name,
                'tool_name' => $usage->tool->name,
                'quantity' => $usage->quantity,
                'return_qty' => $total,
                'qty_normal' => $qtyNormal,
                'qty_broken' => $qtyBroken,
                'qty_lost' => $qtyLost,
                'notes' => $sel['notes'] ?? '',
            ];
        }

        if (empty($selectedItems)) {
            $this->addError('returnSelections', 'Pilih minimal satu alat untuk dikembalikan.');
            return;
        }

        $this->returnConfirmationData = $selectedItems;
        $this->showReturnConfirmation = true;
    }

    public function saveReturn()
    {
        if ($this->saving) {
            return;
        }
        $this->saving = true;

        try {
            DB::transaction(function () {
                foreach ($this->returnConfirmationData as $item) {
                    $usage = ToolUsageModel::lockForUpdate()->findOrFail($item['usage_id']);

                    // Skip if already returned
                    if (!is_null($usage->return_date)) {
                        continue;
                    }

                    $tool = Tool::lockForUpdate()->findOrFail($usage->tool_id);
                    $qtyNormal = $item['qty_normal'];
                    $qtyBroken = $item['qty_broken'];
                    $qtyLost   = $item['qty_lost'];
                    $returnQty = $qtyNormal + $qtyBroken + $qtyLost;
                    $remainingQty = $usage->quantity - $returnQty;

                    // Full return: mark usage as returned
                    if ($remainingQty === 0) {
                        $usage->update(['return_date' => now()->format('Y-m-d')]);
                    } else {
                        // Partial return: reduce original usage, create new usage for remainder
                        $usage->update(['quantity' => $returnQty, 'return_date' => now()->format('Y-m-d')]);

                        ToolUsageModel::create([
                            'house_id' => $usage->house_id,
                            'tool_id' => $usage->tool_id,
                            'user_id' => $usage->user_id,
                            'quantity' => $remainingQty,
                            'checkout_date' => $usage->checkout_date,
                            'return_date' => null,
                            // A4: lineage — remainder points back to the (returned) parent row
                            'parent_usage_id' => $usage->id,
                            'notes' => $usage->notes,
                        ]);
                    }

                    // Normal: return to available (ceiling guard)
                    if ($qtyNormal > 0) {
                        $tool->available_qty = min($tool->available_qty + $qtyNormal, $tool->total_qty);
                    }

                    // A2: broken moves to the broken pool — available UNCHANGED, condition = rusak (display)
                    if ($qtyBroken > 0) {
                        $tool->qty_broken = min($tool->qty_broken + $qtyBroken, $tool->total_qty);
                        $tool->condition = 'rusak';

                        ToolReturnLog::create([
                            'tool_id' => $tool->id,
                            'house_id' => $usage->house_id,
                            'tool_usage_id' => $usage->id,
                            'reported_by' => auth()->id(),
                            'quantity' => $qtyBroken,
                            'report_type' => 'broken',
                            'status' => 'discarded',
                            'notes' => $item['notes'] ?: null,
                        ]);
                    }

                    // Lost: decrement total_qty (floor guard) + log
                    if ($qtyLost > 0) {
                        $tool->total_qty = max(0, $tool->total_qty - $qtyLost);

                        ToolReturnLog::create([
                            'tool_id' => $tool->id,
                            'house_id' => $usage->house_id,
                            'tool_usage_id' => $usage->id,
                            'reported_by' => auth()->id(),
                            'quantity' => $qtyLost,
                            'report_type' => 'lost',
                            'status' => 'discarded',
                            'notes' => $item['notes'] ?: null,
                        ]);
                    }

                    $tool->save();
                }

                session()->flash('success', 'Pengembalian alat berhasil dicatat.');
            });

            $this->showReturnConfirmation = false;
            $this->resetReturnForm();
        } catch (\Exception $e) {
            $this->addError('returnSelections', $e->getMessage());
        } finally {
            $this->saving = false;
        }
    }

    public function resetReturnForm()
    {
        $this->returnSelections = [];
        $this->showReturnConfirmation = false;
        $this->returnConfirmationData = [];
        $this->resetValidation();
    }

    public function toggleHouse($id)
    {
        $id = (int) $id;
        if (in_array($id, $this->house_ids)) {
            $this->house_ids = array_values(array_diff($this->house_ids, [$id]));
        } else {
            $this->house_ids[] = $id;
        }
    }

    public function toggleBlock(array $blockIds)
    {
        $blockIds = array_map('intval', $blockIds);
        $selectedInBlock = array_intersect($blockIds, $this->house_ids);
        if (count($selectedInBlock) === count($blockIds)) {
            $this->house_ids = array_values(array_diff($this->house_ids, $blockIds));
        } else {
            $this->house_ids = array_values(array_unique(array_merge($this->house_ids, $blockIds)));
        }
    }

    public function clearHouses()
    {
        $this->house_ids = [];
    }

    public function resetAll()
    {
        $this->house_ids = [];
        $this->housePickerOpen = false;
        $this->resetMaterialForm();
        $this->resetToolForm();
        $this->resetReturnForm();
    }

    // C: all 10 dead dispatch handlers removed (HEAD L429-477):
    // updatedMaterialQuantity, updatedMaterialId, updatedHouseIds, updatedMaterialNotes,
    // updatedUsageDate, updatedToolId, updatedToolQuantity, updatedToolNotes,
    // updatedCheckoutDate, updatedReturnDate — fired cost-calculated / tool-info-updated
    // with zero listeners; also made every keystroke on .live fields a network round-trip.

    public function getHouses()
    {
        return House::where('status', '!=', 'selesai')->orderBy('name')->get();
    }

    public function getMaterials()
    {
        return Material::where('stock', '>', 0)->orderBy('name')->get();
    }

    public function getTools()
    {
        // D2: available_qty is the only gate — condition is display-only now
        return Tool::where('available_qty', '>', 0)->orderBy('name')->get();
    }

    public function render()
    {
        $houses = $this->getHouses();
        $materials = $this->getMaterials();
        $tools = $this->getTools();

        // Only query active tool usages when on the return tab
        $activeUsages = collect();
        if ($this->activeTab === 'return') {
            $activeUsages = $this->getActiveToolUsages();

            // C: prune stale selections (usages returned/voided since last render)
            $activeIds = $activeUsages->pluck('id')->map(fn ($id) => (int) $id)->all();
            foreach (array_keys($this->returnSelections) as $usageId) {
                if (!in_array((int) $usageId, $activeIds, true)) {
                    unset($this->returnSelections[$usageId]);
                }
            }

            // C: default qty_normal to 0 (operator opts in per row)
            foreach ($activeUsages as $usage) {
                if (!isset($this->returnSelections[$usage->id])) {
                    $this->returnSelections[$usage->id] = [
                        'selected' => false,
                        'qty_normal' => 0,
                        'qty_broken' => 0,
                        'qty_lost' => 0,
                        'notes' => '',
                    ];
                }
            }
        }

        $matNotes = MaterialUsageModel::whereNotNull('notes')->where('notes', '!=', '')->pluck('notes');
        $toolNotes = ToolUsageModel::whereNotNull('notes')->where('notes', '!=', '')->pluck('notes');
        $peruntukkanOptions = $matNotes->merge($toolNotes)->unique()->filter()->values()->all();

        return view('livewire.logistik.transaksi-logistik', compact('houses', 'materials', 'tools', 'activeUsages', 'peruntukkanOptions'))
            ->layout('layouts.app', ['title' => 'Transaksi Logistik']);
    }
}

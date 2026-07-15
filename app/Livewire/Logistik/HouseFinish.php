<?php

namespace App\Livewire\Logistik;

use App\Models\House;
use App\Models\Tool;
use App\Models\ToolUsage;
use App\Models\ToolReturnLog;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class HouseFinish extends Component
{
    use WithPagination;

    public House $house;

    // Array of [tool_usage_id => ['action' => 'normal|broken|lost', 'notes' => '', 'replacement_cost' => '']]
    public array $toolSelections = [];

    // Confirmation Modal State
    public $showConfirmation = false;
    public $confirmingAction = '';
    public $confirmingId = null;
    public $confirmTitle = '';
    public $confirmMessage = '';

    public function confirm($action, $id = null, $title = '', $message = '')
    {
        $this->confirmingAction = $action;
        $this->confirmingId = $id;
        $this->confirmTitle = $title;
        $this->confirmMessage = $message;
        $this->showConfirmation = true;
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
            ->get();

        foreach ($activeUsages as $usage) {
            $this->toolSelections[$usage->id] = [
                'action' => 'normal',
                'notes' => '',
                'replacement_cost' => '',
                'has_charge' => false,
            ];
        }
    }

    public function processCompletion()
    {
        // Validate all selections have an action
        foreach ($this->toolSelections as $usageId => $sel) {
            if (empty($sel['action'])) {
                $this->addError('toolSelections', 'Semua alat harus ditetapkan statusnya sebelum menyelesaikan proyek.');
                return;
            }
        }

        try {
            DB::transaction(function () {
                // Pre-flight check: ensure all active usages are included in selections
                $activeUsageIds = ToolUsage::where('house_id', $this->house->id)
                    ->whereNull('return_date')
                    ->pluck('id')
                    ->sort()
                    ->values();

                $selectionIds = collect($this->toolSelections)->keys()->sort()->values();

                if ($activeUsageIds->diff($selectionIds)->isNotEmpty()) {
                    throw new \Exception('Semua alat yang dipinjam harus dipertanggungjawabkan sebelum menyelesaikan proyek.');
                }

                foreach ($this->toolSelections as $usageId => $sel) {
                    $usage = ToolUsage::lockForUpdate()->findOrFail($usageId);

                    // Skip if already returned (safety guard)
                    if (!is_null($usage->return_date)) {
                        continue;
                    }

                    $tool = Tool::lockForUpdate()->findOrFail($usage->tool_id);
                    $action = $sel['action'];
                    $qty = $usage->quantity;

                    // Mark usage as returned today
                    $usage->update(['return_date' => now()->format('Y-m-d')]);

                    if ($action === 'normal') {
                        // Return to available — enforce ceiling (Bam's ceiling guard)
                        $tool->available_qty = min($tool->available_qty + $qty, $tool->total_qty);
                        $tool->save();

                    } elseif ($action === 'broken') {
                        // Return to stock (available) but mark condition as rusak
                        $tool->condition = 'rusak';
                        $tool->available_qty = min($tool->available_qty + $qty, $tool->total_qty);
                        $tool->save();

                        $hasCharge = $sel['has_charge'] ?? false;
                        $replacementCost = $hasCharge && is_numeric($sel['replacement_cost'] ?? '') ? $sel['replacement_cost'] : null;

                        ToolReturnLog::create([
                            'tool_id' => $tool->id,
                            'house_id' => $this->house->id,
                            'tool_usage_id' => $usage->id,
                            'reported_by' => auth()->id(),
                            'quantity' => $qty,
                            'report_type' => 'broken',
                            'status' => 'discarded',
                            'replacement_cost' => $replacementCost,
                            'notes' => $sel['notes'] ?? null,
                        ]);

                    } elseif ($action === 'lost') {
                        // Permanently decrement total_qty (and ensure it doesn't go below 0)
                        $tool->total_qty = max(0, $tool->total_qty - $qty);
                        $tool->save();

                        $hasCharge = $sel['has_charge'] ?? false;
                        $replacementCost = $hasCharge && is_numeric($sel['replacement_cost'] ?? '') ? $sel['replacement_cost'] : null;

                        ToolReturnLog::create([
                            'tool_id' => $tool->id,
                            'house_id' => $this->house->id,
                            'tool_usage_id' => $usage->id,
                            'reported_by' => auth()->id(),
                            'quantity' => $qty,
                            'report_type' => 'lost',
                            'status' => 'discarded',
                            'replacement_cost' => $replacementCost,
                            'notes' => $sel['notes'] ?? null,
                        ]);
                    }
                }

                // Lock the house as selesai
                $this->house->update(['status' => 'selesai']);
            });

            session()->flash('success', 'Proyek berhasil diselesaikan. Semua alat telah dipertanggungjawabkan.');
            $this->redirect(route('logistik.house-detail', $this->house));

        } catch (\Exception $e) {
            $this->addError('completion', $e->getMessage());
        }
    }

    public function render()
    {
        $materialUsages = \App\Models\MaterialUsage::with('material')
            ->where('house_id', $this->house->id)
            ->orderByDesc('usage_date')
            ->orderByDesc('id')
            ->paginate(15);

        $totalMaterialCost = \App\Models\MaterialUsage::where('house_id', $this->house->id)
            ->sum('total_cost');

        $activeToolUsages = ToolUsage::with(['tool'])
            ->where('house_id', $this->house->id)
            ->whereNull('return_date')
            ->get();

        return view('livewire.logistik.house-finish', [
            'materialUsages' => $materialUsages,
            'totalMaterialCost' => $totalMaterialCost,
            'activeToolUsages' => $activeToolUsages,
        ])->layout('layouts.app', ['title' => 'Selesaikan Proyek — ' . $this->house->name]);
    }
}

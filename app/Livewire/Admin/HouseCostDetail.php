<?php

namespace App\Livewire\Admin;

use App\Exports\MaterialUsageExport;
use App\Models\Category;
use App\Models\DispatchResolutionEvent;
use App\Models\House;
use App\Models\Material;
use App\Models\MaterialUsage;
use App\Models\User;
use App\Traits\WithTableSorting;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class HouseCostDetail extends Component
{
    use WithPagination, WithTableSorting;

    public House $house;

    public $sort = 'date_desc';

    public string $search = '';

    public string $selectedCategory = '';

    // Budget modal (Admin & Logistik)
    public bool $showBudgetModal = false;

    public $budgetAmount = '';

    public $copyBudgetHouseId = '';

    public function mount(House $house)
    {
        abort_unless($house->isAccessibleBy(auth()->user()), 403);
        $this->house = $house;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSelectedCategory(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->selectedCategory = '';
        $this->resetPage();
    }

    public function openBudgetModal(): void
    {
        abort_if($this->house->isPublicWork(), 403);
        abort_unless(in_array(auth()->user()->role, ['admin', 'logistik']), 403);
        $this->budgetAmount = $this->house->material_budget ? (string) $this->house->material_budget : '';
        $this->copyBudgetHouseId = '';
        $this->resetValidation('budgetAmount');
        $this->showBudgetModal = true;
    }

    public function updatedCopyBudgetHouseId($value): void
    {
        abort_if($this->house->isPublicWork(), 403);
        abort_unless(in_array(auth()->user()->role, ['admin', 'logistik']), 403);
        if ($value && ($source = House::forUser(auth()->user())->find($value))) {
            $this->budgetAmount = $source->material_budget ? (string) $source->material_budget : '';
        }
    }

    public function saveMaterialBudget(): void
    {
        abort_if($this->house->isPublicWork(), 403);
        abort_unless(in_array(auth()->user()->role, ['admin', 'logistik']), 403);
        $this->validate([
            'budgetAmount' => 'nullable|numeric|min:0|max:999999999999',
        ], [
            'budgetAmount.numeric' => 'Anggaran material harus berupa angka valid.',
            'budgetAmount.min' => 'Anggaran material tidak boleh kurang dari 0.',
        ]);

        $budget = $this->budgetAmount !== '' && $this->budgetAmount !== null ? (float) $this->budgetAmount : null;
        $this->house->update(['material_budget' => $budget]);
        $this->house->refresh();
        $this->showBudgetModal = false;
        session()->flash('success', 'Anggaran material (RAP) berhasil disimpan.');
    }

    protected function sortableColumns(): array
    {
        return [
            'date' => 'material_usages.usage_date',
            'material' => fn ($query, $direction) => $query->orderBy(
                Material::select('name')->whereColumn('materials.id', 'material_usages.material_id'),
                $direction
            ),
            'quantity' => 'material_usages.quantity',
            'unit_price' => 'material_usages.unit_price_at_usage',
            'total' => 'material_usages.total_cost',
            'user' => fn ($query, $direction) => $query->orderBy(
                User::select('name')->whereColumn('users.id', 'material_usages.user_id'),
                $direction
            ),
            'notes' => 'material_usages.notes',
        ];
    }

    public function render()
    {
        abort_unless($this->house->isAccessibleBy(auth()->user()), 403);

        $materialUsages = MaterialUsage::with(['material.category', 'user'])
            ->where('house_id', $this->house->id)
            ->whereNull('voided_at')
            ->when($this->search, fn ($q) => $q->where(function ($sub) {
                $sub->whereHas('material', fn ($m) => $m->where('name', 'like', "%{$this->search}%"))
                    ->orWhere('notes', 'like', "%{$this->search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$this->search}%"));
            }))
            ->when($this->selectedCategory, fn ($q) => $q->whereHas('material', fn ($m) => $m->where('category_id', $this->selectedCategory)))
            ->tap(fn ($query) => $this->applyTableSort($query))
            ->orderByDesc('material_usages.id')
            ->paginate(15);

        $totalCost = (float) MaterialUsage::where('house_id', $this->house->id)->whereNull('voided_at')->sum('total_cost');
        $totalUsageCount = MaterialUsage::where('house_id', $this->house->id)->whereNull('voided_at')->count();

        $materialLosses = DispatchResolutionEvent::with(['request.material', 'recordedBy', 'dispatchLine.stockIn'])
            ->whereIn('event_type', ['material_damage_loss', 'declare_lost'])
            ->whereHas('request', fn ($query) => $query->where('house_id', $this->house->id))
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->paginate(15, ['*'], 'lossPage');
        $totalMaterialLoss = (float) DispatchResolutionEvent::whereIn('event_type', ['material_damage_loss', 'declare_lost'])
            ->whereHas('request', fn ($query) => $query->where('house_id', $this->house->id))
            ->sum('total_cost');

        // Cost by category
        $costByCategory = MaterialUsage::where('house_id', $this->house->id)
            ->whereNull('material_usages.voided_at')
            ->join('materials', 'material_usages.material_id', '=', 'materials.id')
            ->join('categories', 'materials.category_id', '=', 'categories.id')
            ->selectRaw('categories.id, categories.name as category_name, SUM(material_usages.total_cost) as total, COUNT(material_usages.id) as item_count')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total')
            ->get();

        $allCategories = Category::orderBy('name')->get(['id', 'name']);

        $clusterHouses = ! $this->house->isPublicWork()
            ? House::forUser(auth()->user())
                ->where('id', '!=', $this->house->id)
                ->when($this->house->cluster_id, fn ($q) => $q->where('cluster_id', $this->house->cluster_id))
                ->where('category', House::CATEGORY_HOUSE)
                ->whereNotNull('material_budget')
                ->orderBy('name')
                ->get(['id', 'name', 'material_budget'])
            : collect();

        return view('livewire.admin.house-cost-detail', compact(
            'materialUsages',
            'totalCost',
            'totalUsageCount',
            'costByCategory',
            'allCategories',
            'materialLosses',
            'totalMaterialLoss',
            'clusterHouses'
        ))->layout('layouts.app', ['title' => 'Detail Biaya - ' . $this->house->name]);
    }

    public function exportExcel()
    {
        abort_unless($this->house->isAccessibleBy(auth()->user()), 403);

        $export = new MaterialUsageExport($this->house->id);
        $filename = 'biaya-rumah-' . $this->house->house_code . '-' . now()->format('Ymd') . '.xlsx';

        return response()->streamDownload(function () use ($export) {
            echo Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);
        }, $filename);
    }
}

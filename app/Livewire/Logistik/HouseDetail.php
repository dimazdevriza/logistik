<?php

namespace App\Livewire\Logistik;

use App\Models\House;
use App\Models\ClusterExpense;
use App\Models\Material;
use App\Models\MaterialUsage;
use App\Models\Tool;
use App\Models\ToolUsage;
use App\Models\User;
use App\Traits\WithTableSorting;
use App\Exports\HouseExport;
use App\Exports\MaterialUsageExport;
use App\Exports\ToolUsageExport;
use Maatwebsite\Excel\Facades\Excel;
use Livewire\Component;
use Livewire\WithPagination;

class HouseDetail extends Component
{
    use WithPagination, WithTableSorting;

    public House $house;

    // View state
    public $activeTab = 'material'; // material or tool
    public $sort = 'date_desc';
    public string $search = '';

    public string $vendorServiceSearch = '';

    public string $vendorRentalSearch = '';

    // Budget modal (Logistik / Admin)
    public bool $showBudgetModal = false;
    public $budgetAmount = '';
    public $copyBudgetHouseId = '';

    // Stages modal (Pengawas / Admin)
    public bool $showStageModal = false;
    public array $editableStages = [];
    public $copyStageHouseId = '';

    public function mount(House $house)
    {
        $user = auth()->user();
        abort_unless($user && ($user->role === 'logistik' || $house->isAccessibleBy($user)), 403);
        $this->house = $house;
    }

    public function updatingActiveTab()
    {
        $this->sort = 'date_desc';
        $this->resetPage('materialPage');
        $this->resetPage('toolPage');
        $this->resetPage('warrantyMaterialPage');
        $this->resetPage('warrantyToolPage');
    }

    public function updatingSearch()
    {
        $this->resetPage('materialPage');
        $this->resetPage('toolPage');
        $this->resetPage('warrantyMaterialPage');
        $this->resetPage('warrantyToolPage');
    }

    public function updatingVendorServiceSearch(): void
    {
        $this->resetPage('vendorServicePage');
    }

    public function updatingVendorRentalSearch(): void
    {
        $this->resetPage('vendorRentalPage');
    }

    protected function sortableColumns(): array
    {
        if (str_contains($this->activeTab, 'tool')) {
            return [
                'date' => 'tool_usages.checkout_date',
                'tool' => fn ($query, $direction) => $query->orderBy(
                    Tool::select('name')->whereColumn('tools.id', 'tool_usages.tool_id'),
                    $direction
                ),
                'quantity' => 'tool_usages.quantity',
                'status' => 'tool_usages.return_date',
                'user' => fn ($query, $direction) => $query->orderBy(
                    User::select('name')->whereColumn('users.id', 'tool_usages.user_id'),
                    $direction
                ),
            ];
        }

        return [
            'date' => 'material_usages.usage_date',
            'notes' => 'material_usages.notes',
            'code' => fn ($query, $direction) => $query->orderBy(
                Material::select('code')->whereColumn('materials.id', 'material_usages.material_id'),
                $direction
            ),
            'material' => fn ($query, $direction) => $query->orderBy(
                Material::select('name')->whereColumn('materials.id', 'material_usages.material_id'),
                $direction
            ),
            'quantity' => 'material_usages.quantity',
            'unit' => fn ($query, $direction) => $query->orderBy(
                Material::select('unit')->whereColumn('materials.id', 'material_usages.material_id'),
                $direction
            ),
            'unit_price' => 'material_usages.unit_price_at_usage',
            'total' => 'material_usages.total_cost',
            'user' => fn ($query, $direction) => $query->orderBy(
                User::select('name')->whereColumn('users.id', 'material_usages.user_id'),
                $direction
            ),
        ];
    }

    public function render()
    {
        $user = auth()->user();
        $this->house = ($user && $user->role === 'logistik' ? House::query() : House::forUser($user))->findOrFail($this->house->id);
        $materialUsages = null;
        $toolUsages = null;

        $materialCount = $this->house->materialUsages()->whereNull('voided_at')->where('is_warranty', false)->count();
        $warrantyMaterialCount = $this->house->materialUsages()->whereNull('voided_at')->where('is_warranty', true)->count();
        $warrantyMaterialCost = (float) $this->house->materialUsages()
            ->whereNull('voided_at')
            ->where('is_warranty', true)
            ->sum('total_cost');
        $toolCount = $this->house->toolUsages()->whereNull('voided_at')->where('is_warranty', false)->count();
        $warrantyToolCount = $this->house->toolUsages()->whereNull('voided_at')->where('is_warranty', true)->count();
        $vendorServiceQuery = ClusterExpense::query()
            ->with(['house:id,name,house_code', 'houses:id,name,house_code', 'createdBy:id,name'])
            ->where('type', 'vendor_service')
            ->where(fn ($query) => $query->where('house_id', $this->house->id)
                ->orWhereHas('houses', fn ($houses) => $houses->whereKey($this->house->id)));
        $vendorServiceCount = (clone $vendorServiceQuery)->count();
        $vendorServiceExpenses = $vendorServiceQuery
            ->when(trim($this->vendorServiceSearch) !== '', function ($query) {
                $search = '%'.trim($this->vendorServiceSearch).'%';
                $query->where(fn ($query) => $query
                    ->where('description', 'like', $search)
                    ->orWhere('vendor', 'like', $search)
                    ->orWhere('notes', 'like', $search));
            })
            ->orderByDesc('start_date')->orderByDesc('id')
            ->paginate(10, ['*'], 'vendorServicePage');
        $vendorRentalQuery = ClusterExpense::query()
            ->with(['house:id,name,house_code', 'houses:id,name,house_code', 'createdBy:id,name'])
            ->whereIn('type', ['rental', 'rental_extension'])
            ->where(fn ($query) => $query->where('house_id', $this->house->id)
                ->orWhereHas('houses', fn ($houses) => $houses->whereKey($this->house->id)));
        $vendorRentalCount = (clone $vendorRentalQuery)->count();
        $vendorRentalExpenses = $vendorRentalQuery
            ->when(trim($this->vendorRentalSearch) !== '', function ($query) {
                $search = '%'.trim($this->vendorRentalSearch).'%';
                $query->where(fn ($query) => $query
                    ->where('description', 'like', $search)
                    ->orWhere('vendor', 'like', $search)
                    ->orWhere('notes', 'like', $search));
            })
            ->orderByDesc('start_date')->orderByDesc('id')
            ->paginate(10, ['*'], 'vendorRentalPage');

        if (in_array($this->activeTab, ['material', 'warranty-material'], true)) {
            $isWarranty = str_starts_with($this->activeTab, 'warranty-');
            $materialUsages = MaterialUsage::with(['material', 'user'])
                ->where('house_id', $this->house->id)
                ->whereNull('voided_at')
                ->where('is_warranty', $isWarranty)
                ->when(trim($this->search) !== '', function ($query) {
                    $search = '%' . trim($this->search) . '%';
                    $query->where(fn ($query) => $query
                        ->where('notes', 'like', $search)
                        ->orWhereHas('material', fn ($material) => $material
                            ->where('name', 'like', $search)
                            ->orWhere('code', 'like', $search)
                            ->orWhere('unit', 'like', $search))
                        ->orWhereHas('user', fn ($user) => $user->where('name', 'like', $search)));
                })
                ->tap(fn ($query) => $this->applyTableSort($query))
                ->orderByDesc('id')
                ->paginate(15, ['*'], $isWarranty ? 'warrantyMaterialPage' : 'materialPage');
        } elseif (in_array($this->activeTab, ['tool', 'warranty-tool'], true)) {
            $isWarranty = str_starts_with($this->activeTab, 'warranty-');
            $toolUsages = ToolUsage::with(['tool', 'user'])
                ->where('house_id', $this->house->id)
                ->whereNull('voided_at')
                ->where('is_warranty', $isWarranty)
                ->when(trim($this->search) !== '', function ($query) {
                    $search = '%' . trim($this->search) . '%';
                    $query->where(function ($query) use ($search) {
                        $query->where('notes', 'like', $search)
                            ->orWhereHas('tool', fn ($tool) => $tool
                                ->where('name', 'like', $search)
                                ->orWhere('code', 'like', $search))
                            ->orWhereHas('user', fn ($user) => $user->where('name', 'like', $search));

                        $normalizedSearch = mb_strtolower(trim($this->search));
                        if ($normalizedSearch === 'dipinjam') {
                            $query->orWhereNull('return_date');
                        }
                        if (in_array($normalizedSearch, ['dikembalikan', 'kembali'], true)) {
                            $query->orWhereNotNull('return_date');
                        }
                    });
                })
                ->tap(fn ($query) => $this->applyTableSort($query))
                ->orderByDesc('id')
                ->paginate(15, ['*'], $isWarranty ? 'warrantyToolPage' : 'toolPage');
        }

        $user = auth()->user();
        $otherHouses = ($this->showBudgetModal || $this->showStageModal)
            ? ($user->role === 'logistik' ? House::query() : House::forUser($user))
                ->where('id', '!=', $this->house->id)
                ->where('category', $this->house->category)
                ->orderBy('name')
                ->get(['id', 'name', 'house_code', 'material_budget'])
            : collect();

        return view('livewire.logistik.house-detail', [
            'materialUsages' => $materialUsages,
            'toolUsages' => $toolUsages,
            'materialCount' => $materialCount,
            'warrantyMaterialCount' => $warrantyMaterialCount,
            'warrantyMaterialCost' => $warrantyMaterialCost,
            'toolCount' => $toolCount,
            'warrantyToolCount' => $warrantyToolCount,
            'vendorServiceCount' => $vendorServiceCount,
            'vendorServiceExpenses' => $vendorServiceExpenses,
            'vendorRentalCount' => $vendorRentalCount,
            'vendorRentalExpenses' => $vendorRentalExpenses,
            'otherHouses' => $otherHouses,
        ])->layout('layouts.app', ['title' => ($this->house->isPublicWork() ? 'Detail Pekerjaan Umum: ' : 'Detail Rumah: ') . $this->house->name]);
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
        abort_unless(in_array(auth()->user()->role, ['admin', 'logistik']), 403);
        if ($value && ($source = House::find($value))) {
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
        $this->showBudgetModal = false;
        session()->flash('success', 'Anggaran material (RAP) berhasil disimpan.');
    }

    public function toggleStage(int $index): void
    {
        abort_if($this->house->isPublicWork(), 403);
        abort_unless(in_array(auth()->user()->role, ['admin', 'pengawas']), 403);
        if ($this->house->status === 'selesai') {
            return;
        }

        $stages = $this->house->resolvedProgressStages();
        if (isset($stages[$index])) {
            $stages[$index]['completed'] = ! ($stages[$index]['completed'] ?? false);
            $progress = House::calculatePhysicalProgress($stages);
            $this->house->update([
                'progress_stages' => $stages,
                'physical_progress' => $progress,
            ]);
        }
    }

    public function openStageModal(): void
    {
        abort_if($this->house->isPublicWork(), 403);
        abort_unless(in_array(auth()->user()->role, ['admin', 'pengawas']), 403);
        if ($this->house->status === 'selesai') {
            return;
        }

        $this->editableStages = $this->house->resolvedProgressStages();
        $this->copyStageHouseId = '';
        $this->resetValidation('editableStages');
        $this->showStageModal = true;
    }

    public function addStageRow(): void
    {
        abort_unless(in_array(auth()->user()->role, ['admin', 'pengawas']), 403);
        $this->editableStages[] = ['name' => '', 'weight' => 0, 'completed' => false];
    }

    public function removeStageRow(int $index): void
    {
        abort_unless(in_array(auth()->user()->role, ['admin', 'pengawas']), 403);
        if (isset($this->editableStages[$index])) {
            unset($this->editableStages[$index]);
            $this->editableStages = array_values($this->editableStages);
        }
    }

    public function resetDefaultStages(): void
    {
        abort_unless(in_array(auth()->user()->role, ['admin', 'pengawas']), 403);
        $existingCompleted = collect($this->house->resolvedProgressStages())
            ->pluck('completed', 'name')
            ->all();
        $this->editableStages = array_map(fn (array $stage) => [
            'name' => $stage['name'],
            'weight' => $stage['weight'],
            'completed' => (bool) ($existingCompleted[$stage['name']] ?? false),
        ], House::defaultProgressStages($this->house->category));
    }

    public function updatedCopyStageHouseId($value): void
    {
        abort_unless(in_array(auth()->user()->role, ['admin', 'pengawas']), 403);
        if ($value && ($source = House::forUser(auth()->user())->find($value))) {
            $existingCompleted = collect($this->house->resolvedProgressStages())
                ->pluck('completed', 'name')
                ->all();
            $this->editableStages = array_map(fn (array $stage) => [
                'name' => $stage['name'],
                'weight' => $stage['weight'],
                'completed' => (bool) ($existingCompleted[$stage['name']] ?? false),
            ], $source->resolvedProgressStages());
        }
    }

    public function saveStages(): void
    {
        abort_if($this->house->isPublicWork(), 403);
        abort_unless(in_array(auth()->user()->role, ['admin', 'pengawas']), 403);
        if ($this->house->status === 'selesai') {
            return;
        }

        $this->validate([
            'editableStages' => 'required|array|min:1',
            'editableStages.*.name' => 'required|string|max:150',
            'editableStages.*.weight' => 'required|numeric|min:0.01|max:100',
        ], [
            'editableStages.required' => 'Daftar tahapan tidak boleh kosong.',
            'editableStages.*.name.required' => 'Nama tahapan wajib diisi.',
            'editableStages.*.weight.required' => 'Bobot tahapan wajib diisi.',
            'editableStages.*.weight.min' => 'Bobot tahapan minimal 0.01%.',
        ]);

        $totalWeight = (float) array_sum(array_column($this->editableStages, 'weight'));
        if (abs($totalWeight - 100.0) > 0.01) {
            $this->addError('editableStages', "Total bobot tahapan harus tepat 100% (total saat ini: " . number_format($totalWeight, 1, ',', '.') . "%).");
            return;
        }

        $progress = House::calculatePhysicalProgress($this->editableStages);
        $this->house->update([
            'progress_stages' => $this->editableStages,
            'physical_progress' => $progress,
        ]);

        $this->showStageModal = false;
        session()->flash('success', 'Tahapan pekerjaan dan bobot berhasil diperbarui.');
    }

    public function exportExcel()
    {
        // Only roles with house-wide access or the assigned-cluster logistics user can export.
        if (!in_array(auth()->user()->role, ['admin', 'logistik', 'pengawas'])) {
            return;
        }

        $export = new HouseExport($this->house->id);
        $filename = 'laporan-proyek-' . ($this->house->house_code ?: 'rumah') . '-' . now()->format('Ymd-His') . '.xlsx';

        return response()->streamDownload(function () use ($export) {
            echo Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);
        }, $filename);
    }
}

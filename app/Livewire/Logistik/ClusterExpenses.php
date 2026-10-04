<?php

namespace App\Livewire\Logistik;

use App\Models\Cluster;
use App\Models\ClusterExpense;
use App\Models\House;
use App\Models\MaterialUsage;
use App\Exports\ClusterCostsExport;
use App\Traits\WithTableSorting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ClusterExpenses extends Component
{
    use WithFileUploads, WithPagination, WithTableSorting;

    public Cluster $cluster;

    public string $search = '';

    public string $filterStatus = '';

    public string $filterYear = '';

    public string $sort = 'name_asc';

    public string $type = 'other';

    public string $description = '';

    public string $vendor = '';

    public string $quantity = '1';

    public string $start_date = '';

    public string $due_date = '';

    public string $amount = '';

    public string $notes = '';

    public $bill_image;

    public ?int $parent_expense_id = null;

    public bool $showModal = false;






    public function mount(Cluster $cluster): void
    {
        $this->authorizeCluster($cluster);
        $this->cluster = $cluster;
        $this->start_date = now()->toDateString();
        $this->filterYear = (string) now()->year;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatingFilterYear(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->filterStatus = '';
        $this->filterYear = (string) now()->year;
        $this->resetPage();
    }

    protected function sortableColumns(): array
    {
        $selectedYear = (int) ($this->filterYear ?: now()->year);
        $columns = [
            'name' => 'houses.name',
            'code' => 'houses.house_code',
            'type' => 'houses.type',
            'status' => 'houses.status',
            'year_total' => fn ($query, $direction) => $query->orderBy(
                MaterialUsage::selectRaw('COALESCE(SUM(total_cost), 0)')
                    ->whereColumn('material_usages.house_id', 'houses.id')
                    ->whereNull('material_usages.voided_at')
                    ->whereYear('material_usages.usage_date', $selectedYear),
                $direction,
            ),
            'all_time' => 'material_usages_sum_total_cost',
        ];

        for ($month = 1; $month <= 12; $month++) {
            $columns['month_'.$month] = 'month_'.$month.'_cost';
        }

        return $columns;
    }

    protected function rules(): array
    {
        $rental = in_array($this->type, ['rental', 'rental_extension'], true);

        return [
            'type' => ['required', 'in:rental_extension,drainage,electrical,streetlight,other'],
            'description' => ['required', 'string', 'max:255'],
            'vendor' => [$rental ? 'required' : 'nullable', 'string', 'max:255'],
            'quantity' => [$rental ? 'required' : 'nullable', 'numeric', 'min:0.01'],
            'start_date' => [$rental ? 'required' : 'nullable', 'date'],
            'due_date' => [$rental ? 'required' : 'nullable', 'date', 'after_or_equal:start_date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'bill_image' => [$rental ? 'required' : 'nullable', 'image', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function create(string $type = 'other'): void
    {
        $this->authorizeCluster($this->cluster);
        $this->resetForm();
        $this->type = in_array($type, ['drainage', 'electrical', 'streetlight', 'other'], true) ? $type : 'other';
        $this->showModal = true;
    }

    public function startExtension(int $expenseId): void
    {
        $this->authorizeCluster($this->cluster);
        $parent = ClusterExpense::where('cluster_id', $this->cluster->id)
            ->where('type', 'rental')
            ->where('status', 'active')
            ->find($expenseId);
        if (! $parent) {
            $this->addError('parent_expense_id', 'Rental sudah off-hire dan tidak dapat diperpanjang.');

            return;
        }
        $this->resetForm();
        $this->type = 'rental_extension';
        $this->parent_expense_id = $parent->id;
        $this->vendor = $parent->vendor ?? '';
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->authorizeCluster($this->cluster);
        $validated = $this->validate();
        $path = $this->bill_image ? $this->bill_image->store('cluster-expense-bills', 'public') : null;

        try {
            DB::transaction(function () use ($validated, $path) {
                $parent = null;
                if ($this->type === 'rental_extension') {
                    $parent = ClusterExpense::where('cluster_id', $this->cluster->id)
                        ->where('type', 'rental')
                        ->where('status', 'active')
                        ->lockForUpdate()
                        ->find($this->parent_expense_id);
                    if (! $parent) {
                        throw ValidationException::withMessages(['parent_expense_id' => 'Rental sudah off-hire atau tidak tersedia. Pilih rental aktif.']);
                    }
                }

                $expense = ClusterExpense::create([
                    ...$validated,
                    'cluster_id' => $this->cluster->id,
                    'house_id' => $parent?->house_id,
                    'parent_expense_id' => $parent?->id,
                    'created_by' => auth()->id(),
                    'status' => 'active',
                    'bill_image' => $path,
                ]);
                if ($parent) {
                    $expense->houses()->sync($parent->houses()->pluck('houses.id')->all());
                }
            });
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }

            throw $e;
        }

        $this->showModal = false;
        $this->resetForm();
        session()->flash('success', 'Biaya cluster berhasil dicatat.');
    }

    public function exportExcel()
    {
        $this->authorizeCluster($this->cluster);
        $filename = 'laporan-biaya-cluster-'.$this->cluster->id.'-'.now()->format('Ymd-His').'.xlsx';

        return Excel::download(new ClusterCostsExport($this->cluster), $filename);
    }

    public function resetForm(): void
    {
        $this->reset(['type', 'description', 'vendor', 'quantity', 'start_date', 'due_date', 'amount', 'notes', 'bill_image', 'parent_expense_id']);
        $this->type = 'other';
        $this->quantity = '1';
        $this->start_date = now()->toDateString();
        $this->resetValidation();
    }

    public function render()
    {
        $this->authorizeCluster($this->cluster);
        $selectedYear = (int) ($this->filterYear ?: now()->year);
        $clusterUsages = MaterialUsage::query()
            ->whereNull('voided_at')
            ->whereHas('house', fn ($houses) => $houses->where('cluster_id', $this->cluster->id));

        $houseQuery = House::query()
            ->where('cluster_id', $this->cluster->id)
            ->with('cluster')
            ->withSum(['materialUsages' => fn ($query) => $query->whereNull('voided_at')], 'total_cost');

        for ($month = 1; $month <= 12; $month++) {
            $houseQuery->withSum([
                'materialUsages as month_'.$month.'_cost' => function ($query) use ($selectedYear, $month) {
                    $query->whereNull('voided_at')
                        ->whereYear('usage_date', $selectedYear)
                        ->whereMonth('usage_date', $month);
                },
            ], 'total_cost');
        }

        $houses = $houseQuery
            ->when($this->search, fn ($query) => $query->where(function ($search) {
                $search->where('name', 'like', "%{$this->search}%")
                    ->orWhere('type', 'like', "%{$this->search}%")
                    ->orWhere('house_code', 'like', "%{$this->search}%");
            }))
            ->when($this->filterStatus, fn ($query) => $query->where('status', $this->filterStatus))
            ->tap(fn ($query) => $this->applyTableSort($query))
            ->orderBy('houses.id')
            ->paginate(10);

        $years = (clone $clusterUsages)
            ->selectRaw('DISTINCT YEAR(usage_date) as yr')
            ->pluck('yr')
            ->filter()
            ->map(fn ($year) => (int) $year)
            ->push((int) now()->year)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();

        $monthlyTotals = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthlyTotals[$month] = (clone $clusterUsages)
                ->whereYear('usage_date', $selectedYear)
                ->whereMonth('usage_date', $month)
                ->sum('total_cost');
        }

        $rentalExpenses = $this->cluster->expenses()
            ->whereIn('type', ['rental', 'rental_extension'])
            ->with(['house', 'houses'])
            ->latest()
            ->get();
        $vendorRentalSubtotal = $rentalExpenses->sum('amount');
        $rentalRows = $rentalExpenses->flatMap(fn ($expense) => $expense->houses->isNotEmpty()
            ? $expense->houses->map(fn ($house) => [
                'key' => 'rental-'.$expense->id.'-house-'.$house->id,
                'house' => $house->name,
                'expense' => $expense,
            ])
            : [[
                'key' => 'rental-'.$expense->id.'-cluster',
                'house' => $expense->house?->name ?? 'Cluster (tanpa rumah)',
                'expense' => $expense,
            ]]);
        $vendorServiceExpenses = $this->cluster->expenses()
            ->where('type', 'vendor_service')
            ->with(['house', 'houses'])
            ->latest()
            ->get();
        $vendorServiceSubtotal = $vendorServiceExpenses->sum('amount');
        $vendorServiceRows = $vendorServiceExpenses->flatMap(fn ($expense) => $expense->houses->isNotEmpty()
            ? $expense->houses->map(fn ($house) => [
                'key' => 'vendor-service-'.$expense->id.'-house-'.$house->id,
                'target' => 'Rumah · '.$house->name,
                'expense' => $expense,
            ])
            : [[
                'key' => 'vendor-service-'.$expense->id.'-cluster',
                'target' => $expense->house ? 'Rumah · '.$expense->house->name : 'Area bersama · '.$this->cluster->name,
                'expense' => $expense,
            ]]);
        $houseSubtotal = MaterialUsage::query()
            ->join('houses', 'houses.id', '=', 'material_usages.house_id')
            ->where('houses.cluster_id', $this->cluster->id)
            ->whereNull('material_usages.voided_at')
            ->sum('material_usages.total_cost');
        $clusterTotal = $houseSubtotal + $vendorRentalSubtotal + $vendorServiceSubtotal;

        return view('livewire.logistik.cluster-expenses', compact('rentalRows', 'houseSubtotal', 'vendorRentalSubtotal', 'vendorServiceRows', 'vendorServiceSubtotal', 'clusterTotal', 'houses', 'years', 'selectedYear', 'monthlyTotals'))
            ->layout('layouts.app', ['title' => 'Biaya Cluster: '.$this->cluster->name]);
    }

    private function authorizeCluster(Cluster $cluster): void
    {
        $user = auth()->user();
        abort_unless(
            $user?->canAccessAllClusters()
                || (in_array($user?->role, ['logistik', 'pengawas'], true) && $user->cluster_id && (int) $cluster->id === (int) $user->cluster_id),
            403,
        );
    }
}

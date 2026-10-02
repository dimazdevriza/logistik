<?php

namespace App\Livewire\Logistik;

use App\Models\Cluster;
use App\Models\ClusterExpense;
use App\Traits\WithTableSorting;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Clusters extends Component
{
    use WithPagination, WithTableSorting;

    public string $search = '';
    public string $sort = 'name_asc';
    public string $filterYear = '';
    public bool $isCostPage = false;
    public bool $showModal = false;
    public bool $showConfirmation = false;
    public bool $editMode = false;
    public ?int $clusterId = null;
    public string $name = '';
    public string $description = '';
    public ?int $confirmingId = null;

    public function mount(): void
    {
        abort_unless(in_array(auth()->user()?->role, ['admin', 'keuangan'], true), 403);
        $this->isCostPage = request()->routeIs('logistik.cluster-costs') || auth()->user()->role === 'keuangan';
        $this->filterYear = (string) now()->year;
    }

    public function updatingSearch(): void
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
        $this->filterYear = (string) now()->year;
        $this->resetPage();
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('clusters', 'name')->ignore($this->clusterId)],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function create(): void
    {
        $this->authorizeManagement();
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $this->authorizeManagement();
        $cluster = Cluster::findOrFail($id);
        $this->clusterId = $cluster->id;
        $this->name = $cluster->name;
        $this->description = $cluster->description ?? '';
        $this->editMode = true;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->authorizeManagement();
        $data = $this->validate();

        if ($this->editMode) {
            Cluster::findOrFail($this->clusterId)->update($data);
            session()->flash('success', 'Cluster berhasil diperbarui.');
        } else {
            Cluster::create($data);
            session()->flash('success', 'Cluster berhasil ditambahkan.');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $this->authorizeManagement();
        $this->resetValidation('delete');
        $cluster = Cluster::findOrFail($id);
        if ($cluster->houses()->exists()) {
            $this->addError('delete', 'Cluster tidak dapat dihapus selama masih memiliki rumah. Pindahkan rumah terlebih dahulu.');
            return;
        }

        $this->confirmingId = $cluster->id;
        $this->showConfirmation = true;
    }

    public function delete(): void
    {
        $this->authorizeManagement();
        $cluster = Cluster::findOrFail($this->confirmingId);
        if ($cluster->houses()->exists()) {
            $this->addError('delete', 'Cluster tidak dapat dihapus selama masih memiliki rumah. Pindahkan rumah terlebih dahulu.');
            $this->showConfirmation = false;
            return;
        }

        $cluster->delete();
        $this->showConfirmation = false;
        $this->confirmingId = null;
        session()->flash('success', 'Cluster berhasil dihapus.');
    }

    public function resetForm(): void
    {
        $this->reset(['clusterId', 'name', 'description', 'editMode']);
        $this->resetValidation();
    }

    protected function sortableColumns(): array
    {
        $year = (int) ($this->filterYear ?: now()->year);
        $costOrder = function (?int $month = null, bool $yearOnly = false) use ($year) {
            return function ($query, $direction) use ($year, $month, $yearOnly) {
                $yearFilter = $month !== null || $yearOnly;
                $materialDateFilter = $yearFilter ? ' AND YEAR(mu.usage_date) = ?' : '';
                $expenseDateFilter = $yearFilter ? ' AND YEAR(COALESCE(ce.start_date, ce.created_at)) = ?' : '';
                if ($month !== null) {
                    $materialDateFilter .= ' AND MONTH(mu.usage_date) = ?';
                    $expenseDateFilter .= ' AND MONTH(COALESCE(ce.start_date, ce.created_at)) = ?';
                }
                $sql = '(SELECT COALESCE(SUM(mu.total_cost), 0) FROM material_usages mu JOIN houses h ON h.id = mu.house_id WHERE h.cluster_id = clusters.id AND mu.voided_at IS NULL'.$materialDateFilter.')'
                    .' + (SELECT COALESCE(SUM(ce.amount), 0) FROM cluster_expenses ce WHERE ce.cluster_id = clusters.id AND ce.type IN (?, ?, ?)'.$expenseDateFilter.')';
                $bindings = $yearFilter ? [$year] : [];
                if ($month !== null) {
                    $bindings[] = $month;
                }
                $bindings = [...$bindings, 'rental', 'rental_extension', 'vendor_service'];
                if ($yearFilter) {
                    $bindings[] = $year;
                }
                if ($month !== null) {
                    $bindings[] = $month;
                }

                $query->orderByRaw($sql.' '.$direction, $bindings);
            };
        };

        $columns = [
            'name' => 'clusters.name',
            'houses' => 'houses_count',
            'description' => 'clusters.description',
            'year_total' => $costOrder(null, true),
            'all_time' => $costOrder(),
        ];

        for ($month = 1; $month <= 12; $month++) {
            $columns['month_'.$month] = $costOrder($month);
        }

        return $columns;
    }

    private function authorizeManagement(): void
    {
        abort_unless(auth()->user()?->role === 'admin', 403);
    }

    public function render()
    {
        $selectedYear = (int) ($this->filterYear ?: now()->year);
        $clusters = Cluster::query()
            ->withCount('houses')
            ->with('houses:id,cluster_id,name,house_code')
            ->when($this->search, fn ($query) => $query->where(function ($search) {
                $search->where('clusters.name', 'like', "%{$this->search}%")
                    ->orWhere('clusters.description', 'like', "%{$this->search}%")
                    ->orWhereHas('houses', fn ($houses) => $houses
                        ->where('name', 'like', "%{$this->search}%")
                        ->orWhere('house_code', 'like', "%{$this->search}%"));
            }))
            ->tap(fn ($query) => $this->applyTableSort($query))
            ->orderBy('clusters.id')
            ->paginate(10);

        if ($this->isCostPage) {
            $materialTotals = DB::table('material_usages as mu')
                ->join('houses as h', 'h.id', '=', 'mu.house_id')
                ->whereNull('mu.voided_at')
                ->whereNotNull('h.cluster_id')
                ->selectRaw('h.cluster_id, SUM(mu.total_cost) as total')
                ->groupBy('h.cluster_id')
                ->pluck('total', 'cluster_id');
            $expenseTotals = DB::table('cluster_expenses')
                ->whereIn('type', ['rental', 'rental_extension', 'vendor_service'])
                ->selectRaw('cluster_id, SUM(amount) as total')
                ->groupBy('cluster_id')
                ->pluck('total', 'cluster_id');

            $materialMonths = DB::table('material_usages as mu')
                ->join('houses as h', 'h.id', '=', 'mu.house_id')
                ->whereNull('mu.voided_at')
                ->whereNotNull('h.cluster_id')
                ->whereYear('mu.usage_date', $selectedYear)
                ->selectRaw('h.cluster_id, MONTH(mu.usage_date) as month, SUM(mu.total_cost) as total')
                ->groupBy('h.cluster_id')
                ->groupByRaw('MONTH(mu.usage_date)')
                ->get();
            $expenseMonths = DB::table('cluster_expenses')
                ->whereIn('type', ['rental', 'rental_extension', 'vendor_service'])
                ->whereRaw('YEAR(COALESCE(start_date, created_at)) = ?', [$selectedYear])
                ->selectRaw('cluster_id, MONTH(COALESCE(start_date, created_at)) as month, SUM(amount) as total')
                ->groupBy('cluster_id')
                ->groupByRaw('MONTH(COALESCE(start_date, created_at))')
                ->get();

            $monthlyTotals = array_fill(1, 12, 0);
            $clusterMonths = [];
            foreach ($materialMonths->concat($expenseMonths) as $row) {
                $clusterMonths[$row->cluster_id][$row->month] = ($clusterMonths[$row->cluster_id][$row->month] ?? 0) + (float) $row->total;
                $monthlyTotals[$row->month] += (float) $row->total;
            }

            $years = DB::table('material_usages as mu')
                ->join('houses as h', 'h.id', '=', 'mu.house_id')
                ->whereNull('mu.voided_at')
                ->whereNotNull('h.cluster_id')
                ->selectRaw('DISTINCT YEAR(mu.usage_date) as yr')
                ->pluck('yr')
                ->merge(ClusterExpense::query()
                    ->whereIn('type', ['rental', 'rental_extension', 'vendor_service'])
                    ->selectRaw('DISTINCT YEAR(COALESCE(start_date, created_at)) as yr')
                    ->pluck('yr'))
                ->filter()
                ->map(fn ($year) => (int) $year)
                ->push((int) now()->year)
                ->unique()
                ->sortDesc()
                ->values()
                ->all();

            foreach ($clusters->getCollection() as $cluster) {
                $yearTotal = 0;
                for ($month = 1; $month <= 12; $month++) {
                    $amount = $clusterMonths[$cluster->id][$month] ?? 0;
                    $cluster->setAttribute('month_'.$month.'_cost', $amount);
                    $yearTotal += $amount;
                }
                $cluster->setAttribute('year_total', $yearTotal);
                $cluster->setAttribute('all_time_total', (float) ($materialTotals[$cluster->id] ?? 0) + (float) ($expenseTotals[$cluster->id] ?? 0));
            }

            $totalSpent = $materialTotals->sum() + $expenseTotals->sum();
        }

        return view('livewire.logistik.clusters', [
            'clusters' => $clusters,
            'isCostPage' => $this->isCostPage,
            'selectedYear' => $selectedYear,
            'years' => $years ?? [],
            'monthlyTotals' => $monthlyTotals ?? [],
            'totalSpent' => $totalSpent ?? 0,
        ])
            ->layout('layouts.app', ['title' => $this->isCostPage ? 'Biaya Cluster' : 'Cluster']);
    }
}

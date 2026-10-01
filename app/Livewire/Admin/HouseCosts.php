<?php

namespace App\Livewire\Admin;

use App\Exports\HouseListExport;
use App\Models\House;
use App\Models\MaterialUsage;
use App\Traits\WithTableSorting;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class HouseCosts extends Component
{
    use WithPagination, WithTableSorting;

    public $search = '';

    public $filterStatus = '';

    public $filterYear = '';

    public $sort = 'name_asc';

    public function mount()
    {
        $this->filterYear = (string) now()->year;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterStatus()
    {
        $this->resetPage();
    }

    public function updatingFilterYear()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->reset(['search', 'filterStatus']);
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
                $direction
            ),
            'all_time' => 'material_usages_sum_total_cost',
        ];

        for ($month = 1; $month <= 12; $month++) {
            $columns['month_'.$month] = 'month_'.$month.'_cost';
        }

        return $columns;
    }

    private function materialUsagesQuery()
    {
        $user = auth()->user();

        return MaterialUsage::query()
            ->whereNull('voided_at')
            ->when($user->role === 'logistik', fn ($query) => $query->whereHas(
                'house', fn ($houses) => $houses->forUser($user)
            ));
    }

    public function render()
    {
        $selectedYear = (int) ($this->filterYear ?: now()->year);
        $user = auth()->user();
        $scopeLabel = in_array($user->role, ['admin', 'keuangan'], true)
            ? 'Semua Rumah'
            : ($user->cluster?->name ?? 'Cluster belum ditetapkan');

        $query = House::query()
            ->when($user->role === 'logistik', fn ($q) => $q->forUser($user))
            ->with('cluster')
            ->withSum(['materialUsages' => fn ($q) => $q->whereNull('voided_at')], 'total_cost')
            ->withCount(['materialUsages' => fn ($q) => $q->whereNull('voided_at')]);

        // Add conditional sums for each of the 12 months in the selected year
        for ($m = 1; $m <= 12; $m++) {
            $query->withSum([
                'materialUsages as month_'.$m.'_cost' => function ($q) use ($selectedYear, $m) {
                    $q->whereNull('voided_at')
                        ->whereYear('usage_date', $selectedYear)
                        ->whereMonth('usage_date', $m);
                },
            ], 'total_cost');
        }

        $houses = $query
            ->when($this->search, fn ($q) => $q->where(function ($sub) {
                $sub->where('name', 'like', "%{$this->search}%")
                    ->orWhere('type', 'like', "%{$this->search}%")
                    ->orWhere('house_code', 'like', "%{$this->search}%");
            }))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->tap(fn ($builder) => $this->applyTableSort($builder))
            ->orderBy('houses.id')
            ->paginate(10);

        $scopeKey = $user->role === 'logistik' ? 'cluster_'.($user->cluster_id ?? 0) : 'all';
        $totalSpent = cache()->remember('total_material_spent_'.$scopeKey, 60, fn () => $this->materialUsagesQuery()->sum('total_cost'));

        // Compute monthly totals for the entire project for the selected year
        $monthlyTotals = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthlyTotals[$m] = $this->materialUsagesQuery()
                ->whereYear('usage_date', $selectedYear)
                ->whereMonth('usage_date', $m)
                ->sum('total_cost');
        }

        // Available years for dropdown filter
        $yearExpression = 'YEAR(usage_date)';

        $years = $this->materialUsagesQuery()
            ->selectRaw("DISTINCT {$yearExpression} as yr")
            ->pluck('yr')
            ->filter()
            ->map(fn ($y) => (int) $y)
            ->push((int) now()->year)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();

        return view('livewire.admin.house-costs', compact('houses', 'totalSpent', 'monthlyTotals', 'years', 'selectedYear', 'scopeLabel'))
            ->layout('layouts.app', ['title' => 'Biaya Rumah']);
    }

    public function exportExcel()
    {
        $export = new HouseListExport(
            $this->search,
            $this->filterStatus,
            (int) ($this->filterYear ?: now()->year),
            null,
            auth()->user()->role === 'logistik' ? (int) (auth()->user()->cluster_id ?? 0) : null
        );
        $filename = 'laporan-biaya-rumah-'.($this->filterYear ?: now()->year).'-'.now()->format('Ymd-His').'.xlsx';

        return response()->streamDownload(function () use ($export) {
            echo Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);
        }, $filename);
    }
}

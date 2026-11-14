<?php

namespace App\Livewire\Logistik;

use App\Exports\HouseListExport;
use App\Models\Cluster;
use App\Models\House;
use App\Models\MaterialUsage;
use Illuminate\Support\Str;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

/** Spreadsheet-style yearly recap (houses x months) for one cluster, embedded in Biaya Cluster. */
class ClusterRecap extends Component
{
    public string $clusterId = '';

    public array $allowedClusterIds = [];

    public string $filterYear = '';

    public string $search = '';

    public function mount(?string $clusterId = null, array $allowedClusterIds = []): void
    {
        $this->allowedClusterIds = array_values(array_map('intval', $allowedClusterIds));
        $this->filterYear = (string) now()->year;
        $this->clusterId = $clusterId ?: $this->defaultClusterId();
    }

    public function resetFilters(): void
    {
        $this->filterYear = (string) now()->year;
        $this->search = '';
        $this->clusterId = $this->defaultClusterId();
    }

    private function clustersQuery()
    {
        $user = auth()->user();

        return Cluster::query()
            ->when(! $user->canAccessAllClusters(), fn ($q) => $q->whereIn('id', $user->assignedClusterIds() ?: [0]))
            ->when($this->allowedClusterIds !== [], fn ($q) => $q->whereIn('id', $this->allowedClusterIds))
            ->orderBy('name');
    }

    private function defaultClusterId(): string
    {
        $clusters = $this->clustersQuery()->get(['id', 'name']);

        return (string) ($clusters->first(fn ($c) => str_contains(strtoupper($c->name), 'VILLAGIO'))?->id ?? $clusters->first()?->id ?? '');
    }

    private function withMonthlySums($query, int $year)
    {
        $query->withSum(['materialUsages as year_total' => fn ($q) => $q->whereNull('voided_at')->whereYear('usage_date', $year)], 'total_cost');

        for ($m = 1; $m <= 12; $m++) {
            $query->withSum(['materialUsages as month_'.$m.'_cost' => fn ($q) => $q->whereNull('voided_at')->whereYear('usage_date', $year)->whereMonth('usage_date', $m)], 'total_cost');
        }

        return $query;
    }

    public function exportExcel()
    {
        $clusterName = Cluster::find($this->clusterId)?->name ?? 'Cluster';
        $year = (int) $this->filterYear;
        $user = auth()->user();

        return Excel::download(
            new HouseListExport(
                search: $this->search,
                filterStatus: null,
                year: $year,
                filterCluster: $this->clusterId,
                clusterScope: $user->canAccessAllClusters() ? null : $user->scopedClusterIds(),
                reportTitle: 'REKAPITULASI TOTAL PENGELUARAN CLUSTER '.strtoupper($clusterName).' (PERIODE '.$year.')',
                category: null,
            ),
            'Rekapitulasi-Pengeluaran-'.Str::slug($clusterName).'-'.$year.'.xlsx'
        );
    }

    public function render()
    {
        $clusters = $this->clustersQuery()->get(['id', 'name']);

        // Never trust the client-side clusterId: fall back to an accessible one.
        if (! $clusters->contains('id', (int) $this->clusterId)) {
            $this->clusterId = $this->defaultClusterId();
        }

        $clusterId = (int) $this->clusterId;
        $year = (int) ($this->filterYear ?: now()->year);

        $houses = $this->withMonthlySums(House::query()->housesOnly()->where('cluster_id', $clusterId), $year)
            ->when($this->search, fn ($q) => $q->where(fn ($sub) => $sub
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('house_code', 'like', "%{$this->search}%")
                ->orWhere('mandor_name', 'like', "%{$this->search}%")))
            ->orderBy('name')
            ->get();

        $publicWorks = $this->withMonthlySums(House::query()->publicWorksOnly()->where('cluster_id', $clusterId), $year)->get();

        $saranaMonthCosts = [];
        $monthlyGrandTotals = [];
        for ($m = 1; $m <= 12; $m++) {
            $saranaMonthCosts[$m] = (float) $publicWorks->sum('month_'.$m.'_cost');
            $monthlyGrandTotals[$m] = (float) $houses->sum('month_'.$m.'_cost') + $saranaMonthCosts[$m];
        }

        $years = MaterialUsage::whereNull('voided_at')
            ->selectRaw('DISTINCT YEAR(usage_date) as yr')
            ->pluck('yr')
            ->push(now()->year)
            ->filter()
            ->map(fn ($y) => (int) $y)
            ->unique()
            ->sortDesc()
            ->values();

        return view('livewire.logistik.cluster-recap', [
            'clusters' => $clusters,
            'selectedCluster' => $clusters->firstWhere('id', $clusterId),
            'selectedYear' => $year,
            'years' => $years,
            'monthNames' => ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
            'matrixHouses' => $houses,
            'saranaMonthCosts' => $saranaMonthCosts,
            'saranaYearTotal' => array_sum($saranaMonthCosts),
            'saranaMandors' => $publicWorks->pluck('mandor_name')->filter()->unique()->join(', ') ?: 'Pekerjaan Umum & Fasum',
            'monthlyGrandTotals' => $monthlyGrandTotals,
            'clusterYearGrandTotal' => array_sum($monthlyGrandTotals),
        ]);
    }
}

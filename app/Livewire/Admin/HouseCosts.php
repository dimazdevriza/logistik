<?php

namespace App\Livewire\Admin;

use App\Models\House;
use App\Models\MaterialUsage;

use App\Exports\HouseListExport;
use Maatwebsite\Excel\Facades\Excel;
use Livewire\Component;
use Livewire\WithPagination;

class HouseCosts extends Component
{
    use WithPagination;

    public $search = '';
    public $filterStatus = '';
    public $filterYear = '';

    public function mount()
    {
        $this->filterYear = (string) now()->year;
    }

    public function updatingSearch() { $this->resetPage(); }
    public function updatingFilterStatus() { $this->resetPage(); }
    public function updatingFilterYear() { $this->resetPage(); }

    public function resetFilters()
    {
        $this->reset(['search', 'filterStatus']);
        $this->filterYear = (string) now()->year;
        $this->resetPage();
    }

    public function render()
    {
        $selectedYear = (int) ($this->filterYear ?: now()->year);

        $query = House::query()
            ->withSum(['materialUsages' => fn ($q) => $q->whereNull('voided_at')], 'total_cost')
            ->withCount(['materialUsages' => fn ($q) => $q->whereNull('voided_at')]);

        // Add conditional sums for each of the 12 months in the selected year
        for ($m = 1; $m <= 12; $m++) {
            $query->withSum([
                'materialUsages as month_' . $m . '_cost' => function ($q) use ($selectedYear, $m) {
                    $q->whereNull('voided_at')
                        ->whereYear('usage_date', $selectedYear)
                        ->whereMonth('usage_date', $m);
                }
            ], 'total_cost');
        }

        $houses = $query
            ->when($this->search, fn ($q) => $q->where(function ($sub) {
                $sub->where('name', 'like', "%{$this->search}%")
                    ->orWhere('type', 'like', "%{$this->search}%")
                    ->orWhere('house_code', 'like', "%{$this->search}%");
            }))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->orderBy('name')
            ->paginate(10);

        $totalSpent = cache()->remember('total_material_spent', 60, function () {
            return MaterialUsage::whereNull('voided_at')->sum('total_cost');
        });

        // Compute monthly totals for the entire project for the selected year
        $monthlyTotals = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthlyTotals[$m] = MaterialUsage::whereNull('voided_at')
                ->whereYear('usage_date', $selectedYear)
                ->whereMonth('usage_date', $m)
                ->sum('total_cost');
        }

        // Available years for dropdown filter
        $years = MaterialUsage::whereNull('voided_at')
            ->selectRaw('DISTINCT YEAR(usage_date) as yr')
            ->pluck('yr')
            ->filter()
            ->map(fn ($y) => (int) $y)
            ->push((int) now()->year)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();

        return view('livewire.admin.house-costs', compact('houses', 'totalSpent', 'monthlyTotals', 'years', 'selectedYear'))
            ->layout('layouts.app', ['title' => 'Biaya Rumah']);
    }

    public function exportExcel()
    {
        $export = new HouseListExport(
            $this->search,
            $this->filterStatus,
            (int) ($this->filterYear ?: now()->year)
        );
        $filename = 'laporan-biaya-rumah-' . ($this->filterYear ?: now()->year) . '-' . now()->format('Ymd-His') . '.xlsx';

        return response()->streamDownload(function () use ($export) {
            echo Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);
        }, $filename);
    }
}

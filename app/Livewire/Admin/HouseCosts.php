<?php

namespace App\Livewire\Admin;

use App\Models\House;
use App\Models\MaterialUsage;

use App\Exports\HouseExport;
use Maatwebsite\Excel\Facades\Excel;
use Livewire\Component;
use Livewire\WithPagination;

class HouseCosts extends Component
{
    use WithPagination;

    public $search = '';
    public $filterStatus = '';


    public function render()
    {
        $houses = House::withSum('materialUsages', 'total_cost')
            ->withCount('materialUsages')
            ->when($this->search, fn ($q) => $q->where(function ($sub) {
                $sub->where('name', 'like', "%{$this->search}%")
                    ->orWhere('type', 'like', "%{$this->search}%")
                    ->orWhere('house_code', 'like', "%{$this->search}%");
            }))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->orderBy('name')
            ->paginate(10);

        $totalSpent = cache()->remember('total_material_spent', 60, function () {
            return MaterialUsage::sum('total_cost');
        });
        
        return view('livewire.admin.house-costs', compact('houses', 'totalSpent'))
            ->layout('layouts.app', ['title' => 'Biaya Rumah']);
    }

    public function exportExcel()
    {
        $export = new HouseExport(
            $this->search,
            $this->filterStatus
        );
        $filename = 'laporan-biaya-rumah-' . now()->format('Ymd') . '.xlsx';

        return response()->streamDownload(function () use ($export) {
            echo Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);
        }, $filename);
    }
}

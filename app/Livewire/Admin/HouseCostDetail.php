<?php

namespace App\Livewire\Admin;

use App\Models\House;
use App\Models\MaterialUsage;
use App\Exports\MaterialUsageExport;
use Maatwebsite\Excel\Facades\Excel;
use Livewire\Component;
use Livewire\WithPagination;

class HouseCostDetail extends Component
{
    use WithPagination;

    public House $house;

    public function mount(House $house)
    {
        $this->house = $house;
    }

    public function render()
    {
        $materialUsages = MaterialUsage::with(['material', 'user'])
            ->where('house_id', $this->house->id)
            ->whereNull('voided_at')
            ->orderByDesc('usage_date')
            ->paginate(15);

        $totalCost = MaterialUsage::where('house_id', $this->house->id)->whereNull('voided_at')->sum('total_cost');

        // Cost by category
        $costByCategory = MaterialUsage::where('house_id', $this->house->id)
            ->whereNull('material_usages.voided_at')
            ->join('materials', 'material_usages.material_id', '=', 'materials.id')
            ->join('categories', 'materials.category_id', '=', 'categories.id')
            ->selectRaw('categories.id, categories.name as category_name, SUM(material_usages.total_cost) as total')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total')
            ->get();

        return view('livewire.admin.house-cost-detail', compact('materialUsages', 'totalCost', 'costByCategory'))
            ->layout('layouts.app', ['title' => 'Detail Biaya — ' . $this->house->name]);
    }

    public function exportExcel()
    {
        $export = new MaterialUsageExport($this->house->id);
        $filename = 'biaya-rumah-' . $this->house->house_code . '-' . now()->format('Ymd') . '.xlsx';

        return response()->streamDownload(function () use ($export) {
            echo Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);
        }, $filename);
    }
}

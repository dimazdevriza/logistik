<?php

namespace App\Livewire\Logistik;

use App\Models\House;
use App\Models\MaterialUsage;
use App\Models\ToolUsage;
use App\Exports\HouseExport;
use App\Exports\MaterialUsageExport;
use App\Exports\ToolUsageExport;
use Maatwebsite\Excel\Facades\Excel;
use Livewire\Component;
use Livewire\WithPagination;

class HouseDetail extends Component
{
    use WithPagination;

    public House $house;

    // View state
    public $activeTab = 'material'; // material or tool

    public function mount(House $house)
    {
        $this->house = $house;
    }

    public function updatingActiveTab()
    {
        $this->resetPage('materialPage');
        $this->resetPage('toolPage');
    }

    public function render()
    {
        $materialUsages = null;
        $toolUsages = null;

        $materialCount = $this->house->materialUsages()->whereNull('voided_at')->count();
        $toolCount = $this->house->toolUsages()->count();

        if ($this->activeTab === 'material') {
            $materialUsages = MaterialUsage::with(['material', 'user'])
                ->where('house_id', $this->house->id)
                ->whereNull('voided_at')
                ->orderByDesc('usage_date')
                ->orderByDesc('id')
                ->paginate(15, ['*'], 'materialPage');
        } else {
            $toolUsages = ToolUsage::with(['tool', 'user'])
                ->where('house_id', $this->house->id)
                ->orderByDesc('checkout_date')
                ->orderByDesc('id')
                ->paginate(15, ['*'], 'toolPage');
        }

        return view('livewire.logistik.house-detail', [
            'materialUsages' => $materialUsages,
            'toolUsages' => $toolUsages,
            'materialCount' => $materialCount,
            'toolCount' => $toolCount,
        ])->layout('layouts.app', ['title' => 'Detail Rumah: ' . $this->house->name]);
    }

    public function exportExcel()
    {
        // Only admin or logistik can export
        if (!in_array(auth()->user()->role, ['admin', 'logistik'])) {
            return;
        }

        $export = new HouseExport($this->house->id);
        $filename = 'laporan-proyek-' . ($this->house->house_code ?: 'rumah') . '-' . now()->format('Ymd-His') . '.xlsx';

        return response()->streamDownload(function () use ($export) {
            echo Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);
        }, $filename);
    }
}

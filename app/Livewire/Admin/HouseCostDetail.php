<?php

namespace App\Livewire\Admin;

use App\Models\House;
use App\Models\Material;
use App\Models\MaterialUsage;
use App\Models\DispatchResolutionEvent;
use App\Models\User;
use App\Traits\WithTableSorting;
use App\Exports\MaterialUsageExport;
use Maatwebsite\Excel\Facades\Excel;
use Livewire\Component;
use Livewire\WithPagination;

class HouseCostDetail extends Component
{
    use WithPagination, WithTableSorting;

    public House $house;
    public $sort = 'date_desc';

    public function mount(House $house)
    {
        abort_unless($house->isAccessibleBy(auth()->user()), 403);
        $this->house = $house;
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

        $materialUsages = MaterialUsage::with(['material', 'user'])
            ->where('house_id', $this->house->id)
            ->whereNull('voided_at')
            ->tap(fn ($query) => $this->applyTableSort($query))
            ->orderByDesc('material_usages.id')
            ->paginate(15);

        $totalCost = MaterialUsage::where('house_id', $this->house->id)->whereNull('voided_at')->sum('total_cost');
        $materialLosses = DispatchResolutionEvent::with(['request.material', 'recordedBy', 'dispatchLine.stockIn'])
            ->whereIn('event_type', ['material_damage_loss', 'declare_lost'])
            ->whereHas('request', fn ($query) => $query->where('house_id', $this->house->id))
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->paginate(15, ['*'], 'lossPage');
        $totalMaterialLoss = DispatchResolutionEvent::whereIn('event_type', ['material_damage_loss', 'declare_lost'])
            ->whereHas('request', fn ($query) => $query->where('house_id', $this->house->id))
            ->sum('total_cost');

        // Cost by category
        $costByCategory = MaterialUsage::where('house_id', $this->house->id)
            ->whereNull('material_usages.voided_at')
            ->join('materials', 'material_usages.material_id', '=', 'materials.id')
            ->join('categories', 'materials.category_id', '=', 'categories.id')
            ->selectRaw('categories.id, categories.name as category_name, SUM(material_usages.total_cost) as total')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total')
            ->get();

        return view('livewire.admin.house-cost-detail', compact('materialUsages', 'totalCost', 'costByCategory', 'materialLosses', 'totalMaterialLoss'))
            ->layout('layouts.app', ['title' => 'Detail Biaya - ' . $this->house->name]);
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

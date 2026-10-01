<?php

namespace App\Livewire\Logistik;

use App\Exports\WarehouseExport;
use App\Models\Category;
use App\Models\Warehouse;
use App\Models\ToolWarehouseBalance;
use App\Models\Tool;
use App\Traits\WithTableSorting;
use Maatwebsite\Excel\Facades\Excel;
use Livewire\Component;
use Livewire\WithPagination;

class WarehouseDetail extends Component
{
    use WithPagination, WithTableSorting;

    public Warehouse $warehouse;
    public string $activeTab = 'material';
    public string $sort = 'name_asc';

    public function mount(Warehouse $warehouse): void
    {
        $this->warehouse = $warehouse;
    }

    public function updatingActiveTab(string $value): void
    {
        $this->sort = $value === 'tool' ? 'code_asc' : 'name_asc';
        $this->resetPage('materialsPage');
        $this->resetPage('toolsPage');
    }

    protected function sortableColumns(): array
    {
        if ($this->activeTab === 'tool') {
            return [
                'code' => 'tools.code',
                'name' => 'tools.name',
                'category' => fn ($query, $direction) => $query->orderBy(
                    Category::select('name')->whereColumn('categories.id', 'tools.category_id'),
                    $direction
                ),
                'condition' => 'tools.condition',
                'available' => 'tool_warehouse_balances.available_qty',
                'total' => fn ($query, $direction) => $query->orderByRaw("(tool_warehouse_balances.available_qty + tool_warehouse_balances.qty_broken) {$direction}"),
            ];
        }

        return [
            'name' => 'materials.name',
            'code' => 'materials.code',
            'category' => fn ($query, $direction) => $query->orderBy(
                Category::select('name')->whereColumn('categories.id', 'materials.category_id'),
                $direction
            ),
            'stock' => 'materials.stock',
            'unit' => 'materials.unit',
            'unit_price' => 'materials.unit_price',
        ];
    }

    public function exportExcel()
    {
        $export = new WarehouseExport($this->warehouse->id);
        $filename = 'inventaris-gudang-' . $this->warehouse->id . '-' . now()->format('Ymd-His') . '.xlsx';

        return response()->streamDownload(function () use ($export) {
            echo Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);
        }, $filename);
    }

    public function render()
    {
        $materialCount = $this->warehouse->materials()->count();
        $toolCount = $this->warehouse->toolBalances()->distinct('tool_id')->count('tool_id');
        $toolTotal = $this->warehouse->toolBalances()->sum('available_qty') + $this->warehouse->toolBalances()->sum('qty_broken');
        $toolAvailable = $this->warehouse->toolBalances()->sum('available_qty');

        $materials = $this->activeTab === 'material'
            ? $this->applyTableSort($this->warehouse->materials()->with(['category', 'supplier']))
                ->orderBy('materials.id')
                ->paginate(15, ['*'], 'materialsPage')
            : null;
        $tools = $this->activeTab === 'tool'
            ? $this->applyTableSort(Tool::query()
                ->join('tool_warehouse_balances', 'tools.id', '=', 'tool_warehouse_balances.tool_id')
                ->where('tool_warehouse_balances.warehouse_id', $this->warehouse->id)
                ->with('category')
                ->select('tools.*', 'tool_warehouse_balances.available_qty as warehouse_available_qty', 'tool_warehouse_balances.qty_broken as warehouse_broken_qty'))
                ->orderBy('tools.id')
                ->paginate(15, ['*'], 'toolsPage')
            : null;

        return view('livewire.logistik.warehouse-detail', compact(
            'materialCount', 'toolCount', 'toolTotal', 'toolAvailable', 'materials', 'tools'
        ))->layout('layouts.app', ['title' => 'Detail Gudang: ' . $this->warehouse->name]);
    }
}

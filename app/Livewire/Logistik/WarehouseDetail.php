<?php

namespace App\Livewire\Logistik;

use App\Exports\WarehouseExport;
use App\Models\Category;
use App\Models\StockIn;
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
    public string $search = '';

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

    public function updatingSearch(): void
    {
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
            'stock' => 'stock_ins.remaining_quantity',
            'unit' => 'materials.unit',
            'unit_price' => 'stock_ins.unit_price',
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
        $warehouseBatches = StockIn::query()
            ->join('materials', 'materials.id', '=', 'stock_ins.material_id')
            ->where(fn ($sub) => $sub
                ->where('stock_ins.warehouse_id', $this->warehouse->id)
                ->orWhere(fn ($fallback) => $fallback
                    ->whereNull('stock_ins.warehouse_id')
                    ->where('materials.warehouse_id', $this->warehouse->id)));

        $materialCount = (clone $warehouseBatches)->count();
        $materialValue = (float) ((clone $warehouseBatches)
            ->where('stock_ins.remaining_quantity', '>', 0)
            ->selectRaw('COALESCE(SUM(stock_ins.remaining_quantity * stock_ins.unit_price), 0) as total_value')
            ->value('total_value') ?? 0);
        $toolCount = $this->warehouse->toolBalances()->distinct('tool_id')->count('tool_id');
        $toolTotal = $this->warehouse->toolBalances()->sum('available_qty') + $this->warehouse->toolBalances()->sum('qty_broken');
        $toolAvailable = $this->warehouse->toolBalances()->sum('available_qty');

        $materials = $this->activeTab === 'material'
            ? $this->applyTableSort((clone $warehouseBatches)
                ->with(['material.category', 'material.supplier', 'supplier'])
                ->select(
                    'stock_ins.*',
                    'materials.code as code',
                    'materials.name as name',
                    'materials.unit as unit',
                    'materials.category_id as category_id',
                    'stock_ins.remaining_quantity as stock'
                )
                ->when(trim($this->search) !== '', function ($query) {
                    $search = '%'.trim($this->search).'%';
                    $query->where(fn ($query) => $query
                        ->where('materials.name', 'like', $search)
                        ->orWhere('materials.code', 'like', $search)
                        ->orWhere('stock_ins.entry_code', 'like', $search)
                        ->orWhereHas('material.category', fn ($category) => $category->where('name', 'like', $search)));
                }))
                ->orderBy('materials.id')
                ->orderBy('stock_ins.id')
                ->paginate(15, ['*'], 'materialsPage')
            : null;
        $tools = $this->activeTab === 'tool'
            ? $this->applyTableSort(Tool::query()
                ->join('tool_warehouse_balances', 'tools.id', '=', 'tool_warehouse_balances.tool_id')
                ->where('tool_warehouse_balances.warehouse_id', $this->warehouse->id)
                ->when(trim($this->search) !== '', function ($query) {
                    $search = '%'.trim($this->search).'%';
                    $query->where(fn ($query) => $query
                        ->where('tools.name', 'like', $search)
                        ->orWhere('tools.code', 'like', $search)
                        ->orWhere('tools.condition', 'like', $search)
                        ->orWhereHas('category', fn ($category) => $category->where('name', 'like', $search)));
                })
                ->with('category')
                ->select('tools.*', 'tool_warehouse_balances.available_qty as warehouse_available_qty', 'tool_warehouse_balances.qty_broken as warehouse_broken_qty'))
                ->orderBy('tools.id')
                ->paginate(15, ['*'], 'toolsPage')
            : null;

        return view('livewire.logistik.warehouse-detail', compact(
            'materialCount', 'materialValue', 'toolCount', 'toolTotal', 'toolAvailable', 'materials', 'tools'
        ))->layout('layouts.app', ['title' => 'Detail Gudang: ' . $this->warehouse->name]);
    }
}

<?php

namespace App\Livewire\Logistik;

use App\Exports\MaterialLogExport;
use App\Models\MaterialUsage;
use App\Models\Material;
use App\Models\StockIn;
use App\Models\House;
use App\Models\Supplier;
use App\Traits\WithFilterModal;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class MaterialLog extends Component
{
    use WithPagination, WithFilterModal;

    public $search = '';
    public $filterType = ''; // '' = Semua, 'keluar' = Barang Keluar, 'masuk' = Barang Masuk
    public $filterHouse = '';
    public $filterSupplier = '';
    public $sortDirection = 'desc';

    public function toggleSortDirection(): void
    {
        $this->sortDirection = $this->sortDirection === 'desc' ? 'asc' : 'desc';
        $this->resetPage();
    }

    public function updatingSearch() { $this->resetPage(); }
    public function updatingFilterType()
    {
        $this->filterHouse = '';
        $this->filterSupplier = '';
        $this->resetPage();
    }
    public function updatingFilterHouse() { $this->resetPage(); }
    public function updatingFilterSupplier() { $this->resetPage(); }

    public function resetFilters()
    {
        $this->reset(['search', 'filterType', 'filterHouse', 'filterSupplier']);
        $this->showFilterModal = false;
        $this->resetPage();
    }

    /**
     * B5 — void a material allocation: restore stock, flag the row.
     * Voided rows STAY visible in the log (VOIDED badge) but are excluded
     * from every cost aggregate/export via whereNull('voided_at').
     */
    public function voidMaterial(int $usageId)
    {
        if (!in_array(auth()->user()->role, ['admin', 'logistik'])) {
            abort(403);
        }

        try {
            DB::transaction(function () use ($usageId) {
                $usage = MaterialUsage::lockForUpdate()->findOrFail($usageId);
                if (!is_null($usage->voided_at)) {
                    throw new \Exception('Alokasi ini sudah dibatalkan sebelumnya.');
                }

                $material = Material::lockForUpdate()->findOrFail($usage->material_id);
                $material->increment('stock', $usage->quantity);

                $usage->update([
                    'voided_at' => now(),
                    'voided_by' => auth()->id(),
                ]);
            });
            session()->flash('success', 'Alokasi material dibatalkan; stok dikembalikan.');
        } catch (\Exception $e) {
            $this->addError('void', $e->getMessage());
        }
    }

    protected function getKeluarQuery()
    {
        return MaterialUsage::with(['house', 'material', 'user'])
            ->when($this->search, fn ($q) => $q->whereHas('material', fn ($mq) => $mq->where('name', 'like', "%{$this->search}%")))
            ->when($this->filterHouse, fn ($q) => $q->where('house_id', $this->filterHouse));
    }

    protected function getMasukQuery()
    {
        return StockIn::with(['material', 'supplier', 'user'])
            ->when($this->search, fn ($q) => $q->whereHas('material', fn ($mq) => $mq->where('name', 'like', "%{$this->search}%")))
            ->when($this->filterSupplier, fn ($q) => $q->where('supplier_id', $this->filterSupplier));
    }

    protected function buildCombinedRecords(): LengthAwarePaginator
    {
        $perPage = 10;

        $keluarQuery = MaterialUsage::query()
            ->select(
                DB::raw("'keluar' as type"),
                'material_usages.id as id',
                'material_usages.voided_at as voided_at',
                'material_usages.usage_date as date',
                'users.name as admin_name',
                'users.name as taker_name',
                'houses.name as house_name',
                'material_usages.notes as job_notes',
                'materials.code as item_code',
                'materials.name as item_name',
                'material_usages.quantity as volume',
                'materials.unit as unit',
                'material_usages.unit_price_at_usage as unit_price',
                'material_usages.total_cost as total_cost',
                'suppliers.name as supplier_name',
                'material_usages.created_at as created_at'
            )
            ->join('materials', 'material_usages.material_id', '=', 'materials.id')
            ->join('houses', 'material_usages.house_id', '=', 'houses.id')
            ->join('users', 'material_usages.user_id', '=', 'users.id')
            ->leftJoin('suppliers', 'materials.supplier_id', '=', 'suppliers.id')
            ->when($this->search, fn ($q) => $q->where('materials.name', 'like', "%{$this->search}%"))
            ->when($this->filterHouse, fn ($q) => $q->where('material_usages.house_id', $this->filterHouse));

        $masukQuery = StockIn::query()
            ->select(
                DB::raw("'masuk' as type"),
                'stock_ins.id as id',
                DB::raw('NULL as voided_at'),
                'stock_ins.date',
                'users.name as admin_name',
                DB::raw("'-' as taker_name"),
                DB::raw("'-' as house_name"),
                'stock_ins.notes as job_notes',
                'materials.code as item_code',
                'materials.name as item_name',
                'stock_ins.quantity as volume',
                'materials.unit as unit',
                'stock_ins.unit_price as unit_price',
                'stock_ins.total_cost as total_cost',
                'suppliers.name as supplier_name',
                'stock_ins.created_at as created_at'
            )
            ->join('materials', 'stock_ins.material_id', '=', 'materials.id')
            ->join('suppliers', 'stock_ins.supplier_id', '=', 'suppliers.id')
            ->join('users', 'stock_ins.user_id', '=', 'users.id')
            ->when($this->search, fn ($q) => $q->where('materials.name', 'like', "%{$this->search}%"))
            ->when($this->filterSupplier, fn ($q) => $q->where('stock_ins.supplier_id', $this->filterSupplier));

        if ($this->filterType === 'masuk') {
            $unionQuery = $masukQuery;
        } elseif ($this->filterType === 'keluar') {
            $unionQuery = $keluarQuery;
        } else {
            $unionQuery = $keluarQuery->unionAll($masukQuery);
        }

        return DB::table(DB::raw("({$unionQuery->toSql()}) as combined"))
             ->mergeBindings($unionQuery->getQuery())
             ->orderBy('date', $this->sortDirection)
             ->orderBy('created_at', $this->sortDirection)
             ->paginate($perPage);
    }

    public function exportExcel()
    {
        if (!in_array(auth()->user()->role, ['admin', 'logistik'])) return;

        $export = new MaterialLogExport(
            $this->search,
            $this->filterType,
            $this->filterHouse,
            $this->filterSupplier,
            $this->sortDirection
        );
        $filename = 'catatan-material-' . now()->format('Ymd-His') . '.xlsx';

        return response()->streamDownload(function () use ($export) {
            echo Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);
        }, $filename);
    }

    public function render()
    {
        $houses = House::orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get();

        $records = $this->buildCombinedRecords();

        return view('livewire.logistik.material-log', compact('records', 'houses', 'suppliers'))
            ->layout('layouts.app', ['title' => 'Catatan Material']);
    }
}

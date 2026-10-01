<?php

namespace App\Livewire\Logistik;

use App\Models\House;
use App\Models\Material;
use App\Models\MaterialUsage;
use App\Models\InventoryAdjustment;
use App\Models\InventoryTransfer;
use App\Models\StockIn;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Traits\WithFilterModal;
use App\Traits\WithTableSorting;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class MaterialLog extends Component
{
    use WithFilterModal, WithPagination, WithTableSorting;

    public $search = '';

    public $filterType = ''; // '' = Semua, 'keluar' = Barang Keluar, 'masuk' = Barang Masuk

    public $filterHouse = '';

    public $filterSupplier = '';

    public $sort = 'date_desc';

    public bool $showReceiptModal = false;
    public bool $showReceiptHistoryModal = false;
    public bool $showReceiptCancelModal = false;
    public ?int $activeReceiptId = null;
    public ?int $historyReceiptId = null;
    public ?int $cancelReceiptId = null;
    public string $receiptQuantity = '';
    public string $receiptUnitPrice = '';
    public string $receiptSupplierId = '';
    public string $receiptWarehouseId = '';
    public string $receiptDate = '';
    public string $receiptReceivedAt = '';
    public string $receiptNotes = '';
    public string $receiptReason = '';
    public string $receiptReference = '';
    public string $receiptCancelReason = '';

    protected function sortableColumns(): array
    {
        return [
            'date' => 'date',
            'type' => 'type',
            'admin' => 'admin_name',
            'house' => 'house_name',
            'notes' => 'job_notes',
            'code' => 'item_code',
            'name' => 'item_name',
            'volume' => 'volume',
            'unit' => 'unit',
            'unit_price' => 'unit_price',
            'total' => 'total_cost',
            'supplier' => 'supplier_name',
        ];
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterType()
    {
        $this->filterHouse = '';
        $this->filterSupplier = '';
        $this->resetPage();
    }

    public function updatingFilterHouse()
    {
        $this->resetPage();
    }

    public function updatingFilterSupplier()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->reset(['search', 'filterType', 'filterHouse', 'filterSupplier']);
        $this->showFilterModal = false;
        $this->resetPage();
    }

    private function ensureInventoryAccess(): void
    {
        abort_unless(in_array(auth()->user()->role, ['admin', 'logistik', 'keuangan'], true), 403);
    }

    private function downstreamBatchIds(StockIn $source): array
    {
        $ids = [$source->id];
        $frontier = $ids;
        while ($frontier !== []) {
            $next = InventoryTransfer::whereIn('source_stock_in_id', $frontier)
                ->whereNotNull('destination_stock_in_id')
                ->pluck('destination_stock_in_id')->map(fn ($id) => (int) $id)
                ->diff($ids)->values()->all();
            $ids = array_merge($ids, $next);
            $frontier = $next;
        }

        return $ids;
    }

    public function openReceiptEditor(int $id): void
    {
        $this->ensureInventoryAccess();
        $receipt = StockIn::where('entry_type', 'receipt')->findOrFail($id);
        $this->activeReceiptId = $receipt->id;
        $this->receiptQuantity = (string) $receipt->quantity;
        $this->receiptUnitPrice = (string) $receipt->unit_price;
        $this->receiptSupplierId = (string) ($receipt->supplier_id ?? '');
        $this->receiptWarehouseId = (string) ($receipt->warehouse_id ?? '');
        $this->receiptDate = $receipt->date?->format('Y-m-d') ?? '';
        $this->receiptReceivedAt = $receipt->received_at?->format('Y-m-d\\TH:i') ?? '';
        $this->receiptNotes = (string) ($receipt->notes ?? '');
        $this->receiptReason = '';
        $this->receiptReference = '';
        $this->resetValidation();
        $this->showReceiptModal = true;
    }

    public function saveReceiptCorrection(): void
    {
        $this->ensureInventoryAccess();
        $this->validate([
            'activeReceiptId' => 'required|integer|exists:stock_ins,id',
            'receiptQuantity' => 'required|numeric|min:0.01|max:999999999.99',
            'receiptUnitPrice' => 'required|numeric|min:0|max:999999999999',
            'receiptSupplierId' => 'nullable|integer|exists:suppliers,id',
            'receiptWarehouseId' => 'required|integer|exists:warehouses,id',
            'receiptDate' => 'required|date|before_or_equal:today',
            'receiptReceivedAt' => 'required|date|before_or_equal:now',
            'receiptNotes' => 'nullable|string|max:500',
            'receiptReason' => 'nullable|string|max:500',
            'receiptReference' => 'nullable|string|max:100',
        ]);

        try {
            $affectedHouseCost = 0.0;
            DB::transaction(function () use (&$affectedHouseCost): void {
                $receipt = StockIn::where('entry_type', 'receipt')->lockForUpdate()->findOrFail($this->activeReceiptId);
                $material = Material::lockForUpdate()->findOrFail($receipt->material_id);
                $directUsedQuantity = (float) MaterialUsage::where('stock_in_id', $receipt->id)
                    ->whereNull('voided_at')->lockForUpdate()->sum('quantity');
                $transferredQuantity = (float) InventoryTransfer::where('source_stock_in_id', $receipt->id)->sum('quantity');
                $batchIds = $this->downstreamBatchIds($receipt);
                $usages = MaterialUsage::whereIn('stock_in_id', $batchIds)
                    ->whereNull('voided_at')->lockForUpdate()->get();
                $reservedQuantity = $receipt->reservedQuantityForUpdate();
                $oldQuantity = (float) $receipt->quantity;
                $newQuantity = (float) $this->receiptQuantity;
                $oldPrice = (float) $receipt->unit_price;
                $newPrice = (float) $this->receiptUnitPrice;
                $quantityDelta = round($newQuantity - $oldQuantity, 2);
                $priceChanged = abs($newPrice - $oldPrice) > 0.001;
                $quantityChanged = abs($quantityDelta) > 0.001;
                $warehouseChanged = (int) $receipt->warehouse_id !== (int) $this->receiptWarehouseId;

                if (($quantityChanged || $priceChanged || $warehouseChanged) && trim($this->receiptReason) === '') {
                    throw new \RuntimeException('Isi alasan jika mengubah jumlah, harga, atau gudang.');
                }
                if ($warehouseChanged && ($directUsedQuantity > 0 || $transferredQuantity > 0 || $reservedQuantity > 0)) {
                    throw new \RuntimeException('Gudang tidak dapat diubah setelah stok digunakan atau dicadangkan.');
                }
                $newRemaining = round((float) $receipt->remaining_quantity + $quantityDelta, 2);
                if ($newQuantity + 0.001 < $directUsedQuantity + $transferredQuantity + $reservedQuantity || $newRemaining + 0.001 < $reservedQuantity) {
                    throw new \RuntimeException('Jumlah baru tidak boleh lebih kecil dari stok yang sudah digunakan, dipindahkan, atau dicadangkan.');
                }

                $reason = trim($this->receiptReason);
                if (trim($this->receiptReference) !== '') {
                    $reason .= ' (Referensi: '.trim($this->receiptReference).')';
                }

                if ($quantityChanged) {
                    $receipt->inventoryAdjustments()->create([
                        'user_id' => auth()->id(), 'field' => 'quantity_received',
                        'before_value' => $oldQuantity, 'after_value' => $newQuantity,
                        'delta' => $quantityDelta, 'reason' => $reason,
                    ]);
                    $material->increment('stock', $quantityDelta);
                }
                if ($priceChanged) {
                    $receipt->inventoryAdjustments()->create([
                        'user_id' => auth()->id(), 'field' => 'unit_price',
                        'before_value' => $oldPrice, 'after_value' => $newPrice,
                        'delta' => round($newPrice - $oldPrice, 2), 'reason' => $reason,
                    ]);

                    $downstreamBatches = StockIn::whereIn('id', array_slice($batchIds, 1))->lockForUpdate()->get();
                    foreach ($downstreamBatches as $downstreamBatch) {
                        $previousPrice = (float) $downstreamBatch->unit_price;
                        if (abs($previousPrice - $newPrice) < 0.001) continue;
                        $downstreamBatch->inventoryAdjustments()->create([
                            'user_id' => auth()->id(), 'field' => 'unit_price',
                            'before_value' => $previousPrice, 'after_value' => $newPrice,
                            'delta' => round($newPrice - $previousPrice, 2), 'reason' => $reason.' (Batch asal '.$receipt->entry_code.')',
                        ]);
                        $downstreamBatch->update(['unit_price' => $newPrice]);
                    }

                    foreach ($usages as $usage) {
                        $oldCost = (float) $usage->total_cost;
                        $newCost = round((float) $usage->quantity * $newPrice, 2);
                        $costDelta = round($newCost - $oldCost, 2);
                        if (abs($costDelta) < 0.01) continue;

                        $usage->update(['total_cost' => $newCost]);
                        $usage->inventoryAdjustments()->create([
                            'user_id' => auth()->id(), 'field' => 'total_cost',
                            'before_value' => $oldCost, 'after_value' => $newCost,
                            'delta' => $costDelta, 'reason' => $reason,
                        ]);
                        $affectedHouseCost += $costDelta;
                    }
                }
                if ($warehouseChanged) {
                    $oldWarehouse = Warehouse::find($receipt->warehouse_id)?->name ?? 'Tidak diketahui';
                    $newWarehouse = Warehouse::find($this->receiptWarehouseId)?->name ?? 'Tidak diketahui';
                    $receipt->inventoryAdjustments()->create([
                        'user_id' => auth()->id(), 'field' => 'warehouse_id',
                        'before_value' => (int) $receipt->warehouse_id,
                        'after_value' => (int) $this->receiptWarehouseId,
                        'delta' => (int) $this->receiptWarehouseId - (int) $receipt->warehouse_id,
                        'reason' => $reason.' (Gudang: '.$oldWarehouse.' → '.$newWarehouse.')',
                    ]);
                }

                $receipt->update([
                    'quantity' => $newQuantity,
                    'remaining_quantity' => $newRemaining,
                    'unit_price' => $newPrice,
                    'total_cost' => round($newQuantity * $newPrice, 2),
                    'supplier_id' => $this->receiptSupplierId ?: null,
                    'warehouse_id' => $this->receiptWarehouseId,
                    'date' => $this->receiptDate,
                    'received_at' => $this->receiptReceivedAt,
                    'notes' => trim($this->receiptNotes) ?: null,
                ]);
            });

            cache()->forget('total_material_spent');
            cache()->forget('dashboard_total_cost');
            cache()->forget('dashboard_low_stock_count');
            $this->showReceiptModal = false;
            session()->flash('success', 'Barang masuk berhasil diperbarui.'.(abs($affectedHouseCost) > 0.001
                ? ' Total biaya rumah terkoreksi '.($affectedHouseCost > 0 ? '+' : '-').'Rp '.number_format(abs($affectedHouseCost), 0, ',', '.').'.'
                : ''));
        } catch (\Throwable $exception) {
            $this->addError('receiptCorrection', $exception->getMessage());
        }
    }

    public function openReceiptHistory(int $id): void
    {
        $this->ensureInventoryAccess();
        StockIn::findOrFail($id);
        $this->historyReceiptId = $id;
        $this->showReceiptHistoryModal = true;
    }

    public function openReceiptCancel(int $id): void
    {
        $this->ensureInventoryAccess();
        StockIn::where('entry_type', 'receipt')->findOrFail($id);
        $this->cancelReceiptId = $id;
        $this->receiptCancelReason = '';
        $this->resetValidation('receiptCancelReason');
        $this->showReceiptCancelModal = true;
    }

    public function cancelReceipt(): void
    {
        $this->ensureInventoryAccess();
        $this->validate(['receiptCancelReason' => 'required|string|max:500']);
        try {
            DB::transaction(function (): void {
                $receipt = StockIn::where('entry_type', 'receipt')->lockForUpdate()->findOrFail($this->cancelReceiptId);
                if (MaterialUsage::where('stock_in_id', $receipt->id)->whereNull('voided_at')->exists()
                    || InventoryTransfer::where('source_stock_in_id', $receipt->id)->exists()
                    || $receipt->reservedQuantityForUpdate() > 0) {
                    throw new \RuntimeException('Barang masuk hanya dapat dibatalkan sebelum stok digunakan, dipindahkan, atau dicadangkan.');
                }

                $remaining = (float) $receipt->remaining_quantity;
                Material::lockForUpdate()->findOrFail($receipt->material_id)->decrement('stock', $remaining);
                $receipt->inventoryAdjustments()->create([
                    'user_id' => auth()->id(), 'field' => 'cancelled',
                    'before_value' => 0, 'after_value' => 1, 'delta' => 1,
                    'reason' => trim($this->receiptCancelReason),
                ]);
                $receipt->update(['remaining_quantity' => 0, 'entry_type' => 'cancelled']);
            });
            cache()->forget('dashboard_low_stock_count');
            $this->showReceiptCancelModal = false;
            $this->showReceiptModal = false;
            session()->flash('success', 'Barang masuk dibatalkan; stok dikembalikan dari persediaan.');
        } catch (\Throwable $exception) {
            $this->addError('receiptCancelReason', $exception->getMessage());
        }
    }

    /**
     * B5 — void a material allocation: restore stock, flag the row.
     * Voided rows STAY visible in the log (VOIDED badge) but are excluded
     * from every cost aggregate/export via whereNull('voided_at').
     */
    public function voidMaterial(int $usageId)
    {
        if (! in_array(auth()->user()->role, ['admin', 'logistik', 'keuangan'], true)) {
            abort(403);
        }

        try {
            DB::transaction(function () use ($usageId) {
                $usage = MaterialUsage::whereHas('house', fn ($query) => $query->forUser(auth()->user()))
                    ->lockForUpdate()->findOrFail($usageId);
                if (! is_null($usage->voided_at)) {
                    throw new \Exception('Alokasi ini sudah dibatalkan sebelumnya.');
                }

                $material = Material::lockForUpdate()->findOrFail($usage->material_id);
                $material->increment('stock', $usage->quantity);
                if ($usage->stock_in_id) {
                    StockIn::lockForUpdate()->findOrFail($usage->stock_in_id)
                        ->increment('remaining_quantity', $usage->quantity);
                }

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
            ->whereHas('house', fn ($query) => $query->forUser(auth()->user()))
            ->when($this->search, fn ($q) => $q->where(fn ($query) => $query
                ->where('transaction_code', 'like', "%{$this->search}%")
                ->orWhereHas('material', fn ($material) => $material->where('name', 'like', "%{$this->search}%"))))
            ->when($this->filterHouse, fn ($q) => $q->where('house_id', $this->filterHouse));
    }

    protected function getMasukQuery()
    {
        return StockIn::with(['material', 'supplier', 'user'])
            ->when($this->search, fn ($q) => $q->where(fn ($query) => $query
                ->where('entry_code', 'like', "%{$this->search}%")
                ->orWhereHas('material', fn ($mq) => $mq->where('name', 'like', "%{$this->search}%"))))
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
                'material_usages.stock_in_id as stock_in_id',
                'material_usages.usage_date as date',
                'material_usages.transaction_code as transaction_code',
                'material_usages.dispatch_code as dispatch_code',
                'stock_ins.entry_code as batch_code',
                DB::raw('NULL as received_at'),
                'users.name as admin_name',
                'users.role as admin_role',
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
            ->leftJoin('stock_ins', 'material_usages.stock_in_id', '=', 'stock_ins.id')
            ->join('houses', 'material_usages.house_id', '=', 'houses.id')
            ->join('users', 'material_usages.user_id', '=', 'users.id')
            ->leftJoin('suppliers', 'materials.supplier_id', '=', 'suppliers.id')
            ->addSelect(DB::raw("(SELECT COUNT(*) FROM inventory_adjustments ia WHERE ia.adjustable_type = '".addslashes((new MaterialUsage)->getMorphClass())."' AND ia.adjustable_id = material_usages.id AND ia.field = 'total_cost') as correction_count"))
            ->addSelect(DB::raw('(SELECT COALESCE(SUM(mu.quantity), 0) FROM material_usages mu WHERE mu.stock_in_id = material_usages.stock_in_id AND mu.voided_at IS NULL) as used_quantity'))
            ->addSelect(DB::raw('0 as reserved_quantity'))
            ->addSelect(DB::raw('0 as transferred_quantity'))
            ->addSelect(DB::raw('0 as price_correction_count'))
            ->when(auth()->user()->role === 'logistik', fn ($q) => $q->where('houses.cluster_id', auth()->user()->cluster_id ?? 0))
            ->when($this->search, fn ($q) => $q->where(fn ($query) => $query
                ->where('materials.name', 'like', "%{$this->search}%")
                ->orWhere('material_usages.transaction_code', 'like', "%{$this->search}%")
                ->orWhere('material_usages.dispatch_code', 'like', "%{$this->search}%")
                ->orWhere('stock_ins.entry_code', 'like', "%{$this->search}%")))
            ->when($this->filterHouse, fn ($q) => $q->where('material_usages.house_id', $this->filterHouse));

        $masukQuery = StockIn::query()
            ->select(
                DB::raw("CASE WHEN stock_ins.entry_type = 'opening_balance' THEN 'saldo_awal' WHEN stock_ins.entry_type = 'transfer' THEN 'transfer_masuk' WHEN stock_ins.entry_type = 'cancelled' THEN 'dibatalkan' ELSE 'masuk' END as type"),
                'stock_ins.id as id',
                DB::raw('NULL as voided_at'),
                'stock_ins.id as stock_in_id',
                'stock_ins.date',
                'stock_ins.entry_code as transaction_code',
                DB::raw('NULL as dispatch_code'),
                'stock_ins.entry_code as batch_code',
                'stock_ins.received_at as received_at',
                DB::raw("COALESCE(users.name, 'Tidak tercatat') as admin_name"),
                'users.role as admin_role',
                DB::raw("CASE WHEN stock_ins.entry_type = 'transfer' THEN CONCAT('Asal: ', COALESCE(transfer_source.name, 'tidak tercatat'), '; tujuan: ', COALESCE(transfer_destination.name, 'tidak tercatat')) ELSE '-' END as house_name"),
                'stock_ins.notes as job_notes',
                'materials.code as item_code',
                'materials.name as item_name',
                'stock_ins.quantity as volume',
                'materials.unit as unit',
                'stock_ins.unit_price as unit_price',
                'stock_ins.total_cost as total_cost',
                DB::raw("CASE WHEN stock_ins.entry_type = 'transfer' THEN 'Transfer gudang' ELSE suppliers.name END as supplier_name"),
                'stock_ins.created_at as created_at'
            )
            ->addSelect(DB::raw("(SELECT COUNT(*) FROM inventory_adjustments ia WHERE ia.adjustable_type = '".addslashes((new StockIn)->getMorphClass())."' AND ia.adjustable_id = stock_ins.id) as correction_count"))
            ->addSelect(DB::raw('(SELECT COALESCE(SUM(mu.quantity), 0) FROM material_usages mu WHERE mu.stock_in_id = stock_ins.id AND mu.voided_at IS NULL) as used_quantity'))
            ->addSelect(DB::raw("(SELECT COALESCE(SUM(mtr.quantity), 0) FROM material_tool_requests mtr WHERE mtr.stock_in_id = stock_ins.id AND mtr.type = 'material' AND mtr.status = 'pending') as reserved_quantity"))
            ->addSelect(DB::raw('(SELECT COALESCE(SUM(it.quantity), 0) FROM inventory_transfers it WHERE it.source_stock_in_id = stock_ins.id) as transferred_quantity'))
            ->addSelect(DB::raw("(SELECT COUNT(*) FROM inventory_adjustments ia WHERE ia.adjustable_type = '".addslashes((new StockIn)->getMorphClass())."' AND ia.adjustable_id = stock_ins.id AND ia.field = 'unit_price') as price_correction_count"))
            ->join('materials', 'stock_ins.material_id', '=', 'materials.id')
            ->leftJoin('inventory_transfers as inventory_transfer', 'inventory_transfer.destination_stock_in_id', '=', 'stock_ins.id')
            ->leftJoin('warehouses as transfer_source', 'inventory_transfer.source_warehouse_id', '=', 'transfer_source.id')
            ->leftJoin('warehouses as transfer_destination', 'inventory_transfer.destination_warehouse_id', '=', 'transfer_destination.id')
            ->leftJoin('suppliers', 'stock_ins.supplier_id', '=', 'suppliers.id')
            ->leftJoin('users', 'stock_ins.user_id', '=', 'users.id')
            ->when($this->search, fn ($q) => $q->where(fn ($query) => $query
                ->where('materials.name', 'like', "%{$this->search}%")
                ->orWhere('stock_ins.entry_code', 'like', "%{$this->search}%")
                ->orWhere('transfer_source.name', 'like', "%{$this->search}%")
                ->orWhere('transfer_destination.name', 'like', "%{$this->search}%")))
            ->when($this->filterSupplier, fn ($q) => $q->where('stock_ins.supplier_id', $this->filterSupplier));

        if ($this->filterType === 'masuk') {
            $unionQuery = $masukQuery;
        } elseif ($this->filterType === 'keluar') {
            $unionQuery = $keluarQuery;
        } else {
            $unionQuery = $keluarQuery->unionAll($masukQuery);
        }

        $query = DB::table(DB::raw("({$unionQuery->toSql()}) as combined"))
            ->mergeBindings($unionQuery->getQuery());

        [$field, $direction] = $this->tableSortParts();
        $this->applyTableSort($query);

        if ($field === 'date') {
            $query->orderBy('created_at', $direction);
        } else {
            $query->orderByDesc('date')->orderByDesc('created_at');
        }

        return $query->paginate($perPage);
    }

    public function render()
    {
        $houses = House::forUser(auth()->user())->orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();
        $activeReceipt = $this->showReceiptModal && $this->activeReceiptId
            ? StockIn::with('material')->find($this->activeReceiptId)
            : null;
        $activeReceiptUsed = $activeReceipt
            ? (float) MaterialUsage::where('stock_in_id', $activeReceipt->id)->whereNull('voided_at')->sum('quantity')
            : 0;
        $activeReceiptTransferred = $activeReceipt
            ? (float) InventoryTransfer::where('source_stock_in_id', $activeReceipt->id)->sum('quantity')
            : 0;
        $activeReceiptReserved = $activeReceipt ? (float) $activeReceipt->reservations()->sum('quantity') : 0;
        $receiptHistory = collect();
        $historyReceipt = null;
        if ($this->showReceiptHistoryModal && $this->historyReceiptId) {
            $historyReceipt = StockIn::find($this->historyReceiptId);
            if ($historyReceipt) {
                $receiptHistory = InventoryAdjustment::with('user')
                    ->where('adjustable_type', $historyReceipt->getMorphClass())
                    ->whereIn('adjustable_id', $this->downstreamBatchIds($historyReceipt))
                    ->latest()->get();
                $usageAdjustments = InventoryAdjustment::with('user')
                    ->where('adjustable_type', (new MaterialUsage)->getMorphClass())
                    ->whereIn('adjustable_id', MaterialUsage::whereIn('stock_in_id', $this->downstreamBatchIds($historyReceipt))->pluck('id'))
                    ->where('field', 'total_cost')->latest()->get();
                $receiptHistory = $receiptHistory->concat($usageAdjustments)->sortByDesc('created_at')->values();
            }
        }

        $records = $this->buildCombinedRecords();

        return view('livewire.logistik.material-log', compact('records', 'houses', 'suppliers', 'warehouses', 'receiptHistory', 'historyReceipt', 'activeReceipt', 'activeReceiptUsed', 'activeReceiptTransferred', 'activeReceiptReserved'))
            ->layout('layouts.app', ['title' => 'Catatan Material']);
    }
}

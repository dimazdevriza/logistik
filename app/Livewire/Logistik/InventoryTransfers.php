<?php

namespace App\Livewire\Logistik;

use App\Models\InventoryTransfer;
use App\Models\Material;
use App\Models\StockIn;
use App\Models\Tool;
use App\Models\Warehouse;
use App\Support\ToolInventory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class InventoryTransfers extends Component
{
    public string $inventory_type = 'material';

    public string $source_warehouse_id = '';

    public string $destination_warehouse_id = '';

    public string $item_id = '';

    public string $stock_in_id = '';

    public string $quantity = '1';

    public string $transferred_at = '';

    public string $notes = '';

    public function mount(): void
    {
        $this->transferred_at = now()->toDateString();
    }

    public function updatedInventoryType(): void
    {
        $this->item_id = '';
        $this->stock_in_id = '';
        $this->quantity = '1';
    }

    public function updatedSourceWarehouseId(): void
    {
        $this->item_id = '';
        $this->stock_in_id = '';
    }

    public function updatedItemId(): void
    {
        $this->stock_in_id = '';
    }

    protected function rules(): array
    {
        return [
            'inventory_type' => ['required', 'in:material,tool'],
            'source_warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'destination_warehouse_id' => ['required', 'integer', 'exists:warehouses,id', 'different:source_warehouse_id'],
            'item_id' => ['required', 'integer'],
            'stock_in_id' => [$this->inventory_type === 'material' ? 'required' : 'nullable', 'integer'],
            'quantity' => ['required', 'numeric', 'min:0.01', ...($this->inventory_type === 'material' ? ['decimal:0,2'] : [])],
            'transferred_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function save(): void
    {
        $data = $this->validate();
        $quantity = (float) $data['quantity'];

        DB::transaction(function () use ($data, $quantity) {
            if ($data['inventory_type'] === 'material') {
                $source = Material::whereKey($data['item_id'])
                    ->where('warehouse_id', $data['source_warehouse_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $sourceBatch = StockIn::whereKey($data['stock_in_id'])
                    ->where('material_id', $source->id)
                    ->where('warehouse_id', $data['source_warehouse_id'])
                    ->whereAvailableQuantity($quantity)
                    ->lockForUpdate()
                    ->first();
                if (! $sourceBatch || $sourceBatch->remaining_quantity === null) {
                    throw ValidationException::withMessages(['stock_in_id' => 'Batch sumber tidak tersedia atau jumlahnya berubah. Muat ulang lalu pilih batch lagi.']);
                }
                if ((float) $sourceBatch->remaining_quantity - $sourceBatch->reservedQuantityForUpdate() + 0.001 < $quantity) {
                    throw ValidationException::withMessages(['stock_in_id' => 'Sebagian saldo batch sedang dicadangkan untuk permintaan yang belum dikirim. Pilih batch atau jumlah lain.']);
                }
                if ((float) $source->stock < $quantity) {
                    throw ValidationException::withMessages(['quantity' => 'Stok gudang asal tidak mencukupi.']);
                }

                $destination = Material::where('warehouse_id', $data['destination_warehouse_id'])
                    ->where('name', $source->name)
                    ->where('unit', $source->unit)
                    ->where('unit_price', $sourceBatch->unit_price)
                    ->where('category_id', $source->category_id)
                    ->where('supplier_id', $sourceBatch->supplier_id)
                    ->where('code', $source->code)
                    ->lockForUpdate()
                    ->first();
                if ($destination) {
                    $destination->increment('stock', $quantity);
                } else {
                    $destination = Material::create([
                        'warehouse_id' => $data['destination_warehouse_id'],
                        'code' => $source->code,
                        'supplier_id' => $sourceBatch->supplier_id,
                        'category_id' => $source->category_id,
                        'name' => $source->name,
                        'unit' => $source->unit,
                        'unit_price' => $sourceBatch->unit_price,
                        'stock' => $quantity,
                        'image' => $source->image,
                    ]);
                }
                $source->decrement('stock', $quantity);
                $sourceBatch->decrement('remaining_quantity', $quantity);

                $transfer = $this->createTransfer($data, $source->id, null, $quantity, $destination->id);
                $destinationBatch = StockIn::create([
                    'transaction_code' => $transfer->transfer_code,
                    'entry_code' => $transfer->transfer_code,
                    'entry_type' => 'transfer',
                    'material_id' => $destination->id,
                    'warehouse_id' => $destination->warehouse_id,
                    'supplier_id' => $sourceBatch->supplier_id,
                    'user_id' => auth()->id(),
                    'quantity' => $quantity,
                    'unit_price' => $sourceBatch->unit_price,
                    'total_cost' => 0,
                    'date' => $data['transferred_at'],
                    'remaining_quantity' => $quantity,
                    'notes' => 'Transfer dari batch '.$sourceBatch->entry_code,
                ]);
                $transfer->update([
                    'source_stock_in_id' => $sourceBatch->id,
                    'destination_stock_in_id' => $destinationBatch->id,
                ]);
            } else {
                if ($quantity < 1 || floor($quantity) !== $quantity) {
                    throw ValidationException::withMessages(['quantity' => 'Jumlah alat harus berupa unit bulat minimal 1.']);
                }

                $source = Tool::whereKey($data['item_id'])->lockForUpdate()->firstOrFail();
                ToolInventory::transferAvailable(
                    $source,
                    (int) $data['source_warehouse_id'],
                    (int) $data['destination_warehouse_id'],
                    (int) $quantity,
                );
                $this->createTransfer($data, null, $source->id, $quantity);
            }
        });

        $this->reset(['item_id', 'stock_in_id', 'notes']);
        $this->quantity = '1';
        session()->flash('success', 'Transfer gudang berhasil dicatat.');
    }

    private function createTransfer(array $data, ?int $materialId, ?int $toolId, float $quantity, ?int $destinationMaterialId = null): InventoryTransfer
    {
        return InventoryTransfer::create([
            'transfer_code' => 'TRF-'.Str::ulid(),
            'source_warehouse_id' => $data['source_warehouse_id'],
            'destination_warehouse_id' => $data['destination_warehouse_id'],
            'material_id' => $materialId,
            'destination_material_id' => $destinationMaterialId,
            'tool_id' => $toolId,
            'quantity' => $quantity,
            'created_by' => auth()->id(),
            'transferred_at' => $data['transferred_at'],
            'notes' => $data['notes'] ?: null,
        ]);
    }

    public function render()
    {
        $warehouses = Warehouse::orderBy('name')->get(['id', 'name']);
        $items = $this->inventory_type === 'material'
            ? Material::where('warehouse_id', $this->source_warehouse_id ?: 0)
                ->where('stock', '>', 0)
                ->whereHas('stockIns', fn ($query) => $query->where('warehouse_id', $this->source_warehouse_id ?: 0)->whereAvailableQuantity())
                ->orderBy('name')
                ->get(['id', 'name', 'unit', 'stock', 'unit_price'])
            : Tool::with(['warehouseBalances' => fn ($query) => $query->where('warehouse_id', $this->source_warehouse_id ?: 0)])
                ->whereHas('warehouseBalances', fn ($query) => $query
                    ->where('warehouse_id', $this->source_warehouse_id ?: 0)
                    ->where('available_qty', '>', 0))
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'available_qty', 'total_qty']);
        $batches = $this->inventory_type === 'material' && $this->item_id
            ? StockIn::withReservedQuantity()
                ->where('material_id', $this->item_id)
                ->where('warehouse_id', $this->source_warehouse_id ?: 0)
                ->whereNotNull('remaining_quantity')
                ->whereAvailableQuantity()
                ->orderBy('received_at')
                ->orderBy('id')
                ->get(['id', 'entry_code', 'entry_type', 'remaining_quantity', 'unit_price', 'received_at', 'date'])
            : collect();
        return view('livewire.logistik.inventory-transfers', compact('warehouses', 'items', 'batches'))
            ->layout('layouts.app', ['title' => 'Transfer Gudang']);
    }
}

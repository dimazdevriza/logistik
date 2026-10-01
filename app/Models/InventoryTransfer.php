<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_code', 'source_warehouse_id', 'destination_warehouse_id', 'material_id', 'destination_material_id', 'source_stock_in_id', 'destination_stock_in_id', 'tool_id',
        'quantity', 'created_by', 'transferred_at', 'notes',
    ];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:2', 'transferred_at' => 'date'];
    }

    public function sourceWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'source_warehouse_id');
    }

    public function destinationWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function destinationMaterial(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'destination_material_id');
    }

    public function sourceBatch(): BelongsTo
    {
        return $this->belongsTo(StockIn::class, 'source_stock_in_id');
    }

    public function destinationBatch(): BelongsTo
    {
        return $this->belongsTo(StockIn::class, 'destination_stock_in_id');
    }

    public function tool(): BelongsTo
    {
        return $this->belongsTo(Tool::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Material extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'code',
        'supplier_id',
        'category_id',
        'name',
        'unit',
        'unit_price',
        'stock',
        'image',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'stock' => 'decimal:2',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function usages(): HasMany
    {
        return $this->hasMany(MaterialUsage::class);
    }

    public function stockIns(): HasMany
    {
        return $this->hasMany(StockIn::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function inventoryAdjustments(): MorphMany
    {
        return $this->morphMany(InventoryAdjustment::class, 'adjustable');
    }

    public function inventoryTransfers(): HasMany
    {
        return $this->hasMany(InventoryTransfer::class);
    }

    public function receivedInventoryTransfers(): HasMany
    {
        return $this->hasMany(InventoryTransfer::class, 'destination_material_id');
    }

    public function hasTransactionHistory(): bool
    {
        return $this->stockIns()->exists()
            || $this->usages()->exists()
            || MaterialToolRequest::where('material_id', $this->id)->exists()
            || $this->inventoryAdjustments()->exists()
            || $this->inventoryTransfers()->exists()
            || $this->receivedInventoryTransfers()->exists();
    }
}

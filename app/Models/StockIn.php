<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class StockIn extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_code',
        'entry_code',
        'entry_type',
        'submission_key',
        'material_id',
        'warehouse_id',
        'supplier_id',
        'user_id',
        'quantity',
        'unit_price',
        'total_cost',
        'date',
        'received_at',
        'remaining_quantity',
        'notes',
        'proof_image',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'quantity' => 'decimal:2',
            'remaining_quantity' => 'decimal:2',
            'date' => 'date',
            'received_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (StockIn $stockIn): void {
            $stockIn->entry_code ??= 'MSK-'.Str::ulid();
            $stockIn->remaining_quantity ??= $stockIn->quantity;
            $stockIn->warehouse_id ??= Material::whereKey($stockIn->material_id)->value('warehouse_id');
        });
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(MaterialToolRequest::class)
            ->where('type', 'material')
            ->where('status', 'pending');
    }

    public function inventoryAdjustments(): MorphMany
    {
        return $this->morphMany(InventoryAdjustment::class, 'adjustable');
    }

    public function scopeWithReservedQuantity(Builder $query): Builder
    {
        return $query->withSum('reservations as reserved_quantity', 'quantity');
    }

    public function scopeWhereAvailableQuantity(Builder $query, float $minimum = 0.01): Builder
    {
        return $query->whereRaw(
            'COALESCE(stock_ins.remaining_quantity, 0) - COALESCE((SELECT SUM(material_tool_requests.quantity) FROM material_tool_requests WHERE material_tool_requests.stock_in_id = stock_ins.id AND material_tool_requests.type = ? AND material_tool_requests.status = ?), 0) >= ?',
            ['material', 'pending', $minimum],
        );
    }

    public function reservedQuantityForUpdate(): float
    {
        return (float) $this->reservations()
            ->lockForUpdate()
            ->get(['id', 'quantity'])
            ->sum('quantity');
    }

    public function getAvailableQuantityAttribute(): float
    {
        $reserved = array_key_exists('reserved_quantity', $this->attributes)
            ? $this->attributes['reserved_quantity']
            : $this->reservations()->sum('quantity');

        return max(0, (float) $this->remaining_quantity - (float) $reserved);
    }
}

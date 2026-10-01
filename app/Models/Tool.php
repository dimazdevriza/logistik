<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class Tool extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'category_id',
        'name',
        'code',
        'entry_code',
        'entry_type',
        'submission_key',
        'condition',
        'purchase_price',
        'total_qty',
        'available_qty',
        'qty_broken',
        'image',
        'received_at',
        'received_date',
        'recorded_by_id',
    ];

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',
            'total_qty' => 'integer',
            'available_qty' => 'integer',
            'qty_broken' => 'integer',
            'received_at' => 'datetime',
            'received_date' => 'date',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    protected static function booted(): void
    {
        static::creating(function (Tool $tool): void {
            $tool->entry_code ??= 'MSK-'.Str::ulid();
        });

        static::created(function (Tool $tool): void {
            if ($tool->warehouse_id) {
                ToolWarehouseBalance::create([
                    'tool_id' => $tool->id,
                    'warehouse_id' => $tool->warehouse_id,
                    'available_qty' => (int) ($tool->available_qty ?? 0),
                    'qty_broken' => (int) ($tool->qty_broken ?? 0),
                ]);
            }
        });
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_id');
    }

    public function warehouseBalances(): HasMany
    {
        return $this->hasMany(ToolWarehouseBalance::class);
    }

    public function usages(): HasMany
    {
        return $this->hasMany(ToolUsage::class);
    }

    public function returnLogs(): HasMany
    {
        return $this->hasMany(ToolReturnLog::class);
    }

    public function inventoryAdjustments(): MorphMany
    {
        return $this->morphMany(InventoryAdjustment::class, 'adjustable');
    }

    public function inventoryTransfers(): HasMany
    {
        return $this->hasMany(InventoryTransfer::class);
    }

    public function hasTransactionHistory(): bool
    {
        return $this->usages()->exists()
            || $this->returnLogs()->exists()
            || MaterialToolRequest::where('tool_id', $this->id)->exists()
            || $this->inventoryAdjustments()->exists()
            || $this->inventoryTransfers()->exists();
    }
}

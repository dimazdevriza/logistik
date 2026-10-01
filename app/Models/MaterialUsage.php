<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class MaterialUsage extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_code',
        'dispatch_code',
        'house_id',
        'material_id',
        'stock_in_id',
        'user_id',
        'quantity',
        'unit_price_at_usage',
        'total_cost',
        'usage_date',
        'notes',
        'taken_by',
        'proof_image',
        'is_warranty',
        'voided_at',
        'voided_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price_at_usage' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'usage_date' => 'date',
            'is_warranty' => 'boolean',
            'voided_at' => 'datetime',
        ];
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function house(): BelongsTo
    {
        return $this->belongsTo(House::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function stockIn(): BelongsTo
    {
        return $this->belongsTo(StockIn::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function inventoryAdjustments(): MorphMany
    {
        return $this->morphMany(InventoryAdjustment::class, 'adjustable');
    }
}

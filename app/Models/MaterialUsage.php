<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialUsage extends Model
{
    use HasFactory;

    protected $fillable = [
        'house_id',
        'material_id',
        'user_id',
        'quantity',
        'unit_price_at_usage',
        'total_cost',
        'usage_date',
        'notes',
        'proof_image',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

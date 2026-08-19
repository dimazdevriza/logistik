<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockIn extends Model
{
    use HasFactory;

    protected $fillable = [
        'material_id',
        'supplier_id',
        'user_id',
        'quantity',
        'unit_price',
        'total_cost',
        'date',
        'notes',
        'proof_image',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'quantity' => 'decimal:2',
            'date' => 'date',
        ];
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
}

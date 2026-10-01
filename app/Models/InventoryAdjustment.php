<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InventoryAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'adjustable_type',
        'adjustable_id',
        'field',
        'before_value',
        'after_value',
        'delta',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'before_value' => 'decimal:2',
            'after_value' => 'decimal:2',
            'delta' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function adjustable(): MorphTo
    {
        return $this->morphTo();
    }
}

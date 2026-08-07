<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ToolUsage extends Model
{
    use HasFactory;

    protected $fillable = [
        'house_id',
        'tool_id',
        'user_id',
        'quantity',
        'checkout_date',
        'return_date',
        'notes',
        'parent_usage_id',
        'voided_at',
        'voided_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'checkout_date' => 'date',
            'return_date' => 'date',
            'voided_at' => 'datetime',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_usage_id');
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function house(): BelongsTo
    {
        return $this->belongsTo(House::class);
    }

    public function tool(): BelongsTo
    {
        return $this->belongsTo(Tool::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

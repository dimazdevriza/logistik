<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ToolUsage extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_code',
        'dispatch_code',
        'dispatch_line_id',
        'house_id',
        'tool_id',
        'warehouse_id',
        'warehouse_source_recorded',
        'user_id',
        'quantity',
        'checkout_date',
        'return_date',
        'notes',
        'proof_image',
        'is_warranty',
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
            'warehouse_source_recorded' => 'boolean',
            'is_warranty' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_usage_id');
    }

    public function dispatchLine(): BelongsTo
    {
        return $this->belongsTo(DispatchLine::class);
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

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

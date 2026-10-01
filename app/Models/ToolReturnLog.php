<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ToolReturnLog extends Model
{
    protected $table = 'tool_return_logs';

    protected $fillable = [
        'transaction_code',
        'tool_id',
        'house_id',
        'tool_usage_id',
        'reported_by',
        'receiving_warehouse_id',
        'received_at',
        'received_by_id',
        'resolved_by_id',
        'resolved_at',
        'quantity',
        'report_type',
        'status',
        'replacement_cost',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'replacement_cost' => 'decimal:2',
            'quantity' => 'integer',
            'received_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function tool(): BelongsTo
    {
        return $this->belongsTo(Tool::class);
    }

    public function house(): BelongsTo
    {
        return $this->belongsTo(House::class);
    }

    public function toolUsage(): BelongsTo
    {
        return $this->belongsTo(ToolUsage::class);
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function receivingWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'receiving_warehouse_id');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by_id');
    }

}

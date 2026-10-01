<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DispatchResolutionEvent extends Model
{
    protected $fillable = [
        'material_tool_request_id', 'dispatch_line_id', 'dispatch_receipt_line_id', 'warehouse_id',
        'recorded_by_id', 'event_type', 'submission_key', 'quantity', 'unit_price', 'total_cost', 'notes', 'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'recorded_at' => 'datetime',
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(MaterialToolRequest::class, 'material_tool_request_id');
    }

    public function dispatchLine(): BelongsTo
    {
        return $this->belongsTo(DispatchLine::class);
    }

    public function receiptLine(): BelongsTo
    {
        return $this->belongsTo(DispatchReceiptLine::class, 'dispatch_receipt_line_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DispatchReceiptLine extends Model
{
    protected $fillable = [
        'dispatch_receipt_id',
        'dispatch_line_id',
        'received_quantity',
        'damaged_quantity',
    ];

    protected function casts(): array
    {
        return [
            'received_quantity' => 'decimal:2',
            'damaged_quantity' => 'decimal:2',
        ];
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(DispatchReceipt::class, 'dispatch_receipt_id');
    }

    public function dispatchLine(): BelongsTo
    {
        return $this->belongsTo(DispatchLine::class);
    }
}

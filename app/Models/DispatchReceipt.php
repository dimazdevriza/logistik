<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DispatchReceipt extends Model
{
    protected $fillable = [
        'material_tool_request_id',
        'received_by_id',
        'correction_of_id',
        'event_type',
        'submission_key',
        'received_at',
        'proof_image',
        'notes',
    ];

    protected function casts(): array
    {
        return ['received_at' => 'datetime'];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(MaterialToolRequest::class, 'material_tool_request_id');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by_id');
    }

    public function correctionOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'correction_of_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(DispatchReceiptLine::class);
    }
}

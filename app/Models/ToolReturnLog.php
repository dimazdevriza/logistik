<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ToolReturnLog extends Model
{
    protected $table = 'tool_return_logs';

    protected $fillable = [
        'tool_id',
        'house_id',
        'tool_usage_id',
        'reported_by',
        'quantity',
        'report_type',  // normal, repair, broken, lost
        'status',       // pending, fixed, discarded
        'replacement_cost',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'replacement_cost' => 'decimal:2',
            'quantity' => 'integer',
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
}

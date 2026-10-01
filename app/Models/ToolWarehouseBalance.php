<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ToolWarehouseBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'tool_id',
        'warehouse_id',
        'available_qty',
        'qty_broken',
    ];

    protected function casts(): array
    {
        return [
            'available_qty' => 'integer',
            'qty_broken' => 'integer',
        ];
    }

    public function tool(): BelongsTo
    {
        return $this->belongsTo(Tool::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}

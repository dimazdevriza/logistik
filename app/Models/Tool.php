<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tool extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'code',
        'condition',
        'purchase_price',
        'total_qty',
        'available_qty',
        'qty_broken',
    ];

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',
            'total_qty' => 'integer',
            'available_qty' => 'integer',
            'qty_broken' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function usages(): HasMany
    {
        return $this->hasMany(ToolUsage::class);
    }

    public function returnLogs(): HasMany
    {
        return $this->hasMany(ToolReturnLog::class);
    }
}

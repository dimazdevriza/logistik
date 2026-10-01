<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaterialToolRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_code',
        'dispatch_code',
        'requester_id',
        'dispatcher_id',
        'approver_id',
        'house_id',
        'type',
        'material_id',
        'stock_in_id',
        'tool_id',
        'source_warehouse_id',
        'quantity',
        'unit_price_at_dispatch',
        'notes',
        'status',
        'dispatch_proof_image',
        'arrival_proof_image',
        'dispatched_at',
        'arrived_at',
        'approved_at',
        'rejected_returned_at',
        'rejected_returned_by_id',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price_at_dispatch' => 'decimal:2',
            'dispatched_at' => 'datetime',
            'arrived_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_returned_at' => 'datetime',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function dispatcher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispatcher_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function house(): BelongsTo
    {
        return $this->belongsTo(House::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function stockIn(): BelongsTo
    {
        return $this->belongsTo(StockIn::class);
    }

    public function tool(): BelongsTo
    {
        return $this->belongsTo(Tool::class);
    }

    public function sourceWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'source_warehouse_id');
    }

    public function rejectedReturnedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_returned_by_id');
    }

    public function dispatchLines(): HasMany
    {
        return $this->hasMany(DispatchLine::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(DispatchReceipt::class);
    }

    public function resolutionEvents(): HasMany
    {
        return $this->hasMany(DispatchResolutionEvent::class);
    }

    public function blocksHouseCompletion(): bool
    {
        return in_array($this->status, ['pending', 'dispatched', 'partially_arrived'], true)
            || (in_array($this->status, ['arrived', 'resolved'], true) && $this->hasUndisposedMaterialDamage())
            || ($this->status === 'rejected' && $this->dispatched_at && ! $this->rejected_returned_at);
    }

    public function hasUndisposedMaterialDamage(): bool
    {
        if ($this->type !== 'material') {
            return false;
        }

        $damagedBySource = DispatchReceiptLine::whereHas('receipt', fn ($query) => $query->where('material_tool_request_id', $this->id))
            ->get(['dispatch_line_id', 'damaged_quantity'])
            ->groupBy(fn ($line) => $line->dispatch_line_id === null ? 'legacy' : (string) $line->dispatch_line_id);

        foreach ($damagedBySource as $sourceKey => $receiptLines) {
            $query = $this->resolutionEvents()->where('event_type', 'dispose_damaged');
            $sourceKey === 'legacy'
                ? $query->whereNull('dispatch_line_id')
                : $query->where('dispatch_line_id', (int) $sourceKey);

            if ((float) $receiptLines->sum('damaged_quantity') - (float) $query->sum('quantity') > 0.001) {
                return true;
            }
        }

        return false;
    }
}

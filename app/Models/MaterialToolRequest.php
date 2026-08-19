<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialToolRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_code',
        'requester_id',
        'dispatcher_id',
        'approver_id',
        'house_id',
        'type',
        'material_id',
        'tool_id',
        'quantity',
        'notes',
        'status',
        'arrival_proof_image',
        'dispatched_at',
        'arrived_at',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'dispatched_at' => 'datetime',
            'arrived_at' => 'datetime',
            'approved_at' => 'datetime',
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

    public function tool(): BelongsTo
    {
        return $this->belongsTo(Tool::class);
    }
}

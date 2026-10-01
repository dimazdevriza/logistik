<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClusterExpense extends Model
{
    use HasFactory;

    protected $fillable = [
        'cluster_id', 'house_id', 'parent_expense_id', 'created_by', 'type', 'description', 'vendor',
        'quantity', 'start_date', 'due_date', 'off_hire_date', 'status', 'amount', 'bill_image', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'amount' => 'decimal:2',
            'start_date' => 'date',
            'due_date' => 'date',
            'off_hire_date' => 'date',
        ];
    }

    public function cluster(): BelongsTo
    {
        return $this->belongsTo(Cluster::class);
    }

    public function house(): BelongsTo
    {
        return $this->belongsTo(House::class);
    }

    public function houses(): BelongsToMany
    {
        return $this->belongsToMany(House::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_expense_id');
    }

    public function extensions(): HasMany
    {
        return $this->hasMany(self::class, 'parent_expense_id');
    }

    public function returns(): HasMany
    {
        return $this->hasMany(self::class, 'parent_expense_id')->where('type', 'rental_return');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isRental(): bool
    {
        return in_array($this->type, ['rental', 'rental_extension', 'rental_return'], true);
    }
}

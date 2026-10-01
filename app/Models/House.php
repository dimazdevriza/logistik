<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class House extends Model
{
    use HasFactory;

    protected $fillable = [
        'cluster_id',
        'house_code',
        'name',
        'type',
        'status',
        'start_date',
        'target_end_date',
        'completed_at',
        'warranty_expires_at',
        'completed_by_id',
    ];

    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $user->role === 'admin'
            ? $query
            : $query->where('cluster_id', $user->cluster_id ?? 0);
    }

    public function isAccessibleBy(User $user): bool
    {
        return in_array($user->role, ['admin', 'keuangan'], true)
            || ($user->cluster_id && (int) $this->cluster_id === (int) $user->cluster_id);
    }

    /**
     * Generate a unique house code: [Year]-[BlokStripped]
     * e.g. "Blok A-01" → "2026-A01"
     */
    public static function generateCode(string $houseName): string
    {
        // Extract blok part: strip "Blok " prefix, remove spaces and dashes
        $blok = $houseName;
        $blok = preg_replace('/^blok\s*/i', '', $blok);
        $blok = str_replace([' ', '-'], '', $blok);
        $blok = strtoupper($blok);

        $year = date('Y');

        return "{$year}-{$blok}";
    }

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'target_end_date' => 'date',
            'completed_at' => 'datetime',
            'warranty_expires_at' => 'datetime',
        ];
    }

    public function materialUsages(): HasMany
    {
        return $this->hasMany(MaterialUsage::class);
    }

    public function cluster(): BelongsTo
    {
        return $this->belongsTo(Cluster::class);
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by_id');
    }

    public function toolUsages(): HasMany
    {
        return $this->hasMany(ToolUsage::class);
    }

    public function hasTransactionHistory(): bool
    {
        return $this->materialUsages()->exists()
            || $this->toolUsages()->exists()
            || MaterialToolRequest::where('house_id', $this->id)->exists()
            || ClusterExpense::query()
                ->where('house_id', $this->id)
                ->orWhereHas('houses', fn ($query) => $query->whereKey($this->id))
                ->exists();
    }

    public function hasOpenRequests(): bool
    {
        return MaterialToolRequest::where('house_id', $this->id)
            ->whereIn('status', ['pending', 'dispatched', 'partially_arrived', 'arrived', 'resolved', 'rejected'])
            ->get()
            ->contains(fn (MaterialToolRequest $request) => $request->blocksHouseCompletion());
    }

    public function scopeEligibleForAllocation(Builder $query): Builder
    {
        return $query->where(fn (Builder $query) => $query
            ->where('status', '!=', 'selesai')
            ->orWhere(fn (Builder $query) => $query
                ->where('status', 'selesai')
                ->where('warranty_expires_at', '>', now())));
    }

    public function isUnderWarranty(): bool
    {
        return $this->status === 'selesai'
            && $this->warranty_expires_at
            && $this->warranty_expires_at->isFuture();
    }

    public function canReceiveAllocations(): bool
    {
        return $this->status !== 'selesai' || $this->isUnderWarranty();
    }

    /**
     * Get total material cost for this house.
     */
    public function getTotalMaterialCostAttribute(): float
    {
        return (float) $this->materialUsages()->whereNull('voided_at')->sum('total_cost');
    }

    /**
     * Use house_code as the route key name instead of the numeric ID.
     */
    public function getRouteKeyName(): string
    {
        return 'house_code';
    }
}

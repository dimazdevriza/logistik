<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'cluster_id',
        'warehouse_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'google_linked_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    public function canAccessAllClusters(): bool
    {
        return in_array($this->role, ['admin', 'keuangan'], true);
    }

    public function canAccessAllWarehouses(): bool
    {
        return in_array($this->role, ['admin', 'keuangan'], true) || ! $this->assignedWarehouseIds();
    }

    /** Pivot is source of truth; legacy column covers rows created before the pivot existed. */
    public function assignedClusterIds(): array
    {
        $ids = $this->relationLoaded('clusters') ? $this->clusters->modelKeys() : $this->clusters()->pluck('clusters.id')->all();

        return array_values(array_unique(array_map('intval', $ids ?: array_filter([$this->cluster_id]))));
    }

    public function assignedWarehouseIds(): array
    {
        if (! in_array($this->role, ['logistik', 'pengawas'], true)) {
            return [];
        }
        $ids = $this->relationLoaded('warehouses') ? $this->warehouses->modelKeys() : $this->warehouses()->pluck('warehouses.id')->all();

        return array_values(array_unique(array_map('intval', $ids ?: array_filter([$this->warehouse_id]))));
    }

    /** For whereIn/whereKey: never empty, so a field user without assignment matches nothing. */
    public function scopedClusterIds(): array
    {
        return $this->assignedClusterIds() ?: [0];
    }

    public function scopedWarehouseIds(): array
    {
        return $this->assignedWarehouseIds() ?: [0];
    }

    /** Set only when the account has exactly one cluster; multi-cluster accounts must choose. */
    public function soleClusterId(): ?int
    {
        $ids = $this->assignedClusterIds();

        return count($ids) === 1 ? $ids[0] : null;
    }

    public function canAccessCluster(int|string|null $clusterId): bool
    {
        return $this->canAccessAllClusters() || ($clusterId && in_array((int) $clusterId, $this->assignedClusterIds(), true));
    }

    public function canAccessWarehouse(int|string|null $warehouseId): bool
    {
        return $this->canAccessAllWarehouses() || ($warehouseId && in_array((int) $warehouseId, $this->assignedWarehouseIds(), true));
    }

    /** UI: show cluster chooser/column for unrestricted accounts and for accounts holding 2+ clusters. */
    public function hasClusterChoice(): bool
    {
        return $this->canAccessAllClusters() || count($this->assignedClusterIds()) > 1;
    }

    /** UI: show gudang chooser/column for unrestricted accounts and for accounts holding 2+ gudang. */
    public function hasWarehouseChoice(): bool
    {
        return $this->canAccessAllWarehouses() || count($this->assignedWarehouseIds()) > 1;
    }

    /** Set only when the account has exactly one gudang; multi-gudang accounts must choose. */
    public function assignedWarehouseId(): ?int
    {
        $ids = $this->assignedWarehouseIds();

        return count($ids) === 1 ? $ids[0] : null;
    }

    public function clusters(): BelongsToMany
    {
        return $this->belongsToMany(Cluster::class);
    }

    public function warehouses(): BelongsToMany
    {
        return $this->belongsToMany(Warehouse::class, 'user_warehouse');
    }
    public function cluster(): BelongsTo
    {
        return $this->belongsTo(Cluster::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function materialUsages(): HasMany
    {
        return $this->hasMany(MaterialUsage::class);
    }

    public function toolUsages(): HasMany
    {
        return $this->hasMany(ToolUsage::class);
    }

    public function hasOperationalHistory(): bool
    {
        return $this->materialUsages()->exists()
            || $this->toolUsages()->exists()
            || StockIn::where('user_id', $this->id)->exists()
            || Tool::where('recorded_by_id', $this->id)->exists()
            || ToolReturnLog::where('reported_by', $this->id)->exists()
            || MaterialUsage::where('voided_by', $this->id)->exists()
            || ToolUsage::where('voided_by', $this->id)->exists()
            || InventoryAdjustment::where('user_id', $this->id)->exists()
            || MaterialToolRequest::where(function ($query) {
                $query->where('requester_id', $this->id)
                    ->orWhere('dispatcher_id', $this->id)
                    ->orWhere('approver_id', $this->id);
            })->exists();
    }
}

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
        'category',
        'house_code',
        'name',
        'type',
        'status',
        'material_budget',
        'physical_progress',
        'progress_stages',
        'start_date',
        'target_end_date',
        'completed_at',
        'warranty_expires_at',
        'completed_by_id',
    ];

    public const CATEGORY_HOUSE = 'house';

    public const CATEGORY_PUBLIC_WORK = 'public_work';

    public function scopeHousesOnly(Builder $query): Builder
    {
        return $query->where('houses.category', self::CATEGORY_HOUSE);
    }

    public function scopePublicWorksOnly(Builder $query): Builder
    {
        return $query->where('houses.category', self::CATEGORY_PUBLIC_WORK);
    }

    public function isPublicWork(): bool
    {
        return $this->category === self::CATEGORY_PUBLIC_WORK;
    }

    /**
     * Public work code: [Year]-PU-[NAME], e.g. "Jalan Utama Blok A" -> "2026-PU-JALANUTAMABLOKA".
     */
    public static function generatePublicWorkCode(string $name): string
    {
        $slug = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $name));
        $base = date('Y').'-PU-'.substr($slug !== '' ? $slug : 'PEKERJAAN', 0, 36);
        $code = $base;
        $suffix = 2;

        while (self::where('house_code', $code)->exists()) {
            $code = $base.'-'.$suffix;
            $suffix++;
        }

        return $code;
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $user->canAccessAllClusters()
            ? $query
            : $query->where('cluster_id', $user->cluster_id ?? 0);
    }

    public function isAccessibleBy(User $user): bool
    {
        return $user->canAccessAllClusters()
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
            'material_budget' => 'float',
            'physical_progress' => 'float',
            'progress_stages' => 'array',
            'start_date' => 'date',
            'target_end_date' => 'date',
            'completed_at' => 'datetime',
            'warranty_expires_at' => 'datetime',
        ];
    }

    public static function defaultProgressStages(?string $category = self::CATEGORY_HOUSE): array
    {
        if ($category === self::CATEGORY_PUBLIC_WORK) {
            return [];
        }

        return [
            ['name' => 'Persiapan', 'weight' => 5, 'completed' => false],
            ['name' => 'Pondasi', 'weight' => 15, 'completed' => false],
            ['name' => 'Struktur (sloof, kolom, ring balok)', 'weight' => 20, 'completed' => false],
            ['name' => 'Dinding & plester', 'weight' => 20, 'completed' => false],
            ['name' => 'Atap', 'weight' => 15, 'completed' => false],
            ['name' => 'Kusen, pintu, jendela', 'weight' => 7, 'completed' => false],
            ['name' => 'Lantai & keramik', 'weight' => 8, 'completed' => false],
            ['name' => 'Instalasi listrik & air', 'weight' => 5, 'completed' => false],
            ['name' => 'Finishing & cat', 'weight' => 5, 'completed' => false],
        ];
    }

    public function resolvedProgressStages(): array
    {
        $stages = $this->progress_stages;
        if (! is_array($stages) || empty($stages)) {
            $stages = self::defaultProgressStages($this->category);
        }

        if ($this->status === 'selesai') {
            return array_map(function ($stage) {
                $stage['completed'] = true;
                return $stage;
            }, $stages);
        }

        return $stages;
    }

    public static function calculatePhysicalProgress(array $stages): float
    {
        $total = 0.0;
        foreach ($stages as $stage) {
            if (! empty($stage['completed'])) {
                $total += (float) ($stage['weight'] ?? 0);
            }
        }

        return round(min(100.0, max(0.0, $total)), 2);
    }

    public function getBobotRumahAttribute(): float
    {
        if ($this->status === 'selesai') {
            return 100.0;
        }

        return (float) ($this->physical_progress ?? 0);
    }

    public function getBobotBahanAttribute(): ?float
    {
        $budget = (float) ($this->material_budget ?? 0);
        if ($budget <= 0) {
            return null;
        }

        $used = (float) $this->total_material_cost;

        return round(($used / $budget) * 100, 1);
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
        return (float) ($this->material_usages_sum_total_cost ?? $this->materialUsages()->whereNull('voided_at')->sum('total_cost'));
    }

    /**
     * Use house_code as the route key name instead of the numeric ID.
     */
    public function getRouteKeyName(): string
    {
        return 'house_code';
    }
}

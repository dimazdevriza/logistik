<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class House extends Model
{
    use HasFactory;

    protected $fillable = [
        'house_code',
        'name',
        'type',
        'status',
        'start_date',
        'target_end_date',
    ];

    /**
     * Generate a unique house code: [ClusterInitials]-[Year]-[BlokStripped]
     * e.g. "Cluster Mutiara" + "Blok A-01" → "CM-2026-A01"
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
        ];
    }

    public function materialUsages(): HasMany
    {
        return $this->hasMany(MaterialUsage::class);
    }



    public function toolUsages(): HasMany
    {
        return $this->hasMany(ToolUsage::class);
    }

    /**
     * Get total material cost for this house.
     */
    public function getTotalMaterialCostAttribute(): float
    {
        return (float) $this->materialUsages()->sum('total_cost');
    }

    /**
     * Use house_code as the route key name instead of the numeric ID.
     */
    public function getRouteKeyName(): string
    {
        return 'house_code';
    }

}

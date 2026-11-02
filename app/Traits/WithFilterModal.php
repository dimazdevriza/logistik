<?php

namespace App\Traits;

/**
 * WithFilterModal Trait
 *
 * Provides reusable filter modal functionality for Livewire components.
 * Automatically manages modal state and tracks active filters.
 */
trait WithFilterModal
{
    public bool $showFilterModal = false;

    public function openFilterModal(): void
    {
        $this->showFilterModal = true;
    }

    public function applyFilters(): void
    {
        $this->showFilterModal = false;
    }

    public function getActiveFiltersCount(): int
    {
        $count = 0;

        foreach (get_object_vars($this) as $property => $value) {
            if ($property === 'filterWarehouse' && auth()->user()?->assignedWarehouseId()) {
                continue;
            }
            if ($property === 'filterCluster' && ! (auth()->user()?->canAccessAllClusters() ?? true)) {
                continue;
            }
            if (str_starts_with($property, 'filter') && ! empty($value)) {
                $count++;
            }
        }

        return $count;
    }
}

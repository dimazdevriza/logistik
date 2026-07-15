<?php

namespace App\Traits;

/**
 * WithFilterModal Trait
 *
 * Provides reusable filter modal functionality for Livewire components.
 * Automatically manages modal state and tracks active filters.
 *
 * Usage in Livewire component:
 * - Add `use WithFilterModal;` in your component
 * - Implement `resetFilters()` method in your component to reset your specific filters
 * - Call `openFilterModal()` to open the modal
 * - Call `applyFilters()` to apply changes
 * - Use `getActiveFiltersCount()` to display badge
 */
trait WithFilterModal
{
    /**
     * Whether the filter modal is visible
     */
    public bool $showFilterModal = false;

    /**
     * Open the filter modal
     */
    public function openFilterModal(): void
    {
        $this->showFilterModal = true;
    }

    /**
     * Apply filters and close the modal
     */
    public function applyFilters(): void
    {
        $this->showFilterModal = false;
        // Filter logic is handled by Livewire's live model binding
        // on the individual filter properties
    }

    /**
     * Get count of active filters for badge display
     * 
     * Counts non-empty filter properties excluding sort (which is a default value)
     */
    public function getActiveFiltersCount(): int
    {
        $count = 0;

        // Dynamically count all properties of this component starting with 'filter' that are not empty
        foreach (get_object_vars($this) as $property => $value) {
            if (str_starts_with($property, 'filter') && !empty($value)) {
                $count++;
            }
        }

        return $count;
    }
}


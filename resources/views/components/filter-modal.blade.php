@props(['activeFiltersCount' => 0, 'showBadge' => true])

<div class="d-inline-flex align-items-center gap-2">
    <button 
        type="button"
        wire:click="openFilterModal" 
        class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1 font-semibold"
    >
        <span>Filter</span>
        @if($showBadge && $activeFiltersCount > 0)
            <span class="badge bg-primary rounded-pill ms-1">
                {{ $activeFiltersCount }}
            </span>
        @endif
    </button>

    @if($activeFiltersCount > 0)
        <button 
            type="button"
            wire:click="resetFilters" 
            class="btn btn-link text-danger btn-sm p-0 text-decoration-none"
            title="Reset Filter"
        >
            ✕ Reset
        </button>
    @endif
</div>

{{-- Filter Modal using Livewire component state --}}
@if($this->showFilterModal)
<div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-bottom">
                <h5 class="modal-title font-outfit fw-bold">Filter Pencarian</h5>
                <button type="button" class="btn-close" wire:click="$set('showFilterModal', false)" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <div class="vstack gap-3">
                    {{ $slot }}
                </div>
            </div>
            <div class="modal-footer border-top bg-light">
                <button type="button" class="btn btn-secondary font-semibold" wire:click="$set('showFilterModal', false)">Batal</button>
                <button type="button" class="btn btn-success font-semibold" wire:click="applyFilters">Terapkan Filter</button>
            </div>
        </div>
    </div>
</div>
@endif

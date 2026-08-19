@props(['activeFiltersCount' => 0, 'showBadge' => true])

<div class="d-inline-flex align-items-center gap-2">
    <button 
        type="button"
        wire:click="openFilterModal" 
        class="btn {{ $activeFiltersCount > 0 ? 'btn-success' : 'btn-outline-secondary' }} px-3 d-inline-flex align-items-center gap-2 font-semibold shadow-xs"
        style="height: 38px;"
    >
        <svg width="15" height="15" fill="currentColor" viewBox="0 0 16 16">
            <path d="M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.128.334L10 8.692V13.5a.5.5 0 0 1-.342.474l-3 1A.5.5 0 0 1 6 14.5V8.692L1.628 3.834A.5.5 0 0 1 1.5 3.5zm1 .5v1.308l4.372 4.858A.5.5 0 0 1 7 8.5v5.306l2-.666V8.5a.5.5 0 0 1 .128-.334L13.5 3.308V2z"/>
        </svg>
        <span>Filter</span>
        @if($showBadge && $activeFiltersCount > 0)
            <span class="badge bg-white text-success rounded-pill ms-1 px-2 py-0.5 extra-small fw-bold">
                {{ $activeFiltersCount }}
            </span>
        @endif
    </button>

    @if($activeFiltersCount > 0)
        <button 
            type="button"
            wire:click="resetFilters" 
            class="btn btn-outline-danger btn-sm p-0 d-inline-flex align-items-center justify-content-center rounded-2 shadow-xs"
            style="width: 38px; height: 38px;"
            title="Reset Filter"
            aria-label="Reset Filter"
        >
            ✕
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

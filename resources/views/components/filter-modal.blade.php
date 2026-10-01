@props(['activeFiltersCount' => 0, 'showBadge' => true])

<div class="d-inline-flex align-items-center gap-2">
    <button 
        type="button"
        wire:click="openFilterModal" 
        class="btn {{ $activeFiltersCount > 0 ? 'btn-success' : 'btn-outline-secondary' }} px-3 d-inline-flex align-items-center gap-2 font-semibold shadow-xs"
        style="min-height: 44px;"
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
            style="width: 44px; height: 44px;"
            title="Reset Filter"
            aria-label="Reset Filter"
        >
            <svg width="15" height="15" fill="currentColor" aria-hidden="true"><use href="#i-x"/></svg>
        </button>
    @endif
</div>

{{-- Filter Modal using Livewire component state --}}
@if($this->showFilterModal)
@teleport('body')
<div
    class="modal fade show d-block"
    tabindex="-1"
    style="background-color: rgba(0,0,0,0.5);"
    aria-modal="true"
    role="dialog"
    aria-labelledby="filter-modal-title"
    x-data="{
        opener: document.activeElement,
        init() { this.$nextTick(() => this.focusFirst()); },
        focusables() { return [...this.$refs.dialog.querySelectorAll('button, input, select, textarea, a[href]')].filter(element => !element.disabled); },
        focusFirst() { this.focusables()[0]?.focus(); },
        close() {
            const opener = this.opener;
            this.$wire.set('showFilterModal', false);
            window.setTimeout(() => opener?.focus(), 80);
        },
        trap(event) {
            const items = this.focusables();
            if (!items.length) return;
            const first = items[0];
            const last = items[items.length - 1];
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
            if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
        }
    }"
    x-ref="dialog"
    @keydown.escape.stop="close()"
    @keydown.tab="trap($event)"
>
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom">
                <h5 id="filter-modal-title" class="modal-title font-outfit fw-bold">Filter Pencarian</h5>
                <button type="button" class="btn-close" x-on:click="close()" aria-label="Tutup dialog filter"></button>
            </div>
            <div class="modal-body py-4">
                <div class="vstack gap-3">
                    {{ $slot }}
                </div>
            </div>
            <div class="modal-footer border-top bg-body-tertiary rounded-bottom-4">
                <button type="button" class="btn btn-secondary fw-semibold" x-on:click="close()">Batal</button>
                <button type="button" class="btn btn-success fw-semibold" wire:click="applyFilters">Terapkan Filter</button>
            </div>
        </div>
    </div>
</div>
@endteleport
@endif

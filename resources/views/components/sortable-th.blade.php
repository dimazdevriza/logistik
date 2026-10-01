@props([
    'field',
    'sort',
    'href' => null,
])

@php
    $ascending = $sort === $field . '_asc';
    $descending = $sort === $field . '_desc';
    $active = $ascending || $descending;
    $label = trim(strip_tags((string) $slot));
@endphp

<th
    {{ $attributes->class(['table-sortable', 'is-active' => $active]) }}
    @if ($active) aria-sort="{{ $ascending ? 'ascending' : 'descending' }}" @endif
>
    @if ($href)
        <a
            href="{{ $href }}"
            class="table-sort-button"
            aria-label="Urutkan kolom {{ $label }}{{ $active ? ($ascending ? ', saat ini naik' : ', saat ini turun') : '' }}"
        >
            <span class="table-sort-label">{{ $slot }}</span>
            @if ($active)
                <svg class="table-sort-arrow" width="12" height="12" fill="currentColor" aria-hidden="true"><use href="{{ $ascending ? '#i-chevron-up' : '#i-chevron-down' }}"/></svg>
            @endif
        </a>
    @else
        <button
            type="button"
            class="table-sort-button"
            wire:click="sortBy('{{ $field }}')"
            aria-label="Urutkan kolom {{ $label }}{{ $active ? ($ascending ? ', saat ini naik' : ', saat ini turun') : '' }}"
        >
            <span class="table-sort-label">{{ $slot }}</span>
            @if ($active)
                <svg class="table-sort-arrow" width="12" height="12" fill="currentColor" aria-hidden="true"><use href="{{ $ascending ? '#i-chevron-up' : '#i-chevron-down' }}"/></svg>
            @endif
        </button>
    @endif
</th>

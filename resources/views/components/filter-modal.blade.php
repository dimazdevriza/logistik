@props(['activeFiltersCount' => 0, 'showBadge' => true])

<div class="flex items-center gap-2">
    <flux:button 
        wire:click="openFilterModal" 
        variant="ghost" 
        icon="funnel"
        class="relative"
        :class="['text-zinc-700 dark:text-zinc-300', $activeFiltersCount > 0 ? 'font-semibold' : '']"
    >
        <span>Filter</span>
        @if($showBadge && $activeFiltersCount > 0)
            <span class="ml-2 inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-blue-600 dark:bg-blue-500 rounded-full">
                {{ $activeFiltersCount }}
            </span>
        @endif
    </flux:button>

    @if($activeFiltersCount > 0)
        <flux:button 
            wire:click="resetFilters" 
            variant="ghost" 
            size="sm" 
            icon="x-mark" 
            class="text-zinc-700 dark:text-zinc-300 shrink-0" 
            title="Reset Filter"
        />
    @endif
</div>

{{-- Filter Modal --}}
<flux:modal wire:model="showFilterModal" class="max-w-2xl">
    <div class="space-y-6">
        <flux:heading size="lg">
            Aplikasikan Filter
        </flux:heading>

        <div class="space-y-4">
            {{ $slot }}
        </div>

        <div class="flex justify-end gap-3 pt-4 border-t border-neutral-200 dark:border-neutral-700">
            <flux:button 
                wire:click="$set('showFilterModal', false)" 
                variant="ghost"
            >
                Batal
            </flux:button>
            <flux:button 
                wire:click="applyFilters" 
                variant="primary"
            >
                Terapkan Filter
            </flux:button>
        </div>
    </div>
</flux:modal>


<div wire:key="{{ $purposeId }}-{{ md5(json_encode([$purposeValue, $noteOptions])) }}" class="spreadsheet-purpose-picker" x-data="{
    open: false,
    query: @js((string) ($purposeValue ?? '')),
    active: 0,
    menuStyle: '',
    options: @js($noteOptions ?? []),
    get filtered() { return this.options.filter(option => option.toLocaleLowerCase('id').includes(this.query.trim().toLocaleLowerCase('id'))); },
    sizeMenu() {
        const menu = this.$refs.options;
        const options = [...menu.querySelectorAll('[role=option]')].slice(0, 5);
        if (!options.length) {
            this.menuStyle = '';
            return;
        }
        const style = getComputedStyle(menu);
        const boxExtras = [style.paddingTop, style.paddingBottom, style.borderTopWidth, style.borderBottomWidth]
            .reduce((total, value) => total + (Number.parseFloat(value) || 0), 0);
        const rowsHeight = options.reduce((total, option) => total + option.getBoundingClientRect().height, 0);
        this.menuStyle = `max-height: ${rowsHeight + boxExtras}px;`;
    },
    showMenu() { this.open = true; this.active = 0; this.$nextTick(() => this.sizeMenu()); },
    choose(option) {
        this.query = option;
        this.$refs.input.value = option;
        this.open = false;
        $wire.set(@js($purposeModel), option);
    }
}" x-effect="$dispatch('spreadsheet-picker-open', open)" @click.outside="open = false">
    <label class="visually-hidden" for="{{ $purposeId }}">{{ $purposeLabel }}</label>
    <input id="{{ $purposeId }}" x-ref="input" type="text" maxlength="{{ $purposeMaxlength ?? 500 }}" wire:model="{{ $purposeModel }}" class="form-control form-control-sm" placeholder="{{ $purposePlaceholder ?? 'Pilih atau ketik peruntukan' }}" role="combobox" aria-autocomplete="list" aria-controls="{{ $purposeId }}-options" :aria-expanded="open.toString()" @focus="showMenu()" @input="query = $el.value; showMenu()" @keydown.arrow-down.prevent="active = Math.min(active + 1, filtered.length - 1)" @keydown.arrow-up.prevent="active = Math.max(active - 1, 0)" @keydown.enter.prevent="if (open && filtered.length) choose(filtered[active])" @keydown.escape="open = false">
    <button type="button" class="spreadsheet-purpose-toggle" aria-label="{{ $purposeToggleLabel ?? 'Tampilkan pilihan peruntukan' }}" @mousedown.prevent @click="open ? open = false : showMenu()">
        <svg viewBox="0 0 16 16" aria-hidden="true"><path d="m3 6 5 5 5-5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </button>
    <div id="{{ $purposeId }}-options" x-ref="options" x-show="open" x-cloak :style="menuStyle" class="spreadsheet-item-picker-menu spreadsheet-purpose-menu" @click.stop>
        <div role="listbox" aria-label="{{ $purposeOptionsLabel ?? 'Pilihan peruntukan' }}" class="d-grid">
            <template x-for="option in filtered" :key="option">
                <button type="button" role="option" class="spreadsheet-purpose-option btn d-block w-100 text-start py-2 px-3" :aria-selected="query === option" x-text="option" @mousedown.prevent @click="choose(option)"></button>
            </template>
            <div x-show="query.trim() && filtered.length === 0" class="spreadsheet-item-picker-empty">{{ $purposeNewOptionMessage ?? 'Gunakan sebagai peruntukan baru.' }}</div>
        </div>
        <div x-show="!query.trim() && options.length === 0" class="spreadsheet-item-picker-empty" role="status" aria-live="polite">{{ $purposeEmptyMessage ?? 'Belum ada pilihan tersimpan. Ketik nilai baru.' }}</div>
    </div>
</div>

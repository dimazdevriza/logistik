@php
    $pickerHasError = ! empty($pickerError ?? null) && $errors->has($pickerError);
@endphp
<div wire:key="{{ $pickerId }}-{{ md5(json_encode([$pickerValue, $pickerItems])) }}" class="spreadsheet-item-picker" x-data="{
    open: false,
    query: '',
    active: 0,
    menuStyle: '',
    selectedId: @js((string) $pickerValue),
    items: @js($pickerItems),
    get selected() { return this.items.find(item => item.id === this.selectedId) ?? null; },
    get selectedLabel() {
        if (!this.selected) return '';
        return @js(! empty($pickerTwoLine ?? false)) && this.selected.meta
            ? `${this.selected.label} · ${this.selected.meta}`
            : this.selected.label;
    },
    get filtered() {
        const term = this.query.trim().toLocaleLowerCase('id');
        return term ? this.items.filter(item => `${item.label} ${item.meta || ''}`.toLocaleLowerCase('id').includes(term)) : this.items;
    },
    showMenu() {
        this.open = true;
        this.query = '';
        this.active = 0;
        this.$nextTick(() => {
            if (this.$refs.options) this.$refs.options.scrollTop = 0;
            this.sizeMenu();
        });
    },
    toggle() {
        if (this.open) {
            this.open = false;
            this.query = '';
            return;
        }
        this.showMenu();
        this.$refs.input.focus();
    },
    sizeMenu() {
        const menu = this.$refs.options;
        const options = [...menu.querySelectorAll('[role=option]')].slice(0, 5);
        if (!options.length) {
            this.menuStyle = '';
            return;
        }
        const menuStyle = getComputedStyle(menu);
        const boxExtras = [menuStyle.paddingTop, menuStyle.paddingBottom, menuStyle.borderTopWidth, menuStyle.borderBottomWidth]
            .reduce((total, value) => total + (Number.parseFloat(value) || 0), 0);
        const rowsHeight = options.reduce((total, option) => {
            const optionStyle = getComputedStyle(option);
            return total + option.getBoundingClientRect().height + (Number.parseFloat(optionStyle.marginBottom) || 0);
        }, 0);
        this.menuStyle = `max-height: ${rowsHeight + boxExtras}px;`;
    },
    choose(item) {
        this.selectedId = item.id;
        this.open = false;
        this.query = '';
        @if ($pickerTarget)
            $wire.selectSpreadsheetItem({{ $pickerTarget['row'] }}, @js($pickerTarget['type']), {{ $pickerTarget['line'] }}, Number(item.id));
        @else
            $wire.set(@js($pickerModel), item.id);
        @endif
    },
    clear() {
        this.selectedId = '';
        this.open = false;
        this.query = '';
        @if ($pickerTarget)
            $wire.removeSpreadsheetLine({{ $pickerTarget['row'] }}, @js($pickerTarget['type']), {{ $pickerTarget['line'] }});
        @else
            $wire.set(@js($pickerModel), '');
        @endif
    },
    move(step) {
        if (!this.filtered.length) return;
        this.active = Math.max(0, Math.min(this.filtered.length - 1, this.active + step));
        this.$nextTick(() => this.$refs.options?.querySelectorAll('[role=option]')[this.active]?.scrollIntoView({ block: 'nearest' }));
    }
}" x-effect="$dispatch('spreadsheet-picker-open', open)" @click.outside="open = false; query = ''" @keydown.escape.stop="open = false; query = ''">
    <label class="visually-hidden" for="{{ $pickerId }}-input">{{ $pickerPlaceholder }}</label>
    <div class="spreadsheet-house-picker-control spreadsheet-item-picker-control">
        <input id="{{ $pickerId }}-input" x-ref="input" type="text" class="form-control" placeholder="{{ $pickerPlaceholder }}" role="combobox" aria-autocomplete="list" aria-controls="{{ $pickerId }}-options" :aria-expanded="open.toString()" :aria-activedescendant="open && filtered[active] ? `{{ $pickerId }}-option-${active}` : null" :value="open ? query : selectedLabel" :title="open ? '' : selectedLabel" autocomplete="off"
            @if ($pickerHasError) aria-invalid="true" aria-describedby="{{ $pickerId }}-error" @endif
            @focus="if (!open) showMenu()"
            @input="open = true; query = $el.value; active = 0; $nextTick(() => { if ($refs.options) $refs.options.scrollTop = 0; sizeMenu(); })"
            @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)"
            @keydown.enter.prevent="if (open && filtered.length) choose(filtered[active])"
            @keydown.tab="open = false; query = ''">
        <div class="spreadsheet-house-picker-actions">
            <button x-show="selected" type="button" class="spreadsheet-house-picker-action" aria-label="Kosongkan pilihan" @mousedown.prevent @click="clear()">
                <svg viewBox="0 0 16 16" aria-hidden="true"><path d="m4 4 8 8m0-8-8 8" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
            </button>
            <button type="button" class="spreadsheet-house-picker-action" :aria-label="open ? 'Tutup pilihan' : 'Tampilkan pilihan'" @mousedown.prevent @click="toggle()">
                <svg viewBox="0 0 16 16" aria-hidden="true" :class="open ? 'is-open' : ''"><path d="m3 6 5 5 5-5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
        </div>
    </div>
    @if (! empty($pickerError))
        @error($pickerError)
            <span id="{{ $pickerId }}-error" class="text-danger small" role="alert">{{ $message }}</span>
        @enderror
    @endif
    <div id="{{ $pickerId }}-options" x-ref="options" x-show="open" x-cloak x-bind:style="menuStyle" @click.stop role="listbox" aria-label="{{ $pickerPlaceholder }}" class="spreadsheet-item-picker-menu">
        @if ($pickerItems)
            <template x-for="(item, index) in filtered" :key="item.id">
                <button type="button" role="option" :id="`{{ $pickerId }}-option-${index}`" :aria-selected="selectedId === item.id" :class="[index === active ? 'is-active' : '', item.meta ? '' : 'has-no-meta']" class="spreadsheet-house-picker-option spreadsheet-item-picker-option" @mouseenter="active = index" @mousedown.prevent @click="choose(item)">
                    <span class="spreadsheet-item-picker-option-label" x-text="item.label"></span>
                    <span class="spreadsheet-item-picker-option-meta" x-text="item.meta || ''"></span>
                </button>
            </template>
            <div x-show="filtered.length === 0" class="spreadsheet-item-picker-empty">Tidak ada pilihan yang cocok.</div>
        @else
            <div class="spreadsheet-item-picker-empty">{{ $pickerEmptyMessage }}</div>
        @endif
    </div>
</div>

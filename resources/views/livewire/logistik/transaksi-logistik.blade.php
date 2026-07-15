<div>
    <div class="flex h-full w-full flex-1 flex-col gap-6 p-4">
        {{-- Page Header --}}
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <flux:heading size="xl" class="font-bold">Transaksi Logistik</flux:heading>
                <flux:text class="mt-1 text-zinc-700 dark:text-zinc-300">Catat penggunaan material dan peminjaman alat untuk proyek konstruksi.</flux:text>
            </div>
        </div>

        {{-- Success Flash --}}
        @if (session('success'))
            <flux:callout variant="success" icon="check-circle" dismissible>{{ session('success') }}</flux:callout>
        @endif

        {{-- Shared Multi-House Picker --}}
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-zinc-800 shadow-sm p-6">
            <div class="mb-4">
                <flux:heading size="lg">Pilih Rumah Tujuan</flux:heading>
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">(Bisa lebih dari 1 — berlaku untuk material &amp; alat)</flux:text>
            </div>

            <div
                class="flex flex-col gap-2 min-w-0"
                x-data="{ 
                    open: @entangle('housePickerOpen').live, 
                    search: '',
                    selectAll: false,
                    toggleSelectAll() {
                        if (!this.selectAll) {
                            $wire.house_ids = @js($houses->pluck('id')->toArray());
                        } else {
                            $wire.house_ids = [];
                        }
                        this.selectAll = !this.selectAll;
                    },
                    getSelectedCount() {
                        return $wire.house_ids.length;
                    }
                }"
            >
                <div class="flex items-center justify-between">
                    <flux:text class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Pilih Rumah</flux:text>
                    <flux:text class="text-xs text-zinc-500" x-show="getSelectedCount() > 0">
                        <span x-text="getSelectedCount() + ' dipilih'"></span>
                    </flux:text>
                </div>
                <button
                    type="button"
                    @click="open = !open"
                    class="w-full flex items-center justify-between px-3 py-2 border border-zinc-200 dark:border-white/10 rounded-lg bg-white dark:bg-zinc-800 text-sm text-left shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 hover:bg-zinc-50 dark:hover:bg-white/5 transition-colors"
                >
                    <span class="text-zinc-700 dark:text-zinc-300">
                        <span x-show="$wire.house_ids.length === 0">-- Pilih Rumah --</span>
                        <span x-show="$wire.house_ids.length > 0" x-text="$wire.house_ids.length + ' rumah dipilih'" style="display: none;"></span>
                    </span>
                    <svg
                        class="size-4 text-zinc-400 shrink-0 transition-transform duration-150"
                        :class="open ? 'rotate-180' : ''"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25 12 15.75 4.5 8.25" />
                    </svg>
                </button>

                @if (count($house_ids) > 0)
                    <div class="flex flex-wrap gap-1.5" x-show="!open">
                        @foreach ($houses->whereIn('id', $house_ids) as $selected)
                            <flux:badge variant="pill" size="sm" class="dark:bg-zinc-700 dark:text-zinc-200">
                                {{ $selected->name }}
                            </flux:badge>
                        @endforeach
                    </div>
                @endif

                <div
                    x-show="open"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    @keydown.escape.window="open = false"
                    class="border border-zinc-200 dark:border-white/10 rounded-xl bg-white dark:bg-zinc-800 shadow-sm flex flex-col overflow-hidden"
                    style="display: none;"
                >
                    <div class="p-2 border-b border-zinc-100 dark:border-white/10 shrink-0">
                        <div class="flex gap-2 items-center">
                            <flux:input x-model="search" placeholder="Cari rumah..." icon="magnifying-glass" size="sm" class="flex-1 min-w-0" />
                            <div class="shrink-0">
                                <flux:button 
                                    type="button" 
                                    size="sm" 
                                    :variant="$houses->count() === count($house_ids) ? 'primary' : 'ghost'"
                                    @click="toggleSelectAll()"
                                >
                                    <span x-text="selectAll ? 'Batal Pilih Semua' : 'Pilih Semua'">Pilih Semua</span>
                                </flux:button>
                            </div>
                        </div>
                    </div>
                    <div class="overflow-y-auto max-h-[240px] p-1.5 space-y-0.5 overscroll-contain">
                        @foreach ($houses as $h)
                            <label
                                class="flex items-center gap-3 px-2 py-2 hover:bg-zinc-100 dark:hover:bg-white/5 rounded-md cursor-pointer transition-colors"
                                x-show="search === '' || @js(strtolower($h->name . ' ' . $h->type)).includes(search.toLowerCase())"
                            >
                                <input
                                    type="checkbox"
                                    wire:model.live="house_ids"
                                    value="{{ $h->id }}"
                                    class="rounded border-zinc-300 dark:border-white/10 dark:bg-white/5 dark:checked:bg-primary-500 text-primary-600 shadow-sm focus:ring-primary-500 w-4 h-4"
                                />
                                <span class="text-sm text-zinc-800 dark:text-zinc-200">
                                    {{ $h->name }}
                                    <span class="text-zinc-500 dark:text-zinc-400">({{ $h->type }})</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <div class="flex justify-end gap-2 p-2 border-t border-zinc-100 dark:border-white/10 shrink-0">
                        <flux:button type="button" size="sm" variant="ghost" @click="open = false">Tutup</flux:button>
                    </div>
                </div>

                @if ($errors->has('house_ids'))
                    <flux:text class="text-sm text-red-500">{{ $errors->first('house_ids') }}</flux:text>
                @endif
            </div>
        </div>

        {{-- Tabbed Forms Card --}}
        <div
            class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-zinc-800 shadow-sm p-6"
            x-data="{ activeTab: @entangle('activeTab').live }"
        >
            {{-- Tabs --}}
            <div class="flex border-b border-zinc-200 dark:border-white/10 mb-6">
                <button
                    type="button"
                    @click="activeTab = 'material'"
                    class="px-4 py-3 text-sm font-medium flex items-center gap-2 transition-colors border-b-2"
                    :class="activeTab === 'material' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300'"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                    </svg>
                    Penggunaan Material
                </button>
                <button
                    type="button"
                    @click="activeTab = 'tool'"
                    class="px-4 py-3 text-sm font-medium flex items-center gap-2 transition-colors border-b-2"
                    :class="activeTab === 'tool' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300'"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z" />
                    </svg>
                    Peminjaman Alat
                </button>
                <button
                    type="button"
                    @click="activeTab = 'return'"
                    class="px-4 py-3 text-sm font-medium flex items-center gap-2 transition-colors border-b-2"
                    :class="activeTab === 'return' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300'"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
                    </svg>
                    Pengembalian Alat
                </button>
            </div>

            {{-- Material Tab --}}
            <div x-show="activeTab === 'material'" style="display: none;" class="space-y-6">
                {{-- Material Picker --}}
                <div
                    class="flex flex-col gap-2 min-w-0"
                    x-data="{ 
                        open: @entangle('materialPickerOpen').live, 
                        search: '' 
                    }"
                >
                    <flux:text class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Material</flux:text>
                    <button
                        type="button"
                        @click="open = !open"
                        class="w-full flex items-center justify-between px-3 py-2 border border-zinc-200 dark:border-white/10 rounded-lg bg-white dark:bg-zinc-800 text-sm text-left shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 hover:bg-zinc-50 dark:hover:bg-white/5 transition-colors"
                    >
                        <span class="text-zinc-700 dark:text-zinc-300 truncate pr-2">
                            @if ($material_id && ($selectedMaterial = $materials->firstWhere('id', $material_id)))
                                {{ $selectedMaterial->name }}
                            @else
                                <span>-- Pilih Material --</span>
                            @endif
                        </span>
                        <svg
                            class="size-4 text-zinc-400 shrink-0 transition-transform duration-150"
                            :class="open ? 'rotate-180' : ''"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25 12 15.75 4.5 8.25" />
                        </svg>
                    </button>

                    @if ($material_id && ($selectedMaterial = $materials->firstWhere('id', $material_id)))
                        <p class="text-xs text-zinc-500 dark:text-zinc-400" x-show="!open">
                            Rp {{ number_format($selectedMaterial->unit_price, 0, ',', '.') }}
                            &middot; stok: {{ $selectedMaterial->stock }} {{ $selectedMaterial->unit }}
                        </p>
                    @endif

                    <div
                        x-show="open"
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        @keydown.escape.window="open = false"
                        class="border border-zinc-200 dark:border-white/10 rounded-xl bg-white dark:bg-zinc-800 shadow-sm flex flex-col overflow-hidden"
                        style="display: none;"
                    >
                        <div class="p-2 border-b border-zinc-100 dark:border-white/10 shrink-0">
                            <flux:input x-model="search" placeholder="Cari material..." icon="magnifying-glass" size="sm" />
                        </div>
                        <div class="overflow-y-auto max-h-[240px] p-1.5 space-y-0.5 overscroll-contain">
                            @foreach ($materials as $m)
                                <div
                                    class="flex items-start gap-3 px-2 py-2 hover:bg-zinc-100 dark:hover:bg-white/5 rounded-md cursor-pointer transition-colors"
                                    x-show="search === '' || @js(strtolower($m->name . ' ' . $m->unit)).includes(search.toLowerCase())"
                                    @click="$wire.material_id = '{{ $m->id }}'; open = false"
                                >
                                    <input
                                        type="radio"
                                        wire:model.live="material_id"
                                        value="{{ $m->id }}"
                                        class="mt-0.5 border-zinc-300 dark:border-white/10 dark:bg-white/5 text-primary-600 shadow-sm focus:ring-primary-500"
                                        @click.stop
                                    />
                                    <span class="text-sm text-zinc-800 dark:text-zinc-200 min-w-0">
                                        <span class="block font-medium">{{ $m->name }}</span>
                                        <span class="block text-xs text-zinc-500 dark:text-zinc-400">
                                            Rp {{ number_format($m->unit_price, 0, ',', '.') }}
                                            &middot; stok: {{ $m->stock }} {{ $m->unit }}
                                        </span>
                                    </span>
                                </div>
                            @endforeach
                        </div>
                        <div class="flex justify-end gap-2 p-2 border-t border-zinc-100 dark:border-white/10 shrink-0">
                            <flux:button type="button" size="sm" variant="ghost" @click="open = false">Tutup</flux:button>
                        </div>
                    </div>

                    @if ($errors->has('material_id'))
                        <flux:text class="text-sm text-red-500">{{ $errors->first('material_id') }}</flux:text>
                    @endif
                </div>

                {{-- Quantity + Usage Date --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:input wire:model.live="material_quantity" label="Jumlah" type="number" step="0.01" :error="$errors->first('material_quantity')" />
                    <flux:input wire:model.live="usage_date" label="Tanggal Penggunaan" type="date" :error="$errors->first('usage_date')" />
                </div>

                {{-- Notes --}}
                <flux:textarea wire:model.live="material_notes" label="Catatan" placeholder="Catatan tambahan (opsional)" rows="2" />

                {{-- Cost Preview --}}
                <div 
                    class="rounded-xl bg-zinc-900 dark:bg-zinc-950 p-5 space-y-4"
                    x-data="{
                        materials: @js($materials->keyBy('id')->map(fn($m) => ['unit_price' => $m->unit_price, 'unit' => $m->unit, 'name' => $m->name])),
                        get houseCount() { return $wire.house_ids.length },
                        get materialId() { return $wire.material_id },
                        get quantity() { return parseFloat($wire.material_quantity) || 0 },
                        get isValid() { return this.houseCount > 0 && this.materialId && this.quantity > 0 && this.materials[this.materialId] },
                        get mat() { return this.isValid ? this.materials[this.materialId] : null },
                        get totalQty() { return this.quantity * this.houseCount },
                        get totalCost() { return this.totalQty * parseFloat(this.mat.unit_price) },
                        get houseCountText() { return this.houseCount + ' unit' },
                        get materialNameText() { return '\uD83D\uDCE6 ' + this.mat.name },
                        get quantityText() { return this.quantity.toFixed(2) + ' ' + this.mat.unit },
                        get totalQtyText() { return this.totalQty.toFixed(2) + ' ' + this.mat.unit },
                        get unitPriceText() { return 'Rp ' + new Intl.NumberFormat('id-ID').format(this.mat.unit_price) + ' / ' + this.mat.unit },
                        get totalCostText() { return 'Rp ' + new Intl.NumberFormat('id-ID').format(this.totalCost) },
                    }"
                >
                    <h3 class="text-base font-bold text-white">Pratinjau Biaya</h3>
                    <div x-show="isValid" class="space-y-3" style="display: none;">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-zinc-400">Rumah Dipilih:</span>
                            <span class="font-medium text-white" x-text="houseCountText"></span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-zinc-400">Material:</span>
                            <span class="font-medium text-white" x-text="materialNameText"></span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-zinc-400">Kuantitas/Rumah:</span>
                            <span class="font-medium text-white" x-text="quantityText"></span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-zinc-400">Total Pesanan:</span>
                            <span class="font-medium text-white" x-text="totalQtyText"></span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-zinc-400">Harga Satuan:</span>
                            <span class="font-medium text-white" x-text="unitPriceText"></span>
                        </div>

                        {{-- Total Biaya --}}
                        <div class="pt-4 mt-2 border-t border-white/10 flex justify-between items-center">
                            <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Total Biaya</p>
                            <p class="text-2xl font-bold text-emerald-500" x-text="totalCostText"></p>
                        </div>

                    </div>

                    {{-- Actions --}}
                    <div class="pt-2 space-y-2">
                        <flux:button wire:click="showMaterialConfirmationModal" variant="primary" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold">Simpan</flux:button>
                        <button wire:click="resetMaterialForm" class="w-full text-center text-sm text-zinc-400 hover:text-white transition-colors py-1">Reset</button>
                    </div>
                </div>
            </div>

            {{-- Return Tab --}}
            <div x-show="activeTab === 'return'" style="display: none;" class="space-y-6">
                @if (empty($house_ids))
                    <div class="text-center py-12">
                        <svg class="mx-auto size-12 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
                        </svg>
                        <flux:heading size="md" class="mt-4 text-zinc-500 dark:text-zinc-400">Pilih rumah terlebih dahulu</flux:heading>
                        <flux:text class="text-sm text-zinc-400 dark:text-zinc-500 mt-1">Silakan pilih rumah di bagian atas untuk melihat alat yang sedang dipinjam.</flux:text>
                    </div>
                @elseif ($activeUsages->isEmpty())
                    <div class="text-center py-12">
                        <svg class="mx-auto size-12 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        <flux:heading size="md" class="mt-4 text-zinc-500 dark:text-zinc-400">Tidak ada alat yang sedang dipinjam</flux:heading>
                        <flux:text class="text-sm text-zinc-400 dark:text-zinc-500 mt-1">Semua alat untuk rumah yang dipilih sudah dikembalikan.</flux:text>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs uppercase text-zinc-500 dark:text-zinc-400 border-b border-zinc-200 dark:border-white/10">
                                <tr>
                                    <th class="px-3 py-3 w-10"
                                        x-data="{
                                            get allChecked() {
                                                let ids = @js($activeUsages->pluck('id')->toArray());
                                                return ids.length > 0 && ids.every(id => $wire.returnSelections[id]?.selected);
                                            }
                                        }"
                                    >
                                        <input type="checkbox" class="rounded border-zinc-300 dark:border-white/10 dark:bg-white/5 text-primary-600 shadow-sm focus:ring-primary-500 w-4 h-4"
                                            :checked="allChecked"
                                            @change="
                                                let checked = $el.checked;
                                                @foreach($activeUsages as $usage)
                                                $wire.returnSelections[{{ $usage->id }}].selected = checked;
                                                @endforeach
                                            "
                                        />
                                    </th>
                                    <th class="px-3 py-3">Rumah</th>
                                    <th class="px-3 py-3">Alat</th>
                                    <th class="px-3 py-3 text-center">Jumlah</th>
                                    <th class="px-3 py-3">Tgl Pinjam</th>
                                    <th class="px-3 py-3">Opsi Pengembalian</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-white/5">
                                @foreach ($activeUsages as $usage)
                                    <tr wire:key="return-usage-{{ $usage->id }}" class="hover:bg-zinc-50 dark:hover:bg-white/5">
                                        <td class="px-3 py-3">
                                            <input type="checkbox"
                                                wire:model.live="returnSelections.{{ $usage->id }}.selected"
                                                class="rounded border-zinc-300 dark:border-white/10 dark:bg-white/5 text-primary-600 shadow-sm focus:ring-primary-500 w-4 h-4"
                                            />
                                        </td>
                                        <td class="px-3 py-3 text-zinc-800 dark:text-zinc-200 font-medium">{{ $usage->house->name }}</td>
                                        <td class="px-3 py-3 text-zinc-700 dark:text-zinc-300">{{ $usage->tool->name }}</td>
                                        <td class="px-3 py-3 text-center text-zinc-700 dark:text-zinc-300">{{ $usage->quantity }}</td>
                                        <td class="px-3 py-3 text-zinc-500 dark:text-zinc-400">{{ $usage->checkout_date?->format('d M Y') ?? '-' }}</td>
                                        <td class="px-3 py-3">
                                            <div x-show="$wire.returnSelections[{{ $usage->id }}]?.selected" style="display: none;" class="space-y-2">
                                                <div class="flex flex-wrap items-end gap-3">
                                                    <div class="w-20">
                                                        <label class="block text-xs font-medium text-emerald-600 dark:text-emerald-400 mb-1">Baik</label>
                                                        <input type="number" min="0" max="{{ $usage->quantity }}"
                                                            wire:model.live="returnSelections.{{ $usage->id }}.qty_normal"
                                                            class="w-full rounded-md border-zinc-200 dark:border-white/10 dark:bg-white/5 text-sm px-2 py-1.5 text-zinc-800 dark:text-zinc-200 focus:ring-emerald-500 focus:border-emerald-500"
                                                        />
                                                    </div>
                                                    <div class="w-20">
                                                        <label class="block text-xs font-medium text-amber-600 dark:text-amber-400 mb-1">Rusak</label>
                                                        <input type="number" min="0" max="{{ $usage->quantity }}"
                                                            wire:model.live="returnSelections.{{ $usage->id }}.qty_broken"
                                                            class="w-full rounded-md border-zinc-200 dark:border-white/10 dark:bg-white/5 text-sm px-2 py-1.5 text-zinc-800 dark:text-zinc-200 focus:ring-amber-500 focus:border-amber-500"
                                                        />
                                                    </div>
                                                    <div class="w-20">
                                                        <label class="block text-xs font-medium text-red-600 dark:text-red-400 mb-1">Hilang</label>
                                                        <input type="number" min="0" max="{{ $usage->quantity }}"
                                                            wire:model.live="returnSelections.{{ $usage->id }}.qty_lost"
                                                            class="w-full rounded-md border-zinc-200 dark:border-white/10 dark:bg-white/5 text-sm px-2 py-1.5 text-zinc-800 dark:text-zinc-200 focus:ring-red-500 focus:border-red-500"
                                                        />
                                                    </div>
                                                </div>

                                                @php
                                                    $qn = '$wire.returnSelections[' . $usage->id . ']?.qty_normal';
                                                    $qb = '$wire.returnSelections[' . $usage->id . ']?.qty_broken';
                                                    $ql = '$wire.returnSelections[' . $usage->id . ']?.qty_lost';
                                                    $sumExpr = '(parseInt(' . $qn . ') || 0) + (parseInt(' . $qb . ') || 0) + (parseInt(' . $ql . ') || 0)';
                                                @endphp
                                                <p x-show="{{ $sumExpr }} > {{ $usage->quantity }}" class="text-xs text-red-500" style="display: none;">
                                                    Maksimum {{ $usage->quantity }} unit
                                                </p>

                                                <div x-show="(parseInt($wire.returnSelections[{{ $usage->id }}]?.qty_broken) || 0) > 0 || (parseInt($wire.returnSelections[{{ $usage->id }}]?.qty_lost) || 0) > 0" style="display: none;">
                                                    <flux:input wire:model.live="returnSelections.{{ $usage->id }}.notes" label="Catatan" placeholder="Catatan (opsional)" size="sm" />
                                                </div>
                                            </div>
                                            <span x-show="!$wire.returnSelections[{{ $usage->id }}]?.selected" class="text-xs text-zinc-400 dark:text-zinc-500">Centang untuk memilih</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($errors->has('returnSelections'))
                        <flux:text class="text-sm text-red-500">{{ $errors->first('returnSelections') }}</flux:text>
                    @endif

                    <div class="flex justify-end gap-3 pt-2">
                        <flux:button wire:click="resetReturnForm" variant="ghost">Reset</flux:button>
                        <flux:button wire:click="showReturnConfirmationModal" variant="primary">Simpan Pengembalian</flux:button>
                    </div>
                @endif
            </div>

            {{-- Tool Tab --}}
            <div x-show="activeTab === 'tool'" style="display: none;" class="space-y-6">
                {{-- Tool Picker --}}
                <div
                    class="flex flex-col gap-2 min-w-0"
                    x-data="{ 
                        open: @entangle('toolPickerOpen').live, 
                        search: '' 
                    }"
                >
                    <flux:text class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Alat</flux:text>
                    <button
                        type="button"
                        @click="open = !open"
                        class="w-full flex items-center justify-between px-3 py-2 border border-zinc-200 dark:border-white/10 rounded-lg bg-white dark:bg-zinc-800 text-sm text-left shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 hover:bg-zinc-50 dark:hover:bg-white/5 transition-colors"
                    >
                        <span class="text-zinc-700 dark:text-zinc-300 truncate pr-2">
                            @if ($tool_id && ($selectedTool = $tools->firstWhere('id', $tool_id)))
                                {{ $selectedTool->name }}
                            @else
                                <span>-- Pilih Alat --</span>
                            @endif
                        </span>
                        <svg
                            class="size-4 text-zinc-400 shrink-0 transition-transform duration-150"
                            :class="open ? 'rotate-180' : ''"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25 12 15.75 4.5 8.25" />
                        </svg>
                    </button>

                    @if ($tool_id && ($selectedTool = $tools->firstWhere('id', $tool_id)))
                        <p class="text-xs text-zinc-500 dark:text-zinc-400" x-show="!open">
                            {{ $selectedTool->code }}
                            &middot; sisa: {{ $selectedTool->available_qty }}
                        </p>
                    @endif

                    <div
                        x-show="open"
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        @keydown.escape.window="open = false"
                        class="border border-zinc-200 dark:border-white/10 rounded-xl bg-white dark:bg-zinc-800 shadow-sm flex flex-col overflow-hidden"
                        style="display: none;"
                    >
                        <div class="p-2 border-b border-zinc-100 dark:border-white/10 shrink-0">
                            <flux:input x-model="search" placeholder="Cari alat..." icon="magnifying-glass" size="sm" />
                        </div>
                        <div class="overflow-y-auto max-h-[240px] p-1.5 space-y-0.5 overscroll-contain">
                            @foreach ($tools as $t)
                                <div
                                    class="flex items-start gap-3 px-2 py-2 hover:bg-zinc-100 dark:hover:bg-white/5 rounded-md cursor-pointer transition-colors"
                                    x-show="search === '' || @js(strtolower($t->name . ' ' . $t->code)).includes(search.toLowerCase())"
                                    @click="$wire.tool_id = '{{ $t->id }}'; open = false"
                                >
                                    <input
                                        type="radio"
                                        wire:model.live="tool_id"
                                        value="{{ $t->id }}"
                                        class="mt-0.5 border-zinc-300 dark:border-white/10 dark:bg-white/5 text-primary-600 shadow-sm focus:ring-primary-500"
                                        @click.stop
                                    />
                                    <span class="text-sm text-zinc-800 dark:text-zinc-200 min-w-0">
                                        <span class="block font-medium">{{ $t->name }}</span>
                                        <span class="block text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ $t->code }}
                                            &middot; sisa: {{ $t->available_qty }}
                                        </span>
                                    </span>
                                </div>
                            @endforeach
                        </div>
                        <div class="flex justify-end gap-2 p-2 border-t border-zinc-100 dark:border-white/10 shrink-0">
                            <flux:button type="button" size="sm" variant="ghost" @click="open = false">Tutup</flux:button>
                        </div>
                    </div>

                    @if ($errors->has('tool_id'))
                        <flux:text class="text-sm text-red-500">{{ $errors->first('tool_id') }}</flux:text>
                    @endif
                </div>

                {{-- Quantity + Checkout Date --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:input wire:model.live="tool_quantity" label="Jumlah" type="number" min="1" :error="$errors->first('tool_quantity')" />
                    <flux:input wire:model.live="checkout_date" label="Tgl Pinjam" type="date" :error="$errors->first('checkout_date')" />
                </div>

                {{-- Notes --}}
                <flux:textarea wire:model.live="tool_notes" label="Catatan" placeholder="Catatan tambahan (opsional)" rows="2" />

                {{-- Info Box --}}
                <div 
                    class="rounded-lg border border-zinc-200 dark:border-white/10 bg-zinc-50 dark:bg-zinc-900/50 p-4 space-y-3"
                    x-data="{
                        tools: @js($tools->keyBy('id')->map(fn($t) => ['name' => $t->name, 'code' => $t->code, 'available_qty' => $t->available_qty])),
                        get houseCount() { return $wire.house_ids.length },
                        get toolId() { return $wire.tool_id },
                        get quantity() { return parseInt($wire.tool_quantity) || 0 },
                        get isValid() { return this.houseCount > 0 && this.toolId && this.quantity > 0 && this.tools[this.toolId] },
                        get totalQty() { return this.quantity * this.houseCount },
                        get availableAfter() { return this.isValid ? this.tools[this.toolId].available_qty - this.totalQty : 0 },
                        get toolName() { return this.isValid ? this.tools[this.toolId].name + ' (' + this.tools[this.toolId].code + ')' : '' },
                    }"
                >
                    <flux:heading size="sm" class="text-zinc-700 dark:text-zinc-300">Pratinjau Peminjaman Alat</flux:heading>
                    <div x-show="isValid" class="space-y-2" style="display: none;">
                        <div class="flex justify-between text-sm">
                            <span class="text-zinc-500">Rumah Dipilih:</span>
                            <span class="font-medium text-zinc-800 dark:text-zinc-200" x-text="houseCount"></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-zinc-500">Alat:</span>
                            <span class="font-medium text-zinc-800 dark:text-zinc-200" x-text="toolName"></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-zinc-500">Jumlah Per Rumah:</span>
                            <span class="font-medium text-zinc-800 dark:text-zinc-200" x-text="quantity"></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-zinc-500">Total Alat Dibutuhkan:</span>
                            <span class="font-medium text-zinc-800 dark:text-zinc-200" x-text="totalQty"></span>
                        </div>
                        <div class="border-t border-zinc-200 dark:border-white/10 pt-2 mt-2">
                            <div class="flex justify-between">
                                <span class="font-medium text-zinc-700 dark:text-zinc-300">Sisa Setelah Peminjaman:</span>
                                <span class="font-bold text-lg" :class="availableAfter >= 0 ? 'text-zinc-800 dark:text-zinc-200' : 'text-red-600 dark:text-red-400'" x-text="availableAfter"></span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Tool Tab Buttons --}}
                <div class="flex justify-end gap-3">
                    <flux:button wire:click="resetToolForm" variant="ghost">Reset</flux:button>
                    <flux:button wire:click="showToolConfirmationModal" variant="primary">Simpan</flux:button>
                </div>
            </div>
        </div>
    </div>

    {{-- Material Confirmation Modal --}}
    <div 
        x-data="{ show: @entangle('showMaterialConfirmation').live }"
        x-show="show"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
        aria-labelledby="modal-material-title"
        role="dialog"
        aria-modal="true"
    >
        <div x-show="show" class="fixed inset-0 bg-black/50 transition-opacity" @click="show = false"></div>
        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0 relative z-10">
            <div
                x-show="show"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="relative transform overflow-hidden rounded-xl bg-white dark:bg-zinc-800 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-zinc-200 dark:border-white/10"
            >
                <div class="bg-white dark:bg-zinc-800 px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex size-12 shrink-0 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/30 sm:mx-0 sm:size-10">
                            <svg class="size-6 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                            <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100" id="modal-material-title">
                                Konfirmasi Penggunaan Material
                            </h3>
                            <div class="mt-4 space-y-3 text-sm text-zinc-600 dark:text-zinc-400">
                                <div class="grid grid-cols-2 gap-2">
                                    <div class="font-medium">Rumah Dipilih:</div>
                                    <div x-text="$wire.materialConfirmationData.houseCount + ' rumah'"></div>
                                    
                                    <div class="font-medium">Detail Rumah:</div>
                                    <div class="truncate" x-text="$wire.materialConfirmationData.houses"></div>
                                    
                                    <div class="font-medium">Material:</div>
                                    <div x-text="$wire.materialConfirmationData.materialName"></div>
                                    
                                    <div class="font-medium">Jumlah Per Rumah:</div>
                                    <div x-text="$wire.materialConfirmationData.quantityPerHouse + ' ' + $wire.materialConfirmationData.materialUnit"></div>
                                    
                                    <div class="font-medium">Total Kuantitas:</div>
                                    <div x-text="$wire.materialConfirmationData.totalQuantity + ' ' + $wire.materialConfirmationData.materialUnit"></div>
                                    
                                    <div class="font-medium">Harga Satuan:</div>
                                    <div x-text="'Rp ' + new Intl.NumberFormat('id-ID').format($wire.materialConfirmationData.unitPrice)"></div>
                                    
                                    <div class="font-medium">Tanggal Penggunaan:</div>
                                    <div x-text="$wire.materialConfirmationData.usageDate"></div>
                                </div>
                                
                                @if ($errors->any())
                                    <div class="mt-4 p-3 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800/50">
                                        @foreach ($errors->all() as $error)
                                            <p class="text-sm text-red-600 dark:text-red-400">{{ $error }}</p>
                                        @endforeach
                                    </div>
                                @endif
                                
                                <div class="mt-4 pt-4 border-t border-zinc-200 dark:border-white/10">
                                    <div class="flex justify-between items-center">
                                        <span class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Total Biaya:</span>
                                        <span class="text-xl font-bold text-emerald-600 dark:text-emerald-400" x-text="'Rp ' + new Intl.NumberFormat('id-ID').format($wire.materialConfirmationData.totalCost)"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-zinc-50 dark:bg-zinc-900/50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 gap-2">
                    <flux:button wire:click="saveMaterial" variant="primary" class="w-full sm:w-auto">
                        Ya, Simpan
                    </flux:button>
                    <flux:button @click="show = false" variant="ghost" class="mt-3 w-full sm:mt-0 sm:w-auto">
                        Batal
                    </flux:button>
                </div>
            </div>
        </div>
    </div>

    {{-- Tool Confirmation Modal --}}
    <div 
        x-data="{ show: @entangle('showToolConfirmation').live }"
        x-show="show"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
        aria-labelledby="modal-tool-title"
        role="dialog"
        aria-modal="true"
    >
        <div x-show="show" class="fixed inset-0 bg-black/50 transition-opacity" @click="show = false"></div>
        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0 relative z-10">
            <div
                x-show="show"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="relative transform overflow-hidden rounded-xl bg-white dark:bg-zinc-800 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-zinc-200 dark:border-white/10"
            >
                <div class="bg-white dark:bg-zinc-800 px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex size-12 shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30 sm:mx-0 sm:size-10">
                            <svg class="size-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                            <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100" id="modal-tool-title">
                                Konfirmasi Peminjaman Alat
                            </h3>
                            <div class="mt-4 space-y-3 text-sm text-zinc-600 dark:text-zinc-400">
                                <div class="grid grid-cols-2 gap-2">
                                    <div class="font-medium">Rumah Dipilih:</div>
                                    <div x-text="$wire.toolConfirmationData.houseCount + ' rumah'"></div>
                                    
                                    <div class="font-medium">Detail Rumah:</div>
                                    <div class="truncate" x-text="$wire.toolConfirmationData.houses"></div>
                                    
                                    <div class="font-medium">Alat:</div>
                                    <div x-text="$wire.toolConfirmationData.toolName"></div>
                                    
                                    <div class="font-medium">Jumlah Per Rumah:</div>
                                    <div x-text="$wire.toolConfirmationData.quantityPerHouse"></div>
                                    
                                    <div class="font-medium">Total Alat Dipinjam:</div>
                                    <div x-text="$wire.toolConfirmationData.totalQuantity"></div>
                                    
                                    <div class="font-medium">Sisa Setelah Peminjaman:</div>
                                    <div x-text="$wire.toolConfirmationData.availableAfter"></div>
                                    
                                    <div class="font-medium">Tgl Pinjam:</div>
                                    <div x-text="$wire.toolConfirmationData.checkoutDate"></div>
                                </div>
                                
                                @if ($errors->any())
                                    <div class="mt-4 p-3 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800/50">
                                        @foreach ($errors->all() as $error)
                                            <p class="text-sm text-red-600 dark:text-red-400">{{ $error }}</p>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-zinc-50 dark:bg-zinc-900/50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 gap-2">
                    <flux:button wire:click="saveTool" variant="primary" class="w-full sm:w-auto">
                        Ya, Simpan
                    </flux:button>
                    <flux:button @click="show = false" variant="ghost" class="mt-3 w-full sm:mt-0 sm:w-auto">
                        Batal
                    </flux:button>
                </div>
            </div>
        </div>
    </div>

    {{-- Return Confirmation Modal --}}
    <div
        x-data="{ show: @entangle('showReturnConfirmation').live }"
        x-show="show"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
        aria-labelledby="modal-return-title"
        role="dialog"
        aria-modal="true"
    >
        <div x-show="show" class="fixed inset-0 bg-black/50 transition-opacity" @click="show = false"></div>
        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0 relative z-10">
            <div
                x-show="show"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="relative transform overflow-hidden rounded-xl bg-white dark:bg-zinc-800 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-zinc-200 dark:border-white/10"
            >
                <div class="bg-white dark:bg-zinc-800 px-4 py-5 sm:p-6">
                    <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100 mb-4">Konfirmasi Pengembalian Alat</h3>

                    <div class="space-y-2" x-show="$wire.returnConfirmationData.length > 0">
                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-2"
                           x-text="$wire.returnConfirmationData.length + ' alat akan dikembalikan'"></p>

                        <template x-for="(item, index) in $wire.returnConfirmationData" :key="'return-' + index">
                            <div x-show="item && item.tool_name && (item.qty_normal + item.qty_broken + item.qty_lost) > 0" class="rounded-lg bg-zinc-50 dark:bg-zinc-900/50 border border-zinc-200 dark:border-white/10 p-3 text-sm">
                                <div class="flex justify-between items-start gap-2 mb-2">
                                    <div class="min-w-0">
                                        <span class="font-medium text-zinc-800 dark:text-zinc-200 block truncate" x-text="item.tool_name"></span>
                                        <span class="text-xs text-zinc-500 dark:text-zinc-400" x-text="item.house_name + ' \u2022 Kembalikan ' + item.return_qty + ' dari ' + item.quantity + ' unit'"></span>
                                    </div>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <span x-show="item.qty_normal > 0"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                                        <span x-text="'Baik: ' + item.qty_normal"></span>
                                    </span>
                                    <span x-show="item.qty_broken > 0"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                                        <span x-text="'Rusak: ' + item.qty_broken"></span>
                                    </span>
                                    <span x-show="item.qty_lost > 0"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                                        <span x-text="'Hilang: ' + item.qty_lost"></span>
                                    </span>
                                </div>
                                <div x-show="item.return_qty < item.quantity" class="mt-2 text-xs text-amber-600 dark:text-amber-400 font-medium" x-text="'Sisa ' + (item.quantity - item.return_qty) + ' unit tetap dipinjam'"></div>
                                <div x-show="item.notes" class="mt-1 text-xs text-zinc-500 dark:text-zinc-400 italic" x-text="'Catatan: ' + item.notes"></div>
                            </div>
                        </template>
                    </div>

                    @if ($errors->any())
                        <div class="mt-4 p-3 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800/50">
                            @foreach ($errors->all() as $error)
                                <p class="text-sm text-red-600 dark:text-red-400">{{ $error }}</p>
                            @endforeach
                        </div>
                    @endif
                </div>
                <div class="bg-zinc-50 dark:bg-zinc-900/50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 gap-2">
                    <flux:button wire:click="saveReturn" variant="primary" class="w-full sm:w-auto">
                        Ya, Kembalikan
                    </flux:button>
                    <flux:button @click="show = false" variant="ghost" class="mt-3 w-full sm:mt-0 sm:w-auto">
                        Batal
                    </flux:button>
                </div>
            </div>
        </div>
    </div>
</div>

<div
    class="transaction-workspace"
    x-data="{
        activeTab: @entangle('activeTab').live,
        vendorServiceTarget: @entangle('vendor_service_target').live,
        housePickerOpen: @entangle('housePickerOpen').live,
        allocationType: 'material',
        matPickerOpen: false,
        batchPickerOpen: false,
        toolPickerOpen: false,
        warehousePickerOpen: false,
        matNotePickerOpen: false,
        toolNotePickerOpen: false,
        showAllHouses: false,
        showAllSummaryHouses: false,
        matSearch: '',
        toolSearch: '',
        matNoteSearch: '',
        toolNoteSearch: '',
        peruntukkanOpts: @js(array_values(array_unique(array_merge(['Pemasangan Pondasi', 'Pekerjaan Dinding', 'Pekerjaan Atap', 'Pengecoran Sloof / Kolom', 'Pemasangan Keramik', 'Instalasi Listrik'], $peruntukkanOptions)))),
        materials: @js($materials->keyBy('id')->map(fn($m) => ['id' => $m->id, 'name' => $m->name, 'code' => $m->code, 'unit' => $m->unit, 'stock' => $m->stock])),
        materialBatches: @entangle('materialBatchMap').live,
        materialSourceId: @entangle('material_batch_id').live,
        tools: @js($tools->keyBy('id')->map(fn($t) => ['id' => $t->id, 'name' => $t->name, 'available_qty' => $t->available_qty, 'code' => $t->code, 'warehouse' => $t->warehouse?->name ?? 'Belum ada gudang'])),
        get houseCount() { return $wire.house_ids.length },
        get mat() { return this.materials[$wire.material_id] ?? null },
        get matBatch() { return this.materialBatches[this.materialSourceId] ?? null },
        get tool() { return this.tools[$wire.tool_id] ?? null },
        get matQty() { return parseFloat($wire.material_quantity) || 0 },
        get toolQty() { return parseInt($wire.tool_quantity) || 0 },
        get matReady() { return this.houseCount > 0 && this.mat && this.matBatch && this.matQty > 0 },
        get toolReady() { return this.houseCount > 0 && this.tool && this.toolQty > 0 },
        get totalQty() { return this.matQty * this.houseCount },
        get totalCost() { return this.matBatch ? this.totalQty * parseFloat(this.matBatch.unit_price) : 0 },
        get totalTools() { return this.toolQty * this.houseCount },
        get toolShortfall() { return this.tool ? this.tool.available_qty - this.totalTools : 0 },
        get returnCount() {
            return Object.values($wire.returnSelections ?? {}).filter(s => s.selected).length
        },
        get returnQty() {
            return Object.values($wire.returnSelections ?? {}).filter(s => s.selected)
                .reduce((total, s) => total + (parseInt(s.qty_normal) || 0) + (parseInt(s.qty_broken) || 0) + (parseInt(s.qty_lost) || 0), 0)
        },
        rp(v) { return 'Rp ' + new Intl.NumberFormat('id-ID').format(v) },
    }"
    @keydown.escape.window="matPickerOpen = false; batchPickerOpen = false; toolPickerOpen = false; warehousePickerOpen = false; matNotePickerOpen = false; toolNotePickerOpen = false; housePickerOpen = false"
>
    @if ($clusterAssignmentMissing)
        <div class="alert alert-warning rounded-3" role="alert">Akun Logistik belum memiliki cluster tugas. Minta Admin menetapkan cluster sebelum membuat alokasi.</div>
    @endif
    @php
        $selectedHouses = $houses->whereIn('id', $house_ids);
        $selectedVendorCluster = $clusters->firstWhere('id', (int) $vendor_service_cluster_id);
        $noteOptions = array_values(array_unique(array_merge([
            'Pemasangan Pondasi',
            'Pekerjaan Dinding',
            'Pekerjaan Atap',
            'Pengecoran Sloof / Kolom',
            'Pemasangan Keramik',
            'Instalasi Listrik',
        ], $peruntukkanOptions)));
    @endphp

    <div class="container-fluid p-0 pb-4">

        <header class="card border-0 shadow-sm rounded-4 mb-4 bg-body-tertiary workflow-page-header transaction-header">
            <div class="card-body p-4 p-md-5 transaction-header-body">
                <div>
                    <h1 class="display-5 fw-black text-body mb-2 font-outfit transaction-title">Buat alokasi <span class="text-success">lapangan</span></h1>
                    <p class="text-secondary mb-0 max-w-xl transaction-intro">Pilih alur, tentukan rumah, lalu periksa dampaknya sebelum disimpan.</p>
                </div>
                <p class="transaction-header-note" x-show="activeTab !== 'spreadsheet'" x-cloak>
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M12 3 4.5 7v10L12 21l7.5-4V7L12 3Z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                        <path d="m4.8 7.2 7.2 4 7.2-4M12 11.2V21" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                    </svg>
                    <span x-text="activeTab === 'rental' ? 'Sewa vendor tercatat sebagai biaya cluster, tanpa mengubah stok gudang.' : (activeTab === 'vendor-service' ? 'Catat pekerjaan vendor pada cluster atau satu rumah.' : (activeTab === 'return' ? 'Pengembalian langsung memperbarui pinjaman dan saldo gudang.' : 'Material langsung masuk ke catatan biaya rumah dan alat langsung tercatat sebagai pinjaman rumah.'))"></span>
                </p>
            </div>
        </header>

        @teleport('body')
            <div>
                @if ($errors->any() || $noticeMessage || session('success'))
                <div wire:key="transaction-feedback-{{ $noticeSequence }}" class="transaction-feedback {{ $errors->any() || $noticeType === 'error' ? 'transaction-feedback--error' : 'transaction-feedback--success' }}" x-data="{ visible: true }" x-show="visible" role="{{ $errors->any() || $noticeType === 'error' ? 'alert' : 'status' }}" aria-live="{{ $errors->any() || $noticeType === 'error' ? 'assertive' : 'polite' }}">
                    <div class="transaction-feedback-content">
                        <strong>{{ $errors->any() || $noticeType === 'error' ? 'Periksa isian' : 'Berhasil dicatat' }}</strong>
                        <p class="mb-0">{{ $errors->first() ?: ($noticeMessage ?: session('success')) }}</p>
                        @if ($noticeType === 'success' && $noticeHistoryType === 'material')
                            <a href="{{ route('logistik.material-log') }}" wire:navigate class="transaction-feedback-action link-success">Lihat riwayat</a>
                        @elseif ($noticeType === 'success' && $noticeHistoryType === 'tool')
                            <a href="{{ route('logistik.tool-log') }}" wire:navigate class="transaction-feedback-action link-success">Lihat riwayat</a>
                        @elseif ($noticeType === 'success' && $noticeHistoryType === 'both')
                            <div class="d-flex flex-wrap gap-3">
                                <a href="{{ route('logistik.material-log') }}" wire:navigate class="transaction-feedback-action link-success">Lihat riwayat material</a>
                                <a href="{{ route('logistik.tool-log') }}" wire:navigate class="transaction-feedback-action link-success">Lihat riwayat alat</a>
                            </div>
                        @endif
                    </div>
                    <button type="button" class="transaction-feedback-close" @click="visible = false" aria-label="Tutup pemberitahuan">Tutup</button>
                </div>
                @endif
            </div>
        @endteleport

                <div class="transaction-modes" role="group" aria-label="Jenis alokasi">
                    @foreach ([
                        ['key' => 'spreadsheet', 'title' => 'Alokasi spreadsheet', 'description' => ''],
                        ['key' => 'allocation', 'title' => 'Alokasi Formulir', 'description' => 'Pilih material atau alat'],
                        ['key' => 'vendor-service', 'title' => 'Vendor', 'description' => 'Sewa Jasa Vendor'],
                        ['key' => 'rental', 'title' => 'Sewa alat', 'description' => 'Catat alat milik pihak ketiga'],
                        ['key' => 'return', 'title' => 'Kembalikan alat', 'description' => 'Catat kondisi dan gudang penerima'],
                    ] as $mode)
                            <button
                                type="button"
                                @click="activeTab = '{{ $mode['key'] }}'"
                                wire:click="$set('activeTab', '{{ $mode['key'] }}')"
                                class="transaction-mode"
                                :class="{ 'is-active': activeTab === '{{ $mode['key'] }}' }"
                                :aria-pressed="activeTab === '{{ $mode['key'] }}'"
                            >
                                <span class="transaction-mode-icon" aria-hidden="true">
                                    @if ($mode['key'] === 'spreadsheet')
                                        <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="1.5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M3 9h18M8 9v11m5-11v11m4-11v11M3 14h18" fill="none" stroke="currentColor" stroke-width="1.4"/></svg>
                                    @elseif ($mode['key'] === 'allocation')
                                        <svg viewBox="0 0 24 24"><path d="M6 3.75h8l4 4V20.25H6V3.75Z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M14 3.75v4h4M9 12h6M9 15.5h6" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                                    @elseif ($mode['key'] === 'rental')
                                        <svg viewBox="0 0 24 24"><path d="M3 7h18v13H3V7Zm3 0V4h12v3M8 12h8M8 16h5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    @elseif ($mode['key'] === 'vendor-service')
                                        <svg viewBox="0 0 24 24"><path d="M4 20V9l8-5 8 5v11M8 20v-6h8v6M3 20h18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 9h.01M15 9h.01" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg>
                                    @else
                                        <svg viewBox="0 0 24 24"><path d="M5 8h11a4 4 0 0 1 0 8H8M8 5 5 8l3 3" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    @endif
                                </span>
                                <span class="transaction-mode-copy">
                                    <span class="transaction-mode-title">{{ $mode['title'] }}</span>
                                    <span class="transaction-mode-description">{{ $mode['description'] }}</span>
                                </span>
                            </button>
                    @endforeach
                </div>

        @include('livewire.logistik.partials.spreadsheet-workspace')

        <div class="transaction-grid" x-show="activeTab !== 'spreadsheet'" x-cloak>
            <div class="transaction-form transaction-sheet">
        <section class="transaction-step-block transaction-step-block--destination" aria-labelledby="house-section-title">
            <div class="transaction-step-head">
                <div class="transaction-step-heading">
                    <span class="transaction-step-index" aria-hidden="true">01</span>
                    <div>
                    <h2 id="house-section-title" class="h6 fw-bold mb-1" x-text="activeTab === 'vendor-service' ? 'Target pekerjaan' : (activeTab === 'return' ? 'Rumah asal alat' : 'Rumah tujuan')"></h2>
                    <p class="small text-secondary mb-0" x-text="activeTab === 'vendor-service' ? (vendorServiceTarget === 'cluster' ? 'Untuk pekerjaan area bersama seperti drainase, jalur listrik, atau fasilitas umum.' : 'Pilih satu rumah untuk pekerjaan unit seperti interior atau plumbing.') : (activeTab === 'return' ? 'Pilih rumah yang masih meminjam alat.' : (activeTab === 'rental' ? 'Pilih beberapa rumah dalam satu cluster untuk sewa alat yang sama.' : 'Bisa pilih beberapa rumah untuk satu alokasi.'))"></p>
                    </div>
                </div>
                <div x-show="activeTab === 'vendor-service' && vendorServiceTarget === 'house'" x-cloak>
                    <button type="button" @click="housePickerOpen = true" wire:click="openHousePicker" wire:loading.attr="disabled" wire:target="activeTab,vendor_service_target" class="btn transaction-house-button flex-shrink-0" aria-haspopup="dialog" aria-controls="house-dialog">
                        {{ count($house_ids) ? 'Ubah rumah' : 'Pilih 1 rumah' }}
                    </button>
                </div>
                <button x-show="activeTab !== 'vendor-service'" type="button" @click="housePickerOpen = true" wire:click="openHousePicker" wire:loading.attr="disabled" wire:target="activeTab" class="btn transaction-house-button flex-shrink-0" aria-haspopup="dialog" aria-controls="house-dialog">
                    {{ count($house_ids) ? 'Ubah rumah' : 'Pilih rumah' }}
                </button>
            </div>

            <fieldset x-show="activeTab === 'vendor-service'" x-cloak class="vendor-service-target mt-3">
                <legend class="small fw-semibold mb-2">Pilih target jasa</legend>
                <div class="vendor-service-target-choice">
                    <input type="radio" class="btn-check" name="vendor-service-target" id="vendor-service-target-cluster" value="cluster" wire:model.live="vendor_service_target">
                    <label class="btn btn-outline-success text-start" for="vendor-service-target-cluster">
                        <span class="d-block fw-semibold">Cluster / area bersama</span>
                        <span class="d-block small">Drainase, jalur listrik, fasilitas umum</span>
                    </label>
                    <input type="radio" class="btn-check" name="vendor-service-target" id="vendor-service-target-house" value="house" wire:model.live="vendor_service_target">
                    <label class="btn btn-outline-success text-start" for="vendor-service-target-house">
                        <span class="d-block fw-semibold">Rumah</span>
                        <span class="d-block small">Interior, plumbing, pekerjaan unit</span>
                    </label>
                </div>
                @if (auth()->user()->role === 'admin')
                <div x-show="vendorServiceTarget === 'cluster'" x-cloak class="mt-3">
                    <label for="vendor-service-cluster" class="form-label small fw-semibold">Cluster pekerjaan <span class="text-danger">*</span></label>
                    <select id="vendor-service-cluster" wire:model="vendor_service_cluster_id" class="form-select" required>
                        <option value="">Pilih cluster</option>
                        @foreach ($clusters as $cluster)
                            <option value="{{ $cluster->id }}">{{ $cluster->name }}</option>
                        @endforeach
                    </select>
                    @error('vendor_service_cluster_id') <div class="text-danger small mt-1" role="alert">{{ $message }}</div> @enderror
                    @if ($clusters->isEmpty())
                        <p class="text-secondary small mt-2 mb-0">Belum ada cluster yang dapat dipilih.</p>
                    @endif
                </div>
                @endif
            </fieldset>

            @if ($selectedHouses->isNotEmpty())
                <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
                    @foreach ($selectedHouses->take(3) as $house)
                        <span class="selected-house">{{ $house->name }}</span>
                    @endforeach
                    @if ($selectedHouses->count() > 3)
                        @foreach ($selectedHouses->skip(3) as $house)
                            <span class="selected-house" x-show="showAllHouses" x-cloak>{{ $house->name }}</span>
                        @endforeach
                        <button type="button" class="btn btn-link p-1" @click="showAllHouses = !showAllHouses" :aria-expanded="showAllHouses.toString()" x-text="showAllHouses ? 'Sembunyikan' : '+{{ $selectedHouses->count() - 3 }} lainnya'"></button>
                    @endif
                    <span class="small text-secondary ms-auto">{{ $selectedHouses->count() }} rumah dipilih</span>
                </div>
            @endif
            @error('house_ids')
                <p class="text-danger small mt-2 mb-0" role="alert">{{ $message }}</p>
            @enderror
        </section>

        <!-- ===== Details ===== -->
        <div class="transaction-step-block transaction-step-block--details">

            <div x-show="activeTab === 'allocation'" x-cloak class="allocation-kind-switch mb-4" role="group" aria-label="Jenis alokasi">
                <button type="button" class="btn log-row-action" :class="allocationType === 'material' ? 'log-row-action--edit' : 'log-row-action--quiet'" :aria-pressed="allocationType === 'material'" @click="allocationType = 'material'">Material</button>
                <button type="button" class="btn log-row-action" :class="allocationType === 'tool' ? 'log-row-action--edit' : 'log-row-action--quiet'" :aria-pressed="allocationType === 'tool'" @click="allocationType = 'tool'">Alat</button>
            </div>

                    <div x-show="activeTab === 'rental'" x-cloak>
                <div class="transaction-step-heading mb-4">
                    <span class="transaction-step-index" aria-hidden="true">02</span>
                    <div>
                        <h2 class="h6 fw-bold mb-1">Sewa alat dari vendor</h2>
                        <p class="small text-secondary mb-0">Alat milik vendor dicatat sebagai biaya cluster, tanpa menambah stok gudang.</p>
                    </div>
                </div>
                <form wire:submit="saveRental" class="row g-3" novalidate>
                    <div class="col-12">
                        <label for="rental-description" class="form-label fw-semibold">Nama alat <span class="text-danger">*</span></label>
                        <input id="rental-description" type="text" wire:model.live.debounce.300ms="rental_description" class="form-control" placeholder="Contoh: Excavator 20 ton" required maxlength="255">
                        @error('rental_description') <div class="text-danger small mt-1" role="alert">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6" wire:key="rental-vendor-picker-{{ count($supplierNames) }}" x-data="{
                        open: false,
                        query: '',
                        active: 0,
                        suppliers: @js($supplierNames),
                        get filtered() {
                            const term = this.query.trim().toLocaleLowerCase('id');
                            return term ? this.suppliers.filter(name => name.toLocaleLowerCase('id').includes(term)) : this.suppliers;
                        },
                        choose(name) {
                            this.$refs.input.value = name;
                            this.query = name;
                            this.open = false;
                            $wire.set('rental_vendor', name);
                        },
                        move(step) {
                            if (!this.filtered.length) return;
                            this.active = Math.max(0, Math.min(this.filtered.length - 1, this.active + step));
                            this.$nextTick(() => this.$refs.options?.querySelectorAll('[role=option]')[this.active]?.scrollIntoView({ block: 'nearest' }));
                        }
                    }" @click.outside="open = false">
                        <label for="rental-vendor" class="form-label fw-semibold">Vendor <span class="text-danger">*</span></label>
                        <div class="position-relative">
                            <textarea id="rental-vendor" x-ref="input" rows="1" wire:model="rental_vendor" class="form-control pe-5" style="min-height:56px; max-height:160px; resize:vertical; overflow-y:hidden;" placeholder="Nama vendor..." role="combobox" aria-autocomplete="list" aria-controls="rental-vendor-options" :aria-expanded="open.toString()" :aria-activedescendant="open && filtered[active] ? `rental-vendor-option-${active}` : null" autocomplete="off" required maxlength="255"
                                @focus="open = true; query = $el.value; active = 0"
                                @input="open = true; query = $el.value; active = 0"
                                @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)"
                                @keydown.enter.prevent="if (open && filtered.length) choose(filtered[active])"
                                @keydown.escape.stop="open = false" @keydown.tab="open = false"></textarea>
                            <button type="button" class="btn btn-link text-secondary position-absolute end-0 top-0 p-0 d-flex align-items-center justify-content-center" style="width:44px; height:44px;" aria-label="Tampilkan pilihan vendor" @click="open = !open; query = $refs.input.value; active = 0">
                                <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1 0-.708z"/></svg>
                            </button>
                        </div>
                        <div id="rental-vendor-options" x-ref="options" x-show="open" x-cloak role="listbox" aria-label="Pilihan vendor" class="border rounded-3 mt-2 bg-body" style="max-height:320px; overflow-y:auto;">
                            <template x-for="(name, index) in filtered" :key="name">
                                <button type="button" role="option" :id="`rental-vendor-option-${index}`" :aria-selected="$wire.rental_vendor === name" tabindex="-1" class="d-block w-100 border-0 border-bottom px-3 py-2 text-start" style="min-height:56px;" :class="index === active ? 'bg-success-subtle text-body' : 'bg-body text-body'" @mouseenter="active = index" @mousedown.prevent @click="choose(name)" x-text="name"></button>
                            </template>
                            <div x-show="filtered.length === 0 && !query.trim()" class="small text-secondary px-3 py-3">Belum ada supplier. Ketik nama vendor baru.</div>
                            <div x-show="query.trim() && !suppliers.some(name => name.toLocaleLowerCase('id') === query.trim().toLocaleLowerCase('id'))" class="small text-secondary px-3 py-3">Nama baru akan ditambahkan ke daftar supplier saat sewa disimpan.</div>
                        </div>
                        @error('rental_vendor') <div class="text-danger small mt-1" role="alert">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="rental-quantity" class="form-label fw-semibold">Total unit sewa <span class="text-danger">*</span></label>
                        <input id="rental-quantity" type="number" min="1" step="1" wire:model="rental_quantity" class="form-control" required>
                        @error('rental_quantity') <div class="text-danger small mt-1" role="alert">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="rental-start" class="form-label fw-semibold">Mulai sewa <span class="text-danger">*</span></label>
                        <input id="rental-start" type="date" wire:model="rental_start_date" class="form-control" required>
                        @error('rental_start_date') <div class="text-danger small mt-1" role="alert">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="rental-due" class="form-label fw-semibold">Akhir sewa <span class="text-danger">*</span></label>
                        <input id="rental-due" type="date" wire:model="rental_due_date" class="form-control" required>
                        @error('rental_due_date') <div class="text-danger small mt-1" role="alert">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6" x-data="{
                        display: '',
                        init() {
                            this.display = this.format($wire.rental_amount);
                            this.$watch('display', value => {
                                const digits = value.replace(/\D/g, '');
                                const formatted = digits === '' ? '' : this.format(Number(digits));
                                if (this.display !== formatted) this.display = formatted;
                                $wire.set('rental_amount', digits === '' ? '' : Number(digits));
                            });
                            $wire.$watch('rental_amount', value => {
                                if (document.activeElement !== this.$refs.input) this.display = this.format(value);
                            });
                        },
                        format(value) {
                            return value === '' || value === null || value === undefined ? '' : 'Rp ' + Number(value).toLocaleString('id-ID', { maximumFractionDigits: 0 });
                        }
                    }">
                        <label for="rental-amount" class="form-label fw-semibold">Total biaya sewa <span class="text-danger">*</span></label>
                        <input id="rental-amount" x-ref="input" type="text" inputmode="numeric" x-model="display" class="form-control font-mono" placeholder="Rp 0" required>
                        @error('rental_amount') <div class="text-danger small mt-1" role="alert">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="rental-bill" class="form-label fw-semibold">Foto tagihan <span class="text-danger">*</span></label>
                        <input id="rental-bill" type="file" wire:model="rental_bill_image" accept="image/*" class="form-control" required>
                        @error('rental_bill_image') <div class="text-danger small mt-1" role="alert">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12">
                        <label for="rental-notes" class="form-label fw-semibold">Catatan</label>
                        <textarea id="rental-notes" wire:model="rental_notes" class="form-control" rows="2" maxlength="2000"></textarea>
                        @error('rental_notes') <div class="text-danger small mt-1" role="alert">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 d-flex justify-content-end gap-2">
                        <button type="button" wire:click="resetRentalForm" class="btn btn-outline-secondary">Bersihkan</button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="saveRental,rental_bill_image" class="btn btn-success fw-semibold">Catat sewa vendor</button>
                    </div>
                </form>
            </div>

            <div x-show="activeTab === 'vendor-service'" x-cloak>
                <div class="transaction-step-heading mb-4">
                    <span class="transaction-step-index" aria-hidden="true">02</span>
                    <div>
                        <h2 class="h6 fw-bold mb-1">Sewa Jasa Vendor</h2>
                        <p class="small text-secondary mb-0">Biaya dicatat satu kali pada target pekerjaan yang dipilih.</p>
                    </div>
                </div>
                <form wire:submit="showVendorServiceConfirmationModal" class="row g-3" novalidate>
                    <div class="col-md-6">
                        <label for="vendor-service-description" class="form-label fw-semibold">Nama jasa <span class="text-danger">*</span></label>
                        <input id="vendor-service-description" type="text" wire:model="vendor_service_description" class="form-control" placeholder="Contoh: Pemasangan kanopi" required maxlength="255">
                        @error('vendor_service_description') <div class="text-danger small mt-1" role="alert">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="vendor-service-vendor" class="form-label fw-semibold">Vendor <span class="text-danger">*</span></label>
                        <input id="vendor-service-vendor" type="text" wire:model="vendor_service_vendor" list="vendor-service-suggestions" class="form-control" placeholder="Nama vendor" required maxlength="255" autocomplete="off">
                        <datalist id="vendor-service-suggestions">
                            @foreach ($supplierNames as $supplierName)
                                <option value="{{ $supplierName }}"></option>
                            @endforeach
                        </datalist>
                        @error('vendor_service_vendor') <div class="text-danger small mt-1" role="alert">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="vendor-service-date" class="form-label fw-semibold">Tanggal layanan <span class="text-danger">*</span></label>
                        <input id="vendor-service-date" type="date" wire:model="vendor_service_date" class="form-control" required>
                        @error('vendor_service_date') <div class="text-danger small mt-1" role="alert">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6" x-data="{
                        display: '',
                        init() {
                            this.display = this.format($wire.vendor_service_amount);
                            this.$watch('display', value => {
                                const digits = value.replace(/\D/g, '');
                                const formatted = digits === '' ? '' : this.format(Number(digits));
                                if (this.display !== formatted) this.display = formatted;
                                $wire.set('vendor_service_amount', digits === '' ? '' : Number(digits));
                            });
                            $wire.$watch('vendor_service_amount', value => {
                                if (document.activeElement !== this.$refs.input) this.display = this.format(value);
                            });
                        },
                        format(value) {
                            return value === '' || value === null || value === undefined ? '' : 'Rp ' + Number(value).toLocaleString('id-ID', { maximumFractionDigits: 0 });
                        }
                    }">
                        <label for="vendor-service-amount" class="form-label fw-semibold">Total biaya jasa <span class="text-danger">*</span></label>
                        <input id="vendor-service-amount" x-ref="input" type="text" inputmode="numeric" x-model="display" class="form-control font-mono" placeholder="Rp 0" required>
                        @error('vendor_service_amount') <div class="text-danger small mt-1" role="alert">{{ $message }}</div> @enderror
                        <div class="form-text">Biaya dicatat satu kali pada target pekerjaan yang dipilih.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="vendor-service-bill" class="form-label fw-semibold">Foto tagihan <span class="text-secondary fw-normal">(opsional)</span></label>
                        <input id="vendor-service-bill" type="file" wire:model="vendor_service_bill_image" accept="image/*" class="form-control">
                        @error('vendor_service_bill_image') <div class="text-danger small mt-1" role="alert">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12">
                        <label for="vendor-service-notes" class="form-label fw-semibold">Catatan</label>
                        <textarea id="vendor-service-notes" wire:model="vendor_service_notes" class="form-control" rows="2" maxlength="2000"></textarea>
                        @error('vendor_service_notes') <div class="text-danger small mt-1" role="alert">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 vendor-service-actions">
                        <button type="button" wire:click="resetVendorServiceForm" wire:loading.attr="disabled" wire:target="resetVendorServiceForm" class="btn vendor-service-clear">Bersihkan</button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="showVendorServiceConfirmationModal,vendor_service_bill_image" class="btn btn-success fw-semibold vendor-service-review">
                            <span wire:loading.remove wire:target="showVendorServiceConfirmationModal">Tinjau &amp; catat</span>
                            <span wire:loading wire:target="showVendorServiceConfirmationModal">Memeriksa…</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- ---- Material ---- -->
            <div x-show="activeTab === 'allocation' && allocationType === 'material'" x-cloak>
                <div class="transaction-step-heading mb-4">
                    <span class="transaction-step-index" aria-hidden="true">02</span>
                    <div>
                        <h2 class="h6 fw-bold font-outfit text-body mb-1">Detail alokasi material</h2>
                        <p class="small text-secondary mb-0">Jumlah di bawah diterapkan ke setiap rumah yang dipilih.</p>
                    </div>
                </div>

                <div class="row g-3 align-items-start">
                    <div class="col-12">
                        <div class="d-flex align-items-center justify-content-between mb-1" style="min-height: 21px;">
                            <label for="transaction-material" class="form-label fw-semibold mb-0">Material</label>
                            <span x-show="mat" class="extra-small font-mono text-secondary">
                                Stok: <strong class="text-body" x-text="mat ? mat.stock + ' ' + mat.unit : ''"></strong>
                            </span>
                        </div>
                        <div class="position-relative" @click.outside="matPickerOpen = false">
                            <button
                                id="transaction-material"
                                type="button"
                                class="form-select text-start d-flex align-items-center justify-content-between pe-3"
                                @click="matPickerOpen = !matPickerOpen"
                            >
                                <span class="text-truncate me-2" :class="mat ? 'text-body fw-semibold' : 'text-secondary'">
                                    <span x-show="mat" x-text="mat ? mat.name + ' · ' + (mat.code || ('MAT-' + mat.id)) : ''"></span>
                                    <span x-show="!mat">Pilih material</span>
                                </span>
                                <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor" class="text-secondary flex-shrink-0 transition-transform" :class="matPickerOpen ? 'rotate-180' : ''" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/>
                                </svg>
                            </button>

                            <div
                                x-show="matPickerOpen"
                                x-cloak
                                class="card shadow-lg border rounded-3 w-100 mt-1 bg-body overflow-hidden"
                                style="max-height: 380px;"
                            >
                                <div class="p-2 border-bottom position-relative">
                                    <input
                                        type="text"
                                        x-model="matSearch"
                                        class="form-control form-control-sm pe-4"
                                        placeholder="Cari material..."
                                        @click.stop
                                    />
                                    <span class="position-absolute top-50 end-0 translate-middle-y me-3 text-secondary pointer-events-none">
                                        <svg width="12" height="12" fill="currentColor"><use href="#i-search"/></svg>
                                    </span>
                                </div>
                                <div class="p-1.5" style="max-height: 300px; overflow-y: auto; -webkit-overflow-scrolling: touch;">
                                    @forelse ($materials as $m)
                                        <button
                                            type="button"
                                            class="dropdown-item rounded-2 py-2 px-3 text-start w-100"
                                            :class="$wire.material_id == {{ $m->id }} ? 'active bg-success text-white' : ''"
                                            x-show="matSearch === '' || @js(strtolower($m->name . ' ' . ($m->code ?? '') . ' ' . $m->unit)).includes(matSearch.toLowerCase())"
                                            wire:click="selectMaterial({{ $m->id }})"
                                            @click="matPickerOpen = false; matSearch = ''"
                                        >
                                            <div class="d-flex align-items-center justify-content-between gap-2">
                                                <span class="fw-semibold text-truncate">{{ $m->name }}</span>
                                                <span class="extra-small font-mono opacity-75 flex-shrink-0">{{ $m->code ?: 'MAT-'.$m->id }}</span>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between extra-small opacity-75 font-mono mt-0.5">
                                                <span>{{ $m->unit }}</span>
                                                <span>stok: {{ number_format((float) $m->stock, 2, ',', '.') }}</span>
                                            </div>
                                        </button>
                                    @empty
                                        <div class="px-3 py-4 text-center small text-secondary">Tidak ada material dengan stok tersedia.</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                        @error('material_id') <span class="text-danger small fw-semibold">{{ $message }}</span> @enderror
                    </div>

                    @if ($material_id)
                        <div class="col-12">
                            <label for="material-batch" class="form-label fw-semibold mb-1">Batch masuk</label>
                            <div class="position-relative" @click.outside="batchPickerOpen = false">
                                <button
                                    id="material-batch"
                                    type="button"
                                    class="form-select text-start d-flex align-items-center justify-content-between pe-3"
                                    aria-haspopup="listbox"
                                    :aria-expanded="batchPickerOpen"
                                    @click="batchPickerOpen = !batchPickerOpen"
                                >
                                    <span class="text-truncate me-2" :class="matBatch ? 'text-body fw-semibold' : 'text-secondary'">
                                        <span x-show="matBatch" x-text="matBatch ? matBatch.entry_code + ' · ' + matBatch.warehouse : ''"></span>
                                        <span x-show="!matBatch">Pilih batch masuk</span>
                                    </span>
                                    <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor" class="text-secondary flex-shrink-0 transition-transform" :class="batchPickerOpen ? 'rotate-180' : ''" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/>
                                    </svg>
                                </button>
                                <div x-show="batchPickerOpen" x-cloak class="card shadow-lg border rounded-3 w-100 mt-1 bg-body overflow-hidden" style="max-height: 380px;">
                                    <div class="p-1.5" role="listbox" aria-label="Batch material tersedia" style="max-height: 300px; overflow-y: auto; -webkit-overflow-scrolling: touch;">
                                        @forelse ($materialBatchMap as $batchId => $batch)
                                            <button
                                                type="button"
                                                role="option"
                                                aria-selected="{{ (string) $material_batch_id === (string) $batchId ? 'true' : 'false' }}"
                                                class="dropdown-item d-block rounded-2 py-2 px-3 text-start w-100"
                                                :class="$wire.material_batch_id == {{ $batchId }} ? 'active bg-success text-white' : ''"
                                                @disabled((float) $batch['remaining_quantity'] <= 0)
                                                wire:click="$set('material_batch_id', {{ $batchId }})"
                                                @click="batchPickerOpen = false"
                                            >
                                                <div class="d-flex align-items-center justify-content-between gap-2">
                                                    <span class="fw-semibold text-truncate">{{ $batch['entry_code'] }}</span>
                                                    <span class="extra-small font-mono opacity-75 flex-shrink-0">{{ $batch['warehouse'] }}</span>
                                                </div>
                                                <div class="d-flex align-items-center justify-content-between extra-small opacity-75 font-mono mt-0.5 gap-2">
                                                    <span class="text-truncate">{{ $batch['supplier'] }} · {{ $batch['received_at'] }}</span>
                                                    <span class="flex-shrink-0">Rp {{ number_format($batch['unit_price'], 0, ',', '.') }} · sisa {{ number_format($batch['remaining_quantity'], 2, ',', '.') }}</span>
                                                </div>
                                            </button>
                                        @empty
                                            <div class="px-3 py-3 text-center small text-secondary">Belum ada batch untuk material ini.</div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                            <p class="small text-secondary mb-0 mt-1">Batch dan stok gudang langsung berkurang saat alokasi disimpan.</p>
                            @error('material_batch_id') <span class="text-danger small fw-semibold">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    <div class="col-12">
                        <div class="d-flex align-items-center justify-content-between mb-1" style="min-height: 21px;">
                            <label for="material-quantity" class="form-label fw-semibold mb-0">Jumlah / rumah</label>
                            <span x-show="mat" class="extra-small font-mono text-secondary" :class="mat && mat.stock < totalQty ? 'text-danger fw-bold' : ''">
                                Maks: <span x-text="mat && matBatch ? (houseCount ? Math.floor(Math.min(mat.stock, matBatch.remaining_quantity) / houseCount * 100) / 100 : Math.min(mat.stock, matBatch.remaining_quantity)) : 0"></span>
                            </span>
                        </div>
                        <div class="input-group">
                            <input id="material-quantity" type="number" min="0.01" step="0.01" wire:model.live="material_quantity" class="form-control font-mono" placeholder="0.00" />
                            <span class="input-group-text font-mono text-secondary small" x-text="mat ? mat.unit : 'unit'"></span>
                        </div>
                        @error('material_quantity') <span class="text-danger small fw-semibold">{{ $message }}</span> @enderror
                        @if ($material_id && empty($materialBatchMap))
                            <div class="form-text text-danger">Belum ada penerimaan stok tercatat untuk material ini.</div>
                        @endif
                    </div>

                    <div class="col-12">
                        <label for="material-notes" class="form-label fw-semibold">Peruntukan <span class="text-danger" aria-hidden="true">*</span></label>
                        <div class="position-relative" @click.outside="matNotePickerOpen = false">
                            <div class="position-relative d-flex align-items-center">
                                <input
                                    id="material-notes"
                                    type="text"
                                    wire:model="material_notes"
                                    class="form-control pe-5"
                                    required aria-required="true"
                                    placeholder="Pilih atau ketik peruntukkan (mis. Pemasangan Pondasi, Pekerjaan Dinding)..."
                                    @focus="matNotePickerOpen = true"
                                    @input="matNotePickerOpen = true"
                                />
                                <button
                                    type="button"
                                    class="btn btn-link text-secondary text-decoration-none position-absolute end-0 me-2 p-1 d-flex align-items-center"
                                    @click="matNotePickerOpen = !matNotePickerOpen"
                                    aria-label="Tampilkan pilihan peruntukkan"
                                >
                                    <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor" class="transition-transform" :class="matNotePickerOpen ? 'rotate-180' : ''" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/>
                                    </svg>
                                </button>
                            </div>

                            <div
                                x-show="matNotePickerOpen"
                                x-cloak
                                class="card shadow-lg border rounded-3 position-absolute w-100 mt-1 bg-body overflow-hidden"
                                style="max-height: 220px; z-index: 1050;"
                            >
                                <div class="p-1.5" style="max-height: 210px; overflow-y: auto; -webkit-overflow-scrolling: touch;">
                                    @foreach ($noteOptions as $option)
                                        <button
                                            type="button"
                                            class="dropdown-item rounded-2 py-2 px-3 text-start w-100 d-flex align-items-center justify-content-between"
                                            data-option="{{ $option }}"
                                            x-show="!$wire.material_notes || $el.dataset.option.toLowerCase().includes($wire.material_notes.toLowerCase())"
                                            :class="$wire.material_notes === $el.dataset.option ? 'active bg-success text-white' : ''"
                                            @click="$wire.material_notes = $event.currentTarget.dataset.option; matNotePickerOpen = false"
                                        >
                                            <span class="fw-medium text-truncate">{{ $option }}</span>
                                        </button>
                                    @endforeach
                                    <div
                                        x-show="$wire.material_notes && !peruntukkanOpts.some(o => o.toLowerCase() === $wire.material_notes.toLowerCase())"
                                        class="px-3 py-2 extra-small text-secondary fst-italic border-top"
                                    >
                                        Gunakan "<span class="fw-bold text-body" x-text="$wire.material_notes"></span>" sebagai peruntukkan baru
                                    </div>
                                </div>
                            </div>
                        </div>
                        @error('material_notes') <span class="text-danger small fw-semibold">{{ $message }}</span> @enderror
                    </div>

                    <div class="col-12">
                        <label for="material-taken-by" class="form-label fw-semibold">Pengambil <span class="text-secondary fw-normal">(opsional)</span></label>
                        <input
                            id="material-taken-by"
                            type="text"
                            wire:model="material_taken_by"
                            list="material-taker-suggestions"
                            class="form-control"
                            maxlength="120"
                            autocomplete="off"
                            placeholder="Pilih atau ketik nama pengambil"
                            @if (empty($pengambilOptions)) aria-describedby="material-taker-help" @endif
                        />
                        <datalist id="material-taker-suggestions">
                            @foreach ($pengambilOptions as $pengambil)
                                <option value="{{ $pengambil }}"></option>
                            @endforeach
                        </datalist>
                        @if (empty($pengambilOptions))
                            <div id="material-taker-help" class="form-text">Belum ada riwayat nama pengambil. Ketik nama untuk mencatatnya.</div>
                        @endif
                        @error('material_taken_by') <span class="text-danger small fw-semibold" role="alert">{{ $message }}</span> @enderror
                    </div>

                </div>
            </div>

            <div x-show="activeTab === 'allocation' && allocationType === 'material' && mat && totalQty > parseFloat(mat.stock)" x-cloak class="alert alert-danger small mt-3 mb-0" role="alert">
                Kebutuhan <span x-text="Number(totalQty.toFixed(2))"></span> melebihi stok. Kurangi jumlah per rumah atau rumah tujuan.
            </div>

            <!-- ---- Tool checkout ---- -->
            <div x-show="activeTab === 'allocation' && allocationType === 'tool'" x-cloak>
                <div class="transaction-step-heading mb-4">
                    <span class="transaction-step-index" aria-hidden="true">02</span>
                    <div>
                        <h2 class="h6 fw-bold font-outfit text-body mb-1">Detail peminjaman alat</h2>
                        <p class="small text-secondary mb-0">Jumlah di bawah diterapkan ke setiap rumah yang dipilih.</p>
                    </div>
                </div>
                <div class="row g-3 align-items-start">
                    <div class="col-12">
                        <div class="d-flex align-items-center justify-content-between mb-1" style="min-height: 21px;">
                            <label for="transaction-tool" class="form-label fw-semibold mb-0">Alat kerja</label>
                            <span x-show="tool" class="extra-small font-mono text-secondary">
                                Tersedia: <strong class="text-body" x-text="tool ? tool.available_qty + ' unit' : ''"></strong>
                            </span>
                        </div>
                        <div class="position-relative" @click.outside="toolPickerOpen = false">
                            <button
                                id="transaction-tool"
                                type="button"
                                class="form-select text-start d-flex align-items-center justify-content-between pe-3"
                                @click="toolPickerOpen = !toolPickerOpen"
                            >
                                <span class="text-truncate me-2" :class="tool ? 'text-body fw-semibold' : 'text-secondary'">
                                    <span x-show="tool" x-text="tool ? tool.name + ' (' + tool.code + ')' : ''"></span>
                                    <span x-show="!tool">Pilih alat</span>
                                </span>
                                <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor" class="text-secondary flex-shrink-0 transition-transform" :class="toolPickerOpen ? 'rotate-180' : ''" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/>
                                </svg>
                            </button>

                            <div
                                x-show="toolPickerOpen"
                                x-cloak
                                class="card shadow-lg border rounded-3 w-100 mt-1 bg-body overflow-hidden"
                                style="max-height: 380px;"
                            >
                                <div class="p-2 border-bottom position-relative">
                                    <input
                                        type="text"
                                        x-model="toolSearch"
                                        class="form-control form-control-sm pe-4"
                                        placeholder="Cari alat..."
                                        @click.stop
                                    />
                                    <span class="position-absolute top-50 end-0 translate-middle-y me-3 text-secondary pointer-events-none">
                                        <svg width="12" height="12" fill="currentColor"><use href="#i-search"/></svg>
                                    </span>
                                </div>
                                <div class="p-1.5" style="max-height: 300px; overflow-y: auto; -webkit-overflow-scrolling: touch;">
                                    @forelse ($tools as $t)
                                        <button
                                            type="button"
                                            class="dropdown-item rounded-2 py-2 px-3 text-start w-100"
                                            :class="$wire.tool_id == {{ $t->id }} ? 'active bg-success text-white' : ''"
                                            x-show="toolSearch === '' || @js(strtolower($t->name . ' ' . $t->code . ' ' . ($t->warehouse?->name ?? ''))).includes(toolSearch.toLowerCase())"
                                            @click="$wire.tool_id = '{{ $t->id }}'; $wire.tool_warehouse_id = '{{ $t->warehouseBalances->first()?->warehouse_id ?? $t->warehouse_id }}'; toolPickerOpen = false; toolSearch = ''"
                                        >
                                            <div class="d-flex align-items-center justify-content-between gap-2">
                                                <span class="fw-semibold text-truncate">{{ $t->name }}</span>
                                                <span class="extra-small font-mono opacity-75 flex-shrink-0">{{ $t->code }}</span>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between extra-small opacity-75 font-mono mt-0.5">
                                                <span>{{ $t->warehouse?->name ?? 'Belum ada gudang' }}</span>
                                                <span>sisa: {{ $t->available_qty }}</span>
                                            </div>
                                        </button>
                                    @empty
                                        <div class="px-3 py-4 text-center small text-secondary">Tidak ada alat yang tersedia.</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                        @error('tool_id') <span class="text-danger small fw-semibold">{{ $message }}</span> @enderror
                    </div>

                    @if ($toolWarehouseOptions->isNotEmpty())
                        <div class="col-12">
                            <label for="tool-warehouse" class="form-label fw-semibold mb-1">Gudang asal</label>
                            <div class="position-relative" @click.outside="warehousePickerOpen = false">
                                <button
                                    id="tool-warehouse"
                                    type="button"
                                    class="form-select text-start d-flex align-items-center justify-content-between pe-3"
                                    aria-haspopup="listbox"
                                    :aria-expanded="warehousePickerOpen"
                                    @click="warehousePickerOpen = !warehousePickerOpen"
                                >
                                    <span class="text-truncate me-2 text-body fw-semibold">
                                        @php($selectedToolWarehouse = $toolWarehouseOptions->firstWhere('warehouse_id', (int) $tool_warehouse_id))
                                        {{ $selectedToolWarehouse?->warehouse?->name ?? 'Pilih gudang asal' }}
                                    </span>
                                    <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor" class="text-secondary flex-shrink-0 transition-transform" :class="warehousePickerOpen ? 'rotate-180' : ''" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/>
                                    </svg>
                                </button>
                                <div x-show="warehousePickerOpen" x-cloak class="card shadow-lg border rounded-3 w-100 mt-1 bg-body overflow-hidden" style="max-height: 380px;">
                                    <div class="p-1.5" role="listbox" aria-label="Gudang alat tersedia" style="max-height: 300px; overflow-y: auto; -webkit-overflow-scrolling: touch;">
                                        @foreach ($toolWarehouseOptions as $balance)
                                            <button
                                                type="button"
                                                role="option"
                                                aria-selected="{{ (int) $tool_warehouse_id === (int) $balance->warehouse_id ? 'true' : 'false' }}"
                                                class="dropdown-item d-block rounded-2 py-2 px-3 text-start w-100"
                                                :class="$wire.tool_warehouse_id == {{ $balance->warehouse_id }} ? 'active bg-success text-white' : ''"
                                                wire:click="$set('tool_warehouse_id', {{ $balance->warehouse_id }})"
                                                @click="warehousePickerOpen = false"
                                            >
                                                <div class="d-flex align-items-center justify-content-between gap-2">
                                                    <span class="fw-semibold text-truncate">{{ $balance->warehouse?->name ?? 'Gudang tidak tersedia' }}</span>
                                                    <span class="extra-small font-mono opacity-75 flex-shrink-0">{{ $balance->allocation_available_qty }} unit bebas</span>
                                                </div>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <p class="small text-secondary mb-0 mt-1">Pilih lokasi fisik unit yang akan dipinjam.</p>
                            @error('tool_warehouse_id') <span class="text-danger small fw-semibold">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    <div class="col-12">
                        <div class="d-flex align-items-center justify-content-between mb-1" style="min-height: 21px;">
                            <label for="tool-quantity" class="form-label fw-semibold mb-0">Jumlah / rumah</label>
                            <span x-show="tool" class="extra-small font-mono text-secondary" :class="tool && tool.available_qty < totalTools ? 'text-danger fw-bold' : ''">
                                Maks: <span x-text="tool ? (houseCount ? Math.floor(tool.available_qty / houseCount) : tool.available_qty) : 0"></span>
                            </span>
                        </div>
                        <div class="input-group">
                            <input id="tool-quantity" type="number" min="1" step="1" wire:model.live="tool_quantity" class="form-control font-mono" />
                            <span class="input-group-text font-mono text-secondary small">unit</span>
                        </div>
                        @error('tool_quantity') <span class="text-danger small fw-semibold">{{ $message }}</span> @enderror
                    </div>

                    <div class="col-12">
                        <label for="tool-notes" class="form-label fw-semibold">Peruntukan <span class="text-danger" aria-hidden="true">*</span></label>
                        <div class="position-relative" @click.outside="toolNotePickerOpen = false">
                            <div class="position-relative d-flex align-items-center">
                                <input
                                    id="tool-notes"
                                    type="text"
                                    wire:model="tool_notes"
                                    class="form-control pe-5"
                                    required aria-required="true"
                                    placeholder="Pilih atau ketik peruntukkan (mis. Pemasangan Pondasi, Pekerjaan Dinding)..."
                                    @focus="toolNotePickerOpen = true"
                                    @input="toolNotePickerOpen = true"
                                />
                                <button
                                    type="button"
                                    class="btn btn-link text-secondary text-decoration-none position-absolute end-0 me-2 p-1 d-flex align-items-center"
                                    @click="toolNotePickerOpen = !toolNotePickerOpen"
                                    aria-label="Tampilkan pilihan peruntukkan"
                                >
                                    <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor" class="transition-transform" :class="toolNotePickerOpen ? 'rotate-180' : ''" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/>
                                    </svg>
                                </button>
                            </div>

                            <div
                                x-show="toolNotePickerOpen"
                                x-cloak
                                class="card shadow-lg border rounded-3 position-absolute w-100 mt-1 bg-body overflow-hidden"
                                style="max-height: 220px; z-index: 1050;"
                            >
                                <div class="p-1.5" style="max-height: 210px; overflow-y: auto; -webkit-overflow-scrolling: touch;">
                                    @foreach ($noteOptions as $option)
                                        <button
                                            type="button"
                                            class="dropdown-item rounded-2 py-2 px-3 text-start w-100 d-flex align-items-center justify-content-between"
                                            data-option="{{ $option }}"
                                            x-show="!$wire.tool_notes || $el.dataset.option.toLowerCase().includes($wire.tool_notes.toLowerCase())"
                                            :class="$wire.tool_notes === $el.dataset.option ? 'active bg-success text-white' : ''"
                                            @click="$wire.tool_notes = $event.currentTarget.dataset.option; toolNotePickerOpen = false"
                                        >
                                            <span class="fw-medium text-truncate">{{ $option }}</span>
                                        </button>
                                    @endforeach
                                    <div
                                        x-show="$wire.tool_notes && !peruntukkanOpts.some(o => o.toLowerCase() === $wire.tool_notes.toLowerCase())"
                                        class="px-3 py-2 extra-small text-secondary fst-italic border-top"
                                    >
                                        Gunakan "<span class="fw-bold text-body" x-text="$wire.tool_notes"></span>" sebagai peruntukkan baru
                                    </div>
                                </div>
                            </div>
                        </div>
                        @error('tool_notes') <span class="text-danger small fw-semibold">{{ $message }}</span> @enderror
                    </div>

                </div>

                <div x-show="toolShortfall < 0" x-cloak class="alert alert-danger py-2 small fw-semibold mt-3 mb-0">
                    Jumlah melebihi stok tersedia sebanyak <span x-text="Math.abs(toolShortfall)"></span> unit.
                </div>
            </div>

            <!-- ---- Tool return ---- -->
            <div x-show="activeTab === 'return'" x-cloak
                wire:key="return-list-{{ $activeUsages->pluck('id')->join('-') }}"
                x-data="{
                    returnSearch: '',
                    activeUsages: @js($activeUsages->map(fn($u) => ['text' => strtolower($u->tool->name . ' ' . $u->tool->code . ' ' . $u->house->name)])->values()),
                    get filteredReturnCount() { return this.activeUsages.filter(u => u.text.includes(this.returnSearch.toLowerCase())).length }
                }">
                <div class="transaction-step-head mb-4">
                    <div class="transaction-step-heading">
                        <span class="transaction-step-index" aria-hidden="true">02</span>
                        <div>
                            <h2 class="h6 fw-bold font-outfit text-body mb-1">Alat yang sedang dipinjam</h2>
                            <p class="small text-secondary mb-0">Pilih alat, catat kondisinya, lalu tentukan gudang penerima.</p>
                        </div>
                    </div>
                    @if (!$activeUsages->isEmpty())
                        <span class="transaction-count font-mono extra-small">
                            {{ $activeUsages->count() }} peminjaman aktif
                        </span>
                    @endif
                </div>

                @if (empty($house_ids))
                    <div class="text-center py-5">
                        <p class="fw-semibold text-body mb-1">Pilih unit rumah terlebih dahulu</p>
                        <p class="small text-secondary mb-0">Klik Pilih rumah, lalu pilih rumah asal alat yang dikembalikan.</p>
                    </div>
                @elseif ($activeUsages->isEmpty())
                    <div class="text-center py-5">
                        <p class="fw-semibold text-success mb-1">Semua alat sudah dikembalikan</p>
                        <p class="small text-secondary mb-0">Tidak ada peminjaman aktif pada unit yang dipilih.</p>
                    </div>
                @else
                    @error('returnSelections') <div class="alert alert-danger py-2 small fw-semibold">{{ $message }}</div> @enderror

                    <!-- Quick search bar -->
                    <div class="mb-3 position-relative">
                        <input
                            type="text"
                            x-model="returnSearch"
                            class="form-control pe-5"
                            placeholder="Cari nama alat, kode, atau unit rumah (mis. Molen, AB-001, A-01)..."
                        />
                        <span class="position-absolute top-50 end-0 translate-middle-y me-3 text-secondary pointer-events-none d-flex align-items-center">
                            <svg width="15" height="15" fill="currentColor" aria-hidden="true"><use href="#i-search"/></svg>
                        </span>
                    </div>

                    <div class="vstack gap-2">
                        @foreach ($activeUsages as $usage)
                            <div
                                class="return-record"
                                wire:key="ret-{{ $usage->id }}"
                                :class="{ 'is-selected': $wire.returnSelections[{{ $usage->id }}]?.selected }"
                                x-show="returnSearch === '' || @js(strtolower($usage->tool->name . ' ' . $usage->tool->code . ' ' . $usage->house->name)).includes(returnSearch.toLowerCase())"
                            >
                                <div class="form-check d-flex gap-2 mb-0">
                                    <input type="checkbox" wire:model.live="returnSelections.{{ $usage->id }}.selected" class="form-check-input mt-1 flex-shrink-0" id="rc-{{ $usage->id }}" />
                                    <label class="form-check-label flex-grow-1" for="rc-{{ $usage->id }}">
                                        <div class="d-flex flex-wrap justify-content-between gap-2">
                                            <div>
                                                <div class="fw-bold text-body">{{ $usage->tool->name }}</div>
                                                <div class="extra-small text-secondary font-mono">
                                                    {{ $usage->house->name }} &middot; {{ $usage->tool->code }} &middot; {{ $usage->checkout_date?->format('d/m/Y') ?? '-' }}
                                                </div>
                                            </div>
                                            <span class="badge bg-secondary-subtle text-secondary rounded-pill font-mono align-self-start">{{ $usage->quantity }} unit</span>
                                        </div>
                                    </label>
                                </div>

                                <div x-show="$wire.returnSelections[{{ $usage->id }}]?.selected" x-cloak class="mt-3 pt-3 border-top">
                                    <div class="row g-2">
                                        <div class="col-4">
                                            <label for="return-normal-{{ $usage->id }}" class="small fw-semibold">Baik</label>
                                            <input id="return-normal-{{ $usage->id }}" type="number" min="0" max="{{ $usage->quantity }}" wire:model.live="returnSelections.{{ $usage->id }}.qty_normal" class="form-control form-control-sm font-mono" />
                                        </div>
                                        <div class="col-4">
                                            <label for="return-broken-{{ $usage->id }}" class="small fw-semibold">Rusak</label>
                                            <input id="return-broken-{{ $usage->id }}" type="number" min="0" max="{{ $usage->quantity }}" wire:model.live="returnSelections.{{ $usage->id }}.qty_broken" class="form-control form-control-sm font-mono" />
                                        </div>
                                        <div class="col-4">
                                            <label for="return-lost-{{ $usage->id }}" class="small fw-semibold">Hilang</label>
                                            <input id="return-lost-{{ $usage->id }}" type="number" min="0" max="{{ $usage->quantity }}" wire:model.live="returnSelections.{{ $usage->id }}.qty_lost" class="form-control form-control-sm font-mono" />
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <label for="return-warehouse-{{ $usage->id }}" class="small fw-semibold">Gudang penerima untuk unit baik/rusak</label>
                                        <select id="return-warehouse-{{ $usage->id }}" wire:model.live="returnSelections.{{ $usage->id }}.receiving_warehouse_id" class="form-select form-select-sm">
                                            <option value="">Pilih gudang</option>
                                            @foreach($warehouses as $warehouse)
                                                <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mt-2" x-show="(parseInt($wire.returnSelections[{{ $usage->id }}]?.qty_broken) || 0) > 0 || (parseInt($wire.returnSelections[{{ $usage->id }}]?.qty_lost) || 0) > 0" x-cloak>
                                        <input type="text" wire:model.live="returnSelections.{{ $usage->id }}.notes" class="form-control form-control-sm" placeholder="Catatan kerusakan / kehilangan..." />
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Empty search state -->
                    <div x-show="filteredReturnCount === 0" x-cloak class="text-center py-5">
                        <p class="fw-semibold text-body mb-1">Tidak ada alat ditemukan</p>
                        <p class="small text-secondary mb-0">Tidak ada peminjaman alat yang cocok dengan kata kunci "<span class="fw-bold" x-text="returnSearch"></span>".</p>
                    </div>
                @endif
            </div>
        </div>
    </div> <!-- /transaction-form -->

    <!-- Transaction summary -->
    <aside x-show="activeTab === 'rental'" x-cloak class="transaction-summary" aria-labelledby="rental-summary-title">
        <div class="transaction-summary__sticky transaction-slip p-4">
            <h2 id="rental-summary-title" class="h6 fw-bold mb-3">Ringkasan sewa</h2>
            <p class="small text-secondary mb-3">Pilih rumah dalam satu cluster. Satu biaya sewa dicatat untuk semua rumah terpilih.</p>
            <div class="d-flex justify-content-between gap-3 small border-top pt-3"><span>Rumah tujuan</span><strong class="text-end">{{ $selectedHouses->isEmpty() ? 'Belum dipilih' : $selectedHouses->take(3)->pluck('name')->join(', ').($selectedHouses->count() > 3 ? ' +'.($selectedHouses->count() - 3).' lainnya' : '') }}</strong></div>
            <div class="d-flex justify-content-between gap-3 small border-top pt-3 mt-3"><span>Alat vendor</span><strong>{{ $rental_description ?: 'Belum diisi' }}</strong></div>
            <div class="d-flex justify-content-between gap-3 small border-top pt-3 mt-3"><span>Biaya sewa</span><strong>Rp {{ number_format((float) $rental_amount, 0, ',', '.') }}</strong></div>
            <p class="small text-secondary border-top pt-3 mt-3 mb-0">Tidak mengubah stok alat milik perusahaan. Perpanjangan dan off-hire dicatat di biaya cluster.</p>
        </div>
    </aside>
    <aside x-show="activeTab === 'vendor-service'" x-cloak class="transaction-summary" aria-labelledby="vendor-service-summary-title">
        <div class="transaction-summary__sticky transaction-slip p-4">
            <h2 id="vendor-service-summary-title" class="h6 fw-bold mb-3">Ringkasan jasa vendor</h2>
            <p class="small text-secondary mb-3">Biaya dicatat satu kali pada target pekerjaan yang dipilih.</p>
            <div class="d-flex justify-content-between gap-3 small border-top pt-3">
                <span>Target</span>
                <strong class="text-end">{{ $vendor_service_target === 'cluster' ? ($selectedVendorCluster?->name ?? 'Pilih cluster') : ($selectedHouses->first()?->name ?? 'Pilih satu rumah') }}</strong>
            </div>
            <div class="d-flex justify-content-between gap-3 small border-top pt-3 mt-3"><span>Jasa</span><strong class="text-end">{{ $vendor_service_description ?: 'Belum diisi' }}</strong></div>
            <div class="d-flex justify-content-between gap-3 small border-top pt-3 mt-3"><span>Vendor</span><strong class="text-end">{{ $vendor_service_vendor ?: 'Belum diisi' }}</strong></div>
            <div class="d-flex justify-content-between gap-3 small border-top pt-3 mt-3"><span>Total biaya</span><strong class="font-mono">Rp {{ number_format((float) $vendor_service_amount, 0, ',', '.') }}</strong></div>
            <p class="small text-secondary border-top pt-3 mt-3 mb-0">Tidak mengubah stok material atau alat.</p>
        </div>
    </aside>
    <aside x-show="activeTab !== 'rental' && activeTab !== 'vendor-service'" class="transaction-summary" aria-labelledby="transaction-summary-title">
        <div class="transaction-summary__sticky">
            <div class="transaction-slip">
                <div class="transaction-slip__body">
                    <div class="transaction-slip__header">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Zm3 5h6M9 12h6" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <div>
                            <h2 id="transaction-summary-title" class="h6 fw-bold text-body mb-1">Dampak alokasi</h2>
                            <p class="small text-secondary mb-0" x-text="activeTab === 'return' ? 'Periksa jumlah dan kondisi alat.' : 'Jumlah yang sama untuk setiap rumah.'"></p>
                        </div>
                    </div>

                    <!-- Selected Item Info Section -->
                    <div class="pb-3 mb-0 border-bottom border-dashed extra-small" style="border-color: var(--bs-border-color) !important;">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="text-secondary">Item</span>
                            <span class="fw-bold text-body text-end ms-2" x-text="activeTab === 'allocation' ? (allocationType === 'material' ? (mat ? mat.name : 'Belum dipilih') : (tool ? tool.name : 'Belum dipilih')) : 'Pengembalian alat'"></span>
                        </div>
                        <div x-show.important="activeTab === 'allocation' && allocationType === 'material' && mat" class="d-flex align-items-center justify-content-between mb-1 text-secondary">
                                <span>Harga satuan</span>
                                <span class="text-body fw-semibold" x-text="matBatch ? rp(matBatch.unit_price) + ' / ' + mat.unit : 'Belum tersedia'"></span>
                        </div>
                        <div x-show.important="activeTab === 'allocation' && allocationType === 'material' && mat" class="d-flex align-items-center justify-content-between mb-1 text-secondary">
                                <span>Batch</span>
                                <span class="text-body fw-semibold" x-text="matBatch ? matBatch.entry_code : ''"></span>
                        </div>
                        <div x-show.important="activeTab === 'allocation' && allocationType === 'material' && mat" class="d-flex align-items-center justify-content-between mb-1 text-secondary">
                                <span>Supplier</span>
                                <span class="text-body fw-semibold text-end ms-2" x-text="matBatch ? matBatch.supplier : ''"></span>
                        </div>
                        <div x-show.important="activeTab === 'allocation' && allocationType === 'material' && mat" class="d-flex align-items-center justify-content-between mb-1 text-secondary">
                                <span>Gudang asal</span>
                                <span class="text-body fw-semibold text-end ms-2" x-text="matBatch ? matBatch.warehouse : ''"></span>
                        </div>
                        <div x-show.important="activeTab === 'allocation' && allocationType === 'material' && mat" class="d-flex align-items-center justify-content-between text-secondary">
                                <span>Sisa batch</span>
                                <span :class="matBatch && (matBatch.remaining_quantity - totalQty) < 0 ? 'text-danger fw-bold' : 'text-body fw-bold'" x-text="matBatch ? Number((matBatch.remaining_quantity - totalQty).toFixed(2)) + ' ' + mat.unit : ''"></span>
                        </div>
                        <div x-show.important="activeTab === 'allocation' && allocationType === 'tool' && tool" class="d-flex align-items-center justify-content-between mb-1 text-secondary">
                                <span>Kode alat</span>
                                <span class="text-body fw-semibold" x-text="tool ? tool.code : ''"></span>
                        </div>
                        <div x-show.important="activeTab === 'allocation' && allocationType === 'tool' && tool" class="d-flex align-items-center justify-content-between mb-1 text-secondary">
                                <span>Gudang asal</span>
                                <span class="text-body fw-semibold text-end ms-2" x-text="tool ? tool.warehouse : ''"></span>
                        </div>
                        <div x-show.important="activeTab === 'allocation' && allocationType === 'tool' && tool" class="d-flex align-items-center justify-content-between text-secondary">
                                <span>Sisa di gudang</span>
                                <span :class="tool && toolShortfall < 0 ? 'text-danger fw-bold' : 'text-success fw-bold'" x-text="tool ? (tool.available_qty - totalTools) + ' unit' : ''"></span>
                        </div>
                    </div>

                    <!-- Column Header -->
                    <div x-show.important="activeTab !== 'return'" class="d-flex align-items-center justify-content-between small text-secondary fw-semibold py-2 mb-3 border-bottom">
                        <span>Alokasi per rumah</span>
                        <span x-text="activeTab === 'allocation' && allocationType === 'material' ? 'Biaya' : 'Kode'"></span>
                    </div>

                    <!-- Itemized Stacked List (The Receipt Body) -->
                    <div class="mb-3">
                        @if ($selectedHouses->isEmpty())
                            <div class="py-2 text-secondary small">
                                Pilih rumah untuk melihat ringkasan.
                            </div>
                        @else
                            <!-- MATERIAL STACKED LIST -->
                            <div x-show="activeTab === 'allocation' && allocationType === 'material'" class="vstack gap-2" style="overflow-y: auto; overscroll-behavior: contain;" :style="{ maxHeight: showAllSummaryHouses ? 'none' : '220px' }">
                                @foreach ($selectedHouses as $h)
                                    <div class="d-flex align-items-center justify-content-between extra-small" @if ($loop->index >= 5) x-show="showAllSummaryHouses" x-cloak @endif>
                                        <div class="d-flex align-items-center gap-2 text-truncate me-2">
                                            <span class="fw-bold text-body" x-text="matQty + (mat ? (' ' + mat.unit) : 'x')"></span>
                                            <span class="text-body fw-semibold text-truncate">{{ $h->name }}</span>
                                        </div>
                                        <div class="text-end fw-bold text-body flex-shrink-0" x-text="matBatch ? rp(matQty * parseFloat(matBatch.unit_price)) : '-'"></div>
                                    </div>
                                @endforeach
                            </div>

                            <!-- TOOL STACKED LIST -->
                            <div x-show="activeTab === 'allocation' && allocationType === 'tool'" class="vstack gap-2" style="overflow-y: auto; overscroll-behavior: contain;" :style="{ maxHeight: showAllSummaryHouses ? 'none' : '220px' }" x-cloak>
                                @foreach ($selectedHouses as $h)
                                    <div class="d-flex align-items-center justify-content-between extra-small" @if ($loop->index >= 5) x-show="showAllSummaryHouses" x-cloak @endif>
                                        <div class="d-flex align-items-center gap-2 text-truncate me-2">
                                            <span class="fw-bold text-body" x-text="toolQty + ' unit'"></span>
                                            <span class="text-body fw-semibold text-truncate">{{ $h->name }}</span>
                                        </div>
                                        <div class="text-end text-secondary fw-semibold flex-shrink-0" x-text="tool ? tool.code : '-'"></div>
                                    </div>
                                @endforeach
                            </div>

                            <!-- RETURN STACKED LIST -->
                            <div x-show="activeTab === 'return'" class="text-center py-3 extra-small" x-cloak>
                                <div class="fs-4 fw-black text-success" x-text="returnCount"></div>
                                <div class="text-secondary mt-1">Alokasi alat dikembalikan</div>
                            </div>
                            @if ($selectedHouses->count() > 5)
                                <button x-show="activeTab !== 'return'" type="button" class="btn btn-link btn-sm px-0" @click="showAllSummaryHouses = !showAllSummaryHouses" :aria-expanded="showAllSummaryHouses.toString()" x-text="showAllSummaryHouses ? 'Sembunyikan' : 'Lihat {{ $selectedHouses->count() - 5 }} rumah lainnya'" x-cloak></button>
                            @endif
                        @endif
                    </div>

                    <!-- Receipt Summary Calculations -->
                    <div class="transaction-slip__totals extra-small" aria-live="polite">
                        <div class="d-flex align-items-center justify-content-between text-secondary mb-1">
                            <span x-text="activeTab === 'return' ? 'Rumah asal' : 'Rumah tujuan'"></span>
                            <span class="fw-bold text-body" x-text="houseCount + ' rumah'"></span>
                        </div>
                        <template x-if="activeTab === 'allocation' && allocationType === 'material'">
                            <div class="d-flex align-items-center justify-content-between text-secondary mb-2">
                                <span>Total material</span>
                                <span class="fw-bold text-body" x-text="Number(totalQty.toFixed(2)) + (mat ? (' ' + mat.unit) : '')"></span>
                            </div>
                        </template>
                        <template x-if="activeTab === 'allocation' && allocationType === 'tool'">
                            <div class="d-flex align-items-center justify-content-between text-secondary mb-2">
                                <span>Jumlah per rumah</span>
                                <span class="fw-bold text-body" x-text="toolQty + ' unit'"></span>
                            </div>
                        </template>

                        <!-- Total Amount Line -->
                        <div class="transaction-grand-total d-flex align-items-baseline justify-content-between">
                            <span class="fw-bold text-body small" x-text="activeTab === 'allocation' ? (allocationType === 'material' ? 'Estimasi biaya' : 'Unit dialokasikan') : 'Total kembali'"></span>
                            <template x-if="activeTab === 'allocation' && allocationType === 'material'">
                                <span class="fs-4 fw-black text-success lh-1" x-text="matReady ? rp(totalCost) : 'Rp 0'"></span>
                            </template>
                            <template x-if="activeTab === 'allocation' && allocationType === 'tool'">
                                <span class="fs-4 fw-black lh-1" :class="toolShortfall < 0 ? 'text-danger' : 'text-success'" x-text="toolReady ? totalTools + ' Unit' : '0 Unit'"></span>
                            </template>
                            <template x-if="activeTab === 'return'">
                                <span class="fs-4 fw-black text-body lh-1" x-text="returnQty + ' unit'"></span>
                            </template>
                        </div>
                    </div>

                    <!-- Receipt Actions -->
                    <div class="transaction-actions">
                        <template x-if="activeTab === 'allocation' && allocationType === 'material'">
                            <div class="d-flex gap-2 w-100">
                                <button type="button" wire:click="resetMaterialForm" class="btn log-row-action log-row-action--quiet">Bersihkan</button>
                                <button type="button" wire:click="showMaterialConfirmationModal" wire:loading.attr="disabled" class="btn log-row-action log-row-action--edit fw-semibold flex-grow-1" :disabled="!matReady || totalQty > Math.min(parseFloat(mat.stock), parseFloat(matBatch?.remaining_quantity ?? 0))">Tinjau alokasi</button>
                            </div>
                        </template>
                        <template x-if="activeTab === 'allocation' && allocationType === 'tool'">
                            <div class="d-flex gap-2 w-100">
                                <button type="button" wire:click="resetToolForm" class="btn log-row-action log-row-action--quiet">Bersihkan</button>
                                <button type="button" wire:click="showToolConfirmationModal" wire:loading.attr="disabled" class="btn log-row-action log-row-action--edit fw-semibold flex-grow-1" :disabled="!toolReady || toolShortfall < 0">Tinjau peminjaman</button>
                            </div>
                        </template>
                        <template x-if="activeTab === 'return'">
                            <div class="d-flex gap-2 w-100">
                                <button type="button" wire:click="resetReturnForm" class="btn log-row-action log-row-action--quiet">Bersihkan</button>
                                <button type="button" wire:click="showReturnConfirmationModal" wire:loading.attr="disabled" class="btn log-row-action log-row-action--edit fw-semibold flex-grow-1" :disabled="returnCount === 0 || returnQty <= 0">Tinjau pengembalian</button>
                            </div>
                        </template>
                    </div>

                </div>

            </div>
        </div>
    </aside> <!-- /transaction-summary -->
</div> <!-- /transaction-grid -->

@teleport('body')
<div>
<div class="house-dialog-backdrop" x-show="housePickerOpen" x-cloak @click="housePickerOpen = false" wire:click="closeHousePicker" aria-hidden="true"></div>
<dialog id="house-dialog" open x-show="housePickerOpen" x-cloak class="house-dialog" aria-labelledby="house-dialog-title" aria-modal="true">
    <div class="house-dialog-header">
        <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
            <h2 id="house-dialog-title" class="h5 fw-bold mb-0">{{ $activeTab === 'return' ? 'Pilih rumah asal alat' : ($activeTab === 'vendor-service' && $vendor_service_target === 'house' ? 'Pilih satu rumah untuk jasa vendor' : 'Pilih rumah tujuan') }}</h2>
            <button type="button" class="btn-close" aria-label="Tutup pilihan rumah" @click="housePickerOpen = false" wire:click="closeHousePicker"></button>
        </div>
        <div class="row g-2">
            <div class="col-7">
                <label for="house-search" class="form-label small fw-semibold">Cari rumah</label>
                <input id="house-search" autofocus type="search" wire:model.live.debounce.250ms="houseSearch" class="form-control" placeholder="Nama, kode, atau tipe rumah" />
            </div>
            <div class="col-5">
                <label for="house-cluster" class="form-label small fw-semibold">Cluster</label>
                <select id="house-cluster" wire:model.live="houseCluster" class="form-select">
                    <option value="all">Semua cluster</option>
                    @foreach ($houseClusters as $cluster)
                        <option value="{{ $cluster['id'] }}">{{ $cluster['name'] }} ({{ $cluster['count'] }})</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-2">
            <label class="d-flex align-items-center gap-2 small house-filter-toggle">
                <input type="checkbox" wire:model.live="selectedHousesOnly" class="form-check-input m-0" />
                Hanya dipilih ({{ count($house_ids) }})
            </label>
            @if ($activeTab === 'vendor-service' && $vendor_service_target === 'house')
                <span class="small text-secondary">Pilih satu rumah</span>
            @else
                <button type="button" wire:click="selectVisibleHouses" wire:loading.attr="disabled" class="btn btn-link btn-sm" @disabled($houseResults->isEmpty())>Pilih {{ $houseResults->count() }} di halaman ini</button>
            @endif
        </div>
        <span wire:loading.delay wire:target="houseSearch,houseCluster,selectedHousesOnly,housePage" class="small text-secondary" role="status">Memuat rumah...</span>
    </div>
    <div class="house-dialog-results" wire:loading.class="opacity-50" wire:target="houseSearch,houseCluster,selectedHousesOnly,housePage,toggleHouse,selectVisibleHouses,clearHouses">
        @forelse ($houseResults as $house)
            <label class="house-result {{ in_array($house->id, $house_ids) ? 'is-selected' : '' }}" wire:key="house-result-{{ $house->id }}">
                <input type="{{ $activeTab === 'vendor-service' && $vendor_service_target === 'house' ? 'radio' : 'checkbox' }}" @if($activeTab === 'vendor-service' && $vendor_service_target === 'house') name="vendor-service-house" @endif class="form-check-input m-0 flex-shrink-0" wire:click="toggleHouse({{ $house->id }})" @checked(in_array($house->id, $house_ids)) wire:loading.attr="disabled" />
                <span class="flex-grow-1">
                    <span class="d-block fw-semibold">{{ $house->name }}</span>
                    <span class="d-block small text-secondary">{{ $house->house_code }}</span>
                </span>
                <span class="small text-secondary text-end flex-shrink-0">
                    <span class="d-block fw-semibold">{{ $house->cluster?->name ?? 'Tanpa cluster' }}</span>
                    <span class="d-block extra-small">{{ $activeTab === 'return' ? $house->active_loans_count.' peminjaman' : $house->type }}</span>
                    @if($activeTab !== 'return' && $house->isUnderWarranty())
                        <span class="d-block extra-small text-success">Garansi sampai {{ $house->warranty_expires_at->format('d/m/Y') }}</span>
                    @endif
                </span>
            </label>
        @empty
            <div class="text-center p-4">
                <p class="fw-semibold mb-1">{{ $selectedHousesOnly ? 'Tidak ada rumah dipilih yang cocok' : 'Tidak ada rumah yang cocok' }}</p>
                <p class="small text-secondary mb-0">{{ $activeTab === 'return' ? 'Coba kata kunci lain. Hanya rumah dengan peminjaman aktif yang ditampilkan.' : (in_array($activeTab, ['allocation', 'material', 'tool', 'spreadsheet'], true) ? 'Coba kata kunci atau cluster lain. Rumah selesai hanya ditampilkan selama masa garansi.' : ($activeTab === 'vendor-service' ? 'Coba kata kunci atau cluster lain.' : 'Coba kata kunci atau cluster lain. Rumah yang sudah selesai tidak ditampilkan.')) }}</p>
            </div>
        @endforelse
    </div>
    <div class="house-dialog-footer">
        <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
            <span class="small text-secondary" role="status">
                {{ $houseResultCount ? ($housePage - 1) * 8 + 1 : 0 }}-{{ min($housePage * 8, $houseResultCount) }} dari {{ $houseResultCount }} rumah
            </span>
            <div class="d-flex gap-1" aria-label="Halaman rumah">
                <button type="button" wire:click="$set('housePage', {{ $housePage - 1 }})" class="btn log-row-action log-row-action--quiet" @disabled($housePage <= 1) wire:loading.attr="disabled">Sebelumnya</button>
                <button type="button" wire:click="$set('housePage', {{ $housePage + 1 }})" class="btn log-row-action log-row-action--quiet" @disabled($housePage >= $housePages) wire:loading.attr="disabled">Berikutnya</button>
            </div>
        </div>
        <div class="d-flex align-items-center justify-content-between gap-2 border-top pt-3">
            <button type="button" wire:click="clearHouses" class="btn btn-link text-secondary btn-sm" @disabled(empty($house_ids))>Kosongkan pilihan</button>
            <button type="button" @click="housePickerOpen = false" wire:click="closeHousePicker" class="btn log-row-action log-row-action--edit">Selesai · {{ count($house_ids) }} rumah</button>
        </div>
    </div>
</dialog>
</div>
@endteleport

    <style>
        .transaction-workspace {
            --transaction-accent: #136928;
            --transaction-accent-soft: #e9f4eb;
            --transaction-surface: var(--bs-body-bg);
            --transaction-surface-muted: var(--bs-tertiary-bg);
            --transaction-line: var(--bs-border-color);
            --transaction-shadow: 0 14px 34px rgb(31 48 36 / 8%);
        }

        .transaction-header { overflow: hidden; margin-bottom: 1.5rem !important; }
        .transaction-header-body { display: flex; align-items: center; justify-content: space-between; gap: 2rem; }
        .transaction-title {
            letter-spacing: -.035em;
            line-height: 1.08;
        }
        .transaction-intro { max-width: 38rem; }
        .transaction-header-note {
            display: flex;
            align-items: flex-start;
            gap: .6rem;
            max-width: 21rem;
            margin: 0;
            color: var(--bs-secondary-color);
            font-size: .78rem;
            line-height: 1.45;
        }
        .transaction-header-note svg { width: 19px; height: 19px; flex: 0 0 auto; color: var(--transaction-accent); }

        .transaction-modes {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            margin-bottom: 1.5rem;
            overflow: hidden;
            border: 1px solid var(--transaction-line);
            border-radius: 12px;
            background: var(--transaction-surface);
        }
        .transaction-mode {
            position: relative;
            display: grid;
            grid-template-columns: 28px minmax(0, 1fr);
            align-items: center;
            gap: .7rem;
            min-width: 0;
            min-height: 76px;
            padding: .9rem 1rem;
            border: 0;
            border-right: 1px solid var(--transaction-line);
            background: transparent;
            color: var(--bs-secondary-color);
            text-align: left;
            transition: background-color 160ms ease, color 160ms ease;
        }
        .transaction-mode:last-child { border-right: 0; }
        .transaction-mode::after {
            position: absolute;
            right: 0;
            bottom: 0;
            left: 0;
            height: 3px;
            background: transparent;
            content: "";
        }
        .transaction-mode:hover { background: var(--transaction-surface-muted); color: var(--bs-body-color); }
        .transaction-mode.is-active { background: var(--transaction-accent-soft); color: var(--transaction-accent); }
        .transaction-mode.is-active::after { background: #16913a; }
        .transaction-mode-icon { display: grid; place-items: center; }
        .transaction-mode-icon svg { width: 25px; height: 25px; }
        .transaction-mode-copy { display: block; min-width: 0; }
        .transaction-mode-title { display: block; color: inherit; font-weight: 750; line-height: 1.2; }
        .transaction-mode-description { display: block; margin-top: .25rem; color: var(--bs-secondary-color); font-size: .73rem; line-height: 1.3; }

        .transaction-grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(300px, 350px); gap: 1.25rem; align-items: start; }
        .transaction-form { min-width: 0; }
        .transaction-sheet {
            border: 1px solid var(--transaction-line);
            border-radius: 14px;
            background: var(--transaction-surface);
            box-shadow: 0 8px 24px rgb(31 48 36 / 5%);
        }
        .transaction-step-block { min-width: 0; padding: 1.5rem; }
        .transaction-step-block + .transaction-step-block { border-top: 1px solid var(--transaction-line); }
        .transaction-step-head { display: flex; align-items: center; justify-content: space-between; gap: 1.25rem; }
        .transaction-step-heading { display: flex; align-items: flex-start; gap: .85rem; min-width: 0; }
        .vendor-service-target-choice { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .625rem; }
        .vendor-service-target-choice .btn { min-height: 3.75rem; }
        .vendor-service-target-choice .btn .small { color: var(--bs-secondary-color); }
        .vendor-service-actions { display: flex; align-items: center; justify-content: flex-end; gap: .65rem; border-top: 1px solid var(--bs-border-color); padding-top: 1rem; }
        .vendor-service-actions .btn { min-height: 2.9rem; border-radius: .65rem; padding: .7rem 1rem; font-weight: 650; }
        .vendor-service-clear { border: 1px solid var(--bs-border-color); color: var(--bs-secondary-color); background: transparent; }
        .vendor-service-clear:hover, .vendor-service-clear:focus-visible { border-color: var(--bs-secondary-color); color: var(--bs-body-color); background: var(--bs-tertiary-bg); }
        .vendor-service-review { min-width: 10rem; }
        .vendor-service-confirmation-total { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-top: 1rem; padding: .9rem 1rem; border: 1px solid color-mix(in srgb, var(--bs-success) 30%, var(--bs-border-color)); border-radius: .75rem; background: color-mix(in srgb, var(--bs-success) 7%, var(--bs-body-bg)); }
        .vendor-service-confirmation-total strong { color: var(--bs-success); font-family: var(--font-geist-mono, monospace); font-size: 1.2rem; font-weight: 750; font-variant-numeric: tabular-nums; }
        .vendor-service-confirmation-note { overflow-wrap: anywhere; border-left: 3px solid var(--bs-border-color); padding-left: .75rem; }
        .transaction-step-index {
            flex: 0 0 auto;
            padding-top: .12rem;
            color: var(--transaction-accent);
            font-family: var(--font-geist-mono, ui-monospace, monospace);
            font-size: .76rem;
            font-weight: 800;
            letter-spacing: .08em;
        }
        .transaction-house-button {
            min-width: 8.75rem;
            border-color: var(--transaction-accent);
            background: var(--transaction-accent);
            color: #fff;
            font-weight: 750;
        }
        .transaction-house-button:hover,
        .transaction-house-button:focus-visible {
            border-color: var(--transaction-accent);
            background: var(--transaction-accent);
            color: #fff;
        }
        .selected-house {
            padding: .32rem .6rem;
            border: 1px solid var(--transaction-line);
            border-radius: 6px;
            background: var(--transaction-surface-muted);
            font-size: .84rem;
            font-weight: 650;
        }

        .transaction-workspace .btn,
        .transaction-workspace .form-control,
        .transaction-workspace .form-select { min-height: 44px; }
        .transaction-workspace .btn { border-radius: 8px; }
        .transaction-workspace .btn-success {
            --bs-btn-bg: #136928;
            --bs-btn-border-color: #136928;
            --bs-btn-hover-bg: #0f5b22;
            --bs-btn-hover-border-color: #0f5b22;
            --bs-btn-disabled-bg: #136928;
            --bs-btn-disabled-border-color: #136928;
        }
        .transaction-workspace .btn-link { color: var(--transaction-accent); }
        .transaction-workspace :is(button, input, select):focus-visible { outline: 3px solid var(--transaction-accent); outline-offset: 2px; box-shadow: none; }
        .transaction-workspace .form-control::placeholder { color: var(--bs-secondary-color) !important; opacity: 1; }
        .transaction-workspace input[type="date"] { min-width: 0; width: 100%; }
        .transaction-workspace .card.shadow-lg { border-color: var(--transaction-line) !important; border-radius: 10px !important; box-shadow: var(--transaction-shadow) !important; }

        .transaction-proof-field { padding-top: .25rem; }
        .transaction-upload {
            position: relative;
            display: grid;
            grid-template-columns: auto minmax(0, 1fr);
            align-items: center;
            min-height: 46px;
            overflow: hidden;
            border: 1px solid var(--transaction-line);
            border-radius: 8px;
            background: var(--transaction-surface-muted);
        }
        .transaction-upload:focus-within { outline: 3px solid var(--transaction-accent); outline-offset: 2px; }
        .transaction-upload-input { position: absolute; inset: 0; z-index: 2; width: 100%; height: 100%; opacity: 0; cursor: pointer; }
        .transaction-upload-action { display: inline-flex; align-items: center; gap: .45rem; align-self: stretch; padding: 0 .8rem; border-right: 1px solid var(--transaction-line); color: var(--bs-body-color); font-size: .875rem; font-weight: 700; }
        .transaction-upload-name { min-width: 0; overflow: hidden; padding: 0 .8rem; color: var(--bs-secondary-color); font-size: .84rem; text-overflow: ellipsis; white-space: nowrap; }

        .return-record { padding: 1rem; border: 1px solid var(--transaction-line); border-radius: 10px; background: var(--transaction-surface); transition: border-color 160ms ease, background-color 160ms ease; }
        .return-record:hover { background: var(--transaction-surface-muted); }
        .return-record.is-selected { border-color: var(--transaction-accent); background: var(--transaction-accent-soft); }
        .transaction-count { flex: 0 0 auto; color: var(--bs-secondary-color); }

        .transaction-summary { min-width: 0; }
        .transaction-summary__sticky { position: sticky; top: 1rem; }
        .transaction-slip {
            overflow: hidden;
            border: 1px solid var(--transaction-line);
            border-radius: 14px;
            background: var(--transaction-surface);
            box-shadow: var(--transaction-shadow);
        }
        .transaction-slip__body { padding: 1.25rem; }
        .transaction-slip__header { display: flex; align-items: flex-start; gap: .75rem; padding-bottom: 1rem; margin-bottom: 1rem; border-bottom: 1px solid var(--transaction-line); }
        .transaction-slip__header > svg { width: 22px; height: 22px; flex: 0 0 auto; color: var(--transaction-accent); }
        .transaction-summary .extra-small { font-size: .82rem; }
        .transaction-summary .text-success { color: var(--transaction-accent) !important; }
        .transaction-slip__totals { padding-top: .85rem; border-top: 1px dashed var(--transaction-line); }
        .transaction-grand-total { padding: .85rem 0 .15rem; margin-top: .65rem; border-top: 1px solid var(--transaction-line); }
        .transaction-grand-total .fs-4 { font-family: var(--font-geist-mono, ui-monospace, monospace); font-size: 1.25rem !important; font-variant-numeric: tabular-nums; }
        .transaction-actions { padding-top: 1rem; margin-top: .9rem; border-top: 1px solid var(--transaction-line); }

        .house-dialog-backdrop { position: fixed; inset: 0; z-index: 1055; background: rgb(10 18 12 / 62%); backdrop-filter: blur(2px); }
        .house-dialog { width: min(820px, calc(100vw - 2rem)); max-width: none; height: min(640px, calc(100dvh - 4rem)); max-height: calc(100dvh - 4rem); padding: 0; border: 1px solid var(--transaction-line); border-radius: 14px; color: var(--bs-body-color); background: var(--transaction-surface); }
        .transaction-confirmation[open] { position: fixed; top: 50%; left: 50%; z-index: 1060; transform: translate(-50%, -50%); margin: 0; }
        .house-dialog[open] { position: fixed; inset: auto; top: 50%; left: 50%; z-index: 1060; margin: 0; overflow: hidden; transform: translate(-50%, -50%); display: flex; flex-direction: column; }
        .house-dialog::backdrop, .transaction-confirmation::backdrop { background: rgb(10 18 12 / 62%); backdrop-filter: blur(2px); }
        .house-dialog-header, .house-dialog-footer { padding: 1rem 1.25rem; flex-shrink: 0; }
        .house-dialog-header { border-bottom: 1px solid var(--transaction-line); }
        .house-dialog-footer { border-top: 1px solid var(--transaction-line); }
        .house-dialog-results { flex: 1 1 auto; overflow-y: auto; min-height: 0; overscroll-behavior: contain; }
        .house-result { display: flex; align-items: center; gap: .85rem; min-height: 58px; padding: .6rem 1.25rem; cursor: pointer; }
        .house-result + .house-result { border-top: 1px solid var(--transaction-line); }
        .house-result > span { min-width: 0; overflow-wrap: anywhere; }
        .house-result:hover, .house-result.is-selected { background: var(--transaction-surface-muted); }
        .house-result:focus-within { outline: 3px solid var(--transaction-accent); outline-offset: -3px; }
        .house-result input, .house-filter-toggle input { accent-color: var(--transaction-accent); }
        .transaction-workspace .form-check-input { border-color: #74887c; }
        .transaction-workspace .form-check-input:checked { background-color: #136928; border-color: #136928; }
        .house-filter-toggle { min-height: 44px; cursor: pointer; }
        .house-dialog .btn-close { min-width: 44px; min-height: 44px; }

        .spreadsheet-workspace {
            overflow: hidden;
            border: 1px solid var(--transaction-line);
            border-radius: 14px;
            background: var(--transaction-surface);
        }
        .spreadsheet-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; padding: 1.25rem; }
        .spreadsheet-table-scroll { border-top: 1px solid var(--transaction-line); }
        .spreadsheet-house-cell { min-width: 260px; vertical-align: top; }
        .spreadsheet-house-picker { width: 100%; min-width: 0; font-weight: 400; }
        .spreadsheet-table-scroll > .data-table.table-hover > tbody > tr:hover > .spreadsheet-house-cell { background-color: var(--transaction-surface) !important; box-shadow: none; }
        .spreadsheet-house-picker-control { position: relative; }
        .spreadsheet-house-picker-control .form-control { min-height: 48px; padding-right: 6.25rem; }
        .spreadsheet-house-picker-actions { position: absolute; top: 50%; right: .25rem; display: flex; align-items: center; gap: .15rem; transform: translateY(-50%); }
        .spreadsheet-house-picker-action { display: grid; width: 44px; min-height: 44px; place-items: center; padding: 0; border: 0; border-radius: 6px; color: var(--bs-secondary-color); background: transparent; }
        .spreadsheet-house-picker-action:hover, .spreadsheet-house-picker-action:focus-visible { color: var(--bs-body-color); background: var(--transaction-surface-muted); }
        .spreadsheet-house-picker-action:focus-visible { outline: 2px solid var(--transaction-accent); outline-offset: -2px; }
        .spreadsheet-house-picker-action svg { width: 16px; height: 16px; }
        .spreadsheet-house-picker-option { display: flex; width: 100%; min-height: 56px; align-items: center; justify-content: space-between; gap: .75rem; padding: .6rem .75rem; border: 0; border-bottom: 1px solid var(--transaction-line); color: var(--bs-body-color); background: transparent; text-align: left; }
        .spreadsheet-house-picker-option:last-child { border-bottom: 0; }
        .spreadsheet-house-picker-option:hover, .spreadsheet-house-picker-option.is-active { background: var(--transaction-surface-muted); }
        .spreadsheet-house-picker-option[aria-selected="true"] { background: var(--transaction-accent-soft); }
        .spreadsheet-house-picker-option:focus-visible { outline: 2px solid var(--transaction-accent); outline-offset: -2px; }
        .spreadsheet-house-picker-option-name { min-width: 0; font-weight: 600; overflow-wrap: anywhere; }
        .spreadsheet-house-picker-option-code { flex-shrink: 0; color: var(--bs-secondary-color); font-size: .82rem; font-weight: 400; }
        .spreadsheet-house-picker-empty { padding: .9rem .75rem; color: var(--bs-secondary-color); font-size: .9rem; }
        .spreadsheet-table-scroll td.spreadsheet-action-cell { min-width: 260px; vertical-align: top; }
        .spreadsheet-entry { min-width: 0; }
        .spreadsheet-item-picker { min-width: 0; }
        .spreadsheet-item-picker-selected { flex: 1 1 auto; min-width: 0; }
        .spreadsheet-item-picker-trigger { display: flex; width: 100%; min-height: 48px; align-items: center; justify-content: space-between; gap: .6rem; padding: .65rem .8rem; border: 1px solid var(--transaction-line); border-radius: .65rem; color: var(--bs-secondary-color); background: var(--transaction-surface); text-align: left; }
        .spreadsheet-item-picker-trigger:hover, .spreadsheet-item-picker-trigger:focus-visible, .spreadsheet-item-picker-trigger[aria-expanded="true"] { border-color: var(--transaction-accent); color: var(--bs-body-color); }
        .spreadsheet-item-picker-trigger:focus-visible, .spreadsheet-item-picker-trigger[aria-expanded="true"] { outline: 0; box-shadow: 0 0 0 .2rem color-mix(in srgb, var(--transaction-accent) 28%, transparent); }
        .spreadsheet-item-picker-label { min-width: 0; overflow-wrap: anywhere; }
        .spreadsheet-item-picker-label.is-selected { color: var(--bs-body-color); font-weight: 600; }
        .spreadsheet-item-picker-trigger svg { width: 16px; height: 16px; flex: 0 0 auto; transition: transform 160ms ease; }
        .spreadsheet-item-picker-trigger svg.is-open { transform: rotate(180deg); }
        .spreadsheet-item-picker-menu { display: block; overflow-x: hidden; overflow-y: auto; }
        .spreadsheet-item-picker-menu [role="listbox"] { display: block; flex: 1 1 auto; width: 100%; min-width: 0; min-height: 0; overflow-y: auto; }
        .spreadsheet-item-picker-search { position: relative; z-index: 1; flex: 0 0 auto; width: calc(100% - .75rem); min-height: 44px !important; margin: .35rem; background: var(--ledger-surface-raised); }
        .spreadsheet-item-picker-option { display: block; width: 100%; height: auto; min-width: 0; min-height: 4.75rem; margin: 0 0 .35rem; padding: .85rem .75rem; border: 0; border-bottom: 1px solid var(--transaction-line); border-radius: 0; color: var(--bs-body-color); background: transparent; line-height: 1.35; text-align: left; }
        .spreadsheet-item-picker-option:last-child { border-bottom: 0; }
        .spreadsheet-item-picker-option:hover, .spreadsheet-item-picker-option.is-active { background: var(--transaction-surface-muted); }
        .spreadsheet-item-picker-option[aria-selected="true"] { background: var(--transaction-accent-soft); }
        .spreadsheet-item-picker-option:focus-visible { outline: 2px solid var(--transaction-accent); outline-offset: -2px; }
        .spreadsheet-item-picker-option-label, .spreadsheet-item-picker-option-meta { display: block; width: 100%; max-width: 100%; overflow-wrap: anywhere; white-space: normal; }
        .spreadsheet-item-picker-option-label { font-weight: 700; line-height: 1.3; }
        .spreadsheet-item-picker-option-meta { margin-top: .35rem; color: var(--bs-secondary-color); font-size: .78rem; line-height: 1.3; text-align: left; }
        .spreadsheet-item-picker--two-line .spreadsheet-item-picker-selected { display: grid; gap: .1rem; }
        .spreadsheet-item-picker--two-line .spreadsheet-item-picker-option-meta { white-space: normal; }
        .spreadsheet-item-picker-empty { padding: .8rem .7rem; color: var(--bs-secondary-color); font-size: .84rem; }
        .spreadsheet-entry-fields { display: grid; gap: .45rem; margin-top: .45rem; }
        .spreadsheet-field-label { margin-bottom: -.3rem; color: var(--bs-secondary-color); font-size: .75rem; font-weight: 600; }
        .spreadsheet-return-quantity { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .35rem; margin-top: .6rem; }
        .spreadsheet-return-quantity .spreadsheet-field-label { grid-row: 1; }
        .spreadsheet-remove-line, .spreadsheet-add-line { min-height: 44px; padding: .25rem .4rem; border: 0; background: transparent; font-size: .78rem; text-align: left; }
        .spreadsheet-remove-line { color: #f28b82; }
        .spreadsheet-remove-line:hover, .spreadsheet-remove-line:focus-visible { color: #ffb4ab; text-decoration: underline; }
        .spreadsheet-add-line { color: var(--transaction-accent); font-weight: 600; }
        .spreadsheet-add-line:hover { text-decoration: underline; }
        .spreadsheet-submit-bar { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; padding: 1rem 1.25rem; border-top: 1px solid var(--transaction-line); }
        .spreadsheet-submit-actions { display: flex; align-items: center; justify-content: flex-end; gap: .75rem; margin-left: auto; }
        .spreadsheet-submit-actions .transaction-house-button { min-height: 48px; }

        .transaction-confirmation { width: min(560px, calc(100vw - 2rem)); max-height: calc(100dvh - 2rem); padding: 0; border: 1px solid var(--bs-border-color); border-radius: 14px; background: var(--bs-body-bg); color: var(--bs-body-color); }
        .transaction-confirmation:not(.transaction-confirmation--return)[open] { display: flex; flex-direction: column; overflow: hidden; }
        .transaction-confirmation .modal-dialog { width: 100%; margin: 0 !important; min-height: 0; }
        .allocation-confirmation-content { max-height: calc(100dvh - 2rem); overflow: hidden; }
        .allocation-confirmation-header, .allocation-confirmation-footer { flex-shrink: 0; padding: 1rem 1.25rem; }
        .allocation-confirmation-header { background: color-mix(in srgb, var(--bs-success) 8%, var(--bs-body-bg)) !important; }
        .allocation-confirmation-header .modal-title { font-size: 1.1rem; }
        .allocation-confirmation-body { min-height: 0; overflow-y: auto; padding: 1.1rem 1.25rem; }
        .allocation-confirmation-summary { display: grid; grid-template-columns: minmax(105px, .65fr) minmax(0, 1.6fr); gap: .55rem 1rem; }
        .allocation-confirmation-summary dt { color: var(--bs-secondary-color); font-weight: 600; }
        .allocation-confirmation-summary dd { min-width: 0; overflow-wrap: anywhere; font-weight: 650; }
        .allocation-confirmation-note { border-left: 3px solid var(--bs-success); padding: .1rem 0 .1rem .75rem; }
        .allocation-confirmation-proof { padding: .9rem; border: 1px solid var(--bs-border-color); border-radius: 10px; background: color-mix(in srgb, var(--bs-secondary-bg) 55%, transparent); }
        .allocation-confirmation-houses { border-top: 1px solid var(--bs-border-color); margin-top: 1rem; padding-top: 1rem; }
        .allocation-confirmation-house { padding: .65rem 0; border-bottom: 1px solid var(--bs-border-color); }
        .allocation-confirmation-house:last-child { border-bottom: 0; }
        .allocation-confirmation-footer { background: var(--bs-tertiary-bg); }
        .transaction-confirmation .btn { min-height: 44px; }
        .transaction-confirmation .btn-success { --bs-btn-bg: #136928; --bs-btn-border-color: #136928; }
        dialog.transaction-confirmation--return { background-color: var(--bs-body-bg) !important; backdrop-filter: none !important; -webkit-backdrop-filter: none !important; overflow: hidden; }
        .transaction-confirmation--return[open] { display: flex; flex-direction: column; }
        .return-confirmation-header, .return-confirmation-body, .return-confirmation-footer { padding: 1.25rem 1.5rem; }
        .return-confirmation-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; border-bottom: 1px solid var(--bs-border-color); }
        .return-confirmation-header h2 { font-size: 1.25rem; line-height: 1.3; }
        .return-confirmation-body { overflow-y: auto; }
        .return-confirmation-item + .return-confirmation-item { border-top: 1px solid var(--bs-border-color); padding-top: 1rem; margin-top: 1rem; }
        .return-confirmation-item-main { display: flex; flex-wrap: wrap; align-items: baseline; justify-content: space-between; gap: .25rem 1rem; }
        .return-confirmation-quantity { white-space: nowrap; font-variant-numeric: tabular-nums; }
        .return-confirmation-footer { display: flex; justify-content: flex-end; gap: .75rem; border-top: 1px solid var(--bs-border-color); }
        @media (max-width: 575.98px) {
            .return-confirmation-header, .return-confirmation-body, .return-confirmation-footer,
            .allocation-confirmation-header, .allocation-confirmation-body, .allocation-confirmation-footer { padding: 1rem; }
            .return-confirmation-footer .btn, .allocation-confirmation-footer .btn { flex: 1; }
            .allocation-confirmation-summary { grid-template-columns: minmax(84px, .6fr) minmax(0, 1.4fr); gap: .5rem .75rem; }
            .vendor-service-actions { flex-direction: column-reverse; align-items: stretch; }
            .vendor-service-actions .btn { width: 100%; }
            .vendor-service-confirmation-total strong { font-size: 1.05rem; }
        }

        [data-bs-theme="dark"] .transaction-workspace {
            --transaction-accent: #8cdaa0;
            --transaction-accent-soft: #172a1d;
            --transaction-shadow: 0 16px 38px rgb(0 0 0 / 24%);
        }
        [data-bs-theme="dark"] .transaction-workspace .text-secondary,
        [data-bs-theme="dark"] .transaction-confirmation .text-secondary { color: #aebbb1 !important; }
        [data-bs-theme="dark"] .transaction-workspace .form-check-input { border-color: #aebbb1; }
        [data-bs-theme="dark"] .transaction-workspace .btn-outline-secondary { --bs-btn-color: #e1e9e3; --bs-btn-border-color: #708076; --bs-btn-hover-bg: #263029; --bs-btn-hover-border-color: #8fa096; }
        [data-bs-theme="dark"] .transaction-house-button,
        [data-bs-theme="dark"] .transaction-house-button:hover,
        [data-bs-theme="dark"] .transaction-house-button:focus-visible { color: #102116; }
        [data-bs-theme="dark"] .transaction-mode.is-active::after { background: #8cdaa0; }

        @media (max-width: 1099.98px) {
            .transaction-modes { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .transaction-grid { grid-template-columns: minmax(0, 1fr) minmax(280px, 320px); }
            .transaction-mode { padding-inline: .8rem; }
        }
        @media (max-width: 991.98px) {
            .transaction-grid { grid-template-columns: minmax(0, 1fr); }
            .transaction-summary__sticky { position: static; }
            .transaction-slip__body { display: grid; grid-template-columns: minmax(0, 1fr); }
        }
        @media (max-width: 767.98px) {
            .transaction-modes { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .transaction-header-body { align-items: flex-start; flex-direction: column; gap: .75rem; }
            .transaction-header-note { max-width: 36rem; }
            .transaction-mode-description { display: none; }
            .transaction-mode { min-height: 62px; }
            .transaction-step-block { padding: 1.25rem; }
        }
        @media (max-width: 575.98px) {
            .transaction-title { font-size: 2rem; }
            .transaction-intro { font-size: .875rem; }
            .transaction-header-note { font-size: .75rem; }
            .transaction-mode { display: flex; flex-direction: column; justify-content: center; gap: .3rem; min-height: 70px; padding: .65rem .35rem; text-align: center; }
            .transaction-mode-icon svg { width: 20px; height: 20px; }
            .transaction-mode-title { font-size: .78rem; line-height: 1.15; }
            .transaction-step-block { padding: 1rem; }
            .transaction-step-head { align-items: stretch; flex-direction: column; }
            .transaction-house-button { width: 100%; }
            .vendor-service-target-choice { grid-template-columns: minmax(0, 1fr); }
            .transaction-step-index { font-size: .7rem; }
            .selected-house + .small.ms-auto { width: 100%; margin-left: 0 !important; }
            .house-dialog { width: calc(100vw - 1rem); height: calc(100dvh - 1rem); max-height: calc(100dvh - 1rem); }
            .house-dialog-header, .house-dialog-footer { padding: .75rem; }
            .house-dialog-header .row > [class*="col-"] { width: 100%; }
            .house-result { padding-inline: .75rem; }
            .transaction-slip__body { padding: 1rem; }
            .spreadsheet-heading { align-items: stretch; flex-direction: column; }
            .spreadsheet-heading { padding: 1rem; }
            .spreadsheet-submit-bar { align-items: stretch; flex-direction: column; padding: 1rem; }
            .spreadsheet-submit-actions { align-items: stretch; flex-direction: column; margin-left: 0; }
            .spreadsheet-submit-actions .transaction-house-button { width: 100%; }
        }
        @media (max-width: 399.98px) {
            .transaction-actions .d-flex { flex-direction: column-reverse; }
            .transaction-actions .btn { width: 100%; }
        }
        @media (prefers-reduced-motion: reduce) {
            .transaction-mode, .return-record { transition: none; }
        }
    </style>

    <!-- ===== Confirmation modals ===== -->
    @if($showMaterialConfirmation)
    @teleport('body')
    <dialog class="transaction-confirmation" x-data x-effect="if ($wire.showMaterialConfirmation && !$el.open) { $el.showModal ? $el.showModal() : $el.setAttribute('open', '') }" @cancel="$wire.set('showMaterialConfirmation', false)" aria-label="Konfirmasi Alokasi Material">
        <div class="modal-dialog">
            <div class="modal-content allocation-confirmation-content border-0 shadow-lg rounded-4">
                <div class="modal-header allocation-confirmation-header border-bottom">
                    <h5 class="modal-title font-outfit fw-bold">Konfirmasi Alokasi Material</h5>
                    <button type="button" class="btn-close" aria-label="Tutup konfirmasi" wire:click="$set('showMaterialConfirmation', false)"></button>
                </div>
                <div class="modal-body allocation-confirmation-body">
                    <dl class="allocation-confirmation-summary small mb-0">
                        <dt class="text-secondary fw-semibold">Unit rumah</dt>
                        <dd class="mb-0 fw-bold">{{ $materialConfirmationData['houseCount'] ?? 0 }} rumah</dd>
                        <dt class="text-secondary fw-semibold">Cluster</dt>
                        <dd class="mb-0 fw-bold">{{ $materialConfirmationData['clusters'] ?? '-' }}</dd>
                        <dt class="text-secondary fw-semibold">Material</dt>
                        <dd class="mb-0 fw-bold">{{ $materialConfirmationData['materialName'] ?? '-' }} · {{ $materialConfirmationData['batchCode'] ?? '-' }}</dd>
                        <dt class="text-secondary fw-semibold">Supplier</dt>
                        <dd class="mb-0 fw-bold">{{ $materialConfirmationData['materialSupplier'] ?? '-' }}</dd>
                        <dt class="text-secondary fw-semibold">Gudang asal</dt>
                        <dd class="mb-0 fw-bold">{{ $materialConfirmationData['warehouseName'] ?? '-' }}</dd>
                        <dt class="text-secondary fw-semibold">Pengambil</dt>
                        <dd class="mb-0 fw-bold">{{ $materialConfirmationData['takenBy'] ?? 'Tidak dicatat' }}</dd>
                        <dt class="text-secondary fw-semibold">Total qty</dt>
                        <dd class="mb-0 fw-bold font-mono">{{ $materialConfirmationData['totalQuantity'] ?? 0 }} {{ $materialConfirmationData['materialUnit'] ?? '' }}</dd>
                        <dt class="text-secondary fw-semibold">Estimasi biaya batch</dt>
                        <dd class="mb-0 fw-bold text-success font-mono">Rp {{ number_format($materialConfirmationData['totalCost'] ?? 0, 0, ',', '.') }}</dd>
                    </dl>
                    <p class="allocation-confirmation-note small text-secondary mt-3 mb-0">Saat disimpan, stok langsung berkurang dan biaya material tercatat pada setiap rumah.</p>
                    <div class="allocation-confirmation-proof mt-3">
                        <label for="material-allocation-proof" class="form-label small fw-semibold">Foto bukti alokasi material <span class="text-secondary fw-normal">(opsional)</span></label>
                        <input id="material-allocation-proof" type="file" class="form-control" accept="image/*" wire:model="materialAllocationProofImage">
                        <div wire:loading wire:target="materialAllocationProofImage" class="form-text" role="status">Mengunggah foto…</div>
                        <div class="form-text">Format gambar, maksimal 5 MB.</div>
                        @error('materialAllocationProofImage') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="allocation-confirmation-houses">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="small fw-bold mb-0">Dampak per rumah</h6>
                            <span class="extra-small text-secondary">{{ $materialConfirmationData['houseCount'] ?? 0 }} rumah</span>
                        </div>
                        <div>
                            @foreach (($materialConfirmationData['houseRows'] ?? []) as $houseRow)
                                <div class="allocation-confirmation-house d-flex align-items-center justify-content-between gap-3">
                                    <div class="min-w-0">
                                        <div class="small fw-semibold text-truncate">{{ $houseRow['name'] }}</div>
                                        <div class="extra-small text-secondary font-mono">{{ $houseRow['code'] }} · {{ $houseRow['cluster'] }}</div>
                                    </div>
                                    <div class="text-end flex-shrink-0 font-mono extra-small">
                                        <div class="fw-bold">{{ $materialConfirmationData['quantityPerHouse'] ?? 0 }} {{ $materialConfirmationData['materialUnit'] ?? '' }}</div>
                                        <div class="text-secondary">Rp {{ number_format($materialConfirmationData['quantityPerHouse'] * $materialConfirmationData['unitPrice'], 0, ',', '.') }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="modal-footer allocation-confirmation-footer border-top rounded-bottom-4">
                    <button type="button" class="btn btn-secondary fw-semibold" wire:click="$set('showMaterialConfirmation', false)">Batal</button>
                    <button type="button" class="btn btn-success fw-semibold" wire:click="saveMaterial" wire:loading.attr="disabled" wire:target="saveMaterial,materialAllocationProofImage"><span wire:loading.remove wire:target="saveMaterial">Alokasikan material</span><span wire:loading wire:target="saveMaterial">Menyimpan alokasi…</span></button>
                </div>
            </div>
        </div>
    </dialog>
    @endteleport
    @endif

    @if($showToolConfirmation)
    @teleport('body')
    <dialog class="transaction-confirmation" x-data x-effect="if ($wire.showToolConfirmation && !$el.open) { $el.showModal ? $el.showModal() : $el.setAttribute('open', '') }" @cancel="$wire.set('showToolConfirmation', false)" aria-label="Konfirmasi Alokasi Alat">
        <div class="modal-dialog">
            <div class="modal-content allocation-confirmation-content border-0 shadow-lg rounded-4">
                <div class="modal-header allocation-confirmation-header border-bottom">
                    <h5 class="modal-title font-outfit fw-bold">Konfirmasi Alokasi Alat</h5>
                    <button type="button" class="btn-close" aria-label="Tutup konfirmasi" wire:click="$set('showToolConfirmation', false)"></button>
                </div>
                <div class="modal-body allocation-confirmation-body">
                    <dl class="allocation-confirmation-summary small mb-0">
                        <dt class="text-secondary fw-semibold">Unit rumah</dt>
                        <dd class="mb-0 fw-bold">{{ $toolConfirmationData['houseCount'] ?? 0 }} rumah</dd>
                        <dt class="text-secondary fw-semibold">Cluster</dt>
                        <dd class="mb-0 fw-bold">{{ $toolConfirmationData['clusters'] ?? '-' }}</dd>
                        <dt class="text-secondary fw-semibold">Alat</dt>
                        <dd class="mb-0 fw-bold">{{ $toolConfirmationData['toolName'] ?? '-' }}</dd>
                        <dt class="text-secondary fw-semibold">Gudang asal</dt>
                        <dd class="mb-0 fw-bold">{{ $toolConfirmationData['warehouseName'] ?? '-' }}</dd>
                        <dt class="text-secondary fw-semibold">Unit dialokasikan</dt>
                        <dd class="mb-0 fw-bold font-mono">{{ $toolConfirmationData['totalQuantity'] ?? 0 }} unit</dd>
                        <dt class="text-secondary fw-semibold">Sisa stok</dt>
                        <dd class="mb-0 fw-bold font-mono">{{ $toolConfirmationData['availableAfter'] ?? 0 }} unit</dd>
                    </dl>
                    <div class="allocation-confirmation-proof mt-3">
                        <label for="tool-allocation-proof" class="form-label small fw-semibold">Foto bukti alokasi alat <span class="text-danger">*</span></label>
                        <input id="tool-allocation-proof" type="file" class="form-control" accept="image/*" required wire:model="toolAllocationProofImage">
                        <div wire:loading wire:target="toolAllocationProofImage" class="form-text" role="status">Mengunggah foto…</div>
                        <div class="form-text">Format gambar, maksimal 5 MB.</div>
                        @error('toolAllocationProofImage') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="allocation-confirmation-houses">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="small fw-bold mb-0">Dampak per rumah</h6>
                            <span class="extra-small text-secondary">{{ $toolConfirmationData['houseCount'] ?? 0 }} rumah</span>
                        </div>
                        <div>
                            @foreach (($toolConfirmationData['houseRows'] ?? []) as $houseRow)
                                <div class="allocation-confirmation-house d-flex align-items-center justify-content-between gap-3">
                                    <div class="min-w-0">
                                        <div class="small fw-semibold text-truncate">{{ $houseRow['name'] }}</div>
                                        <div class="extra-small text-secondary font-mono">{{ $houseRow['code'] }} · {{ $houseRow['cluster'] }}</div>
                                    </div>
                                    <div class="text-end flex-shrink-0 font-mono extra-small fw-bold">{{ $toolConfirmationData['quantityPerHouse'] ?? 0 }} unit</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="modal-footer allocation-confirmation-footer border-top rounded-bottom-4">
                    <button type="button" class="btn btn-secondary fw-semibold" wire:click="$set('showToolConfirmation', false)">Batal</button>
                    <button type="button" class="btn btn-success fw-semibold" wire:click="saveTool" wire:loading.attr="disabled" wire:target="saveTool,toolAllocationProofImage"><span wire:loading.remove wire:target="saveTool">Alokasikan alat</span><span wire:loading wire:target="saveTool">Menyimpan alokasi…</span></button>
                </div>
            </div>
        </div>
    </dialog>
    @endteleport
    @endif

    @if($showVendorServiceConfirmation)
    @teleport('body')
    <dialog class="transaction-confirmation" x-data x-effect="if ($wire.showVendorServiceConfirmation && !$el.open) { $el.showModal ? $el.showModal() : $el.setAttribute('open', '') }" @cancel="$wire.set('showVendorServiceConfirmation', false)" aria-labelledby="vendor-service-confirmation-title">
        <div class="modal-dialog">
            <div class="modal-content allocation-confirmation-content border-0 rounded-4">
                <div class="modal-header allocation-confirmation-header border-bottom">
                    <div>
                        <div class="small text-secondary mb-1">Konfirmasi sebelum dicatat</div>
                        <h2 id="vendor-service-confirmation-title" class="modal-title font-outfit fw-bold">Catat jasa vendor?</h2>
                    </div>
                    <button type="button" class="btn-close" aria-label="Tutup konfirmasi" wire:click="$set('showVendorServiceConfirmation', false)"></button>
                </div>
                <div class="modal-body allocation-confirmation-body">
                    <p class="small text-secondary mb-3">Periksa rincian target dan biaya sebelum menyimpan.</p>
                    <dl class="allocation-confirmation-summary small mb-0">
                        <dt>Target</dt><dd>{{ $vendorServiceConfirmationData['target'] }}</dd>
                        <dt>Jasa</dt><dd>{{ $vendorServiceConfirmationData['description'] }}</dd>
                        <dt>Vendor</dt><dd>{{ $vendorServiceConfirmationData['vendor'] }}</dd>
                        <dt>Tanggal layanan</dt><dd>{{ \Illuminate\Support\Carbon::parse($vendorServiceConfirmationData['date'])->format('d/m/Y') }}</dd>
                        <dt>Tagihan</dt><dd>{{ $vendorServiceConfirmationData['billAttached'] ? 'Terlampir' : 'Tidak dilampirkan' }}</dd>
                    </dl>
                    <div class="vendor-service-confirmation-total">
                        <span class="small fw-semibold">Total biaya jasa</span>
                        <strong>Rp {{ number_format($vendorServiceConfirmationData['amount'], 0, ',', '.') }}</strong>
                    </div>
                    <p class="vendor-service-confirmation-note small text-secondary mt-3 mb-0">Catatan ini akan masuk ke riwayat biaya target dan tidak mengubah stok material atau alat.</p>
                    @if (filled($vendor_service_notes))
                        <p class="small mt-3 mb-0"><span class="text-secondary">Catatan:</span> {{ $vendor_service_notes }}</p>
                    @endif
                </div>
                <div class="modal-footer allocation-confirmation-footer border-top rounded-bottom-4">
                    <button type="button" class="btn btn-outline-secondary fw-semibold" wire:click="$set('showVendorServiceConfirmation', false)">Kembali</button>
                    <button type="button" class="btn btn-success fw-semibold" wire:click="saveVendorService" wire:loading.attr="disabled" wire:target="saveVendorService,vendor_service_bill_image">
                        <span wire:loading.remove wire:target="saveVendorService">Catat jasa vendor</span>
                        <span wire:loading wire:target="saveVendorService">Menyimpan catatan…</span>
                    </button>
                </div>
            </div>
        </div>
    </dialog>
    @endteleport
    @endif

    @if($showReturnConfirmation)
    @teleport('body')
    <dialog class="transaction-confirmation transaction-confirmation--return" x-data x-init="if ($el.showModal) { $el.showModal() } else { $el.setAttribute('open', '') }" @cancel="$wire.set('showReturnConfirmation', false)" aria-labelledby="return-confirmation-title">
        <header class="return-confirmation-header">
            <div>
                <h2 id="return-confirmation-title" class="font-outfit fw-bold mb-1">Konfirmasi pengembalian alat</h2>
                <p class="small text-secondary mb-0">Periksa kondisi dan gudang penerima sebelum menyimpan.</p>
            </div>
            <button type="button" class="btn-close flex-shrink-0" aria-label="Tutup konfirmasi" wire:click="$set('showReturnConfirmation', false)"></button>
        </header>
        <div class="return-confirmation-body">
            @foreach ($returnConfirmationData as $item)
                <div class="return-confirmation-item">
                    <div class="return-confirmation-item-main">
                        <strong>{{ $item['tool_name'] }}</strong>
                        <span class="return-confirmation-quantity font-mono fw-semibold">{{ $item['return_qty'] }} dari {{ $item['quantity'] }} unit</span>
                    </div>
                    <div class="small text-secondary mt-1">{{ $item['house_name'] }} · Baik {{ $item['qty_normal'] }} · Rusak {{ $item['qty_broken'] }} · Hilang {{ $item['qty_lost'] }}</div>
                    @if ($item['receiving_warehouse_name'])
                        <div class="small mt-2">Gudang penerima: <strong>{{ $item['receiving_warehouse_name'] }}</strong></div>
                    @endif
                </div>
            @endforeach
        </div>
        <footer class="return-confirmation-footer">
            <button type="button" class="btn btn-outline-secondary fw-semibold" wire:click="$set('showReturnConfirmation', false)">Batal</button>
            <button type="button" class="btn btn-success fw-semibold" wire:click="saveReturn" wire:loading.attr="disabled" wire:target="saveReturn"><span wire:loading.remove wire:target="saveReturn">Simpan pengembalian</span><span wire:loading wire:target="saveReturn">Menyimpan pengembalian…</span></button>
        </footer>
    </dialog>
    @endteleport
    @endif
</div>

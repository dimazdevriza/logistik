<div
    x-data="{
        activeTab: @entangle('activeTab').live,
        pickerOpen: false,
        search: '',
        returnSearch: '',
        blockFilter: 'all',
        activeUsages: @js($activeUsages->map(fn($u) => ['id' => $u->id, 'text' => strtolower($u->tool->name . ' ' . $u->tool->code . ' ' . $u->house->name)])->values()),
        get filteredReturnCount() {
            if (!this.returnSearch) return this.activeUsages.length;
            const q = this.returnSearch.toLowerCase();
            return this.activeUsages.filter(u => u.text.includes(q)).length;
        },
        matPickerOpen: false,
        toolPickerOpen: false,
        matNotePickerOpen: false,
        toolNotePickerOpen: false,
        matSearch: '',
        toolSearch: '',
        matNoteSearch: '',
        toolNoteSearch: '',
        peruntukkanOpts: @js(array_values(array_unique(array_merge(['Pemasangan Pondasi', 'Pekerjaan Dinding', 'Pekerjaan Atap', 'Pengecoran Sloof / Kolom', 'Pemasangan Keramik', 'Instalasi Listrik'], $peruntukkanOptions)))),
        materials: @js($materials->keyBy('id')->map(fn($m) => ['id' => $m->id, 'name' => $m->name, 'unit_price' => $m->unit_price, 'unit' => $m->unit, 'stock' => $m->stock])),
        tools: @js($tools->keyBy('id')->map(fn($t) => ['id' => $t->id, 'name' => $t->name, 'available_qty' => $t->available_qty, 'code' => $t->code])),
        allHouses: @js($houses->map(fn($h) => ['id' => (int) $h->id, 'name' => $h->name, 'type' => $h->type])->values()),
        get selectedHousesList() {
            const ids = ($wire.house_ids || []).map(Number);
            return this.allHouses.filter(h => ids.includes(h.id));
        },
        get houseCount() { return $wire.house_ids.length },
        get mat() { return this.materials[$wire.material_id] ?? null },
        get tool() { return this.tools[$wire.tool_id] ?? null },
        get matQty() { return parseFloat($wire.material_quantity) || 0 },
        get toolQty() { return parseInt($wire.tool_quantity) || 0 },
        get matReady() { return this.houseCount > 0 && this.mat && this.matQty > 0 },
        get toolReady() { return this.houseCount > 0 && this.tool && this.toolQty > 0 },
        get totalQty() { return this.matQty * this.houseCount },
        get totalCost() { return this.mat ? this.totalQty * parseFloat(this.mat.unit_price) : 0 },
        get totalTools() { return this.toolQty * this.houseCount },
        get toolShortfall() { return this.tool ? this.tool.available_qty - this.totalTools : 0 },
        get returnCount() {
            return Object.values($wire.returnSelections ?? {}).filter(s => s.selected).length
        },
        rp(v) { return 'Rp ' + new Intl.NumberFormat('id-ID').format(v) },
    }"
>
    @php
        $groupedHouses = $houses->groupBy(function ($h) {
            preg_match('/Blok\s*([A-Za-z]+)/i', $h->name, $matches);
            return isset($matches[1]) ? 'Blok ' . strtoupper($matches[1]) : 'Lainnya';
        });
        $selectedHouses = $houses->whereIn('id', $house_ids);
    @endphp

    <div class="container-fluid p-0" style="padding-bottom: 10rem !important;">

        <!-- Slim page header -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <span class="extra-small fw-bold text-uppercase tracking-wider text-secondary font-geist">Logistik</span>
                <h1 class="h3 fw-black text-body font-outfit mb-0">Transaksi Logistik</h1>
            </div>
            @if (count($house_ids) > 0)
                <button type="button" wire:click="clearHouses" class="btn btn-sm btn-outline-danger fw-semibold">
                    Kosongkan {{ count($house_ids) }} unit
                </button>
            @endif
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Main 2-Column Workspace Grid -->
        <div class="row g-4 align-items-start">
            <!-- Left Form Column (col-lg-7 col-xl-8) -->
            <div class="col-lg-7 col-xl-8">

                <!-- ===== Mode selector ===== -->
                <div class="mb-2">
                    <span class="extra-small fw-bold text-uppercase tracking-wider text-secondary font-geist">Pilih Jenis Transaksi</span>
                </div>
                <div class="row g-3 mb-4">
                    @foreach ([
                        ['key' => 'material', 'title' => 'Pakai Material', 'desc' => 'Catat konsumsi material per unit'],
                        ['key' => 'tool', 'title' => 'Pinjam Alat', 'desc' => 'Keluarkan alat ke unit rumah'],
                        ['key' => 'return', 'title' => 'Kembalikan Alat', 'desc' => 'Terima alat kembali ke gudang'],
                    ] as $mode)
                        <div class="col-md-4">
                            <button
                                type="button"
                                @click="activeTab = '{{ $mode['key'] }}'"
                                class="btn w-100 h-100 text-start p-3 rounded-4 border transition-all"
                                :class="activeTab === '{{ $mode['key'] }}'
                                    ? 'btn-success shadow-sm'
                                    : 'bg-body-tertiary border-secondary-subtle text-body'"
                            >
                                <span class="d-block fw-bold font-outfit" :class="activeTab === '{{ $mode['key'] }}' ? 'text-white' : 'text-body'">{{ $mode['title'] }}</span>
                                <span class="d-block extra-small" :class="activeTab === '{{ $mode['key'] }}' ? 'text-white-50' : 'text-secondary'">{{ $mode['desc'] }}</span>
                            </button>
                        </div>
                    @endforeach
                </div>

                <!-- ===== House selection ===== -->
        <div class="mb-2">
            <span class="extra-small fw-bold text-uppercase tracking-wider text-secondary font-geist">Pilih Unit Rumah</span>
        </div>
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-body-tertiary">
            <button type="button" @click="pickerOpen = !pickerOpen" class="btn text-start w-100 p-3 p-md-4 d-flex align-items-center justify-content-between gap-3 border-0">
                <div class="overflow-hidden">
                    <span class="d-block fw-bold font-outfit text-body">Unit Rumah Tujuan</span>
                    @if (count($house_ids) === 0)
                        <span class="d-block extra-small text-secondary">Belum ada unit dipilih</span>
                    @else
                        <span class="d-block extra-small text-success fw-semibold font-mono text-truncate">
                            {{ $selectedHouses->take(6)->pluck('name')->join(', ') }}{{ count($house_ids) > 6 ? ' +' . (count($house_ids) - 6) . ' lainnya' : '' }}
                        </span>
                    @endif
                </div>
                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                    <span class="badge rounded-pill font-mono {{ count($house_ids) > 0 ? 'bg-success' : 'bg-secondary-subtle text-secondary' }}">
                        {{ count($house_ids) }}
                    </span>
                    <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor" class="text-secondary flex-shrink-0 transition-transform" :class="pickerOpen ? 'rotate-180' : ''" aria-hidden="true">
                        <path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/>
                    </svg>
                </div>
            </button>

            <div x-show="pickerOpen" x-collapse x-cloak>
                <div class="border-top p-3 p-md-4">
                    <!-- Block filter + search -->
                    <div class="row g-2 mb-3">
                        <div class="col-md-7">
                            <input type="text" x-model="search" class="form-control" placeholder="Cari unit (mis. A-01, Tipe 36)..." />
                        </div>
                        <div class="col-md-5">
                            <select x-model="blockFilter" class="form-select">
                                <option value="all">Semua blok ({{ $houses->count() }} unit)</option>
                                @foreach ($groupedHouses as $blockName => $blockUnits)
                                    <option value="{{ $blockName }}">{{ $blockName }} ({{ count($blockUnits) }} unit)</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Per-block quick toggles -->
                    <div class="d-flex flex-wrap gap-2 mb-3 pb-3 border-bottom">
                        @foreach ($groupedHouses as $blockName => $blockUnits)
                            @php
                                $blockIds = $blockUnits->pluck('id')->toArray();
                                $selectedCount = count(array_intersect($blockIds, $house_ids));
                                $allSelected = $selectedCount === count($blockIds);
                            @endphp
                            <button
                                type="button"
                                wire:key="blk-{{ $blockName }}"
                                wire:click="toggleBlock({{ json_encode($blockIds) }})"
                                x-show="blockFilter === 'all' || blockFilter === @js($blockName)"
                                class="btn btn-sm fw-semibold d-flex align-items-center gap-2 {{ $allSelected ? 'btn-success' : 'btn-outline-secondary' }}"
                            >
                                {{ $blockName }}
                                <span class="badge rounded-pill extra-small font-mono {{ $allSelected ? 'bg-white text-success' : 'bg-secondary-subtle text-secondary' }}">
                                    {{ $selectedCount }}/{{ count($blockUnits) }}
                                </span>
                            </button>
                        @endforeach
                    </div>

                    <!-- Unit chips -->
                    <div class="d-flex flex-wrap gap-2" style="max-height: 320px; overflow-y: auto;">
                        @foreach ($groupedHouses as $blockName => $blockUnits)
                            @foreach ($blockUnits as $unit)
                                @php $isSelected = in_array($unit->id, $house_ids); @endphp
                                <button
                                    type="button"
                                    wire:key="unit-{{ $unit->id }}"
                                    wire:click="toggleHouse({{ $unit->id }})"
                                    x-show="(blockFilter === 'all' || blockFilter === @js($blockName))
                                        && (search === '' || @js(strtolower($unit->name . ' ' . $unit->type)).includes(search.toLowerCase()))"
                                    class="btn btn-sm font-mono text-start px-3 py-2 {{ $isSelected ? 'btn-success' : 'btn-outline-secondary' }}"
                                >
                                    <span class="d-block fw-bold leading-tight">{{ $unit->name }}</span>
                                    <span class="d-block extra-small opacity-75">{{ $unit->type }}</span>
                                </button>
                            @endforeach
                        @endforeach
                    </div>
                </div>
            </div>

            @error('house_ids')
                <div class="px-3 px-md-4 pb-3"><span class="text-danger small fw-semibold">{{ $message }}</span></div>
            @enderror
        </div>

        <!-- ===== Details ===== -->
        <div class="mb-2">
            <span class="extra-small fw-bold text-uppercase tracking-wider text-secondary font-geist">Isi Detail Transaksi</span>
        </div>
        <div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 bg-body-tertiary">

            <!-- ---- Material ---- -->
            <div x-show="activeTab === 'material'" x-cloak>
                <h6 class="fw-bold font-outfit text-body mb-3">Detail Pemakaian Material</h6>

                <div class="row g-3 align-items-start">
                    <div class="col-lg-6">
                        <div class="d-flex align-items-center justify-content-between mb-1" style="min-height: 21px;">
                            <label class="form-label fw-semibold mb-0">Material</label>
                            <template x-if="mat">
                                <span class="extra-small font-mono text-secondary">
                                    Stok: <strong class="text-body" x-text="mat.stock + ' ' + mat.unit"></strong>
                                </span>
                            </template>
                        </div>
                        <div class="position-relative" @click.outside="matPickerOpen = false">
                            <button
                                type="button"
                                class="form-select text-start d-flex align-items-center justify-content-between pe-3"
                                @click="matPickerOpen = !matPickerOpen"
                            >
                                <span class="text-truncate me-2" :class="mat ? 'text-body fw-semibold' : 'text-secondary'">
                                    <template x-if="mat">
                                        <span x-text="mat.name + ' — ' + rp(mat.unit_price) + ' / ' + mat.unit"></span>
                                    </template>
                                    <template x-if="!mat">
                                        <span>— Pilih material —</span>
                                    </template>
                                </span>
                                <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor" class="text-secondary flex-shrink-0 transition-transform" :class="matPickerOpen ? 'rotate-180' : ''" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/>
                                </svg>
                            </button>

                            <div
                                x-show="matPickerOpen"
                                x-cloak
                                class="card shadow-lg border rounded-3 position-absolute w-100 mt-1 bg-body overflow-hidden"
                                style="max-height: 280px; z-index: 1050;"
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
                                <div class="p-1.5" style="max-height: 220px; overflow-y: auto; -webkit-overflow-scrolling: touch;">
                                    @foreach ($materials as $m)
                                        <button
                                            type="button"
                                            class="dropdown-item rounded-2 py-2 px-3 text-start w-100"
                                            :class="$wire.material_id == {{ $m->id }} ? 'active bg-success text-white' : ''"
                                            x-show="matSearch === '' || @js(strtolower($m->name . ' ' . $m->unit)).includes(matSearch.toLowerCase())"
                                            @click="$wire.material_id = '{{ $m->id }}'; matPickerOpen = false; matSearch = ''"
                                        >
                                            <div class="fw-semibold text-truncate">{{ $m->name }}</div>
                                            <div class="d-flex align-items-center justify-content-between extra-small opacity-75 font-mono mt-0.5">
                                                <span>Rp {{ number_format($m->unit_price, 0, ',', '.') }} / {{ $m->unit }}</span>
                                                <span>stok: {{ $m->stock }}</span>
                                            </div>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @error('material_id') <span class="text-danger small fw-semibold">{{ $message }}</span> @enderror
                    </div>

                    <div class="col-lg-3 col-6">
                        <div class="d-flex align-items-center justify-content-between mb-1" style="min-height: 21px;">
                            <label class="form-label fw-semibold mb-0">Qty / unit</label>
                            <template x-if="mat">
                                <span class="extra-small font-mono text-secondary" :class="mat.stock < totalQty ? 'text-danger fw-bold' : ''">
                                    Maks: <span x-text="mat.stock"></span>
                                </span>
                            </template>
                        </div>
                        <div class="input-group">
                            <input type="number" step="0.01" wire:model.live="material_quantity" class="form-control font-mono" placeholder="0.00" />
                            <span class="input-group-text font-mono text-secondary small" x-text="mat ? mat.unit : 'unit'"></span>
                        </div>
                        @error('material_quantity') <span class="text-danger small fw-semibold">{{ $message }}</span> @enderror
                    </div>

                    <div class="col-lg-3 col-6">
                        <div class="d-flex align-items-center justify-content-between mb-1" style="min-height: 21px;">
                            <label class="form-label fw-semibold mb-0">Waktu</label>
                        </div>
                        <input type="datetime-local" wire:model="usage_date" class="form-control" />
                        @error('usage_date') <span class="text-danger small fw-semibold">{{ $message }}</span> @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Peruntukkan</label>
                        <div class="position-relative" @click.outside="matNotePickerOpen = false">
                            <div class="position-relative d-flex align-items-center">
                                <input
                                    type="text"
                                    wire:model="material_notes"
                                    class="form-control pe-5"
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
                                    <template x-for="opt in peruntukkanOpts.filter(o => !$wire.material_notes || o.toLowerCase().includes($wire.material_notes.toLowerCase()))" :key="opt">
                                        <button
                                            type="button"
                                            class="dropdown-item rounded-2 py-2 px-3 text-start w-100 d-flex align-items-center justify-content-between"
                                            :class="$wire.material_notes === opt ? 'active bg-success text-white' : ''"
                                            @click="$wire.material_notes = opt; matNotePickerOpen = false"
                                        >
                                            <span class="fw-medium text-truncate" x-text="opt"></span>
                                        </button>
                                    </template>
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

                    <!-- Proof Image Input -->
                    <div class="col-lg-6">
                        <label class="form-label fw-semibold d-inline-flex align-items-center gap-1">
                            <svg width="14" height="14" fill="currentColor"><use href="#i-camera"/></svg> Foto Bukti Pengiriman (Opsional)
                        </label>
                        <input type="file" wire:model="proof_image" class="form-control" accept="image/*" />
                        <div class="extra-small text-secondary mt-1">Ambil atau unggah foto bukti saat material tiba di lokasi unit rumah.</div>
                        @error('proof_image') <span class="text-danger small fw-semibold d-block mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <!-- ---- Tool checkout ---- -->
            <div x-show="activeTab === 'tool'" x-cloak>
                <h6 class="fw-bold font-outfit text-body mb-3">Detail Peminjaman Alat</h6>
                <div class="row g-3 align-items-start">
                    <div class="col-lg-6">
                        <div class="d-flex align-items-center justify-content-between mb-1" style="min-height: 21px;">
                            <label class="form-label fw-semibold mb-0">Alat Kerja</label>
                            <template x-if="tool">
                                <span class="extra-small font-mono text-secondary">
                                    Tersedia: <strong class="text-body" x-text="tool.available_qty + ' unit'"></strong>
                                </span>
                            </template>
                        </div>
                        <div class="position-relative" @click.outside="toolPickerOpen = false">
                            <button
                                type="button"
                                class="form-select text-start d-flex align-items-center justify-content-between pe-3"
                                @click="toolPickerOpen = !toolPickerOpen"
                            >
                                <span class="text-truncate me-2" :class="tool ? 'text-body fw-semibold' : 'text-secondary'">
                                    <template x-if="tool">
                                        <span x-text="tool.name + ' (' + tool.code + ')'"></span>
                                    </template>
                                    <template x-if="!tool">
                                        <span>— Pilih alat —</span>
                                    </template>
                                </span>
                                <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor" class="text-secondary flex-shrink-0 transition-transform" :class="toolPickerOpen ? 'rotate-180' : ''" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/>
                                </svg>
                            </button>

                            <div
                                x-show="toolPickerOpen"
                                x-cloak
                                class="card shadow-lg border rounded-3 position-absolute w-100 mt-1 bg-body overflow-hidden"
                                style="max-height: 280px; z-index: 1050;"
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
                                <div class="p-1.5" style="max-height: 220px; overflow-y: auto; -webkit-overflow-scrolling: touch;">
                                    @foreach ($tools as $t)
                                        <button
                                            type="button"
                                            class="dropdown-item rounded-2 py-2 px-3 text-start w-100"
                                            :class="$wire.tool_id == {{ $t->id }} ? 'active bg-success text-white' : ''"
                                            x-show="toolSearch === '' || @js(strtolower($t->name . ' ' . $t->code)).includes(toolSearch.toLowerCase())"
                                            @click="$wire.tool_id = '{{ $t->id }}'; toolPickerOpen = false; toolSearch = ''"
                                        >
                                            <div class="fw-semibold text-truncate">{{ $t->name }}</div>
                                            <div class="d-flex align-items-center justify-content-between extra-small opacity-75 font-mono mt-0.5">
                                                <span>{{ $t->code }}</span>
                                                <span>sisa: {{ $t->available_qty }}</span>
                                            </div>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @error('tool_id') <span class="text-danger small fw-semibold">{{ $message }}</span> @enderror
                    </div>

                    <div class="col-lg-3 col-6">
                        <div class="d-flex align-items-center justify-content-between mb-1" style="min-height: 21px;">
                            <label class="form-label fw-semibold mb-0">Qty / unit</label>
                            <template x-if="tool">
                                <span class="extra-small font-mono text-secondary" :class="tool.available_qty < totalTools ? 'text-danger fw-bold' : ''">
                                    Maks: <span x-text="tool.available_qty"></span>
                                </span>
                            </template>
                        </div>
                        <div class="input-group">
                            <input type="number" min="1" wire:model.live="tool_quantity" class="form-control font-mono" />
                            <span class="input-group-text font-mono text-secondary small">unit</span>
                        </div>
                        @error('tool_quantity') <span class="text-danger small fw-semibold">{{ $message }}</span> @enderror
                    </div>

                    <div class="col-lg-3 col-6">
                        <div class="d-flex align-items-center justify-content-between mb-1" style="min-height: 21px;">
                            <label class="form-label fw-semibold mb-0">Waktu</label>
                        </div>
                        <input type="datetime-local" wire:model="checkout_date" class="form-control" />
                        @error('checkout_date') <span class="text-danger small fw-semibold">{{ $message }}</span> @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Peruntukkan</label>
                        <div class="position-relative" @click.outside="toolNotePickerOpen = false">
                            <div class="position-relative d-flex align-items-center">
                                <input
                                    type="text"
                                    wire:model="tool_notes"
                                    class="form-control pe-5"
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
                                    <template x-for="opt in peruntukkanOpts.filter(o => !$wire.tool_notes || o.toLowerCase().includes($wire.tool_notes.toLowerCase()))" :key="opt">
                                        <button
                                            type="button"
                                            class="dropdown-item rounded-2 py-2 px-3 text-start w-100 d-flex align-items-center justify-content-between"
                                            :class="$wire.tool_notes === opt ? 'active bg-success text-white' : ''"
                                            @click="$wire.tool_notes = opt; toolNotePickerOpen = false"
                                        >
                                            <span class="fw-medium text-truncate" x-text="opt"></span>
                                        </button>
                                    </template>
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

                    <!-- Proof Image Input -->
                    <div class="col-lg-6">
                        <label class="form-label fw-semibold d-inline-flex align-items-center gap-1">
                            <svg width="14" height="14" fill="currentColor"><use href="#i-camera"/></svg> Foto Bukti Pengiriman (Opsional)
                        </label>
                        <input type="file" wire:model="proof_image" class="form-control" accept="image/*" />
                        <div class="extra-small text-secondary mt-1">Ambil atau unggah foto bukti saat alat tiba di lokasi unit rumah.</div>
                        @error('proof_image') <span class="text-danger small fw-semibold d-block mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div x-show="toolShortfall < 0" x-cloak class="alert alert-danger py-2 small fw-semibold mt-3 mb-0">
                    Jumlah melebihi stok tersedia sebanyak <span x-text="Math.abs(toolShortfall)"></span> unit.
                </div>
            </div>

            <!-- ---- Tool return ---- -->
            <div x-show="activeTab === 'return'" x-cloak>
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                    <h6 class="fw-bold font-outfit text-body mb-0">Alat Sedang Dipinjam</h6>
                    @if (!$activeUsages->isEmpty())
                        <span class="badge bg-secondary-subtle text-secondary rounded-pill font-mono extra-small">
                            {{ $activeUsages->count() }} peminjaman aktif
                        </span>
                    @endif
                </div>

                @if (empty($house_ids))
                    <div class="text-center py-5">
                        <p class="fw-semibold text-body mb-1">Pilih unit rumah terlebih dahulu</p>
                        <p class="small text-secondary mb-0">Buka panel unit rumah di atas dan pilih minimal satu unit.</p>
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
                                class="border rounded-3 p-3 bg-body"
                                wire:key="ret-{{ $usage->id }}"
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
                                            <label class="extra-small fw-bold text-success text-uppercase">Baik</label>
                                            <input type="number" min="0" max="{{ $usage->quantity }}" wire:model.live="returnSelections.{{ $usage->id }}.qty_normal" class="form-control form-control-sm font-mono" />
                                        </div>
                                        <div class="col-4">
                                            <label class="extra-small fw-bold text-warning text-uppercase">Rusak</label>
                                            <input type="number" min="0" max="{{ $usage->quantity }}" wire:model.live="returnSelections.{{ $usage->id }}.qty_broken" class="form-control form-control-sm font-mono" />
                                        </div>
                                        <div class="col-4">
                                            <label class="extra-small fw-bold text-danger text-uppercase">Hilang</label>
                                            <input type="number" min="0" max="{{ $usage->quantity }}" wire:model.live="returnSelections.{{ $usage->id }}.qty_lost" class="form-control form-control-sm font-mono" />
                                        </div>
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
    </div> <!-- /col-lg-7 col-xl-8 (Left Form Column) -->

    <!-- Right Receipt Column (col-lg-5 col-xl-4) -->
    <div class="col-lg-5 col-xl-4">
        <!-- Section label aligned with Pilih Jenis Transaksi -->
        <div class="mb-2">
            <span class="extra-small fw-bold text-uppercase tracking-wider text-secondary font-geist">Detail Transaksi</span>
        </div>

        <div class="sticky-top" style="top: 1.5rem; z-index: 1020;">
            <!-- Authentic Thermal POS Receipt Card -->
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden position-relative" style="background-color: var(--bs-tertiary-bg); border: 1px solid var(--bs-border-color) !important;">
                
                <!-- Receipt top saw-tooth / dashed border header -->
                <div class="w-100 py-1" style="background: repeating-linear-gradient(90deg, var(--bs-border-color), var(--bs-border-color) 6px, transparent 6px, transparent 12px); height: 2px;"></div>

                <div class="card-body p-4 font-mono">
                    <!-- Receipt Header -->
                    <div class="text-center pb-3 mb-3 border-bottom border-dashed" style="border-color: var(--bs-border-color) !important;">
                        <h5 class="font-outfit fw-black text-body mb-0 tracking-wider">D'ROYAL VILLAGE</h5>
                        <div class="extra-small text-secondary mt-1.5 d-flex align-items-center justify-content-center gap-2">
                            <span>{{ now()->format('d/m/Y H:i') }}</span>
                            <span>&bull;</span>
                            <span class="fw-bold" :class="activeTab === 'material' ? 'text-success' : (activeTab === 'tool' ? 'text-primary' : 'text-warning')" x-text="activeTab === 'material' ? 'MATERIAL' : (activeTab === 'tool' ? 'ALAT' : 'PENGEMBALIAN')"></span>
                        </div>
                    </div>

                    <!-- Selected Item Info Section -->
                    <div class="pb-2.5 mb-2.5 border-bottom border-dashed extra-small" style="border-color: var(--bs-border-color) !important;">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="text-secondary text-uppercase">ITEM</span>
                            <span class="fw-bold text-body text-truncate" style="max-width: 180px;" x-text="activeTab === 'material' ? (mat ? mat.name : '— Belum dipilih —') : (activeTab === 'tool' ? (tool ? tool.name : '— Belum dipilih —') : 'Pengembalian Alat')"></span>
                        </div>
                        <template x-if="activeTab === 'material' && mat">
                            <div class="d-flex align-items-center justify-content-between mb-1 text-secondary">
                                <span>HARGA SATUAN</span>
                                <span class="text-body fw-semibold" x-text="rp(mat.unit_price) + ' / ' + mat.unit"></span>
                            </div>
                        </template>
                        <template x-if="activeTab === 'material' && mat">
                            <div class="d-flex align-items-center justify-content-between text-secondary">
                                <span>STOK GUDANG</span>
                                <span :class="(mat.stock - totalQty) < 0 ? 'text-danger fw-bold' : 'text-success fw-bold'" x-text="(mat.stock - totalQty) + ' ' + mat.unit"></span>
                            </div>
                        </template>
                        <template x-if="activeTab === 'tool' && tool">
                            <div class="d-flex align-items-center justify-content-between mb-1 text-secondary">
                                <span>KODE ALAT</span>
                                <span class="text-body fw-semibold" x-text="tool.code"></span>
                            </div>
                        </template>
                        <template x-if="activeTab === 'tool' && tool">
                            <div class="d-flex align-items-center justify-content-between text-secondary">
                                <span>SISA ALAT</span>
                                <span :class="toolShortfall < 0 ? 'text-danger fw-bold' : 'text-success fw-bold'" x-text="(tool.available_qty - totalTools) + ' unit'"></span>
                            </div>
                        </template>
                    </div>

                    <!-- Column Header -->
                    <div class="d-flex align-items-center justify-content-between extra-small text-secondary fw-bold text-uppercase pb-1.5 mb-1.5 border-bottom border-dashed" style="border-color: var(--bs-border-color) !important;">
                        <span>QTY // TUJUAN</span>
                        <span>SUBTOTAL</span>
                    </div>

                    <!-- Itemized Stacked List (The Receipt Body) -->
                    <div class="mb-3" style="min-height: 90px;">
                        @if ($selectedHouses->isEmpty())
                            <div class="py-4 text-center text-secondary extra-small fst-italic">
                                [ Pilih unit rumah di formulir kiri ]
                            </div>
                        @else
                            <!-- MATERIAL STACKED LIST -->
                            <div x-show="activeTab === 'material'" class="vstack gap-2" style="max-height: 220px; overflow-y: auto; overscroll-behavior: contain;">
                                @foreach ($selectedHouses as $h)
                                    <div class="d-flex align-items-center justify-content-between extra-small">
                                        <div class="d-flex align-items-center gap-2 text-truncate me-2">
                                            <span class="fw-bold text-warning" x-text="matQty + (mat ? (' ' + mat.unit) : 'x')"></span>
                                            <span class="text-body fw-semibold text-truncate">{{ $h->name }}</span>
                                        </div>
                                        <div class="text-end fw-bold text-body flex-shrink-0" x-text="mat ? rp(matQty * parseFloat(mat.unit_price)) : '—'"></div>
                                    </div>
                                @endforeach
                            </div>

                            <!-- TOOL STACKED LIST -->
                            <div x-show="activeTab === 'tool'" class="vstack gap-2" style="max-height: 220px; overflow-y: auto; overscroll-behavior: contain;" x-cloak>
                                @foreach ($selectedHouses as $h)
                                    <div class="d-flex align-items-center justify-content-between extra-small">
                                        <div class="d-flex align-items-center gap-2 text-truncate me-2">
                                            <span class="fw-bold text-warning" x-text="toolQty + ' unit'"></span>
                                            <span class="text-body fw-semibold text-truncate">{{ $h->name }}</span>
                                        </div>
                                        <div class="text-end text-secondary fw-semibold flex-shrink-0" x-text="tool ? tool.code : '—'"></div>
                                    </div>
                                @endforeach
                            </div>

                            <!-- RETURN STACKED LIST -->
                            <div x-show="activeTab === 'return'" class="text-center py-3 extra-small" x-cloak>
                                <div class="fs-4 fw-black text-success" x-text="returnCount"></div>
                                <div class="text-secondary mt-1">Transaksi alat dikembalikan</div>
                            </div>
                        @endif
                    </div>

                    <!-- Receipt Summary Calculations -->
                    <div class="pt-2 border-top border-dashed extra-small" style="border-color: var(--bs-border-color) !important;">
                        <div class="d-flex align-items-center justify-content-between text-secondary mb-1">
                            <span>TOTAL TARGET</span>
                            <span class="fw-bold text-body" x-text="houseCount + ' Unit'"></span>
                        </div>
                        <template x-if="activeTab === 'material'">
                            <div class="d-flex align-items-center justify-content-between text-secondary mb-2">
                                <span>TOTAL KUANTITAS</span>
                                <span class="fw-bold text-warning" x-text="totalQty + (mat ? (' ' + mat.unit) : '')"></span>
                            </div>
                        </template>
                        <template x-if="activeTab === 'tool'">
                            <div class="d-flex align-items-center justify-content-between text-secondary mb-2">
                                <span>TOTAL PINJAM</span>
                                <span class="fw-bold text-warning" x-text="totalTools + ' Unit'"></span>
                            </div>
                        </template>

                        <!-- Total Amount Line -->
                        <div class="d-flex align-items-baseline justify-content-between pt-2.5 pb-1 border-top border-dashed" style="border-color: var(--bs-border-color) !important;">
                            <span class="fw-black text-uppercase text-body small tracking-wider">TOTAL AMOUNT</span>
                            <template x-if="activeTab === 'material'">
                                <span class="fs-4 fw-black text-success lh-1" x-text="matReady ? rp(totalCost) : 'Rp 0'"></span>
                            </template>
                            <template x-if="activeTab === 'tool'">
                                <span class="fs-4 fw-black lh-1" :class="toolShortfall < 0 ? 'text-danger' : 'text-success'" x-text="toolReady ? totalTools + ' Unit' : '0 Unit'"></span>
                            </template>
                            <template x-if="activeTab === 'return'">
                                <span class="fs-4 fw-black text-success lh-1" x-text="returnCount + ' Alat'"></span>
                            </template>
                        </div>
                    </div>

                    <!-- Receipt Actions -->
                    <div class="d-flex gap-2 pt-3 mt-2 border-top border-dashed" style="border-color: var(--bs-border-color) !important;">
                        <template x-if="activeTab === 'material'">
                            <div class="d-flex gap-2 w-100">
                                <button type="button" wire:click="resetMaterialForm" class="btn btn-outline-secondary btn-sm px-3 fw-semibold font-mono">Reset</button>
                                <button type="button" wire:click="showMaterialConfirmationModal" class="btn btn-success btn-sm fw-bold px-3 shadow-sm flex-grow-1 font-mono" :disabled="!matReady">Simpan Alokasi &raquo;</button>
                            </div>
                        </template>
                        <template x-if="activeTab === 'tool'">
                            <div class="d-flex gap-2 w-100">
                                <button type="button" wire:click="resetToolForm" class="btn btn-outline-secondary btn-sm px-3 fw-semibold font-mono">Reset</button>
                                <button type="button" wire:click="showToolConfirmationModal" class="btn btn-success btn-sm fw-bold px-3 shadow-sm flex-grow-1 font-mono" :disabled="!toolReady || toolShortfall < 0">Simpan Peminjaman &raquo;</button>
                            </div>
                        </template>
                        <template x-if="activeTab === 'return'">
                            <div class="d-flex gap-2 w-100">
                                <button type="button" wire:click="resetReturnForm" class="btn btn-outline-secondary btn-sm px-3 fw-semibold font-mono">Reset</button>
                                <button type="button" wire:click="showReturnConfirmationModal" class="btn btn-success btn-sm fw-bold px-3 shadow-sm flex-grow-1 font-mono" :disabled="returnCount === 0">Simpan Pengembalian &raquo;</button>
                            </div>
                        </template>
                    </div>

                </div>

                <!-- Receipt bottom saw-tooth / dashed border footer -->
                <div class="w-100 py-1" style="background: repeating-linear-gradient(90deg, var(--bs-border-color), var(--bs-border-color) 6px, transparent 6px, transparent 12px); height: 2px;"></div>
            </div>
        </div>
    </div> <!-- /col-lg-5 col-xl-4 (Right Receipt Column) -->
</div> <!-- /row g-4 align-items-start -->

    <!-- ===== Confirmation modals ===== -->
    @if($showMaterialConfirmation)
    @teleport('body')
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title font-outfit fw-bold">Konfirmasi Alokasi Material</h5>
                    <button type="button" class="btn-close" wire:click="$set('showMaterialConfirmation', false)"></button>
                </div>
                <div class="modal-body py-4">
                    <dl class="row g-2 small mb-0">
                        <dt class="col-5 text-secondary fw-semibold">Unit rumah</dt>
                        <dd class="col-7 mb-0 fw-bold">{{ $materialConfirmationData['houseCount'] ?? 0 }} rumah</dd>
                        <dt class="col-5 text-secondary fw-semibold">Material</dt>
                        <dd class="col-7 mb-0 fw-bold">{{ $materialConfirmationData['materialName'] ?? '-' }}</dd>
                        <dt class="col-5 text-secondary fw-semibold">Total qty</dt>
                        <dd class="col-7 mb-0 fw-bold font-mono">{{ $materialConfirmationData['totalQuantity'] ?? 0 }} {{ $materialConfirmationData['materialUnit'] ?? '' }}</dd>
                        <dt class="col-5 text-secondary fw-semibold">Total biaya</dt>
                        <dd class="col-7 mb-0 fw-bold text-success font-mono">Rp {{ number_format($materialConfirmationData['totalCost'] ?? 0, 0, ',', '.') }}</dd>
                    </dl>
                </div>
                <div class="modal-footer border-top bg-body-tertiary rounded-bottom-4">
                    <button type="button" class="btn btn-secondary fw-semibold" wire:click="$set('showMaterialConfirmation', false)">Batal</button>
                    <button type="button" class="btn btn-success fw-semibold" wire:click="saveMaterial" wire:loading.attr="disabled" wire:target="saveMaterial">Ya, Simpan</button>
                </div>
            </div>
        </div>
    </div>
    @endteleport
    @endif

    @if($showToolConfirmation)
    @teleport('body')
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title font-outfit fw-bold">Konfirmasi Peminjaman Alat</h5>
                    <button type="button" class="btn-close" wire:click="$set('showToolConfirmation', false)"></button>
                </div>
                <div class="modal-body py-4">
                    <dl class="row g-2 small mb-0">
                        <dt class="col-5 text-secondary fw-semibold">Unit rumah</dt>
                        <dd class="col-7 mb-0 fw-bold">{{ $toolConfirmationData['houseCount'] ?? 0 }} rumah</dd>
                        <dt class="col-5 text-secondary fw-semibold">Alat</dt>
                        <dd class="col-7 mb-0 fw-bold">{{ $toolConfirmationData['toolName'] ?? '-' }}</dd>
                        <dt class="col-5 text-secondary fw-semibold">Total dipinjam</dt>
                        <dd class="col-7 mb-0 fw-bold font-mono">{{ $toolConfirmationData['totalQuantity'] ?? 0 }} unit</dd>
                        <dt class="col-5 text-secondary fw-semibold">Sisa stok</dt>
                        <dd class="col-7 mb-0 fw-bold font-mono">{{ $toolConfirmationData['availableAfter'] ?? 0 }} unit</dd>
                    </dl>
                </div>
                <div class="modal-footer border-top bg-body-tertiary rounded-bottom-4">
                    <button type="button" class="btn btn-secondary fw-semibold" wire:click="$set('showToolConfirmation', false)">Batal</button>
                    <button type="button" class="btn btn-success fw-semibold" wire:click="saveTool" wire:loading.attr="disabled" wire:target="saveTool">Ya, Simpan</button>
                </div>
            </div>
        </div>
    </div>
    @endteleport
    @endif

    @if($showReturnConfirmation)
    @teleport('body')
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title font-outfit fw-bold">Konfirmasi Pengembalian Alat</h5>
                    <button type="button" class="btn-close" wire:click="$set('showReturnConfirmation', false)"></button>
                </div>
                <div class="modal-body py-4">
                    <div class="vstack gap-2">
                        @foreach ($returnConfirmationData as $item)
                            <div class="d-flex justify-content-between align-items-center border-bottom pb-2">
                                <div>
                                    <div class="fw-bold small">{{ $item['tool_name'] }}</div>
                                    <div class="extra-small text-secondary font-mono">{{ $item['house_name'] }}</div>
                                </div>
                                <div class="text-end extra-small font-mono">
                                    <div class="fw-bold">{{ $item['return_qty'] }} / {{ $item['quantity'] }} unit</div>
                                    <div class="text-secondary">
                                        Baik {{ $item['qty_normal'] }} &middot; Rusak {{ $item['qty_broken'] }} &middot; Hilang {{ $item['qty_lost'] }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer border-top bg-body-tertiary rounded-bottom-4">
                    <button type="button" class="btn btn-secondary fw-semibold" wire:click="$set('showReturnConfirmation', false)">Batal</button>
                    <button type="button" class="btn btn-success fw-semibold" wire:click="saveReturn" wire:loading.attr="disabled" wire:target="saveReturn">Ya, Kembalikan</button>
                </div>
            </div>
        </div>
    </div>
    @endteleport
    @endif
</div>

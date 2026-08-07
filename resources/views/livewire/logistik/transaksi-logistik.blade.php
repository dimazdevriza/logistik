<div
    x-data="{
        activeTab: @entangle('activeTab').live,
        pickerOpen: false,
        search: '',
        blockFilter: 'all',
        materials: @js($materials->keyBy('id')->map(fn($m) => ['unit_price' => $m->unit_price, 'unit' => $m->unit, 'name' => $m->name])),
        tools: @js($tools->keyBy('id')->map(fn($t) => ['name' => $t->name, 'available_qty' => $t->available_qty, 'code' => $t->code])),
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

    <div class="container-fluid p-0" style="padding-bottom: 6rem !important;">

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

        <!-- ===== Mode selector ===== -->
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
                            : 'btn-outline-secondary bg-body-tertiary'"
                    >
                        <span class="d-block fw-bold font-outfit">{{ $mode['title'] }}</span>
                        <span class="d-block extra-small opacity-75">{{ $mode['desc'] }}</span>
                    </button>
                </div>
            @endforeach
        </div>

        <!-- ===== House selection ===== -->
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
                    <span class="text-secondary extra-small" x-text="pickerOpen ? '▴' : '▾'"></span>
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
        <div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 bg-body-tertiary">

            <!-- ---- Material ---- -->
            <div x-show="activeTab === 'material'" x-cloak>
                <h6 class="fw-bold font-outfit text-body mb-3">Detail Pemakaian Material</h6>

                <div class="row g-3">
                    <div class="col-lg-6">
                        <label class="form-label fw-semibold">Material</label>
                        <select wire:model.live="material_id" class="form-select">
                            <option value="">— Pilih material —</option>
                            @foreach ($materials as $m)
                                <option value="{{ $m->id }}">
                                    {{ $m->name }} — Rp {{ number_format($m->unit_price, 0, ',', '.') }} / {{ $m->unit }} (stok {{ $m->stock }})
                                </option>
                            @endforeach
                        </select>
                        @error('material_id') <span class="text-danger small fw-semibold">{{ $message }}</span> @enderror
                    </div>

                    <div class="col-lg-3 col-6">
                        <label class="form-label fw-semibold">Qty / unit</label>
                        <input type="number" step="0.01" wire:model.live="material_quantity" class="form-control font-mono" placeholder="0.00" />
                        @error('material_quantity') <span class="text-danger small fw-semibold">{{ $message }}</span> @enderror
                    </div>

                    <div class="col-lg-3 col-6">
                        <label class="form-label fw-semibold">Tanggal</label>
                        <input type="date" wire:model="usage_date" class="form-control" />
                        @error('usage_date') <span class="text-danger small fw-semibold">{{ $message }}</span> @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Catatan <span class="text-secondary fw-normal">(opsional)</span></label>
                        <textarea wire:model="material_notes" class="form-control" rows="2" placeholder="Keterangan penggunaan material"></textarea>
                    </div>
                </div>

                <!-- Inline breakdown -->
                <div x-show="matReady" x-cloak class="mt-4 pt-3 border-top">
                    <div class="row g-3 text-center">
                        <div class="col-6 col-md-3">
                            <div class="extra-small text-uppercase text-secondary fw-bold tracking-wider">Unit</div>
                            <div class="fw-black font-mono text-body" x-text="houseCount"></div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="extra-small text-uppercase text-secondary fw-bold tracking-wider">Total qty</div>
                            <div class="fw-black font-mono text-body" x-text="mat ? totalQty.toFixed(2) + ' ' + mat.unit : ''"></div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="extra-small text-uppercase text-secondary fw-bold tracking-wider">Harga satuan</div>
                            <div class="fw-black font-mono text-body" x-text="mat ? rp(mat.unit_price) : ''"></div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="extra-small text-uppercase text-secondary fw-bold tracking-wider">Total biaya</div>
                            <div class="fw-black font-mono text-success" x-text="rp(totalCost)"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ---- Tool checkout ---- -->
            <div x-show="activeTab === 'tool'" x-cloak>
                <h6 class="fw-bold font-outfit text-body mb-3">Detail Peminjaman Alat</h6>

                <div class="row g-3">
                    <div class="col-lg-6">
                        <label class="form-label fw-semibold">Alat</label>
                        <select wire:model.live="tool_id" class="form-select">
                            <option value="">— Pilih alat —</option>
                            @foreach ($tools as $t)
                                <option value="{{ $t->id }}">{{ $t->name }} — {{ $t->code }} (tersedia {{ $t->available_qty }})</option>
                            @endforeach
                        </select>
                        @error('tool_id') <span class="text-danger small fw-semibold">{{ $message }}</span> @enderror
                    </div>

                    <div class="col-lg-3 col-6">
                        <label class="form-label fw-semibold">Qty / unit</label>
                        <input type="number" min="1" wire:model.live="tool_quantity" class="form-control font-mono" />
                        @error('tool_quantity') <span class="text-danger small fw-semibold">{{ $message }}</span> @enderror
                    </div>

                    <div class="col-lg-3 col-6">
                        <label class="form-label fw-semibold">Tanggal pinjam</label>
                        <input type="date" wire:model="checkout_date" class="form-control" />
                        @error('checkout_date') <span class="text-danger small fw-semibold">{{ $message }}</span> @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Catatan <span class="text-secondary fw-normal">(opsional)</span></label>
                        <textarea wire:model="tool_notes" class="form-control" rows="2" placeholder="Keterangan peminjaman"></textarea>
                    </div>
                </div>

                <div x-show="toolReady" x-cloak class="mt-4 pt-3 border-top">
                    <div class="row g-3 text-center">
                        <div class="col-4">
                            <div class="extra-small text-uppercase text-secondary fw-bold tracking-wider">Unit</div>
                            <div class="fw-black font-mono text-body" x-text="houseCount"></div>
                        </div>
                        <div class="col-4">
                            <div class="extra-small text-uppercase text-secondary fw-bold tracking-wider">Total dipinjam</div>
                            <div class="fw-black font-mono" :class="toolShortfall < 0 ? 'text-danger' : 'text-body'" x-text="totalTools"></div>
                        </div>
                        <div class="col-4">
                            <div class="extra-small text-uppercase text-secondary fw-bold tracking-wider">Sisa stok</div>
                            <div class="fw-black font-mono" :class="toolShortfall < 0 ? 'text-danger' : 'text-success'" x-text="toolShortfall"></div>
                        </div>
                    </div>
                    <div x-show="toolShortfall < 0" x-cloak class="alert alert-danger py-2 small fw-semibold mt-3 mb-0">
                        Jumlah melebihi stok tersedia sebanyak <span x-text="Math.abs(toolShortfall)"></span> unit.
                    </div>
                </div>
            </div>

            <!-- ---- Tool return ---- -->
            <div x-show="activeTab === 'return'" x-cloak>
                <h6 class="fw-bold font-outfit text-body mb-3">Alat Sedang Dipinjam</h6>

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

                    <div class="vstack gap-2">
                        @foreach ($activeUsages as $usage)
                            <div class="border rounded-3 p-3 bg-body" wire:key="ret-{{ $usage->id }}">
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
                @endif
            </div>
        </div>
    </div>

    <!-- ===== Persistent action bar ===== -->
    <div class="position-sticky bottom-0 z-3 mt-3">
        <div class="card border-0 shadow-lg rounded-4 bg-dark text-white">
            <div class="card-body p-3 d-flex flex-wrap align-items-center justify-content-between gap-3">

                <div class="d-flex align-items-center gap-4">
                    <div>
                        <div class="extra-small text-uppercase text-secondary fw-bold tracking-wider">Unit</div>
                        <div class="fw-black font-mono lh-1" x-text="houseCount"></div>
                    </div>

                    <template x-if="activeTab === 'material'">
                        <div>
                            <div class="extra-small text-uppercase text-secondary fw-bold tracking-wider">Total biaya</div>
                            <div class="fs-5 fw-black font-mono text-success lh-1" x-text="matReady ? rp(totalCost) : '—'"></div>
                        </div>
                    </template>

                    <template x-if="activeTab === 'tool'">
                        <div>
                            <div class="extra-small text-uppercase text-secondary fw-bold tracking-wider">Total alat</div>
                            <div class="fs-5 fw-black font-mono lh-1" :class="toolShortfall < 0 ? 'text-danger' : 'text-success'" x-text="toolReady ? totalTools + ' unit' : '—'"></div>
                        </div>
                    </template>

                    <template x-if="activeTab === 'return'">
                        <div>
                            <div class="extra-small text-uppercase text-secondary fw-bold tracking-wider">Dipilih</div>
                            <div class="fs-5 fw-black font-mono text-success lh-1" x-text="returnCount + ' alat'"></div>
                        </div>
                    </template>
                </div>

                <div class="d-flex gap-2 ms-auto">
                    <template x-if="activeTab === 'material'">
                        <div class="d-flex gap-2">
                            <button type="button" wire:click="resetMaterialForm" class="btn btn-outline-light fw-semibold px-3">Reset</button>
                            <button type="button" wire:click="showMaterialConfirmationModal" class="btn btn-success fw-semibold px-4" :disabled="!matReady">Simpan Alokasi</button>
                        </div>
                    </template>
                    <template x-if="activeTab === 'tool'">
                        <div class="d-flex gap-2">
                            <button type="button" wire:click="resetToolForm" class="btn btn-outline-light fw-semibold px-3">Reset</button>
                            <button type="button" wire:click="showToolConfirmationModal" class="btn btn-success fw-semibold px-4" :disabled="!toolReady || toolShortfall < 0">Simpan Peminjaman</button>
                        </div>
                    </template>
                    <template x-if="activeTab === 'return'">
                        <div class="d-flex gap-2">
                            <button type="button" wire:click="resetReturnForm" class="btn btn-outline-light fw-semibold px-3">Reset</button>
                            <button type="button" wire:click="showReturnConfirmationModal" class="btn btn-success fw-semibold px-4" :disabled="returnCount === 0">Simpan Pengembalian</button>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== Confirmation modals ===== -->
    @if($showMaterialConfirmation)
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
    @endif

    @if($showToolConfirmation)
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
    @endif

    @if($showReturnConfirmation)
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
    @endif
</div>

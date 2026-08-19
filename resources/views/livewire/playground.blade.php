<div>
    <div class="container-fluid p-0">
        <!-- Playground Hero Header -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-body-tertiary">
            <div class="card-body p-4 p-md-5 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-2 text-uppercase mb-2 font-geist small d-inline-flex align-items-center gap-1">
                        <svg width="12" height="12" fill="currentColor"><use href="#i-gear"/></svg> Wadah Aset & Sandbox UI
                    </span>
                    <h1 class="display-5 fw-black text-body mb-2 font-outfit">
                        Playground <span class="text-success">Desain</span>
                    </h1>
                    <p class="text-secondary mb-0 max-w-xl">
                        Wadah utama penampungan aset UI, prototipe komponen, tabel, modal, dan eksperimen desain sebelum diterapkan ke seluruh sistem.
                    </p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-body border px-3 py-2 text-body font-mono">Rute: /admin/playground</span>
                </div>
            </div>
        </div>

        <!-- Playground Mode Tabs -->
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-body-tertiary mb-4">
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-sm d-inline-flex align-items-center gap-1 {{ $activeTab === 'components' ? 'btn-success' : 'btn-outline-secondary' }}" wire:click="$set('activeTab', 'components')">
                    <svg width="14" height="14" fill="currentColor"><use href="#i-tags"/></svg> Galeri Komponen UI
                </button>
                <button type="button" class="btn btn-sm d-inline-flex align-items-center gap-1 {{ $activeTab === 'modals' ? 'btn-success' : 'btn-outline-secondary' }}" wire:click="$set('activeTab', 'modals')">
                    <svg width="14" height="14" fill="currentColor"><use href="#i-box"/></svg> Modal & Pemilih
                </button>
                <button type="button" class="btn btn-sm d-inline-flex align-items-center gap-1 {{ $activeTab === 'experiment' ? 'btn-success' : 'btn-outline-secondary' }}" wire:click="$set('activeTab', 'experiment')">
                    <svg width="14" height="14" fill="currentColor"><use href="#i-gear"/></svg> Area Eksperimen Aktif
                </button>
            </div>
        </div>

        @if ($activeTab === 'components')
            <!-- Color Palette Showcase Card -->
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-body-tertiary mb-4">
                <h5 class="fw-bold font-outfit text-body mb-1">Palet Warna Resmi Brand D'Royal Village</h5>
                <p class="text-secondary extra-small mb-3">Referensi palet warna standar sesuai logo resmi perusahaan.</p>

                <div class="row row-cols-2 row-cols-md-3 row-cols-lg-5 g-3">
                    <div class="col">
                        <div class="p-3 rounded-3 text-white shadow-sm h-100 d-flex flex-column justify-content-between" style="background-color: var(--color-leaf-green);">
                            <div class="fw-bold font-outfit">Leaf Green</div>
                            <div class="font-mono extra-small opacity-90 mt-2">#1F9B3A</div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="p-3 rounded-3 text-dark shadow-sm h-100 d-flex flex-column justify-content-between" style="background-color: var(--color-lime-accent);">
                            <div class="fw-bold font-outfit">Lime Accent</div>
                            <div class="font-mono extra-small opacity-90 mt-2">#78C800</div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="p-3 rounded-3 text-white shadow-sm h-100 d-flex flex-column justify-content-between" style="background-color: var(--color-forest-deep);">
                            <div class="fw-bold font-outfit">Forest Deep</div>
                            <div class="font-mono extra-small opacity-90 mt-2">#136928</div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="p-3 rounded-3 text-dark shadow-sm h-100 d-flex flex-column justify-content-between" style="background-color: var(--color-gold-accent);">
                            <div class="fw-bold font-outfit">Royal Gold</div>
                            <div class="font-mono extra-small opacity-90 mt-2">#D4AF37</div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="p-3 rounded-3 bg-night-drunk text-white shadow-sm h-100 d-flex flex-column justify-content-between">
                            <div class="fw-bold font-outfit">Night Charcoal</div>
                            <div class="font-mono extra-small opacity-75 mt-2">#1B1B1B</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Component Library Section -->
            <div class="row g-4">
                <!-- Color & Status Badges -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-body-tertiary h-100">
                        <h5 class="fw-bold font-outfit text-body mb-3">Lencana Status & Chip</h5>
                        <div class="d-flex flex-wrap gap-2 mb-4">
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill">Selesai</span>
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-2 rounded-pill">Menunggu</span>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 rounded-pill">Ditolak</span>
                            <span class="badge bg-info-subtle text-info border border-info-subtle px-3 py-2 rounded-pill">Dalam Pengiriman</span>
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-3 py-2 rounded-pill">Draf</span>
                        </div>

                        <h5 class="fw-bold font-outfit text-body mb-3">Tombol & Aksi</h5>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-success fw-semibold px-3 py-2 rounded-3">Aksi Utama</button>
                            <button type="button" class="btn btn-outline-secondary fw-semibold px-3 py-2 rounded-3">Aksi Sekunder</button>
                            <button type="button" class="btn btn-danger fw-semibold px-3 py-2 rounded-3">Aksi Bahaya</button>
                        </div>
                    </div>
                </div>

                <!-- Card Variants -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-body-tertiary h-100">
                        <h5 class="fw-bold font-outfit text-body mb-3">Varian Kartu</h5>
                        <div class="card bg-body border rounded-3 p-3 mb-3">
                            <div class="fw-semibold text-body">Kartu Konten Standar</div>
                            <div class="text-secondary extra-small">Digunakan untuk item daftar standar dan kontainer formulir.</div>
                        </div>
                        <div class="card bg-success-subtle border-success-subtle rounded-3 p-3">
                            <div class="fw-semibold text-success">Kartu Ringkasan Utama</div>
                            <div class="text-success-emphasis extra-small">Digunakan untuk total biaya, kalkulasi stok, atau umpan balik sukses.</div>
                        </div>
                    </div>
                </div>
            </div>
        @elseif ($activeTab === 'modals')
            <div class="row g-4">
                <!-- Dropdown Chevron Component -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-body-tertiary h-100">
                        <h5 class="fw-bold font-outfit text-body mb-2">Pemilih Dropdown Standar</h5>
                        <p class="text-secondary small mb-3">Pemilih khusus dengan indikator rotasi SVG chevron.</p>

                        <div x-data="{ open: false }" class="position-relative">
                            <button type="button" class="btn form-select text-start w-100 d-flex align-items-center justify-content-between py-2.5 px-3 rounded-3 border" @click="open = !open">
                                <span class="text-body fw-medium">Pilih Salah Satu Options</span>
                                <svg width="14" height="14" fill="currentColor" class="text-secondary transition-transform" :class="open ? 'rotate-180' : ''">
                                    <path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/>
                                </svg>
                            </button>
                            <div x-show="open" @click.outside="open = false" x-cloak class="card shadow-lg border rounded-3 mt-1 p-2 bg-body position-absolute w-100 z-3">
                                <div class="dropdown-item rounded-2 py-2 px-3 cursor-pointer" @click="open = false">Opsi Material Pilihan A</div>
                                <div class="dropdown-item rounded-2 py-2 px-3 cursor-pointer" @click="open = false">Opsi Alat Pilihan B</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Confirmation Modal Component Showcase -->
                <div class="col-md-6" x-data="{ showConfirm: false, actionSuccess: false }">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-body-tertiary h-100">
                        <h5 class="fw-bold font-outfit text-body mb-2">Modal Konfirmasi Standar</h5>
                        <p class="text-secondary small mb-3">Dialog konfirmasi aksi penting untuk transaksi atau penghapusan data.</p>

                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <button type="button" class="btn btn-success fw-semibold px-3 py-2 rounded-3 d-inline-flex align-items-center gap-2" @click="showConfirm = true; actionSuccess = false">
                                <svg width="16" height="16" fill="currentColor"><use href="#i-check"/></svg>
                                Buka Modal Konfirmasi
                            </button>
                        </div>

                        <!-- Feedback Alert -->
                        <template x-if="actionSuccess">
                            <div class="alert alert-success d-flex align-items-center gap-2 mt-3 mb-0 rounded-3 py-2 px-3 extra-small">
                                <svg width="16" height="16" fill="currentColor"><use href="#i-check"/></svg>
                                <span>Aksi berhasil dikonfirmasi dan diproses!</span>
                            </div>
                        </template>
                    </div>

                    <!-- Reusable Confirmation Modal Backdrop -->
                    <template x-teleport="body">
                        <div x-show="showConfirm" x-cloak class="modal fade show d-block" style="background-color: rgba(0,0,0,0.5); z-index: 1070;" tabindex="-1" @click.self="showConfirm = false">
                            <div class="modal-dialog modal-dialog-centered" style="z-index: 1080;">
                                <div class="modal-content border-0 shadow-lg rounded-4 p-2">
                                    <div class="modal-header border-0 pb-0">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="p-2.5 rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                                                <svg width="22" height="22" fill="currentColor"><use href="#i-info"/></svg>
                                            </div>
                                            <div>
                                                <h5 class="modal-title font-outfit fw-bold text-body mb-0">Konfirmasi Aksi Sistem</h5>
                                                <p class="text-secondary extra-small mb-0">Apakah Anda yakin ingin memproses transaksi ini?</p>
                                            </div>
                                        </div>
                                        <button type="button" class="btn-close" @click="showConfirm = false" aria-label="Tutup"></button>
                                    </div>
                                    <div class="modal-body py-3">
                                        <div class="card bg-body-tertiary border rounded-3 p-3 mb-0">
                                            <div class="row g-2 extra-small">
                                                <div class="col-6 text-secondary">Unit Tujuan:</div>
                                                <div class="col-6 fw-bold text-body text-end">Blok A-01 (Tipe 36)</div>
                                                <div class="col-6 text-secondary">Material / Barang:</div>
                                                <div class="col-6 fw-bold text-body text-end">Semen Portland 50kg</div>
                                                <div class="col-6 text-secondary">Jumlah Alokasi:</div>
                                                <div class="col-6 fw-bold text-success text-end">10 Sak</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-0 pt-0 d-flex justify-content-end gap-2">
                                        <button type="button" class="btn btn-outline-secondary fw-semibold px-3 py-2 rounded-3" @click="showConfirm = false">Batal</button>
                                        <button type="button" class="btn btn-success fw-semibold px-3 py-2 rounded-3 d-inline-flex align-items-center gap-2" @click="showConfirm = false; actionSuccess = true">
                                            <svg width="16" height="16" fill="currentColor"><use href="#i-check"/></svg>
                                            Ya, Konfirmasi
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        @elseif ($activeTab === 'experiment')
            <!-- Premium Table Design Prototype -->
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-body-tertiary">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <h5 class="fw-bold font-outfit text-body mb-0">Tabel Transaksi Logistik</h5>
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 extra-small font-mono">{{ $rows->count() }} Records</span>
                        </div>
                        <p class="text-secondary extra-small mb-0">Prototype desain tabel modern dengan micro-interactions, kustomisasi status, dan filter interaktif.</p>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <div class="position-relative">
                            <input type="text" wire:model.live.debounce.250ms="search" class="form-control form-control-sm ps-4" placeholder="Cari data..." style="width: 220px;" />
                            <span class="position-absolute top-50 start-0 translate-middle-y ms-2 text-secondary pointer-events-none">
                                <svg width="12" height="12" fill="currentColor"><use href="#i-search"/></svg>
                            </span>
                        </div>
                        <button type="button" wire:click="$set('showFilterModal', true)" class="btn btn-sm {{ $activeFiltersCount > 0 ? 'btn-success' : 'btn-outline-secondary' }} d-inline-flex align-items-center gap-1 font-semibold rounded-3 position-relative">
                            <svg width="12" height="12" fill="currentColor"><use href="#i-tags"/></svg> Filter
                            @if ($activeFiltersCount > 0)
                                <span class="badge bg-white text-success rounded-circle ms-1 px-1.5 py-0.5 extra-small">{{ $activeFiltersCount }}</span>
                            @endif
                        </button>
                    </div>
                </div>

                <!-- Active Filter Badges Strip -->
                @if ($activeFiltersCount > 0 || $search)
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-3 p-2.5 bg-body rounded-3 border">
                        <span class="extra-small font-semibold text-secondary">Filter Aktif:</span>
                        @if ($search)
                            <span class="badge bg-secondary-subtle text-body border rounded-pill px-2.5 py-1 extra-small">Cari: "{{ $search }}"</span>
                        @endif
                        @if ($filterType)
                            <span class="badge bg-secondary-subtle text-body border rounded-pill px-2.5 py-1 extra-small">Tipe: {{ ucfirst($filterType) }}</span>
                        @endif
                        @if ($filterStatus)
                            <span class="badge bg-secondary-subtle text-body border rounded-pill px-2.5 py-1 extra-small">Status: {{ ucfirst($filterStatus) }}</span>
                        @endif
                        @if ($filterHouse)
                            <span class="badge bg-secondary-subtle text-body border rounded-pill px-2.5 py-1 extra-small">Rumah: {{ $filterHouse }}</span>
                        @endif
                        <button type="button" wire:click="resetFilters" class="btn btn-link text-danger text-decoration-none p-0 extra-small font-semibold ms-auto">
                            Reset Semua
                        </button>
                    </div>
                @endif

                <!-- Modern Table Container -->
                <div class="table-responsive rounded-3 border overflow-hidden">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-body-secondary border-bottom">
                            <tr class="text-secondary extra-small text-uppercase font-geist tracking-wider">
                                <th class="py-3 px-3">Kode & Tanggal</th>
                                <th class="py-3 px-3">Item / Material</th>
                                <th class="py-3 px-3">Unit Rumah</th>
                                <th class="py-3 px-3 text-end">Jumlah</th>
                                <th class="py-3 px-3 text-end">Harga Satuan</th>
                                <th class="py-3 px-3 text-end">Total Biaya</th>
                                <th class="py-3 px-3">Status</th>
                                <th class="py-3 px-3 text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse ($rows as $row)
                                <tr class="transition-all">
                                    <td class="py-3 px-3">
                                        <div class="fw-bold font-mono text-body small">{{ $row['code'] }}</div>
                                        <div class="extra-small text-secondary">{{ $row['date'] }}</div>
                                    </td>
                                    <td class="py-3 px-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="p-2 rounded-2 {{ $row['item_type'] === 'material' ? 'bg-success-subtle text-success' : 'bg-primary-subtle text-primary' }} d-inline-flex align-items-center justify-content-center">
                                                <svg width="14" height="14" fill="currentColor"><use href="#{{ $row['item_type'] === 'material' ? 'i-box' : 'i-wrench' }}"/></svg>
                                            </div>
                                            <div>
                                                <div class="fw-semibold text-body small">{{ $row['item_name'] }}</div>
                                                <div class="extra-small text-secondary">Kategori: {{ $row['category'] }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-3">
                                        <span class="badge bg-body border text-body font-mono">{{ $row['house'] }}</span>
                                    </td>
                                    <td class="py-3 px-3 text-end font-mono fw-bold">
                                        {{ number_format($row['qty'], 0, ',', '.') }} <span class="extra-small text-secondary font-normal">{{ $row['unit'] }}</span>
                                    </td>
                                    <td class="py-3 px-3 text-end font-mono text-secondary small">
                                        {{ $row['unit_price'] > 0 ? 'Rp ' . number_format($row['unit_price'], 0, ',', '.') : '-' }}
                                    </td>
                                    <td class="py-3 px-3 text-end font-mono fw-bold {{ $row['total_cost'] > 0 ? 'text-success' : 'text-body-tertiary' }}">
                                        {{ $row['total_cost'] > 0 ? 'Rp ' . number_format($row['total_cost'], 0, ',', '.') : '-' }}
                                    </td>
                                    <td class="py-3 px-3">
                                        <span class="badge {{ $row['status_badge'] }} border rounded-pill px-2.5 py-1 extra-small font-semibold d-inline-flex align-items-center gap-1">
                                            {{ $row['status_label'] }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 text-end">
                                        <button type="button" class="btn btn-sm btn-icon btn-outline-secondary rounded-2 p-1.5 me-1" title="Detail">
                                            <svg width="12" height="12" fill="currentColor"><use href="#i-journal-text"/></svg>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-icon btn-outline-danger rounded-2 p-1.5" title="Hapus">
                                            <svg width="12" height="12" fill="currentColor"><use href="#i-logout"/></svg>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-secondary">
                                        Tidak ada data yang cocok dengan filter yang dipilih.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Filter Modal -->
            @if ($showFilterModal)
                <div class="modal fade show d-block" style="background-color: rgba(0,0,0,0.5);" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content rounded-4 border-0 shadow-lg bg-body">
                            <div class="modal-header border-bottom">
                                <h5 class="modal-title fw-bold font-outfit">Filter Data Tabel</h5>
                                <button type="button" class="btn-close" wire:click="$set('showFilterModal', false)"></button>
                            </div>
                            <div class="modal-body py-4 vstack gap-3">
                                <div>
                                    <label class="form-label fw-semibold">Jenis Item</label>
                                    <select wire:model.live="filterType" class="form-select">
                                        <option value="">Semua Jenis Item</option>
                                        <option value="material">Material</option>
                                        <option value="tool">Alat</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label fw-semibold">Status Transaksi</label>
                                    <select wire:model.live="filterStatus" class="form-select">
                                        <option value="">Semua Status</option>
                                        <option value="approved">Approved</option>
                                        <option value="borrowed">Dipinjam</option>
                                        <option value="pending">Menunggu Kirim</option>
                                        <option value="rejected">Ditolak</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label fw-semibold">Unit Rumah</label>
                                    <select wire:model.live="filterHouse" class="form-select">
                                        <option value="">Semua Unit Rumah</option>
                                        <option value="Blok A-01">Blok A-01</option>
                                        <option value="Blok B-04">Blok B-04</option>
                                        <option value="Blok C-02">Blok C-02</option>
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer border-top d-flex justify-content-between">
                                <button type="button" class="btn btn-outline-danger rounded-3" wire:click="resetFilters">Reset Filter</button>
                                <button type="button" class="btn btn-success rounded-3 fw-bold" wire:click="$set('showFilterModal', false)">Terapkan Filter</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>

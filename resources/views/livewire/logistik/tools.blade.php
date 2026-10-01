<div>
    <div class="container-fluid p-0">
        <!-- Hero Header -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-body-tertiary">
            <div class="card-body p-4 p-md-5 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <div>
                    <h1 class="display-5 fw-black text-body mb-2 font-outfit">
                        Inventaris <span class="text-success">Alat Kerja</span>
                    </h1>
                    <p class="text-secondary mb-0 max-w-xl">
                        Kelola inventaris alat kerja konstruksi, jumlah ketersediaan, dan alokasi peminjaman proyek.
                    </p>
                </div>
                <div class="d-flex flex-column gap-2" style="min-width: 260px;">
                    @if(in_array(auth()->user()->role, ['admin', 'logistik', 'keuangan'], true))
                        <div class="d-flex gap-2">
                            <button type="button" wire:click="openImportModal" class="btn btn-hero-action flex-fill">
                                <svg width="15" height="15" fill="currentColor" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z"/></svg>
                                <span>Import</span>
                            </button>
                            <button type="button" wire:click="exportExcel" class="btn btn-hero-action flex-fill">
                                <svg width="15" height="15" fill="currentColor" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5"/><path d="M7.646 1.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 2.707V10.5a.5.5 0 0 1-1 0V2.707L5.354 4.854a.5.5 0 1 1-.708-.708z"/></svg>
                                <span>Export</span>
                            </button>
                        </div>
                    @endif
                    <button type="button" wire:click="create" class="btn btn-hero-primary w-100">
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4"/></svg>
                        <span>Tambah Alat</span>
                    </button>
                </div>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @error('delete')
            <div class="alert alert-danger" role="alert">{{ $message }}</div>
        @enderror

        <!-- Bento Stats Grid -->
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card border-0 border-start border-4 border-primary shadow-sm rounded-4 p-4 bg-body-tertiary">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="small fw-bold text-secondary text-uppercase tracking-wider">Total Alat</span>
                        <div class="p-2 bg-primary-subtle text-primary rounded d-flex align-items-center justify-content-center">
                            <svg width="18" height="18" fill="currentColor"><use href="#i-wrench"/></svg>
                        </div>
                    </div>
                    <div>
                        <h2 class="fw-black text-body mb-1">{{ number_format($totalTools) }} <span class="fs-6 text-secondary font-normal">Unit</span></h2>
                        <span class="text-secondary small">Jumlah seluruh unit alat proyek terdaftar</span>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 border-start border-4 border-success shadow-sm rounded-4 p-4 bg-body-tertiary">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="small fw-bold text-secondary text-uppercase tracking-wider">Tersedia di Stok</span>
                        <div class="p-2 bg-success-subtle text-success rounded d-flex align-items-center justify-content-center">
                            <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l4.992-5.99a.75.75 0 0 0-.018-1.042z"/></svg>
                        </div>
                    </div>
                    <div>
                        <h2 class="fw-black text-success mb-1">{{ number_format($totalAvailable) }} <span class="fs-6 text-secondary font-normal">Unit</span></h2>
                        <span class="text-secondary small">Alat yang siap digunakan di gudang</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search & Filter Controls -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 p-3 bg-body-tertiary">
            <div class="d-flex flex-column flex-md-row gap-3 align-items-md-center justify-content-between">
                <div class="w-100 max-w-sm">
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama atau kode alat..." class="form-control" />
                </div>
                <div class="d-flex align-items-center gap-2">
                    <x-filter-modal :activeFiltersCount="$this->getActiveFiltersCount()">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase text-secondary">Kategori</label>
                        <select wire:model.live="filterCategory" class="form-select">
                            <option value="">Semua Kategori</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase text-secondary">Kondisi</label>
                        <select wire:model.live="filterCondition" class="form-select">
                            <option value="">Semua Kondisi</option>
                            <option value="baik">Baik</option>
                            <option value="rusak">Rusak</option>
                            <option value="hilang">Hilang</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase text-secondary">Gudang</label>
                        <select wire:model.live="filterWarehouse" class="form-select">
                            <option value="">Semua Gudang</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase text-secondary">Status Stok</label>
                        <select wire:model.live="filterStock" class="form-select">
                            <option value="">Semua Status Stok</option>
                            <option value="available">Tersedia di Stok (> 0)</option>
                            <option value="empty">Stok Habis / Terpinjam Semua (0)</option>
                            <option value="broken">Memiliki Alat Rusak (> 0)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase text-secondary">Foto Alat</label>
                        <select wire:model.live="filterPhoto" class="form-select">
                            <option value="">Semua Alat</option>
                            <option value="has_photo">Memiliki Foto</option>
                            <option value="no_photo">Belum Ada Foto</option>
                        </select>
                    </div>
                </x-filter-modal>
                </div>
            </div>
        </div>

        <!-- Tools Table -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 data-table-card">
            <div wire:loading.delay class="data-table-status" role="status">Memuat alat...</div>
            <div wire:offline class="data-table-status is-error" role="alert">Koneksi terputus. Data mungkin tidak terbaru.</div>
            <div class="table-responsive data-table-scroll" tabindex="0" role="region" aria-label="Daftar alat">
                <table class="table table-hover align-middle mb-0 data-table data-table--inventory data-table--sticky-identity">
                    <thead class="table-light text-uppercase small font-geist">
                        <tr>
                            <th class="text-center data-mobile-secondary" style="width: 50px;">No.</th>
                            <th class="text-center data-mobile-secondary" style="width: 60px;">Foto</th>
                            <x-sortable-th field="code" :sort="$sort" class="data-key-code">Kode</x-sortable-th>
                            <x-sortable-th field="name" :sort="$sort" class="data-key-name">Nama</x-sortable-th>
                            <x-sortable-th field="category" :sort="$sort">Kategori</x-sortable-th>
                            <x-sortable-th field="warehouse" :sort="$sort">Gudang</x-sortable-th>
                            <x-sortable-th field="condition" :sort="$sort">Kondisi</x-sortable-th>
                            <x-sortable-th field="price" :sort="$sort" class="text-end data-number">Harga Beli</x-sortable-th>
                            <x-sortable-th field="qty" :sort="$sort" class="text-end data-number">Total</x-sortable-th>
                            <x-sortable-th field="broken" :sort="$sort" class="text-end data-number">Rusak</x-sortable-th>
                            <x-sortable-th field="available" :sort="$sort" class="text-end data-number">Tersedia</x-sortable-th>
                            <x-sortable-th field="date" :sort="$sort" class="data-date">Masuk / Dicatat</x-sortable-th>
                            <th class="text-end" style="width: 140px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tools as $tool)
                        <tr wire:key="tool-{{ $tool->id }}" style="cursor: pointer;" x-on:click="if (window.innerWidth >= 768 && !$event.target.closest('button') && !$event.target.closest('a') && !$event.target.closest('input')) { $wire.edit({{ $tool->id }}) }">
                            <td class="text-center text-secondary small data-mobile-secondary">{{ ($tools->currentPage() - 1) * $tools->perPage() + $loop->iteration }}</td>
                            <td class="text-center data-mobile-secondary">
                                @if($tool->image)
                                    <button type="button" wire:click.stop="showToolImage({{ $tool->id }})" class="btn btn-link p-0 border-0" title="Klik untuk memperbesar foto">
                                        <img src="{{ asset('storage/' . $tool->image) }}" alt="{{ $tool->name }}" class="rounded-2 border shadow-sm" style="width: 36px; height: 36px; object-fit: cover;" />
                                    </button>
                                @else
                                    <span class="badge bg-body-secondary text-secondary font-mono small" title="Belum ada foto">-</span>
                                @endif
                            </td>
                            <td class="font-mono text-secondary small data-key-code" title="{{ $tool->entry_code }}">
                                <div>{{ $tool->code }}</div>
                                <div class="text-muted extra-small">{{ $tool->entry_code }}</div>
                            </td>
                            <td class="fw-bold text-body data-key-name" title="{{ $tool->name }}">{{ $tool->name }}</td>
                            <td class="text-secondary small data-cell-truncate" title="{{ $tool->category?->name ?? 'Kategori tidak tersedia' }}">{{ $tool->category?->name ?? '-' }}</td>
                            <td class="text-secondary small" title="Lokasi fisik alat">
                                @forelse ($tool->warehouseBalances->where(fn ($balance) => ($balance->available_qty + $balance->qty_broken) > 0) as $balance)
                                    <div class="text-nowrap">{{ $balance->warehouse?->name ?? 'Belum ditetapkan' }} <span class="font-mono">· {{ $balance->available_qty }} tersedia</span></div>
                                @empty
                                    <span>Belum ditetapkan</span>
                                @endforelse
                            </td>
                            <td>
                                @php 
                                    $badgeClasses = ['baik' => 'bg-success-subtle text-success', 'rusak' => 'bg-danger-subtle text-danger', 'hilang' => 'bg-warning-subtle text-warning'];
                                @endphp
                                <span class="badge {{ $badgeClasses[$tool->condition] ?? 'bg-secondary-subtle text-secondary' }}">{{ ucfirst($tool->condition) }}</span>
                            </td>
                            <td class="text-end font-mono text-secondary data-number">Rp {{ number_format($tool->purchase_price, 0, ',', '.') }}</td>
                            <td class="text-end fw-bold data-number">{{ $tool->total_qty }}</td>
                            <td class="text-end data-number">
                                <span class="{{ $tool->qty_broken > 0 ? 'text-amber-600 dark:text-amber-400 font-bold' : 'dark:text-zinc-100 font-bold' }}">{{ $tool->qty_broken }}</span>
                            </td>
                            <td class="text-end data-number">
                                <span class="{{ $tool->available_qty === 0 ? 'badge bg-danger-subtle text-danger' : 'fw-bold' }}">{{ $tool->available_qty }}</span>
                            </td>
                            <td class="font-mono text-secondary small data-date">
                                <div>{{ $tool->received_at ? 'Masuk '.$tool->received_at->format('d/m/Y H:i') : ($tool->received_date ? 'Masuk '.$tool->received_date->format('d/m/Y').' · waktu tidak tercatat' : 'Waktu masuk tidak tercatat') }}</div>
                                <div class="text-muted extra-small">Dicatat {{ $tool->created_at?->format('d/m/Y H:i') ?? '-' }} oleh {{ $tool->recordedBy?->name ?? 'Tidak tercatat' }}</div>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm data-row-actions d-none d-md-inline-flex">
                                    @if($tool->image)
                                        <button type="button" wire:click.stop="showToolImage({{ $tool->id }})" class="btn log-row-action log-row-action--quiet d-inline-flex align-items-center justify-content-center" title="Lihat Foto Alat" aria-label="Lihat foto alat {{ $tool->name }}">
                                            <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0"/><path d="M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8m8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7"/></svg>
                                        </button>
                                    @endif
                                    <button type="button" wire:click="edit({{ $tool->id }})" class="btn log-row-action log-row-action--edit d-inline-flex align-items-center justify-content-center" title="Edit" aria-label="Edit {{ $tool->name }}">
                                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325"/></svg>
                                    </button>
                                    <button type="button" wire:click="confirm('delete', {{ $tool->id }}, 'Hapus Alat?', 'Apakah Anda yakin ingin menghapus alat ini dari inventaris?')" class="btn log-row-action log-row-action--danger d-inline-flex align-items-center justify-content-center" title="Hapus" aria-label="Hapus {{ $tool->name }}">
                                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/><path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/></svg>
                                    </button>
                                </div>
                                <div class="dropdown d-md-none">
                                    <button type="button" class="btn btn-outline-secondary data-row-menu" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false" aria-label="Aksi untuk {{ $tool->name }}" title="Buka menu aksi" @click.stop>
                                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><circle cx="8" cy="3" r="1.25"/><circle cx="8" cy="8" r="1.25"/><circle cx="8" cy="13" r="1.25"/></svg>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end data-actions-menu">
                                        @if($tool->image)
                                            <button type="button" wire:click.stop="showToolImage({{ $tool->id }})" class="dropdown-item">Lihat foto</button>
                                        @endif
                                        <button type="button" wire:click.stop="edit({{ $tool->id }})" class="dropdown-item">Edit alat</button>
                                        <button type="button" wire:click.stop="confirm('delete', {{ $tool->id }}, 'Hapus Alat?', 'Apakah Anda yakin ingin menghapus alat ini dari inventaris?')" class="dropdown-item text-danger">Hapus alat</button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="13" class="text-center py-4 text-secondary">Belum ada data alat.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">{{ $tools->links('vendor.livewire.bootstrap') }}</div>
    </div>

    <!-- Modal: Create / Edit Tool -->
    @if($showModal)
    @teleport('body')
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog" aria-labelledby="tool-form-title"
        x-data="{
            opener: document.activeElement,
            init() { this.$nextTick(() => this.focusables()[0]?.focus()); },
            focusables() { return [...this.$refs.dialog.querySelectorAll('button, input, select, textarea, a[href]')].filter(element => !element.disabled && element.offsetParent !== null); },
            close() {
                const opener = this.opener;
                this.$wire.set('showModal', false);
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
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header border-bottom py-3 px-4">
                    <div class="d-flex align-items-center gap-2">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success-subtle text-success p-2" style="width: 32px; height: 32px;">
                            <svg width="16" height="16" fill="currentColor"><use href="#i-wrench"/></svg>
                        </span>
                        <h5 id="tool-form-title" class="modal-title font-outfit fw-bold text-body mb-0">{{ $editMode ? 'Edit Alat Kerja' : 'Tambah Alat Kerja Baru' }}</h5>
                    </div>
                    <button type="button" class="btn-close btn-close-icon" x-on:click="close()" aria-label="Tutup dialog alat"><svg width="16" height="16" fill="currentColor" aria-hidden="true"><use href="#i-x"/></svg></button>
                </div>
                <div class="modal-body p-4">
                    @if ($saveSuccess)
                        <div class="alert alert-success py-2 mb-3" role="status" aria-live="polite">{{ $saveSuccess }}</div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label font-semibold small text-secondary">Nama Alat <span class="text-danger">*</span></label>
                        <input type="text" wire:model="name" class="form-control" placeholder="Contoh: Molen Beton 500L" />
                        @error('name') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label font-semibold small text-secondary">Kategori <span class="text-danger">*</span></label>
                            <select wire:model.live="category_id" class="form-select">
                                <option value="">-- Pilih Kategori --</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            @error('category_id') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold small text-secondary">Kode Aset</label>
                            <div class="input-group">
                                <input type="text" wire:model="code" class="form-control bg-body-secondary font-mono" readonly placeholder="Pilih kategori..." />
                                <span class="input-group-text small fw-bold text-success font-geist bg-body-secondary">Otomatis</span>
                            </div>
                            @error('code') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-semibold small text-secondary">Gudang <span class="text-danger">*</span></label>
                        <select wire:model="warehouse_id" class="form-select" @disabled($hasMultipleWarehouseBalances)>
                            <option value="">Pilih Gudang</option>
                            @foreach ($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                            @endforeach
                        </select>
                        @error('warehouse_id') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label font-semibold small text-secondary">Kondisi Awal</label>
                            <select wire:model="condition" class="form-select">
                                <option value="baik">Baik (Layak Pakai)</option>
                                <option value="rusak">Rusak</option>
                                <option value="hilang">Hilang</option>
                            </select>
                            @error('condition') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-6" x-data="{ 
                            display: '',
                            init() {
                                this.display = this.format($wire.purchase_price);
                                this.$watch('display', val => {
                                    let clean = val.replace(/[^\d]/g, '');
                                    if (clean === '') { $wire.purchase_price = null; this.display = ''; return; }
                                    let num = parseInt(clean, 10);
                                    let formatted = this.format(num);
                                    if (this.display !== formatted) { this.display = formatted; }
                                    $wire.purchase_price = num;
                                });
                                $wire.$watch('purchase_price', val => {
                                    if (document.activeElement !== this.$refs.input) {
                                        this.display = this.format(val);
                                    }
                                });
                            },
                            format(num) {
                                if (num === null || num === undefined || num === '') return '';
                                return 'Rp ' + Number(num).toLocaleString('id-ID', { maximumFractionDigits: 0 });
                            }
                        }">
                            <label class="form-label font-semibold small text-secondary">Harga Beli / Unit</label>
                            <input type="text" x-ref="input" x-model="display" class="form-control font-mono" placeholder="Rp 0" />
                            @error('purchase_price') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    @unless($editMode)
                    <div class="mb-3">
                        <label class="form-label font-semibold small text-secondary">Waktu alat tiba di gudang <span class="text-danger">*</span></label>
                        <input type="datetime-local" wire:model="receivedAt" class="form-control font-mono" />
                        @error('receivedAt') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    @endunless

                    <div class="card bg-body-tertiary border p-3 rounded-3 mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold font-outfit text-body mb-0 small text-uppercase font-geist">
                                {{ $editMode ? 'Koreksi Kuantitas & Kondisi Alat' : 'Penerimaan Alat' }}
                            </h6>
                            @if($editMode)
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle extra-small">
                                Koreksi Data
                            </span>
                            @endif
                        </div>
                        <div class="row g-3">
                            <div class="col-4">
                                <label class="form-label font-semibold extra-small text-secondary">Total Aset <span class="text-danger">*</span></label>
                                <input type="number" wire:model="total_qty" class="form-control font-mono fw-bold" min="1" />
                                @error('total_qty') <span class="text-danger extra-small">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-4">
                                <label class="form-label font-semibold extra-small text-success">Siap Pakai <span class="text-danger">*</span></label>
                                <input type="number" wire:model="available_qty" class="form-control font-mono fw-bold text-success" min="0" @disabled($hasMultipleWarehouseBalances) />
                                @error('available_qty') <span class="text-danger extra-small">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-4">
                                <label class="form-label font-semibold extra-small text-danger">Rusak <span class="text-danger">*</span></label>
                                <input type="number" wire:model="qty_broken" class="form-control font-mono fw-bold text-danger" min="0" @disabled($hasMultipleWarehouseBalances) />
                                @error('qty_broken') <span class="text-danger extra-small">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        @if($hasMultipleWarehouseBalances)
                        <div class="alert alert-info border-0 small mt-3 mb-0">
                            Unit alat ini tersebar di beberapa gudang. Gunakan <strong>Transfer Gudang</strong> atau pengembalian alat agar saldo per gudang dan riwayat tetap akurat.
                        </div>
                        @endif
                        @if($editMode)
                        <div class="mt-3">
                            <label class="form-label font-semibold extra-small text-secondary">Alasan koreksi inventaris <span class="text-secondary fw-normal">(opsional)</span></label>
                            <textarea wire:model="inventoryAdjustmentReason" class="form-control" rows="2" placeholder="Contoh: hasil stok opname 18-09-2026"></textarea>
                            @error('inventoryAdjustmentReason') <span class="text-danger extra-small">{{ $message }}</span> @enderror
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top extra-small text-secondary">
                            <span>Status Pinjaman Lapangan:</span>
                            <span class="badge {{ $loanedCount > 0 ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-body-secondary text-secondary' }} font-mono">
                                {{ $loanedCount }} unit sedang dipinjam
                            </span>
                        </div>
                        @endif
                    </div>

                    <div>
                        <label class="form-label font-semibold small text-secondary d-inline-flex align-items-center gap-1">
                            <svg width="14" height="14" fill="currentColor" aria-hidden="true"><use href="#i-camera"/></svg> Foto Alat Kerja <span class="extra-small text-secondary fw-normal">(Opsional)</span>
                        </label>

                        @if ($existingImage && !$image)
                            <div class="p-3 border rounded-3 bg-body-tertiary mb-2">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                        Foto Saat Ini
                                    </span>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <img src="{{ asset('storage/' . $existingImage) }}" alt="Foto Alat" class="img-thumbnail rounded-3" style="max-height: 90px; object-fit: cover;" />
                                    <p class="text-secondary extra-small mb-0">
                                        Unggah file baru di bawah ini jika ingin mengganti foto alat kerja ini.
                                    </p>
                                </div>
                            </div>
                        @endif

                        <input type="file" wire:model="image" class="form-control" accept="image/*" />
                        <div class="extra-small text-secondary mt-1">Format gambar: JPG, PNG, WEBP (Maksimal 5MB).</div>
                        @error('image') <span class="text-danger small d-block mt-1">{{ $message }}</span> @enderror

                        @if ($image)
                            <div class="mt-2">
                                <img src="{{ $image->temporaryUrl() }}" alt="Preview Foto" class="img-thumbnail rounded-3" style="max-height: 110px; object-fit: cover;" />
                            </div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer border-top bg-body-tertiary rounded-bottom-4 py-3 px-4 d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary fw-semibold px-3" x-on:click="close()">Batal</button>
                    <button type="button" class="btn btn-success fw-semibold px-4" wire:click="save" wire:loading.attr="disabled" wire:target="save"><span wire:loading.remove wire:target="save">{{ $editMode ? 'Perbarui Alat' : 'Simpan Alat' }}</span><span wire:loading wire:target="save">Menyimpan alat…</span></button>
                </div>
            </div>
        </div>
    </div>
    @endteleport
    @endif

    <!-- Confirmation Modal -->
    @if($showConfirmation)
    @teleport('body')
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title font-outfit fw-bold">{{ $confirmTitle ?? 'Konfirmasi' }}</h5>
                    <button type="button" class="btn-close" wire:click="$set('showConfirmation', false)"></button>
                </div>
                <div class="modal-body py-4">
                    <p class="text-secondary mb-0">{{ $confirmMessage ?? 'Apakah Anda yakin ingin melakukan tindakan ini?' }}</p>
                </div>
                <div class="modal-footer border-top bg-body-tertiary rounded-bottom-4">
                    <button type="button" class="btn btn-secondary btn-sm fw-semibold" wire:click="$set('showConfirmation', false)">Batal</button>
                    <button type="button" class="btn btn-danger btn-sm fw-semibold" wire:click="executeConfirmedAction" wire:loading.attr="disabled" wire:target="executeConfirmedAction">{{ $confirmingAction === 'fixTool' ? 'Ya, Konfirmasi' : 'Ya, Hapus' }}</button>
                </div>
            </div>
        </div>
    </div>
    @endteleport
    @endif

    <!-- Import Excel Modal -->
    @if($showImportModal)
    @teleport('body')
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog" aria-labelledby="tool-import-title"
        x-data="{
            opener: document.activeElement,
            init() { this.$nextTick(() => this.focusables()[0]?.focus()); },
            focusables() { return [...this.$refs.dialog.querySelectorAll('button, input, select, textarea, a[href]')].filter(element => !element.disabled && element.offsetParent !== null); },
            close() {
                const opener = this.opener;
                this.$wire.set('showImportModal', false);
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
                    <h5 id="tool-import-title" class="modal-title font-outfit fw-bold d-flex align-items-center gap-2">
                        <svg width="20" height="20" fill="currentColor" class="text-primary" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5"/><path d="M7.646 1.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 2.707V10.5a.5.5 0 0 1-1 0V2.707L5.354 4.854a.5.5 0 1 1-.708-.708z"/></svg>
                        Import & Validasi Data Alat & Peralatan
                    </h5>
                    <button type="button" class="btn-close" x-on:click="close()" aria-label="Tutup dialog impor alat"></button>
                </div>
                <div class="modal-body py-4">
                    @if(!$importResultSummary)
                        <p class="text-body-secondary small mb-3">
                            Unggah berkas Excel (<code>.xlsx</code> / <code>.xls</code>) atau CSV berisi daftar alat/peralatan atau transaksi peminjaman & pengembalian. Sistem akan membaca, memvalidasi integritas baris, dan meregistrasi data secara otomatis.
                        </p>

                        <div class="bg-body-tertiary p-3 rounded-3 mb-3 border">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <h6 class="fw-bold extra-small text-uppercase text-body-secondary mb-0">Panduan Kolom Excel</h6>
                                <a href="{{ asset('sample_tool_import.xlsx') }}" download class="btn btn-outline-success btn-sm py-1 px-2.5 extra-small fw-semibold d-inline-flex align-items-center gap-1">
                                    <svg width="13" height="13" fill="currentColor" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z"/></svg>
                                    <span>Unduh Format Contoh (.xlsx)</span>
                                </a>
                            </div>
                            <ul class="extra-small text-body-secondary mb-0 ps-3">
                                <li><strong>Format A.KELUAR:</strong> <code>Tanggal</code>, <code>Bulan</code>, <code>Tahun</code>, <code>Penanggung Jawab</code>, <code>Blok Rumah</code>, <code>Keterangan Pekerjaan</code>, <code>Kode Alat</code>, <code>Nama Alat</code>, <code>Volume</code>, <code>Satuan</code>, <code>Harga Satuan</code>, <code>Jumlah</code>, <code>Toko/Supplier</code>, <code>Kode Transaksi</code></li>
                                <li><strong>Format lama:</strong> inventaris dengan <code>Kode</code>, <code>Nama Alat</code>, <code>Kategori</code>, <code>Kondisi</code>, <code>Total Qty</code>, <code>Harga Beli</code>, dan <code>Gudang</code>, serta transaksi opsional. Isi <code>Gudang</code> jika ada lebih dari satu gudang.</li>
                                <li><strong>Catatan Peminjaman:</strong> Tambahkan kolom <code>Jenis</code> (pinjam/kembali), <code>Unit Rumah</code> (mis. Blok B-04), <code>Tanggal</code>, <code>Catatan</code></li>
                                <li><strong>Tanggal dan kode:</strong> tanggal wajib untuk transaksi. <code>Kode Transaksi</code> opsional, gunakan kode baru hanya untuk transaksi baru yang sama persis.</li>
                                <li><strong>Workbook lama:</strong> pisahkan data alat dari material dan petakan gudang serta tanggal sebelum menyiapkan satu sheet sesuai format impor.</li>
                            </ul>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Pilih Berkas Excel / CSV</label>
                            <input type="file" wire:model="importFile" class="form-control" accept=".xlsx,.xls,.csv" />
                            @error('importFile') <span class="text-danger small fw-semibold d-block mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div wire:loading wire:target="importFile" class="text-primary small fw-semibold">
                            <div class="spinner-border spinner-border-sm me-1" role="status"></div> Mengunggah berkas...
                        </div>
                    @else
                        <!-- Validation Report Results -->
                        <div class="alert alert-success d-flex align-items-center gap-3 mb-3">
                            <div class="fs-3 text-success" aria-hidden="true"><svg width="28" height="28" viewBox="0 0 16 16" fill="currentColor"><path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m3.97-9.03-4.5 4.5a.75.75 0 0 1-1.06 0l-2-2a.75.75 0 0 1 1.06-1.06L6.94 9.88l3.97-3.97a.75.75 0 0 1 1.06 1.06"/></svg></div>
                            <div>
                                <h6 class="fw-bold mb-1">Validasi Impor Selesai!</h6>
                                <p class="mb-0 small">Seluruh baris data peralatan pada berkas Excel telah diproses dan divalidasi ke database.</p>
                            </div>
                        </div>

                        <!-- Summary Cards -->
                        <div class="row g-2 mb-3 text-center">
                            <div class="col-3">
                                <div class="p-2 border rounded bg-body-tertiary">
                                    <span class="d-block text-secondary extra-small fw-bold text-uppercase">Total Baris</span>
                                    <span class="fs-5 fw-bold text-body">{{ $importResultSummary['totalRows'] }}</span>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="p-2 border rounded bg-success-subtle text-success">
                                    <span class="d-block extra-small fw-bold text-uppercase">Sukses Diproses</span>
                                    <span class="fs-5 fw-bold">{{ $importResultSummary['successfulRows'] }}</span>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="p-2 border rounded bg-warning-subtle text-warning">
                                    <span class="d-block extra-small fw-bold text-uppercase">Alat Baru</span>
                                    <span class="fs-5 fw-bold">{{ $importResultSummary['toolsImported'] }}</span>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="p-2 border rounded bg-info-subtle text-info">
                                    <span class="d-block extra-small fw-bold text-uppercase">Log Transaksi</span>
                                    <span class="fs-5 fw-bold">{{ $importResultSummary['transactionsImported'] }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Detailed Row Logs -->
                        <h6 class="fw-bold extra-small text-uppercase text-secondary mb-2">Rincian Status per Baris</h6>
                        <div class="table-responsive border rounded" style="max-height: 250px; overflow-y: auto;">
                            <table class="table table-sm table-striped align-middle mb-0 extra-small">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th style="width: 60px;">Baris Data</th>
                                        <th>Peralatan</th>
                                        <th>Status Validasi</th>
                                        <th>Keterangan Sistem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($importResultSummary['logs'] as $log)
                                        <tr>
                                            <td class="fw-mono text-center">#{{ max(1, (int) $log['row'] - 1) }}</td>
                                            <td class="fw-semibold">{{ $log['item'] }}</td>
                                            <td>
                                                @if($log['status'] === 'success')
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle">VALID & DIPROSES</span>
                                                @else
                                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">DILEWATI</span>
                                                @endif
                                            </td>
                                            <td class="text-secondary">{{ $log['message'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
                <div class="modal-footer border-top bg-body-tertiary rounded-bottom-4">
                    @if(!$importResultSummary)
                        <button type="button" class="btn btn-secondary fw-semibold" x-on:click="close()">Batal</button>
                        <button type="button" class="btn btn-primary fw-semibold" wire:click="importExcel" wire:loading.attr="disabled">
                            <svg width="14" height="14" fill="currentColor" class="me-1" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5"/><path d="M7.646 1.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 2.707V10.5a.5.5 0 0 1-1 0V2.707L5.354 4.854a.5.5 0 1 1-.708-.708z"/></svg>
                            Proses & Validasi Import
                        </button>
                    @else
                        <button type="button" class="btn btn-success fw-semibold px-4" x-on:click="close()">Tutup & Selesai</button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endteleport
    @endif

    <!-- View Tool Image Modal -->
    @if($showImageModal)
    @teleport('body')
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title font-outfit fw-bold d-flex align-items-center gap-2">
                        <svg width="18" height="18" fill="currentColor" class="text-info"><use href="#i-eye"/></svg>
                        Foto Alat: {{ $viewingImageToolName }}
                    </h5>
                    <button type="button" class="btn-close" wire:click="$set('showImageModal', false)"></button>
                </div>
                <div class="modal-body p-3 text-center bg-dark-subtle">
                    <img src="{{ $viewingImageUrl }}" alt="{{ $viewingImageToolName }}" class="img-fluid rounded-3 border shadow" style="max-height: 480px; object-fit: contain;" />
                </div>
                <div class="modal-footer border-top bg-body-tertiary rounded-bottom-4 justify-content-between">
                    <a href="{{ $viewingImageUrl }}" target="_blank" download class="btn btn-outline-primary btn-sm fw-semibold d-inline-flex align-items-center gap-1">
                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z"/></svg> Unduh Foto
                    </a>
                    <button type="button" class="btn btn-secondary btn-sm fw-semibold" wire:click="$set('showImageModal', false)">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    @endteleport
    @endif
</div>

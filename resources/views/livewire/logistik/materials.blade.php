<div>
    <div class="container-fluid p-0">
        <!-- Hero Header -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-body-tertiary">
            <div class="card-body p-4 p-md-5 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 text-uppercase mb-2 font-geist small">Modul Inventaris</span>
                    <h1 class="display-5 fw-black text-body mb-2 font-outfit">
                        Kontrol Stok <span class="text-success">Material</span>
                    </h1>
                    <p class="text-secondary mb-0 max-w-xl">
                        Kelola stok material bangunan, lakukan penambahan stok, dan pantau harga satuan.
                    </p>
                </div>
                <div class="d-flex flex-column gap-2" style="min-width: 260px;">
                    @if(in_array(auth()->user()->role, ['admin', 'logistik']))
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
                        <span>Tambah Material</span>
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

        <!-- Bento Stats Grid -->
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card border-0 border-start border-4 border-warning shadow-sm rounded-4 p-4 bg-body-tertiary">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="small fw-bold text-secondary text-uppercase tracking-wider">Total Nilai Material</span>
                        <div class="p-2 bg-warning-subtle text-warning rounded d-flex align-items-center justify-content-center">
                            <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M12.136.326A1.5 1.5 0 0 1 14 1.78V3h.5A1.5 1.5 0 0 1 16 4.5v9a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 0 13.5v-9a1.5 1.5 0 0 1 1.432-1.499L12.136.326zM5.562 3H13V1.78a.5.5 0 0 0-.621-.484zM1.5 4a.5.5 0 0 0-.5.5v9a.5.5 0 0 0 .5.5h13a.5.5 0 0 0 .5-.5v-9a.5.5 0 0 0-.5-.5z"/></svg>
                        </div>
                    </div>
                    <div>
                        <h2 class="fw-black text-warning mb-1">Rp {{ number_format($totalValue, 0, ',', '.') }}</h2>
                        <span class="text-secondary small">Jumlah harga × stok seluruh material</span>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 border-start border-4 border-primary shadow-sm rounded-4 p-4 bg-body-tertiary">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="small fw-bold text-secondary text-uppercase tracking-wider">Total Jenis Material</span>
                        <div class="p-2 bg-primary-subtle text-primary rounded d-flex align-items-center justify-content-center">
                            <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5l6.154 2.38 6.154-2.38zM15 4.239l-6.5 2.515v7.182l6.5-2.6v-7.097zM7.5 13.936V6.754L1 4.239v7.097z"/></svg>
                        </div>
                    </div>
                    <div>
                        <h2 class="fw-black text-primary mb-1">{{ $totalItems }} <span class="fs-6 text-secondary font-normal">Item</span></h2>
                        <span class="text-secondary small">Material dengan stok tersedia</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search & Filter Controls -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 p-3 bg-body-tertiary">
            <div class="d-flex flex-column flex-md-row gap-3 align-items-md-center justify-content-between">
                <div class="w-100 max-w-sm">
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari material..." class="form-control" />
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button 
                        type="button" 
                        wire:click="toggleSortDirection" 
                        class="btn btn-outline-secondary d-inline-flex align-items-center justify-content-center shadow-xs" 
                        style="height: 38px; min-width: 38px; padding: 0 10px;"
                        title="Urutan: {{ str_ends_with($sort, '_asc') ? 'Ascending (A-Z / Terendah / Terlama)' : 'Descending (Z-A / Tertinggi / Terbaru)' }}"
                    >
                        @if(str_ends_with($sort, '_asc'))
                            {{-- Arrow Up / Ascending --}}
                            <svg width="15" height="15" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 15a.5.5 0 0 0 .5-.5V2.707l3.146 3.147a.5.5 0 0 0 .708-.708l-4-4a.5.5 0 0 0-.708 0l-4 4a.5.5 0 1 0 .708.708L7.5 2.707V14.5a.5.5 0 0 0 .5.5"/></svg>
                        @else
                            {{-- Arrow Down / Descending --}}
                            <svg width="15" height="15" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 1a.5.5 0 0 1 .5.5v11.793l3.146-3.147a.5.5 0 0 1 .708.708l-4 4a.5.5 0 0 1-.708 0l-4-4a.5.5 0 0 1 .708-.708L7.5 13.293V1.5A.5.5 0 0 1 8 1"/></svg>
                        @endif
                    </button>

                    <x-filter-modal :activeFiltersCount="$this->getActiveFiltersCount()">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase text-secondary">Urutkan Berdasarkan</label>
                        <select wire:model.live="sort" class="form-select">
                            <option value="name_asc">Nama Material (A-Z)</option>
                            <option value="name_desc">Nama Material (Z-A)</option>
                            <option value="date_desc">Tanggal Ditambahkan (Terbaru)</option>
                            <option value="date_asc">Tanggal Ditambahkan (Terlama)</option>
                            <option value="stock_desc">Stok (Tertinggi)</option>
                            <option value="stock_asc">Stok (Terendah)</option>
                            <option value="unit_price_desc">Harga Satuan (Tertinggi)</option>
                            <option value="unit_price_asc">Harga Satuan (Terendah)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase text-secondary">Kategori</label>
                        <select wire:model.live="filterCategory" class="form-select">
                            <option value="">Semua Kategori</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase text-secondary">Mitra Supplier</label>
                        <select wire:model.live="filterSupplier" class="form-select">
                            <option value="">Semua Supplier</option>
                            @foreach($suppliers as $sup)
                                <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase text-secondary">Status Stok</label>
                        <select wire:model.live="filterStock" class="form-select">
                            <option value="">Semua Stok</option>
                            <option value="safe">Aman (&gt; 10)</option>
                            <option value="low">Menipis (&le; 10)</option>
                            <option value="empty">Habis (0)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase text-secondary">Foto Material</label>
                        <select wire:model.live="filterPhoto" class="form-select">
                            <option value="">Semua Material</option>
                            <option value="has_photo">Memiliki Foto</option>
                            <option value="no_photo">Belum Ada Foto</option>
                        </select>
                    </div>
                </x-filter-modal>
                </div>
            </div>
        </div>

        <!-- Materials Table -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-uppercase small font-geist">
                        <tr>
                            <th class="text-center" style="width: 50px;">No.</th>
                            <th class="text-center" style="width: 60px;">Foto</th>
                            <th>Kode</th>
                            <th>Nama</th>
                            <th>Kategori</th>
                            <th>Supplier</th>
                            <th class="text-end">Stok + Satuan</th>
                            <th class="text-end">Harga Satuan</th>
                            <th class="text-end">Total Nilai</th>
                            <th class="text-center">Tanggal Masuk</th>
                            <th class="text-end" style="width: 160px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($materials as $material)
                        <tr wire:key="mat-{{ $material->id }}" style="cursor: pointer;" x-on:click="if (!$event.target.closest('button') && !$event.target.closest('a') && !$event.target.closest('input')) { $wire.edit({{ $material->id }}) }">
                            <td class="text-center text-secondary small">{{ ($materials->currentPage() - 1) * $materials->perPage() + $loop->iteration }}</td>
                            <td class="text-center">
                                @if($material->image)
                                    <button type="button" wire:click.stop="showMaterialImage({{ $material->id }})" class="btn btn-link p-0 border-0" title="Klik untuk memperbesar foto">
                                        <img src="{{ asset('storage/' . $material->image) }}" alt="{{ $material->name }}" class="rounded-2 border shadow-sm" style="width: 36px; height: 36px; object-fit: cover;" />
                                    </button>
                                @else
                                    <span class="badge bg-body-secondary text-secondary font-mono small" title="Belum ada foto">—</span>
                                @endif
                            </td>
                            <td class="font-mono text-secondary small">{{ $material->code ?? '-' }}</td>
                            <td class="fw-bold text-body">{{ $material->name }}</td>
                            <td class="text-secondary small">{{ $material->category?->name ?? '-' }}</td>
                            <td class="text-secondary small">{{ $material->supplier?->name ?? '-' }}</td>
                            <td class="text-end fw-bold">
                                <span class="{{ $material->stock <= 10 ? 'badge bg-danger-subtle text-danger border border-danger-subtle' : '' }}">
                                    {{ rtrim(rtrim(number_format((float) $material->stock, 2, ',', '.'), '0'), ',') }}
                                </span>
                                <span class="text-secondary small font-normal ms-1">{{ $material->unit }}</span>
                            </td>
                            <td class="text-end font-mono text-secondary">Rp {{ number_format($material->unit_price, 0, ',', '.') }}</td>
                            <td class="text-end font-mono fw-bold text-success">Rp {{ number_format($material->unit_price * $material->stock, 0, ',', '.') }}</td>
                            <td class="text-center font-mono text-secondary small">
                                <div>{{ $material->created_at ? $material->created_at->format('d/m/Y H:i') : '-' }}</div>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    @if($material->image)
                                        <button type="button" wire:click.stop="showMaterialImage({{ $material->id }})" class="btn btn-outline-info d-inline-flex align-items-center justify-content-center" title="Lihat Foto Material">
                                            <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0"/><path d="M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8m8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7"/></svg>
                                        </button>
                                    @endif
                                    <button type="button" wire:click="edit({{ $material->id }})" class="btn btn-outline-secondary d-inline-flex align-items-center justify-content-center" title="Edit">
                                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325"/></svg>
                                    </button>
                                    <button type="button" wire:click="confirm('delete', {{ $material->id }}, 'Hapus Material?', 'Apakah Anda yakin ingin menghapus material ini? Seluruh data stok terkait akan dihapus permanen.')" class="btn btn-outline-danger d-inline-flex align-items-center justify-content-center" title="Hapus">
                                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/><path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center py-4 text-secondary">Belum ada data material.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">{{ $materials->links('vendor.livewire.bootstrap') }}</div>
    </div>

    <!-- Modal: Create / Edit Material -->
    @if($showModal)
    @teleport('body')
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header border-bottom py-3 px-4">
                    <div class="d-flex align-items-center gap-2">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success-subtle text-success p-2" style="width: 32px; height: 32px;">
                            <svg width="16" height="16" fill="currentColor"><use href="#i-box"/></svg>
                        </span>
                        <h5 class="modal-title font-outfit fw-bold text-body mb-0">{{ $editMode ? 'Edit Material' : 'Tambah Material Baru' }}</h5>
                    </div>
                    <button type="button" class="btn-close" wire:click="$set('showModal', false)"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label font-semibold small text-secondary">Nama Material <span class="text-danger">*</span></label>
                            <input type="text" wire:model="name" class="form-control" placeholder="Contoh: Semen Portland 50kg" />
                            @error('name') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold small text-secondary">Kode Material</label>
                            <div class="input-group">
                                <input type="text" wire:model="code" class="form-control bg-body-secondary font-mono" readonly placeholder="Pilih kategori..." />
                                <span class="input-group-text small fw-bold text-success font-geist bg-body-secondary">Otomatis</span>
                            </div>
                            @error('code') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
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
                        <div class="col-md-6" x-data="{ supPickerOpen: false, supSearch: '' }">
                            <label class="form-label font-semibold small text-secondary">Mitra Supplier</label>
                            <div class="position-relative" @click.outside="supPickerOpen = false">
                                <div class="position-relative d-flex align-items-center">
                                    <input
                                        type="text"
                                        wire:model="supplier_name"
                                        class="form-control pe-5"
                                        placeholder="Pilih atau ketik nama supplier..."
                                        @focus="supPickerOpen = true; supSearch = $wire.supplier_name || ''"
                                        @input="supPickerOpen = true; supSearch = $el.value"
                                    />
                                    <button
                                        type="button"
                                        class="btn btn-link text-secondary text-decoration-none position-absolute end-0 me-2 p-1 d-flex align-items-center"
                                        @click="supPickerOpen = !supPickerOpen; supSearch = $wire.supplier_name || ''"
                                        aria-label="Tampilkan pilihan supplier"
                                    >
                                        <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor" class="transition-transform" :class="supPickerOpen ? 'rotate-180' : ''" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/>
                                        </svg>
                                    </button>
                                </div>

                                <div
                                    x-show="supPickerOpen"
                                    x-cloak
                                    class="card shadow-lg border rounded-3 position-absolute w-100 mt-1 bg-body overflow-hidden"
                                    style="max-height: 200px; z-index: 1050;"
                                >
                                    <div class="p-1.5" style="max-height: 190px; overflow-y: auto; -webkit-overflow-scrolling: touch;">
                                        @foreach ($suppliers as $sup)
                                            <button
                                                type="button"
                                                class="dropdown-item rounded-2 py-2 px-3 text-start w-100 font-semibold"
                                                :class="$wire.supplier_name === @js($sup->name) ? 'active bg-success text-white' : ''"
                                                x-show="supSearch === '' || @js(strtolower($sup->name)).includes(supSearch.toLowerCase())"
                                                @click="$wire.supplier_name = @js($sup->name); supPickerOpen = false; supSearch = @js($sup->name);"
                                            >
                                                {{ $sup->name }}
                                            </button>
                                        @endforeach
                                        <div x-show="supSearch !== '' && !@js($suppliers->pluck('name')->map(fn($n) => strtolower($n))->all()).includes(supSearch.toLowerCase())" class="p-2 border-top extra-small text-secondary bg-body-tertiary">
                                            <span>Ketik <strong>&quot;<span x-text="supSearch"></span>&quot;</strong> untuk menambahkan supplier baru secara otomatis saat disimpan.</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @error('supplier_name') <span class="text-danger small d-block mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label font-semibold small text-secondary">Satuan Material <span class="text-danger">*</span></label>
                            <select wire:model="unit" class="form-select">
                                <option value="">-- Pilih Satuan --</option>
                                <option value="sak">Sak / Zak</option>
                                <option value="batang">Batang</option>
                                <option value="buah">Buah / Pcs</option>
                                <option value="lembar">Lembar</option>
                                <option value="kg">Kilogram (kg)</option>
                                <option value="meter">Meter (m)</option>
                                <option value="m²">Meter Persegi (m²)</option>
                                <option value="m³">Meter Kubik (m³)</option>
                                <option value="liter">Liter (L)</option>
                                <option value="kaleng">Kaleng</option>
                                <option value="dus">Dus / Kotak</option>
                                <option value="rol">Rol</option>
                                <option value="set">Set</option>
                                <option value="ton">Ton</option>
                                <option value="rit">Rit / Truk</option>
                            </select>
                            @error('unit') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-6" x-data="{
                            display: '',
                            init() {
                                this.display = this.format($wire.unit_price);
                                this.$watch('display', val => {
                                    let digits = val.replace(/\D/g, '');
                                    if (digits === '') {
                                        $wire.unit_price = null;
                                        this.display = '';
                                        return;
                                    }
                                    let num = parseInt(digits, 10);
                                    let formatted = this.format(num);
                                    if (this.display !== formatted) {
                                        this.display = formatted;
                                    }
                                    $wire.unit_price = num;
                                });
                                $wire.$watch('unit_price', val => {
                                    if (document.activeElement !== this.$refs.input) {
                                        this.display = this.format(val);
                                    }
                                });
                            },
                            format(num) {
                                if (num === null || num === undefined || num === '') return '';
                                return 'Rp ' + num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                            }
                        }">
                            <label class="form-label font-semibold small text-secondary">Harga Satuan</label>
                            <input type="text" x-ref="input" x-model="display" class="form-control font-mono" placeholder="Rp 0" />
                            @error('unit_price') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="card bg-body-tertiary border p-3 rounded-3 mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold font-outfit text-body mb-0 small text-uppercase font-geist">
                                {{ $editMode ? 'Koreksi Kuantitas Stok' : 'Kuantitas Stok Awal' }}
                            </h6>
                            @if($editMode)
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle extra-small">
                                Koreksi Data
                            </span>
                            @endif
                        </div>
                        <div>
                            <label class="form-label font-semibold extra-small text-secondary">
                                {{ $editMode ? 'Jumlah Stok Fisik Riil' : 'Jumlah Stok Masuk Awal' }} <span class="text-danger">*</span>
                            </label>
                            <input type="number" step="0.01" min="0" wire:model="stock" class="form-control font-mono fw-bold text-success" />
                            @error('stock') <span class="text-danger extra-small">{{ $message }}</span> @enderror
                            @if($editMode)
                            <div class="extra-small text-secondary mt-1">
                                Perubahan stok dan harga satuan otomatis merekonsiliasi seluruh data biaya terkait di sistem.
                            </div>
                            @endif
                        </div>
                    </div>

                    <div>
                        <label class="form-label font-semibold small text-secondary d-inline-flex align-items-center gap-1">
                            <svg width="14" height="14" fill="currentColor" aria-hidden="true"><use href="#i-camera"/></svg> Foto / Gambar Material <span class="extra-small text-secondary fw-normal">(Opsional)</span>
                        </label>

                        @if ($existingImage && !$image)
                            <div class="p-3 border rounded-3 bg-body-tertiary mb-2">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                        Foto Saat Ini
                                    </span>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <img src="{{ asset('storage/' . $existingImage) }}" alt="Foto Material" class="img-thumbnail rounded-3" style="max-height: 90px; object-fit: cover;" />
                                    <p class="text-secondary extra-small mb-0">
                                        Unggah file baru di bawah jika ingin mengganti foto material ini.
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
                    <button type="button" class="btn btn-secondary fw-semibold px-3" wire:click="$set('showModal', false)">Batal</button>
                    <button type="button" class="btn btn-success fw-semibold px-4" wire:click="save" wire:loading.attr="disabled" wire:target="save">{{ $editMode ? 'Perbarui' : 'Simpan Material' }}</button>
                </div>
            </div>
        </div>
    </div>
    @endteleport
    @endif

    <!-- Modal: Restock Material -->
    @if($showRestockModal)
    @teleport('body')
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-bottom">
                    <div>
                        <h5 class="modal-title font-outfit fw-bold">Restock Material</h5>
                        <div class="small text-secondary">Tambah stok untuk <strong>{{ $restockMaterialName }}</strong></div>
                    </div>
                    <button type="button" class="btn-close" wire:click="$set('showRestockModal', false)"></button>
                </div>
                <div class="modal-body py-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Jumlah ({{ $restockMaterialUnit }})</label>
                            <input type="number" step="0.01" min="0.01" wire:model="restockQuantity" class="form-control" />
                            @error('restockQuantity') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-6" x-data="{
                            display: '',
                            init() {
                                this.display = this.format($wire.restockUnitPrice);
                                this.$watch('display', val => {
                                    let digits = val.replace(/\D/g, '');
                                    if (digits === '') {
                                        $wire.restockUnitPrice = null;
                                        this.display = '';
                                        return;
                                    }
                                    let num = parseInt(digits, 10);
                                    let formatted = this.format(num);
                                    if (this.display !== formatted) {
                                        this.display = formatted;
                                    }
                                    $wire.restockUnitPrice = num;
                                });
                                $wire.$watch('restockUnitPrice', val => {
                                    if (document.activeElement !== this.$refs.restockPriceInput) {
                                        this.display = this.format(val);
                                    }
                                });
                            },
                            format(num) {
                                if (num === null || num === undefined || num === '') return '';
                                return 'Rp' + num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                            }
                        }">
                            <label class="form-label font-semibold">Harga Satuan</label>
                            <input type="text" x-ref="restockPriceInput" x-model="display" class="form-control" />
                            @error('restockUnitPrice') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="mb-3" x-data="{ supPickerOpen: false, supSearch: '' }">
                        <label class="form-label font-semibold">Supplier</label>
                        <div class="position-relative" @click.outside="supPickerOpen = false">
                            <div class="position-relative d-flex align-items-center">
                                <input
                                    type="text"
                                    wire:model="restockSupplierName"
                                    class="form-control pe-5"
                                    placeholder="Pilih atau ketik nama supplier..."
                                    @focus="supPickerOpen = true; supSearch = $wire.restockSupplierName || ''"
                                    @input="supPickerOpen = true; supSearch = $el.value"
                                />
                                <button
                                    type="button"
                                    class="btn btn-link text-secondary text-decoration-none position-absolute end-0 me-2 p-1 d-flex align-items-center"
                                    @click="supPickerOpen = !supPickerOpen; supSearch = $wire.restockSupplierName || ''"
                                    aria-label="Tampilkan pilihan supplier"
                                >
                                    <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor" class="transition-transform" :class="supPickerOpen ? 'rotate-180' : ''" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/>
                                    </svg>
                                </button>
                            </div>

                            <div
                                x-show="supPickerOpen"
                                x-cloak
                                class="card shadow-lg border rounded-3 position-absolute w-100 mt-1 bg-body overflow-hidden"
                                style="max-height: 200px; z-index: 1050;"
                            >
                                <div class="p-1.5" style="max-height: 190px; overflow-y: auto; -webkit-overflow-scrolling: touch;">
                                    @foreach ($suppliers as $sup)
                                        <button
                                            type="button"
                                            class="dropdown-item rounded-2 py-2 px-3 text-start w-100 font-semibold"
                                            :class="$wire.restockSupplierName === @js($sup->name) ? 'active bg-success text-white' : ''"
                                            x-show="supSearch === '' || @js(strtolower($sup->name)).includes(supSearch.toLowerCase())"
                                            @click="$wire.restockSupplierName = @js($sup->name); supPickerOpen = false; supSearch = @js($sup->name);"
                                        >
                                            {{ $sup->name }}
                                        </button>
                                    @endforeach
                                    <div x-show="supSearch !== '' && !@js($suppliers->pluck('name')->map(fn($n) => strtolower($n))->all()).includes(supSearch.toLowerCase())" class="p-2 border-top extra-small text-secondary bg-body-tertiary">
                                        <span>Ketik <strong>&quot;<span x-text="supSearch"></span>&quot;</strong> untuk menambahkan supplier baru secara otomatis saat disimpan.</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @error('restockSupplierName') <span class="text-danger small d-block mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Tanggal</label>
                            <input type="date" wire:model="restockDate" class="form-control" />
                            @error('restockDate') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Foto Bukti / Surat Jalan <span class="text-secondary fw-normal">(opsional)</span></label>
                            <input type="file" wire:model="restockProofImage" accept="image/*" class="form-control" />
                            @error('restockProofImage') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-semibold">Catatan (opsional)</label>
                        <input type="text" wire:model="restockNotes" class="form-control" placeholder="No. nota, keterangan..." />
                    </div>
                    <div class="card bg-success-subtle border-success-subtle p-3" x-data="{
                        get totalCost() {
                            let qty = parseFloat($wire.restockQuantity) || 0;
                            let price = parseFloat($wire.restockUnitPrice) || 0;
                            return qty * price;
                        },
                        formatRp(num) {
                            return 'Rp' + num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                        }
                    }">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-semibold text-success">Total Biaya Restock</span>
                            <span class="fs-5 fw-bold text-success font-mono" x-text="formatRp(totalCost)"></span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top bg-body-tertiary rounded-bottom-4">
                    <button type="button" class="btn btn-secondary fw-semibold" wire:click="$set('showRestockModal', false)">Batal</button>
                    <button type="button" class="btn btn-success fw-semibold" wire:click="saveRestock" wire:loading.attr="disabled" wire:target="saveRestock">Simpan Restock</button>
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
                    <button type="button" class="btn btn-danger btn-sm fw-semibold" wire:click="executeConfirmedAction" wire:loading.attr="disabled" wire:target="executeConfirmedAction">Ya, Hapus</button>
                </div>
            </div>
        </div>
    </div>
    @endteleport
    @endif

    <!-- Import Excel Modal -->
    @if($showImportModal)
    @teleport('body')
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title font-outfit fw-bold d-flex align-items-center gap-2">
                        <svg width="20" height="20" fill="currentColor" class="text-primary" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5"/><path d="M7.646 1.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 2.707V10.5a.5.5 0 0 1-1 0V2.707L5.354 4.854a.5.5 0 1 1-.708-.708z"/></svg>
                        Import & Validasi Data Material
                    </h5>
                    <button type="button" class="btn-close" wire:click="$set('showImportModal', false)"></button>
                </div>
                <div class="modal-body py-4">
                    @if(!$importResultSummary)
                        <p class="text-secondary small mb-3">
                            Unggah berkas Excel (<code>.xlsx</code> / <code>.xls</code>) berisi inventaris stok material atau transaksi catatan (restock / alokasi rumah). Sistem akan membaca, memvalidasi integritas baris, dan menambahkan data secara otomatis.
                        </p>

                        <div class="bg-body-tertiary p-3 rounded-3 mb-3 border">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <h6 class="fw-bold extra-small text-uppercase text-secondary mb-0">Panduan Kolom Excel</h6>
                                <a href="{{ asset('sample_material_import.xlsx') }}" download class="btn btn-outline-success btn-sm py-1 px-2.5 extra-small fw-semibold d-inline-flex align-items-center gap-1">
                                    <svg width="13" height="13" fill="currentColor" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z"/></svg>
                                    <span>Unduh Format Contoh (.xlsx)</span>
                                </a>
                            </div>
                            <ul class="extra-small text-secondary mb-0 ps-3">
                                <li><strong>Stok Inventaris:</strong> <code>Nama Material</code>, <code>Kategori</code>, <code>Satuan</code>, <code>Harga Satuan</code>, <code>Sisa Stok</code>, <code>Supplier</code></li>
                                <li><strong>Catatan Transaksi:</strong> Tambahkan kolom <code>Jenis</code> (masuk/keluar), <code>Unit Rumah</code> (mis. Blok A-01), <code>Tanggal</code>, <code>Catatan</code></li>
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
                            <div class="fs-3">✅</div>
                            <div>
                                <h6 class="fw-bold mb-1">Validasi Impor Selesai!</h6>
                                <p class="mb-0 small">Seluruh baris data pada berkas Excel telah diproses dan divalidasi ke database.</p>
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
                                    <span class="d-block extra-small fw-bold text-uppercase">Material Baru</span>
                                    <span class="fs-5 fw-bold">{{ $importResultSummary['materialsImported'] }}</span>
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
                                        <th style="width: 60px;">Baris</th>
                                        <th>Material</th>
                                        <th>Status Validasi</th>
                                        <th>Keterangan Sistem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($importResultSummary['logs'] as $log)
                                        <tr>
                                            <td class="fw-mono text-center">#{{ $log['row'] }}</td>
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
                        <button type="button" class="btn btn-secondary fw-semibold" wire:click="$set('showImportModal', false)">Batal</button>
                        <button type="button" class="btn btn-primary fw-semibold" wire:click="importExcel" wire:loading.attr="disabled">
                            <svg width="14" height="14" fill="currentColor" class="me-1" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5"/><path d="M7.646 1.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 2.707V10.5a.5.5 0 0 1-1 0V2.707L5.354 4.854a.5.5 0 1 1-.708-.708z"/></svg>
                            Proses & Validasi Import
                        </button>
                    @else
                        <button type="button" class="btn btn-success fw-semibold px-4" wire:click="$set('showImportModal', false)">Tutup & Selesai</button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endteleport
    @endif

    <!-- View Material Image Modal -->
    @if($showImageModal)
    @teleport('body')
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title font-outfit fw-bold d-flex align-items-center gap-2">
                        <svg width="18" height="18" fill="currentColor" class="text-info" viewBox="0 0 16 16"><path d="M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0"/><path d="M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8m8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7"/></svg>
                        Foto Material: {{ $viewingImageMaterialName }}
                    </h5>
                    <button type="button" class="btn-close" wire:click="$set('showImageModal', false)"></button>
                </div>
                <div class="modal-body p-3 text-center bg-dark-subtle">
                    <img src="{{ $viewingImageUrl }}" alt="{{ $viewingImageMaterialName }}" class="img-fluid rounded-3 border shadow" style="max-height: 480px; object-fit: contain;" />
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

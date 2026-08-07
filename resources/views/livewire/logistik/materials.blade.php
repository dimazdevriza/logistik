<div>
    <div class="container-fluid p-0">
        <!-- Hero Header -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-body-tertiary">
            <div class="card-body p-4 p-md-5 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 text-uppercase mb-2 font-geist small">Inventory Module</span>
                    <h1 class="display-5 fw-black text-body mb-2 font-outfit">
                        Material <span class="text-success">Stock Control</span>
                    </h1>
                    <p class="text-secondary mb-0 max-w-xl">
                        Manage building material stocks, execute restocks, and monitor resource pricing.
                    </p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @if(in_array(auth()->user()->role, ['admin', 'logistik']))
                        <button type="button" wire:click="exportExcel" class="btn btn-outline-success font-semibold">Export Excel</button>
                    @endif
                    <button type="button" wire:click="create" class="btn btn-success font-semibold">+ Tambah Material</button>
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
                        <div class="p-2 bg-warning-subtle text-warning rounded">💵</div>
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
                        <div class="p-2 bg-primary-subtle text-primary rounded">📦</div>
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
                <x-filter-modal :activeFiltersCount="$this->getActiveFiltersCount()">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase text-secondary">Urutkan Berdasarkan</label>
                        <select wire:model.live="sort" class="form-select">
                            <option value="name_asc">Nama A-Z</option>
                            <option value="name_desc">Nama Z-A</option>
                            <option value="stock_asc">Stok Terendah</option>
                            <option value="stock_desc">Stok Tertinggi</option>
                            <option value="unit_price_asc">Harga Terendah</option>
                            <option value="unit_price_desc">Harga Tertinggi</option>
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
                        <label class="form-label small fw-bold text-uppercase text-secondary">Status Stok</label>
                        <select wire:model.live="filterStock" class="form-select">
                            <option value="">Semua Stok</option>
                            <option value="safe">Aman (&gt; 10)</option>
                            <option value="low">Menipis (&le; 10)</option>
                            <option value="empty">Habis (0)</option>
                        </select>
                    </div>
                </x-filter-modal>
            </div>
        </div>

        <!-- Materials Table -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-uppercase small font-geist">
                        <tr>
                            <th class="text-center" style="width: 50px;">No.</th>
                            <th>Nama</th>
                            <th>Kategori</th>
                            <th>Supplier</th>
                            <th class="text-end">Stok + Satuan</th>
                            <th class="text-end">Harga Satuan</th>
                            <th class="text-end">Total Nilai</th>
                            <th class="text-end" style="width: 140px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($materials as $material)
                        <tr wire:key="mat-{{ $material->id }}" style="cursor: pointer;" x-on:click="if (!$event.target.closest('button') && !$event.target.closest('a') && !$event.target.closest('input')) { $wire.edit({{ $material->id }}) }">
                            <td class="text-center text-secondary small">{{ ($materials->currentPage() - 1) * $materials->perPage() + $loop->iteration }}</td>
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
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" wire:click="restock({{ $material->id }})" class="btn btn-outline-success" title="Restock">🔄</button>
                                    <button type="button" wire:click="edit({{ $material->id }})" class="btn btn-outline-secondary" title="Edit">✏️</button>
                                    <button type="button" wire:click="confirm('delete', {{ $material->id }}, 'Hapus Material?', 'Apakah Anda yakin ingin menghapus material ini? Seluruh data stok terkait akan dihapus permanen.')" class="btn btn-outline-danger" title="Hapus">🗑️</button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-secondary">Belum ada data material.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="d-flex justify-content-end">{{ $materials->links() }}</div>
    </div>

    <!-- Modal: Create / Edit Material -->
    @if($showModal)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title font-outfit fw-bold">{{ $editMode ? 'Edit Material' : 'Tambah Material' }}</h5>
                    <button type="button" class="btn-close" wire:click="$set('showModal', false)"></button>
                </div>
                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label class="form-label font-semibold">Nama Material</label>
                        <input type="text" wire:model="name" class="form-control" placeholder="Contoh: Semen Portland 50kg" />
                        @error('name') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Kategori</label>
                            <select wire:model="category_id" class="form-select">
                                <option value="">-- Pilih --</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Supplier</label>
                            <input type="text" wire:model="supplier_name" list="suppliers-list" class="form-control" placeholder="Pilih atau ketik nama baru..." />
                            <datalist id="suppliers-list">
                                @foreach ($suppliers as $sup)
                                    <option value="{{ $sup->name }}"></option>
                                @endforeach
                            </datalist>
                            @error('supplier_name') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label font-semibold">Satuan</label>
                            <select wire:model="unit" class="form-select">
                                <option value="">-- Pilih --</option>
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
                        <div class="col-md-4" x-data="{
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
                                return 'Rp' + num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                            }
                        }">
                            <label class="form-label font-semibold">Harga Satuan</label>
                            <input type="text" x-ref="input" x-model="display" class="form-control" />
                            @error('unit_price') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label font-semibold">Stok Awal</label>
                            <input type="number" step="0.01" min="0" wire:model="stock" class="form-control" />
                            @error('stock') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-secondary font-semibold" wire:click="$set('showModal', false)">Batal</button>
                    <button type="button" class="btn btn-success font-semibold" wire:click="save">{{ $editMode ? 'Perbarui' : 'Simpan' }}</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal: Restock Material -->
    @if($showRestockModal)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
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
                    <div class="mb-3">
                        <label class="form-label font-semibold">Supplier</label>
                        <input type="text" wire:model="restockSupplierName" list="restock-suppliers-list" class="form-control" placeholder="Pilih atau ketik nama baru..." />
                        <datalist id="restock-suppliers-list">
                            @foreach ($suppliers as $sup)
                                <option value="{{ $sup->name }}"></option>
                            @endforeach
                        </datalist>
                        @error('restockSupplierName') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Tanggal</label>
                            <input type="date" wire:model="restockDate" class="form-control" />
                            @error('restockDate') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Catatan (opsional)</label>
                            <input type="text" wire:model="restockNotes" class="form-control" placeholder="No. nota, keterangan..." />
                        </div>
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
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-secondary font-semibold" wire:click="$set('showRestockModal', false)">Batal</button>
                    <button type="button" class="btn btn-success font-semibold" wire:click="saveRestock">Simpan Restock</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Confirmation Modal -->
    @if($showConfirmation)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title font-outfit fw-bold">{{ $confirmTitle ?? 'Konfirmasi' }}</h5>
                    <button type="button" class="btn-close" wire:click="$set('showConfirmation', false)"></button>
                </div>
                <div class="modal-body py-3">
                    <p class="text-secondary mb-0">{{ $confirmMessage ?? 'Apakah Anda yakin ingin melakukan tindakan ini?' }}</p>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-secondary btn-sm font-semibold" wire:click="$set('showConfirmation', false)">Batal</button>
                    <button type="button" class="btn btn-danger btn-sm font-semibold" wire:click="executeConfirmedAction">Ya, Hapus</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

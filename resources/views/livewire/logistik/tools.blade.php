<div>
    <div class="container-fluid p-0">
        <!-- Hero Header -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-body-tertiary">
            <div class="card-body p-4 p-md-5 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 text-uppercase mb-2 font-geist small">Equipment Module</span>
                    <h1 class="display-5 fw-black text-body mb-2 font-outfit">
                        Equipment <span class="text-success">Inventory Control</span>
                    </h1>
                    <p class="text-secondary mb-0 max-w-xl">
                        Manage construction tools, equipment condition statuses, and project allocations.
                    </p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @if(in_array(auth()->user()->role, ['admin', 'logistik']))
                        <button type="button" wire:click="exportExcel" class="btn btn-outline-success font-semibold">Export Excel</button>
                    @endif
                    <button type="button" wire:click="create" class="btn btn-success font-semibold">+ Tambah Alat</button>
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

        <!-- Bento Stats Grid -->
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card border-0 border-start border-4 border-primary shadow-sm rounded-4 p-4 bg-body-tertiary">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="small fw-bold text-secondary text-uppercase tracking-wider">Total Alat</span>
                        <div class="p-2 bg-primary-subtle text-primary rounded">🔧</div>
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
                        <div class="p-2 bg-success-subtle text-success rounded">✅</div>
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
                </x-filter-modal>
            </div>
        </div>

        <!-- Tools Table -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-uppercase small font-geist">
                        <tr>
                            <th class="text-center" style="width: 50px;">No.</th>
                            <th>Kode</th>
                            <th>Nama</th>
                            <th>Kategori</th>
                            <th>Kondisi</th>
                            <th class="text-end">Harga Beli</th>
                            <th class="text-center">Total</th>
                            <th class="text-center">Rusak</th>
                            <th class="text-center">Tersedia</th>
                            <th class="text-end" style="width: 100px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tools as $tool)
                        <tr wire:key="tool-{{ $tool->id }}" style="cursor: pointer;" x-on:click="if (!$event.target.closest('button') && !$event.target.closest('a') && !$event.target.closest('input')) { $wire.edit({{ $tool->id }}) }">
                            <td class="text-center text-secondary small">{{ ($tools->currentPage() - 1) * $tools->perPage() + $loop->iteration }}</td>
                            <td class="font-mono text-secondary small">{{ $tool->code }}</td>
                            <td class="fw-bold text-body">{{ $tool->name }}</td>
                            <td class="text-secondary small">{{ $tool->category?->name ?? '-' }}</td>
                            <td>
                                @php 
                                    $badgeClasses = ['baik' => 'bg-success-subtle text-success', 'rusak' => 'bg-danger-subtle text-danger', 'hilang' => 'bg-warning-subtle text-warning'];
                                @endphp
                                <span class="badge {{ $badgeClasses[$tool->condition] ?? 'bg-secondary-subtle text-secondary' }}">{{ ucfirst($tool->condition) }}</span>
                            </td>
                            <td class="text-end font-mono text-secondary">Rp {{ number_format($tool->purchase_price, 0, ',', '.') }}</td>
                            <td class="text-center fw-bold">{{ $tool->total_qty }}</td>
                            <td class="text-center">
                                <span class="{{ $tool->qty_broken > 0 ? 'text-amber-600 dark:text-amber-400 font-bold' : 'dark:text-zinc-100 font-bold' }}">{{ $tool->qty_broken }}</span>
                            </td>
                            <td class="text-center">
                                <span class="{{ $tool->available_qty === 0 ? 'badge bg-danger-subtle text-danger' : 'fw-bold' }}">{{ $tool->available_qty }}</span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" wire:click="edit({{ $tool->id }})" class="btn btn-outline-secondary" title="Edit">✏️</button>
                                    <button type="button" wire:click="confirm('delete', {{ $tool->id }}, 'Hapus Alat?', 'Apakah Anda yakin ingin menghapus alat ini dari inventaris?')" class="btn btn-outline-danger" title="Hapus">🗑️</button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center py-4 text-secondary">Belum ada data alat.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="d-flex justify-content-end">{{ $tools->links() }}</div>
    </div>

    <!-- Modal: Create / Edit Tool -->
    @if($showModal)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title font-outfit fw-bold">{{ $editMode ? 'Edit Alat' : 'Tambah Alat' }}</h5>
                    <button type="button" class="btn-close" wire:click="$set('showModal', false)"></button>
                </div>
                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label class="form-label font-semibold">Nama Alat</label>
                        <input type="text" wire:model="name" class="form-control" placeholder="Contoh: Molen Beton" />
                        @error('name') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Kategori</label>
                            <select wire:model.live="category_id" class="form-select">
                                <option value="">-- Pilih Kategori --</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            @error('category_id') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Kode Aset</label>
                            <div class="input-group">
                                <input type="text" wire:model="code" class="form-control bg-light" readonly placeholder="Pilih kategori..." />
                                <span class="input-group-text small fw-bold text-success font-geist">Otomatis</span>
                            </div>
                            @error('code') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-semibold">Kondisi</label>
                        <select wire:model="condition" class="form-select">
                            <option value="baik">Baik</option>
                            <option value="rusak">Rusak</option>
                            <option value="hilang">Hilang</option>
                        </select>
                        @error('condition') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4" x-data="{ 
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
                                return 'Rp ' + num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                            }
                        }">
                            <label class="form-label font-semibold">Harga Beli</label>
                            <input type="text" x-ref="input" x-model="display" class="form-control" />
                            @error('purchase_price') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label font-semibold">Total Qty</label>
                            <input type="number" wire:model="total_qty" class="form-control" />
                            @error('total_qty') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label font-semibold">Tersedia</label>
                            <input type="number" wire:model="available_qty" class="form-control" />
                            @error('available_qty') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label font-semibold">Rusak</label>
                            <input type="number" wire:model="qty_broken" class="form-control" />
                            @error('qty_broken') <span class="text-danger small">{{ $message }}</span> @enderror
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
                    <button type="button" class="btn btn-danger btn-sm font-semibold" wire:click="executeConfirmedAction">{{ $confirmingAction === 'fixTool' ? 'Ya, Konfirmasi' : 'Ya, Hapus' }}</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

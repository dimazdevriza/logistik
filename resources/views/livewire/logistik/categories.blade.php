<div>
    <div class="container-fluid p-0">
        <!-- Hero Header -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-body-tertiary">
            <div class="card-body p-4 p-md-5 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 text-uppercase mb-2 font-geist small">Pengelolaan Kategori</span>
                    <h1 class="display-5 fw-black text-body mb-2 font-outfit">
                        Kategori <span class="text-success">Inventaris</span>
                    </h1>
                    <p class="text-secondary mb-0 max-w-xl">
                        Kelola dan kelompokkan jenis material dan alat konstruksi perumahan.
                    </p>
                </div>
                <div>
                    <button type="button" wire:click="create" class="btn btn-success font-semibold">+ Tambah Kategori</button>
                </div>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Search & Filter Controls -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 p-3 bg-body-tertiary">
            <div class="d-flex flex-column flex-md-row gap-3 align-items-md-center justify-content-between">
                <div class="d-flex flex-column flex-sm-row gap-2 w-100 max-w-lg">
                    <div class="position-relative flex-grow-1">
                        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama kategori..." class="form-control" style="height: 42px;" />
                    </div>
                    <div style="min-width: 160px;">
                        <select wire:model.live="filterType" class="form-select" style="height: 42px;">
                            <option value="">Semua Tipe</option>
                            <option value="material">Material</option>
                            <option value="tool">Alat Kerja</option>
                        </select>
                    </div>
                </div>
                @if ($search || $filterType)
                    <button 
                        type="button" 
                        wire:click="resetFilters" 
                        class="btn btn-outline-danger px-3 d-inline-flex align-items-center gap-1 font-semibold shadow-xs" 
                        style="height: 42px;"
                        title="Reset Filter"
                    >
                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/></svg>
                        <span>Reset</span>
                    </button>
                @endif
            </div>
        </div>

        <!-- Categories Table -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 bg-body-tertiary">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light border-bottom text-uppercase extra-small font-geist tracking-wider text-secondary">
                        <tr>
                            <th class="text-center py-3" style="width: 60px;">No.</th>
                            <th class="py-3">Nama Kategori</th>
                            <th class="py-3 text-center" style="width: 160px;">Tipe Inventaris</th>
                            <th class="text-end py-3 pe-4" style="width: 140px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        @forelse ($categories as $category)
                        <tr wire:key="cat-{{ $category->id }}" style="cursor: pointer;" x-on:click="if (!$event.target.closest('button') && !$event.target.closest('a') && !$event.target.closest('input')) { $wire.edit({{ $category->id }}) }">
                            <td class="text-center text-secondary small font-mono">{{ ($categories->currentPage() - 1) * $categories->perPage() + $loop->iteration }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="p-2 rounded-3 {{ $category->type === 'material' ? 'bg-primary-subtle text-primary' : 'bg-warning-subtle text-warning' }} d-flex align-items-center justify-content-center">
                                        @if($category->type === 'material')
                                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5l6.154 2.38 6.154-2.38zM15 4.239l-6.5 2.515v7.182l6.5-2.6v-7.097zM7.5 13.936V6.754L1 4.239v7.097z"/></svg>
                                        @else
                                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M1 0 0 1l2.2 3.081a1 1 0 0 0 .815.419h.07a1 1 0 0 1 .708.293l2.675 2.675-2.617 2.654A3.003 3.003 0 0 0 0 13a3 3 0 1 0 5.878-.851l2.654-2.617.968.968-.305.914a1 1 0 0 0 .242 1.023l3.27 3.27a.997.997 0 0 0 1.414 0l1.586-1.586a.997.997 0 0 0 0-1.414l-3.27-3.27a1 1 0 0 0-1.023-.242L10.5 9.5l-.96-.96 2.68-2.643A3.005 3.005 0 0 0 16 3c0-.269-.035-.53-.102-.777l-3.04 3.04a1 1 0 0 1-1.414 0l-1.414-1.414a1 1 0 0 1 0-1.414l3.04-3.04A3.02 3.02 0 0 0 13 0c-1.398 0-2.582.955-2.919 2.25L7.427 4.904l-2.676-2.676A1 1 0 0 1 4.459 1.52l-.004-.052A1 1 0 0 0 4.037.653z"/></svg>
                                        @endif
                                    </div>
                                    <span class="fw-bold text-body font-outfit fs-6">{{ $category->name }}</span>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $category->type === 'material' ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-warning-subtle text-warning border border-warning-subtle' }} rounded-pill px-3 py-2 font-mono extra-small text-uppercase">
                                    {{ $category->type === 'material' ? 'Material' : 'Alat Kerja' }}
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-inline-flex align-items-center gap-1">
                                    <button type="button" wire:click="edit({{ $category->id }})" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center justify-content-center p-2 rounded-3 shadow-xs" title="Edit Kategori">
                                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325"/></svg>
                                    </button>
                                    <button type="button" wire:click="confirm('delete', {{ $category->id }}, 'Hapus Kategori?', 'Yakin ingin menghapus kategori ini? Seluruh data material dan alat di dalamnya akan tetap ada namun referensi kategori akan hilang.')" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center justify-content-center p-2 rounded-3 shadow-xs" title="Hapus Kategori">
                                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/><path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-5 text-secondary">
                                <div class="d-flex flex-column align-items-center justify-content-center py-3">
                                    <svg width="40" height="40" fill="currentColor" class="text-secondary opacity-50 mb-3" viewBox="0 0 16 16"><path d="M2 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2zm10-1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1z"/><path d="M6 5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v1a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V5z"/></svg>
                                    <div class="fw-bold text-body font-outfit mb-1">Belum Ada Kategori Ditemukan</div>
                                    <div class="small text-secondary">Silakan tambahkan kategori baru atau sesuaikan kata kunci pencarian.</div>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">{{ $categories->links('vendor.livewire.bootstrap') }}</div>
    </div>

    <!-- Modal: Create / Edit Category -->
    @if($showModal)
    @teleport('body')
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header border-bottom p-4 bg-body-tertiary">
                    <div>
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1 extra-small text-uppercase mb-1 font-geist">Formulir Kategori</span>
                        <h5 class="modal-title font-outfit fw-bold mb-0 text-body">{{ $editMode ? 'Edit Data Kategori' : 'Tambah Kategori Baru' }}</h5>
                    </div>
                    <button type="button" class="btn-close" wire:click="$set('showModal', false)"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label font-semibold text-body small">Nama Kategori <span class="text-danger">*</span></label>
                        <input type="text" wire:model="name" class="form-control form-control-lg fs-6" placeholder="Contoh: Semen & Pasir, Besi Beton" />
                        @error('name') <span class="text-danger small mt-1 d-block">{{ $message }}</span> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-semibold text-body small">Tipe Inventaris <span class="text-danger">*</span></label>
                        <select wire:model="type" class="form-select form-select-lg fs-6">
                            <option value="material">Material Bangunan</option>
                            <option value="tool">Alat Kerja Proyek</option>
                        </select>
                        @error('type') <span class="text-danger small mt-1 d-block">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="modal-footer border-top bg-body-tertiary rounded-bottom-4 p-3 px-4 d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary fw-semibold px-4" wire:click="$set('showModal', false)">Batal</button>
                    <button type="button" class="btn btn-success fw-semibold px-4 shadow-xs" wire:click="save" wire:loading.attr="disabled" wire:target="save">{{ $editMode ? 'Simpan Perubahan' : 'Simpan Kategori' }}</button>
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
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header border-bottom p-3 bg-body-tertiary">
                    <h5 class="modal-title font-outfit fw-bold text-body fs-6">{{ $confirmTitle ?? 'Konfirmasi' }}</h5>
                    <button type="button" class="btn-close" wire:click="$set('showConfirmation', false)"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <div class="p-3 bg-danger-subtle text-danger rounded-circle d-inline-flex align-items-center justify-content-center mb-3">
                        <svg width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/></svg>
                    </div>
                    <p class="text-secondary small mb-0">{{ $confirmMessage ?? 'Apakah Anda yakin ingin melakukan tindakan ini?' }}</p>
                </div>
                <div class="modal-footer border-top bg-body-tertiary rounded-bottom-4 p-3 d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm fw-semibold px-3" wire:click="$set('showConfirmation', false)">Batal</button>
                    <button type="button" class="btn btn-danger btn-sm fw-semibold px-3 shadow-xs" wire:click="executeConfirmedAction" wire:loading.attr="disabled" wire:target="executeConfirmedAction">Ya, Hapus</button>
                </div>
            </div>
        </div>
    </div>
    @endteleport
    @endif
</div>

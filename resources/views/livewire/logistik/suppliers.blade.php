<div>
    <div class="container-fluid p-0">
        <!-- Hero Header -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-body-tertiary">
            <div class="card-body p-4 p-md-5 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 text-uppercase mb-2 font-geist small">Supplier Management</span>
                    <h1 class="display-5 fw-black text-body mb-2 font-outfit">
                        Vendor <span class="text-success">Suppliers</span>
                    </h1>
                    <p class="text-secondary mb-0 max-w-xl">
                        Kelola data supplier material bangunan dan kontak mitra pasokan.
                    </p>
                </div>
                <div>
                    <button type="button" wire:click="create" class="btn btn-success font-semibold">+ Tambah Supplier</button>
                </div>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Search Controls -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 p-3 bg-body-tertiary">
            <div class="d-flex flex-column flex-md-row gap-3 align-items-md-center">
                <div class="w-100 max-w-sm">
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari supplier..." class="form-control" />
                </div>
                @if ($search)
                    <button type="button" wire:click="resetFilters" class="btn btn-link text-secondary text-decoration-none btn-sm">✕ Reset Filter</button>
                @endif
            </div>
        </div>

        <!-- Suppliers Table -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-uppercase small font-geist">
                        <tr>
                            <th class="text-center" style="width: 50px;">No.</th>
                            <th>Nama</th>
                            <th>Kontak</th>
                            <th>Telepon</th>
                            <th>Alamat</th>
                            <th class="text-end" style="width: 100px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($suppliers as $supplier)
                        <tr wire:key="sup-{{ $supplier->id }}" style="cursor: pointer;" x-on:click="if (!$event.target.closest('button') && !$event.target.closest('a') && !$event.target.closest('input')) { $wire.edit({{ $supplier->id }}) }">
                            <td class="text-center text-secondary small">{{ ($suppliers->currentPage() - 1) * $suppliers->perPage() + $loop->iteration }}</td>
                            <td class="fw-bold text-body">{{ $supplier->name }}</td>
                            <td class="text-secondary small">{{ $supplier->contact_person ?? '-' }}</td>
                            <td class="text-secondary small">{{ $supplier->phone ?? '-' }}</td>
                            <td class="text-secondary small text-truncate" style="max-width: 200px;">{{ $supplier->address ?? '-' }}</td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" wire:click="edit({{ $supplier->id }})" class="btn btn-outline-secondary" title="Edit">✏️</button>
                                    <button type="button" wire:click="confirm('delete', {{ $supplier->id }}, 'Hapus Supplier?', 'Yakin ingin menghapus supplier ini? Seluruh data riwayat material terkait akan tetap ada namun referensi supplier akan hilang.')" class="btn btn-outline-danger" title="Hapus">🗑️</button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-secondary">Belum ada data supplier.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="d-flex justify-content-end">{{ $suppliers->links() }}</div>
    </div>

    <!-- Modal: Create / Edit Supplier -->
    @if($showModal)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title font-outfit fw-bold">{{ $editMode ? 'Edit Supplier' : 'Tambah Supplier' }}</h5>
                    <button type="button" class="btn-close" wire:click="$set('showModal', false)"></button>
                </div>
                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label class="form-label font-semibold">Nama Supplier</label>
                        <input type="text" wire:model="name" class="form-control" placeholder="PT. Example" />
                        @error('name') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-semibold">Nama Kontak</label>
                        <input type="text" wire:model="contact_person" class="form-control" placeholder="Nama kontak person" />
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-semibold">Telepon</label>
                        <input type="text" wire:model="phone" class="form-control" placeholder="0812-xxxx-xxxx" />
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-semibold">Alamat</label>
                        <textarea wire:model="address" class="form-control" rows="2" placeholder="Alamat lengkap supplier"></textarea>
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
                    <button type="button" class="btn btn-danger btn-sm font-semibold" wire:click="executeConfirmedAction">Ya, Hapus</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

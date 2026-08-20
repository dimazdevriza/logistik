<div>
    <div class="container-fluid p-0">
        <!-- Hero Header -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-body-tertiary">
            <div class="card-body p-4 p-md-5 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 text-uppercase mb-2 font-geist small">Pengelolaan Pemasok</span>
                    <h1 class="display-5 fw-black text-body mb-2 font-outfit">
                        Data Mitra <span class="text-success">Pemasok</span>
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
            <div class="d-flex flex-column flex-md-row gap-3 align-items-md-center justify-content-between">
                <div class="position-relative w-100 max-w-md">
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama supplier, kontak, atau telepon..." class="form-control ps-4" style="height: 42px;" />
                </div>
                @if ($search)
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

        <!-- Suppliers Table -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 bg-body-tertiary">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light border-bottom text-uppercase extra-small font-geist tracking-wider text-secondary">
                        <tr>
                            <th class="text-center py-3" style="width: 60px;">No.</th>
                            <th class="py-3">Nama Mitra Supplier</th>
                            <th class="py-3">Kontak Person</th>
                            <th class="py-3">No. Telepon</th>
                            <th class="py-3">Alamat</th>
                            <th class="text-end py-3 pe-4" style="width: 140px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        @forelse ($suppliers as $supplier)
                        <tr wire:key="sup-{{ $supplier->id }}" style="cursor: pointer;" x-on:click="if (!$event.target.closest('button') && !$event.target.closest('a') && !$event.target.closest('input')) { $wire.edit({{ $supplier->id }}) }">
                            <td class="text-center text-secondary small font-mono">{{ ($suppliers->currentPage() - 1) * $suppliers->perPage() + $loop->iteration }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="p-2 rounded-3 bg-success-subtle text-success d-flex align-items-center justify-content-center">
                                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M0 3.5A1.5 1.5 0 0 1 1.5 2h9A1.5 1.5 0 0 1 12 3.5V5h1.02a1.5 1.5 0 0 1 1.17.563l1.481 1.85a1.5 1.5 0 0 1 .329.938V10.5a1.5 1.5 0 0 1-1.5 1.5H14a2 2 0 1 1-4 0H5a2 2 0 1 1-4 0H1.5A1.5 1.5 0 0 1 0 10.5v-7zm1 0v7a.5.5 0 0 0 .5.5H2a2 2 0 0 1 3.5 0h4.5a2 2 0 0 1 3.5 0h1.5a.5.5 0 0 0 .5-.5V8.851a.5.5 0 0 0-.11-.312L14.41 6.689A.5.5 0 0 0 14.02 6.5H12v-3a.5.5 0 0 0-.5-.5h-9a.5.5 0 0 0-.5.5z"/></svg>
                                    </div>
                                    <span class="fw-bold text-body font-outfit fs-6">{{ $supplier->name }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-1 text-body small">
                                    @if($supplier->contact_person)
                                        <svg width="14" height="14" fill="currentColor" class="text-secondary" viewBox="0 0 16 16"><path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4zm-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10c-2.29 0-3.516.68-4.168 1.332-.678.678-.83 1.418-.832 1.664h10z"/></svg>
                                        <span>{{ $supplier->contact_person }}</span>
                                    @else
                                        <span class="text-secondary opacity-50">-</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if($supplier->phone)
                                    <span class="badge bg-secondary-subtle text-secondary font-mono px-2 py-1">
                                        {{ $supplier->phone }}
                                    </span>
                                @else
                                    <span class="text-secondary opacity-50 small">-</span>
                                @endif
                            </td>
                            <td>
                                @if($supplier->address)
                                    <span class="text-secondary small text-truncate d-inline-block" style="max-width: 260px;" title="{{ $supplier->address }}">
                                        {{ $supplier->address }}
                                    </span>
                                @else
                                    <span class="text-secondary opacity-50 small">-</span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-inline-flex align-items-center gap-1">
                                    <button type="button" wire:click="edit({{ $supplier->id }})" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center justify-content-center p-2 rounded-3 shadow-xs" title="Edit Supplier">
                                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325"/></svg>
                                    </button>
                                    <button type="button" wire:click="confirm('delete', {{ $supplier->id }}, 'Hapus Supplier?', 'Yakin ingin menghapus supplier ini? Seluruh data riwayat material terkait akan tetap ada namun referensi supplier akan hilang.')" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center justify-content-center p-2 rounded-3 shadow-xs" title="Hapus Supplier">
                                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/><path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-secondary">
                                <div class="d-flex flex-column align-items-center justify-content-center py-3">
                                    <svg width="40" height="40" fill="currentColor" class="text-secondary opacity-50 mb-3" viewBox="0 0 16 16"><path d="M0 3.5A1.5 1.5 0 0 1 1.5 2h9A1.5 1.5 0 0 1 12 3.5V5h1.02a1.5 1.5 0 0 1 1.17.563l1.481 1.85a1.5 1.5 0 0 1 .329.938V10.5a1.5 1.5 0 0 1-1.5 1.5H14a2 2 0 1 1-4 0H5a2 2 0 1 1-4 0H1.5A1.5 1.5 0 0 1 0 10.5v-7zm1 0v7a.5.5 0 0 0 .5.5H2a2 2 0 0 1 3.5 0h4.5a2 2 0 0 1 3.5 0h1.5a.5.5 0 0 0 .5-.5V8.851a.5.5 0 0 0-.11-.312L14.41 6.689A.5.5 0 0 0 14.02 6.5H12v-3a.5.5 0 0 0-.5-.5h-9a.5.5 0 0 0-.5.5z"/></svg>
                                    <div class="fw-bold text-body font-outfit mb-1">Belum Ada Data Supplier</div>
                                    <div class="small text-secondary">Silakan tambahkan supplier baru atau sesuaikan kata kunci pencarian.</div>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">{{ $suppliers->links('vendor.livewire.bootstrap') }}</div>
    </div>

    <!-- Modal: Create / Edit Supplier -->
    @if($showModal)
    @teleport('body')
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header border-bottom p-4 bg-body-tertiary">
                    <div>
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1 extra-small text-uppercase mb-1 font-geist">Formulir Pemasok</span>
                        <h5 class="modal-title font-outfit fw-bold mb-0 text-body">{{ $editMode ? 'Edit Data Supplier' : 'Tambah Supplier Baru' }}</h5>
                    </div>
                    <button type="button" class="btn-close" wire:click="$set('showModal', false)"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label font-semibold text-body small">Nama Supplier / Toko <span class="text-danger">*</span></label>
                        <input type="text" wire:model="name" class="form-control form-control-lg fs-6" placeholder="Contoh: PT. Semen Padang, Toko Bangunan Jaya" />
                        @error('name') <span class="text-danger small mt-1 d-block">{{ $message }}</span> @enderror
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label font-semibold text-body small">Kontak Person</label>
                            <input type="text" wire:model="contact_person" class="form-control" placeholder="Nama perwakilan" />
                            @error('contact_person') <span class="text-danger small mt-1 d-block">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold text-body small">Nomor Telepon</label>
                            <input type="text" wire:model="phone" class="form-control" placeholder="0812-xxxx-xxxx" />
                            @error('phone') <span class="text-danger small mt-1 d-block">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-semibold text-body small">Alamat Lengkap</label>
                        <textarea wire:model="address" class="form-control" rows="3" placeholder="Alamat lengkap toko / distributor pemasok"></textarea>
                        @error('address') <span class="text-danger small mt-1 d-block">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="modal-footer border-top bg-body-tertiary rounded-bottom-4 p-3 px-4 d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary fw-semibold px-4" wire:click="$set('showModal', false)">Batal</button>
                    <button type="button" class="btn btn-success fw-semibold px-4 shadow-xs" wire:click="save" wire:loading.attr="disabled" wire:target="save">{{ $editMode ? 'Simpan Perubahan' : 'Simpan Supplier' }}</button>
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

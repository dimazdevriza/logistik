<div>
    <div class="container-fluid p-0">
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-body-tertiary">
            <div class="card-body p-4 p-md-5 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <div>
                    <h1 class="display-5 fw-black text-body mb-2 font-outfit">Daftar <span class="text-success">Gudang</span></h1>
                    <p class="text-secondary mb-0 max-w-xl">Kelola lokasi penyimpanan material dan alat proyek.</p>
                </div>
                <button type="button" wire:click="create" class="btn btn-success fw-semibold">+ Tambah Gudang</button>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @error('delete')
            <div class="alert alert-danger" role="alert">{{ $message }}</div>
        @enderror

        <div class="card border-0 shadow-sm rounded-4 mb-4 p-3 bg-body-tertiary">
            <input type="search" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Cari nama atau alamat gudang..." aria-label="Cari gudang" />
        </div>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            @if ($warehouses->isEmpty())
                <div class="text-center py-5 px-3 text-secondary">{{ trim($search) !== '' ? 'Tidak ada gudang yang cocok dengan pencarian.' : 'Belum ada gudang. Tambahkan gudang pertama untuk menyimpan inventaris.' }}</div>
            @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-uppercase small font-geist">
                        <tr>
                            <th class="text-center" style="width: 60px;">No.</th>
                            <x-sortable-th field="name" :sort="$sort">Nama Gudang</x-sortable-th>
                            <x-sortable-th field="address" :sort="$sort">Alamat</x-sortable-th>
                            <x-sortable-th field="materials" :sort="$sort" class="text-center">Material</x-sortable-th>
                            <x-sortable-th field="tools" :sort="$sort" class="text-center">Alat</x-sortable-th>
                            <th class="text-end" style="width: 215px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($warehouses as $warehouse)
                            <tr wire:key="warehouse-{{ $warehouse->id }}">
                                <td class="text-center text-secondary small">{{ $warehouses->firstItem() + $loop->index }}</td>
                                <td class="fw-bold text-body">{{ $warehouse->name }}</td>
                                <td class="text-secondary small">{{ $warehouse->address ?: 'Tidak ada alamat' }}</td>
                                <td class="text-center">
                                    <div class="fw-semibold">{{ $warehouse->materials_count }} baris stok</div>
                                </td>
                                <td class="text-center">
                                    <div class="fw-semibold">{{ $warehouse->tools_count }} jenis</div>
                                    <div class="small text-secondary">{{ (int) ($warehouse->tools_sum_total_qty ?? 0) }} unit</div>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm data-row-actions">
                                        <a href="{{ route('logistik.warehouse-detail', $warehouse) }}" wire:navigate class="btn log-row-action log-row-action--edit">Detail</a>
                                        <button type="button" wire:click="edit({{ $warehouse->id }})" class="btn log-row-action log-row-action--quiet">Edit</button>
                                        <button type="button" wire:click="confirmDelete({{ $warehouse->id }})" class="btn log-row-action log-row-action--danger">Hapus</button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
        <div class="mt-3">{{ $warehouses->links('vendor.livewire.bootstrap') }}</div>
    </div>

    @if ($showModal)
        @teleport('body')
            <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog" wire:keydown.escape.window="$set('showModal', false)">
                <div class="modal-dialog modal-dialog-centered modal-md">
                    <div class="modal-content border-0 shadow-lg rounded-4">
                        <div class="modal-header border-bottom">
                            <h5 class="modal-title font-outfit fw-bold">{{ $editMode ? 'Edit Gudang' : 'Tambah Gudang' }}</h5>
                            <button type="button" class="btn-close" wire:click="$set('showModal', false)" aria-label="Tutup"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="warehouse-name">Nama Gudang</label>
                                <input id="warehouse-name" type="text" wire:model="name" class="form-control" placeholder="Contoh: Gudang Barat" autofocus />
                                @error('name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div>
                                <label class="form-label fw-semibold" for="warehouse-address">Alamat atau lokasi</label>
                                <textarea id="warehouse-address" wire:model="address" class="form-control" rows="3" placeholder="Keterangan lokasi, opsional"></textarea>
                                @error('address') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="modal-footer border-top bg-body-tertiary rounded-bottom-4">
                            <button type="button" class="btn btn-secondary fw-semibold" wire:click="$set('showModal', false)">Batal</button>
                            <button type="button" class="btn btn-success fw-semibold" wire:click="save" wire:loading.attr="disabled" wire:target="save"><span wire:loading.remove wire:target="save">{{ $editMode ? 'Simpan Perubahan' : 'Simpan Gudang' }}</span><span wire:loading wire:target="save">Menyimpan gudang…</span></button>
                        </div>
                    </div>
                </div>
            </div>
        @endteleport
    @endif

    @if ($showConfirmation)
        @teleport('body')
            <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog" wire:keydown.escape.window="$set('showConfirmation', false)">
                <div class="modal-dialog modal-dialog-centered modal-sm">
                    <div class="modal-content border-0 shadow-lg rounded-4">
                        <div class="modal-header border-bottom"><h5 class="modal-title font-outfit fw-bold">Hapus Gudang?</h5></div>
                        <div class="modal-body"><p class="text-secondary mb-0">Gudang kosong ini akan dihapus.</p></div>
                        <div class="modal-footer border-top bg-body-tertiary rounded-bottom-4">
                            <button type="button" class="btn btn-secondary btn-sm" wire:click="$set('showConfirmation', false)">Batal</button>
                            <button type="button" class="btn btn-danger btn-sm" wire:click="delete" wire:loading.attr="disabled" wire:target="delete">Ya, Hapus</button>
                        </div>
                    </div>
                </div>
            </div>
        @endteleport
    @endif
</div>

<div>
    <div class="container-fluid p-0">
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-body-tertiary">
            <div class="card-body p-4 p-md-5 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <div>
                    <h1 class="display-5 fw-black text-body mb-2 font-outfit">{{ auth()->user()->role === 'keuangan' ? 'Biaya' : 'Cluster' }} <span class="text-success">{{ auth()->user()->role === 'keuangan' ? 'Cluster' : 'Rumah' }}</span></h1>
                    <p class="text-secondary mb-0 max-w-xl">{{ auth()->user()->role === 'keuangan' ? 'Pilih cluster untuk melihat dan mencatat biaya.' : 'Kelompokkan unit rumah berdasarkan area proyek atau tahap pembangunan.' }}</p>
                </div>
                @if (auth()->user()->role === 'admin')
                    <button type="button" wire:click="create" class="btn btn-success fw-semibold">+ Tambah Cluster</button>
                @endif
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
            <input type="search" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Cari nama cluster..." aria-label="Cari cluster" />
        </div>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="table-responsive management-table-scroll" tabindex="0" role="region" aria-label="Daftar cluster">
                <table class="table table-hover align-middle mb-0 management-card-table">
                    <thead class="table-light text-uppercase small font-geist">
                        <tr>
                            <th class="text-center" style="width: 60px;">No.</th>
                            <x-sortable-th field="name" :sort="$sort">Nama Cluster</x-sortable-th>
                            <x-sortable-th field="houses" :sort="$sort">Rumah</x-sortable-th>
                            <x-sortable-th field="description" :sort="$sort">Catatan</x-sortable-th>
                            <th class="text-end" style="width: 150px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($clusters as $cluster)
                            <tr wire:key="cluster-{{ $cluster->id }}">
                                <td data-label="No." class="text-center text-secondary small"><span class="management-cell-value">{{ $clusters->firstItem() + $loop->index }}</span></td>
                                <td data-label="Nama cluster" class="fw-bold text-body data-key-name" title="{{ $cluster->name }}"><span class="management-cell-value">{{ $cluster->name }}</span></td>
                                <td data-label="Rumah"><span class="management-cell-value"><span class="fw-semibold">{{ $cluster->houses_count }} rumah</span>@if ($cluster->houses_count)<span class="d-block small text-secondary data-cell-truncate" title="{{ $cluster->houses->pluck('name')->join(', ') }}">{{ $cluster->houses->take(3)->pluck('name')->join(', ') }}@if ($cluster->houses_count > 3)<span>, +{{ $cluster->houses_count - 3 }} lainnya</span>@endif</span>@endif</span></td>
                                <td data-label="Catatan" class="text-secondary small data-cell-truncate" title="{{ $cluster->description ?: '-' }}"><span class="management-cell-value">{{ $cluster->description ?: '-' }}</span></td>
                            <td data-label="Aksi" class="text-end"><span class="management-cell-value"><span class="btn-group btn-group-sm data-row-actions">
                                        <a href="{{ route('logistik.cluster-expenses', $cluster) }}" wire:navigate class="btn log-row-action log-row-action--edit">Biaya</a>
                                        @if (auth()->user()->role === 'admin')
                                            <button type="button" wire:click="edit({{ $cluster->id }})" class="btn log-row-action log-row-action--quiet">Edit</button>
                                            <button type="button" wire:click="confirmDelete({{ $cluster->id }})" class="btn log-row-action log-row-action--danger">Hapus</button>
                                        @endif
                                    </span></span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="management-table-empty text-center py-5 text-secondary">Belum ada cluster. Tambahkan cluster pertama untuk mulai mengelompokkan rumah.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3">{{ $clusters->links('vendor.livewire.bootstrap') }}</div>
    </div>

    @if ($showModal)
        @teleport('body')
            <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog" wire:keydown.escape.window="$set('showModal', false)">
                <div class="modal-dialog modal-dialog-centered modal-md">
                    <div class="modal-content border-0 shadow-lg rounded-4">
                        <div class="modal-header border-bottom">
                            <h5 class="modal-title font-outfit fw-bold">{{ $editMode ? 'Edit Cluster' : 'Tambah Cluster' }}</h5>
                            <button type="button" class="btn-close" wire:click="$set('showModal', false)" aria-label="Tutup"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="cluster-name">Nama Cluster</label>
                                <input id="cluster-name" type="text" wire:model="name" class="form-control" placeholder="Contoh: Cluster A" autofocus />
                                @error('name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div>
                                <label class="form-label fw-semibold" for="cluster-description">Catatan</label>
                                <textarea id="cluster-description" wire:model="description" class="form-control" rows="3" placeholder="Keterangan lokasi atau tahap proyek, opsional"></textarea>
                                @error('description') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="modal-footer border-top bg-body-tertiary rounded-bottom-4">
                            <button type="button" class="btn btn-secondary fw-semibold" wire:click="$set('showModal', false)">Batal</button>
                            <button type="button" class="btn btn-success fw-semibold" wire:click="save" wire:loading.attr="disabled" wire:target="save"><span wire:loading.remove wire:target="save">{{ $editMode ? 'Simpan Perubahan' : 'Simpan Cluster' }}</span><span wire:loading wire:target="save">Menyimpan cluster…</span></button>
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
                        <div class="modal-header border-bottom"><h5 class="modal-title font-outfit fw-bold">Hapus Cluster?</h5></div>
                        <div class="modal-body"><p class="text-secondary mb-0">Cluster kosong ini akan dihapus.</p></div>
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

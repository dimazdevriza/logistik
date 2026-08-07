<div>
    <div class="container-fluid p-0">
        <!-- Hero Header -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-body-tertiary">
            <div class="card-body p-4 p-md-5 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 text-uppercase mb-2 font-geist small">Project Tracking</span>
                    <h1 class="display-5 fw-black text-body mb-2 font-outfit">
                        Housing <span class="text-success">Unit Registry</span>
                    </h1>
                    <p class="text-secondary mb-0 max-w-xl">
                        Monitor residential unit construction progress, project timelines, and raw material utilization.
                    </p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @if(in_array(auth()->user()->role, ['admin', 'logistik']))
                        <button type="button" wire:click="exportExcel" class="btn btn-outline-success font-semibold">Export Excel</button>
                    @endif
                    <button type="button" wire:click="create" class="btn btn-success font-semibold">+ Tambah Rumah</button>
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
                <div class="w-100 max-w-sm">
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari rumah, kode, atau tipe..." class="form-control" />
                </div>
                <x-filter-modal :activeFiltersCount="$this->getActiveFiltersCount()">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase text-secondary">Status Rumah</label>
                        <select wire:model.live="filterStatus" class="form-select">
                            <option value="">Semua Status</option>
                            <option value="perencanaan">Perencanaan</option>
                            <option value="pembangunan">Pembangunan</option>
                            <option value="selesai">Selesai</option>
                        </select>
                    </div>
                </x-filter-modal>
            </div>
        </div>

        <!-- Houses Table -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-uppercase small font-geist">
                        <tr>
                            <th class="text-center" style="width: 50px;">No.</th>
                            <th>Kode</th>
                            <th>Nama / Blok</th>
                            <th>Tipe</th>
                            <th>Status</th>
                            <th class="text-end">Total Biaya</th>
                            <th>Mulai</th>
                            <th>Target Selesai</th>
                            <th class="text-end" style="width: 120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($houses as $house)
                        <tr wire:key="house-{{ $house->id }}" style="cursor: pointer;" x-on:click="if (!$event.target.closest('button') && !$event.target.closest('a')) { window.Livewire.navigate('{{ route('logistik.house-detail', $house) }}') }">
                            <td class="text-center text-secondary small">{{ $houses->firstItem() + $loop->index }}</td>
                            <td class="font-mono text-secondary small">{{ $house->house_code ?? '-' }}</td>
                            <td class="fw-bold text-body">{{ $house->name }}</td>
                            <td class="text-secondary small">{{ $house->type }}</td>
                            <td>
                                @php
                                    $statusClasses = ['perencanaan' => 'bg-warning-subtle text-warning', 'pembangunan' => 'bg-primary-subtle text-primary', 'selesai' => 'bg-success-subtle text-success'];
                                @endphp
                                <span class="badge {{ $statusClasses[$house->status] ?? 'bg-secondary-subtle text-secondary' }}">{{ ucfirst($house->status) }}</span>
                            </td>
                            <td class="text-end font-mono fw-bold text-body">Rp {{ number_format($house->total_material_cost, 0, ',', '.') }}</td>
                            <td class="text-secondary small">{{ $house->start_date?->format('d/m/Y') ?? '-' }}</td>
                            <td class="text-secondary small">{{ $house->target_end_date?->format('d/m/Y') ?? '-' }}</td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('logistik.house-detail', $house) }}" wire:navigate class="btn btn-outline-secondary" title="Lihat Detail">👁️</a>
                                    <button type="button" wire:click="edit({{ $house->id }})" class="btn btn-outline-secondary" title="Edit">✏️</button>
                                    <button type="button" wire:click="confirm('delete', {{ $house->id }}, 'Hapus Rumah?', 'Yakin ingin menghapus data rumah ini? Semua data penggunaan material dan peminjaman alat terkait akan ikut dihapus secara permanen.')" class="btn btn-outline-danger" title="Hapus">🗑️</button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-secondary">Belum ada data rumah.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="d-flex justify-content-end">{{ $houses->links() }}</div>
    </div>

    <!-- Modal: Create / Edit House -->
    @if($showModal)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title font-outfit fw-bold">{{ $editMode ? 'Edit Rumah' : 'Tambah Rumah' }}</h5>
                    <button type="button" class="btn-close" wire:click="$set('showModal', false)"></button>
                </div>
                <div class="modal-body py-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Nama / Blok</label>
                            <input type="text" wire:model="name" class="form-control" placeholder="Blok A-01" />
                            @error('name') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Tipe Rumah</label>
                            <input type="text" wire:model="type" class="form-control" placeholder="Tipe 36/72" />
                            @error('type') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-semibold">Status</label>
                        <select wire:model="status" class="form-select">
                            <option value="perencanaan">Perencanaan</option>
                            <option value="pembangunan">Pembangunan</option>
                            <option value="selesai">Selesai</option>
                        </select>
                        @error('status') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Tanggal Mulai</label>
                            <input type="date" wire:model="start_date" class="form-control" />
                            @error('start_date') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Target Selesai</label>
                            <input type="date" wire:model="target_end_date" class="form-control" />
                            @error('target_end_date') <span class="text-danger small">{{ $message }}</span> @enderror
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
                    <button type="button" class="btn btn-danger btn-sm font-semibold" wire:click="executeConfirmedAction">Ya, Hapus</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

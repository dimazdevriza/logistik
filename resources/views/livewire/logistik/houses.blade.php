<div>
    <div class="container-fluid p-0">
        <!-- Hero Header -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-body-tertiary">
            <div class="card-body p-4 p-md-5 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <div>
                    <h1 class="display-5 fw-black text-body mb-2 font-outfit">
                        Daftar Unit <span class="text-success">Rumah Proyek</span>
                    </h1>
                    <p class="text-secondary mb-0 max-w-xl">
                        Pantau pembangunan unit rumah dan rincian alokasi material per unit.
                    </p>
                </div>
                <div class="d-flex flex-column gap-2" style="min-width: 260px;">
                    @if(in_array(auth()->user()->role, ['admin', 'logistik', 'pengawas']))
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
                        <span>Tambah Rumah</span>
                    </button>
                </div>
            </div>
        </div>

        @if ($clusterAssignmentMissing)
            <div class="alert alert-warning rounded-3" role="alert">Akun belum memiliki cluster tugas. Minta Admin menetapkan cluster sebelum mengelola rumah.</div>
        @endif

        @if ($clusterAssignmentMissing)
            <div class="alert alert-warning rounded-3" role="alert">Akun belum memiliki cluster tugas. Minta Admin menetapkan cluster sebelum mengelola rumah.</div>
        @endif

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @error('delete')
            <div class="alert alert-danger" role="alert">{{ $message }}</div>
        @enderror

        <div class="card border shadow-sm rounded-4 mb-4 p-3 p-md-4 bg-body-tertiary standard-table-panel">
            <div class="standard-table-toolbar">
                <h2 class="h5 fw-bold mb-0 font-outfit">Daftar rumah</h2>
                <div class="standard-table-toolbar-controls">
                    <input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari rumah, kode, atau tipe..." aria-label="Cari rumah, kode, atau tipe" class="form-control standard-table-toolbar-search" />
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
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase text-secondary">Cluster</label>
                        <select wire:model.live="filterCluster" class="form-select">
                            <option value="">Semua Cluster</option>
                            @foreach ($clusters as $cluster)
                                <option value="{{ $cluster->id }}">{{ $cluster->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </x-filter-modal>
                </div>
            </div>

            <div class="table-responsive data-table-scroll house-table-scroll standard-table-frame" role="region" aria-label="Daftar unit rumah" tabindex="0">
                <table class="table table-hover align-middle mb-0 data-table standard-data-table">
                    <thead class="table-light text-uppercase small font-geist">
                        <tr>
                            <th class="text-center" style="width: 50px;">No.</th>
                            <x-sortable-th field="code" :sort="$sort">Kode</x-sortable-th>
                            <x-sortable-th field="name" :sort="$sort">Nama / Blok</x-sortable-th>
                            <x-sortable-th field="cluster" :sort="$sort">Cluster</x-sortable-th>
                            <x-sortable-th field="type" :sort="$sort">Tipe</x-sortable-th>
                            <x-sortable-th field="status" :sort="$sort">Status</x-sortable-th>
                            <x-sortable-th field="cost" :sort="$sort" class="text-end">Total Biaya</x-sortable-th>
                            <x-sortable-th field="start_date" :sort="$sort">Mulai</x-sortable-th>
                            <x-sortable-th field="target_end_date" :sort="$sort">Target Selesai</x-sortable-th>
                            <th class="text-end" style="width: 120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($houses as $house)
                        <tr wire:key="house-{{ $house->id }}" style="cursor: pointer;" x-on:click="if (!$event.target.closest('button') && !$event.target.closest('a')) { window.Livewire.navigate('{{ route('logistik.house-detail', $house) }}') }">
                            <td class="text-center text-secondary small">{{ $houses->firstItem() + $loop->index }}</td>
                            <td class="font-mono text-secondary small data-key-code" title="{{ $house->house_code ?? '-' }}">{{ $house->house_code ?? '-' }}</td>
                            <td class="fw-bold text-body data-key-name" title="{{ $house->name }}">{{ $house->name }}</td>
                            <td class="text-secondary small data-cell-truncate" title="{{ $house->cluster?->name ?? 'Belum dikelompokkan' }}">{{ $house->cluster?->name ?? '-' }}</td>
                            <td class="text-secondary small data-cell-truncate" title="{{ $house->type ?? '-' }}">{{ $house->type ?: '-' }}</td>
                            <td>
                                @php
                                    $statusClasses = ['perencanaan' => 'bg-warning-subtle text-warning', 'pembangunan' => 'bg-primary-subtle text-primary', 'selesai' => 'bg-success-subtle text-success'];
                                @endphp
                                <span class="badge {{ $statusClasses[$house->status] ?? 'bg-secondary-subtle text-secondary' }}">{{ ucfirst($house->status) }}</span>
                            </td>
                            <td class="text-end font-mono fw-bold text-body data-number">Rp {{ number_format($house->total_material_cost, 0, ',', '.') }}</td>
                            <td class="text-secondary small data-date">{{ $house->start_date?->format('d/m/Y') ?? '-' }}</td>
                            <td class="text-secondary small data-date">{{ $house->target_end_date?->format('d/m/Y') ?? '-' }}</td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm data-row-actions">
                                    <a href="{{ route('logistik.house-detail', $house) }}" wire:navigate class="btn log-row-action log-row-action--edit d-inline-flex align-items-center justify-content-center" title="Lihat Detail" aria-label="Lihat detail {{ $house->name }}">
                                        <svg width="14" height="14" fill="currentColor"><use href="#i-eye"/></svg>
                                    </a>
                                    <button type="button" wire:click="edit({{ $house->id }})" class="btn log-row-action log-row-action--quiet d-inline-flex align-items-center justify-content-center" title="Edit" aria-label="Edit {{ $house->name }}">
                                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325"/></svg>
                                    </button>
                                    <button type="button" wire:click="confirm('delete', {{ $house->id }}, 'Hapus Rumah?', 'Yakin ingin menghapus data rumah ini? Semua data penggunaan material dan peminjaman alat terkait akan ikut dihapus secara permanen.')" class="btn log-row-action log-row-action--danger d-inline-flex align-items-center justify-content-center" title="Hapus" aria-label="Hapus {{ $house->name }}">
                                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/><path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center py-4 text-secondary">Belum ada data rumah.</td>
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
    @teleport('body')
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" aria-labelledby="house-form-title" role="dialog" x-on:keydown.escape.window="$wire.set('showModal', false)">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable {{ !$editMode && (int) $houseCount > 1 ? 'modal-lg' : 'modal-md' }}">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-bottom">
                    <h5 id="house-form-title" class="modal-title font-outfit fw-bold">{{ $editMode ? 'Edit Rumah' : 'Tambah Rumah' }}</h5>
                    <button type="button" class="btn-close" wire:click="$set('showModal', false)" aria-label="Tutup form rumah"></button>
                </div>
                <div class="modal-body py-4">
                    @if (!$editMode)
                        <div class="mb-3">
                            <label for="house-count" class="form-label font-semibold">Jumlah rumah</label>
                            <input id="house-count" type="number" min="1" max="100" wire:model.live="houseCount" class="form-control" />
                            <div class="form-text">Isi 1 untuk satu rumah, atau hingga 100 rumah sekaligus.</div>
                            @error('houseCount') <span class="text-danger small" role="alert">{{ $message }}</span> @enderror
                        </div>

                        @if ((int) $houseCount <= 1)
                            <div class="mb-3">
                                <label for="house-name" class="form-label font-semibold">Nama / Blok</label>
                                <input id="house-name" type="text" wire:model="name" class="form-control" placeholder="Blok A-01" />
                                @error('name') <span class="text-danger small" role="alert">{{ $message }}</span> @enderror
                            </div>
                        @else
                            <div class="row g-3 mb-3">
                                <div class="col-sm-6">
                                    <label for="house-block" class="form-label font-semibold">Blok</label>
                                    <input id="house-block" type="text" wire:model.live="bulkBlock" class="form-control" placeholder="A" autocomplete="off" />
                                    @error('bulkBlock') <span class="text-danger small" role="alert">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-sm-6">
                                    <label for="house-starting-number" class="form-label font-semibold">Nomor awal</label>
                                    <input id="house-starting-number" type="number" min="1" max="999999" wire:model.live="startingNumber" class="form-control" />
                                    @error('startingNumber') <span class="text-danger small" role="alert">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <p class="form-text mt-n2 mb-3">Nama dibuat berurutan, misalnya Blok A-01 sampai Blok A-10.</p>
                        @endif
                    @endif

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="house-type" class="form-label font-semibold">Tipe Rumah</label>
                            <input id="house-type" type="text" wire:model="type" class="form-control" placeholder="Tipe 36/72" />
                            @error('type') <span class="text-danger small" role="alert">{{ $message }}</span> @enderror
                        </div>
                        @if ($editMode)
                            <div class="col-md-6">
                                <label for="house-status" class="form-label font-semibold">Status</label>
                                <select id="house-status" wire:model="status" class="form-select">
                                    <option value="perencanaan">Perencanaan</option>
                                    <option value="pembangunan">Pembangunan</option>
                                    <option value="selesai">Selesai</option>
                                </select>
                                @error('status') <span class="text-danger small" role="alert">{{ $message }}</span> @enderror
                            </div>
                        @endif
                    </div>
                    <div class="mb-3">
                        <label for="house-cluster" class="form-label font-semibold">Cluster</label>
                        <select id="house-cluster" wire:model="cluster_id" class="form-select" @disabled(! auth()->user()->canAccessAllClusters())>
                            <option value="">Tanpa Cluster</option>
                            @foreach ($clusters as $cluster)
                                <option value="{{ $cluster->id }}">{{ $cluster->name }}</option>
                            @endforeach
                        </select>
                        @error('cluster_id') <span class="text-danger small" role="alert">{{ $message }}</span> @enderror
                    </div>
                    @unless ($editMode)
                        <div class="mb-3">
                            <label for="house-status" class="form-label font-semibold">Status</label>
                            <select id="house-status" wire:model="status" class="form-select">
                                <option value="perencanaan">Perencanaan</option>
                                <option value="pembangunan">Pembangunan</option>
                                <option value="selesai">Selesai</option>
                            </select>
                            @error('status') <span class="text-danger small" role="alert">{{ $message }}</span> @enderror
                        </div>
                    @endunless
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="house-start-date" class="form-label font-semibold">Tanggal Mulai</label>
                            <input id="house-start-date" type="date" wire:model="start_date" class="form-control" />
                            @error('start_date') <span class="text-danger small" role="alert">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="house-target-end-date" class="form-label font-semibold">Target Selesai</label>
                            <input id="house-target-end-date" type="date" wire:model="target_end_date" class="form-control" />
                            @error('target_end_date') <span class="text-danger small" role="alert">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    @if (!$editMode && (int) $houseCount > 1)
                        <section class="house-bulk-preview mt-4 pt-3 border-top" aria-labelledby="house-bulk-preview-title" aria-live="polite">
                            <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2 mb-3">
                                <div>
                                    <h6 id="house-bulk-preview-title" class="fw-bold mb-1">Pratinjau rumah</h6>
                                    <p class="text-secondary small mb-0">Periksa nama dan tipe sebelum semua rumah disimpan.</p>
                                </div>
                                @if (!$bulkPreviewReady || count($bulkRows) === 0)
                                    <button type="button" class="btn btn-outline-success flex-shrink-0" wire:click="previewBulkHouses" wire:loading.attr="disabled" wire:target="previewBulkHouses">
                                        <span wire:loading.remove wire:target="previewBulkHouses">{{ $bulkPreviewReady ? 'Isi ulang daftar' : 'Tampilkan pratinjau' }}</span>
                                        <span wire:loading wire:target="previewBulkHouses">Menyiapkan…</span>
                                    </button>
                                @endif
                            </div>

                            @error('bulkRows') <div class="alert alert-danger py-2 small" role="alert">{{ $message }}</div> @enderror

                            @if ($bulkPreviewReady && count($bulkRows) === 0)
                                <p class="small text-secondary mb-0">Daftar pratinjau kosong. Tampilkan pratinjau lagi untuk mengisi daftar.</p>
                            @elseif ($bulkPreviewReady)
                                <div class="table-responsive house-bulk-preview-scroll" role="region" aria-label="Daftar rumah yang akan disimpan" tabindex="0">
                                    <table class="table table-sm mb-0 house-bulk-preview-table">
                                        <caption class="visually-hidden">Edit nama dan tipe setiap rumah. Kode rumah dibuat dari nama.</caption>
                                        <thead class="table-light">
                                            <tr>
                                                <th scope="col">Nama rumah / kode</th>
                                                <th scope="col">Tipe rumah</th>
                                                <th scope="col" class="text-end">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($bulkRows as $index => $row)
                                                <tr wire:key="bulk-house-{{ $index }}" class="{{ in_array($index, $bulkConflicts, true) ? 'table-warning' : '' }}">
                                                    <td data-label="Nama rumah / kode">
                                                        <input type="text" wire:model.blur="bulkRows.{{ $index }}.name" class="form-control" aria-label="Nama rumah nomor {{ $index + 1 }}" />
                                                        @if (trim((string) ($row['name'] ?? '')) !== '')
                                                            <div class="small text-secondary mt-1">Kode: <span class="font-mono">{{ \App\Models\House::generateCode($row['name']) }}</span></div>
                                                        @endif
                                                        @if (in_array($index, $bulkConflicts, true))
                                                            <div class="small text-warning-emphasis mt-1" role="status">Kode sudah digunakan. Ubah nama atau hapus rumah ini.</div>
                                                        @endif
                                                        @error("bulkRows.{$index}.name") <div class="small text-danger mt-1" role="alert">{{ $message }}</div> @enderror
                                                    </td>
                                                    <td data-label="Tipe rumah">
                                                        <input type="text" wire:model.blur="bulkRows.{{ $index }}.type" class="form-control" aria-label="Tipe rumah untuk {{ $row['name'] ?? 'rumah ' . ($index + 1) }}" />
                                                        @error("bulkRows.{$index}.type") <div class="small text-danger mt-1" role="alert">{{ $message }}</div> @enderror
                                                    </td>
                                                    <td data-label="Aksi" class="text-end">
                                                        <button type="button" wire:click="removeBulkHouse({{ $index }})" class="btn btn-outline-danger" aria-label="Hapus {{ $row['name'] ?: 'rumah nomor ' . ($index + 1) }} dari pratinjau">
                                                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/><path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/></svg>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="d-flex flex-wrap justify-content-between gap-2 mt-2 small text-secondary">
                                    <span>{{ count($bulkRows) }} rumah dalam pratinjau</span>
                                    @if ($bulkConflicts !== [])
                                        <span>{{ count($bulkConflicts) }} baris perlu diperbaiki</span>
                                    @endif
                                </div>
                            @endif
                        </section>
                    @endif
                </div>
                <div class="modal-footer border-top bg-body-tertiary rounded-bottom-4">
                    <button type="button" class="btn btn-secondary fw-semibold" wire:click="$set('showModal', false)">Batal</button>
                    <button type="button" class="btn btn-success fw-semibold" wire:click="save" wire:loading.attr="disabled" wire:target="save" @disabled(!$editMode && (int) $houseCount > 1 && $bulkPreviewReady && count($bulkRows) === 0)>
                        <span wire:loading.remove wire:target="save">{{ $editMode ? 'Perbarui' : (!$editMode && (int) $houseCount > 1 ? 'Simpan ' . ($bulkPreviewReady ? count($bulkRows) : (int) $houseCount) . ' rumah' : 'Simpan') }}</span>
                        <span wire:loading wire:target="save">Menyimpan rumah…</span>
                    </button>
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
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title font-outfit fw-bold d-flex align-items-center gap-2">
                        <svg width="20" height="20" fill="currentColor" class="text-primary" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5"/><path d="M7.646 1.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 2.707V10.5a.5.5 0 0 1-1 0V2.707L5.354 4.854a.5.5 0 1 1-.708-.708z"/></svg>
                        Import Data Unit Rumah dari Excel
                    </h5>
                    <button type="button" class="btn-close" wire:click="$set('showImportModal', false)"></button>
                </div>
                <div class="modal-body p-4">
                    @if(!$importResultSummary)
                        <p class="text-secondary small mb-3">
                            Unggah berkas Excel (<code>.xlsx</code> / <code>.xls</code>) yang berisi daftar unit rumah, alokasi pemakaian material, serta catatan peminjaman alat kerja. Sistem akan memproses seluruh sheet secara terintegrasi.
                        </p>

                        <div class="bg-body-tertiary p-3 rounded-3 mb-3 border">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <h6 class="fw-bold extra-small text-uppercase text-secondary mb-0">Panduan Struktur Sheet & Kolom</h6>
                                <a href="{{ asset('sample_house_import.xlsx') }}" download class="btn btn-outline-success btn-sm py-1 px-2.5 extra-small fw-semibold d-inline-flex align-items-center gap-1">
                                    <svg width="13" height="13" fill="currentColor" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z"/></svg>
                                    <span>Unduh Format 3-Sheet (.xlsx)</span>
                                </a>
                            </div>
                            <ul class="extra-small text-secondary mb-0 ps-3">
                                <li><strong>Sheet 1 (Unit Rumah):</strong> <code>Kode Rumah</code>, <code>Nama / Blok</code>, <code>Tipe</code>, <code>Status</code>, <code>Mulai</code>, <code>Target Selesai</code></li>
                                <li><strong>Sheet 2 (Pemakaian Material):</strong> <code>Unit Rumah</code>, <code>Nama Material</code>, <code>Qty</code>, <code>Satuan</code>, <code>Harga Satuan</code>, <code>Kategori</code>, <code>Tanggal</code>, <code>Peruntukkan</code></li>
                                <li><strong>Sheet 3 (Peminjaman Alat):</strong> <code>Unit Rumah</code>, <code>Kode Alat</code>, <code>Nama Alat</code>, <code>Qty</code>, <code>Status</code>, <code>Tanggal Pinjam</code>, <code>Tanggal Kembali</code>, <code>Peruntukkan</code></li>
                                <li class="text-danger"><strong>Penting:</strong> Material dan alat harus sudah terdaftar di gudang. Stok material harus tersedia; baris pengembalian alat harus memiliki peminjaman aktif. Tambahkan kolom <code>Gudang</code> pada sheet material dan alat jika ada lebih dari satu gudang.</li>
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
                                <p class="mb-0 small">Seluruh baris data pada berkas Excel telah diproses dan divalidasi ke database.</p>
                            </div>
                        </div>

                        <!-- Summary Cards -->
                        <div class="row row-cols-2 row-cols-sm-3 row-cols-lg-5 g-2 mb-3 text-center">
                            <div class="col">
                                <div class="h-100 p-2 border rounded bg-body-tertiary d-flex flex-column justify-content-center">
                                    <span class="d-block text-secondary extra-small fw-bold text-uppercase lh-sm">Total Baris</span>
                                    <span class="fs-5 fw-bold text-body lh-sm">{{ $importResultSummary['totalRows'] }}</span>
                                </div>
                            </div>
                            <div class="col">
                                <div class="h-100 p-2 border rounded bg-success-subtle text-success d-flex flex-column justify-content-center">
                                    <span class="d-block extra-small fw-bold text-uppercase lh-sm">Sukses</span>
                                    <span class="fs-5 fw-bold lh-sm">{{ $importResultSummary['successfulRows'] }}</span>
                                </div>
                            </div>
                            <div class="col">
                                <div class="h-100 p-2 border rounded bg-warning-subtle text-warning d-flex flex-column justify-content-center">
                                    <span class="d-block extra-small fw-bold text-uppercase lh-sm">Unit Baru</span>
                                    <span class="fs-5 fw-bold lh-sm">{{ $importResultSummary['housesImported'] }}</span>
                                </div>
                            </div>
                            <div class="col">
                                <div class="h-100 p-2 border rounded bg-info-subtle text-info d-flex flex-column justify-content-center">
                                    <span class="d-block extra-small fw-bold text-uppercase lh-sm">Alokasi Mat.</span>
                                    <span class="fs-5 fw-bold lh-sm">{{ $importResultSummary['materialsImported'] ?? 0 }}</span>
                                </div>
                            </div>
                            <div class="col">
                                <div class="h-100 p-2 border rounded bg-primary-subtle text-primary d-flex flex-column justify-content-center">
                                    <span class="d-block extra-small fw-bold text-uppercase lh-sm">Pinjam Alat</span>
                                    <span class="fs-5 fw-bold lh-sm">{{ $importResultSummary['toolsImported'] ?? 0 }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Detailed Row Logs -->
                        <h6 class="fw-bold extra-small text-uppercase text-secondary mb-2">Rincian Status per Baris</h6>
                        <div class="table-responsive border rounded" style="max-height: 250px; overflow-y: auto;">
                            <table class="table table-sm table-striped align-middle mb-0 extra-small">
                                <thead class="table-light sticky-top">
                                    <tr>
                                <th style="width: 50px;">Baris Data</th>
                                        <th>Sheet / Bagian</th>
                                        <th>Item / Unit</th>
                                        <th>Status</th>
                                        <th>Keterangan Sistem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($importResultSummary['logs'] as $log)
                                        <tr>
                                            <td class="fw-mono text-center">#{{ max(1, (int) $log['row'] - 1) }}</td>
                                            <td><span class="badge bg-secondary-subtle text-secondary">{{ $log['sheet'] ?? 'Unit Rumah' }}</span></td>
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
</div>

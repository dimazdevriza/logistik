<div>
    <div class="container-fluid p-0">
        <!-- Hero Header -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-body-tertiary">
            <div class="card-body p-4 p-md-5 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 text-uppercase mb-2 font-geist small">Pemantauan Proyek</span>
                    <h1 class="display-5 fw-black text-body mb-2 font-outfit">
                        Daftar Unit <span class="text-success">Rumah Proyek</span>
                    </h1>
                    <p class="text-secondary mb-0 max-w-xl">
                        Pantau pembangunan unit rumah, tipe klaster, dan rincian alokasi material per unit.
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
                        <span>Tambah Rumah</span>
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
                                    <a href="{{ route('logistik.house-detail', $house) }}" wire:navigate class="btn btn-outline-secondary d-inline-flex align-items-center justify-content-center" title="Lihat Detail">
                                        <svg width="14" height="14" fill="currentColor"><use href="#i-eye"/></svg>
                                    </a>
                                    <button type="button" wire:click="edit({{ $house->id }})" class="btn btn-outline-secondary d-inline-flex align-items-center justify-content-center" title="Edit">
                                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325"/></svg>
                                    </button>
                                    <button type="button" wire:click="confirm('delete', {{ $house->id }}, 'Hapus Rumah?', 'Yakin ingin menghapus data rumah ini? Semua data penggunaan material dan peminjaman alat terkait akan ikut dihapus secara permanen.')" class="btn btn-outline-danger d-inline-flex align-items-center justify-content-center" title="Hapus">
                                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/><path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/></svg>
                                    </button>
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
    @teleport('body')
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content border-0 shadow-lg rounded-4">
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
                <div class="modal-footer border-top bg-body-tertiary rounded-bottom-4">
                    <button type="button" class="btn btn-secondary fw-semibold" wire:click="$set('showModal', false)">Batal</button>
                    <button type="button" class="btn btn-success fw-semibold" wire:click="save" wire:loading.attr="disabled" wire:target="save">{{ $editMode ? 'Perbarui' : 'Simpan' }}</button>
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
                            <div class="col">
                                <div class="p-2 border rounded bg-body-tertiary">
                                    <span class="d-block text-secondary extra-small fw-bold text-uppercase">Total Baris</span>
                                    <span class="fs-5 fw-bold text-body">{{ $importResultSummary['totalRows'] }}</span>
                                </div>
                            </div>
                            <div class="col">
                                <div class="p-2 border rounded bg-success-subtle text-success">
                                    <span class="d-block extra-small fw-bold text-uppercase">Sukses</span>
                                    <span class="fs-5 fw-bold">{{ $importResultSummary['successfulRows'] }}</span>
                                </div>
                            </div>
                            <div class="col">
                                <div class="p-2 border rounded bg-warning-subtle text-warning">
                                    <span class="d-block extra-small fw-bold text-uppercase">Unit Baru</span>
                                    <span class="fs-5 fw-bold">{{ $importResultSummary['housesImported'] }}</span>
                                </div>
                            </div>
                            <div class="col">
                                <div class="p-2 border rounded bg-info-subtle text-info">
                                    <span class="d-block extra-small fw-bold text-uppercase">Alokasi Mat.</span>
                                    <span class="fs-5 fw-bold">{{ $importResultSummary['materialsImported'] ?? 0 }}</span>
                                </div>
                            </div>
                            <div class="col">
                                <div class="p-2 border rounded bg-primary-subtle text-primary">
                                    <span class="d-block extra-small fw-bold text-uppercase">Pinjam Alat</span>
                                    <span class="fs-5 fw-bold">{{ $importResultSummary['toolsImported'] ?? 0 }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Detailed Row Logs -->
                        <h6 class="fw-bold extra-small text-uppercase text-secondary mb-2">Rincian Status per Baris</h6>
                        <div class="table-responsive border rounded" style="max-height: 250px; overflow-y: auto;">
                            <table class="table table-sm table-striped align-middle mb-0 extra-small">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th style="width: 50px;">Baris</th>
                                        <th>Sheet / Bagian</th>
                                        <th>Item / Unit</th>
                                        <th>Status</th>
                                        <th>Keterangan Sistem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($importResultSummary['logs'] as $log)
                                        <tr>
                                            <td class="fw-mono text-center">#{{ $log['row'] }}</td>
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

<div>
    <div class="container-fluid p-0">
        <!-- Hero Header -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-body-tertiary">
            <div class="card-body p-4 p-md-5 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <div>
                    <h1 class="display-5 fw-black text-body mb-2 font-outfit">
                        Catatan Riwayat <span class="text-success">Alat</span>
                    </h1>
                    <p class="text-secondary mb-0 max-w-xl">
                        Penerimaan alat, peminjaman, dan pengembalian alat kerja proyek.
                    </p>
                </div>
                <div>
                    @if(in_array(auth()->user()->role, ['admin', 'logistik', 'keuangan', 'pengawas'], true))
                        <button type="button" wire:click="exportExcel" class="btn btn-utility font-semibold">
                            <svg width="15" height="15" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5"/>
                                <path d="M7.646 1.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 2.707V10.5a.5.5 0 0 1-1 0V2.707L5.354 4.854a.5.5 0 1 1-.708-.708z"/>
                            </svg>
                            <span>Ekspor Excel</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success" role="alert">{{ session('success') }}</div>
        @endif
        @if ($pendingBrokenReturns->isNotEmpty() && in_array(auth()->user()->role, ['admin', 'logistik', 'pengawas'], true))
            <section class="card border-warning-subtle shadow-sm rounded-4 mb-4" aria-labelledby="broken-tool-resolution-heading">
                <div class="card-body p-4">
                    <h2 id="broken-tool-resolution-heading" class="h5 mb-1">Alat rusak yang perlu ditindaklanjuti</h2>
                    <p class="text-secondary small mb-3">Catat hasil pemeriksaan sebelum unit dikembalikan ke stok atau diafkir.</p>
                    <div class="vstack gap-3">
                        @foreach ($pendingBrokenReturns as $brokenReturn)
                            <div class="border rounded-3 p-3" wire:key="broken-return-{{ $brokenReturn->id }}">
                                <div class="fw-semibold">{{ $brokenReturn->tool->name }} · {{ $brokenReturn->quantity }} unit</div>
                                <div class="small text-secondary mb-3">{{ $brokenReturn->house->name }} · {{ $brokenReturn->receivingWarehouse?->name ?? 'Gudang tidak tercatat' }} · {{ $brokenReturn->notes ?: 'Tanpa catatan' }}</div>
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-6">
                                        <label class="form-label small" for="broken-return-note-{{ $brokenReturn->id }}">Catatan penyelesaian</label>
                                        <input id="broken-return-note-{{ $brokenReturn->id }}" class="form-control" wire:model="resolutionNotesById.{{ $brokenReturn->id }}" maxlength="500" required>
                                        @error('resolutionNotesById.'.$brokenReturn->id) <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small" for="broken-return-cost-{{ $brokenReturn->id }}">Biaya perbaikan (opsional)</label>
                                        <input id="broken-return-cost-{{ $brokenReturn->id }}" type="number" min="0" step="0.01" class="form-control" wire:model="repairCostsById.{{ $brokenReturn->id }}">
                                        @error('repairCostsById.'.$brokenReturn->id) <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-3 d-flex gap-2">
                                        <button type="button" class="btn btn-success" wire:click="resolveBrokenReturn({{ $brokenReturn->id }}, 'fixed')">Sudah diperbaiki</button>
                                        <button type="button" class="btn btn-outline-danger" wire:click="resolveBrokenReturn({{ $brokenReturn->id }}, 'discarded')" wire:confirm="Afkir unit rusak ini? Jumlah tercatat akan dikurangi.">Afkir</button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif
        @error('void')
            <div class="alert alert-danger" role="alert">{{ $message }}</div>
        @enderror
        @php
            $monthNames = [1 => 'JANUARI', 2 => 'FEBRUARI', 3 => 'MARET', 4 => 'APRIL', 5 => 'MEI', 6 => 'JUNI', 7 => 'JULI', 8 => 'AGUSTUS', 9 => 'SEPTEMBER', 10 => 'OKTOBER', 11 => 'NOVEMBER', 12 => 'DESEMBER'];
        @endphp

        <div class="card border shadow-sm rounded-4 mb-4 p-3 p-md-4 bg-body-tertiary standard-table-panel data-table-card">
            <div class="standard-table-toolbar">
                <h2 class="h5 fw-bold mb-0 font-outfit">Riwayat alat</h2>
                <div class="standard-table-toolbar-controls">
                    <input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari alat / kode masuk / pengiriman..." aria-label="Cari alat, kode masuk, atau pengiriman" class="form-control standard-table-toolbar-search" />
                    <x-filter-modal :activeFiltersCount="$this->getActiveFiltersCount()">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-uppercase text-secondary">Status Peminjaman</label>
                            <select wire:model.live="filterStatus" class="form-select">
                                <option value="">Semua Status</option>
                                <option value="dipinjam">Dipinjam</option>
                                <option value="dikembalikan">Dikembalikan</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-uppercase text-secondary">Rumah</label>
                            <select wire:model.live="filterHouse" class="form-select">
                                <option value="">Semua Rumah</option>
                                @foreach ($houses as $house)
                                    <option value="{{ $house->id }}">{{ $house->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </x-filter-modal>
                </div>
            </div>

        <!-- Tool Log Table -->
            <div wire:loading.delay class="data-table-status" role="status">Memuat catatan alat...</div>
            <div wire:offline class="data-table-status is-error" role="alert">Koneksi terputus. Data mungkin tidak terbaru.</div>
            <div class="table-responsive data-table-scroll standard-table-frame tool-log-table-scroll" tabindex="0" role="region" aria-label="Catatan alat">
                <table class="table table-hover table-striped align-middle mb-0 excel-log-table data-table data-table--compact data-table--sticky-history tool-log-table">
                    <colgroup>
                        <col style="width: 220px"><col style="width: 120px"><col style="width: 75px"><col style="width: 130px">
                        <col style="width: 180px"><col style="width: 150px">
                        <col style="width: 260px"><col style="width: 120px"><col style="width: 256px"><col style="width: 90px">
                        <col style="width: 90px"><col style="width: 140px"><col style="width: 150px"><col style="width: 120px">
                    </colgroup>
                    <thead class="table-light text-uppercase small font-geist">
                        <tr>
                            <x-sortable-th field="date" :sort="$sort">Tanggal</x-sortable-th>
                            <th>Bulan</th>
                            <th>Tahun</th>
                            <x-sortable-th field="type" :sort="$sort">Tipe</x-sortable-th>
                            <x-sortable-th field="admin" :sort="$sort">Penanggung Jawab</x-sortable-th>
                            <x-sortable-th field="house" :sort="$sort">Rumah / Gudang</x-sortable-th>
                            <x-sortable-th field="notes" :sort="$sort">Keterangan Pekerjaan</x-sortable-th>
                            <x-sortable-th field="code" :sort="$sort" class="data-key-code">Kode Alat</x-sortable-th>
                            <x-sortable-th field="name" :sort="$sort" class="data-key-name">Nama Alat</x-sortable-th>
                            <x-sortable-th field="volume" :sort="$sort" class="text-end data-number">Volume</x-sortable-th>
                            <th>Satuan</th>
                            <x-sortable-th field="unit_price" :sort="$sort" class="text-end data-number">Harga Satuan</x-sortable-th>
                            <th class="text-end data-number">Jumlah</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($records as $record)
                        @php $date = $record->date ? \Carbon\Carbon::parse($record->date) : null; @endphp
                        <tr wire:key="t-log-{{ $record->type }}-{{ $record->id }}">
                            <td data-label="Tanggal" class="font-mono text-secondary small">
                                <span class="tool-log-cell-value">
                                    {{ $date?->format('d/m/Y') ?? 'Tanggal tidak tercatat' }}
                                    @if ($record->transaction_code)
                                        <span class="d-block extra-small" title="{{ $record->type === 'kembali' ? 'Kode pengembalian' : ($record->type === 'keluar' ? 'Kode transaksi' : ($record->type === 'transfer' ? 'Kode transfer' : 'Kode masuk')) }}">{{ $record->transaction_code }}</span>
                                    @endif
                                    @if ($record->type === 'keluar' && $record->dispatch_code)
                                        <span class="d-block extra-small">Pengiriman {{ $record->dispatch_code }}</span>
                                        @if ($record->source_line_id)<span class="d-block extra-small">Sumber {{ $record->source_entry_code ?? 'Tidak tercatat' }} · {{ $record->source_warehouse_name ?? '-' }}</span>@endif
                                    @endif
                                </span>
                            </td>
                            <td data-label="Bulan" class="font-mono text-secondary small"><span class="tool-log-cell-value">{{ $date ? $monthNames[$date->month] : '-' }}</span></td>
                            <td data-label="Tahun" class="text-center font-mono text-secondary small"><span class="tool-log-cell-value">{{ $date?->year ?? '-' }}</span></td>
                            <td data-label="Tipe"><span class="tool-log-cell-value">
                                @if ($record->type === 'rental')<span class="badge bg-warning-subtle text-warning-emphasis">Sewa vendor</span>
                                @elseif ($record->type === 'rental_extension')<span class="badge bg-info-subtle text-info-emphasis">Perpanjangan sewa</span>
                                @elseif ($record->type === 'rental_return')<span class="badge bg-success-subtle text-success">Kembali ke vendor</span>
                                @elseif ($record->type === 'saldo_awal')<span class="badge bg-secondary-subtle text-secondary">Saldo awal</span>
                                @elseif ($record->type === 'masuk')<span class="badge bg-success-subtle text-success">Masuk</span>
                                @elseif ($record->type === 'kembali')<span class="badge bg-success-subtle text-success">Kembali</span>
                                @elseif ($record->type === 'transfer')<span class="badge bg-info-subtle text-info-emphasis">Transfer</span><span class="d-block extra-small text-secondary">Antargudang</span>
                                @else<span class="badge {{ $record->voided_at ? 'bg-secondary-subtle text-secondary' : ($record->return_date ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning') }}">{{ $record->voided_at ? 'Dibatalkan' : ($record->return_date ? 'Dikembalikan' : 'Dipinjam') }}</span>@endif
                            </span></td>
                            <td data-label="Penanggung jawab" class="fw-semibold data-cell-truncate" title="{{ $record->admin_name ?? '-' }}">
                                <span class="tool-log-cell-value">
                                    {{ $record->admin_name ?: '-' }}
                                    @if ($record->admin_role)
                                        <span class="d-block extra-small text-secondary">{{ ucfirst($record->admin_role) }}</span>
                                    @endif
                                </span>
                            </td>
                            <td data-label="Rumah / Gudang" class="fw-semibold data-cell-truncate" title="{{ $record->house_name ?? '-' }}"><span class="tool-log-cell-value">{{ $record->house_name ?: '-' }}</span></td>
                            <td data-label="Keterangan" class="data-cell-truncate" title="{{ $record->job_notes ?? '-' }}">
                                <span class="tool-log-cell-value">
                                    {{ $record->job_notes ?: '-' }}
                                    @if (in_array($record->type, ['rental', 'rental_extension', 'rental_return']))
                                        <span class="d-block extra-small text-secondary">Vendor: {{ $record->vendor_name ?: '-' }}</span>
                                        @if ($record->rental_due_date)<span class="d-block extra-small text-secondary">Batas sewa: {{ \Carbon\Carbon::parse($record->rental_due_date)->format('d/m/Y') }}</span>@endif
                                        <span class="badge {{ $record->rental_status === 'Aktif' ? 'bg-warning-subtle text-warning-emphasis' : ($record->rental_status === 'Terlambat' ? 'bg-danger-subtle text-danger' : 'bg-secondary-subtle text-secondary') }}">{{ $record->rental_status }}</span>
                                        @if ($record->parent_transaction_code)<span class="d-block extra-small text-secondary">Sewa {{ $record->parent_transaction_code }}</span>@endif
                                        @if ($record->type === 'rental_return')<span class="d-block extra-small">Kondisi: {{ $record->job_notes ?: '-' }}</span>@endif
                                        @if ($record->rental_evidence_path)<a class="d-block extra-small" href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($record->rental_evidence_path) }}" target="_blank" rel="noopener">Lihat bukti</a>@endif
                                    @endif
                                    @if ($record->type === 'keluar' && $record->return_date)<span class="badge bg-success-subtle text-success ms-1">Dikembalikan {{ \Carbon\Carbon::parse($record->return_date)->format('d/m/Y') }}</span>@endif
                                    @if ($record->type === 'kembali' && $record->parent_transaction_code)<span class="d-block extra-small text-secondary">Peminjaman {{ $record->parent_transaction_code }}</span>@endif
                                </span>
                            </td>
                            <td data-label="Kode alat" class="font-mono text-secondary small data-key-code" title="{{ $record->item_code ?? '-' }}"><span class="tool-log-cell-value">{{ in_array($record->type, ['rental', 'rental_extension', 'rental_return']) ? $record->transaction_code : ($record->item_code ?: '-') }}</span></td>
                            <td data-label="Nama alat" class="fw-bold text-body data-key-name" title="{{ $record->item_name ?? '-' }}"><span class="tool-log-cell-value">{{ $record->item_name ?: '-' }}</span></td>
                            <td data-label="Volume" class="text-end fw-bold font-mono data-number"><span class="tool-log-cell-value">{{ number_format((float) $record->volume, 2, ',', '.') }}</span></td>
                            <td data-label="Satuan" class="data-cell-truncate" title="unit"><span class="tool-log-cell-value">unit</span></td>
                            <td data-label="Harga satuan" class="text-end font-mono text-secondary data-number"><span class="tool-log-cell-value">{{ $record->type === 'kembali' ? '—' : 'Rp '.number_format((float) $record->unit_price, 0, ',', '.') }}</span></td>
                            <td data-label="Jumlah" class="text-end font-mono fw-bold text-success data-number"><span class="tool-log-cell-value">{{ $record->type === 'kembali' ? '—' : 'Rp '.number_format((float) $record->total_cost, 0, ',', '.') }}</span></td>
                            <td data-label="Aksi" class="text-end">
                                @if ($record->type !== 'keluar')
                                    <span class="small text-secondary">-</span>
                                @elseif ($record->voided_at)
                                    <span class="small text-secondary">Dibatalkan</span>
                                @elseif (!$record->return_date)
                                    <button type="button" wire:click="voidTool({{ $record->id }})" wire:confirm="Batalkan peminjaman ini? Unit akan dikembalikan ke stok tersedia dan catatan tetap disimpan." class="btn log-row-action log-row-action--danger">Batalkan</button>
                                @else
                                    <span class="small text-secondary">-</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="14" class="data-table-empty-state py-4 text-secondary">Belum ada catatan alat.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="d-flex justify-content-end">{{ $records->links() }}</div>
    </div>
</div>

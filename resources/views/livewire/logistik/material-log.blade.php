<div>
    <div class="container-fluid p-0">
        <!-- Hero Header -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-body-tertiary">
            <div class="card-body p-4 p-md-5 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 text-uppercase mb-2 font-geist small">Riwayat Transaksi</span>
                    <h1 class="display-5 fw-black text-body mb-2 font-outfit">
                        Catatan Riwayat <span class="text-success">Material</span>
                    </h1>
                    <p class="text-secondary mb-0 max-w-xl">
                        Rekam jejak riwayat penambahan stok dari pemasok dan pengeluaran material ke unit rumah.
                    </p>
                </div>
                <div>
                    @if(in_array(auth()->user()->role, ['admin', 'logistik']))
                        <button type="button" wire:click="exportExcel" class="btn btn-outline-success font-semibold">Export Excel</button>
                    @endif
                </div>
            </div>
        </div>

        <!-- Search & Filter Controls -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 p-3 bg-body-tertiary">
            <div class="d-flex flex-column flex-md-row gap-3 align-items-md-center justify-content-between">
                <div class="w-100 max-w-sm">
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari material..." class="form-control" />
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" wire:click="toggleSortDirection" class="btn btn-outline-secondary px-3 d-inline-flex align-items-center gap-2 font-semibold shadow-xs" style="height: 38px;" title="Urutkan Tanggal">
                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16" style="{{ $sortDirection === 'asc' ? 'transform: rotate(180deg);' : '' }}">
                            <path fill-rule="evenodd" d="M8 1a.5.5 0 0 1 .5.5v11.793l3.146-3.147a.5.5 0 0 1 .708.708l-4 4a.5.5 0 0 1-.708 0l-4-4a.5.5 0 0 1 .708-.708L7.5 13.293V1.5A.5.5 0 0 1 8 1z"/>
                        </svg>
                        <span>Tanggal</span>
                    </button>
                    <x-filter-modal :activeFiltersCount="$this->getActiveFiltersCount()">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-uppercase text-secondary">Tipe Transaksi</label>
                            <select wire:model.live="filterType" class="form-select">
                                <option value="">Semua Transaksi</option>
                                <option value="masuk">Barang Masuk</option>
                                <option value="keluar">Barang Keluar</option>
                            </select>
                        </div>
                        @if ($filterType === 'keluar')
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-uppercase text-secondary">Rumah</label>
                                <select wire:model.live="filterHouse" class="form-select">
                                    <option value="">Semua Rumah</option>
                                    @foreach ($houses as $house)
                                        <option value="{{ $house->id }}">{{ $house->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @elseif ($filterType === 'masuk')
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-uppercase text-secondary">Supplier</label>
                                <select wire:model.live="filterSupplier" class="form-select">
                                    <option value="">Semua Supplier</option>
                                    @foreach ($suppliers as $sup)
                                        <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </x-filter-modal>
                </div>
            </div>
        </div>

        @php
            $monthNames = [1 => 'JANUARI', 2 => 'FEBRUARI', 3 => 'MARET', 4 => 'APRIL', 5 => 'MEI', 6 => 'JUNI', 7 => 'JULI', 8 => 'AGUSTUS', 9 => 'SEPTEMBER', 10 => 'OKTOBER', 11 => 'NOVEMBER', 12 => 'DESEMBER'];
        @endphp

        <!-- Material Log Table -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 excel-log-table">
                    <colgroup>
                        <col style="width: 100px"><col style="width: 100px"><col style="width: 70px"><col style="width: 150px">
                        <col style="width: 110px"><col style="width: 110px"><col style="width: 130px">
                        <col style="width: 260px"><col style="width: 120px"><col style="width: 260px"><col style="width: 90px">
                        <col style="width: 90px"><col style="width: 140px"><col style="width: 150px"><col style="width: 190px">
                    </colgroup>
                    <thead class="table-light text-uppercase small font-geist">
                        <tr>
                            <th>Tanggal</th>
                            <th>Bulan</th>
                            <th>Tahun</th>
                            <th title="Masuk: ditambahkan ke gudang. Keluar: digunakan dalam konstruksi.">Tipe</th>
                            <th>Admin</th>
                            <th>Pengambil</th>
                            <th>Blok Rumah</th>
                            <th>Keterangan Pekerjaan</th>
                            <th>Kode Barang</th>
                            <th>Nama Barang</th>
                            <th class="text-end">Volume</th>
                            <th>Satuan</th>
                            <th class="text-end">Harga Satuan</th>
                            <th class="text-end">Jumlah</th>
                            <th>Toko/Supplier</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($records as $record)
                            @php $date = \Carbon\Carbon::parse($record->date); @endphp
                            <tr wire:key="m-log-{{ $record->type }}-{{ $record->id }}">
                                <td class="font-mono text-secondary small">{{ $date->format('d/m/Y') }}</td>
                                <td class="font-mono text-secondary small">{{ $monthNames[$date->month] }}</td>
                                <td class="text-center font-mono text-secondary small">{{ $date->year }}</td>
                                <td>
                                    @if ($record->type === 'masuk')
                                        <span class="badge bg-success-subtle text-success">Masuk</span>
                                        <span class="d-block extra-small text-secondary">Gudang</span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning">Keluar</span>
                                        <span class="d-block extra-small text-secondary">Konstruksi</span>
                                    @endif
                                </td>
                                <td class="fw-semibold">{{ $record->admin_name ?? '-' }}</td>
                                <td>{{ $record->taker_name ?? '-' }}</td>
                                <td class="fw-semibold">{{ $record->house_name ?? '-' }}</td>
                                <td class="log-wrap">
                                    {{ $record->job_notes ?? '-' }}
                                    @if (($record->voided_at ?? null) !== null)
                                        <span class="badge bg-secondary-subtle text-secondary ms-1">VOIDED</span>
                                    @endif
                                </td>
                                <td class="font-mono text-secondary small">{{ $record->item_code ?? '-' }}</td>
                                <td class="fw-bold text-body log-wrap">{{ $record->item_name ?? '-' }}</td>
                                <td class="text-end fw-bold font-mono">{{ rtrim(rtrim(number_format((float) $record->volume, 2, ',', '.'), '0'), ',') }}</td>
                                <td>{{ $record->unit ?? '-' }}</td>
                                <td class="text-end font-mono text-secondary">Rp {{ number_format((float) $record->unit_price, 0, ',', '.') }}</td>
                                <td class="text-end font-mono fw-bold text-success">Rp {{ number_format((float) $record->total_cost, 0, ',', '.') }}</td>
                                <td>{{ $record->supplier_name ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="15" class="text-center py-4 text-secondary">Belum ada catatan material.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="d-flex justify-content-end">{{ $records->links() }}</div>
    </div>
</div>

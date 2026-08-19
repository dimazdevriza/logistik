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

        <!-- Material Log Table -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-uppercase small font-geist">
                        <tr>
                            <th class="text-center" style="width: 50px;">No.</th>
                            <th>Tanggal</th>
                            @if ($filterType === '') <th>Tipe</th> @endif
                            <th>
                                @if ($filterType === 'masuk') Supplier
                                @elseif ($filterType === 'keluar') Rumah
                                @else Referensi
                                @endif
                            </th>
                            <th>Material</th>
                            <th class="text-end">Qty</th>
                            <th class="text-end">Harga Satuan</th>
                            <th class="text-end">Total Biaya</th>
                            <th>Dicatat oleh</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($filterType === '')
                            @forelse ($records as $record)
                            <tr wire:key="m-log-{{ $loop->index }}">
                                <td class="text-center text-secondary small">{{ ($records->currentPage() - 1) * $records->perPage() + $loop->iteration }}</td>
                                <td class="font-mono text-secondary small">
                                    {{ \Carbon\Carbon::parse($record->created_at ?? $record->date)->format('d/m/Y H:i') }}
                                </td>
                                <td>
                                    @if ($record->type === 'masuk')
                                        <span class="badge bg-success-subtle text-success">▼ Masuk</span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning">▲ Keluar</span>
                                    @endif
                                </td>
                                <td class="fw-bold text-body">{{ $record->reference }}</td>
                                <td class="fw-bold text-body">{{ $record->material_name }}</td>
                                <td class="text-end fw-bold">{{ number_format($record->quantity, 0, ',', '.') }} <span class="text-secondary small font-normal">{{ $record->material_unit }}</span></td>
                                <td class="text-end font-mono text-secondary">Rp {{ number_format($record->unit_price, 0, ',', '.') }}</td>
                                <td class="text-end font-mono fw-bold text-success">Rp {{ number_format($record->total_cost, 0, ',', '.') }}</td>
                                <td class="text-secondary small">
                                    <div class="d-inline-flex align-items-center gap-1 flex-wrap">
                                        <span>{{ $record->user_name }}</span>
                                        @if ($record->type === 'keluar' && in_array(auth()->user()->role, ['admin', 'logistik']))
                                            @if (($record->voided_at ?? null) !== null)
                                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle extra-small text-uppercase tracking-wider">VOIDED</span>
                                            @else
                                                <button type="button" wire:confirm="Yakin membatalkan alokasi material ini? Stok akan dikembalikan."
                                                    wire:click="voidMaterial({{ $record->id }})"
                                                    class="btn btn-outline-danger btn-xs py-0 px-1.5 ms-1 extra-small font-semibold rounded-2 d-inline-flex align-items-center gap-1"
                                                    title="Batalkan Alokasi">
                                                    <svg width="10" height="10" fill="currentColor" viewBox="0 0 16 16"><path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/></svg>
                                                    Batalkan
                                                </button>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center py-4 text-secondary">Belum ada catatan material.</td>
                            </tr>
                            @endforelse
                        @elseif ($filterType === 'masuk')
                            @forelse ($records as $record)
                            <tr wire:key="m-in-log-{{ $loop->index }}">
                                <td class="text-center text-secondary small">{{ ($records->currentPage() - 1) * $records->perPage() + $loop->iteration }}</td>
                                <td class="font-mono text-secondary small">{{ ($record->created_at ?? $record->date)->format('d/m/Y H:i') }}</td>
                                <td class="fw-bold text-body">{{ $record->supplier->name ?? '-' }}</td>
                                <td class="fw-bold text-body">{{ $record->material->name ?? '-' }}</td>
                                <td class="text-end fw-bold">{{ number_format($record->quantity, 0, ',', '.') }} <span class="text-secondary small font-normal">{{ $record->material->unit ?? '' }}</span></td>
                                <td class="text-end font-mono text-secondary">Rp {{ number_format($record->unit_price, 0, ',', '.') }}</td>
                                <td class="text-secondary small">
                                    {{ $record->user->name ?? '-' }}
                                    @if($record->proof_image)
                                        <a href="{{ asset('storage/' . $record->proof_image) }}" target="_blank" class="badge bg-info-subtle text-info ms-1 text-decoration-none d-inline-flex align-items-center gap-1" title="Lihat Foto Bukti">
                                            <svg width="12" height="12" fill="currentColor"><use href="#i-camera"/></svg> Bukti
                                        </a>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-secondary">Belum ada data barang masuk.</td>
                            </tr>
                            @endforelse
                        @else
                            @forelse ($records as $record)
                            <tr wire:key="m-out-log-{{ $loop->index }}">
                                <td class="text-center text-secondary small">{{ ($records->currentPage() - 1) * $records->perPage() + $loop->iteration }}</td>
                                <td class="font-mono text-secondary small">{{ ($record->created_at ?? $record->usage_date)->format('d/m/Y H:i') }}</td>
                                <td class="fw-bold text-body">{{ $record->house->name }}</td>
                                <td class="fw-bold text-body">{{ $record->material->name }}</td>
                                <td class="text-end fw-bold">{{ str_replace('.', ',', (float) $record->quantity) }} <span class="text-secondary small font-normal">{{ $record->material->unit }}</span></td>
                                <td class="text-end font-mono text-secondary">Rp {{ number_format($record->unit_price_at_usage, 0, ',', '.') }}</td>
                                <td class="text-end font-mono fw-bold text-success">Rp {{ number_format($record->total_cost, 0, ',', '.') }}</td>
                                <td class="text-secondary small">
                                    <div class="d-inline-flex align-items-center gap-1 flex-wrap">
                                        <span>{{ $record->user->name }}</span>
                                        @if($record->proof_image)
                                            <a href="{{ asset('storage/' . $record->proof_image) }}" target="_blank" class="badge bg-info-subtle text-info text-decoration-none d-inline-flex align-items-center gap-1" title="Lihat Foto Bukti">
                                                <svg width="12" height="12" fill="currentColor"><use href="#i-camera"/></svg> Bukti
                                            </a>
                                        @endif
                                        @if (in_array(auth()->user()->role, ['admin', 'logistik']))
                                            @if ($record->voided_at !== null)
                                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle extra-small text-uppercase tracking-wider">VOIDED</span>
                                            @else
                                                <button type="button" wire:confirm="Yakin membatalkan alokasi material ini? Stok akan dikembalikan."
                                                    wire:click="voidMaterial({{ $record->id }})"
                                                    class="btn btn-outline-danger btn-xs py-0 px-1.5 ms-1 extra-small font-semibold rounded-2 d-inline-flex align-items-center gap-1"
                                                    title="Batalkan Alokasi">
                                                    <svg width="10" height="10" fill="currentColor" viewBox="0 0 16 16"><path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/></svg>
                                                    Batalkan
                                                </button>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-secondary">Belum ada data barang keluar.</td>
                            </tr>
                            @endforelse
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        <div class="d-flex justify-content-end">{{ $records->links() }}</div>
    </div>
</div>

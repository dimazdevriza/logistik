<div>
    <div class="container-fluid p-0">
        <!-- Hero Header -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-body-tertiary">
            <div class="card-body p-4 p-md-5 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 text-uppercase mb-2 font-geist small">Audit Trail</span>
                    <h1 class="display-5 fw-black text-body mb-2 font-outfit">
                        Material <span class="text-success">Transaction Log</span>
                    </h1>
                    <p class="text-secondary mb-0 max-w-xl">
                        Comprehensive ledger recording raw material restocks and project site consumption logs.
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
                    <button type="button" wire:click="toggleSortDirection" class="btn btn-outline-secondary btn-sm" title="Urutkan Tanggal">
                        {{ $sortDirection === 'asc' ? '⬆ Tanggal' : '⬇ Tanggal' }}
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
                                <td class="font-mono text-secondary small">{{ \Carbon\Carbon::parse($record->date)->format('d/m/Y') }}</td>
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
                                <td class="text-secondary small">{{ $record->user_name }}
                                    @if ($record->type === 'keluar' && in_array(auth()->user()->role, ['admin', 'logistik']))
                                        @if (($record->voided_at ?? null) !== null)
                                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-zinc-500/10 text-zinc-500 dark:text-zinc-400 border border-zinc-500/20">VOIDED</span>
                                        @else
                                            <button type="button" wire:confirm="Yakin membatalkan alokasi material ini? Stok akan dikembalikan."
                                                wire:click="voidMaterial({{ $record->id }})"
                                                class="ml-2 text-[11px] font-bold text-rose-500 hover:text-rose-700 dark:hover:text-rose-400">Batalkan</button>
                                        @endif
                                    @endif
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
                                <td class="font-mono text-secondary small">{{ $record->date->format('d/m/Y') }}</td>
                                <td class="fw-bold text-body">{{ $record->supplier->name ?? '-' }}</td>
                                <td class="fw-bold text-body">{{ $record->material->name ?? '-' }}</td>
                                <td class="text-end fw-bold">{{ number_format($record->quantity, 0, ',', '.') }} <span class="text-secondary small font-normal">{{ $record->material->unit ?? '' }}</span></td>
                                <td class="text-end font-mono text-secondary">Rp {{ number_format($record->unit_price, 0, ',', '.') }}</td>
                                <td class="text-end font-mono fw-bold text-success">Rp {{ number_format($record->total_cost, 0, ',', '.') }}</td>
                                <td class="text-secondary small">{{ $record->user->name ?? '-' }}</td>
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
                                <td class="font-mono text-secondary small">{{ $record->usage_date->format('d/m/Y') }}</td>
                                <td class="fw-bold text-body">{{ $record->house->name }}</td>
                                <td class="fw-bold text-body">{{ $record->material->name }}</td>
                                <td class="text-end fw-bold">{{ str_replace('.', ',', (float) $record->quantity) }} <span class="text-secondary small font-normal">{{ $record->material->unit }}</span></td>
                                <td class="text-end font-mono text-secondary">Rp {{ number_format($record->unit_price_at_usage, 0, ',', '.') }}</td>
                                <td class="text-end font-mono fw-bold text-success">Rp {{ number_format($record->total_cost, 0, ',', '.') }}</td>
                                <td class="text-secondary small">{{ $record->user->name }}
                                    @if (in_array(auth()->user()->role, ['admin', 'logistik']))
                                        @if ($record->voided_at !== null)
                                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-zinc-500/10 text-zinc-500 dark:text-zinc-400 border border-zinc-500/20">VOIDED</span>
                                        @else
                                            <button type="button" wire:confirm="Yakin membatalkan alokasi material ini? Stok akan dikembalikan."
                                                wire:click="voidMaterial({{ $record->id }})"
                                                class="ml-2 text-[11px] font-bold text-rose-500 hover:text-rose-700 dark:hover:text-rose-400">Batalkan</button>
                                        @endif
                                    @endif
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

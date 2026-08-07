<div>
    <div class="container-fluid p-0">
        <!-- Hero Header -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-body-tertiary">
            <div class="card-body p-4 p-md-5 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 text-uppercase mb-2 font-geist small">Equipment History</span>
                    <h1 class="display-5 fw-black text-body mb-2 font-outfit">
                        Tool <span class="text-success">Checkout Log</span>
                    </h1>
                    <p class="text-secondary mb-0 max-w-xl">
                        Detailed record tracking project equipment borrowing timelines and return statuses.
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
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari alat..." class="form-control" />
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" wire:click="toggleSortDirection" class="btn btn-outline-secondary btn-sm" title="Urutkan Tanggal">
                        {{ $sortDirection === 'asc' ? '⬆ Tanggal' : '⬇ Tanggal' }}
                    </button>
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
        </div>

        <!-- Tool Log Table -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-uppercase small font-geist">
                        <tr>
                            <th class="text-center" style="width: 50px;">No.</th>
                            <th>Tgl Pinjam</th>
                            <th>Rumah</th>
                            <th>Alat</th>
                            <th class="text-center">Qty</th>
                            <th>Tgl Kembali</th>
                            <th>Status</th>
                            <th>Dicatat oleh</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($usages as $usage)
                        <tr wire:key="t-log-{{ $usage->id }}">
                            <td class="text-center text-secondary small">{{ ($usages->currentPage() - 1) * $usages->perPage() + $loop->iteration }}</td>
                            <td class="font-mono text-secondary small">{{ $usage->checkout_date->format('d/m/Y') }}</td>
                            <td class="fw-bold text-body">{{ $usage->house->name }}</td>
                            <td>
                                <div class="fw-bold text-body">{{ $usage->tool->name }}</div>
                                <span class="font-mono extra-small text-secondary">Kode: {{ $usage->tool->code }}</span>
                            </td>
                            <td class="text-center font-mono fw-bold">{{ $usage->quantity }}</td>
                            <td class="font-mono text-secondary small">{{ $usage->return_date?->format('d/m/Y') ?? '-' }}</td>
                            <td>
                                @if ($usage->return_date)
                                    <span class="badge bg-success-subtle text-success">✓ Dikembalikan</span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning">⏳ Dipinjam</span>
                                @endif
                            </td>
                            <td class="text-secondary small">{{ $usage->user->name }}
                                @if (is_null($usage->return_date) && in_array(auth()->user()->role, ['admin', 'logistik']))
                                    @if ($usage->voided_at !== null)
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-zinc-500/10 text-zinc-500 dark:text-zinc-400 border border-zinc-500/20">VOIDED</span>
                                    @else
                                        <button type="button" wire:confirm="Yakin membatalkan peminjaman ini? Qty tersedia akan dikembalikan."
                                            wire:click="voidTool({{ $usage->id }})"
                                            class="ml-2 text-[11px] font-bold text-rose-500 hover:text-rose-700 dark:hover:text-rose-400">Batalkan</button>
                                    @endif
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-secondary">Belum ada data penggunaan alat.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="d-flex justify-content-end">{{ $usages->links() }}</div>
    </div>
</div>

<div>
    <div class="container-fluid p-0">
        <!-- Hero Header -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-body-tertiary">
            <div class="card-body p-4 p-md-5 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 text-uppercase mb-2 font-geist small">Riwayat Alat Kerja</span>
                    <h1 class="display-5 fw-black text-body mb-2 font-outfit">
                        Catatan Riwayat <span class="text-success">Peminjaman Alat</span>
                    </h1>
                    <p class="text-secondary mb-0 max-w-xl">
                        Catatan riwayat peminjaman dan pemrosesan pengembalian alat kerja proyek.
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
                    <button type="button" wire:click="toggleSortDirection" class="btn btn-outline-secondary px-3 d-inline-flex align-items-center gap-2 font-semibold shadow-xs" style="height: 38px;" title="Urutkan Tanggal">
                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16" style="{{ $sortDirection === 'asc' ? 'transform: rotate(180deg);' : '' }}">
                            <path fill-rule="evenodd" d="M8 1a.5.5 0 0 1 .5.5v11.793l3.146-3.147a.5.5 0 0 1 .708.708l-4 4a.5.5 0 0 1-.708 0l-4-4a.5.5 0 0 1 .708-.708L7.5 13.293V1.5A.5.5 0 0 1 8 1z"/>
                        </svg>
                        <span>Tanggal</span>
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
                            <td class="text-secondary small">
                                <div class="d-inline-flex align-items-center gap-1 flex-wrap">
                                    <span>{{ $usage->user->name }}</span>
                                    @if($usage->proof_image)
                                        <a href="{{ asset('storage/' . $usage->proof_image) }}" target="_blank" class="badge bg-info-subtle text-info text-decoration-none d-inline-flex align-items-center gap-1" title="Lihat Foto Bukti">
                                            <svg width="12" height="12" fill="currentColor"><use href="#i-camera"/></svg> Bukti
                                        </a>
                                    @endif
                                    @if (is_null($usage->return_date) && in_array(auth()->user()->role, ['admin', 'logistik']))
                                        @if ($usage->voided_at !== null)
                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle extra-small text-uppercase tracking-wider">VOIDED</span>
                                        @else
                                            <button type="button" wire:confirm="Yakin membatalkan peminjaman ini? Qty tersedia akan dikembalikan."
                                                wire:click="voidTool({{ $usage->id }})"
                                                class="btn btn-outline-danger btn-xs py-0 px-1.5 ms-1 extra-small font-semibold rounded-2 d-inline-flex align-items-center gap-1"
                                                title="Batalkan Peminjaman">
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

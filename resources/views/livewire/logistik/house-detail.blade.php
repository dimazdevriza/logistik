<div>
    <div class="container-fluid p-0">
        <!-- Hero Header -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-body-tertiary">
            <div class="card-body p-4 p-md-5 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <a href="{{ route('logistik.houses') }}" wire:navigate class="btn btn-outline-secondary btn-sm font-semibold">
                            ← Kembali
                        </a>
                        @php
                            $statusClasses = ['perencanaan' => 'bg-warning-subtle text-warning', 'pembangunan' => 'bg-primary-subtle text-primary', 'selesai' => 'bg-success-subtle text-success'];
                        @endphp
                        <span class="badge {{ $statusClasses[$house->status] ?? 'bg-secondary-subtle text-secondary' }}">{{ ucfirst($house->status) }}</span>
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <h1 class="display-5 fw-black text-body mb-0 font-outfit">{{ $house->name }}</h1>
                        @if($house->house_code)
                            <span class="badge bg-secondary-subtle text-secondary font-mono fs-6">{{ $house->house_code }}</span>
                        @endif
                    </div>
                    <p class="text-secondary mb-0 mt-1">Tipe Unit: {{ $house->type }}</p>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    @if(in_array(auth()->user()->role, ['admin', 'logistik']))
                        <button type="button" wire:click="exportExcel" class="btn btn-outline-success font-semibold">Export Excel</button>
                    @endif
                    @if($house->status !== 'selesai')
                        <a href="{{ route('logistik.house-finish', $house) }}" wire:navigate class="btn btn-warning font-semibold">Selesaikan Rumah</a>
                    @else
                        <span class="badge bg-success-subtle text-success p-2">Proyek Selesai — Data Terkunci</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Bento Stats Grid -->
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card border-0 border-start border-4 border-warning shadow-sm rounded-4 p-4 bg-body-tertiary">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="small fw-bold text-secondary text-uppercase tracking-wider">Total Biaya Material</span>
                        <div class="p-2 bg-warning-subtle text-warning rounded d-flex align-items-center justify-content-center">
                            <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M12.136.326A1.5 1.5 0 0 1 14 1.78V3h.5A1.5 1.5 0 0 1 16 4.5v9a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 0 13.5v-9A1.5 1.5 0 0 1 1.5 3H2V1.78a1.5 1.5 0 0 1 1.864-1.454l8.272 2zm-7.468 3h6.664V1.8a.5.5 0 0 0-.62-.485L3.864 2.827a.5.5 0 0 0-.196.499zM1 4.5v9a.5.5 0 0 0 .5.5h13a.5.5 0 0 0 .5-.5v-9a.5.5 0 0 0-.5-.5h-13a.5.5 0 0 0-.5.5z"/></svg>
                        </div>
                    </div>
                    <div>
                        <h2 class="fw-black text-warning mb-1 font-mono">Rp {{ number_format($house->total_material_cost, 0, ',', '.') }}</h2>
                        <span class="text-secondary small">Akumulasi pengeluaran material pada unit ini</span>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 border-start border-4 border-primary shadow-sm rounded-4 p-4 bg-body-tertiary">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="small fw-bold text-secondary text-uppercase tracking-wider">Total Transaksi</span>
                        <div class="p-2 bg-primary-subtle text-primary rounded d-flex align-items-center justify-content-center">
                            <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2z"/><path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466"/></svg>
                        </div>
                    </div>
                    <div>
                        <h2 class="fw-black text-body mb-1 font-mono">{{ $materialCount + $toolCount }} <span class="fs-6 text-secondary font-normal">Log</span></h2>
                        <span class="text-secondary small">Aktivitas penggunaan material & peminjaman alat</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs Container -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-body-tertiary">
            <ul class="nav nav-tabs mb-4">
                <li class="nav-item">
                    <button type="button" class="nav-link font-semibold d-inline-flex align-items-center gap-2" :class="$wire.activeTab === 'material' ? 'active text-success border-success' : 'text-secondary'" wire:click="$set('activeTab', 'material')">
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5l6.154 2.38 6.154-2.38zM15 4.239l-6.5 2.515v7.182l6.5-2.6v-7.097zM7.5 13.936V6.754L1 4.239v7.097z"/></svg>
                        <span>Penggunaan Material ({{ $materialCount }})</span>
                    </button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link font-semibold d-inline-flex align-items-center gap-2" :class="$wire.activeTab === 'tool' ? 'active text-success border-success' : 'text-secondary'" wire:click="$set('activeTab', 'tool')">
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M1 0 0 1l2.2 3.081a1 1 0 0 0 .815.419h.07a1 1 0 0 1 .708.293l2.675 2.675-2.617 2.654A3.003 3.003 0 0 0 0 13a3 3 0 1 0 5.293-1.971l2.646-2.646 2.675 2.675a1 1 0 0 1 .293.707v.07a1 1 0 0 0 .419.815L15 16l1-1-3.081-2.2a1 1 0 0 0-.815-.419h-.07a1 1 0 0 1-.708-.293L8.65 9.412l2.617-2.654A3.003 3.003 0 0 0 16 3a3 3 0 1 0-5.293 1.971L8.06 7.618 5.386 4.943a1 1 0 0 1-.293-.707v-.07a1 1 0 0 0-.419-.815z"/></svg>
                        <span>Peminjaman Alat ({{ $toolCount }})</span>
                    </button>
                </li>
            </ul>

            @if($activeTab === 'material')
            <div>
                <h5 class="fw-bold mb-3 font-outfit">Log Penggunaan Material</h5>
                <div class="table-responsive mb-3">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-uppercase small font-geist">
                            <tr>
                                <th class="text-center" style="width: 50px;">No.</th>
                                <th>Tanggal</th>
                                <th>Pekerjaan</th>
                                <th>Kode</th>
                                <th>Material</th>
                                <th class="text-end">Volume</th>
                                <th>Satuan</th>
                                <th class="text-end">Harga Satuan</th>
                                <th class="text-end">Total Biaya</th>
                                <th>Pencatat</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($materialUsages as $usage)
                            <tr wire:key="m-usage-{{ $usage->id }}">
                                <td class="text-center text-secondary small">{{ ($materialUsages->currentPage() - 1) * $materialUsages->perPage() + $loop->iteration }}</td>
                                <td class="font-mono text-secondary small">{{ $usage->usage_date->format('d/m/Y H:i') }}</td>
                                <td class="fw-semibold text-body">{{ $usage->notes ?: '-' }}</td>
                                <td class="font-mono text-secondary small">{{ $usage->material->code ?? '-' }}</td>
                                <td class="fw-bold text-body">{{ $usage->material->name }}</td>
                                <td class="text-end fw-bold">{{ str_replace('.', ',', (float) $usage->quantity) }}</td>
                                <td class="text-secondary small">{{ $usage->material->unit }}</td>
                                <td class="text-end font-mono text-secondary">Rp {{ number_format($usage->unit_price_at_usage, 0, ',', '.') }}</td>
                                <td class="text-end font-mono fw-bold text-success">Rp {{ number_format($usage->total_cost, 0, ',', '.') }}</td>
                                <td class="text-secondary small">{{ $usage->user->name }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="10" class="text-center py-4 text-secondary">Belum ada data penggunaan material untuk rumah ini.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-end">{{ $materialUsages->links() }}</div>
            </div>
            @endif

            @if($activeTab === 'tool')
            <div>
                <h5 class="fw-bold mb-3 font-outfit">Log Peminjaman Alat</h5>
                <div class="table-responsive mb-3">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-uppercase small font-geist">
                            <tr>
                                <th class="text-center" style="width: 50px;">No.</th>
                                <th>Tanggal Pinjam</th>
                                <th>Alat</th>
                                <th class="text-end">Jumlah</th>
                                <th class="text-center">Status</th>
                                <th>Pencatat</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($toolUsages as $usage)
                            <tr wire:key="t-usage-{{ $usage->id }}">
                                <td class="text-center text-secondary small">{{ ($toolUsages->currentPage() - 1) * $toolUsages->perPage() + $loop->iteration }}</td>
                                <td class="font-mono text-secondary small">{{ $usage->checkout_date->format('d/m/Y H:i') }}</td>
                                <td>
                                    <div class="fw-bold">{{ $usage->tool->name }}</div>
                                    <div class="extra-small text-secondary font-mono">Kode: {{ $usage->tool->code }}</div>
                                </td>
                                <td class="text-end font-mono fw-bold">{{ number_format($usage->quantity) }}</td>
                                <td class="text-center">
                                    @if($usage->return_date)
                                        <span class="badge bg-success-subtle text-success">Dikembalikan ({{ $usage->return_date->format('d/m') }})</span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning">Dipinjam</span>
                                    @endif
                                </td>
                                <td class="text-secondary small">{{ $usage->user->name }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-secondary">Belum ada data peminjaman alat untuk rumah ini.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-end">{{ $toolUsages->links() }}</div>
            </div>
            @endif
        </div>
    </div>
</div>

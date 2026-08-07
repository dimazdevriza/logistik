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
                        <div class="p-2 bg-warning-subtle text-warning rounded">💵</div>
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
                        <div class="p-2 bg-primary-subtle text-primary rounded">🔄</div>
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
                    <button type="button" class="nav-link font-semibold" :class="$wire.activeTab === 'material' ? 'active text-success border-success' : 'text-secondary'" wire:click="$set('activeTab', 'material')">
                        📦 Penggunaan Material ({{ $materialCount }})
                    </button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link font-semibold" :class="$wire.activeTab === 'tool' ? 'active text-success border-success' : 'text-secondary'" wire:click="$set('activeTab', 'tool')">
                        🔧 Peminjaman Alat ({{ $toolCount }})
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
                                <th>Material</th>
                                <th class="text-end">Jumlah</th>
                                <th class="text-end">Total Biaya</th>
                                <th>Pencatat</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($materialUsages as $usage)
                            <tr wire:key="m-usage-{{ $usage->id }}">
                                <td class="text-center text-secondary small">{{ ($materialUsages->currentPage() - 1) * $materialUsages->perPage() + $loop->iteration }}</td>
                                <td class="font-mono text-secondary small">{{ $usage->usage_date->format('d/m/Y H:i') }}</td>
                                <td>
                                    <div class="fw-bold">{{ $usage->material->name }}</div>
                                    @if($usage->notes)
                                        <div class="extra-small text-secondary">{{ Str::limit($usage->notes, 40) }}</div>
                                    @endif
                                </td>
                                <td class="text-end fw-bold">{{ str_replace('.', ',', (float) $usage->quantity) }} <span class="text-secondary small font-normal">{{ $usage->material->unit }}</span></td>
                                <td class="text-end font-mono fw-bold text-warning">Rp {{ number_format($usage->total_cost, 0, ',', '.') }}</td>
                                <td class="text-secondary small">{{ $usage->user->name }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-secondary">Belum ada data penggunaan material untuk rumah ini.</td>
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

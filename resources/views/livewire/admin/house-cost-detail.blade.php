<div>
    <div class="container-fluid p-0">
        <!-- Hero Header -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-body-tertiary">
            <div class="card-body p-4 p-md-5 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <a href="{{ route('admin.house-costs') }}" wire:navigate class="btn btn-outline-secondary btn-sm font-semibold">
                            ← Kembali
                        </a>
                        <span class="badge bg-success-subtle text-success">Detail Biaya Unit</span>
                    </div>
                    <h1 class="display-5 fw-black text-body mb-1 font-outfit">{{ $house->name }} — {{ $house->type }}</h1>
                    <p class="text-secondary mb-0">Detail pengeluaran dan rincian alokasi material pembangunan.</p>
                </div>

                @if(auth()->user()->role === 'admin')
                    <div>
                        <button type="button" wire:click="exportExcel" class="btn btn-outline-success font-semibold">Export Excel</button>
                    </div>
                @endif
            </div>
        </div>

        <!-- Summary Card -->
        <div class="row g-4 mb-4">
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 border-start border-4 border-warning shadow-sm rounded-4 p-4 bg-body-tertiary">
                    <span class="small fw-bold text-secondary text-uppercase tracking-wider mb-2 d-block">Total Biaya Material</span>
                    <h2 class="fw-black text-warning font-mono mb-0">Rp {{ number_format($totalCost, 0, ',', '.') }}</h2>
                </div>
            </div>
        </div>

        <!-- Cost by Category -->
        @if ($costByCategory->count() > 0)
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-body-tertiary">
            <h5 class="fw-bold font-outfit mb-3">Biaya per Kategori</h5>
            <div class="vstack gap-2">
                @foreach ($costByCategory as $cat)
                <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                    <span class="text-body fw-medium">{{ $cat->category_name }}</span>
                    <span class="font-mono fw-bold text-body">Rp {{ number_format($cat->total, 0, ',', '.') }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Usage Table -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-uppercase small font-geist">
                        <tr>
                            <th>Tanggal</th>
                            <th>Material</th>
                            <th class="text-end">Qty</th>
                            <th class="text-end">Harga Satuan</th>
                            <th class="text-end">Total</th>
                            <th>Dicatat oleh</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($materialUsages as $usage)
                        <tr wire:key="h-cost-det-{{ $usage->id }}">
                            <td class="font-mono text-secondary small">{{ $usage->usage_date->format('d/m/Y') }}</td>
                            <td class="fw-bold text-body">{{ $usage->material->name }}</td>
                            <td class="text-end fw-bold">{{ str_replace('.', ',', (float) $usage->quantity) }} <span class="text-secondary small font-normal">{{ $usage->material->unit }}</span></td>
                            <td class="text-end font-mono text-secondary">Rp {{ number_format($usage->unit_price_at_usage, 0, ',', '.') }}</td>
                            <td class="text-end font-mono fw-bold text-warning">Rp {{ number_format($usage->total_cost, 0, ',', '.') }}</td>
                            <td class="text-secondary small">{{ $usage->user->name }}</td>
                            <td class="text-secondary small text-truncate" style="max-width: 200px;">{{ $usage->notes ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-secondary">Belum ada penggunaan material untuk rumah ini.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="d-flex justify-content-end">{{ $materialUsages->links() }}</div>
    </div>
</div>

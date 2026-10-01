<div>
    <div class="container-fluid p-0">
        <!-- Hero Header -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-body-tertiary">
            <div class="card-body p-4 p-md-5 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <div>
                    <div class="mb-2">
                        <a href="{{ route(auth()->user()->role === 'admin' ? 'admin.house-costs' : 'logistik.house-costs') }}" wire:navigate class="back-link">
                            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8"/></svg>
                            Kembali
                        </a>
                    </div>
                    <h1 class="display-5 fw-black text-body mb-1 font-outfit">{{ $house->name }} - {{ $house->type }}</h1>
                    <p class="text-secondary mb-0">Detail pengeluaran dan rincian alokasi material pembangunan.</p>
                </div>

                    <div>
                        <button type="button" wire:click="exportExcel" class="btn btn-utility font-semibold">
                            <svg width="15" height="15" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5"/>
                                <path d="M7.646 1.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 2.707V10.5a.5.5 0 0 1-1 0V2.707L5.354 4.854a.5.5 0 1 1-.708-.708z"/>
                            </svg>
                            <span>Ekspor Excel</span>
                        </button>
                    </div>
            </div>
        </div>

        <!-- Summary Card -->
        <div class="row g-4 mb-4 house-cost-detail-summary">
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 border-start border-4 border-warning shadow-sm rounded-4 p-4 bg-body-tertiary">
                    <span class="small fw-bold text-secondary text-uppercase tracking-wider mb-2 d-block">Total Biaya Material</span>
                    <h2 class="fw-black text-warning font-mono mb-0">Rp {{ number_format($totalCost, 0, ',', '.') }}</h2>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 border-start border-4 border-danger shadow-sm rounded-4 p-4 bg-body-tertiary">
                    <span class="small fw-bold text-secondary text-uppercase tracking-wider mb-2 d-block">Kerugian Material</span>
                    <h2 class="fw-black text-danger font-mono mb-0">Rp {{ number_format((float) $totalMaterialLoss, 0, ',', '.') }}</h2>
                    <span class="small text-secondary mt-2">Rusak dan hilang; terpisah dari biaya material layak pakai.</span>
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
            <div class="table-responsive house-cost-detail-table-wrap">
                <table class="table table-hover align-middle mb-0 house-cost-detail-table">
                    <thead class="table-light text-uppercase small font-geist">
                        <tr>
                            <x-sortable-th field="date" :sort="$sort">Tanggal</x-sortable-th>
                            <x-sortable-th field="material" :sort="$sort">Material</x-sortable-th>
                            <x-sortable-th field="quantity" :sort="$sort" class="text-end">Qty</x-sortable-th>
                            <x-sortable-th field="unit_price" :sort="$sort" class="text-end">Harga Satuan</x-sortable-th>
                            <x-sortable-th field="total" :sort="$sort" class="text-end">Total</x-sortable-th>
                            <x-sortable-th field="user" :sort="$sort">Dicatat oleh</x-sortable-th>
                            <x-sortable-th field="notes" :sort="$sort">Catatan</x-sortable-th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($materialUsages as $usage)
                        <tr wire:key="h-cost-det-{{ $usage->id }}">
                            <td data-label="Tanggal" class="font-mono text-secondary small"><span class="house-cost-detail-cell-value">{{ $usage->usage_date->format('d/m/Y') }}</span></td>
                            <td data-label="Material" class="fw-bold text-body"><span class="house-cost-detail-cell-value">{{ $usage->material->name }}</span></td>
                            <td data-label="Qty" class="text-end fw-bold"><span class="house-cost-detail-cell-value">{{ str_replace('.', ',', (float) $usage->quantity) }} <span class="text-secondary small font-normal">{{ $usage->material->unit }}</span></span></td>
                            <td data-label="Harga satuan" class="text-end font-mono text-secondary"><span class="house-cost-detail-cell-value">Rp {{ number_format($usage->unit_price_at_usage, 0, ',', '.') }}</span></td>
                            <td data-label="Total" class="text-end font-mono fw-bold text-warning"><span class="house-cost-detail-cell-value">Rp {{ number_format($usage->total_cost, 0, ',', '.') }}</span></td>
                            <td data-label="Dicatat oleh" class="text-secondary small"><span class="house-cost-detail-cell-value">{{ $usage->user->name }}</span></td>
                            <td data-label="Catatan" class="text-secondary small"><span class="house-cost-detail-cell-value">{{ $usage->notes ?? '-' }}</span></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="house-cost-detail-empty text-center py-4 text-secondary">Belum ada penggunaan material untuk rumah ini.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="d-flex justify-content-end">{{ $materialUsages->links() }}</div>

        <section class="mt-5" aria-labelledby="material-loss-title">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-2 mb-3">
                <div>
                    <h2 id="material-loss-title" class="h4 fw-bold mb-1">Kerugian material</h2>
                    <p class="text-secondary small mb-0">Nilai kerusakan dan kehilangan tercatat terpisah dari biaya material layak pakai.</p>
                </div>
                <div class="font-mono fw-bold text-danger">Rp {{ number_format((float) $totalMaterialLoss, 0, ',', '.') }}</div>
            </div>
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-body-tertiary">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-uppercase small">
                            <tr><th>Tanggal</th><th>Kode pengiriman / batch</th><th>Material</th><th class="text-end">Qty</th><th class="text-end">Nilai</th><th>Dicatat oleh</th><th>Alasan</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($materialLosses as $loss)
                                <tr wire:key="material-loss-{{ $loss->id }}">
                                    <td class="font-mono text-secondary small">{{ $loss->recorded_at->format('d/m/Y H:i') }}</td>
                                    <td class="small"><span class="d-block">{{ $loss->request->dispatch_code ?? $loss->request->request_code }}</span><span class="text-secondary">{{ $loss->dispatchLine?->stockIn?->entry_code ?? 'Batch warisan' }}</span></td>
                                    <td class="fw-semibold">{{ $loss->request->material?->name ?? 'Material dihapus' }}</td>
                                    <td class="text-end font-mono">{{ number_format((float) $loss->quantity, 2, ',', '.') }}</td>
                                    <td class="text-end font-mono fw-semibold text-danger">Rp {{ number_format((float) $loss->total_cost, 0, ',', '.') }}</td>
                                    <td class="small">{{ $loss->recordedBy?->name ?? 'Pengguna dihapus' }}</td>
                                    <td class="small">{{ $loss->notes }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center py-4 text-secondary">Belum ada kerugian material yang tercatat.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="d-flex justify-content-end mt-2">{{ $materialLosses->links() }}</div>
        </section>
    </div>
</div>

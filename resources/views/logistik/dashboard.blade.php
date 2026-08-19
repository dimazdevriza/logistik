<x-layouts::app.sidebar title="Dashboard Logistik">
    <div class="container-fluid p-0">
        <!-- Hero Header -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-body-tertiary">
            <div class="card-body p-4 p-md-5 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 text-uppercase mb-2 font-geist small">Modul Logistik</span>
                    <h1 class="display-5 fw-black text-body mb-2 font-outfit">
                        Kontrol Operasional <span class="text-success">Logistik</span>
                    </h1>
                    <p class="text-secondary mb-0 max-w-xl">
                        Pantau ketersediaan stok, peringatan stok menipis, dan kelola peminjaman alat konstruksi.
                    </p>
                </div>
                <div class="p-3 rounded-3 border bg-body-tertiary shadow-xs d-flex align-items-center gap-3">
                    <div class="p-2 bg-success-subtle text-success rounded d-flex align-items-center justify-content-center">
                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M0 3.5A1.5 1.5 0 0 1 1.5 2h9A1.5 1.5 0 0 1 12 3.5V5h1.02a1.5 1.5 0 0 1 1.17.563l1.481 1.85a1.5 1.5 0 0 1 .329.938V10.5a1.5 1.5 0 0 1-1.5 1.5H14a2 2 0 1 1-4 0H5a2 2 0 1 1-4 0H1.5A1.5 1.5 0 0 1 0 10.5v-7zm1 0v7a.5.5 0 0 0 .5.5H2a2 2 0 0 1 3.5 0h4.5a2 2 0 0 1 3.5 0h1.5a.5.5 0 0 0 .5-.5V8.851a.5.5 0 0 0-.11-.312L14.41 6.689A.5.5 0 0 0 14.02 6.5H12v-3a.5.5 0 0 0-.5-.5h-9a.5.5 0 0 0-.5.5z"/></svg>
                    </div>
                    <div>
                        <div class="extra-small text-secondary fw-bold text-uppercase">Status Inventaris</div>
                        <div class="fw-bold text-body small">Sistem Berjalan Normal</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bento Stats Grid -->
        <div class="row g-4 mb-4">
            <!-- Total Material -->
            <div class="col-md-4">
                <div class="card border-0 border-start border-4 border-success shadow-sm rounded-4 p-4 bg-body-tertiary">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="small fw-bold text-secondary text-uppercase tracking-wider">Total Material</span>
                        <div class="p-2 bg-success-subtle text-success rounded d-flex align-items-center justify-content-center">
                            <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5l6.154 2.38 6.154-2.38zM15 4.239l-6.5 2.515v7.182l6.5-2.6v-7.097zM7.5 13.936V6.754L1 4.239v7.097z"/></svg>
                        </div>
                    </div>
                    <div>
                        <h2 class="fw-black text-body mb-1">{{ $total_materials }} <span class="fs-6 text-secondary font-normal">Item</span></h2>
                        <span class="text-secondary small">Tersedia di Gudang</span>
                    </div>
                </div>
            </div>

            <!-- Low Stock Alert -->
            <div class="col-md-4">
                <div class="card border-0 border-start border-4 border-danger shadow-sm rounded-4 p-4 bg-body-tertiary">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="small fw-bold text-secondary text-uppercase tracking-wider">Stok Menipis</span>
                        <div class="p-2 bg-danger-subtle text-danger rounded d-flex align-items-center justify-content-center">
                            <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/></svg>
                        </div>
                    </div>
                    <div>
                        <h2 class="fw-black text-danger mb-1">{{ $low_stock_count }} <span class="fs-6 text-secondary font-normal">Item</span></h2>
                        <span class="text-danger small">Stok di bawah 10 unit</span>
                    </div>
                </div>
            </div>

            <!-- Tools on Loan -->
            <div class="col-md-4">
                <div class="card border-0 border-start border-4 border-warning shadow-sm rounded-4 p-4 bg-body-tertiary">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="small fw-bold text-secondary text-uppercase tracking-wider">Alat Sedang Dipinjam</span>
                        <div class="p-2 bg-warning-subtle text-warning rounded d-flex align-items-center justify-content-center">
                            <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M1 0 0 1l2.2 3.081a1 1 0 0 0 .815.419h.07a1 1 0 0 1 .708.293l2.675 2.675-2.617 2.654A3.003 3.003 0 0 0 0 13a3 3 0 1 0 5.293-1.881l2.654-2.617 2.675 2.675a1 1 0 0 1 .293.707v.07a1 1 0 0 0 .419.815L15 16l1-1-3.081-2.2a1 1 0 0 0-.419-.815v-.07a1 1 0 0 1-.293-.708L9.53 8.532l2.617-2.654A3.003 3.003 0 0 0 16 3a3 3 0 1 0-5.293 1.881L8.053 7.5 5.378 4.825a1 1 0 0 1-.293-.707v-.07a1 1 0 0 0-.419-.815L1 0z"/></svg>
                        </div>
                    </div>
                    <div>
                        <h2 class="fw-black text-warning mb-1">{{ $tools_on_loan }} <span class="fs-6 text-secondary font-normal">Transaksi</span></h2>
                        <span class="text-secondary small">Menunggu pengembalian</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activities Table -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-body-tertiary mb-4">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <h5 class="fw-bold font-outfit text-body mb-0">Aktivitas Penggunaan Terbaru</h5>
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 extra-small font-mono">{{ $recent_activities->count() }} Records</span>
                    </div>
                    <p class="text-secondary extra-small mb-0">Catatan alokasi material terbaru pada proyek pembangunan rumah.</p>
                </div>
                <a href="{{ route('logistik.houses') }}" wire:navigate class="btn btn-outline-secondary btn-sm font-semibold rounded-3 d-inline-flex align-items-center gap-1">
                    Buka Proyek Rumah →
                </a>
            </div>

            <!-- Modern Playground-Style Table Container -->
            <div class="table-responsive rounded-3 border overflow-hidden">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-body-secondary border-bottom">
                        <tr class="text-secondary extra-small text-uppercase font-geist tracking-wider">
                            <th class="py-3 px-3">Waktu</th>
                            <th class="py-3 px-3">Unit Rumah</th>
                            <th class="py-3 px-3">Item / Material</th>
                            <th class="py-3 px-3 text-end">Jumlah Alokasi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($recent_activities as $activity)
                        <tr class="transition-all">
                            <td class="py-3 px-3">
                                <div class="fw-bold font-mono text-body small">{{ $activity->created_at->diffForHumans() }}</div>
                                <div class="extra-small text-secondary">{{ $activity->created_at->format('d/m/Y H:i') }}</div>
                            </td>
                            <td class="py-3 px-3">
                                <span class="badge bg-body border text-body font-mono px-2.5 py-1.5 rounded-2">{{ $activity->house->name }}</span>
                            </td>
                            <td class="py-3 px-3">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="p-2 rounded-2 bg-success-subtle text-success d-inline-flex align-items-center justify-content-center">
                                        <svg width="14" height="14" fill="currentColor"><use href="#i-box"/></svg>
                                    </div>
                                    <div>
                                        <div class="fw-semibold text-body small">{{ $activity->material->name }}</div>
                                        <div class="extra-small text-secondary">Kategori: {{ $activity->material->category?->name ?? 'Material' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-3 text-end font-mono fw-bold text-success">
                                {{ str_replace('.', ',', (float) $activity->quantity) }} <span class="extra-small text-secondary font-normal">{{ $activity->material->unit }}</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="py-4 text-center text-secondary extra-small">Belum ada aktivitas penggunaan.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts::app.sidebar>

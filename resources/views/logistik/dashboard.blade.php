<x-layouts::app.sidebar title="Dashboard Logistik">
    <div class="container-fluid p-0">
        <!-- Hero Header -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-body-tertiary">
            <div class="card-body p-4 p-md-5 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <div>
                    <h1 class="display-5 fw-black text-body mb-2 font-outfit">
                        Kontrol Operasional <span class="text-success">{{ auth()->user()->role === 'pengawas' ? 'Pengawas' : 'Logistik' }}</span>
                    </h1>
                    <p class="text-secondary mb-0 max-w-xl">
                        Pantau ketersediaan stok, peringatan stok menipis, dan kelola peminjaman alat konstruksi.
                    </p>
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
                    Buka Proyek Rumah
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793L9.146 3.354a.5.5 0 1 1 .708-.708l5 5a.5.5 0 0 1 0 .708l-5 5a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8"/></svg>
                </a>
            </div>

            <!-- Modern Playground-Style Table Container -->
            <div class="table-responsive rounded-3 border overflow-hidden">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-body-secondary border-bottom">
                        <tr class="text-secondary extra-small text-uppercase font-geist tracking-wider">
                            <x-sortable-th field="date" :sort="$activity_sort" :href="request()->fullUrlWithQuery(['activity_sort' => $activity_sort === 'date_asc' ? 'date_desc' : 'date_asc'])" class="py-3 px-3">Waktu</x-sortable-th>
                            <x-sortable-th field="house" :sort="$activity_sort" :href="request()->fullUrlWithQuery(['activity_sort' => $activity_sort === 'house_asc' ? 'house_desc' : 'house_asc'])" class="py-3 px-3">Unit Rumah</x-sortable-th>
                            <x-sortable-th field="material" :sort="$activity_sort" :href="request()->fullUrlWithQuery(['activity_sort' => $activity_sort === 'material_asc' ? 'material_desc' : 'material_asc'])" class="py-3 px-3">Item / Material</x-sortable-th>
                            <x-sortable-th field="quantity" :sort="$activity_sort" :href="request()->fullUrlWithQuery(['activity_sort' => $activity_sort === 'quantity_asc' ? 'quantity_desc' : 'quantity_asc'])" class="py-3 px-3 text-end">Jumlah Alokasi</x-sortable-th>
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

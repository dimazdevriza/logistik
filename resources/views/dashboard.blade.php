<x-layouts::app.sidebar title="Admin Dashboard">
    <div class="container-fluid p-0">
        <!-- Hero Header -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-body-tertiary">
            <div class="card-body p-4 p-md-5 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <div>
                    <h1 class="display-5 fw-black text-body mb-2 font-outfit">
                        Portal Kontrol <span class="text-success">D'Royal Village</span>
                    </h1>
                    <p class="text-secondary mb-0 max-w-xl">
                        Pantau arus logistik, analisis biaya pembangunan, dan kelola sumber daya proyek.
                    </p>
                </div>
            </div>
        </div>

        <!-- Bento Grid -->
        <div class="row g-4">
            <!-- Total Houses -->
            <div class="col-md-6 col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 h-100 p-4 bg-body-tertiary">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="small fw-bold text-secondary text-uppercase tracking-wider">Unit Rumah</span>
                        <div class="p-2 bg-body rounded text-success d-flex align-items-center justify-content-center">
                            <svg width="18" height="18" fill="currentColor" aria-hidden="true"><use href="#i-houses"/></svg>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline gap-2 mb-3">
                        <span class="display-5 fw-extrabold text-body">{{ $total_houses }}</span>
                        <span class="text-secondary small fw-semibold">Unit terdaftar</span>
                    </div>
                </div>
            </div>

            <!-- Total Users -->
            <div class="col-md-3 col-lg-3">
                <div class="card border-0 shadow-sm rounded-4 h-100 p-4 bg-body-tertiary">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="small fw-bold text-secondary text-uppercase tracking-wider">Pengguna</span>
                        <div class="p-2 bg-body rounded text-primary d-flex align-items-center justify-content-center">
                            <svg width="18" height="18" fill="currentColor" aria-hidden="true"><use href="#i-people"/></svg>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline gap-2 mb-3">
                        <span class="display-5 fw-extrabold text-body">{{ $total_users }}</span>
                        <span class="text-secondary extra-small fw-semibold">Terdaftar</span>
                    </div>
                    <div class="pt-3 border-top text-secondary small">
                        Manajemen peran & akses
                    </div>
                </div>
            </div>

            <!-- Total Suppliers -->
            <div class="col-md-3 col-lg-3">
                <div class="card border-0 shadow-sm rounded-4 h-100 p-4 bg-body-tertiary">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="small fw-bold text-secondary text-uppercase tracking-wider">Supplier</span>
                        <div class="p-2 bg-body rounded text-info d-flex align-items-center justify-content-center">
                            <svg width="18" height="18" fill="currentColor" aria-hidden="true"><use href="#i-truck"/></svg>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline gap-2 mb-3">
                        <span class="display-5 fw-extrabold text-body">{{ $total_suppliers }}</span>
                        <span class="text-secondary extra-small fw-semibold">Rekan</span>
                    </div>
                    <div class="pt-3 border-top text-secondary small">
                        Mitra rantai pasok
                    </div>
                </div>
            </div>

            <!-- Total Expenses -->
            <div class="col-md-6 col-lg-6">
                <div class="card border-0 border-start border-4 border-warning shadow-sm rounded-4 h-100 p-4 bg-body-tertiary">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="small fw-bold text-secondary text-uppercase tracking-wider">Pengeluaran</span>
                        <div class="p-2 bg-warning-subtle text-warning rounded d-flex align-items-center justify-content-center">
                            <svg width="18" height="18" fill="currentColor" aria-hidden="true"><use href="#i-chart"/></svg>
                        </div>
                    </div>
                    <div class="mb-3">
                        <h2 class="fw-black text-warning mb-1">Rp {{ number_format($total_cost, 0, ',', '.') }}</h2>
                        <span class="text-secondary small">Akumulasi pengeluaran material konstruksi</span>
                    </div>
                </div>
            </div>

            <!-- Quick Access -->
            <div class="col-md-6 col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 h-100 p-4 bg-body-tertiary">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="small fw-bold text-secondary text-uppercase tracking-wider">Akses Cepat</span>
                        <div class="p-2 bg-body rounded text-success d-flex align-items-center justify-content-center">
                            <svg width="18" height="18" fill="currentColor" aria-hidden="true"><use href="#i-dashboard"/></svg>
                        </div>
                    </div>
                    <div class="row g-2">
                        @if (auth()->user()->role === 'admin')
                            <div class="col-6"><a href="{{ route('admin.users') }}" class="btn btn-outline-secondary btn-sm w-100 text-start">Kelola User</a></div>
                        @endif
                        @if (in_array(auth()->user()->role, ['admin', 'logistik', 'pengawas'], true))
                            <div class="col-6"><a href="{{ route('logistik.houses') }}" class="btn btn-outline-secondary btn-sm w-100 text-start">Unit Rumah</a></div>
                        @endif
                        <div class="col-6"><a href="{{ route('logistik.materials') }}" class="btn btn-outline-secondary btn-sm w-100 text-start">Inventaris</a></div>
                        <div class="col-6"><a href="{{ route('admin.house-costs') }}" class="btn btn-outline-secondary btn-sm w-100 text-start">Laporan Biaya</a></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts::app.sidebar>

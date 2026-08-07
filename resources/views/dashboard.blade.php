<x-layouts::app.sidebar title="Admin Dashboard">
    <div class="container-fluid p-0">
        <!-- Hero Header -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-body-tertiary">
            <div class="card-body p-4 p-md-5 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 text-uppercase mb-2 font-geist small">System Operational</span>
                    <h1 class="display-5 fw-black text-body mb-2 font-outfit">
                        D'Royal Village <span class="text-success">Control Portal</span>
                    </h1>
                    <p class="text-secondary mb-0 max-w-xl">
                        Monitor logistics, analyze operational costs, and manage resources across system modules.
                    </p>
                </div>
                <div class="card border border-body-secondary shadow-sm rounded-3 p-3 bg-body" style="min-width: 260px;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="spinner-grow spinner-grow-sm text-success" role="status"></span>
                            <span class="small fw-bold text-secondary text-uppercase">Server Status</span>
                        </div>
                        <span class="badge bg-secondary-subtle text-secondary font-mono">v1.0.0</span>
                    </div>
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <div class="bg-success-subtle text-success p-2 rounded">
                            💻
                        </div>
                        <div>
                            <div class="extra-small text-secondary">Database</div>
                            <div class="fw-bold small">Connected</div>
                        </div>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: 94%;"></div>
                    </div>
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
                        <div class="p-2 bg-body rounded">🏠</div>
                    </div>
                    <div class="d-flex align-items-baseline gap-2 mb-3">
                        <span class="display-5 fw-extrabold text-body">{{ $total_houses }}</span>
                        <span class="text-secondary small fw-semibold">Unit terdaftar</span>
                    </div>
                    <div class="pt-3 border-top d-flex justify-content-between align-items-center small">
                        <span class="text-secondary">Status pembangunan</span>
                        <span class="badge bg-success-subtle text-success">Active</span>
                    </div>
                </div>
            </div>

            <!-- Total Users -->
            <div class="col-md-3 col-lg-3">
                <div class="card border-0 shadow-sm rounded-4 h-100 p-4 bg-body-tertiary">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="small fw-bold text-secondary text-uppercase tracking-wider">User</span>
                        <div class="p-2 bg-body rounded">👤</div>
                    </div>
                    <div class="d-flex align-items-baseline gap-2 mb-3">
                        <span class="display-5 fw-extrabold text-body">{{ $total_users }}</span>
                        <span class="text-secondary extra-small fw-semibold">Aktif</span>
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
                        <div class="p-2 bg-body rounded">🏬</div>
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
                        <div class="p-2 bg-warning-subtle text-warning rounded">💵</div>
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
                        <div class="p-2 bg-body rounded">➔</div>
                    </div>
                    <div class="row g-2">
                        <div class="col-6"><a href="{{ route('admin.users') }}" class="btn btn-outline-secondary btn-sm w-100 text-start">Kelola User</a></div>
                        <div class="col-6"><a href="{{ route('logistik.houses') }}" class="btn btn-outline-secondary btn-sm w-100 text-start">Unit Rumah</a></div>
                        <div class="col-6"><a href="{{ route('logistik.materials') }}" class="btn btn-outline-secondary btn-sm w-100 text-start">Inventaris</a></div>
                        <div class="col-6"><a href="{{ route('admin.house-costs') }}" class="btn btn-outline-secondary btn-sm w-100 text-start">Laporan Biaya</a></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts::app.sidebar>

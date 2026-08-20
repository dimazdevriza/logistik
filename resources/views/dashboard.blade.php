<x-layouts::app.sidebar title="Admin Dashboard">
    <div class="container-fluid p-0">
        <!-- Hero Header -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-body-tertiary">
            <div class="card-body p-4 p-md-5 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 text-uppercase mb-2 font-geist small">Sistem Operasional</span>
                    <h1 class="display-5 fw-black text-body mb-2 font-outfit">
                        Portal Kontrol <span class="text-success">D'Royal Village</span>
                    </h1>
                    <p class="text-secondary mb-0 max-w-xl">
                        Pantau arus logistik, analisis biaya pembangunan, dan kelola sumber daya proyek.
                    </p>
                </div>
                <div class="card border border-body-secondary shadow-sm rounded-3 p-3 bg-body" style="min-width: 260px;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="spinner-grow spinner-grow-sm text-success" role="status"></span>
                            <span class="small fw-bold text-secondary text-uppercase">Status Server</span>
                        </div>
                        <span class="badge bg-secondary-subtle text-secondary font-mono">v1.0.0</span>
                    </div>
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <div class="bg-success-subtle text-success p-2 rounded d-flex align-items-center justify-content-center">
                            <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M2 2a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2zm12 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z"/><path d="M6 12v-2a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2z"/></svg>
                        </div>
                        <div>
                            <div class="extra-small text-secondary">Basis Data</div>
                            <div class="fw-bold small">Terhubung</div>
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
                        <div class="p-2 bg-body rounded text-success d-flex align-items-center justify-content-center">
                            <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M8.707 1.5a1 1 0 0 0-1.414 0L.646 8.146a.5.5 0 0 0 .708.708L2 8.207V13.5A1.5 1.5 0 0 0 3.5 15h9a1.5 1.5 0 0 0 1.5-1.5V8.207l.646.647a.5.5 0 0 0 .708-.708L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293zM13 7.207V13.5a.5.5 0 0 1-.5.5h-9a.5.5 0 0 1-.5-.5V7.207l5-5z"/></svg>
                        </div>
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
                        <div class="p-2 bg-body rounded text-primary d-flex align-items-center justify-content-center">
                            <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0zE"/><path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1z"/></svg>
                        </div>
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
                        <div class="p-2 bg-body rounded text-info d-flex align-items-center justify-content-center">
                            <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M2.97 1.35A1 1 0 0 1 3.73 1h8.54a1 1 0 0 1 .76.35l2.609 3.044A1.5 1.5 0 0 1 16 5.37v.255a2.375 2.375 0 0 1-4.25 1.458A2.37 2.37 0 0 1 9.875 8 2.37 2.37 0 0 1 8 7.083 2.37 2.37 0 0 1 6.125 8a2.37 2.37 0 0 1-1.875-.917A2.375 2.375 0 0 1 0 5.625V5.37a1.5 1.5 0 0 1 .361-.976zm1.78 4.275a1.375 1.375 0 0 0 2.75 0 .5.5 0 0 1 1 0 1.375 1.375 0 0 0 2.75 0 .5.5 0 0 1 1 0 1.375 1.375 0 1 0 2.75 0V5.37a.5.5 0 0 0-.12-.325L12.27 2H3.73L1.12 5.045a.5.5 0 0 0-.12.325v.255a1.375 1.375 0 0 0 2.75 0 .5.5 0 0 1 1 0M1.5 8.5bea1 1 0 0 1 1-1h11a1 1 0 0 1 1 1v6a1 1 0 0 1-1 1h-11a1 1 0 0 1-1-1z"/></svg>
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
                            <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M12.136.326A1.5 1.5 0 0 1 14 1.78V3h.5A1.5 1.5 0 0 1 16 4.5v9a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 0 13.5v-9a1.5 1.5 0 0 1 1.432-1.499L12.136.326zM5.562 3H13V1.78a.5.5 0 0 0-.621-.484zM1.5 4a.5.5 0 0 0-.5.5v9a.5.5 0 0 0 .5.5h13a.5.5 0 0 0 .5-.5v-9a.5.5 0 0 0-.5-.5z"/></svg>
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

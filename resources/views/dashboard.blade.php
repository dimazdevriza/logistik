<x-layouts::app.sidebar title="Admin Dashboard">
    <div class="container-fluid p-0">
        {{-- Hero Header --}}
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
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('logistik.guide') }}" wire:navigate class="btn btn-outline-secondary btn-sm rounded-3 d-inline-flex align-items-center gap-2">
                        <svg width="15" height="15" fill="currentColor" aria-hidden="true"><use href="#i-book"/></svg>
                        <span>Panduan Sistem</span>
                    </a>
                    <a href="{{ route('admin.house-costs') }}" wire:navigate class="btn btn-outline-secondary btn-sm rounded-3 d-inline-flex align-items-center gap-2">
                        <svg width="15" height="15" fill="currentColor" aria-hidden="true"><use href="#i-chart"/></svg>
                        <span>Laporan Biaya</span>
                    </a>
                    @if(auth()->user()->role === 'admin')
                        <a href="{{ route('admin.users') }}" wire:navigate class="btn btn-success btn-sm rounded-3 d-inline-flex align-items-center gap-2">
                            <svg width="15" height="15" fill="currentColor" aria-hidden="true"><use href="#i-people"/></svg>
                            <span>Kelola User</span>
                        </a>
                    @endif
                </div>
            </div>
        </div>

        {{-- Bento Stats Grid --}}
        <div class="row g-3 g-md-4 mb-4">
            {{-- Total Houses with Status Breakdown --}}
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-4 h-100 p-4 bg-body-tertiary d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="small fw-bold text-secondary text-uppercase tracking-wider">Unit Rumah</span>
                        <div class="p-2 bg-body rounded text-success d-flex align-items-center justify-content-center">
                            <svg width="18" height="18" fill="currentColor" aria-hidden="true"><use href="#i-houses"/></svg>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline gap-2 mb-2">
                        <span class="display-6 fw-extrabold text-body">{{ $total_houses }}</span>
                        <span class="text-secondary small fw-semibold">Unit terdaftar</span>
                    </div>
                    <div class="d-flex flex-wrap gap-1 mt-auto pt-2 border-top">
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill extra-small font-mono">{{ $houses_construction }} Bangun</span>
                        <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill extra-small font-mono">{{ $houses_warranty }} Garansi</span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill extra-small font-mono">{{ $houses_finished }} Selesai</span>
                    </div>
                </div>
            </div>

            {{-- Total Expenses --}}
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 border-start border-4 border-warning shadow-sm rounded-4 h-100 p-4 bg-body-tertiary d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="small fw-bold text-secondary text-uppercase tracking-wider">Biaya Material</span>
                        <div class="p-2 bg-warning-subtle text-warning rounded d-flex align-items-center justify-content-center">
                            <svg width="18" height="18" fill="currentColor" aria-hidden="true"><use href="#i-chart"/></svg>
                        </div>
                    </div>
                    <div class="mb-2">
                        <h3 class="fw-black text-warning mb-1 font-mono">Rp {{ number_format($total_cost, 0, ',', '.') }}</h3>
                        <span class="text-secondary extra-small">Akumulasi material terdata</span>
                    </div>
                    <div class="pt-2 border-top text-secondary extra-small mt-auto">
                        Total alokasi material aktif
                    </div>
                </div>
            </div>

            {{-- Low Stock Alert --}}
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 border-start border-4 {{ $low_stock_count > 0 ? 'border-danger' : 'border-success' }} shadow-sm rounded-4 h-100 p-4 bg-body-tertiary d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="small fw-bold text-secondary text-uppercase tracking-wider">Stok Kritis</span>
                        <div class="p-2 {{ $low_stock_count > 0 ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' }} rounded d-flex align-items-center justify-content-center">
                            <svg width="18" height="18" fill="currentColor" aria-hidden="true"><use href="#i-box"/></svg>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline gap-2 mb-2">
                        <span class="display-6 fw-extrabold {{ $low_stock_count > 0 ? 'text-danger' : 'text-body' }}">{{ $low_stock_count }}</span>
                        <span class="text-secondary small fw-semibold">Item &le; 10 unit</span>
                    </div>
                    <div class="pt-2 border-top text-secondary extra-small mt-auto">
                        @if($low_stock_count > 0)
                            <span class="text-danger fw-semibold">Butuh pengadaan stok</span>
                        @else
                            <span class="text-success fw-semibold">Semua persediaan aman</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Alat Sedang Dipinjam --}}
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 border-start border-4 border-primary shadow-sm rounded-4 h-100 p-4 bg-body-tertiary d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="small fw-bold text-secondary text-uppercase tracking-wider">Alat di Lapangan</span>
                        <div class="p-2 bg-body rounded text-primary d-flex align-items-center justify-content-center">
                            <svg width="18" height="18" fill="currentColor" aria-hidden="true"><use href="#i-wrench"/></svg>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline gap-2 mb-2">
                        <span class="display-6 fw-extrabold text-body">{{ $tools_on_loan_count }}</span>
                        <span class="text-secondary small fw-semibold">Alat dipinjam</span>
                    </div>
                    <div class="pt-2 border-top text-secondary extra-small mt-auto">
                        Peminjaman aktif di lapangan
                    </div>
                </div>
            </div>
        </div>

        {{-- Operational Data Sections --}}
        <div class="row g-4 mb-4">
            {{-- Left Column: Cluster Costs & Recent Activities --}}
            <div class="col-12 col-xl-8">
                {{-- Sebaran Biaya per Cluster --}}
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-body-tertiary mb-4">
                    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2 mb-3">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <div class="p-2 rounded-2 bg-success-subtle text-success d-inline-flex align-items-center justify-content-center">
                                    <svg width="15" height="15" fill="currentColor" aria-hidden="true"><use href="#i-cluster"/></svg>
                                </div>
                                <h5 class="fw-bold font-outfit text-body mb-0">Sebaran Biaya per Cluster</h5>
                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 extra-small font-mono">{{ $clusters_summary->count() }} Area</span>
                            </div>
                            <p class="text-secondary extra-small mb-0">Rekapitulasi biaya material proyek dan beban operasional sewa/vendor per cluster.</p>
                        </div>
                        <a href="{{ route('logistik.clusters') }}" wire:navigate class="btn btn-outline-secondary btn-sm rounded-3 align-self-start align-self-sm-auto">
                            Kelola Cluster
                        </a>
                    </div>

                    <div class="table-responsive rounded-3 border">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-body-secondary border-bottom">
                                <tr class="text-secondary extra-small text-uppercase font-geist tracking-wider">
                                    <th class="py-3 px-3">Cluster</th>
                                    <th class="py-3 px-2 text-center">Unit</th>
                                    <th class="py-3 px-3 text-end">Material</th>
                                    <th class="py-3 px-3 text-end">Operasional</th>
                                    <th class="py-3 px-3 text-end">Total Biaya</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                @forelse($clusters_summary as $cluster)
                                    <tr>
                                        <td class="py-3 px-3">
                                            <a href="{{ route('clusters.expenses', $cluster) }}" wire:navigate class="fw-bold text-success text-decoration-none small d-inline-flex align-items-center gap-1">
                                                <span>{{ $cluster->name }}</span>
                                                <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793L9.146 3.354a.5.5 0 1 1 .708-.708l5 5a.5.5 0 0 1 0 .708l-5 5a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8"/></svg>
                                            </a>
                                            @if($cluster->description)
                                                <div class="extra-small text-secondary">{{ \Illuminate\Support\Str::limit($cluster->description, 36) }}</div>
                                            @endif
                                        </td>
                                        <td class="py-3 px-2 text-center">
                                            <span class="badge bg-body border text-body font-mono px-2 py-1 rounded-2">{{ $cluster->houses_count }}</span>
                                        </td>
                                        <td class="py-3 px-3 text-end font-mono small text-secondary">
                                            Rp {{ number_format($cluster->material_cost, 0, ',', '.') }}
                                        </td>
                                        <td class="py-3 px-3 text-end font-mono small text-secondary">
                                            Rp {{ number_format($cluster->expense_cost, 0, ',', '.') }}
                                        </td>
                                        <td class="py-3 px-3 text-end font-mono small fw-bold text-body">
                                            Rp {{ number_format($cluster->total_cost, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-4 text-center text-secondary extra-small">Belum ada cluster terdaftar.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Aktivitas Pengeluaran Material Terkini --}}
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-body-tertiary">
                    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2 mb-3">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <div class="p-2 rounded-2 bg-success-subtle text-success d-inline-flex align-items-center justify-content-center">
                                    <svg width="15" height="15" fill="currentColor" aria-hidden="true"><use href="#i-journal-text"/></svg>
                                </div>
                                <h5 class="fw-bold font-outfit text-body mb-0">Aktivitas Material Terkini</h5>
                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 extra-small font-mono">{{ $recent_activities->count() }} Records</span>
                            </div>
                            <p class="text-secondary extra-small mb-0">Catatan alokasi material keluar ke unit rumah proyek.</p>
                        </div>
                        <a href="{{ route('logistik.material-log') }}" wire:navigate class="btn btn-outline-secondary btn-sm rounded-3 align-self-start align-self-sm-auto">
                            Catatan Lengkap
                        </a>
                    </div>

                    <div class="table-responsive rounded-3 border overflow-hidden">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-body-secondary border-bottom">
                                <tr class="text-secondary extra-small text-uppercase font-geist tracking-wider">
                                    <th class="py-3 px-3">Waktu</th>
                                    <th class="py-3 px-3">Unit Rumah</th>
                                    <th class="py-3 px-3">Item Material</th>
                                    <th class="py-3 px-3">Penerima</th>
                                    <th class="py-3 px-3 text-end">Jumlah & Biaya</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                @forelse($recent_activities as $act)
                                    <tr>
                                        <td class="py-3 px-3">
                                            <div class="fw-bold font-mono text-body small">{{ $act->created_at->diffForHumans() }}</div>
                                            <div class="extra-small text-secondary">{{ $act->created_at->format('d/m/Y H:i') }}</div>
                                        </td>
                                        <td class="py-3 px-3">
                                            <span class="badge bg-body border text-body font-mono px-2.5 py-1.5 rounded-2">
                                                {{ $act->house ? ($act->house->house_code ?: $act->house->name) : 'Umum' }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-3">
                                            <div class="fw-semibold text-body small">{{ $act->material->name ?? '-' }}</div>
                                            @if($act->is_warranty)
                                                <span class="badge bg-warning-subtle text-warning extra-small">Garansi</span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-3">
                                            <div class="small text-body">{{ $act->taken_by ?: ($act->user->name ?? '-') }}</div>
                                            <div class="extra-small text-secondary">Mandor / PJ</div>
                                        </td>
                                        <td class="py-3 px-3 text-end">
                                            <div class="font-mono fw-bold text-success small">
                                                {{ str_replace('.', ',', (float) $act->quantity) }} <span class="extra-small text-secondary fw-normal">{{ $act->material->unit ?? '' }}</span>
                                            </div>
                                            <div class="extra-small text-secondary font-mono">
                                                Rp {{ number_format($act->total_cost, 0, ',', '.') }}
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-4 text-center text-secondary extra-small">Belum ada alokasi material terdata.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Kolom Kanan: Stok Kritis, Alat Dipinjam & Akses Cepat --}}
            <div class="col-12 col-xl-4">
                {{-- Radar Stok Kritis --}}
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-body-tertiary mb-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="p-2 rounded-2 bg-danger-subtle text-danger d-inline-flex align-items-center justify-content-center">
                                <svg width="15" height="15" fill="currentColor" aria-hidden="true"><use href="#i-box"/></svg>
                            </div>
                            <h6 class="fw-bold font-outfit text-body mb-0">Radar Stok Kritis</h6>
                        </div>
                        <span class="badge {{ $low_stock_count > 0 ? 'bg-danger-subtle text-danger border border-danger-subtle' : 'bg-success-subtle text-success border border-success-subtle' }} rounded-pill px-2.5 py-1 extra-small font-mono">
                            {{ $low_stock_count }} Item
                        </span>
                    </div>

                    <div class="d-flex flex-column gap-2 mb-3">
                        @forelse($low_stock_materials as $mat)
                            <div class="p-2 rounded-3 border bg-body d-flex align-items-center justify-content-between gap-2">
                                <div class="text-truncate">
                                    <div class="small fw-semibold text-body text-truncate">{{ $mat->name }}</div>
                                    <div class="extra-small text-secondary text-truncate">{{ $mat->warehouse->name ?? 'Gudang' }}</div>
                                </div>
                                <div class="text-end flex-shrink-0">
                                    <span class="badge {{ $mat->stock <= 0 ? 'bg-danger text-white' : 'bg-warning-subtle text-warning border border-warning-subtle' }} font-mono px-2 py-1 rounded-2">
                                        {{ str_replace('.', ',', (float) $mat->stock) }} {{ $mat->unit }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="p-3 rounded-3 border bg-body text-center text-secondary extra-small">
                                Persediaan material aman di atas batas minimum.
                            </div>
                        @endforelse
                    </div>

                    <a href="{{ route('logistik.materials') }}" wire:navigate class="btn btn-outline-secondary btn-sm w-100 rounded-3 d-inline-flex align-items-center justify-content-center gap-1">
                        <span>Kelola Inventaris</span>
                        <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793L9.146 3.354a.5.5 0 1 1 .708-.708l5 5a.5.5 0 0 1 0 .708l-5 5a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8"/></svg>
                    </a>
                </div>

                {{-- Alat Sedang Dipinjam --}}
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-body-tertiary mb-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="p-2 rounded-2 bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center">
                                <svg width="15" height="15" fill="currentColor" aria-hidden="true"><use href="#i-wrench"/></svg>
                            </div>
                            <h6 class="fw-bold font-outfit text-body mb-0">Alat di Lapangan</h6>
                        </div>
                        <span class="badge bg-body border text-body rounded-pill px-2.5 py-1 extra-small font-mono">
                            {{ $tools_on_loan_count }} Unit
                        </span>
                    </div>

                    <div class="d-flex flex-column gap-2 mb-3">
                        @forelse($recent_tool_loans as $loan)
                            <div class="p-2 rounded-3 border bg-body d-flex align-items-center justify-content-between gap-2">
                                <div class="text-truncate">
                                    <div class="small fw-semibold text-body text-truncate">{{ $loan->tool->name ?? 'Alat' }}</div>
                                    <div class="extra-small text-secondary font-mono">{{ $loan->tool->code ?? '-' }}</div>
                                </div>
                                <div class="text-end flex-shrink-0">
                                    <span class="badge bg-body border text-body font-mono px-2 py-1 rounded-2">
                                        {{ $loan->house ? ($loan->house->house_code ?: $loan->house->name) : 'Proyek' }}
                                    </span>
                                    <div class="extra-small text-secondary font-mono mt-1">
                                        {{ $loan->checkout_date ? \Carbon\Carbon::parse($loan->checkout_date)->format('d/m') : '-' }}
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="p-3 rounded-3 border bg-body text-center text-secondary extra-small">
                                Tidak ada alat proyek yang sedang dipinjam.
                            </div>
                        @endforelse
                    </div>

                    <a href="{{ route('logistik.tools') }}" wire:navigate class="btn btn-outline-secondary btn-sm w-100 rounded-3 d-inline-flex align-items-center justify-content-center gap-1">
                        <span>Lihat Semua Alat</span>
                        <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793L9.146 3.354a.5.5 0 1 1 .708-.708l5 5a.5.5 0 0 1 0 .708l-5 5a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8"/></svg>
                    </a>
                </div>

                {{-- Akses Cepat & Master Data --}}
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-body-tertiary">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="small fw-bold text-secondary text-uppercase tracking-wider">Akses Cepat & Master Data</span>
                        <div class="p-2 bg-body rounded text-success d-flex align-items-center justify-content-center">
                            <svg width="16" height="16" fill="currentColor" aria-hidden="true"><use href="#i-dashboard"/></svg>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-3 p-3 rounded-3 bg-body border mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <svg width="16" height="16" fill="currentColor" class="text-primary"><use href="#i-people"/></svg>
                            <div>
                                <div class="fw-bold font-mono text-body small">{{ $total_users }}</div>
                                <div class="extra-small text-secondary">Pengguna</div>
                            </div>
                        </div>
                        <div class="vr my-1"></div>
                        <div class="d-flex align-items-center gap-2">
                            <svg width="16" height="16" fill="currentColor" class="text-info"><use href="#i-truck"/></svg>
                            <div>
                                <div class="fw-bold font-mono text-body small">{{ $total_suppliers }}</div>
                                <div class="extra-small text-secondary">Supplier</div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-2">
                        @if (auth()->user()->role === 'admin')
                            <div class="col-6"><a href="{{ route('admin.users') }}" wire:navigate class="btn btn-outline-secondary btn-sm w-100 text-start py-2">Kelola User</a></div>
                        @endif
                        <div class="col-6"><a href="{{ route('logistik.houses') }}" wire:navigate class="btn btn-outline-secondary btn-sm w-100 text-start py-2">Unit Rumah</a></div>
                        <div class="col-6"><a href="{{ route('logistik.materials') }}" wire:navigate class="btn btn-outline-secondary btn-sm w-100 text-start py-2">Inventaris</a></div>
                        <div class="col-6"><a href="{{ route('admin.house-costs') }}" wire:navigate class="btn btn-outline-secondary btn-sm w-100 text-start py-2">Laporan Biaya</a></div>
                        <div class="col-6"><a href="{{ route('logistik.clusters') }}" wire:navigate class="btn btn-outline-secondary btn-sm w-100 text-start py-2">Cluster Proyek</a></div>
                        <div class="col-6"><a href="{{ route('logistik.suppliers') }}" wire:navigate class="btn btn-outline-secondary btn-sm w-100 text-start py-2">Data Supplier</a></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts::app.sidebar>

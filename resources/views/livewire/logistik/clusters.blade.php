<div>
    <div class="container-fluid p-0">
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-body-tertiary">
            <div class="card-body p-4 p-md-5 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <div>
                    <h1 class="display-5 fw-black text-body mb-2 font-outfit">{{ $isCostPage ? 'Biaya' : 'Cluster' }} <span class="text-success">{{ $isCostPage ? 'Cluster' : 'Rumah' }}</span></h1>
                    <p class="text-secondary mb-0 max-w-xl">{{ $isCostPage ? 'Pilih cluster untuk melihat dan mencatat biaya.' : 'Kelompokkan unit rumah berdasarkan area proyek atau tahap pembangunan.' }}</p>
                </div>
                @if (auth()->user()->role === 'admin' && ! $isCostPage)
                    <button type="button" wire:click="create" class="btn btn-success fw-semibold">+ Tambah Cluster</button>
                @endif
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @error('delete')
            <div class="alert alert-danger" role="alert">{{ $message }}</div>
        @enderror

        @if ($isCostPage)
            @php
                $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
                $monthNamesFull = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                $chartMaximum = (float) max($monthlyTotals ?: [0]);
                $chartPoints = [];
                foreach ($monthlyTotals as $monthNumber => $monthAmount) {
                    $x = 3 + (($monthNumber - 1) / 11 * 94);
                    $y = $chartMaximum > 0 ? 4 + (1 - ($monthAmount / $chartMaximum)) * 92 : 96;
                    $chartPoints[] = round($x * 10, 2).','.round($y, 2);
                }
                $formatChartAmount = static fn (float $amount): string => $amount >= 1_000_000
                    ? number_format($amount / 1_000_000, 1, ',', '.').' jt'
                    : ($amount >= 1_000
                        ? number_format($amount / 1_000, 1, ',', '.').' rb'
                        : number_format($amount, 0, ',', '.'));
            @endphp

            <div class="row g-4 mb-4 cluster-cost-overview-row">
                <div class="col-12">
                    <section class="card border shadow-sm rounded-4 p-4 bg-body-tertiary cluster-cost-growth-panel h-100" aria-labelledby="cluster-cost-growth-title">
                        <div class="cluster-cost-growth-heading">
                            <div>
                                <h2 id="cluster-cost-growth-title" class="h5 fw-bold mb-1 font-outfit">Tren biaya cluster</h2>
                                <p class="small text-secondary mb-0">Pengeluaran semua cluster per bulan · {{ $selectedYear }}</p>
                            </div>
                        </div>

                        @if ($chartMaximum > 0)
                            <div class="cluster-cost-growth-chart" role="list" aria-label="Total biaya cluster per bulan tahun {{ $selectedYear }}">
                                <div class="cluster-cost-growth-scale" aria-hidden="true">
                                    @foreach ([$chartMaximum, $chartMaximum / 2, 0] as $axisAmount)
                                        <span>Rp {{ $formatChartAmount((float) $axisAmount) }}</span>
                                    @endforeach
                                </div>
                                <div class="cluster-cost-growth-plot">
                                    <svg class="cluster-cost-growth-svg" viewBox="0 0 1000 100" preserveAspectRatio="none" aria-hidden="true" focusable="false">
                                        <line x1="0" y1="4" x2="1000" y2="4" class="cluster-cost-growth-gridline" />
                                        <line x1="0" y1="50" x2="1000" y2="50" class="cluster-cost-growth-gridline" />
                                        <line x1="0" y1="96" x2="1000" y2="96" class="cluster-cost-growth-gridline" />
                                        <polyline points="{{ implode(' ', $chartPoints) }}" class="cluster-cost-growth-line" />
                                    </svg>
                                    @foreach ($monthlyTotals as $monthNumber => $monthAmount)
                                        @php
                                            $pointX = 3 + (($monthNumber - 1) / 11 * 94);
                                            $pointY = $chartMaximum > 0 ? 4 + (1 - ($monthAmount / $chartMaximum)) * 92 : 96;
                                        @endphp
                                        <span class="cluster-cost-growth-point" style="left: {{ $pointX }}%; top: {{ $pointY }}%;" role="listitem" aria-label="{{ $monthNamesFull[$monthNumber - 1] }}: Rp {{ number_format($monthAmount, 0, ',', '.') }}" title="{{ $monthNamesFull[$monthNumber - 1] }}: Rp {{ number_format($monthAmount, 0, ',', '.') }}"></span>
                                    @endforeach
                                </div>
                                <div class="cluster-cost-growth-months" aria-hidden="true">
                                    @foreach ($monthNames as $monthName)
                                        <span>{{ $monthName }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <p class="cluster-cost-growth-empty mb-0" role="status">Belum ada biaya tercatat untuk tahun {{ $selectedYear }}.</p>
                        @endif
                    </section>
                </div>
                <div class="col-12">
                    <div class="card border-0 border-start border-4 border-warning shadow-sm rounded-4 p-4 bg-body-tertiary d-flex flex-column flex-sm-row align-items-sm-center justify-content-sm-between gap-2">
                        <span class="small fw-bold text-secondary text-uppercase tracking-wider mb-0">Total Biaya Cluster (Semua Cluster)</span>
                        <h2 class="fw-black text-warning font-mono mb-0">Rp {{ number_format($totalSpent, 0, ',', '.') }}</h2>
                    </div>
                </div>
            </div>

            <div class="card border shadow-sm rounded-4 p-4 mb-4 bg-body-tertiary house-cost-overview-panel">
                <div class="house-log-filters house-cost-period-filter" role="group" aria-label="Tahun rincian biaya cluster">
                    <span class="small text-secondary">Tampilkan rincian biaya</span>
                    <select id="cluster-cost-filter-year" aria-label="Filter tahun biaya" wire:model.live="filterYear" class="form-select font-mono fw-bold house-cost-year-select">
                        @foreach ($years as $year)
                            <option value="{{ $year }}">Tahun {{ $year }}</option>
                        @endforeach
                    </select>
                    @if ($search || (int) $filterYear !== now()->year)
                        <button type="button" wire:click="resetFilters" class="btn btn-link text-secondary text-decoration-none btn-sm d-inline-flex align-items-center gap-1"><svg width="13" height="13" fill="currentColor" aria-hidden="true"><use href="#i-x"/></svg> Reset Filter</button>
                    @endif
                </div>

                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3 house-cost-table-heading">
                    <h2 class="h5 fw-bold mb-0 font-outfit">Rincian biaya cluster</h2>
                    <div class="flex-grow-1 house-cost-search">
                        <input type="search" wire:model.live.debounce.300ms="search" aria-label="Cari cluster atau rumah" placeholder="Cari cluster atau rumah..." class="form-control" />
                    </div>
                </div>

                <div class="data-table-card">
                <div wire:loading.delay class="data-table-status" role="status">Memuat biaya cluster...</div>
                <div wire:offline class="data-table-status is-error" role="alert">Koneksi terputus. Data mungkin tidak terbaru.</div>
                <div class="table-responsive data-table-scroll house-cost-table-scroll standard-table-frame" tabindex="0" role="region" aria-label="Rincian biaya cluster">
                    <table class="table table-hover align-middle mb-0 text-nowrap data-table data-table--sticky-identity house-cost-table cluster-cost-table standard-data-table">
                        <thead class="text-uppercase small font-geist border-bottom">
                            <tr class="bg-body-tertiary">
                                <th class="text-center text-secondary py-3" style="width: 45px;">No.</th>
                                <x-sortable-th field="name" :sort="$sort" class="text-secondary py-3 data-key-name" style="width: 10rem; min-width: 10rem; max-width: 10rem;">Nama Cluster</x-sortable-th>
                                <x-sortable-th field="houses" :sort="$sort" class="text-secondary py-3 cluster-cost-house-column" style="width: 9.5rem; min-width: 9.5rem; max-width: 9.5rem;">Rumah</x-sortable-th>
                                <x-sortable-th field="description" :sort="$sort" class="text-secondary py-3" style="min-width: 16rem;">Catatan</x-sortable-th>
                                @foreach ($monthNames as $monthIndex => $monthName)
                                    <x-sortable-th :field="'month_' . ($monthIndex + 1)" :sort="$sort" class="text-end text-secondary font-mono py-3 data-number" style="min-width: 90px;">{{ $monthName }}</x-sortable-th>
                                @endforeach
                                <x-sortable-th field="year_total" :sort="$sort" class="text-end text-success font-mono fw-bold py-3 data-number" style="min-width: 125px;">Total {{ $selectedYear }}</x-sortable-th>
                                <x-sortable-th field="all_time" :sort="$sort" class="text-end text-warning font-mono fw-bold py-3 data-number" style="min-width: 130px;">Total Keseluruhan</x-sortable-th>
                                <th class="text-end text-secondary py-3" style="width: 80px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($clusters as $cluster)
                                <tr wire:key="cluster-cost-{{ $cluster->id }}">
                                    <td data-label="No." class="text-center text-secondary small">{{ $clusters->firstItem() + $loop->index }}</td>
                                    <td data-label="Nama cluster" class="fw-bold text-body data-key-name" style="width: 10rem; min-width: 10rem; max-width: 10rem;" title="{{ $cluster->name }}"><span class="house-cost-cell-value">{{ $cluster->name }}</span></td>
                                    <td data-label="Rumah" class="cluster-cost-house-column" style="width: 9.5rem; min-width: 9.5rem; max-width: 9.5rem;">
                                        <span class="fw-semibold">{{ $cluster->houses_count }} rumah</span>
                                        @if ($cluster->houses_count)
                                            <span class="d-block small text-secondary data-cell-truncate" title="{{ $cluster->houses->pluck('name')->join(', ') }}">
                                                {{ $cluster->houses->take(3)->pluck('name')->join(', ') }}
                                                @if ($cluster->houses_count > 3)
                                                    , +{{ $cluster->houses_count - 3 }} lainnya
                                                @endif
                                            </span>
                                        @else
                                            <span class="text-secondary">-</span>
                                        @endif
                                    </td>
                                    <td data-label="Catatan" class="text-secondary small" style="min-width: 16rem;">{{ $cluster->description ?: '-' }}</td>
                                    @for ($month = 1; $month <= 12; $month++)
                                        @php $monthCost = (float) ($cluster->{'month_'.$month.'_cost'} ?? 0); @endphp
                                        <td data-label="{{ $monthNames[$month - 1] }}" class="house-cost-month text-end font-mono data-number {{ $monthCost > 0 ? 'text-body font-semibold' : 'text-secondary text-opacity-50 is-zero-month' }}">{{ $monthCost > 0 ? number_format($monthCost, 0, ',', '.') : '-' }}</td>
                                    @endfor
                                    <td data-label="Total {{ $selectedYear }}" class="text-end font-mono fw-bold text-success bg-success-subtle bg-opacity-10 data-number">Rp {{ number_format($cluster->year_total, 0, ',', '.') }}</td>
                                    <td data-label="Total keseluruhan" class="text-end font-mono fw-bold text-warning bg-warning-subtle bg-opacity-10 data-number">Rp {{ number_format($cluster->all_time_total, 0, ',', '.') }}</td>
                                    <td data-label="Aksi" class="text-end"><a href="{{ route('clusters.expenses', ['cluster' => $cluster, 'from' => 'costs']) }}" wire:navigate class="btn log-row-action log-row-action--edit">Lihat</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="19" class="house-cost-empty text-center py-5 text-secondary">Belum ada data biaya cluster.</td></tr>
                            @endforelse
                        </tbody>
                        @if ($clusters->isNotEmpty())
                            <tfoot class="border-top fw-bold font-geist bg-body-tertiary">
                                <tr class="align-middle">
                                    <td colspan="3" class="text-end text-uppercase text-secondary small py-3 cluster-cost-total-label">Total Biaya Cluster ({{ $selectedYear }}):</td>
                                    <td class="cluster-cost-total-spacer"></td>
                                    @php $yearTotal = 0; @endphp
                                    @foreach ($monthNames as $monthIndex => $monthName)
                                        @php $monthTotal = $monthlyTotals[$monthIndex + 1] ?? 0; $yearTotal += $monthTotal; @endphp
                                        <td class="text-end font-mono py-3 data-number {{ $monthTotal > 0 ? 'text-body' : 'text-secondary text-opacity-50' }}">{{ $monthTotal > 0 ? number_format($monthTotal, 0, ',', '.') : '-' }}</td>
                                    @endforeach
                                    <td class="text-end font-mono text-success fw-black py-3 bg-success-subtle bg-opacity-25 data-number">Rp {{ number_format($yearTotal, 0, ',', '.') }}</td>
                                    <td class="text-end font-mono text-warning fw-black py-3 bg-warning-subtle bg-opacity-25 data-number">Rp {{ number_format($totalSpent, 0, ',', '.') }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
                @php $mobileYearTotal = array_sum($monthlyTotals); @endphp
                <div class="house-cost-mobile-total d-none" aria-label="Ringkasan biaya semua cluster">
                    <span>Total {{ $selectedYear }}</span>
                    <strong class="font-mono text-success">Rp {{ number_format($mobileYearTotal, 0, ',', '.') }}</strong>
                    <span>Total keseluruhan</span>
                    <strong class="font-mono text-warning">Rp {{ number_format($totalSpent, 0, ',', '.') }}</strong>
                </div>
            </div>
            </div>
            <div class="d-flex justify-content-end">{{ $clusters->links('vendor.livewire.bootstrap') }}</div>
        @else
        <div class="card border shadow-sm rounded-4 mb-4 p-3 p-md-4 bg-body-tertiary standard-table-panel">
            <div class="standard-table-toolbar">
                <h2 class="h5 fw-bold mb-0 font-outfit">Daftar cluster</h2>
                <div class="standard-table-toolbar-controls">
                    <input type="search" wire:model.live.debounce.300ms="search" class="form-control standard-table-toolbar-search" placeholder="Cari nama cluster..." aria-label="Cari cluster" />
                </div>
            </div>
            <div class="table-responsive management-table-scroll standard-table-frame" tabindex="0" role="region" aria-label="Daftar cluster">
                <table class="table table-hover align-middle mb-0 management-card-table standard-data-table">
                    <thead class="table-light text-uppercase small font-geist">
                        <tr>
                            <th class="text-center" style="width: 60px;">No.</th>
                            <x-sortable-th field="name" :sort="$sort">Nama Cluster</x-sortable-th>
                            <x-sortable-th field="houses" :sort="$sort">Rumah</x-sortable-th>
                            <x-sortable-th field="description" :sort="$sort">Catatan</x-sortable-th>
                            <th class="text-end" style="width: 150px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($clusters as $cluster)
                            <tr wire:key="cluster-{{ $cluster->id }}">
                                <td data-label="No." class="text-center text-secondary small"><span class="management-cell-value">{{ $clusters->firstItem() + $loop->index }}</span></td>
                                <td data-label="Nama cluster" class="fw-bold text-body data-key-name" title="{{ $cluster->name }}"><span class="management-cell-value">{{ $cluster->name }}</span></td>
                                <td data-label="Rumah"><span class="management-cell-value"><span class="fw-semibold">{{ $cluster->houses_count }} rumah</span>@if ($cluster->houses_count)<span class="d-block small text-secondary data-cell-truncate" title="{{ $cluster->houses->pluck('name')->join(', ') }}">{{ $cluster->houses->take(3)->pluck('name')->join(', ') }}@if ($cluster->houses_count > 3)<span>, +{{ $cluster->houses_count - 3 }} lainnya</span>@endif</span>@endif</span></td>
                                <td data-label="Catatan" class="text-secondary small data-cell-truncate" title="{{ $cluster->description ?: '-' }}"><span class="management-cell-value">{{ $cluster->description ?: '-' }}</span></td>
                            <td data-label="Aksi" class="text-end"><span class="management-cell-value"><span class="btn-group btn-group-sm data-row-actions">
                                        <a href="{{ route('clusters.expenses', $isCostPage ? ['cluster' => $cluster, 'from' => 'costs'] : $cluster) }}" wire:navigate class="btn log-row-action log-row-action--edit">{{ $isCostPage ? 'Lihat' : 'Biaya' }}</a>
                                        @if (auth()->user()->role === 'admin' && ! $isCostPage)
                                            <button type="button" wire:click="edit({{ $cluster->id }})" class="btn log-row-action log-row-action--quiet">Edit</button>
                                            <button type="button" wire:click="confirmDelete({{ $cluster->id }})" class="btn log-row-action log-row-action--danger">Hapus</button>
                                        @endif
                                    </span></span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="management-table-empty text-center py-5 text-secondary">Belum ada cluster. Tambahkan cluster pertama untuk mulai mengelompokkan rumah.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3">{{ $clusters->links('vendor.livewire.bootstrap') }}</div>
        @endif
    </div>

    @if ($showModal)
        @teleport('body')
            <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog" wire:keydown.escape.window="$set('showModal', false)">
                <div class="modal-dialog modal-dialog-centered modal-md">
                    <div class="modal-content border-0 shadow-lg rounded-4">
                        <div class="modal-header border-bottom">
                            <h5 class="modal-title font-outfit fw-bold">{{ $editMode ? 'Edit Cluster' : 'Tambah Cluster' }}</h5>
                            <button type="button" class="btn-close" wire:click="$set('showModal', false)" aria-label="Tutup"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="cluster-name">Nama Cluster</label>
                                <input id="cluster-name" type="text" wire:model="name" class="form-control" placeholder="Contoh: Cluster A" autofocus />
                                @error('name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div>
                                <label class="form-label fw-semibold" for="cluster-description">Catatan</label>
                                <textarea id="cluster-description" wire:model="description" class="form-control" rows="3" placeholder="Keterangan lokasi atau tahap proyek, opsional"></textarea>
                                @error('description') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="modal-footer border-top bg-body-tertiary rounded-bottom-4">
                            <button type="button" class="btn btn-secondary fw-semibold" wire:click="$set('showModal', false)">Batal</button>
                            <button type="button" class="btn btn-success fw-semibold" wire:click="save" wire:loading.attr="disabled" wire:target="save"><span wire:loading.remove wire:target="save">{{ $editMode ? 'Simpan Perubahan' : 'Simpan Cluster' }}</span><span wire:loading wire:target="save">Menyimpan cluster…</span></button>
                        </div>
                    </div>
                </div>
            </div>
        @endteleport
    @endif

    @if ($showConfirmation)
        @teleport('body')
            <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog" wire:keydown.escape.window="$set('showConfirmation', false)">
                <div class="modal-dialog modal-dialog-centered modal-sm">
                    <div class="modal-content border-0 shadow-lg rounded-4">
                        <div class="modal-header border-bottom"><h5 class="modal-title font-outfit fw-bold">Hapus Cluster?</h5></div>
                        <div class="modal-body"><p class="text-secondary mb-0">Cluster kosong ini akan dihapus.</p></div>
                        <div class="modal-footer border-top bg-body-tertiary rounded-bottom-4">
                            <button type="button" class="btn btn-secondary btn-sm" wire:click="$set('showConfirmation', false)">Batal</button>
                            <button type="button" class="btn btn-danger btn-sm" wire:click="delete" wire:loading.attr="disabled" wire:target="delete">Ya, Hapus</button>
                        </div>
                    </div>
                </div>
            </div>
        @endteleport
    @endif
</div>

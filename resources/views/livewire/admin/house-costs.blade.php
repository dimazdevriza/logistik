<div>
    <div class="container-fluid p-0">
        <!-- Hero Header -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-body-tertiary">
            <div class="card-body p-4 p-md-5 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <div>
                    <h1 class="display-5 fw-black text-body mb-2 font-outfit">
                        Monitoring Biaya <span class="text-success">Pembangunan Rumah</span>
                    </h1>
                    <p class="text-secondary mb-0 max-w-xl">
                        Monitor biaya pembangunan dan total alokasi material setiap unit rumah.
                    </p>
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
        <div class="row g-4 mb-4">
            <div class="col-12">
                <div class="card border-0 border-start border-4 border-warning shadow-sm rounded-4 p-4 bg-body-tertiary d-flex flex-column flex-sm-row align-items-sm-center justify-content-sm-between gap-2">
                    <span class="small fw-bold text-secondary text-uppercase tracking-wider mb-0">Total Biaya Material ({{ $scopeLabel }})</span>
                    <h2 class="fw-black text-warning font-mono mb-0">Rp {{ number_format($totalSpent, 0, ',', '.') }}</h2>
                </div>
            </div>
        </div>

        <!-- House cost ledger -->
        <div class="card border shadow-sm rounded-4 p-4 mb-4 bg-body-tertiary house-cost-overview-panel">
            @php $statusFilters = ['' => 'Semua Status', 'perencanaan' => 'Perencanaan', 'pembangunan' => 'Pembangunan', 'selesai' => 'Selesai']; @endphp
            <div class="house-log-period-tabs house-cost-status-tabs" role="group" aria-label="Filter status rumah">
                @foreach ($statusFilters as $statusValue => $statusLabel)
                    <button type="button" class="house-log-period-tab {{ $filterStatus === $statusValue ? 'active' : '' }}" aria-pressed="{{ $filterStatus === $statusValue ? 'true' : 'false' }}" wire:click="$set('filterStatus', '{{ $statusValue }}')">
                        {{ $statusLabel }}
                    </button>
                @endforeach
            </div>

            <div class="house-log-filters house-cost-period-filter" role="group" aria-label="Tahun rincian biaya">
                <span class="small text-secondary">Tampilkan rincian biaya</span>
                <select id="house-cost-filter-year" aria-label="Filter tahun biaya" wire:model.live="filterYear" class="form-select font-mono fw-bold house-cost-year-select">
                    @foreach ($years as $yr)
                        <option value="{{ $yr }}">Tahun {{ $yr }}</option>
                    @endforeach
                </select>
                @if ($search || $filterStatus || $filterYear != now()->year)
                    <button type="button" wire:click="resetFilters" class="btn btn-link text-secondary text-decoration-none btn-sm d-inline-flex align-items-center gap-1"><svg width="13" height="13" fill="currentColor" aria-hidden="true"><use href="#i-x"/></svg> Reset Filter</button>
                @endif
            </div>

            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3 house-cost-table-heading">
                <h2 class="h5 fw-bold mb-0 font-outfit">Rincian biaya rumah</h2>
                <div class="flex-grow-1 house-cost-search">
                    <input type="search" wire:model.live.debounce.300ms="search" aria-label="Cari rumah, kode, atau tipe" placeholder="Cari rumah, kode, atau tipe..." class="form-control" />
                </div>
            </div>

            <div class="data-table-card">
            <div wire:loading.delay class="data-table-status" role="status">Memuat biaya rumah...</div>
            <div wire:offline class="data-table-status is-error" role="alert">Koneksi terputus. Data mungkin tidak terbaru.</div>
            <div class="table-responsive data-table-scroll house-cost-table-scroll standard-table-frame" tabindex="0" role="region" aria-label="Rincian biaya rumah">
                <table class="table table-hover align-middle mb-0 text-nowrap data-table data-table--sticky-identity house-cost-table standard-data-table">
                    <thead class="text-uppercase small font-geist border-bottom">
                        <tr class="bg-body-tertiary">
                            <th class="text-center text-secondary py-3" style="width: 45px;">No.</th>
                            <x-sortable-th field="code" :sort="$sort" class="text-secondary py-3 data-key-code">Kode</x-sortable-th>
                            <x-sortable-th field="name" :sort="$sort" class="text-secondary py-3 data-key-name">Rumah</x-sortable-th>
                            <th class="text-secondary py-3">Cluster</th>
                            <x-sortable-th field="type" :sort="$sort" class="text-secondary py-3">Tipe</x-sortable-th>
                            <x-sortable-th field="status" :sort="$sort" class="text-secondary py-3 text-center">Status</x-sortable-th>
                            @php
                                $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
                            @endphp
                            @foreach ($monthNames as $mIdx => $mName)
                                <x-sortable-th :field="'month_' . ($mIdx + 1)" :sort="$sort" class="text-end text-secondary font-mono py-3 data-number" style="min-width: 90px;">{{ $mName }}</x-sortable-th>
                            @endforeach
                            <x-sortable-th field="year_total" :sort="$sort" class="text-end text-success font-mono fw-bold py-3 data-number" style="min-width: 125px;">Total {{ $selectedYear }}</x-sortable-th>
                            <x-sortable-th field="all_time" :sort="$sort" class="text-end text-warning font-mono fw-bold py-3 data-number" style="min-width: 130px;">Total Keseluruhan</x-sortable-th>
                            <th class="text-end text-secondary py-3" style="width: 80px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($houses as $house)
                        @php
                            $totalAllTime = $house->material_usages_sum_total_cost ?? 0;
                            $statusClasses = ['perencanaan' => 'bg-warning-subtle text-warning', 'pembangunan' => 'bg-primary-subtle text-primary', 'selesai' => 'bg-success-subtle text-success'];
                            
                            $yearSum = 0;
                            for ($m = 1; $m <= 12; $m++) {
                                $yearSum += (float) ($house->{'month_' . $m . '_cost'} ?? 0);
                            }
                        @endphp
                        <tr wire:key="h-cost-{{ $house->id }}" style="cursor: pointer;" x-on:click="if (!$event.target.closest('button') && !$event.target.closest('a')) { window.Livewire.navigate('{{ route(auth()->user()->role === 'admin' ? 'admin.house-costs.detail' : 'logistik.house-costs.detail', $house) }}') }">
                            <td data-label="No." class="text-center text-secondary small">{{ $houses->firstItem() + $loop->index }}</td>
                            <td data-label="Kode" class="font-mono text-secondary small data-key-code" title="{{ $house->house_code ?? 'Kode tidak tersedia' }}"><span class="house-cost-cell-value">{{ $house->house_code ?? '-' }}</span></td>
                            <td data-label="Rumah" class="fw-bold text-body data-key-name" title="{{ $house->name }}"><span class="house-cost-cell-value">{{ $house->name }}</span></td>
                            <td data-label="Cluster" class="text-secondary small"><span class="house-cost-cell-value">{{ $house->cluster?->name ?? 'Tanpa Cluster' }}</span></td>
                            <td data-label="Tipe" class="text-secondary small"><span class="house-cost-cell-value">{{ $house->type }}</span></td>
                            <td data-label="Status" class="text-center">
                                <span class="badge {{ $statusClasses[$house->status] ?? 'bg-secondary-subtle text-secondary' }}">{{ ucfirst($house->status) }}</span>
                            </td>
                            
                            @for ($m = 1; $m <= 12; $m++)
                                @php
                                    $mCost = (float) ($house->{'month_' . $m . '_cost'} ?? 0);
                                @endphp
                                <td data-label="{{ $monthNames[$m - 1] }}" class="house-cost-month text-end font-mono data-number {{ $mCost > 0 ? 'text-body font-semibold' : 'text-secondary text-opacity-50' }} {{ $mCost > 0 ? '' : 'is-zero-month' }}">
                                    {{ $mCost > 0 ? number_format($mCost, 0, ',', '.') : '-' }}
                                </td>
                            @endfor

                            <td data-label="Total {{ $selectedYear }}" class="text-end font-mono fw-bold text-success bg-success-subtle bg-opacity-10 data-number">
                                Rp {{ number_format($yearSum, 0, ',', '.') }}
                            </td>
                            <td data-label="Total keseluruhan" class="text-end font-mono fw-bold text-warning bg-warning-subtle bg-opacity-10 data-number">
                                Rp {{ number_format($totalAllTime, 0, ',', '.') }}
                            </td>
                            <td data-label="Aksi" class="text-end">
                                <a href="{{ route(auth()->user()->role === 'admin' ? 'admin.house-costs.detail' : 'logistik.house-costs.detail', $house) }}" wire:navigate class="btn log-row-action log-row-action--edit">Lihat</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="21" class="house-cost-empty text-center py-5 text-secondary">Belum ada data rumah.</td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if ($houses->isNotEmpty())
                    <tfoot class="border-top fw-bold font-geist bg-body-tertiary">
                        <tr class="align-middle">
                            <td colspan="6" class="text-end text-uppercase text-secondary small py-3">Total {{ in_array(auth()->user()->role, ['admin', 'keuangan'], true) ? 'Proyek' : 'Cluster' }} ({{ $selectedYear }}):</td>
                            @php
                                $grandYearTotal = 0;
                            @endphp
                            @for ($m = 1; $m <= 12; $m++)
                                @php
                                    $totMonth = $monthlyTotals[$m] ?? 0;
                                    $grandYearTotal += $totMonth;
                                @endphp
                                <td class="text-end font-mono py-3 data-number {{ $totMonth > 0 ? 'text-body' : 'text-secondary text-opacity-50' }}">
                                    {{ $totMonth > 0 ? number_format($totMonth, 0, ',', '.') : '-' }}
                                </td>
                            @endfor
                            <td class="text-end font-mono text-success fw-black py-3 bg-success-subtle bg-opacity-25 data-number">
                                Rp {{ number_format($grandYearTotal, 0, ',', '.') }}
                            </td>
                            <td class="text-end font-mono text-warning fw-black py-3 bg-warning-subtle bg-opacity-25 data-number">
                                Rp {{ number_format($totalSpent, 0, ',', '.') }}
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
            @php
                $mobileYearTotal = 0;
                foreach ($monthlyTotals as $monthTotal) {
                    $mobileYearTotal += (float) $monthTotal;
                }
            @endphp
            <div class="house-cost-mobile-total d-none" aria-label="Ringkasan biaya {{ in_array(auth()->user()->role, ['admin', 'keuangan'], true) ? 'proyek' : 'cluster' }}">
                <span>Total {{ $selectedYear }}</span>
                <strong class="font-mono text-success">Rp {{ number_format($mobileYearTotal, 0, ',', '.') }}</strong>
                <span>Total keseluruhan</span>
                <strong class="font-mono text-warning">Rp {{ number_format($totalSpent, 0, ',', '.') }}</strong>
            </div>
            </div>
        </div>

        <div class="d-flex justify-content-end">{{ $houses->links() }}</div>
    </div>
</div>

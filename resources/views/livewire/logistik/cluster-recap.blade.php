<div class="card border shadow-sm rounded-4 overflow-hidden mb-4 bg-body-tertiary">
    <div class="p-4 border-bottom">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
            <h2 class="h5 fw-bold mb-0 font-outfit">Rekap pengeluaran per rumah</h2>
            <div class="d-flex align-items-center gap-2">
                <div wire:loading.delay class="small text-secondary font-mono" role="status">Memuat...</div>
                <button type="button" wire:click="exportExcel" class="btn btn-utility fw-semibold d-inline-flex align-items-center gap-2">
                    <svg width="15" height="15" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                        <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5"/>
                        <path d="M7.646 1.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 2.707V10.5a.5.5 0 0 1-1 0V2.707L5.354 4.854a.5.5 0 1 1-.708-.708z"/>
                    </svg>
                    <span>Ekspor Rekap</span>
                </button>
            </div>
        </div>
        <div class="row g-3 align-items-end">
            @if ($clusters->count() > 1)
                <div class="col-12 col-sm-6 col-md-4 col-xl-3">
                    <label for="recap-cluster" class="form-label small fw-semibold text-secondary mb-1">Cluster</label>
                    <select id="recap-cluster" wire:model.live="clusterId" class="form-select fw-bold">
                        @foreach ($clusters as $cl)
                            <option value="{{ $cl->id }}">{{ $cl->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="col-12 col-sm-6 col-md-4 col-xl-3">
                <label for="recap-year" class="form-label small fw-semibold text-secondary mb-1">Periode</label>
                <select id="recap-year" wire:model.live="filterYear" class="form-select font-mono fw-bold">
                    @foreach ($years as $yr)
                        <option value="{{ $yr }}">Tahun {{ $yr }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-4">
                <label for="recap-search" class="form-label small fw-semibold text-secondary mb-1">Cari blok atau mandor</label>
                <input id="recap-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Blok A1, nama mandor..." class="form-control">
            </div>
            @if ($search || $filterYear != now()->year)
                <div class="col-12 col-sm-auto ms-auto">
                    <button type="button" wire:click="resetFilters" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1">
                        <svg width="14" height="14" fill="currentColor" aria-hidden="true"><use href="#i-x"/></svg>
                        <span>Reset</span>
                    </button>
                </div>
            @endif
        </div>
    </div>

    <div class="p-3 px-md-4 border-bottom text-center matrix-report-banner">
        <h3 class="h6 fw-black text-success font-outfit text-uppercase mb-1">
            Rekapitulasi total pengeluaran cluster {{ $selectedCluster?->name ?? '-' }}
        </h3>
        <div class="small text-secondary font-mono">SUMBER: BARANG KELUAR (MATERIAL LOG) &bull; PERIODE {{ $selectedYear }}</div>
    </div>

    <div class="table-responsive" tabindex="0" role="region" aria-label="Tabel rekapitulasi pengeluaran cluster">
        <table class="table table-bordered table-hover table-sm align-middle mb-0 text-nowrap matrix-report-table">
            <thead class="text-uppercase small font-geist">
                <tr class="text-center">
                    <th rowspan="2" class="text-secondary" style="width: 45px;">No</th>
                    <th rowspan="2" class="text-secondary text-start" style="min-width: 140px;">Mandor</th>
                    <th rowspan="2" class="text-secondary text-start" style="min-width: 110px;">Blok rumah</th>
                    <th colspan="2" class="text-success matrix-head-progress">Bobot kerja</th>
                    <th colspan="12" class="text-warning text-start ps-3 matrix-head-period">Periode {{ $selectedYear }}</th>
                    <th rowspan="2" class="text-success text-end matrix-head-total" style="min-width: 130px;">Total akhir</th>
                </tr>
                <tr class="text-center text-secondary">
                    <th class="matrix-head-progress-sub" style="width: 75px;">Rencana</th>
                    <th class="matrix-head-progress-sub" style="width: 75px;">Realisasi</th>
                    @foreach ($monthNames as $mName)
                        <th class="font-mono" style="min-width: 95px;">{{ $mName }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($matrixHouses as $house)
                    @php
                        $progress = (float) ($house->physical_progress ?? 0);
                        $yearTotal = (float) ($house->year_total ?? 0);
                    @endphp
                    <tr wire:key="recap-{{ $house->id }}">
                        <td class="text-center font-mono text-secondary">{{ $loop->iteration }}</td>
                        <td class="fw-semibold text-body text-truncate" title="{{ $house->mandor_name ?: '-' }}">{{ $house->mandor_name ?: '-' }}</td>
                        <td class="fw-bold font-outfit text-body">
                            {{ $house->name }}
                            @if ($house->house_code)
                                <span class="small text-secondary font-mono fw-normal">({{ $house->house_code }})</span>
                            @endif
                        </td>
                        <td class="text-center font-mono text-secondary">100%</td>
                        <td class="text-center font-mono fw-bold {{ $progress >= 100 ? 'text-success' : ($progress > 0 ? 'text-info' : 'text-secondary') }}">
                            {{ $progress > 0 ? number_format($progress, 1, ',', '.').'%' : '-' }}
                        </td>
                        @for ($m = 1; $m <= 12; $m++)
                            @php $cost = (float) ($house->{'month_'.$m.'_cost'} ?? 0); @endphp
                            <td class="text-end font-mono {{ $cost > 0 ? 'text-body fw-semibold' : 'text-secondary opacity-50' }}">
                                {{ $cost > 0 ? 'Rp '.number_format($cost, 0, ',', '.') : '-' }}
                            </td>
                        @endfor
                        <td class="text-end font-mono fw-bold text-success matrix-cell-total">
                            {{ $yearTotal > 0 ? 'Rp '.number_format($yearTotal, 0, ',', '.') : '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="18" class="text-center text-secondary py-4 small">Tidak ada unit rumah pada cluster atau pencarian ini.</td>
                    </tr>
                @endforelse

                <tr class="table-active border-top border-2">
                    <td class="text-center font-mono text-secondary fw-bold">{{ $matrixHouses->count() + 1 }}</td>
                    <td class="fw-semibold text-body small text-truncate" title="{{ $saranaMandors }}">{{ $saranaMandors }}</td>
                    <td class="fw-bold font-outfit text-warning">SARANA LAINNYA</td>
                    <td class="text-center font-mono text-secondary">-</td>
                    <td class="text-center font-mono text-secondary">-</td>
                    @for ($m = 1; $m <= 12; $m++)
                        @php $sCost = (float) ($saranaMonthCosts[$m] ?? 0); @endphp
                        <td class="text-end font-mono {{ $sCost > 0 ? 'text-warning fw-semibold' : 'text-secondary opacity-50' }}">
                            {{ $sCost > 0 ? 'Rp '.number_format($sCost, 0, ',', '.') : '-' }}
                        </td>
                    @endfor
                    <td class="text-end font-mono fw-bold text-warning">
                        {{ $saranaYearTotal > 0 ? 'Rp '.number_format($saranaYearTotal, 0, ',', '.') : '-' }}
                    </td>
                </tr>
            </tbody>
            <tfoot class="border-top border-2 matrix-report-foot">
                <tr class="fw-black">
                    <td colspan="5" class="text-uppercase font-outfit text-success text-center">JUMLAH RINCIAN PERBULAN</td>
                    @for ($m = 1; $m <= 12; $m++)
                        @php $mGrand = (float) ($monthlyGrandTotals[$m] ?? 0); @endphp
                        <td class="text-end font-mono {{ $mGrand > 0 ? 'text-success' : 'text-secondary' }}">
                            {{ $mGrand > 0 ? 'Rp '.number_format($mGrand, 0, ',', '.') : '-' }}
                        </td>
                    @endfor
                    <td class="text-end font-mono text-success matrix-cell-grand">Rp {{ number_format($clusterYearGrandTotal, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

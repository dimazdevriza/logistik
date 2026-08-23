<div>
    <div class="container-fluid p-0">
        <!-- Hero Header -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-body-tertiary">
            <div class="card-body p-4 p-md-5 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 text-uppercase mb-2 font-geist small">Laporan Finansial</span>
                    <h1 class="display-5 fw-black text-body mb-2 font-outfit">
                        Monitoring Biaya <span class="text-success">Pembangunan Rumah</span>
                    </h1>
                    <p class="text-secondary mb-0 max-w-xl">
                        Monitor biaya pembangunan dan total alokasi material setiap unit rumah.
                    </p>
                </div>
                <div>
                    <button type="button" wire:click="exportExcel" class="btn btn-outline-success font-semibold">Export Excel</button>
                </div>
            </div>
        </div>

        <!-- Summary Card -->
        <div class="row g-4 mb-4">
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 border-start border-4 border-warning shadow-sm rounded-4 p-4 bg-body-tertiary">
                    <span class="small fw-bold text-secondary text-uppercase tracking-wider mb-2 d-block">Total Biaya Material (Semua Rumah)</span>
                    <h2 class="fw-black text-warning font-mono mb-0">Rp {{ number_format($totalSpent, 0, ',', '.') }}</h2>
                </div>
            </div>
        </div>

        <!-- Search & Filter Controls -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 p-3 bg-body-tertiary">
            <div class="d-flex flex-column flex-md-row gap-3 align-items-md-center justify-content-between">
                <div class="d-flex flex-column flex-md-row gap-3 align-items-md-center flex-grow-1">
                    <div class="w-100 max-w-sm">
                        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari rumah, kode, atau tipe..." class="form-control" />
                    </div>
                    <div class="max-w-[170px]">
                        <select wire:model.live="filterStatus" class="form-select">
                            <option value="">Semua Status</option>
                            <option value="perencanaan">Perencanaan</option>
                            <option value="pembangunan">Pembangunan</option>
                            <option value="selesai">Selesai</option>
                        </select>
                    </div>
                    <div class="max-w-[140px]">
                        <select wire:model.live="filterYear" class="form-select font-mono fw-bold">
                            @foreach ($years as $yr)
                                <option value="{{ $yr }}">Tahun {{ $yr }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if ($search || $filterStatus || $filterYear != now()->year)
                        <button type="button" wire:click="resetFilters" class="btn btn-link text-secondary text-decoration-none btn-sm">✕ Reset Filter</button>
                    @endif
                </div>
                <div class="text-secondary small font-mono">
                    Menampilkan rincian biaya: <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold">Tahun {{ $selectedYear }}</span>
                </div>
            </div>
        </div>

        <!-- House Costs Table -->
        <div class="card border shadow-sm rounded-4 overflow-hidden mb-4 bg-body">
            <div class="table-responsive" style="max-height: 700px;">
                <table class="table table-hover align-middle mb-0 text-nowrap">
                    <thead class="text-uppercase small font-geist sticky-top border-bottom" style="z-index: 5;">
                        <tr class="bg-body-tertiary">
                            <th class="text-center text-secondary py-3" style="width: 45px;">No.</th>
                            <th class="text-secondary py-3">Kode</th>
                            <th class="text-secondary py-3">Rumah</th>
                            <th class="text-secondary py-3">Tipe</th>
                            <th class="text-secondary py-3 text-center">Status</th>
                            @php
                                $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
                            @endphp
                            @foreach ($monthNames as $mIdx => $mName)
                                <th class="text-end text-secondary font-mono py-3" style="min-width: 90px;">{{ $mName }}</th>
                            @endforeach
                            <th class="text-end text-success font-mono fw-bold py-3" style="min-width: 125px;">Total {{ $selectedYear }}</th>
                            <th class="text-end text-warning font-mono fw-bold py-3" style="min-width: 130px;">Total Keseluruhan</th>
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
                        <tr wire:key="h-cost-{{ $house->id }}" style="cursor: pointer;" x-on:click="if (!$event.target.closest('button') && !$event.target.closest('a')) { window.Livewire.navigate('{{ route('admin.house-costs.detail', $house) }}') }">
                            <td class="text-center text-secondary small">{{ $houses->firstItem() + $loop->index }}</td>
                            <td class="font-mono text-secondary small">{{ $house->house_code ?? '-' }}</td>
                            <td class="fw-bold text-body">{{ $house->name }}</td>
                            <td class="text-secondary small">{{ $house->type }}</td>
                            <td class="text-center">
                                <span class="badge {{ $statusClasses[$house->status] ?? 'bg-secondary-subtle text-secondary' }}">{{ ucfirst($house->status) }}</span>
                            </td>
                            
                            @for ($m = 1; $m <= 12; $m++)
                                @php
                                    $mCost = (float) ($house->{'month_' . $m . '_cost'} ?? 0);
                                @endphp
                                <td class="text-end font-mono {{ $mCost > 0 ? 'text-body font-semibold' : 'text-secondary text-opacity-50' }}">
                                    {{ $mCost > 0 ? number_format($mCost, 0, ',', '.') : '—' }}
                                </td>
                            @endfor

                            <td class="text-end font-mono fw-bold text-success bg-success-subtle bg-opacity-10">
                                Rp {{ number_format($yearSum, 0, ',', '.') }}
                            </td>
                            <td class="text-end font-mono fw-bold text-warning bg-warning-subtle bg-opacity-10">
                                Rp {{ number_format($totalAllTime, 0, ',', '.') }}
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.house-costs.detail', $house) }}" wire:navigate class="btn btn-outline-secondary btn-sm px-2 py-0.5 extra-small">Lihat</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="20" class="text-center py-5 text-secondary">Belum ada data rumah.</td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if ($houses->isNotEmpty())
                    <tfoot class="border-top fw-bold font-geist bg-body-tertiary">
                        <tr class="align-middle">
                            <td colspan="5" class="text-end text-uppercase text-secondary small py-3">Total Proyek ({{ $selectedYear }}):</td>
                            @php
                                $grandYearTotal = 0;
                            @endphp
                            @for ($m = 1; $m <= 12; $m++)
                                @php
                                    $totMonth = $monthlyTotals[$m] ?? 0;
                                    $grandYearTotal += $totMonth;
                                @endphp
                                <td class="text-end font-mono py-3 {{ $totMonth > 0 ? 'text-body' : 'text-secondary text-opacity-50' }}">
                                    {{ $totMonth > 0 ? number_format($totMonth, 0, ',', '.') : '—' }}
                                </td>
                            @endfor
                            <td class="text-end font-mono text-success fw-black py-3 bg-success-subtle bg-opacity-25">
                                Rp {{ number_format($grandYearTotal, 0, ',', '.') }}
                            </td>
                            <td class="text-end font-mono text-warning fw-black py-3 bg-warning-subtle bg-opacity-25">
                                Rp {{ number_format($totalSpent, 0, ',', '.') }}
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>

        <div class="d-flex justify-content-end">{{ $houses->links() }}</div>
    </div>
</div>

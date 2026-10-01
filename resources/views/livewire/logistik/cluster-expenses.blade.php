<div>
    <div class="container-fluid p-0 cluster-expenses-page">
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-body-tertiary">
            <div class="card-body p-4 p-md-5 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <div>
                    @if (in_array(auth()->user()->role, ['admin', 'keuangan'], true))
                    <a href="{{ route('logistik.clusters') }}" wire:navigate class="back-link mb-3">
                        <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 .708.708L2.707 8.5H14.5A.5.5 0 0 0 15 8"/></svg>
                        Kembali ke Cluster
                    </a>
                    @endif
                    <h1 class="display-5 fw-black text-body mb-2 font-outfit">{{ $cluster->name }}</h1>
                    <p class="text-secondary mb-0">Ringkasan biaya material rumah, sewa alat, dan jasa vendor dalam cluster.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" wire:click="exportExcel" class="btn btn-success fw-semibold text-nowrap">Ekspor Excel</button>
                </div>
            </div>
        </div>

        @if (session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
        @error('parent_expense_id') <div class="alert alert-warning" role="alert">{{ $message }}</div> @enderror

        <div class="row g-3 mb-3">
            <div class="col-12 col-md-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-body-tertiary h-100">
                    <div class="small text-secondary text-uppercase fw-bold mb-2">Total Biaya Rumah</div>
                    <div class="fs-4 fw-black font-mono text-success">Rp {{ number_format($houseSubtotal, 0, ',', '.') }}</div>
                    <div class="small text-secondary mt-2">Material yang sudah dipakai unit</div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-body-tertiary h-100">
                    <div class="small text-secondary text-uppercase fw-bold mb-2">Total Sewa Jasa Vendor</div>
                    <div class="fs-4 fw-black font-mono text-success">Rp {{ number_format($vendorServiceSubtotal, 0, ',', '.') }}</div>
                    <div class="small text-secondary mt-2">Pekerjaan jasa vendor di cluster</div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-body-tertiary h-100">
                    <div class="small text-secondary text-uppercase fw-bold mb-2">Total Sewa Alat Vendor</div>
                    <div class="fs-4 fw-black font-mono text-success">Rp {{ number_format($vendorRentalSubtotal, 0, ',', '.') }}</div>
                    <div class="small text-secondary mt-2">Penyewaan alat di cluster</div>
                </div>
            </div>
        </div>
        <div class="card border-0 border-start border-4 border-success shadow-sm rounded-4 p-4 mb-4 bg-body-tertiary">
            <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2">
                <div>
                    <div class="small text-secondary text-uppercase fw-bold">Total Keseluruhan</div>
                    <div class="small text-secondary mt-1">Biaya rumah + jasa vendor + sewa alat vendor</div>
                </div>
                <div class="fs-3 fw-black font-mono text-success">Rp {{ number_format($clusterTotal, 0, ',', '.') }}</div>
            </div>
        </div>

        <fieldset class="cluster-expense-table-switch card border-0 shadow-sm rounded-4 p-4 bg-body-tertiary mb-4">
            <legend class="visually-hidden">Pilih jenis biaya cluster</legend>
            <span class="small text-secondary">Tampilkan</span>
            <input type="radio" class="btn-check" name="cluster-expense-table" id="cluster-expense-show-houses" autocomplete="off" checked>
            <label class="house-log-filter-button" for="cluster-expense-show-houses">
                <svg width="16" height="16" fill="currentColor" aria-hidden="true"><use href="#i-houses"/></svg>
                <span>Rumah dalam cluster</span>
                <span class="house-log-filter-count">{{ $houses->total() }}</span>
            </label>
            <input type="radio" class="btn-check" name="cluster-expense-table" id="cluster-expense-show-rentals" autocomplete="off">
            <label class="house-log-filter-button" for="cluster-expense-show-rentals">
                <svg width="16" height="16" fill="currentColor" aria-hidden="true"><use href="#i-truck"/></svg>
                <span>Sewa alat vendor</span>
                <span class="house-log-filter-count">{{ $rentalRows->count() }}</span>
            </label>
            <input type="radio" class="btn-check" name="cluster-expense-table" id="cluster-expense-show-vendor-services" autocomplete="off">
            <label class="house-log-filter-button" for="cluster-expense-show-vendor-services">
                <svg width="16" height="16" fill="currentColor" aria-hidden="true"><use href="#i-journal-text"/></svg>
                <span>Jasa vendor</span>
                <span class="house-log-filter-count">{{ $vendorServiceRows->count() }}</span>
            </label>

            <div class="cluster-expense-table-panels">
                <div class="cluster-house-table-panel">
                    <div class="cluster-house-panel-heading">
                        <h2 class="h4 fw-bold mb-0 font-outfit">Rincian biaya rumah</h2>
                        <div class="cluster-house-search">
                            <input type="search" wire:model.live.debounce.300ms="search" aria-label="Cari rumah, kode, atau tipe" placeholder="Cari rumah, kode, atau tipe..." class="form-control" autocomplete="off" />
                        </div>
                    </div>
                    <div class="cluster-house-filter-panel">
                        <div class="cluster-house-filter-controls">
                                    <select id="cluster-house-filter-status" aria-label="Filter status rumah" wire:model.live="filterStatus" class="form-select">
                                        <option value="">Semua Status</option>
                                        <option value="perencanaan">Perencanaan</option>
                                        <option value="pembangunan">Pembangunan</option>
                                        <option value="selesai">Selesai</option>
                                    </select>
                                    <select id="cluster-house-filter-year" aria-label="Filter tahun biaya" wire:model.live="filterYear" class="form-select font-mono fw-bold">
                                        @foreach ($years as $year)
                                            <option value="{{ $year }}">Tahun {{ $year }}</option>
                                        @endforeach
                                    </select>
                                    @if ($search || $filterStatus || (int) $filterYear !== now()->year)
                                        <button type="button" wire:click="resetFilters" class="btn btn-link text-secondary text-decoration-none btn-sm text-nowrap">Reset Filter</button>
                                    @endif
                        </div>
                            <div class="text-secondary small font-mono">
                                Menampilkan rincian biaya: <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold">Tahun {{ $selectedYear }}</span>
                            </div>
                    </div>

                    <div class="cluster-house-table-surface data-table-card mb-3">
                        <div wire:loading.delay class="data-table-status" role="status">Memuat biaya rumah...</div>
                        <div wire:offline class="data-table-status is-error" role="alert">Koneksi terputus. Data mungkin tidak terbaru.</div>
                        <div class="table-responsive data-table-scroll house-cost-table-scroll" tabindex="0" role="region" aria-label="Rincian biaya rumah dalam cluster">
                            <table class="table table-hover align-middle mb-0 text-nowrap data-table data-table--sticky-identity house-cost-table">
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
                                        @foreach ($monthNames as $monthIndex => $monthName)
                                            <x-sortable-th :field="'month_' . ($monthIndex + 1)" :sort="$sort" class="text-end text-secondary font-mono py-3 data-number" style="min-width: 90px;">{{ $monthName }}</x-sortable-th>
                                        @endforeach
                                        <x-sortable-th field="year_total" :sort="$sort" class="text-end text-success font-mono fw-bold py-3 data-number" style="min-width: 125px;">Total {{ $selectedYear }}</x-sortable-th>
                                        <x-sortable-th field="all_time" :sort="$sort" class="text-end text-warning font-mono fw-bold py-3 data-number" style="min-width: 130px;">Total Keseluruhan</x-sortable-th>
                                        <th class="text-end text-secondary py-3" style="width: 80px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if ($houses->isEmpty())
                                        <tr><td colspan="21" class="house-cost-empty text-center py-5 text-secondary">{{ $search || $filterStatus ? 'Tidak ada rumah yang cocok dengan filter.' : 'Belum ada rumah di cluster ini.' }}</td></tr>
                                    @else
                                    @foreach ($houses as $house)
                                        @php
                                            $totalAllTime = (float) ($house->material_usages_sum_total_cost ?? 0);
                                            $statusClasses = ['perencanaan' => 'bg-warning-subtle text-warning', 'pembangunan' => 'bg-primary-subtle text-primary', 'selesai' => 'bg-success-subtle text-success'];
                                            $yearTotal = 0;
                                            for ($month = 1; $month <= 12; $month++) {
                                                $yearTotal += (float) ($house->{'month_' . $month . '_cost'} ?? 0);
                                            }
                                        @endphp
                                        <tr wire:key="cluster-house-cost-{{ $house->id }}">
                                            <td data-label="No." class="text-center text-secondary small">{{ $houses->firstItem() + $loop->index }}</td>
                                            <td data-label="Kode" class="font-mono text-secondary small data-key-code" title="{{ $house->house_code ?? 'Kode tidak tersedia' }}"><span class="house-cost-cell-value">{{ $house->house_code ?? '-' }}</span></td>
                                            <td data-label="Rumah" class="fw-bold text-body data-key-name" title="{{ $house->name }}"><span class="house-cost-cell-value">{{ $house->name }}</span></td>
                                            <td data-label="Cluster" class="text-secondary small"><span class="house-cost-cell-value">{{ $house->cluster?->name ?? 'Tanpa Cluster' }}</span></td>
                                            <td data-label="Tipe" class="text-secondary small"><span class="house-cost-cell-value">{{ $house->type ?: '-' }}</span></td>
                                            <td data-label="Status" class="text-center"><span class="badge {{ $statusClasses[$house->status] ?? 'bg-secondary-subtle text-secondary' }}">{{ ucfirst($house->status) }}</span></td>
                                            @for ($month = 1; $month <= 12; $month++)
                                                @php
                                                    $monthCost = (float) ($house->{'month_' . $month . '_cost'} ?? 0);
                                                @endphp
                                                <td data-label="{{ $monthNames[$month - 1] }}" class="house-cost-month text-end font-mono data-number {{ $monthCost > 0 ? 'text-body font-semibold' : 'text-secondary text-opacity-50 is-zero-month' }}">{{ $monthCost > 0 ? number_format($monthCost, 0, ',', '.') : '-' }}</td>
                                            @endfor
                                            <td data-label="Total {{ $selectedYear }}" class="text-end font-mono fw-bold text-success bg-success-subtle bg-opacity-10 data-number">Rp {{ number_format($yearTotal, 0, ',', '.') }}</td>
                                            <td data-label="Total keseluruhan" class="text-end font-mono fw-bold text-warning bg-warning-subtle bg-opacity-10 data-number">Rp {{ number_format($totalAllTime, 0, ',', '.') }}</td>
                                            <td data-label="Aksi" class="text-end"><a href="{{ route(auth()->user()->role === 'admin' ? 'admin.house-costs.detail' : 'logistik.house-costs.detail', $house) }}" wire:navigate class="btn log-row-action log-row-action--edit">Lihat</a></td>
                                        </tr>
                                    @endforeach
                                    @endif
                                </tbody>
                                @if ($houses->total() > 0)
                                    <tfoot class="border-top fw-bold font-geist bg-body-tertiary">
                                        <tr class="align-middle">
                                            <td class="cluster-total-leading-spacer"></td>
                                            <td colspan="2" class="cluster-total-label text-end text-uppercase text-secondary small py-3">Total Biaya Rumah ({{ $selectedYear }}):</td>
                                            <td colspan="3"></td>
                                            @php
                                                $yearClusterTotal = 0;
                                            @endphp
                                            @for ($month = 1; $month <= 12; $month++)
                                                @php
                                                    $monthTotal = $monthlyTotals[$month] ?? 0;
                                                    $yearClusterTotal += $monthTotal;
                                                @endphp
                                                <td class="text-end font-mono py-3 data-number {{ $monthTotal > 0 ? 'text-body' : 'text-secondary text-opacity-50' }}">{{ $monthTotal > 0 ? number_format($monthTotal, 0, ',', '.') : '-' }}</td>
                                            @endfor
                                            <td class="text-end font-mono text-success fw-black py-3 bg-success-subtle bg-opacity-25 data-number">Rp {{ number_format($yearClusterTotal, 0, ',', '.') }}</td>
                                            <td class="text-end font-mono text-warning fw-black py-3 bg-warning-subtle bg-opacity-25 data-number">Rp {{ number_format($houseSubtotal, 0, ',', '.') }}</td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                @endif
                            </table>
                        </div>
                        @php
                            $mobileYearTotal = array_sum($monthlyTotals);
                        @endphp
                        <div class="house-cost-mobile-total d-none" aria-label="Ringkasan biaya cluster">
                            <span>Total {{ $selectedYear }}</span>
                            <strong class="font-mono text-success">Rp {{ number_format($mobileYearTotal, 0, ',', '.') }}</strong>
                            <span>Total keseluruhan</span>
                            <strong class="font-mono text-warning">Rp {{ number_format($houseSubtotal, 0, ',', '.') }}</strong>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end">{{ $houses->links() }}</div>
                </div>

                <div class="cluster-rental-table-panel">
                    <div class="cluster-expense-panel-heading mb-3">
                        <h2 class="h4 fw-bold mb-2 font-outfit">Sewa alat vendor</h2>
                        <p class="small text-secondary mb-0">Nilai dan jumlah unit yang tercantum adalah total transaksi sewa. Jika satu transaksi mencakup beberapa rumah, transaksi tampil di setiap rumah tetapi hanya dihitung sekali pada total cluster.</p>
                    </div>
                    <div class="table-responsive cluster-expense-table-scroll" tabindex="0" role="region" aria-label="Daftar sewa alat vendor per rumah">
                            <table class="table table-hover align-middle mb-0 cluster-expense-table">
                                <thead class="table-light text-uppercase small font-geist"><tr><th>Rumah</th><th>Penyewaan alat</th><th>Vendor</th><th>Periode</th><th>Unit (transaksi)</th><th class="text-end">Nilai sewa (transaksi)</th><th>Status &amp; aksi</th></tr></thead>
                                <tbody>
                                @forelse($rentalRows as $row)
                                    @php
                                        $expense = $row['expense'];
                                    @endphp
                                    <tr wire:key="{{ $row['key'] }}">
                                        <td data-label="Rumah"><span class="cluster-expense-cell-value fw-semibold">{{ $row['house'] }}</span></td>
                                        <td data-label="Penyewaan alat"><span class="cluster-expense-cell-value fw-semibold">{{ $expense->description }} @if($expense->type === 'rental_extension') <span class="d-block small text-secondary">Perpanjangan sewa</span> @endif @if($expense->houses->count() > 1) <span class="d-block small text-secondary">Satu transaksi untuk {{ $expense->houses->count() }} rumah</span> @endif @if($expense->bill_image) <a class="d-block small text-success" href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($expense->bill_image) }}" target="_blank" rel="noopener">Lihat bukti</a> @endif</span></td>
                                        <td data-label="Vendor"><span class="cluster-expense-cell-value text-secondary">{{ $expense->vendor ?: '-' }}</span></td>
                                        <td data-label="Periode"><span class="cluster-expense-cell-value small text-secondary">{{ $expense->start_date?->format('d/m/Y') ?: '-' }} @if($expense->due_date) sampai {{ $expense->due_date->format('d/m/Y') }} @endif</span></td>
                                        <td data-label="Unit (transaksi)"><span class="cluster-expense-cell-value">{{ rtrim(rtrim(number_format((float) $expense->quantity, 2, ',', '.'), '0'), ',') }}</span></td>
                                        <td data-label="Nilai sewa (transaksi)" class="text-end"><span class="cluster-expense-cell-value font-mono fw-bold">Rp {{ number_format($expense->amount, 0, ',', '.') }}</span></td>
                                        <td data-label="Status & aksi"><span class="cluster-expense-cell-value d-inline-flex align-items-center gap-2 flex-nowrap"><span class="badge bg-secondary-subtle text-secondary">{{ ucfirst($expense->status) }}</span>@if($expense->type === 'rental' && $expense->status === 'active') <button type="button" wire:click="startExtension({{ $expense->id }})" class="btn btn-outline-secondary btn-sm">Perpanjang</button>@endif</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="cluster-expense-empty text-center py-5 text-secondary">Belum ada penyewaan alat vendor untuk cluster ini.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                    </div>
                </div>

                <div class="cluster-vendor-service-table-panel">
                    <div class="cluster-expense-panel-heading mb-3">
                        <h2 class="h4 fw-bold mb-2 font-outfit">Jasa vendor</h2>
                        <p class="small text-secondary mb-0">Biaya jasa adalah total transaksi. Jika satu transaksi mencakup beberapa rumah, tampilannya dicantumkan per rumah tetapi hanya dihitung sekali pada total cluster.</p>
                    </div>
                    <div class="table-responsive cluster-expense-table-scroll" tabindex="0" role="region" aria-label="Daftar jasa vendor per rumah">
                            <table class="table table-hover align-middle mb-0 cluster-expense-table">
                                <thead class="table-light text-uppercase small font-geist"><tr><th>Target pekerjaan</th><th>Jasa</th><th>Vendor</th><th>Tanggal layanan</th><th class="text-end">Nilai transaksi</th><th>Catatan</th></tr></thead>
                                <tbody>
                                @forelse($vendorServiceRows as $row)
                                    @php
                                        $expense = $row['expense'];
                                    @endphp
                                    <tr wire:key="{{ $row['key'] }}">
                                        <td data-label="Target pekerjaan"><span class="cluster-expense-cell-value fw-semibold">{{ $row['target'] }}</span></td>
                                        <td data-label="Jasa"><span class="cluster-expense-cell-value fw-semibold">{{ $expense->description }} @if($expense->houses->count() > 1) <span class="d-block small text-secondary">Satu transaksi untuk {{ $expense->houses->count() }} rumah</span> @endif @if($expense->bill_image) <a class="d-block small text-success" href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($expense->bill_image) }}" target="_blank" rel="noopener">Lihat bukti</a> @endif</span></td>
                                        <td data-label="Vendor"><span class="cluster-expense-cell-value text-secondary">{{ $expense->vendor ?: '-' }}</span></td>
                                        <td data-label="Tanggal layanan"><span class="cluster-expense-cell-value small text-secondary">{{ $expense->start_date?->format('d/m/Y') ?: '-' }}</span></td>
                                        <td data-label="Nilai transaksi" class="text-end"><span class="cluster-expense-cell-value font-mono fw-bold">Rp {{ number_format($expense->amount, 0, ',', '.') }}</span></td>
                                        <td data-label="Catatan"><span class="cluster-expense-cell-value text-secondary">{{ $expense->notes ?: '-' }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="cluster-expense-empty text-center py-5 text-secondary">Belum ada jasa vendor untuk cluster ini.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                    </div>
                </div>
            </div>
        </fieldset>
    </div>

    @if($showModal)
        @teleport('body')
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,.5)" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-bottom"><h5 class="modal-title font-outfit fw-bold">{{ $type === 'rental_extension' ? 'Tambah Biaya Perpanjangan Rental' : 'Tambah Biaya Cluster' }}</h5><button type="button" class="btn-close" wire:click="$set('showModal', false)" aria-label="Tutup"></button></div>
                <div class="modal-body p-4">
                    @error('parent_expense_id') <div class="alert alert-warning" role="alert">{{ $message }}</div> @enderror
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label fw-semibold">Jenis</label><select wire:model.live="type" class="form-select"><option value="drainage">Drainase</option><option value="electrical">Pekerjaan listrik</option><option value="streetlight">Lampu jalan</option><option value="other">Lainnya</option>@if($parent_expense_id)<option value="rental_extension">Perpanjangan rental</option>@endif</select>@error('type')<div class="text-danger small">{{ $message }}</div>@enderror</div>
                        <div class="col-md-6"><label class="form-label fw-semibold">Nilai biaya</label><input type="number" min="0" wire:model="amount" class="form-control font-mono" placeholder="0" />@error('amount')<div class="text-danger small">{{ $message }}</div>@enderror</div>
                        <div class="col-12"><label class="form-label fw-semibold">Deskripsi</label><input type="text" wire:model="description" class="form-control" placeholder="Contoh: Sewa excavator untuk pekerjaan drainase" />@error('description')<div class="text-danger small">{{ $message }}</div>@enderror</div>
                        @if(in_array($type, ['rental', 'rental_extension'], true))
                            <div class="col-md-6"><label class="form-label fw-semibold">Vendor pihak ketiga</label><input type="text" wire:model="vendor" class="form-control" />@error('vendor')<div class="text-danger small mt-1">{{ $message }}</div>@enderror</div>
                            <div class="col-md-6"><label class="form-label fw-semibold">Jumlah unit</label><input type="number" min="0.01" step="0.01" wire:model="quantity" class="form-control font-mono" />@error('quantity')<div class="text-danger small mt-1">{{ $message }}</div>@enderror</div>
                            <div class="col-md-6"><label class="form-label fw-semibold">Mulai rental</label><input type="date" wire:model="start_date" class="form-control" />@error('start_date')<div class="text-danger small mt-1">{{ $message }}</div>@enderror</div>
                            <div class="col-md-6"><label class="form-label fw-semibold">Jatuh tempo manual</label><input type="date" wire:model="due_date" class="form-control" />@error('due_date')<div class="text-danger small mt-1">{{ $message }}</div>@enderror</div>
                            <div class="col-12"><label class="form-label fw-semibold">Foto tagihan <span class="text-danger">*</span></label><input type="file" wire:model="bill_image" accept="image/*" class="form-control" />@error('bill_image')<div class="text-danger small mt-1">{{ $message }}</div>@enderror<div class="small text-secondary mt-1">Wajib untuk rental dan setiap perpanjangan.</div></div>
                        @endif
                        <div class="col-12"><label class="form-label fw-semibold">Catatan</label><textarea wire:model="notes" class="form-control" rows="2"></textarea>@error('notes')<div class="text-danger small mt-1">{{ $message }}</div>@enderror</div>
                    </div>
                </div>
                <div class="modal-footer border-top bg-body-tertiary rounded-bottom-4"><button type="button" class="btn btn-secondary fw-semibold" wire:click="$set('showModal', false)">Batal</button><button type="button" class="btn btn-success fw-semibold" wire:click="save" wire:loading.attr="disabled" wire:target="save"><span wire:loading.remove wire:target="save">Simpan biaya</span><span wire:loading wire:target="save">Menyimpan biaya…</span></button></div>
            </div></div>
        </div>
        @endteleport
    @endif


</div>

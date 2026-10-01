<div>
    <div class="container-fluid p-0">
        <!-- Hero Header -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-body-tertiary">
            <div class="card-body p-4 p-md-5 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <div>
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <a href="{{ route('logistik.houses') }}" wire:navigate class="back-link">
                            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8"/></svg>
                            Kembali
                        </a>
                        @php
                            $statusClasses = ['perencanaan' => 'bg-warning-subtle text-warning', 'pembangunan' => 'bg-primary-subtle text-primary', 'selesai' => 'bg-success-subtle text-success'];
                        @endphp
                        <span class="badge {{ $statusClasses[$house->status] ?? 'bg-secondary-subtle text-secondary' }}">{{ ucfirst($house->status) }}</span>
                        @if($house->isUnderWarranty())
                            <span class="badge bg-warning-subtle text-warning-emphasis">Masa garansi sampai {{ $house->warranty_expires_at->format('d/m/Y') }}</span>
                        @elseif($house->status === 'selesai' && $house->warranty_expires_at)
                            <span class="badge bg-secondary-subtle text-secondary">Garansi berakhir {{ $house->warranty_expires_at->format('d/m/Y') }}</span>
                        @endif
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <h1 class="display-5 fw-black text-body mb-0 font-outfit">{{ $house->name }}</h1>
                        @if($house->house_code)
                            <span class="badge bg-secondary-subtle text-secondary font-mono fs-6 house-detail-code">{{ $house->house_code }}</span>
                        @endif
                    </div>
                    <p class="text-secondary mb-0 mt-1">Tipe Unit: {{ $house->type }}</p>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    @if(in_array(auth()->user()->role, ['admin', 'logistik']))
                        <button type="button" wire:click="exportExcel" class="btn btn-utility font-semibold">
                            <svg width="15" height="15" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5"/>
                                <path d="M7.646 1.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 2.707V10.5a.5.5 0 0 1-1 0V2.707L5.354 4.854a.5.5 0 1 1-.708-.708z"/>
                            </svg>
                            <span>Ekspor Excel</span>
                        </button>
                    @endif
                    @if($house->status !== 'selesai')
                        <a href="{{ route('logistik.house-finish', $house) }}" wire:navigate class="btn btn-warning font-semibold">Selesaikan Rumah</a>
                    @else
                        <span class="badge bg-success-subtle text-success p-2">{{ $house->isUnderWarranty() ? 'Proyek selesai · Masa garansi aktif' : 'Proyek selesai · Data terkunci' }}</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Bento Stats Grid -->
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card border-0 border-start border-4 border-warning shadow-sm rounded-4 p-4 bg-body-tertiary h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="small fw-bold text-secondary text-uppercase tracking-wider">Total Biaya Material</span>
                        <div class="p-2 bg-warning-subtle text-warning rounded d-flex align-items-center justify-content-center">
                            <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M12.136.326A1.5 1.5 0 0 1 14 1.78V3h.5A1.5 1.5 0 0 1 16 4.5v9a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 0 13.5v-9A1.5 1.5 0 0 1 1.5 3H2V1.78a1.5 1.5 0 0 1 1.864-1.454l8.272 2zm-7.468 3h6.664V1.8a.5.5 0 0 0-.62-.485L3.864 2.827a.5.5 0 0 0-.196.499zM1 4.5v9a.5.5 0 0 0 .5.5h13a.5.5 0 0 0 .5-.5v-9a.5.5 0 0 0-.5-.5h-13a.5.5 0 0 0-.5.5z"/></svg>
                        </div>
                    </div>
                    <div>
                        <h2 class="fw-black text-warning mb-1 font-mono text-nowrap">Rp {{ number_format($house->total_material_cost, 0, ',', '.') }}</h2>
                        <span class="text-secondary small">Akumulasi material normal dan masa garansi</span>
                    </div>
                    <div class="mt-3 pt-3 border-top d-flex justify-content-between align-items-center gap-3">
                        <span class="small fw-semibold text-secondary">Biaya Material Garansi</span>
                        <span class="fw-bold text-success font-mono text-nowrap">Rp {{ number_format($warrantyMaterialCost, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 border-start border-4 border-primary shadow-sm rounded-4 p-4 bg-body-tertiary h-100 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="small fw-bold text-secondary text-uppercase tracking-wider">Total Transaksi</span>
                        <div class="p-2 bg-primary-subtle text-primary rounded d-flex align-items-center justify-content-center">
                            <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2z"/><path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466"/></svg>
                        </div>
                    </div>
                    <div class="mt-auto">
                        <h2 class="fw-black text-body mb-1 font-mono">{{ $materialCount + $warrantyMaterialCount + $toolCount + $warrantyToolCount + $vendorServiceCount + $vendorRentalCount }} <span class="fs-6 text-secondary font-normal">Log</span></h2>
                        <span class="text-secondary small">Penggunaan material, peminjaman alat, jasa vendor, dan sewa alat</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs Container -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-body-tertiary">
            @php
                $isWarrantyLog = str_starts_with($activeTab, 'warranty-');
                $isToolLog = str_ends_with($activeTab, 'tool');
                $isVendorServiceLog = $activeTab === 'vendor-service';
                $isVendorRentalLog = $activeTab === 'vendor-rental';
                $pastLogCount = $materialCount + $toolCount + $vendorServiceCount + $vendorRentalCount;
                $warrantyLogCount = $warrantyMaterialCount + $warrantyToolCount;
            @endphp
            <div class="house-log-period-tabs" role="group" aria-label="Bagian log rumah">
                <button type="button" class="house-log-period-tab {{ $isWarrantyLog ? '' : 'active' }}" aria-pressed="{{ $isWarrantyLog ? 'false' : 'true' }}" wire:click="$set('activeTab', '{{ $isToolLog ? 'tool' : 'material' }}')">
                    <span>Catatan lama</span>
                    <span class="house-log-period-count">{{ $pastLogCount }}</span>
                </button>
                <button type="button" class="house-log-period-tab {{ $isWarrantyLog ? 'active' : '' }}" aria-pressed="{{ $isWarrantyLog ? 'true' : 'false' }}" wire:click="$set('activeTab', 'warranty-{{ $isToolLog ? 'tool' : 'material' }}')">
                    <span>Catatan garansi</span>
                    <span class="house-log-period-count">{{ $warrantyLogCount }}</span>
                </button>
            </div>

            <div class="house-log-filters" role="group" aria-label="Jenis catatan">
                <span class="small text-secondary">Tampilkan</span>
                <div class="d-flex flex-wrap gap-2" role="group" aria-label="Pilih jenis catatan">
                    <button type="button" class="house-log-filter-button {{ $activeTab === 'material' || $activeTab === 'warranty-material' ? 'active' : '' }}" aria-pressed="{{ $activeTab === 'material' || $activeTab === 'warranty-material' ? 'true' : 'false' }}" wire:click="$set('activeTab', '{{ $isWarrantyLog ? 'warranty-material' : 'material' }}')">
                        <svg width="16" height="16" fill="currentColor" aria-hidden="true"><use href="#i-box"/></svg>
                        <span>Material</span>
                        <span class="house-log-filter-count">{{ $isWarrantyLog ? $warrantyMaterialCount : $materialCount }}</span>
                    </button>
                    <button type="button" class="house-log-filter-button {{ $isToolLog ? 'active' : '' }}" aria-pressed="{{ $isToolLog ? 'true' : 'false' }}" wire:click="$set('activeTab', '{{ $isWarrantyLog ? 'warranty-tool' : 'tool' }}')">
                        <svg width="16" height="16" fill="currentColor" aria-hidden="true"><use href="#i-wrench"/></svg>
                        <span>Alat</span>
                        <span class="house-log-filter-count">{{ $isWarrantyLog ? $warrantyToolCount : $toolCount }}</span>
                    </button>
                    <button type="button" class="house-log-filter-button {{ $isVendorServiceLog ? 'active' : '' }}" aria-pressed="{{ $isVendorServiceLog ? 'true' : 'false' }}" wire:click="$set('activeTab', 'vendor-service')" @disabled($isWarrantyLog)>
                        <svg width="16" height="16" fill="currentColor" aria-hidden="true"><use href="#i-journal-text"/></svg>
                        <span>Jasa Vendor</span>
                        <span class="house-log-filter-count">{{ $isWarrantyLog ? 0 : $vendorServiceCount }}</span>
                    </button>
                    <button type="button" class="house-log-filter-button {{ $isVendorRentalLog ? 'active' : '' }}" aria-pressed="{{ $isVendorRentalLog ? 'true' : 'false' }}" wire:click="$set('activeTab', 'vendor-rental')" @disabled($isWarrantyLog)>
                        <svg width="16" height="16" fill="currentColor" aria-hidden="true"><use href="#i-truck"/></svg>
                        <span>Sewa Alat</span>
                        <span class="house-log-filter-count">{{ $isWarrantyLog ? 0 : $vendorRentalCount }}</span>
                    </button>
                </div>
            </div>

            @if($activeTab === 'material' || $activeTab === 'warranty-material')
            <div>
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                    <h5 class="fw-bold mb-0 font-outfit">{{ $activeTab === 'warranty-material' ? 'Log Material Masa Garansi' : 'Log Penggunaan Material' }}</h5>
                    <div class="flex-grow-1" style="max-width: 340px;">
                        <input type="search" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Cari material, kode, pekerjaan..." aria-label="Cari material, kode, pekerjaan, atau pencatat" autocomplete="off">
                    </div>
                </div>
                <div class="table-responsive mb-3 house-detail-log-table-scroll">
                    <table class="table table-hover align-middle mb-0 house-detail-material-table">
                        <thead class="table-light text-uppercase small font-geist">
                            <tr>
                                <th class="text-center" style="width: 50px;">No.</th>
                                <x-sortable-th field="date" :sort="$sort">Tanggal</x-sortable-th>
                                <x-sortable-th field="notes" :sort="$sort">Pekerjaan</x-sortable-th>
                                <x-sortable-th field="code" :sort="$sort">Kode</x-sortable-th>
                                <x-sortable-th field="material" :sort="$sort">Material</x-sortable-th>
                                <x-sortable-th field="quantity" :sort="$sort" class="text-end">Volume</x-sortable-th>
                                <x-sortable-th field="unit" :sort="$sort">Satuan</x-sortable-th>
                                <x-sortable-th field="unit_price" :sort="$sort" class="text-end">Harga Satuan</x-sortable-th>
                                <x-sortable-th field="total" :sort="$sort" class="text-end">Total Biaya</x-sortable-th>
                                <x-sortable-th field="user" :sort="$sort">Pencatat</x-sortable-th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($materialUsages as $usage)
                            <tr wire:key="{{ $activeTab }}-usage-{{ $usage->id }}">
                                <td class="text-center text-secondary small data-mobile-secondary">{{ ($materialUsages->currentPage() - 1) * $materialUsages->perPage() + $loop->iteration }}</td>
                                <td data-label="Tanggal" class="font-mono text-secondary small data-date"><span class="house-detail-cell-value">{{ $usage->usage_date->format('d/m/Y') }}</span></td>
                                <td data-label="Pekerjaan" class="fw-semibold text-body data-cell-truncate" title="{{ $usage->notes ?: '-' }}"><span class="house-detail-cell-value">{{ $usage->notes ?: '-' }}</span></td>
                                <td data-label="Kode" class="font-mono text-secondary small data-key-code" title="{{ $usage->material->code ?? '-' }}"><span class="house-detail-cell-value">{{ $usage->material->code ?? '-' }}</span></td>
                                <td data-label="Material" class="fw-bold text-body data-key-name" title="{{ $usage->material->name }}"><span class="house-detail-cell-value">{{ $usage->material->name }}</span></td>
                                <td data-label="Volume" class="text-end fw-bold font-mono data-number"><span class="house-detail-cell-value">{{ str_replace('.', ',', (float) $usage->quantity) }}</span></td>
                                <td data-label="Satuan" class="text-secondary small data-cell-truncate" title="{{ $usage->material->unit }}"><span class="house-detail-cell-value">{{ $usage->material->unit }}</span></td>
                                <td data-label="Harga satuan" class="text-end font-mono text-secondary data-number"><span class="house-detail-cell-value">Rp {{ number_format($usage->unit_price_at_usage, 0, ',', '.') }}</span></td>
                                <td data-label="Total biaya" class="text-end font-mono fw-bold text-success data-number"><span class="house-detail-cell-value">Rp {{ number_format($usage->total_cost, 0, ',', '.') }}</span></td>
                                <td data-label="Pencatat" class="text-secondary small data-cell-truncate" title="{{ $usage->user->name ?? '-' }}"><span class="house-detail-cell-value">{{ $usage->user->name ?? '-' }}</span></td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="10" class="house-detail-log-empty text-center py-4 text-secondary">{{ $search !== '' ? 'Tidak ada catatan yang cocok dengan pencarian.' : ($activeTab === 'warranty-material' ? 'Belum ada material yang dialokasikan selama masa garansi.' : 'Belum ada data penggunaan material untuk rumah ini.') }}</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-end">{{ $materialUsages->links() }}</div>
            </div>
            @endif

            @if($activeTab === 'tool' || $activeTab === 'warranty-tool')
            <div>
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                    <h5 class="fw-bold mb-0 font-outfit">{{ $activeTab === 'warranty-tool' ? 'Log Alat Masa Garansi' : 'Log Peminjaman Alat' }}</h5>
                    <div class="flex-grow-1" style="max-width: 340px;">
                        <input type="search" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Cari alat, kode, atau status..." aria-label="Cari alat, kode, catatan, status, atau pencatat" autocomplete="off">
                    </div>
                </div>
                <div class="table-responsive mb-3 house-detail-log-table-scroll">
                    <table class="table table-hover align-middle mb-0 house-detail-tool-table">
                        <thead class="table-light text-uppercase small font-geist">
                            <tr>
                                <th class="text-center" style="width: 50px;">No.</th>
                                <x-sortable-th field="date" :sort="$sort">Tanggal Pinjam</x-sortable-th>
                                <x-sortable-th field="tool" :sort="$sort">Alat</x-sortable-th>
                                <x-sortable-th field="quantity" :sort="$sort" class="text-end">Jumlah</x-sortable-th>
                                <x-sortable-th field="status" :sort="$sort" class="text-center">Status</x-sortable-th>
                                <x-sortable-th field="user" :sort="$sort">Pencatat</x-sortable-th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($toolUsages as $usage)
                            <tr wire:key="t-usage-{{ $usage->id }}">
                                <td class="text-center text-secondary small data-mobile-secondary">{{ ($toolUsages->currentPage() - 1) * $toolUsages->perPage() + $loop->iteration }}</td>
                                <td data-label="Tanggal pinjam" class="font-mono text-secondary small data-date"><span class="house-detail-cell-value">{{ $usage->checkout_date->format('d/m/Y') }}</span></td>
                                <td data-label="Alat">
                                    <span class="house-detail-cell-value"><span class="fw-bold data-cell-truncate" title="{{ $usage->tool->name }}">{{ $usage->tool->name }}</span><span class="d-block extra-small text-secondary font-mono data-key-code" title="{{ $usage->tool->code }}">Kode: {{ $usage->tool->code }}</span></span>
                                </td>
                                <td data-label="Jumlah" class="text-end font-mono fw-bold data-number"><span class="house-detail-cell-value">{{ number_format($usage->quantity) }}</span></td>
                                <td data-label="Status" class="text-center"><span class="house-detail-cell-value">
                                    @if($usage->return_date)
                                        <span class="badge bg-success-subtle text-success">Dikembalikan ({{ $usage->return_date->format('d/m') }})</span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning">Dipinjam</span>
                                    @endif
                                </span></td>
                                <td data-label="Pencatat" class="text-secondary small data-cell-truncate" title="{{ $usage->user->name ?? '-' }}"><span class="house-detail-cell-value">{{ $usage->user->name ?? '-' }}</span></td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="house-detail-log-empty text-center py-4 text-secondary">{{ $search !== '' ? 'Tidak ada catatan yang cocok dengan pencarian.' : ($activeTab === 'warranty-tool' ? 'Belum ada alat yang dialokasikan selama masa garansi.' : 'Belum ada data peminjaman alat untuk rumah ini.') }}</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-end">{{ $toolUsages->links() }}</div>
            </div>
            @endif

            @if($activeTab === 'vendor-service')
            <div>
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                    <h5 id="house-vendor-service-title" class="fw-bold mb-0 font-outfit">Jasa Vendor Rumah</h5>
                    <div class="flex-grow-1" style="max-width: 340px;">
                        <input type="search" wire:model.live.debounce.300ms="vendorServiceSearch" class="form-control" placeholder="Cari pekerjaan atau vendor..." aria-label="Cari pekerjaan atau vendor" autocomplete="off">
                    </div>
                </div>
                <div class="table-responsive house-detail-log-table-scroll" tabindex="0" role="region" aria-label="Daftar jasa vendor untuk {{ $house->name }}">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-uppercase small font-geist">
                            <tr><th>Tanggal layanan</th><th>Pekerjaan</th><th>Vendor</th><th class="text-end">Total biaya</th><th>Catatan</th><th>Pencatat</th></tr>
                        </thead>
                        <tbody>
                            @forelse($vendorServiceExpenses as $expense)
                                <tr wire:key="house-vendor-service-{{ $expense->id }}">
                                    <td data-label="Tanggal layanan" class="font-mono text-secondary small">{{ $expense->start_date?->format('d/m/Y') ?? '-' }}</td>
                                    <td data-label="Pekerjaan" class="fw-semibold">
                                        {{ $expense->description }}
                                        @if($expense->houses->count() > 1)<span class="d-block small text-secondary">Satu transaksi untuk {{ $expense->houses->count() }} rumah</span>@endif
                                        @if($expense->bill_image)<a class="d-block small text-success" href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($expense->bill_image) }}" target="_blank" rel="noopener">Lihat bukti</a>@endif
                                    </td>
                                    <td data-label="Vendor" class="text-secondary">{{ $expense->vendor ?: '-' }}</td>
                                    <td data-label="Total biaya" class="text-end font-mono fw-bold">Rp {{ number_format($expense->amount, 0, ',', '.') }}</td>
                                    <td data-label="Catatan" class="text-secondary">{{ $expense->notes ?: '-' }}</td>
                                    <td data-label="Pencatat" class="text-secondary small">{{ $expense->createdBy?->name ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center py-4 text-secondary">{{ $vendorServiceSearch !== '' ? 'Tidak ada jasa vendor yang cocok dengan pencarian.' : 'Belum ada jasa vendor yang dicatat untuk rumah ini.' }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-end mt-3">{{ $vendorServiceExpenses->links() }}</div>
            </div>
            @endif

            @if($activeTab === 'vendor-rental')
            <div>
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                    <h5 id="house-vendor-rental-title" class="fw-bold mb-0 font-outfit">Sewa Alat Vendor</h5>
                    <div class="flex-grow-1" style="max-width: 340px;">
                        <input type="search" wire:model.live.debounce.300ms="vendorRentalSearch" class="form-control" placeholder="Cari alat atau vendor..." aria-label="Cari sewa alat berdasarkan nama alat, vendor, atau catatan" autocomplete="off">
                    </div>
                </div>
                <div class="table-responsive house-detail-log-table-scroll" tabindex="0" role="region" aria-label="Daftar sewa alat vendor untuk {{ $house->name }}">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-uppercase small font-geist">
                            <tr><th>Periode</th><th>Alat</th><th>Vendor</th><th class="text-end">Jumlah unit</th><th class="text-end">Biaya sewa</th><th>Status</th><th>Catatan</th><th>Pencatat</th></tr>
                        </thead>
                        <tbody>
                            @forelse($vendorRentalExpenses as $expense)
                                <tr wire:key="house-vendor-rental-{{ $expense->id }}">
                                    <td data-label="Periode" class="small text-secondary">
                                        {{ $expense->start_date?->format('d/m/Y') ?? '-' }}
                                        @if($expense->due_date)<span class="d-block">s.d. {{ $expense->due_date->format('d/m/Y') }}</span>@endif
                                        @if($expense->off_hire_date)<span class="d-block">Off-hire {{ $expense->off_hire_date->format('d/m/Y') }}</span>@endif
                                    </td>
                                    <td data-label="Alat" class="fw-semibold">
                                        {{ $expense->description }}
                                        @if($expense->type === 'rental_extension')<span class="d-block small text-secondary">Perpanjangan sewa</span>@endif
                                        @if($expense->houses->count() > 1)<span class="d-block small text-secondary">Satu transaksi untuk {{ $expense->houses->count() }} rumah</span>@endif
                                        @if($expense->bill_image)<a class="d-block small text-success" href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($expense->bill_image) }}" target="_blank" rel="noopener">Lihat bukti</a>@endif
                                    </td>
                                    <td data-label="Vendor" class="text-secondary">{{ $expense->vendor ?: '-' }}</td>
                                    <td data-label="Jumlah unit" class="text-end font-mono">{{ $expense->quantity === null ? '-' : rtrim(rtrim(number_format((float) $expense->quantity, 2, ',', '.'), '0'), ',') }}</td>
                                    <td data-label="Biaya sewa" class="text-end font-mono fw-bold">Rp {{ number_format($expense->amount, 0, ',', '.') }}</td>
                                    <td data-label="Status"><span class="badge bg-secondary-subtle text-secondary">{{ $expense->off_hire_date ? 'Selesai' : ($expense->status === 'active' ? 'Aktif' : ucfirst($expense->status)) }}</span></td>
                                    <td data-label="Catatan" class="text-secondary">{{ $expense->notes ?: '-' }}</td>
                                    <td data-label="Pencatat" class="small text-secondary">{{ $expense->createdBy?->name ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center py-4 text-secondary">{{ $vendorRentalSearch !== '' ? 'Tidak ada sewa alat yang cocok dengan pencarian.' : 'Belum ada sewa alat vendor yang dicatat untuk rumah ini.' }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-end mt-3">{{ $vendorRentalExpenses->links() }}</div>
            </div>
            @endif
        </div>
    </div>
</div>

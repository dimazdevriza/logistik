@php
    $unitLabelLower = $house->isPublicWork() ? 'pekerjaan umum' : 'rumah';
@endphp
<div>
    <div class="container-fluid p-0">
        <!-- Hero Header -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-body-tertiary">
            <div class="card-body p-4 p-md-5 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <div>
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <a href="{{ $house->isPublicWork() ? route('logistik.public-works') : route('logistik.houses') }}" wire:navigate class="back-link">
                            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8"/></svg>
                            {{ $house->isPublicWork() ? 'Kembali ke Pekerjaan Umum' : 'Kembali' }}
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
                    <p class="text-secondary mb-0 mt-1">{{ $house->isPublicWork() ? 'Cluster: ' . ($house->cluster?->name ?? 'Tanpa Cluster') : 'Tipe Unit: ' . ($house->type ?: '-') }}</p>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    @if(in_array(auth()->user()->role, ['admin', 'logistik', 'pengawas']))
                        <button type="button" wire:click="exportExcel" class="btn btn-utility font-semibold">
                            <svg width="15" height="15" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5"/>
                                <path d="M7.646 1.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 2.707V10.5a.5.5 0 0 1-1 0V2.707L5.354 4.854a.5.5 0 1 1-.708-.708z"/>
                            </svg>
                            <span>Ekspor Excel</span>
                        </button>
                    @endif
                    @if($house->status !== 'selesai')
                        <a href="{{ route('logistik.house-finish', $house) }}" wire:navigate class="btn btn-warning font-semibold">{{ $house->isPublicWork() ? 'Selesaikan Pekerjaan' : 'Selesaikan Rumah' }}</a>
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

        <!-- Bobot Rumah & Bobot Bahan Panel (Hanya untuk Rumah, bukan Pekerjaan Umum) -->
        @unless ($house->isPublicWork())
        @php
            $stages = $house->resolvedProgressStages();
            $bobotRumah = $house->bobot_rumah;
            $bobotBahan = $house->bobot_bahan;
            $isOverBudget = $bobotBahan !== null && $bobotBahan > 100;
            $canManageProgress = in_array(auth()->user()->role, ['admin', 'pengawas']);
            $canManageBudget = in_array(auth()->user()->role, ['admin', 'logistik']);
        @endphp
        <div class="card border-0 shadow-sm rounded-4 mb-4 p-4 bg-body-tertiary">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                <div>
                    <h2 class="h5 fw-bold mb-1 font-outfit text-body">Bobot &amp; Progres Pembangunan</h2>
                    <p class="text-secondary small mb-0">Pantau perbandingan progres fisik bangunan (bobot rumah) terhadap penyerapan anggaran material (bobot bahan).</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @if ($canManageBudget)
                        <button type="button" wire:click="openBudgetModal" class="btn btn-sm btn-outline-secondary font-semibold d-inline-flex align-items-center gap-1">
                            <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M1 3a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1H1zm7 8a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/><path d="M0 5a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H1a1 1 0 0 1-1-1V5zm3 0a2 2 0 0 1-2 2v4a2 2 0 0 1 2 2h10a2 2 0 0 1 2-2V7a2 2 0 0 1-2-2H3z"/></svg>
                            <span>Atur Anggaran (RAP)</span>
                        </button>
                    @endif
                    @if ($canManageProgress && $house->status !== 'selesai')
                        <button type="button" wire:click="openStageModal" class="btn btn-sm btn-outline-secondary font-semibold d-inline-flex align-items-center gap-1">
                            <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325"/></svg>
                            <span>Atur Tahap &amp; Bobot</span>
                        </button>
                    @endif
                </div>
            </div>

            <!-- Two Progress Bars Grid -->
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="p-3 rounded-3 border bg-body h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small fw-bold text-secondary text-uppercase tracking-wider">Bobot Rumah (Fisik)</span>
                                <span class="fs-4 fw-black font-mono text-body">{{ number_format($bobotRumah, 1, ',', '.') }}%</span>
                            </div>
                            <p class="extra-small text-secondary mb-3">Persentase tahapan pekerjaan fisik yang telah selesai.</p>
                        </div>
                        <div>
                            <div class="progress rounded-pill bg-body-tertiary" style="height: 10px;">
                                <div class="progress-bar bg-success rounded-pill" role="progressbar" style="width: {{ min(100, $bobotRumah) }}%;" aria-valuenow="{{ $bobotRumah }}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="p-3 rounded-3 border bg-body h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small fw-bold text-secondary text-uppercase tracking-wider">Bobot Bahan (Material)</span>
                                @if ($bobotBahan !== null)
                                    <span class="fs-4 fw-black font-mono {{ $isOverBudget ? 'text-danger' : 'text-body' }}">
                                        {{ number_format($bobotBahan, 1, ',', '.') }}%
                                    </span>
                                @else
                                    <span class="small text-secondary italic">Belum diatur</span>
                                @endif
                            </div>
                            <p class="extra-small text-secondary mb-3">
                                @if ($house->material_budget > 0)
                                    Rp {{ number_format($house->total_material_cost, 0, ',', '.') }} terpakai dari pagu Rp {{ number_format($house->material_budget, 0, ',', '.') }}
                                    @if ($isOverBudget)
                                        <span class="text-danger fw-semibold d-block mt-1">Melebihi anggaran ({{ number_format($bobotBahan - 100, 1, ',', '.') }}% lebih tinggi)</span>
                                    @endif
                                @else
                                    Anggaran material (RAP) belum ditentukan.
                                    @if ($canManageBudget)
                                        <a href="javascript:void(0)" wire:click="openBudgetModal" class="text-success text-decoration-none fw-semibold">Tentukan anggaran &rarr;</a>
                                    @endif
                                @endif
                            </p>
                        </div>
                        <div>
                            <div class="progress rounded-pill bg-body-tertiary" style="height: 10px;">
                                @if ($bobotBahan !== null)
                                    <div class="progress-bar rounded-pill {{ $isOverBudget ? 'bg-danger' : 'bg-success' }}" role="progressbar" style="width: {{ min(100, $bobotBahan) }}%;" aria-valuenow="{{ $bobotBahan }}" aria-valuemin="0" aria-valuemax="100"></div>
                                @else
                                    <div class="progress-bar rounded-pill bg-secondary text-opacity-25" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stages Checklist -->
            <div class="border-top pt-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="h6 fw-bold mb-0 font-outfit text-body">
                        Tahapan Pekerjaan <span class="text-secondary fw-normal">({{ count($stages) }} tahap)</span>
                    </h3>
                    @if ($canManageProgress && $house->status !== 'selesai')
                        <span class="extra-small text-secondary">Klik kotak untuk menandai tahap selesai</span>
                    @endif
                </div>

                @if (empty($stages))
                    <div class="p-3 text-center text-secondary small bg-body rounded-3 border">
                        Belum ada tahapan pekerjaan untuk unit ini.
                        @if ($canManageProgress && $house->status !== 'selesai')
                            <button type="button" wire:click="openStageModal" class="btn btn-link btn-sm text-success p-0 text-decoration-none d-block mx-auto mt-1">Tambah tahapan sekarang &rarr;</button>
                        @endif
                    </div>
                @else
                    <div class="row g-2">
                        @foreach ($stages as $idx => $stage)
                            @php
                                $isDone = ! empty($stage['completed']);
                            @endphp
                            <div class="col-md-6 col-lg-4" wire:key="stage-item-{{ $idx }}">
                                <div class="py-2 px-3 rounded-3 border {{ $isDone ? 'bg-success-subtle bg-opacity-10 border-success-subtle' : 'bg-body' }} d-flex align-items-center justify-content-between gap-2 transition-colors" style="min-height: 44px;">
                                    <label class="d-flex align-items-center gap-2 mb-0 flex-grow-1 min-w-0 {{ $canManageProgress && $house->status !== 'selesai' ? 'cursor-pointer' : '' }}">
                                        @if ($canManageProgress && $house->status !== 'selesai')
                                            <input type="checkbox"
                                                class="form-check-input m-0 flex-shrink-0"
                                                style="width: 18px; height: 18px; cursor: pointer;"
                                                wire:click="toggleStage({{ $idx }})"
                                                {{ $isDone ? 'checked' : '' }}
                                                aria-label="Tandai {{ $stage['name'] }} selesai">
                                        @else
                                            <input type="checkbox"
                                                class="form-check-input m-0 flex-shrink-0"
                                                style="width: 18px; height: 18px;"
                                                disabled
                                                {{ $isDone ? 'checked' : '' }}>
                                        @endif
                                        <span class="small fw-semibold text-truncate {{ $isDone ? 'text-body text-decoration-line-through text-opacity-75' : 'text-body' }}" title="{{ $stage['name'] }}">
                                            {{ $stage['name'] }}
                                        </span>
                                    </label>
                                    <div class="d-flex align-items-center gap-1 flex-shrink-0">
                                        <span class="badge {{ $isDone ? 'bg-success text-white' : 'bg-secondary-subtle text-secondary' }} font-mono small">
                                            {{ $stage['weight'] }}%
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
        @endunless

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
                                <td colspan="10" class="house-detail-log-empty text-center py-4 text-secondary">{{ $search !== '' ? 'Tidak ada catatan yang cocok dengan pencarian.' : ($activeTab === 'warranty-material' ? 'Belum ada material yang dialokasikan selama masa garansi.' : "Belum ada data penggunaan material untuk {$unitLabelLower} ini.") }}</td>
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
                                <td colspan="6" class="house-detail-log-empty text-center py-4 text-secondary">{{ $search !== '' ? 'Tidak ada catatan yang cocok dengan pencarian.' : ($activeTab === 'warranty-tool' ? 'Belum ada alat yang dialokasikan selama masa garansi.' : "Belum ada data peminjaman alat untuk {$unitLabelLower} ini.") }}</td>
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
                    <h5 id="house-vendor-service-title" class="fw-bold mb-0 font-outfit">Jasa Vendor {{ $house->isPublicWork() ? 'Pekerjaan Umum' : 'Rumah' }}</h5>
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
                                <tr><td colspan="6" class="text-center py-4 text-secondary">{{ $vendorServiceSearch !== '' ? 'Tidak ada jasa vendor yang cocok dengan pencarian.' : "Belum ada jasa vendor yang dicatat untuk {$unitLabelLower} ini." }}</td></tr>
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
                                <tr><td colspan="8" class="text-center py-4 text-secondary">{{ $vendorRentalSearch !== '' ? 'Tidak ada sewa alat yang cocok dengan pencarian.' : "Belum ada sewa alat vendor yang dicatat untuk {$unitLabelLower} ini." }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-end mt-3">{{ $vendorRentalExpenses->links() }}</div>
            </div>
            @endif
        </div>
    </div>

    @unless ($house->isPublicWork())
    <!-- Modal Atur Anggaran (Logistik / Admin) -->
    @if ($showBudgetModal)
    @teleport('body')
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog" x-on:keydown.escape.window="$wire.set('showBudgetModal', false)">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title font-outfit fw-bold">Atur Anggaran Material (RAP)</h5>
                    <button type="button" class="btn-close" wire:click="$set('showBudgetModal', false)" aria-label="Tutup modal"></button>
                </div>
                <form wire:submit="saveMaterialBudget">
                    <div class="modal-body py-4">
                        @if ($otherHouses->isNotEmpty())
                            <div class="mb-3">
                                <label for="copy-budget-select" class="form-label small fw-bold text-secondary text-uppercase">Salin Anggaran dari Unit Lain (Opsional)</label>
                                <select id="copy-budget-select" wire:model.live="copyBudgetHouseId" class="form-select">
                                    <option value="">-- Pilih unit untuk disalin --</option>
                                    @foreach ($otherHouses as $oh)
                                        @if ($oh->material_budget > 0)
                                            <option value="{{ $oh->id }}">{{ $oh->name }} (Rp {{ number_format($oh->material_budget, 0, ',', '.') }})</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="mb-3">
                            <label for="budget-amount-input" class="form-label font-semibold">Pagu Anggaran Material (Rp)</label>
                            <div class="input-group">
                                <span class="input-group-text font-mono">Rp</span>
                                <input id="budget-amount-input" type="number" step="any" min="0" wire:model="budgetAmount" class="form-control font-mono" placeholder="Contoh: 150000000" />
                            </div>
                            <div class="form-text">Masukkan total pagu biaya material (RAP) untuk unit ini.</div>
                            @error('budgetAmount') <span class="text-danger small" role="alert">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="modal-footer border-top bg-body-tertiary rounded-bottom-4">
                        <button type="button" class="btn btn-secondary btn-sm fw-semibold" wire:click="$set('showBudgetModal', false)">Batal</button>
                        <button type="submit" class="btn btn-success btn-sm fw-semibold">Simpan Anggaran</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endteleport
    @endif

    <!-- Modal Atur Tahap & Bobot (Pengawas / Admin) -->
    @if ($showStageModal)
    @teleport('body')
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog" x-on:keydown.escape.window="$wire.set('showStageModal', false)">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title font-outfit fw-bold">Atur Tahapan &amp; Bobot Pekerjaan</h5>
                    <button type="button" class="btn-close" wire:click="$set('showStageModal', false)" aria-label="Tutup modal"></button>
                </div>
                <form wire:submit="saveStages">
                    <div class="modal-body py-4">
                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3">
                            <p class="text-secondary small mb-0">Total bobot semua tahapan harus berjumlah tepat <strong>100%</strong>.</p>
                            <div class="d-flex gap-2">
                                <button type="button" wire:click="resetDefaultStages" class="btn btn-outline-secondary btn-sm">
                                    Template Default (9 Tahap)
                                </button>
                            </div>
                        </div>

                        @if ($otherHouses->isNotEmpty())
                            <div class="mb-3">
                                <label for="copy-stage-select" class="form-label small fw-bold text-secondary text-uppercase">Salin Tahapan dari Unit Lain</label>
                                <select id="copy-stage-select" wire:model.live="copyStageHouseId" class="form-select form-select-sm">
                                    <option value="">-- Pilih unit untuk disalin --</option>
                                    @foreach ($otherHouses as $oh)
                                        <option value="{{ $oh->id }}">{{ $oh->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        @error('editableStages')
                            <div class="alert alert-danger py-2 small mb-3" role="alert">{{ $message }}</div>
                        @enderror

                        <div class="table-responsive border rounded-3 mb-3">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light small font-geist text-uppercase">
                                    <tr>
                                        <th style="width: 45px;">No.</th>
                                        <th>Nama Tahapan</th>
                                        <th style="width: 140px;" class="text-end">Bobot (%)</th>
                                        <th style="width: 50px;" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $calcTotal = 0; @endphp
                                    @foreach ($editableStages as $idx => $stg)
                                        @php $calcTotal += (float) ($stg['weight'] ?? 0); @endphp
                                        <tr wire:key="edit-stage-row-{{ $idx }}">
                                            <td class="text-secondary small font-mono">{{ $loop->iteration }}</td>
                                            <td>
                                                <input type="text" wire:model="editableStages.{{ $idx }}.name" class="form-control form-control-sm" placeholder="Nama tahapan..." required />
                                                @error("editableStages.{$idx}.name") <span class="text-danger extra-small">{{ $message }}</span> @enderror
                                            </td>
                                            <td>
                                                <div class="input-group input-group-sm">
                                                    <input type="number" step="0.01" min="0.01" max="100" wire:model.blur="editableStages.{{ $idx }}.weight" class="form-control form-control-sm text-end font-mono" placeholder="0" required />
                                                    <span class="input-group-text">%</span>
                                                </div>
                                                @error("editableStages.{$idx}.weight") <span class="text-danger extra-small">{{ $message }}</span> @enderror
                                            </td>
                                            <td class="text-center">
                                                <button type="button" wire:click="removeStageRow({{ $idx }})" class="btn btn-sm btn-link text-danger p-0 text-decoration-none d-inline-flex align-items-center justify-content-center" style="min-width: 36px; min-height: 36px;" title="Hapus baris" aria-label="Hapus tahapan">
                                                    <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/></svg>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light border-top">
                                    <tr>
                                        <td colspan="2" class="fw-bold font-geist text-end">Total Bobot:</td>
                                        <td class="text-end font-mono fw-bold {{ abs($calcTotal - 100.0) <= 0.01 ? 'text-success' : 'text-danger' }}">
                                            {{ number_format($calcTotal, 2, ',', '.') }}%
                                        </td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <button type="button" wire:click="addStageRow" class="btn btn-outline-success btn-sm font-semibold">
                                + Tambah Tahapan
                            </button>
                            <span class="extra-small fw-semibold {{ abs($calcTotal - 100.0) <= 0.01 ? 'text-success' : 'text-danger' }}">
                                {{ abs($calcTotal - 100.0) <= 0.01 ? 'Total bobot tepat 100%' : 'Total bobot harus tepat 100%' }}
                            </span>
                        </div>
                    </div>
                    <div class="modal-footer border-top bg-body-tertiary rounded-bottom-4">
                        <button type="button" class="btn btn-secondary btn-sm fw-semibold" wire:click="$set('showStageModal', false)">Batal</button>
                        <button type="submit" class="btn btn-success btn-sm fw-semibold">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endteleport
    @endif
    @endunless
</div>

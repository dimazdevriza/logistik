<div>
    <div class="container-fluid p-0">
        @if (session()->has('success') || session()->has('budget_success'))
            <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4 d-flex align-items-center gap-2" role="alert">
                <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0m-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/></svg>
                <span>{{ session('success') ?? session('budget_success') }}</span>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
            </div>
        @endif

        <!-- Hero Header -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-body-tertiary">
            <div class="card-body p-4 p-md-5 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <div>
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <a href="{{ $house->isPublicWork() && $house->cluster_id ? route('clusters.expenses', $house->cluster_id) : route(auth()->user()->canAccessAllClusters() ? 'admin.house-costs' : 'logistik.house-costs') }}" wire:navigate class="back-link me-2">
                            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8"/></svg>
                            Kembali
                        </a>

                        <span class="badge bg-body-secondary text-secondary font-mono border px-2 py-1 extra-small">{{ $house->house_code }}</span>

                        <span class="badge bg-body-secondary text-body border px-2.5 py-1 extra-small">
                            <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor" class="me-1" aria-hidden="true"><path d="M8.707 1.5a1 1 0 0 0-1.414 0L.646 8.146a.5.5 0 0 0 .708.708L2 8.207V13.5A1.5 1.5 0 0 0 3.5 15h9a1.5 1.5 0 0 0 1.5-1.5V8.207l.646.647a.5.5 0 0 0 .708-.708L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293zM13 7.207V13.5a.5.5 0 0 1-.5.5h-9a.5.5 0 0 1-.5-.5V7.207l5-5z"/></svg>
                            {{ $house->cluster?->name ?? 'Tanpa Cluster' }}
                        </span>

                        @php
                            $statusClasses = [
                                'perencanaan' => 'bg-info-subtle text-info border border-info-subtle',
                                'pembangunan' => 'bg-warning-subtle text-warning border border-warning-subtle',
                                'selesai' => 'bg-success-subtle text-success border border-success-subtle',
                            ];
                        @endphp
                        <span class="badge {{ $statusClasses[$house->status] ?? 'bg-secondary-subtle text-secondary' }} px-2.5 py-1 text-uppercase extra-small fw-bold">
                            {{ ucfirst($house->status) }}
                        </span>

                        @if ($house->start_date || $house->target_end_date)
                            <span class="text-secondary extra-small font-mono d-inline-flex align-items-center gap-1 ms-1">
                                <svg width="12" height="12" fill="currentColor" viewBox="0 0 16 16"><path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4z"/></svg>
                                {{ $house->start_date?->format('d/m/Y') ?? '-' }} &bull; {{ $house->target_end_date?->format('d/m/Y') ?? '-' }}
                            </span>
                        @endif
                    </div>
                    <h1 class="display-5 fw-black text-body mb-1 font-outfit">{{ $house->name }}{{ $house->type ? ' — ' . $house->type : '' }}</h1>
                    <p class="text-secondary mb-0">Detail pengeluaran dan rincian alokasi material pembangunan.</p>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2">
                    <a href="{{ route('logistik.house-detail', $house->house_code) }}" wire:navigate class="btn btn-outline-secondary font-semibold d-inline-flex align-items-center gap-1.5" style="min-height: 44px;">
                        <svg width="15" height="15" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                            <path d="M14 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1zM2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z"/>
                            <path d="M10.97 4.97a.75.75 0 0 1 1.071 1.05l-3.992 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425z"/>
                        </svg>
                        <span>{{ $house->isPublicWork() ? 'Detail Lapangan' : 'Progres & Lapangan' }}</span>
                    </a>

                    <button type="button" wire:click="exportExcel" class="btn btn-utility font-semibold d-inline-flex align-items-center gap-1.5" style="min-height: 44px;">
                        <svg width="15" height="15" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                            <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5"/>
                            <path d="M7.646 1.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 2.707V10.5a.5.5 0 0 1-1 0V2.707L5.354 4.854a.5.5 0 1 1-.708-.708z"/>
                        </svg>
                        <span>Ekspor Excel</span>
                    </button>
                </div>
            </div>
        </div>

        @php
            $budget = $house->material_budget;
            $bobotRumah = $house->bobot_rumah;
            $bobotBahan = $house->bobot_bahan;
            $isOverBudget = $bobotBahan !== null && $bobotBahan > 100;
            $canManageBudget = in_array(auth()->user()->role, ['admin', 'logistik'], true) && ! $house->isPublicWork();
        @endphp

        <!-- Summary KPI Cards -->
        @if ($house->isPublicWork())
            <div class="row g-4 mb-4 house-cost-detail-summary">
                <div class="col-md-6">
                    <div class="card border-0 border-start border-4 border-warning shadow-sm rounded-4 p-4 bg-body-tertiary">
                        <span class="small fw-bold text-secondary text-uppercase tracking-wider mb-2 d-block">Total Biaya Material</span>
                        <h2 class="fw-black text-warning font-mono mb-0">Rp {{ number_format($totalCost, 0, ',', '.') }}</h2>
                        <span class="small text-secondary mt-2">Dari {{ $totalUsageCount }} transaksi penggunaan material.</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 border-start border-4 border-danger shadow-sm rounded-4 p-4 bg-body-tertiary">
                        <span class="small fw-bold text-secondary text-uppercase tracking-wider mb-2 d-block">Kerugian Material</span>
                        <h2 class="fw-black text-danger font-mono mb-0">Rp {{ number_format((float) $totalMaterialLoss, 0, ',', '.') }}</h2>
                        <span class="small text-secondary mt-2">Rusak dan hilang; terpisah dari biaya material layak pakai.</span>
                    </div>
                </div>
            </div>
        @else
            <div class="row g-3 mb-4 house-cost-detail-summary">
                <!-- Card 1: Anggaran Material (RAP) -->
                <div class="col-sm-6 col-xl-3">
                    <div class="card border-0 border-start border-4 border-info shadow-sm rounded-4 p-3 ps-3.5 pe-3 bg-body-tertiary h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-1 gap-1">
                                <span class="extra-small fw-bold text-secondary text-uppercase tracking-wider text-truncate">Pagu RAP</span>
                                @if ($canManageBudget)
                                    <button type="button" wire:click="openBudgetModal" class="btn btn-link p-0 text-decoration-none extra-small fw-semibold text-info d-inline-flex align-items-center gap-1 flex-shrink-0" title="Atur Anggaran Material">
                                        <svg width="12" height="12" fill="currentColor" viewBox="0 0 16 16"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325"/></svg>
                                        {{ $budget ? 'Ubah' : 'Atur' }}
                                    </button>
                                @endif
                            </div>
                            @if ($budget)
                                <h3 class="fs-4 fw-black text-body font-mono mb-1 text-truncate">Rp {{ number_format($budget, 0, ',', '.') }}</h3>
                                <span class="extra-small text-secondary">Pagu rencana biaya material</span>
                            @else
                                <h3 class="fw-bold text-secondary font-mono mb-1 small fst-italic">Belum diatur</h3>
                                <span class="extra-small text-secondary">Klik "Atur" untuk menentukan pagu</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Card 2: Realisasi Biaya Material -->
                <div class="col-sm-6 col-xl-3">
                    <div class="card border-0 border-start border-4 {{ $isOverBudget ? 'border-danger' : 'border-warning' }} shadow-sm rounded-4 p-3 ps-3.5 pe-3 bg-body-tertiary h-100 d-flex flex-column justify-content-between">
                        <div>
                            <span class="extra-small fw-bold text-secondary text-uppercase tracking-wider mb-1 d-block text-truncate">Realisasi Biaya</span>
                            <h3 class="fs-4 fw-black {{ $isOverBudget ? 'text-danger' : 'text-warning' }} font-mono mb-1 text-truncate">Rp {{ number_format($totalCost, 0, ',', '.') }}</h3>
                            @if ($budget)
                                @php $diff = $budget - $totalCost; @endphp
                                @if ($diff >= 0)
                                    <span class="extra-small text-success fw-medium font-mono text-truncate d-block">Sisa: Rp {{ number_format($diff, 0, ',', '.') }}</span>
                                @else
                                    <span class="extra-small text-danger fw-bold font-mono text-truncate d-block">Defisit: +Rp {{ number_format(abs($diff), 0, ',', '.') }}</span>
                                @endif
                            @else
                                <span class="extra-small text-secondary font-mono text-truncate d-block">{{ $totalUsageCount }} transaksi pemakaian</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Card 3: Bobot Realisasi (Fisik & Bahan) -->
                <div class="col-sm-6 col-xl-3">
                    <div class="card border-0 border-start border-4 border-success shadow-sm rounded-4 p-3 ps-3.5 pe-3 bg-body-tertiary h-100 d-flex flex-column justify-content-between">
                        <div>
                            <span class="extra-small fw-bold text-secondary text-uppercase tracking-wider mb-2 d-block text-truncate">Bobot (Fisik / Bahan)</span>
                            
                            <!-- Fisik -->
                            <div class="d-flex justify-content-between align-items-center extra-small mb-1">
                                <span class="text-secondary">Progres Fisik:</span>
                                <span class="font-mono fw-bold text-body">{{ number_format($bobotRumah, 1, ',', '.') }}%</span>
                            </div>
                            <div class="progress bg-body mb-2" style="height: 5px;">
                                <div class="progress-bar bg-success" style="width: {{ min(100, $bobotRumah) }}%;"></div>
                            </div>

                            <!-- Bahan -->
                            <div class="d-flex justify-content-between align-items-center extra-small mb-1">
                                <span class="text-secondary">Pagu Bahan:</span>
                                @if ($bobotBahan !== null)
                                    <span class="font-mono fw-bold {{ $isOverBudget ? 'text-danger' : 'text-body' }}">{{ number_format($bobotBahan, 1, ',', '.') }}%</span>
                                @else
                                    <span class="text-secondary italic">-</span>
                                @endif
                            </div>
                            <div class="progress bg-body" style="height: 5px;">
                                @if ($bobotBahan !== null)
                                    <div class="progress-bar {{ $isOverBudget ? 'bg-danger' : 'bg-success' }}" style="width: {{ min(100, $bobotBahan) }}%;"></div>
                                @endif
                            </div>
                        </div>

                        @if ($isOverBudget)
                            <div class="extra-small text-danger fw-semibold mt-2 pt-1 border-top border-danger-subtle d-flex align-items-center gap-1">
                                <svg width="12" height="12" fill="currentColor" viewBox="0 0 16 16"><path d="M7.938 2.016A.13.13 0 0 1 8.002 2a.13.13 0 0 1 .063.016.15.15 0 0 1 .054.057l6.857 11.667c.036.06.035.124.002.183a.2.2 0 0 1-.054.06.1.1 0 0 1-.066.017H1.146a.1.1 0 0 1-.066-.017.2.2 0 0 1-.054-.06.18.18 0 0 1 .002-.183L7.884 2.073a.15.15 0 0 1 .054-.057m1.044-.45a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767z"/><path d="M7.002 12a1 1 0 1 1 2 0 1 1 0 0 1-2 0M7.1 5.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0z"/></svg>
                                Biaya melebihi RAP
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Card 4: Kerugian Material -->
                <div class="col-sm-6 col-xl-3">
                    <div class="card border-0 border-start border-4 border-danger shadow-sm rounded-4 p-3 ps-3.5 pe-3 bg-body-tertiary h-100 d-flex flex-column justify-content-between">
                        <div>
                            <span class="extra-small fw-bold text-secondary text-uppercase tracking-wider mb-1 d-block text-truncate">Kerugian Material</span>
                            <h3 class="fs-4 fw-black text-danger font-mono mb-1 text-truncate">Rp {{ number_format((float) $totalMaterialLoss, 0, ',', '.') }}</h3>
                            <span class="extra-small text-secondary">Rusak & hilang (terpisah)</span>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Cost by Category Breakdown -->
        @if ($costByCategory->count() > 0)
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-body-tertiary">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3">
                <div>
                    <h5 class="fw-bold font-outfit mb-0">Biaya per Kategori Material</h5>
                    <span class="text-secondary extra-small">Proporsi pengeluaran berdasarkan kelompok material</span>
                </div>
                @if ($selectedCategory)
                    <button type="button" wire:click="$set('selectedCategory', '')" class="btn btn-sm btn-outline-secondary extra-small font-semibold">
                        Tampilkan Semua Kategori
                    </button>
                @endif
            </div>

            <div class="row g-3">
                @foreach ($costByCategory as $cat)
                    @php
                        $catTotal = (float) $cat->total;
                        $share = $totalCost > 0 ? round(($catTotal / $totalCost) * 100, 1) : 0;
                        $isSelected = (string) $selectedCategory === (string) $cat->id;
                    @endphp
                    <div class="col-md-6 col-xl-4">
                        <div
                            class="p-3 rounded-3 border transition-colors {{ $isSelected ? 'border-success bg-success-subtle bg-opacity-25' : 'border-subtle bg-body' }}"
                            style="cursor: pointer;"
                            wire:click="$set('selectedCategory', '{{ $isSelected ? '' : $cat->id }}')"
                            title="Klik untuk memfilter tabel"
                        >
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-semibold text-body extra-small">{{ $cat->category_name }}</span>
                                <span class="font-mono fw-bold text-body small">Rp {{ number_format($catTotal, 0, ',', '.') }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center extra-small text-secondary mb-1.5">
                                <span>{{ $cat->item_count }} alokasi</span>
                                <span class="font-mono fw-semibold text-body">{{ $share }}%</span>
                            </div>
                            <div class="progress bg-body-tertiary" style="height: 5px;">
                                <div class="progress-bar {{ $isSelected ? 'bg-success' : 'bg-secondary' }}" style="width: {{ $share }}%;"></div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Filter & Material Usage Table -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="p-3 px-4 border-bottom bg-body-tertiary d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-2 flex-grow-1">
                    <div class="position-relative flex-grow-1" style="max-width: 380px;">
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            class="form-control form-control-sm pe-4"
                            placeholder="Cari material, pencatat, atau catatan..."
                            aria-label="Cari data penggunaan material"
                        />
                        @if ($search)
                            <button
                                type="button"
                                wire:click="$set('search', '')"
                                class="btn btn-link text-secondary position-absolute end-0 top-50 translate-middle-y p-1 text-decoration-none"
                                aria-label="Hapus pencarian"
                            >
                                <svg width="12" height="12" fill="currentColor" viewBox="0 0 16 16"><path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708"/></svg>
                            </button>
                        @endif
                    </div>

                    <div style="min-width: 180px;">
                        <select wire:model.live="selectedCategory" class="form-select form-select-sm" aria-label="Filter kategori material">
                            <option value="">Semua Kategori</option>
                            @foreach ($allCategories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if ($search || $selectedCategory)
                        <button type="button" wire:click="resetFilters" class="btn btn-sm btn-link text-secondary text-decoration-none extra-small">
                            Reset Filter
                        </button>
                    @endif
                </div>

                <div class="text-secondary extra-small font-mono text-nowrap ms-md-auto">
                    Menampilkan {{ $materialUsages->total() }} catatan alokasi
                </div>
            </div>

            <div class="table-responsive house-cost-detail-table-wrap">
                <table class="table table-hover align-middle mb-0 house-cost-detail-table">
                    <thead class="table-light text-uppercase small font-geist">
                        <tr>
                            <x-sortable-th field="date" :sort="$sort">Tanggal</x-sortable-th>
                            <x-sortable-th field="material" :sort="$sort">Material</x-sortable-th>
                            <x-sortable-th field="quantity" :sort="$sort" class="text-end">Qty</x-sortable-th>
                            <x-sortable-th field="unit_price" :sort="$sort" class="text-end">Harga Satuan</x-sortable-th>
                            <x-sortable-th field="total" :sort="$sort" class="text-end">Total</x-sortable-th>
                            <x-sortable-th field="user" :sort="$sort">Dicatat oleh</x-sortable-th>
                            <x-sortable-th field="notes" :sort="$sort">Catatan</x-sortable-th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($materialUsages as $usage)
                        <tr wire:key="h-cost-det-{{ $usage->id }}">
                            <td data-label="Tanggal" class="font-mono text-secondary small"><span class="house-cost-detail-cell-value">{{ $usage->usage_date->format('d/m/Y') }}</span></td>
                            <td data-label="Material">
                                <span class="fw-bold text-body d-block">{{ $usage->material->name }}</span>
                                @if ($usage->material->category)
                                    <span class="badge bg-body-secondary text-secondary extra-small border">{{ $usage->material->category->name }}</span>
                                @endif
                            </td>
                            <td data-label="Qty" class="text-end fw-bold font-mono"><span class="house-cost-detail-cell-value">{{ str_replace('.', ',', (float) $usage->quantity) }} <span class="text-secondary small font-normal">{{ $usage->material->unit }}</span></span></td>
                            <td data-label="Harga satuan" class="text-end font-mono text-secondary"><span class="house-cost-detail-cell-value">Rp {{ number_format($usage->unit_price_at_usage, 0, ',', '.') }}</span></td>
                            <td data-label="Total" class="text-end font-mono fw-bold text-warning"><span class="house-cost-detail-cell-value">Rp {{ number_format($usage->total_cost, 0, ',', '.') }}</span></td>
                            <td data-label="Dicatat oleh" class="text-secondary small"><span class="house-cost-detail-cell-value">{{ $usage->user->name }}</span></td>
                            <td data-label="Catatan" class="text-secondary small"><span class="house-cost-detail-cell-value">{{ $usage->notes ?? '-' }}</span></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="house-cost-detail-empty text-center py-4 text-secondary">
                                @if ($search || $selectedCategory)
                                    Tidak ada data alokasi material yang cocok dengan filter yang dipilih.
                                @else
                                    Belum ada penggunaan material untuk {{ $house->isPublicWork() ? 'pekerjaan umum' : 'rumah' }} ini.
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="d-flex justify-content-end mb-5">{{ $materialUsages->links() }}</div>

        <!-- Material Loss Section -->
        <section aria-labelledby="material-loss-title">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-2 mb-3">
                <div>
                    <h2 id="material-loss-title" class="h4 fw-bold mb-1 font-outfit">Kerugian Material</h2>
                    <p class="text-secondary small mb-0">Nilai kerusakan dan kehilangan tercatat terpisah dari biaya material layak pakai.</p>
                </div>
                <div class="font-mono fw-bold text-danger">Total: Rp {{ number_format((float) $totalMaterialLoss, 0, ',', '.') }}</div>
            </div>
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-body-tertiary">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-uppercase small">
                            <tr><th>Tanggal</th><th>Kode pengiriman / batch</th><th>Material</th><th class="text-end">Qty</th><th class="text-end">Nilai</th><th>Dicatat oleh</th><th>Alasan</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($materialLosses as $loss)
                                <tr wire:key="material-loss-{{ $loss->id }}">
                                    <td class="font-mono text-secondary small">{{ $loss->recorded_at->format('d/m/Y H:i') }}</td>
                                    <td class="small"><span class="d-block">{{ $loss->request->dispatch_code ?? $loss->request->request_code }}</span><span class="text-secondary">{{ $loss->dispatchLine?->stockIn?->entry_code ?? 'Batch warisan' }}</span></td>
                                    <td class="fw-semibold">{{ $loss->request->material?->name ?? 'Material dihapus' }}</td>
                                    <td class="text-end font-mono">{{ number_format((float) $loss->quantity, 2, ',', '.') }}</td>
                                    <td class="text-end font-mono fw-semibold text-danger">Rp {{ number_format((float) $loss->total_cost, 0, ',', '.') }}</td>
                                    <td class="small">{{ $loss->recordedBy?->name ?? 'Pengguna dihapus' }}</td>
                                    <td class="small">{{ $loss->notes }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center py-4 text-secondary">Belum ada kerugian material yang tercatat.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="d-flex justify-content-end mt-2">{{ $materialLosses->links() }}</div>
        </section>
    </div>

    <!-- Modal: Atur Anggaran Material (RAP) -->
    @if ($showBudgetModal)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title font-outfit fw-bold">Anggaran Material (RAP)</h5>
                        <button type="button" class="btn-close" wire:click="$set('showBudgetModal', false)" aria-label="Tutup"></button>
                    </div>
                    <form wire:submit.prevent="saveMaterialBudget">
                        <div class="modal-body py-4">
                            @if ($clusterHouses->isNotEmpty())
                                <div class="mb-3">
                                    <label for="copy-cost-budget-house" class="form-label font-semibold extra-small text-secondary">Salin RAP dari unit lain di cluster:</label>
                                    <select id="copy-cost-budget-house" wire:model.live="copyBudgetHouseId" class="form-select form-select-sm">
                                        <option value="">Pilih unit...</option>
                                        @foreach ($clusterHouses as $ch)
                                            <option value="{{ $ch->id }}">{{ $ch->name }} (Rp {{ number_format($ch->material_budget, 0, ',', '.') }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            <div class="mb-2">
                                <label for="cost-budget-amount" class="form-label font-semibold">Total Anggaran (Rp)</label>
                                <div class="input-group">
                                    <span class="input-group-text font-mono">Rp</span>
                                    <input
                                        id="cost-budget-amount"
                                        type="number"
                                        step="any"
                                        min="0"
                                        wire:model="budgetAmount"
                                        class="form-control font-mono"
                                        placeholder="0"
                                        autofocus
                                    />
                                </div>
                                @error('budgetAmount') <span class="text-danger small mt-1 d-block" role="alert">{{ $message }}</span> @enderror
                                <div class="form-text">Kosongkan jika rumah tidak memiliki batasan pagu material.</div>
                            </div>
                        </div>
                        <div class="modal-footer border-top bg-body-tertiary">
                            <button type="button" class="btn btn-outline-secondary" wire:click="$set('showBudgetModal', false)" style="min-height: 44px;">Batal</button>
                            <button type="submit" class="btn btn-success font-semibold px-4" style="min-height: 44px;">Simpan RAP</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>

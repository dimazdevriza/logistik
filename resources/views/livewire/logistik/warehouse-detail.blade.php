<div>
    <div class="container-fluid p-0">
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-body-tertiary">
            <div class="card-body p-4 p-md-5 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <div>
                    <a href="{{ route('logistik.warehouses') }}" wire:navigate class="back-link mb-3"><svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8"/></svg>Kembali ke Gudang</a>
                    <h1 class="display-5 fw-black text-body mb-2 font-outfit">{{ $warehouse->name }}</h1>
                    <p class="text-secondary mb-0">{{ $warehouse->address ?: 'Alamat gudang belum dicatat.' }}</p>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <button type="button" wire:click="exportExcel" wire:loading.attr="disabled" wire:target="exportExcel" class="btn btn-utility font-semibold">
                        <svg width="15" height="15" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                            <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5"/>
                            <path d="M7.646 1.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 2.707V10.5a.5.5 0 0 1-1 0V2.707L5.354 4.854a.5.5 0 1 1-.708-.708z"/>
                        </svg>
                        <span>Ekspor Excel</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card border-0 border-start border-4 border-success shadow-sm rounded-4 p-4 bg-body-tertiary h-100">
                    <div class="small fw-bold text-secondary text-uppercase tracking-wider mb-2">Material di gudang</div>
                    <div class="d-flex align-items-end gap-2">
                        <h2 class="fw-black text-success mb-0 font-mono">{{ $materialCount }}</h2>
                        <span class="text-body-secondary mb-1">baris stok</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 border-start border-4 border-warning shadow-sm rounded-4 p-4 bg-body-tertiary h-100">
                    <div class="small fw-bold text-secondary text-uppercase tracking-wider mb-2">Total biaya material</div>
                    <h2 class="fs-5 fw-black text-warning mb-1 font-mono text-nowrap">Rp {{ number_format($materialValue, 0, ',', '.') }}</h2>
                    <span class="text-secondary small">Nilai stok material saat ini</span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 border-start border-4 border-primary shadow-sm rounded-4 p-4 bg-body-tertiary h-100">
                    <div class="small fw-bold text-secondary text-uppercase tracking-wider mb-2">Alat tersimpan</div>
                    <div class="d-flex align-items-end gap-2">
                        <h2 class="fw-black text-primary mb-0 font-mono">{{ $toolCount }}</h2>
                        <span class="text-secondary mb-1">jenis · {{ (int) $toolAvailable }}/{{ (int) $toolTotal }} unit tersedia</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 p-4 bg-body-tertiary data-table-card">
            <div wire:loading.delay class="data-table-status" role="status">Memuat inventaris gudang...</div>
            <div wire:offline class="data-table-status is-error" role="alert">Koneksi terputus. Data mungkin tidak terbaru.</div>
            <div class="house-log-period-tabs" role="group" aria-label="Inventaris gudang">
                <button type="button" class="house-log-period-tab {{ $activeTab === 'material' ? 'active' : '' }}" aria-pressed="{{ $activeTab === 'material' ? 'true' : 'false' }}" wire:click="$set('activeTab', 'material')">
                    <span>Material</span>
                    <span class="house-log-period-count">{{ $materialCount }}</span>
                </button>
                <button type="button" class="house-log-period-tab {{ $activeTab === 'tool' ? 'active' : '' }}" aria-pressed="{{ $activeTab === 'tool' ? 'true' : 'false' }}" wire:click="$set('activeTab', 'tool')">
                    <span>Alat</span>
                    <span class="house-log-period-count">{{ $toolCount }}</span>
                </button>
            </div>

            @if ($activeTab === 'material')
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                    <h5 class="fw-bold mb-0 font-outfit">Daftar material gudang</h5>
                    <div class="flex-grow-1" style="max-width: 340px;">
                        <input type="search" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Cari material, kode, atau kategori..." aria-label="Cari material berdasarkan nama, kode, atau kategori" autocomplete="off">
                    </div>
                </div>
                @if ($materials->isEmpty())
                    <div class="text-center py-5 text-body-secondary">{{ trim($search) !== '' ? 'Tidak ada material yang cocok dengan pencarian.' : 'Belum ada material di gudang ini.' }}</div>
                @else
                <div class="table-responsive mb-3 house-detail-log-table-scroll standard-table-frame" tabindex="0" role="region" aria-label="Material di {{ $warehouse->name }}">
                    <table class="table table-hover align-middle mb-0 house-detail-material-table standard-data-table">
                        <thead class="table-light text-uppercase small font-geist">
                            <tr>
                                <th class="text-center" style="width: 50px;">No.</th>
                                <x-sortable-th field="code" :sort="$sort">Kode</x-sortable-th>
                                <x-sortable-th field="name" :sort="$sort">Material</x-sortable-th>
                                <x-sortable-th field="category" :sort="$sort">Kategori</x-sortable-th>
                                <x-sortable-th field="stock" :sort="$sort" class="text-end">Stok</x-sortable-th>
                                <x-sortable-th field="unit" :sort="$sort">Satuan</x-sortable-th>
                                <x-sortable-th field="unit_price" :sort="$sort" class="text-end">Harga Satuan</x-sortable-th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($materials as $material)
                                <tr wire:key="warehouse-material-{{ $material->id }}">
                                    <td class="text-center text-secondary small data-mobile-secondary">{{ ($materials->currentPage() - 1) * $materials->perPage() + $loop->iteration }}</td>
                                    <td data-label="Kode" class="font-mono text-secondary small data-key-code" title="{{ $material->code ?: 'Kode tidak tersedia' }}"><span class="house-detail-cell-value">{{ $material->code ?: '-' }}</span></td>
                                    <td data-label="Material" class="fw-bold text-body data-key-name" title="{{ $material->name }}"><span class="house-detail-cell-value">{{ $material->name }}</span></td>
                                    <td data-label="Kategori" class="text-secondary small data-cell-truncate" title="{{ $material->category?->name ?: 'Kategori tidak tersedia' }}"><span class="house-detail-cell-value">{{ $material->category?->name ?: '-' }}</span></td>
                                    <td data-label="Stok" class="text-end fw-semibold data-number"><span class="house-detail-cell-value">{{ rtrim(rtrim(number_format((float) $material->stock, 2, ',', '.'), '0'), ',') }}</span></td>
                                    <td data-label="Satuan" class="text-secondary small"><span class="house-detail-cell-value">{{ $material->unit }}</span></td>
                                    <td data-label="Harga Satuan" class="text-end font-mono text-secondary data-number"><span class="house-detail-cell-value">Rp {{ number_format($material->unit_price, 0, ',', '.') }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
                <div class="d-flex justify-content-end mt-3">{{ $materials->links() }}</div>
            @endif

            @if ($activeTab === 'tool')
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                    <h5 class="fw-bold mb-0 font-outfit">Daftar alat gudang</h5>
                    <div class="flex-grow-1" style="max-width: 340px;">
                        <input type="search" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Cari alat, kode, atau kategori..." aria-label="Cari alat berdasarkan nama, kode, kategori, atau kondisi" autocomplete="off">
                    </div>
                </div>
                @if ($tools->isEmpty())
                    <div class="text-center py-5 text-body-secondary">{{ trim($search) !== '' ? 'Tidak ada alat yang cocok dengan pencarian.' : 'Belum ada alat di gudang ini.' }}</div>
                @else
                <div class="table-responsive mb-3 house-detail-log-table-scroll standard-table-frame" tabindex="0" role="region" aria-label="Alat di {{ $warehouse->name }}">
                    <table class="table table-hover align-middle mb-0 house-detail-tool-table standard-data-table">
                        <thead class="table-light text-uppercase small font-geist">
                            <tr>
                                <th class="text-center" style="width: 50px;">No.</th>
                                <x-sortable-th field="code" :sort="$sort">Kode</x-sortable-th>
                                <x-sortable-th field="name" :sort="$sort">Alat</x-sortable-th>
                                <x-sortable-th field="category" :sort="$sort">Kategori</x-sortable-th>
                                <x-sortable-th field="condition" :sort="$sort">Kondisi</x-sortable-th>
                                <x-sortable-th field="available" :sort="$sort" class="text-end">Tersedia</x-sortable-th>
                                <x-sortable-th field="total" :sort="$sort" class="text-end">Total</x-sortable-th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tools as $tool)
                                <tr wire:key="warehouse-tool-{{ $tool->id }}">
                                    <td class="text-center text-secondary small data-mobile-secondary">{{ ($tools->currentPage() - 1) * $tools->perPage() + $loop->iteration }}</td>
                                    <td data-label="Kode" class="font-mono text-secondary small data-key-code" title="{{ $tool->code }}"><span class="house-detail-cell-value">{{ $tool->code }}</span></td>
                                    <td data-label="Alat" class="fw-bold text-body data-key-name" title="{{ $tool->name }}"><span class="house-detail-cell-value">{{ $tool->name }}</span></td>
                                    <td data-label="Kategori" class="text-secondary small data-cell-truncate" title="{{ $tool->category?->name ?: 'Kategori tidak tersedia' }}"><span class="house-detail-cell-value">{{ $tool->category?->name ?: '-' }}</span></td>
                                    <td data-label="Kondisi"><span class="house-detail-cell-value"><span class="badge bg-secondary-subtle text-secondary">{{ ucfirst($tool->condition) }}</span></span></td>
                                    <td data-label="Tersedia" class="text-end fw-semibold data-number"><span class="house-detail-cell-value">{{ $tool->warehouse_available_qty }}</span></td>
                                    <td data-label="Total" class="text-end text-secondary data-number"><span class="house-detail-cell-value">{{ $tool->warehouse_available_qty + $tool->warehouse_broken_qty }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
                <div class="d-flex justify-content-end mt-3">{{ $tools->links() }}</div>
            @endif
        </div>
    </div>
</div>

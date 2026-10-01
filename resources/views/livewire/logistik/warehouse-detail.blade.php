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
            <div class="col-md-6">
                <div class="card border-0 border-start border-4 border-success shadow-sm rounded-4 p-4 bg-body-tertiary h-100">
                    <div class="small fw-bold text-secondary text-uppercase tracking-wider mb-2">Material di gudang</div>
                    <div class="d-flex align-items-end gap-2">
                        <h2 class="fw-black text-success mb-0 font-mono">{{ $materialCount }}</h2>
                        <span class="text-body-secondary mb-1">baris stok</span>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
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
            <ul class="nav nav-tabs mb-4">
                <li class="nav-item">
                    <button type="button" class="nav-link font-semibold" :class="$wire.activeTab === 'material' ? 'active text-success border-success' : 'text-secondary'" wire:click="$set('activeTab', 'material')">
                        Material ({{ $materialCount }})
                    </button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link font-semibold" :class="$wire.activeTab === 'tool' ? 'active text-success border-success' : 'text-secondary'" wire:click="$set('activeTab', 'tool')">
                        Alat ({{ $toolCount }})
                    </button>
                </li>
            </ul>

            @if ($activeTab === 'material')
                @if ($materials->isEmpty())
                    <div class="text-center py-5 text-body-secondary">Belum ada material di gudang ini.</div>
                @else
                <div class="table-responsive data-table-scroll" tabindex="0" role="region" aria-label="Material di {{ $warehouse->name }}">
                    <table class="table table-hover align-middle mb-0 data-table data-table--detail data-table--sticky-identity">
                        <thead class="table-light text-uppercase small font-geist">
                            <tr>
                                <th class="text-center" style="width: 60px;">No.</th>
                                <x-sortable-th field="code" :sort="$sort" class="data-key-code">Kode</x-sortable-th>
                                <x-sortable-th field="name" :sort="$sort" class="data-key-name">Material</x-sortable-th>
                                <x-sortable-th field="category" :sort="$sort">Kategori</x-sortable-th>
                                <x-sortable-th field="stock" :sort="$sort" class="text-end data-number">Stok</x-sortable-th>
                                <x-sortable-th field="unit" :sort="$sort">Satuan</x-sortable-th>
                                <x-sortable-th field="unit_price" :sort="$sort" class="text-end data-number">Harga Satuan</x-sortable-th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($materials as $material)
                                <tr wire:key="warehouse-material-{{ $material->id }}">
                                    <td class="text-center text-secondary small">{{ ($materials->currentPage() - 1) * $materials->perPage() + $loop->iteration }}</td>
                                    <td class="font-mono text-secondary small data-key-code" title="{{ $material->code ?: 'Kode tidak tersedia' }}">{{ $material->code ?: '-' }}</td>
                                    <td class="fw-bold text-body data-key-name" title="{{ $material->name }}">{{ $material->name }}</td>
                                    <td class="text-secondary small data-cell-truncate" title="{{ $material->category?->name ?: 'Kategori tidak tersedia' }}">{{ $material->category?->name ?: '-' }}</td>
                                    <td class="text-end fw-semibold data-number">{{ rtrim(rtrim(number_format((float) $material->stock, 2, ',', '.'), '0'), ',') }}</td>
                                    <td class="text-secondary small">{{ $material->unit }}</td>
                                    <td class="text-end font-mono text-secondary data-number">Rp {{ number_format($material->unit_price, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
                <div class="d-flex justify-content-end mt-3">{{ $materials->links() }}</div>
            @endif

            @if ($activeTab === 'tool')
                @if ($tools->isEmpty())
                    <div class="text-center py-5 text-body-secondary">Belum ada alat di gudang ini.</div>
                @else
                <div class="table-responsive data-table-scroll" tabindex="0" role="region" aria-label="Alat di {{ $warehouse->name }}">
                    <table class="table table-hover align-middle mb-0 data-table data-table--detail data-table--sticky-identity">
                        <thead class="table-light text-uppercase small font-geist">
                            <tr>
                                <th class="text-center" style="width: 60px;">No.</th>
                                <x-sortable-th field="code" :sort="$sort" class="data-key-code">Kode</x-sortable-th>
                                <x-sortable-th field="name" :sort="$sort" class="data-key-name">Alat</x-sortable-th>
                                <x-sortable-th field="category" :sort="$sort">Kategori</x-sortable-th>
                                <x-sortable-th field="condition" :sort="$sort">Kondisi</x-sortable-th>
                                <x-sortable-th field="available" :sort="$sort" class="text-end data-number">Tersedia</x-sortable-th>
                                <x-sortable-th field="total" :sort="$sort" class="text-end data-number">Total</x-sortable-th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tools as $tool)
                                <tr wire:key="warehouse-tool-{{ $tool->id }}">
                                    <td class="text-center text-secondary small">{{ ($tools->currentPage() - 1) * $tools->perPage() + $loop->iteration }}</td>
                                    <td class="font-mono text-secondary small data-key-code" title="{{ $tool->code }}">{{ $tool->code }}</td>
                                    <td class="fw-bold text-body data-key-name" title="{{ $tool->name }}">{{ $tool->name }}</td>
                                    <td class="text-secondary small data-cell-truncate" title="{{ $tool->category?->name ?: 'Kategori tidak tersedia' }}">{{ $tool->category?->name ?: '-' }}</td>
                                    <td><span class="badge bg-secondary-subtle text-secondary">{{ ucfirst($tool->condition) }}</span></td>
                                    <td class="text-end fw-semibold data-number">{{ $tool->warehouse_available_qty }}</td>
                                    <td class="text-end text-secondary data-number">{{ $tool->warehouse_available_qty + $tool->warehouse_broken_qty }}</td>
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

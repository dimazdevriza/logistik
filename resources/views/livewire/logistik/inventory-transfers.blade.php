<div>
    @php
        $sourceWarehouse = $warehouses->firstWhere('id', (int) $source_warehouse_id);
        $destinationWarehouse = $warehouses->firstWhere('id', (int) $destination_warehouse_id);
        $selectedItem = $items->firstWhere('id', (int) $item_id);
        $selectedBatch = $inventory_type === 'material'
            ? $batches->firstWhere('id', (int) $stock_in_id)
            : null;

        $unitLabel = $inventory_type === 'material'
            ? ($selectedItem?->unit ?? 'satuan')
            : 'unit';

        $maxAvailable = 0.0;
        if ($inventory_type === 'material' && $selectedBatch) {
            $maxAvailable = (float) ($selectedBatch->available_quantity ?? $selectedBatch->remaining_quantity ?? 0);
        } elseif ($inventory_type === 'tool' && $selectedItem) {
            $maxAvailable = (float) ($selectedItem->warehouseBalances->first()?->available_qty ?? 0);
        }

        $parsedQty = is_numeric($quantity) ? (float) $quantity : 0.0;
        $remainingAfter = $maxAvailable - $parsedQty;
        $isOverStock = $selectedItem && ($inventory_type === 'tool' || $selectedBatch) && $parsedQty > ($maxAvailable + 0.0001);
        $estimatedValue = $inventory_type === 'material' && $selectedBatch
            ? $parsedQty * (float) $selectedBatch->unit_price
            : 0.0;
        $canSubmit = $sourceWarehouse
            && $destinationWarehouse
            && $sourceWarehouse->id !== $destinationWarehouse->id
            && $selectedItem
            && ($inventory_type === 'tool' || $selectedBatch)
            && $parsedQty > 0
            && ! $isOverStock
            && filled($transferred_at);

        $formatQty = static fn (float $val): string => rtrim(rtrim(number_format($val, 2, ',', '.'), '0'), ',');
    @endphp

    <div class="container-fluid p-0 pb-4 transfer-workspace">
        <header class="card border-0 shadow-sm rounded-4 mb-4 bg-body-tertiary workflow-page-header">
            <div class="card-body p-4 p-md-5 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <div>
                    <h1 class="display-5 fw-black text-body mb-2 font-outfit">Transfer <span class="text-success">Gudang</span></h1>
                    <p class="text-secondary mb-0">Pindahkan stok material atau alat antargudang mana saja dengan jejak batch dan saldo real-time.</p>
                </div>
                <div class="d-flex flex-wrap flex-lg-nowrap gap-2 flex-shrink-0">
                    <a href="{{ route('logistik.material-log') }}" wire:navigate class="btn btn-outline-secondary fw-semibold d-inline-flex align-items-center gap-2 text-nowrap">
                        <svg width="15" height="15" fill="currentColor" aria-hidden="true"><use href="#i-journal-text"/></svg>
                        <span>Catatan Material</span>
                    </a>
                    <a href="{{ route('logistik.tool-log') }}" wire:navigate class="btn btn-outline-secondary fw-semibold d-inline-flex align-items-center gap-2 text-nowrap">
                        <svg width="15" height="15" fill="currentColor" aria-hidden="true"><use href="#i-journal-check"/></svg>
                        <span>Catatan Alat</span>
                    </a>
                </div>
            </div>
        </header>

        @if (session('success'))
            <div class="alert alert-success d-flex align-items-center justify-content-between gap-3 rounded-3 mb-4" role="status">
                <div>
                    <strong class="me-1">Berhasil dicatat.</strong>
                    <span>{{ session('success') }}</span>
                </div>
                <span class="extra-small font-mono text-secondary">Stok gudang telah diperbarui</span>
            </div>
        @endif

        <div class="transfer-grid mb-4">
            <!-- Form Sheet -->
            <div class="transfer-sheet card border-0 shadow-sm rounded-4 bg-body-tertiary overflow-hidden">
                <!-- STEP 01: Rute Gudang -->
                <section class="transfer-step-block" aria-labelledby="transfer-step-route">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <div class="d-flex align-items-start gap-2">
                            <span class="transfer-step-index" aria-hidden="true">01</span>
                            <div>
                                <h2 id="transfer-step-route" class="h6 fw-bold mb-1">Rute gudang</h2>
                                <p class="small text-secondary mb-0">Pilih gudang asal pengambilan dan gudang tujuan penerima.</p>
                            </div>
                        </div>
                        @if (auth()->user()->warehouse)
                            <span class="badge bg-success-subtle text-success fw-semibold">
                                Gudang tugas Anda: {{ auth()->user()->warehouse->name }}
                            </span>
                        @endif
                    </div>

                    <div class="transfer-route-grid">
                        <div>
                            <label for="transfer-source-warehouse" class="form-label fw-semibold">Gudang asal <span class="text-danger">*</span></label>
                            <select id="transfer-source-warehouse" wire:model.live="source_warehouse_id" class="form-select">
                                <option value="">Pilih gudang asal</option>
                                @foreach ($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                @endforeach
                            </select>
                            @error('source_warehouse_id') <div class="text-danger small mt-1" role="alert">{{ $message }}</div> @enderror
                        </div>

                        <div class="transfer-swap-col">
                            <button
                                type="button"
                                wire:click="swapWarehouses"
                                class="btn btn-outline-secondary transfer-swap-btn"
                                title="Tukar gudang asal dan tujuan"
                                aria-label="Tukar gudang asal dan tujuan"
                                @disabled(! $source_warehouse_id && ! $destination_warehouse_id)
                            >
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M1 11.5a.5.5 0 0 0 .5.5h11.793l-3.147 3.146a.5.5 0 0 0 .708.708l4-4a.5.5 0 0 0 0-.708l-4-4a.5.5 0 0 0-.708.708L13.293 11H1.5a.5.5 0 0 0-.5.5m14-7a.5.5 0 0 1-.5.5H2.707l3.147 3.146a.5.5 0 1 1-.708.708l-4-4a.5.5 0 0 1 0-.708l4-4a.5.5 0 1 1 .708.708L2.707 4H14.5a.5.5 0 0 1 .5.5"/>
                                </svg>
                            </button>
                        </div>

                        <div>
                            <label for="transfer-destination-warehouse" class="form-label fw-semibold">Gudang tujuan <span class="text-danger">*</span></label>
                            <select id="transfer-destination-warehouse" wire:model.live="destination_warehouse_id" class="form-select">
                                <option value="">Pilih gudang tujuan</option>
                                @foreach ($warehouses->where('id', '!=', (int) $source_warehouse_id) as $warehouse)
                                    <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                @endforeach
                            </select>
                            @error('destination_warehouse_id') <div class="text-danger small mt-1" role="alert">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </section>

                <!-- STEP 02: Inventaris & Jumlah -->
                <section class="transfer-step-block border-top" aria-labelledby="transfer-step-item">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <div class="d-flex align-items-start gap-2">
                            <span class="transfer-step-index" aria-hidden="true">02</span>
                            <div>
                                <h2 id="transfer-step-item" class="h6 fw-bold mb-1">Inventaris & jumlah dipindahkan</h2>
                                <p class="small text-secondary mb-0">Pilih item tersedia di gudang asal dan tentukan jumlahnya.</p>
                            </div>
                        </div>

                        <div class="d-inline-flex gap-2 flex-shrink-0" role="group" aria-label="Jenis inventaris">
                            <button
                                type="button"
                                wire:click="setInventoryType('material')"
                                class="btn log-row-action {{ $inventory_type === 'material' ? 'log-row-action--edit' : 'log-row-action--quiet' }}"
                                aria-pressed="{{ $inventory_type === 'material' ? 'true' : 'false' }}"
                            >
                                <svg width="14" height="14" fill="currentColor" class="me-1" aria-hidden="true"><use href="#i-box"/></svg>
                                Material
                            </button>
                            <button
                                type="button"
                                wire:click="setInventoryType('tool')"
                                class="btn log-row-action {{ $inventory_type === 'tool' ? 'log-row-action--edit' : 'log-row-action--quiet' }}"
                                aria-pressed="{{ $inventory_type === 'tool' ? 'true' : 'false' }}"
                            >
                                <svg width="14" height="14" fill="currentColor" class="me-1" aria-hidden="true"><use href="#i-wrench"/></svg>
                                Alat
                            </button>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="{{ $inventory_type === 'material' ? 'col-md-6' : 'col-12' }}">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <label for="transfer-item" class="form-label fw-semibold mb-0">
                                    {{ $inventory_type === 'material' ? 'Material / lot harga' : 'Alat kerja' }} <span class="text-danger">*</span>
                                </label>
                                @if ($sourceWarehouse)
                                    <span class="extra-small font-mono text-secondary">{{ $items->count() }} tersedia di {{ $sourceWarehouse->name }}</span>
                                @endif
                            </div>
                            <select id="transfer-item" wire:model.live="item_id" class="form-select" @disabled(! $source_warehouse_id)>
                                <option value="">
                                    @if (! $source_warehouse_id)
                                        Pilih gudang asal terlebih dahulu
                                    @elseif ($items->isEmpty())
                                        Tidak ada {{ $inventory_type === 'material' ? 'material' : 'alat' }} tersedia di {{ $sourceWarehouse?->name }}
                                    @else
                                        Pilih {{ $inventory_type === 'material' ? 'material / lot harga' : 'alat kerja' }}
                                    @endif
                                </option>
                                @foreach ($items as $item)
                                    <option value="{{ $item->id }}">
                                        {{ $item->name }} · {{ $inventory_type === 'material' ? 'Rp '.number_format((float) $item->unit_price, 0, ',', '.').' · '.$formatQty((float) $item->stock).' '.$item->unit : $item->code.' ('.($item->warehouseBalances->first()?->available_qty ?? 0).'/'.$item->total_qty.' unit)' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('item_id') <div class="text-danger small mt-1" role="alert">{{ $message }}</div> @enderror
                        </div>

                        @if ($inventory_type === 'material')
                            <div class="col-md-6">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <label for="transfer-stock-in" class="form-label fw-semibold mb-0">Batch sumber <span class="text-danger">*</span></label>
                                    @if ($selectedItem)
                                        <span class="extra-small font-mono text-secondary">{{ $batches->count() }} batch aktif</span>
                                    @endif
                                </div>
                                <select id="transfer-stock-in" wire:model.live="stock_in_id" class="form-select" @disabled(! $item_id)>
                                    <option value="">{{ $item_id ? 'Pilih batch sumber' : 'Pilih material terlebih dahulu' }}</option>
                                    @foreach ($batches as $batch)
                                        <option value="{{ $batch->id }}">
                                            {{ $batch->entry_code }} · tersedia {{ $formatQty((float) $batch->available_quantity) }} {{ $selectedItem?->unit }} · Rp {{ number_format((float) $batch->unit_price, 0, ',', '.') }} · {{ $batch->received_at?->format('d/m/Y H:i') ?? ($batch->entry_type === 'transfer' ? 'transfer '.($batch->date?->format('d/m/Y') ?? '') : 'waktu tiba tidak tercatat') }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('stock_in_id') <div class="text-danger small mt-1" role="alert">{{ $message }}</div> @enderror
                            </div>
                        @endif

                        <div class="col-md-6">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <label for="transfer-quantity" class="form-label fw-semibold mb-0">Jumlah <span class="text-danger">*</span></label>
                                @if ($maxAvailable > 0)
                                    <button
                                        type="button"
                                        wire:click="setMaxQuantity({{ $maxAvailable }})"
                                        class="btn btn-link link-success p-0 extra-small font-mono text-decoration-none fw-semibold"
                                        title="Isi seluruh stok tersedia"
                                    >
                                        Maks: {{ $formatQty($maxAvailable) }} {{ $unitLabel }} (Semua)
                                    </button>
                                @endif
                            </div>
                            <div class="input-group">
                                <input
                                    id="transfer-quantity"
                                    type="number"
                                    min="{{ $inventory_type === 'tool' ? '1' : '0.01' }}"
                                    step="{{ $inventory_type === 'tool' ? '1' : '0.01' }}"
                                    wire:model.live="quantity"
                                    class="form-control font-mono {{ $isOverStock ? 'is-invalid' : '' }}"
                                />
                                <span class="input-group-text font-mono text-secondary small">{{ $unitLabel }}</span>
                            </div>
                            @if ($isOverStock)
                                <div class="text-danger small mt-1 fw-semibold" role="alert">
                                    Melebihi stok tersedia ({{ $formatQty($maxAvailable) }} {{ $unitLabel }}).
                                </div>
                            @endif
                            @error('quantity') <div class="text-danger small mt-1" role="alert">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <label for="transfer-date" class="form-label fw-semibold mb-0">Tanggal transfer <span class="text-danger">*</span></label>
                            </div>
                            <input id="transfer-date" type="date" wire:model.live="transferred_at" class="form-control" />
                            @error('transferred_at') <div class="text-danger small mt-1" role="alert">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12">
                            <label for="transfer-notes" class="form-label fw-semibold">Catatan <span class="text-secondary fw-normal">(opsional)</span></label>
                            <input id="transfer-notes" type="text" wire:model="notes" class="form-control" placeholder="Contoh: Pindah stok ke lokasi proyek blok timur" />
                            @error('notes') <div class="text-danger small mt-1" role="alert">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </section>
            </div>

            <!-- Right Summary Slip -->
            <aside class="transfer-summary" aria-labelledby="transfer-summary-title">
                <div class="transfer-summary-sticky card border-0 shadow-sm rounded-4 bg-body-tertiary p-4">
                    <div class="d-flex align-items-start gap-2 pb-3 mb-3 border-bottom">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-success-subtle text-success p-2 flex-shrink-0">
                            <svg width="18" height="18" fill="currentColor" aria-hidden="true"><use href="#i-transfer"/></svg>
                        </span>
                        <div>
                            <h2 id="transfer-summary-title" class="h6 fw-bold mb-1">Dampak transfer</h2>
                            <p class="extra-small text-secondary mb-0">Periksa rute gudang dan sisa saldo sebelum menyimpan.</p>
                        </div>
                    </div>

                    <dl class="transfer-summary-list small mb-0">
                        <div class="d-flex justify-content-between gap-3 py-1">
                            <dt class="text-secondary fw-normal flex-shrink-0">Gudang asal</dt>
                            <dd class="mb-0 fw-bold text-end">{{ $sourceWarehouse?->name ?? 'Belum dipilih' }}</dd>
                        </div>
                        <div class="d-flex justify-content-between gap-3 py-1">
                            <dt class="text-secondary fw-normal flex-shrink-0">Gudang tujuan</dt>
                            <dd class="mb-0 fw-bold text-end {{ $destinationWarehouse ? 'text-success' : '' }}">{{ $destinationWarehouse?->name ?? 'Belum dipilih' }}</dd>
                        </div>
                        <div class="d-flex justify-content-between gap-3 py-1 border-top mt-2 pt-2">
                            <dt class="text-secondary fw-normal flex-shrink-0">{{ $inventory_type === 'material' ? 'Material' : 'Alat' }}</dt>
                            <dd class="mb-0 fw-bold text-end">{{ $selectedItem?->name ?? 'Belum dipilih' }}</dd>
                        </div>
                        @if ($inventory_type === 'material' && $selectedBatch)
                            <div class="d-flex justify-content-between gap-3 py-1">
                                <dt class="text-secondary fw-normal flex-shrink-0">Batch sumber</dt>
                                <dd class="mb-0 font-mono extra-small fw-semibold text-end text-break">{{ $selectedBatch->entry_code }}</dd>
                            </div>
                            <div class="d-flex justify-content-between gap-3 py-1">
                                <dt class="text-secondary fw-normal flex-shrink-0">Harga batch</dt>
                                <dd class="mb-0 font-mono fw-semibold text-end">Rp {{ number_format((float) $selectedBatch->unit_price, 0, ',', '.') }} / {{ $unitLabel }}</dd>
                            </div>
                        @elseif ($inventory_type === 'tool' && $selectedItem)
                            <div class="d-flex justify-content-between gap-3 py-1">
                                <dt class="text-secondary fw-normal flex-shrink-0">Kode alat</dt>
                                <dd class="mb-0 font-mono fw-semibold text-end">{{ $selectedItem->code }}</dd>
                            </div>
                        @endif

                        @if ($selectedItem && ($inventory_type === 'tool' || $selectedBatch))
                            <div class="d-flex justify-content-between gap-3 py-1 border-top mt-2 pt-2">
                                <dt class="text-secondary fw-normal flex-shrink-0">Tersedia di asal</dt>
                                <dd class="mb-0 font-mono fw-semibold text-end">{{ $formatQty($maxAvailable) }} {{ $unitLabel }}</dd>
                            </div>
                            <div class="d-flex justify-content-between gap-3 py-1">
                                <dt class="text-secondary fw-normal flex-shrink-0">Sisa di asal</dt>
                                <dd class="mb-0 font-mono fw-bold text-end {{ $isOverStock ? 'text-danger' : 'text-body' }}">
                                    {{ $formatQty($remainingAfter) }} {{ $unitLabel }}
                                </dd>
                            </div>
                        @endif
                    </dl>

                    <div class="border-top mt-3 pt-3">
                        <div class="d-flex align-items-baseline justify-content-between gap-2">
                            <span class="small fw-bold">Total dipindahkan</span>
                            <span class="fs-5 fw-black font-mono {{ $isOverStock ? 'text-danger' : 'text-success' }}">
                                {{ $parsedQty > 0 ? $formatQty($parsedQty) : '0' }} {{ $unitLabel }}
                            </span>
                        </div>
                        @if ($inventory_type === 'material' && $selectedBatch)
                            <div class="d-flex align-items-baseline justify-content-between gap-2 mt-1 extra-small text-secondary">
                                <span>Nilai buku batch</span>
                                <span class="font-mono fw-semibold text-body">Rp {{ number_format($estimatedValue, 0, ',', '.') }}</span>
                            </div>
                        @endif
                    </div>

                    <div class="d-flex gap-2 mt-4 pt-3 border-top">
                        <button type="button" wire:click="resetForm" class="btn log-row-action log-row-action--quiet">
                            Bersihkan
                        </button>
                        <button
                            type="button"
                            wire:click="save"
                            wire:loading.attr="disabled"
                            wire:target="save"
                            class="btn btn-success fw-semibold flex-grow-1"
                            @disabled(! $canSubmit)
                        >
                            <span wire:loading.remove wire:target="save">Simpan transfer</span>
                            <span wire:loading wire:target="save">Menyimpan transfer…</span>
                        </button>
                    </div>
                </div>
            </aside>
        </div>

        <!-- Recent Transfers Ledger -->
        <section class="card border-0 shadow-sm rounded-4 p-4 bg-body-tertiary" aria-labelledby="recent-transfers-title">
            <div class="standard-table-toolbar">
                <div>
                    <h2 id="recent-transfers-title" class="h5 fw-bold mb-1">Riwayat transfer antargudang</h2>
                    <p class="small text-secondary mb-0">Jejak perpindahan material dan alat seluruh gudang.</p>
                </div>
                <div class="standard-table-toolbar-controls">
                    <div class="d-inline-flex gap-1" role="group" aria-label="Filter jenis inventaris">
                        <button type="button" wire:click="$set('filterType', '')" class="btn log-row-action {{ $filterType === '' ? 'log-row-action--edit' : 'log-row-action--quiet' }}">Semua</button>
                        <button type="button" wire:click="$set('filterType', 'material')" class="btn log-row-action {{ $filterType === 'material' ? 'log-row-action--edit' : 'log-row-action--quiet' }}">Material</button>
                        <button type="button" wire:click="$set('filterType', 'tool')" class="btn log-row-action {{ $filterType === 'tool' ? 'log-row-action--edit' : 'log-row-action--quiet' }}">Alat</button>
                    </div>
                    <input
                        type="search"
                        wire:model.live.debounce.250ms="search"
                        class="form-control standard-table-toolbar-search"
                        placeholder="Cari kode transfer, item, atau gudang..."
                        aria-label="Cari riwayat transfer"
                    />
                </div>
            </div>

            <div class="table-responsive standard-table-frame inventory-transfer-table-scroll">
                <table class="table table-hover align-middle mb-0 standard-data-table inventory-transfer-table">
                    <thead class="table-light font-geist">
                        <tr>
                            <th>Tanggal & Kode</th>
                            <th>Jenis</th>
                            <th>Item & Batch</th>
                            <th>Rute Gudang</th>
                            <th class="text-end data-number">Jumlah</th>
                            <th>Pencatat & Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($transfers as $transfer)
                            @php
                                $isMat = ! is_null($transfer->material_id);
                                $itemName = $isMat ? ($transfer->material?->name ?? 'Material dihapus') : ($transfer->tool?->name ?? 'Alat dihapus');
                                $itemCode = $isMat ? ($transfer->material?->code ?? '-') : ($transfer->tool?->code ?? '-');
                                $rowUnit = $isMat ? ($transfer->material?->unit ?? '') : 'unit';
                            @endphp
                            <tr wire:key="transfer-row-{{ $transfer->id }}">
                                <td data-label="Tanggal & Kode" class="font-mono small">
                                    <div>
                                        <div class="fw-bold text-body">{{ $transfer->transferred_at?->format('d/m/Y') ?? '-' }} · {{ $transfer->created_at?->format('H:i') }}</div>
                                        <div class="extra-small text-secondary text-truncate" style="max-width: 9.5rem;" title="{{ $transfer->transfer_code }}">{{ $transfer->transfer_code }}</div>
                                    </div>
                                </td>
                                <td data-label="Jenis">
                                    <span class="badge {{ $isMat ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                        {{ $isMat ? 'Material' : 'Alat' }}
                                    </span>
                                </td>
                                <td data-label="Item & Batch">
                                    <div>
                                        <div class="fw-bold text-body">{{ $itemName }}</div>
                                        <div class="extra-small font-mono text-secondary text-truncate" style="max-width: 11.5rem;" title="{{ $itemCode }}{{ $isMat && $transfer->sourceBatch ? ' · Batch '.$transfer->sourceBatch->entry_code : '' }}">
                                            {{ $itemCode }}
                                            @if ($isMat && $transfer->sourceBatch)
                                                · {{ $transfer->sourceBatch->entry_code }}
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Rute Gudang" class="small">
                                    <div>
                                        <div class="text-secondary text-truncate" style="max-width: 13.5rem;" title="Asal: {{ $transfer->sourceWarehouse?->name ?? '-' }}">Asal: <strong class="text-body">{{ $transfer->sourceWarehouse?->name ?? '-' }}</strong></div>
                                        <div class="text-secondary text-truncate" style="max-width: 13.5rem;" title="Tujuan: {{ $transfer->destinationWarehouse?->name ?? '-' }}">Tujuan: <strong class="text-success">{{ $transfer->destinationWarehouse?->name ?? '-' }}</strong></div>
                                    </div>
                                </td>
                                <td data-label="Jumlah" class="text-end font-mono fw-bold data-number">
                                    <span>{{ $formatQty((float) $transfer->quantity) }} {{ $rowUnit }}</span>
                                </td>
                                <td data-label="Pencatat & Catatan" class="small text-secondary">
                                    <div>
                                        <div class="fw-semibold text-body">{{ $transfer->createdBy?->name ?? 'Tidak tercatat' }}</div>
                                        @if ($transfer->notes)
                                            <div class="extra-small text-secondary text-truncate" style="max-width: 10.5rem;" title="{{ $transfer->notes }}">{{ $transfer->notes }}</div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-secondary">
                                    Belum ada riwayat transfer antargudang{{ $search || $filterType ? ' yang cocok dengan pencarian' : '' }}.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $transfers->links('vendor.livewire.bootstrap') }}
            </div>
        </section>
    </div>

    <style>
        .transfer-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(300px, 350px);
            gap: 1.25rem;
            align-items: start;
        }
        .transfer-step-block {
            padding: 1.5rem;
        }
        .transfer-step-index {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 1.85rem;
            height: 1.85rem;
            padding: 0 .4rem;
            border-radius: .5rem;
            background: rgba(31, 155, 58, 0.12);
            color: var(--color-action-green);
            font-family: var(--bs-font-monospace);
            font-size: .76rem;
            font-weight: 800;
            letter-spacing: .04em;
            flex-shrink: 0;
        }
        [data-bs-theme="dark"] .transfer-step-index {
            background: rgba(143, 227, 163, 0.14);
            color: #8fe3a3;
        }
        .transfer-route-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
            gap: .875rem;
            align-items: start;
        }
        .transfer-swap-col {
            padding-top: 1.85rem;
        }
        .transfer-swap-btn {
            min-width: 44px;
            min-height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: .625rem;
        }
        .transfer-summary-sticky {
            position: sticky;
            top: 1rem;
        }
        .transfer-workspace .form-control,
        .transfer-workspace .form-select,
        .transfer-workspace .btn:not(.btn-link) {
            min-height: 44px;
        }
        @media (max-width: 991.98px) {
            .transfer-grid {
                grid-template-columns: minmax(0, 1fr);
            }
            .transfer-summary-sticky {
                position: static;
            }
        }
        @media (max-width: 767.98px) {
            .transfer-step-block {
                padding: 1.15rem;
            }
            .transfer-route-grid {
                grid-template-columns: minmax(0, 1fr);
                gap: .65rem;
            }
            .transfer-swap-col {
                padding-top: 0;
                display: flex;
                justify-content: flex-end;
            }
        }
    </style>
</div>

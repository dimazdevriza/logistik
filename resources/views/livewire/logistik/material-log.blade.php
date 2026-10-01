<div>
    <div class="container-fluid p-0">
        <!-- Hero Header -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-body-tertiary">
            <div class="card-body p-4 p-md-5 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <div>
                    <h1 class="display-5 fw-black text-body mb-2 font-outfit">
                        Catatan Riwayat <span class="text-success">Material</span>
                    </h1>
                    <p class="text-secondary mb-0 max-w-xl">
                        Riwayat penerimaan, transfer gudang, dan penggunaan material di rumah.
                    </p>
                </div>
                <div>
                    @if(in_array(auth()->user()->role, ['admin', 'logistik', 'keuangan'], true))
                        <a href="{{ route('logistik.material-log.export', ['search' => $search, 'type' => $filterType, 'house' => $filterHouse, 'supplier' => $filterSupplier, 'sort' => $sort]) }}" class="btn btn-utility font-semibold text-decoration-none" download>
                            <svg width="15" height="15" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5"/>
                                <path d="M7.646 1.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 2.707V10.5a.5.5 0 0 1-1 0V2.707L5.354 4.854a.5.5 0 1 1-.708-.708z"/>
                            </svg>
                            <span>Ekspor Excel</span>
                        </a>
                    @endif
                </div>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success" role="alert">{{ session('success') }}</div>
        @endif
        @error('void')
            <div class="alert alert-danger" role="alert">{{ $message }}</div>
        @enderror
        @error('receiptCorrection')
            <div class="alert alert-danger" role="alert">{{ $message }}</div>
        @enderror

        <!-- Search & Filter Controls -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 p-3 bg-body-tertiary">
            <div class="d-flex flex-column flex-md-row gap-3 align-items-md-center justify-content-between">
                <div class="w-100 max-w-sm">
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari material / kode batch / pengiriman..." aria-label="Cari material, kode batch, atau pengiriman" class="form-control" />
                </div>
                <div class="d-flex align-items-center gap-2">
                    <x-filter-modal :activeFiltersCount="$this->getActiveFiltersCount()">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-uppercase text-secondary">Tipe Transaksi</label>
                            <select wire:model.live="filterType" class="form-select">
                                <option value="">Semua Transaksi</option>
                                <option value="masuk">Barang Masuk</option>
                                <option value="keluar">Barang Keluar</option>
                            </select>
                        </div>
                        @if ($filterType === 'keluar')
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-uppercase text-secondary">Rumah</label>
                                <select wire:model.live="filterHouse" class="form-select">
                                    <option value="">Semua Rumah</option>
                                    @foreach ($houses as $house)
                                        <option value="{{ $house->id }}">{{ $house->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @elseif ($filterType === 'masuk')
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-uppercase text-secondary">Supplier</label>
                                <select wire:model.live="filterSupplier" class="form-select">
                                    <option value="">Semua Supplier</option>
                                    @foreach ($suppliers as $sup)
                                        <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </x-filter-modal>
                </div>
            </div>
        </div>

        @php
            $monthNames = [1 => 'JANUARI', 2 => 'FEBRUARI', 3 => 'MARET', 4 => 'APRIL', 5 => 'MEI', 6 => 'JUNI', 7 => 'JULI', 8 => 'AGUSTUS', 9 => 'SEPTEMBER', 10 => 'OKTOBER', 11 => 'NOVEMBER', 12 => 'DESEMBER'];
        @endphp

        <!-- Material Log Table -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 data-table-card">
            <div wire:loading.delay class="data-table-status" role="status">Memuat catatan material...</div>
            <div wire:offline class="data-table-status is-error" role="alert">Koneksi terputus. Data mungkin tidak terbaru.</div>
            <div class="table-responsive data-table-scroll material-log-table-scroll" tabindex="0" role="region" aria-label="Catatan material">
                <table class="table table-hover table-striped align-middle mb-0 excel-log-table data-table data-table--compact data-table--sticky-history material-log-table">
                    <colgroup>
                        <col style="width: 220px"><col style="width: 120px"><col style="width: 75px"><col style="width: 130px">
                        <col style="width: 180px"><col style="width: 150px">
                        <col style="width: 260px"><col style="width: 120px"><col style="width: 256px"><col style="width: 90px">
                        <col style="width: 90px"><col style="width: 140px"><col style="width: 150px"><col style="width: 190px"><col style="width: 120px">
                    </colgroup>
                    <thead class="table-light text-uppercase small font-geist">
                        <tr>
                            <x-sortable-th field="date" :sort="$sort">Tanggal</x-sortable-th>
                            <th>Bulan</th>
                            <th>Tahun</th>
                            <x-sortable-th field="type" :sort="$sort" title="Masuk menambah stok; transfer berpindah gudang tanpa menambah biaya; keluar dipakai di rumah.">Tipe</x-sortable-th>
                            <x-sortable-th field="admin" :sort="$sort">Penanggung Jawab</x-sortable-th>
                            <x-sortable-th field="house" :sort="$sort">Rumah / Gudang</x-sortable-th>
                            <x-sortable-th field="notes" :sort="$sort">Keterangan Pekerjaan</x-sortable-th>
                            <x-sortable-th field="code" :sort="$sort" class="data-key-code">Kode Barang</x-sortable-th>
                            <x-sortable-th field="name" :sort="$sort" class="data-key-name">Nama Barang</x-sortable-th>
                            <x-sortable-th field="volume" :sort="$sort" class="text-end data-number">Volume</x-sortable-th>
                            <x-sortable-th field="unit" :sort="$sort">Satuan</x-sortable-th>
                            <x-sortable-th field="unit_price" :sort="$sort" class="text-end data-number">Harga Satuan</x-sortable-th>
                            <x-sortable-th field="total" :sort="$sort" class="text-end data-number">Jumlah</x-sortable-th>
                            <x-sortable-th field="supplier" :sort="$sort">Toko/Supplier</x-sortable-th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($records as $record)
                            @php $date = \Carbon\Carbon::parse($record->date); @endphp
                            <tr wire:key="m-log-{{ $record->type }}-{{ $record->id }}">
                                <td data-label="Tanggal" class="font-mono text-secondary small">
                                    <span class="material-log-cell-value">
                                        {{ $record->type === 'saldo_awal' ? 'Saldo warisan' : $date->format('d/m/Y') }}
                                        <span class="d-block extra-small text-muted">Dicatat {{ \Carbon\Carbon::parse($record->created_at)->format('d/m/Y H:i') }}</span>
                                        @if ($record->transaction_code)
                                            <span class="d-block extra-small" title="{{ $record->type === 'keluar' ? 'Kode transaksi' : 'Kode masuk' }}">{{ $record->transaction_code }}</span>
                                        @endif
                                        @if ($record->batch_code && $record->type === 'keluar')
                                            <span class="d-block extra-small">Batch {{ $record->batch_code }}</span>
                                        @endif
                                        @if ($record->dispatch_code)
                                            <span class="d-block extra-small">Pengiriman {{ $record->dispatch_code }}</span>
                                        @endif
                                    </span>
                                </td>
                                <td data-label="Bulan" class="font-mono text-secondary small"><span class="material-log-cell-value">{{ $monthNames[$date->month] }}</span></td>
                                <td data-label="Tahun" class="text-center font-mono text-secondary small"><span class="material-log-cell-value">{{ $date->year }}</span></td>
                                <td data-label="Tipe">
                                    <span class="material-log-cell-value">
                                        @if ($record->type === 'saldo_awal')
                                            <span class="badge bg-secondary-subtle text-secondary">Saldo warisan</span>
                                            <span class="d-block extra-small text-secondary">Waktu tiba tidak tercatat</span>
                                        @elseif ($record->type === 'transfer_masuk')
                                            <span class="badge bg-info-subtle text-info-emphasis" title="Transfer antargudang; biaya pembelian tidak dihitung ulang.">Transfer</span>
                                            <span class="d-block extra-small text-body-secondary">Antargudang</span>
                                        @elseif ($record->type === 'dibatalkan')
                                            <span class="badge bg-danger-subtle text-danger">Dibatalkan</span>
                                            <span class="d-block extra-small text-secondary">Barang masuk</span>
                                        @elseif ($record->type === 'masuk')
                                            <span class="badge bg-success-subtle text-success">Masuk</span>
                                            <span class="d-block extra-small text-secondary">Gudang</span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning">Keluar</span>
                                            <span class="d-block extra-small text-secondary">Konstruksi</span>
                                        @endif
                                    </span>
                                </td>
                                <td data-label="Penanggung jawab" class="fw-semibold data-cell-truncate" title="{{ $record->admin_name ?? '-' }}">
                                    <span class="material-log-cell-value">
                                        {{ $record->admin_name ?: '-' }}
                                        @if ($record->admin_role)
                                            <span class="d-block extra-small text-secondary">{{ ucfirst($record->admin_role) }}</span>
                                        @endif
                                    </span>
                                </td>
                                <td data-label="Rumah / Gudang" class="fw-semibold data-cell-truncate" title="{{ $record->house_name ?? '-' }}"><span class="material-log-cell-value">{{ $record->house_name ?: '-' }}</span></td>
                                <td data-label="Keterangan" class="data-cell-truncate" title="{{ $record->job_notes ?? '-' }}">
                                    <span class="material-log-cell-value">
                                        {{ $record->job_notes ?: '-' }}
                                        @if (($record->voided_at ?? null) !== null)
                                            <span class="badge bg-secondary-subtle text-secondary ms-1">VOIDED</span>
                                        @endif
                                        @if ($record->type === 'dibatalkan')
                                            <span class="badge bg-danger-subtle text-danger ms-1">DIBATALKAN</span>
                                        @endif
                                    </span>
                                </td>
                                <td data-label="Kode barang" class="font-mono text-secondary small data-key-code" title="{{ $record->item_code ?? '-' }}"><span class="material-log-cell-value">{{ $record->item_code ?: '-' }}</span></td>
                                <td data-label="Nama barang" class="fw-bold text-body data-key-name" title="{{ $record->item_name ?? '-' }}"><span class="material-log-cell-value">{{ $record->item_name ?: '-' }}</span></td>
                                <td data-label="Volume" class="text-end fw-bold font-mono data-number"><span class="material-log-cell-value">{{ rtrim(rtrim(number_format((float) $record->volume, 2, ',', '.'), '0'), ',') }}</span></td>
                                <td data-label="Satuan" class="data-cell-truncate" title="{{ $record->unit ?? '-' }}"><span class="material-log-cell-value">{{ $record->unit ?: '-' }}</span></td>
                                <td data-label="Harga satuan" class="text-end font-mono text-secondary data-number"><span class="material-log-cell-value">Rp {{ number_format((float) $record->unit_price, 0, ',', '.') }}@if (($record->type === 'keluar' && ($record->correction_count ?? 0) > 0) || (in_array($record->type, ['masuk', 'transfer_masuk']) && ($record->price_correction_count ?? 0) > 0))<span class="d-block extra-small text-warning">Harga dikoreksi</span>@endif</span></td>
                                <td data-label="Jumlah" class="text-end font-mono fw-bold text-success data-number"><span class="material-log-cell-value">Rp {{ number_format((float) $record->total_cost, 0, ',', '.') }}</span></td>
                                <td data-label="Toko/supplier" class="data-cell-truncate" title="{{ $record->supplier_name ?? '-' }}"><span class="material-log-cell-value">{{ $record->supplier_name ?: '-' }}</span></td>
                                <td data-label="Aksi" class="text-end">
                                    @if ($record->type === 'masuk')
                                        <div class="log-row-actions">
                                            <button type="button" wire:click="openReceiptEditor({{ $record->id }})" class="btn log-row-action log-row-action--edit">Koreksi</button>
                                            @if (($record->correction_count ?? 0) > 0)
                                                <button type="button" wire:click="openReceiptHistory({{ $record->id }})" class="btn log-row-action log-row-action--quiet">Riwayat Koreksi</button>
                                            @endif
                                            @if ((float) ($record->used_quantity ?? 0) === 0.0 && (float) ($record->reserved_quantity ?? 0) === 0.0 && (float) ($record->transferred_quantity ?? 0) === 0.0)
                                                <button type="button" wire:click="openReceiptCancel({{ $record->id }})" class="btn log-row-action log-row-action--danger">Batalkan Masuk</button>
                                            @endif
                                        </div>
                                    @elseif ($record->type === 'dibatalkan')
                                        @if (($record->correction_count ?? 0) > 0)
                                            <button type="button" wire:click="openReceiptHistory({{ $record->id }})" class="btn log-row-action log-row-action--quiet">Lihat Alasan</button>
                                        @else
                                            <span class="small text-secondary">Dibatalkan</span>
                                        @endif
                                    @elseif ($record->type === 'transfer_masuk' && ($record->correction_count ?? 0) > 0)
                                        <button type="button" wire:click="openReceiptHistory({{ $record->id }})" class="btn log-row-action log-row-action--quiet">Lihat Alasan</button>
                                    @elseif ($record->type === 'keluar' && ($record->voided_at ?? null) === null)
                                        <div class="log-row-actions">
                                            <button type="button" wire:click="voidMaterial({{ $record->id }})" wire:confirm="Batalkan alokasi ini? Stok lot material akan dikembalikan dan catatan tetap disimpan." class="btn log-row-action log-row-action--danger">Batalkan</button>
                                            @if (($record->correction_count ?? 0) > 0 && $record->stock_in_id)
                                                <button type="button" wire:click="openReceiptHistory({{ $record->stock_in_id }})" class="btn log-row-action log-row-action--quiet">Lihat Alasan</button>
                                            @endif
                                        </div>
                                    @elseif (($record->voided_at ?? null) !== null)
                                        <span class="small text-secondary">Dibatalkan</span>
                                    @else
                                        <span class="small text-secondary">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="15" class="data-table-empty-state py-4 text-secondary">Belum ada catatan material.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="d-flex justify-content-end">{{ $records->links() }}</div>
    </div>

        @if ($showReceiptModal && $activeReceipt)
            @teleport('body')
            <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.6)" role="dialog" aria-modal="true" aria-labelledby="receipt-correction-title">
                <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
                    <div class="modal-content border-0 shadow-lg rounded-4">
                        <div class="modal-header">
                            <div>
                                <h2 id="receipt-correction-title" class="modal-title fs-5 fw-bold">Koreksi Barang Masuk</h2>
                                <div class="small text-secondary">{{ $activeReceipt->entry_code }} · {{ $activeReceipt->material?->name }}</div>
                            </div>
                            <button type="button" class="btn-close" wire:click="$set('showReceiptModal', false)" aria-label="Tutup"></button>
                        </div>
                        <form wire:submit="saveReceiptCorrection">
                            <div class="modal-body">
                                @if ($activeReceiptUsed > 0)
                                    <div class="alert alert-warning" role="status">
                                        Sudah digunakan {{ rtrim(rtrim(number_format($activeReceiptUsed, 2, ',', '.'), '0'), ',') }} {{ $activeReceipt->material?->unit }}. Koreksi jumlah akan menyesuaikan stok, dan koreksi harga akan menghitung ulang biaya rumah yang memakai batch ini.
                                    </div>
                                @elseif ($activeReceiptTransferred > 0)
                                    <div class="alert alert-warning" role="status">
                                        Sebagian stok sudah dipindahkan ke gudang lain. Koreksi harga akan mengikuti batch hasil transfer dan menghitung ulang biaya rumah; jumlah tidak dapat dikoreksi di bawah jumlah yang sudah dipindahkan.
                                    </div>
                                @elseif ($activeReceiptReserved > 0)
                                    <div class="alert alert-warning" role="status">
                                        {{ rtrim(rtrim(number_format($activeReceiptReserved, 2, ',', '.'), '0'), ',') }} {{ $activeReceipt->material?->unit }} sedang dicadangkan. Jumlah tidak dapat dikoreksi di bawah stok yang dicadangkan.
                                    </div>
                                @else
                                    <div class="alert alert-info" role="status">Batch belum digunakan. Jumlah dan harga dapat diperbarui langsung.</div>
                                @endif
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label" for="receipt-quantity">Jumlah diterima</label>
                                        <input id="receipt-quantity" type="number" min="0.01" step="0.01" wire:model="receiptQuantity" class="form-control">
                                        @error('receiptQuantity') <small class="text-danger">{{ $message }}</small> @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="receipt-price">Harga per satuan</label>
                                        <input id="receipt-price" type="number" min="0" step="0.01" wire:model="receiptUnitPrice" class="form-control">
                                        @error('receiptUnitPrice') <small class="text-danger">{{ $message }}</small> @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="receipt-supplier">Supplier</label>
                                        <select id="receipt-supplier" wire:model="receiptSupplierId" class="form-select">
                                            <option value="">Pilih supplier</option>
                                            @foreach ($suppliers as $supplier)
                                                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="receipt-warehouse">Gudang</label>
                                        <select id="receipt-warehouse" wire:model="receiptWarehouseId" class="form-select" @disabled($activeReceiptUsed > 0 || $activeReceiptTransferred > 0 || (float) $activeReceipt->available_quantity < (float) $activeReceipt->remaining_quantity)>
                                            @foreach ($warehouses as $warehouse)
                                                <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="receipt-date">Tanggal penerimaan</label>
                                        <input id="receipt-date" type="date" wire:model="receiptDate" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="receipt-received-at">Waktu dicatat tiba</label>
                                        <input id="receipt-received-at" type="datetime-local" wire:model="receiptReceivedAt" class="form-control">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label" for="receipt-notes">Catatan / nomor dokumen</label>
                                        <textarea id="receipt-notes" wire:model="receiptNotes" rows="2" class="form-control"></textarea>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label" for="receipt-reason">Alasan koreksi</label>
                                        <input id="receipt-reason" type="text" wire:model="receiptReason" class="form-control" placeholder="Wajib jika jumlah, harga, atau gudang berubah">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label" for="receipt-reference">Nomor referensi</label>
                                        <input id="receipt-reference" type="text" wire:model="receiptReference" class="form-control" placeholder="Invoice / surat jalan">
                                    </div>
                                    @error('receiptCorrection') <div class="col-12 text-danger" role="alert">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="modal-footer justify-content-between">
                                <button type="button" wire:click="openReceiptCancel({{ $activeReceipt->id }})" class="btn btn-outline-danger">Batalkan barang masuk</button>
                                <div class="d-flex gap-2">
                                    <button type="button" wire:click="$set('showReceiptModal', false)" class="btn btn-outline-secondary">Tutup</button>
                                    <button type="submit" class="btn btn-success" wire:loading.attr="disabled" wire:target="saveReceiptCorrection">Simpan koreksi</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endteleport
        @endif

        @if ($showReceiptHistoryModal)
            @teleport('body')
            <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.65)" role="dialog" aria-modal="true" aria-labelledby="receipt-history-title">
                <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content border-0 shadow-lg rounded-4">
                        <div class="modal-header">
                            <div>
                                <h2 id="receipt-history-title" class="modal-title fs-5 fw-bold">Riwayat Koreksi · {{ $historyReceipt?->entry_code }}</h2>
                                <div class="small text-secondary">Harga dan jumlah batch serta dampak biaya rumah.</div>
                            </div>
                            <button type="button" class="btn-close" wire:click="$set('showReceiptHistoryModal', false)" aria-label="Tutup"></button>
                        </div>
                        <div class="modal-body">
                            @forelse ($receiptHistory as $adjustment)
                                <div class="border-bottom pb-3 mb-3">
                                    <div class="d-flex justify-content-between gap-3 fw-semibold">
                                        <span>{{ match($adjustment->field) { 'quantity_received' => 'Jumlah diterima', 'unit_price' => 'Harga batch', 'warehouse_id' => 'Gudang penerimaan', 'total_cost' => 'Biaya rumah', 'cancelled' => 'Pembatalan barang masuk', default => $adjustment->field } }}</span>
                                        @if ($adjustment->field !== 'warehouse_id')
                                            <span>{{ in_array($adjustment->field, ['unit_price', 'total_cost']) ? 'Rp ' : '' }}{{ number_format((float) $adjustment->before_value, in_array($adjustment->field, ['unit_price', 'total_cost']) ? 0 : 2, ',', '.') }} → {{ in_array($adjustment->field, ['unit_price', 'total_cost']) ? 'Rp ' : '' }}{{ number_format((float) $adjustment->after_value, in_array($adjustment->field, ['unit_price', 'total_cost']) ? 0 : 2, ',', '.') }}</span>
                                        @endif
                                    </div>
                                    @if ($adjustment->field === 'total_cost' && $adjustment->adjustable)
                                        <div class="small text-secondary">Rumah: {{ $adjustment->adjustable->house?->name ?? '-' }}</div>
                                    @endif
                                    <div class="small text-secondary mt-1">{{ $adjustment->user?->name ?? 'Pengguna tidak tercatat' }} · {{ $adjustment->created_at->format('d/m/Y H:i') }}</div>
                                    <div class="mt-2">{{ $adjustment->reason }}</div>
                                </div>
                            @empty
                                <p class="text-secondary mb-0">Belum ada koreksi untuk batch ini.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
            @endteleport
        @endif

        @if ($showReceiptCancelModal)
            @teleport('body')
            <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.72)" role="dialog" aria-modal="true" aria-labelledby="receipt-cancel-title">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg rounded-4">
                        <div class="modal-header">
                            <h2 id="receipt-cancel-title" class="modal-title fs-5 fw-bold">Batalkan barang masuk</h2>
                            <button type="button" class="btn-close" wire:click="$set('showReceiptCancelModal', false)" aria-label="Tutup"></button>
                        </div>
                        <form wire:submit="cancelReceipt">
                            <div class="modal-body">
                                <p>Stok batch akan diambil dari persediaan. Catatan barang masuk tetap disimpan.</p>
                                <label class="form-label" for="receipt-cancel-reason">Alasan pembatalan</label>
                                <textarea id="receipt-cancel-reason" wire:model="receiptCancelReason" rows="3" class="form-control" required></textarea>
                                @error('receiptCancelReason') <small class="text-danger" role="alert">{{ $message }}</small> @enderror
                            </div>
                            <div class="modal-footer">
                                <button type="button" wire:click="$set('showReceiptCancelModal', false)" class="btn btn-outline-secondary">Kembali</button>
                                <button type="submit" class="btn btn-danger" wire:loading.attr="disabled" wire:target="cancelReceipt">Batalkan barang masuk</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endteleport
        @endif
</div>

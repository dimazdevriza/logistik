<div>
    <div class="container-fluid p-0">
        <!-- Hero Header -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-body-tertiary workflow-page-header">
            <div class="card-body p-4 p-md-5">
                <h1 class="display-5 fw-black text-body mb-2 font-outfit">
                    Barang <span class="text-success">Dalam Pengiriman</span>
                </h1>
                <p class="text-secondary mb-0 max-w-xl">
                Pantau barang yang sudah dikirim, catat penerimaan di lokasi, dan selesaikan setiap selisih.
                </p>
            </div>
        </div>

        @if ($clusterAssignmentMissing)
            <div class="alert alert-warning rounded-3" role="alert">Akun Logistik belum memiliki cluster tugas. Minta Admin menetapkan cluster sebelum memproses pengiriman.</div>
        @endif

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show rounded-3" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Focused work queues -->
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-body-tertiary mb-4 workflow-filter-bar">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                <div class="d-flex flex-wrap gap-2" role="group" aria-label="Kelompok antrean pengiriman">
                    @foreach ([
                        'transit' => 'Dalam Pengiriman',
                        'history' => 'Riwayat',
                    ] as $queue => $label)
                        <button type="button" class="btn workflow-filter-button {{ $queueView === $queue ? 'btn-success' : 'btn-outline-secondary' }}" wire:click="showQueue('{{ $queue }}')" aria-pressed="{{ $queueView === $queue ? 'true' : 'false' }}">
                            {{ $label }} <span class="ms-1">{{ number_format($queueCounts[$queue]) }}</span>
                        </button>
                    @endforeach
                </div>

                <div class="dispatch-search">
                    <label for="dispatch-search" class="form-label small fw-semibold mb-1">Cari alokasi</label>
                    <div class="input-group">
                        <input id="dispatch-search" type="search" class="form-control" wire:model.live.debounce.300ms="search" placeholder="Kode, pencatat, rumah, item" autocomplete="off">
                        @if ($search !== '')
                            <button type="button" class="btn btn-outline-secondary dispatch-clear-search" wire:click="$set('search', '')">Hapus</button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div wire:loading.flex wire:target="search, showQueue" class="small text-secondary mb-3" role="status" aria-live="polite">Memuat antrean…</div>

        <!-- Requests table -->
        <div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 bg-body-tertiary">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 data-table dispatch-table">
                    <thead>
                        <tr class="text-secondary extra-small text-uppercase">
                            <x-sortable-th field="date" :sort="$sort">Kode & Waktu</x-sortable-th>
                            <th>Tipe alokasi</th>
                            <x-sortable-th field="requester" :sort="$sort">Dicatat oleh</x-sortable-th>
                            <x-sortable-th field="house" :sort="$sort">Unit Rumah & Item</x-sortable-th>
                            <x-sortable-th field="quantity" :sort="$sort" class="text-end">Jumlah (Qty)</x-sortable-th>
                            <x-sortable-th field="status" :sort="$sort">Status & Bukti Tiba</x-sortable-th>
                            <th class="text-end data-action-column">Tindakan Logistik</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($requests as $req)
                            @php
                                $formatQuantity = fn ($quantity) => rtrim(rtrim(number_format((float) $quantity, 2, ',', '.'), '0'), ',');
                            @endphp
                            <tr id="dispatch-row-{{ $req->id }}" tabindex="-1">
                                <td data-label="Kode & waktu">
                                    <div class="dispatch-cell-stack">
                                        <div class="fw-bold text-body small font-mono">{{ $req->dispatch_code ?? $req->request_code }}</div>
                                        @if ($req->dispatch_code)
                                            <div class="extra-small text-secondary">Alokasi {{ $req->request_code }}</div>
                                        @endif
                                        <div class="extra-small text-secondary">{{ $req->created_at->format('d/m/Y H:i') }}</div>
                                        @if ($req->dispatchLines->isNotEmpty())
                                            <details class="small mt-1">
                                                <summary>Rincian sumber · {{ $req->dispatchLines->count() }}</summary>
                                                @foreach ($req->dispatchLines as $line)
                                                    <div class="extra-small text-secondary mt-1">{{ $line->stockIn?->entry_code ?? $line->tool?->entry_code ?? 'Batch tidak tercatat' }} · {{ $line->warehouse?->name ?? '-' }} · {{ number_format((float) $line->quantity, 2, ',', '.') }}</div>
                                                @endforeach
                                            </details>
                                        @endif
                                    </div>
                                </td>
                                <td data-label="Tipe alokasi">
                                    <div class="dispatch-cell-stack">
                                        <div>
                                            <span class="badge {{ $req->type === 'material' ? 'bg-primary-subtle text-primary' : 'bg-secondary-subtle text-secondary' }}">
                                                {{ $req->type === 'material' ? 'Alokasi material' : 'Peminjaman alat' }}
                                            </span>
                                        </div>
                                        @if ($req->returnedQuantity > 0)
                                            <div class="extra-small text-success fw-semibold mt-1">
                                                Pengembalian {{ $req->type === 'material' ? 'material' : 'alat' }}: {{ $formatQuantity($req->returnedQuantity) }} {{ $req->type === 'material' ? ($req->material->unit ?? '') : 'unit' }}
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td data-label="Dicatat oleh">
                                    <div class="dispatch-cell-stack">
                                        <div class="fw-bold text-body">{{ $req->requester->name ?? '-' }}</div>
                                        <div class="extra-small text-secondary">{{ ucfirst($req->requester->role ?? '') }}</div>
                                    </div>
                                </td>
                                <td data-label="Unit rumah & item">
                                    <div class="dispatch-cell-stack">
                                        <div class="fw-bold text-body">{{ $req->house->name ?? '-' }} ({{ $req->house->block ?? '-' }})</div>
                                        <div class="extra-small text-secondary">
                                            {{ $req->type === 'material' ? ($req->material->name ?? '-') : ($req->tool->name ?? '-') }}
                                        </div>
                                        @if ($req->status === 'pending' && $req->stockIn)
                                            <div class="extra-small text-secondary">Batch pilihan: {{ $req->stockIn->entry_code }} · {{ $req->stockIn->warehouse?->name ?? 'Gudang tidak tercatat' }}</div>
                                        @endif
                                        @if ($req->notes)
                                            <div class="extra-small text-secondary fst-italic">Peruntukkan: {{ $req->notes }}</div>
                                        @endif
                                        @if ($req->sourceWarehouse)
                                            <div class="extra-small text-secondary">Gudang sumber: {{ $req->sourceWarehouse->name }}</div>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-end font-mono fw-bold" data-label="Jumlah">
                                    <span>{{ $formatQuantity($req->quantity) }}</span>
                                </td>
                                <td data-label="Status & bukti tiba">
                                    <div class="dispatch-cell-stack">
                                        @if ($req->status === 'pending')
                                            <span class="badge bg-warning-subtle text-warning">Belum dikirim · data lama</span>
                                        @elseif ($req->status === 'dispatched')
                                            <span class="badge bg-primary-subtle text-primary">Dalam Pengiriman</span>
                                        @elseif ($req->status === 'partially_arrived')
                                            <span class="badge bg-info-subtle text-info">Tiba sebagian</span>
                                        @elseif ($req->status === 'arrived')
                                            <span class="badge bg-success-subtle text-success">Penerimaan lengkap</span>
                                        @elseif ($req->status === 'resolved')
                                            <span class="badge bg-warning-subtle text-warning">Selisih dituntaskan</span>
                                        @elseif ($req->status === 'approved')
                                            <span class="badge bg-success-subtle text-success">Selesai</span>
                                        @elseif ($req->status === 'rejected')
                                            <span class="badge bg-danger-subtle text-danger">
                                                {{ $req->dispatched_at && ! $req->rejected_returned_at ? 'Ditolak, menunggu kembali' : 'Ditolak' }}
                                            </span>
                                        @endif

                                        @if ($req->dispatch_code || $req->dispatched_at || $req->dispatchLines->isNotEmpty())
                                            <div class="extra-small text-secondary mt-1" aria-label="Rincian jumlah pengiriman">
                                                Dikirim: {{ $formatQuantity($req->dispatchQuantity) }} · Layak pakai: {{ $formatQuantity($req->usableReceivedQuantity) }} · Rusak: {{ $formatQuantity($req->damagedReceivedQuantity) }} · Dalam perjalanan: {{ $formatQuantity($req->inTransitQuantity) }}
                                                @if ($req->returnedQuantity > 0) · Kembali: {{ $formatQuantity($req->returnedQuantity) }} @endif
                                                @if ($req->lostQuantity > 0) · Hilang: {{ $formatQuantity($req->lostQuantity) }} @endif
                                                @if ($req->undisposedDamageQuantity > 0) · Rusak belum dibuang: {{ $formatQuantity($req->undisposedDamageQuantity) }} @endif
                                            </div>
                                        @endif

                                        @if ($req->dispatch_proof_image)
                                            <div class="mt-1">
                                                <a href="{{ asset('storage/' . $req->dispatch_proof_image) }}" target="_blank" rel="noopener" class="badge bg-success-subtle text-success text-decoration-none d-inline-flex align-items-center gap-1" title="Lihat Foto Bukti Pengiriman">
                                                    <svg width="12" height="12" fill="currentColor"><use href="#i-camera"/></svg> Foto Pengiriman
                                                </a>
                                            </div>
                                        @endif
                                        @if ($req->arrival_proof_image)
                                            <div class="mt-1">
                                                <a href="{{ asset('storage/' . $req->arrival_proof_image) }}" target="_blank" class="badge bg-info text-dark text-decoration-none d-inline-flex align-items-center gap-1" title="Lihat Foto Bukti Arrival">
                                                    <svg width="12" height="12" fill="currentColor"><use href="#i-camera"/></svg> Bukti pengiriman
                                                </a>
                                            </div>
                                        @endif
                                        @foreach ($req->receipts as $receipt)
                                            <details class="small mt-2">
                                                <summary>{{ $receipt->event_type === 'correction' ? 'Koreksi' : 'Penerimaan' }} · {{ $receipt->received_at->format('d/m/Y H:i') }}</summary>
                                                <div class="extra-small text-secondary mt-1">Dicatat {{ $receipt->receivedBy?->name ?? 'Pengguna dihapus' }}</div>
                                                @foreach ($receipt->lines as $receiptLine)
                                                    <div class="extra-small text-secondary">{{ $receiptLine->dispatchLine?->stockIn?->entry_code ?? $receiptLine->dispatchLine?->tool?->entry_code ?? 'Sumber warisan' }} · diterima {{ number_format((float) $receiptLine->received_quantity, 2, ',', '.') }} · rusak {{ number_format((float) $receiptLine->damaged_quantity, 2, ',', '.') }}</div>
                                                @endforeach
                                                @if ($receipt->notes)<div class="extra-small text-secondary">{{ $receipt->notes }}</div>@endif
                                                @if ($receipt->proof_image)<a class="extra-small" href="{{ asset('storage/'.$receipt->proof_image) }}" target="_blank" rel="noopener">Lihat bukti</a>@endif
                                                @if (auth()->user()->role === 'admin')
                                                    <div><button id="receipt-correction-{{ $receipt->id }}" type="button" class="btn btn-sm btn-outline-secondary mt-1" wire:click="openCorrectionModal({{ $receipt->id }})">Tambah koreksi</button></div>
                                                @endif
                                            </details>
                                        @endforeach
                                        @if ($req->resolutionEvents->isNotEmpty())
                                            <details class="small mt-2">
                                                <summary>Riwayat selisih · {{ $req->resolutionEvents->count() }}</summary>
                                                @foreach ($req->resolutionEvents as $event)
                                                    <div class="extra-small text-secondary mt-1">
                                                        @switch($event->event_type)
                                                            @case('return_to_warehouse') Barang kembali @break
                                                            @case('declare_lost') Dinyatakan hilang @break
                                                            @case('dispose_damaged') Material rusak dibuang @break
                                                            @case('material_damage_loss') Kerugian material rusak @break
                                                            @default Penyelesaian
                                                        @endswitch
                                                        · {{ number_format((float) $event->quantity, 2, ',', '.') }}
                                                        @if ($event->total_cost !== null) · Rp {{ number_format((float) $event->total_cost, 0, ',', '.') }} @endif
                                                        · {{ $event->recorded_at->format('d/m/Y H:i') }} · {{ $event->recordedBy?->name ?? 'Pengguna dihapus' }}
                                                        @if ($event->notes) · {{ $event->notes }} @endif
                                                    </div>
                                                @endforeach
                                            </details>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-end data-action-column" data-label="Tindakan logistik">
                                    <div class="dispatch-cell-stack dispatch-action-stack">
                                        @if ($req->status === 'pending')
                                            <button id="dispatch-action-{{ $req->id }}" type="button" class="btn log-row-action log-row-action--edit d-inline-flex align-items-center gap-1" wire:click="dispatchRequest({{ $req->id }})">
                                                <svg width="14" height="14" fill="currentColor"><use href="#i-truck"/></svg> Kirim &amp; unggah foto
                                            </button>
                                            <button type="button" class="btn log-row-action log-row-action--danger ms-1" wire:click="rejectRequest({{ $req->id }})">
                                                Tolak
                                            </button>
                                        @elseif (in_array($req->status, ['dispatched', 'partially_arrived']))
                                            <button id="receipt-action-{{ $req->id }}" type="button" class="btn log-row-action log-row-action--edit d-inline-flex align-items-center gap-1" wire:click="openReceiptModal({{ $req->id }})">
                                                <svg width="14" height="14" fill="currentColor" aria-hidden="true"><use href="#i-check"/></svg> Catat penerimaan
                                            </button>
                                        @elseif ($req->status === 'approved')
                                            <span class="extra-small text-success fw-bold d-inline-flex align-items-center gap-1"><svg width="12" height="12" fill="currentColor" aria-hidden="true"><use href="#i-check"/></svg> Disetujui</span>
                                        @elseif ($req->status === 'rejected' && $req->dispatched_at && ! $req->rejected_returned_at)
                                            <button type="button" class="btn log-row-action log-row-action--edit" wire:click="receiveRejectedReturn({{ $req->id }})" wire:confirm="Pastikan barang sudah benar-benar kembali ke gudang sebelum mencatatnya.">
                                                Catat barang kembali
                                            </button>
                                        @endif
                                        @if ($req->canResolveTransit && in_array(auth()->user()->role, ['admin', 'logistik'], true))
                                            <button type="button" class="btn log-row-action log-row-action--edit" wire:click="openResolutionModal({{ $req->id }}, 'return_to_warehouse')">Catat kembali ke gudang</button>
                                            <button type="button" class="btn log-row-action log-row-action--danger" wire:click="openResolutionModal({{ $req->id }}, 'declare_lost')">Tandai hilang</button>
                                        @endif
                                        @if ($req->canDisposeDamage && in_array(auth()->user()->role, ['admin', 'logistik'], true))
                                            <button type="button" class="btn log-row-action log-row-action--danger" wire:click="openResolutionModal({{ $req->id }}, 'dispose_damaged')">Selesaikan barang rusak</button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="dispatch-empty-state text-center py-4 text-secondary">
                                    @if (trim($search) !== '')
                                        Tidak ada alokasi yang cocok. Ubah kata pencarian.
                                    @elseif ($queueView === 'transit')
                                        Tidak ada barang yang sedang dalam perjalanan.
                                    @else
                                        Belum ada riwayat transaksi.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $requests->links() }}
            </div>
        </div>
    </div>

    @if ($showDispatchModal && $selectedRequest)
        @teleport('body')
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,.5);" role="dialog" aria-modal="true" aria-labelledby="dispatch-modal-title"
            x-data="{
                opener: document.activeElement,
                init() { this.$nextTick(() => this.focusables()[0]?.focus()); },
                focusables() { return [...this.$refs.dialog.querySelectorAll('button, input, select, textarea, a[href]')].filter(element => !element.disabled && element.offsetParent !== null); },
                async close() { const opener = this.opener; await this.$wire.set('showDispatchModal', false); window.requestAnimationFrame(() => opener?.focus()); },
                async submit(action, targetId, rowId) { await this.$wire[action](); window.requestAnimationFrame(() => { if (this.$el.isConnected) return; const target = document.getElementById(targetId) || document.getElementById(rowId); const details = target?.closest('details'); if (details) details.open = true; target?.focus(); }); },
                trap(event) { const items = this.focusables(); if (!items.length) return; const first = items[0], last = items[items.length - 1]; if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); } if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); } }
            }"
            x-ref="dialog" @keydown.escape.stop="close()" @keydown.tab="trap($event)">
            <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                <div class="modal-content border-0 shadow-lg rounded-4 bg-body">
                    <div class="modal-header border-bottom">
                        <div>
                            <h2 id="dispatch-modal-title" class="modal-title h5 fw-bold">Kirim barang</h2>
                            <div class="small text-secondary">{{ $selectedRequest->house->name }} · {{ $selectedRequest->type === 'material' ? $selectedRequest->material->name : $selectedRequest->tool->name }} · {{ number_format((float) $selectedRequest->quantity, 2, ',', '.') }} {{ $selectedRequest->type === 'material' ? $selectedRequest->material->unit : 'unit' }}</div>
                        </div>
                        <button type="button" class="btn-close" x-on:click="close()" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body p-4">
                        <p class="small text-secondary">Jumlah mengikuti alokasi. @if ($selectedRequest->type === 'material' && $selectedRequest->stockIn) Batch {{ $selectedRequest->stockIn->entry_code }} sudah dipilih dan dicadangkan untuk alokasi ini. @elseif ($selectedRequest->type === 'material') Alokasi lama tanpa pilihan batch memakai saldo bebas tertua. @else Gudang sumber alat sudah dicadangkan untuk alokasi ini. @endif</p>
                        @php
                            $dispatchSourceKey = $selectedRequest->type === 'material' ? 'id' : 'warehouse_id';
                            $dispatchSourceMap = $availableDispatchSources->keyBy($dispatchSourceKey);
                            $dispatchReady = abs(array_sum(array_column($dispatchLines, 'quantity')) - (float) $selectedRequest->quantity) <= 0.001;
                        @endphp
                        <div class="border rounded-3 p-3 mb-4 bg-body-tertiary">
                            <div class="fw-semibold mb-2">Rencana pengambilan stok</div>
                            @foreach ($dispatchLines as $line)
                                @php $source = $dispatchSourceMap->get((int) $line['source_id']); @endphp
                                <div class="small text-secondary d-flex justify-content-between gap-3">
                                    <span>{{ $selectedRequest->type === 'material' ? ($source?->entry_code ?? 'Batch tidak tersedia') : ($selectedRequest->tool->entry_code ?? $selectedRequest->tool->name) }} · {{ $source?->warehouse?->name ?? 'Gudang tidak tersedia' }}</span>
                                    <span class="font-mono text-body">{{ number_format((float) $line['quantity'], 2, ',', '.') }} {{ $selectedRequest->type === 'material' ? $selectedRequest->material->unit : 'unit' }}</span>
                                </div>
                            @endforeach
                            @unless ($dispatchReady)
                                <div class="small text-danger mt-2">Stok batch sudah berubah. Tutup lalu buka alokasi lagi.</div>
                            @endunless
                        </div>
                        <div>
                            <label for="dispatch-proof-image" class="form-label fw-semibold">Foto barang yang dikirim <span class="text-danger">*</span></label>
                            <input id="dispatch-proof-image" type="file" accept="image/*" class="form-control" wire:model="dispatchProofImage" required aria-describedby="dispatch-proof-help">
                            <div id="dispatch-proof-help" class="form-text">Unggah foto bukti pengiriman, maksimal 5 MB.</div>
                            <div wire:loading wire:target="dispatchProofImage" class="small text-secondary mt-2" role="status">Mengunggah foto…</div>
                            @if ($dispatchProofImage)
                                <div class="small text-success mt-2" aria-live="polite">Siap diunggah: {{ $dispatchProofImage->getClientOriginalName() }}</div>
                            @endif
                            @error('dispatchProofImage') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-outline-secondary" x-on:click="close()">Batal</button>
                        <button type="button" class="btn btn-success fw-semibold" x-on:click="submit('submitDispatch', 'receipt-action-{{ $selectedRequestId }}', 'dispatch-row-{{ $selectedRequestId }}')" wire:loading.attr="disabled" wire:target="submitDispatch" @disabled(! $dispatchReady || ! $dispatchProofImage)>
                            <span wire:loading.remove wire:target="submitDispatch">Catat pengiriman</span>
                            <span wire:loading wire:target="submitDispatch">Menyimpan…</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endteleport
    @endif

    @if ($showReceiptModal)
        @teleport('body')
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,.5);" role="dialog" aria-modal="true" aria-labelledby="receipt-modal-title"
            x-data="{
                opener: document.activeElement,
                init() { this.$nextTick(() => this.focusables()[0]?.focus()); },
                focusables() { return [...this.$refs.dialog.querySelectorAll('button, input, select, textarea, a[href]')].filter(element => !element.disabled && element.offsetParent !== null); },
                async close() { const opener = this.opener; await this.$wire.set('showReceiptModal', false); window.requestAnimationFrame(() => opener?.focus()); },
                async submit(action, targetId, rowId) { await this.$wire[action](); window.requestAnimationFrame(() => { if (this.$el.isConnected) return; const target = document.getElementById(targetId) || document.getElementById(rowId); const details = target?.closest('details'); if (details) details.open = true; target?.focus(); }); },
                trap(event) { const items = this.focusables(); if (!items.length) return; const first = items[0], last = items[items.length - 1]; if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); } if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); } }
            }"
            x-ref="dialog" @keydown.escape.stop="close()" @keydown.tab="trap($event)">
            <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                <div class="modal-content border-0 shadow-lg rounded-4 bg-body">
                    <div class="modal-header border-bottom">
                        <div>
                            <h2 id="receipt-modal-title" class="modal-title h5 fw-bold">Konfirmasi penerimaan</h2>
                            <div class="small text-secondary">Catat jumlah yang benar-benar tiba. Jumlah rusak termasuk dalam jumlah diterima.</div>
                        </div>
                        <button type="button" class="btn-close" x-on:click="close()" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body p-4">
                        @foreach ($receiptLines as $key => $line)
                            <fieldset class="border rounded-3 p-3 mb-3" wire:key="receipt-line-{{ $key }}">
                                <legend class="float-none w-auto px-2 fs-6 fw-semibold">{{ $line['label'] }}</legend>
                                <div class="small text-secondary mb-2">Dikirim {{ number_format((float) $line['shipped_quantity'], 2, ',', '.') }} · masih dalam perjalanan {{ number_format((float) $line['remaining_quantity'], 2, ',', '.') }}</div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label" for="received-{{ $key }}">Jumlah diterima</label>
                                        <input id="received-{{ $key }}" type="number" min="0" max="{{ $line['remaining_quantity'] }}" step="0.01" class="form-control" wire:model="receiptLines.{{ $key }}.received_quantity" />
                                        @error('receiptLines.'.$key.'.received_quantity')<div class="text-danger small">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="damaged-{{ $key }}">Bagian rusak</label>
                                        <input id="damaged-{{ $key }}" type="number" min="0" step="0.01" class="form-control" wire:model="receiptLines.{{ $key }}.damaged_quantity" />
                                        @error('receiptLines.'.$key.'.damaged_quantity')<div class="text-danger small">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </fieldset>
                        @endforeach
                        @error('receiptLines')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror
                        <div class="mb-3">
                            <label for="receipt-proof" class="form-label fw-semibold">Foto bukti</label>
                            <input id="receipt-proof" type="file" accept="image/*" class="form-control" wire:model="arrivalProofImage" />
                            <div class="form-text">Wajib untuk kerusakan atau penerimaan sebagian/selisih. Maksimal 5 MB.</div>
                            @error('arrivalProofImage')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label for="receipt-notes" class="form-label fw-semibold">Catatan</label>
                            <textarea id="receipt-notes" class="form-control" rows="2" maxlength="500" wire:model="receiptNotes"></textarea>
                            @error('receiptNotes')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-outline-secondary" x-on:click="close()">Batal</button>
                        <button type="button" class="btn btn-success fw-semibold" x-on:click="submit('submitReceipt', 'receipt-action-{{ $receiptRequestId }}', 'dispatch-row-{{ $receiptRequestId }}')" wire:loading.attr="disabled" wire:target="submitReceipt,arrivalProofImage">
                            <span wire:loading.remove wire:target="submitReceipt">Simpan penerimaan</span>
                            <span wire:loading wire:target="submitReceipt">Menyimpan…</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endteleport
    @endif

    @if ($showCorrectionModal)
        @teleport('body')
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,.5);" role="dialog" aria-modal="true" aria-labelledby="correction-modal-title"
            x-data="{
                opener: document.activeElement,
                init() { this.$nextTick(() => this.focusables()[0]?.focus()); },
                focusables() { return [...this.$refs.dialog.querySelectorAll('button, input, select, textarea, a[href]')].filter(element => !element.disabled && element.offsetParent !== null); },
                async close() { const openerId = this.opener?.id; const opener = this.opener; await this.$wire.set('showCorrectionModal', false); window.requestAnimationFrame(() => { const target = openerId ? document.getElementById(openerId) : opener; const details = target?.closest('details'); if (details) details.open = true; target?.focus(); }); },
                async submit(action, targetId, rowId) { await this.$wire[action](); window.requestAnimationFrame(() => { if (this.$el.isConnected) return; const target = document.getElementById(targetId) || (rowId ? document.getElementById(rowId) : null); const details = target?.closest('details'); if (details) details.open = true; target?.focus(); }); },
                trap(event) { const items = this.focusables(); if (!items.length) return; const first = items[0], last = items[items.length - 1]; if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); } if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); } }
            }"
            x-ref="dialog" @keydown.escape.stop="close()" @keydown.tab="trap($event)">
            <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                <div class="modal-content border-0 shadow-lg rounded-4 bg-body">
                    <div class="modal-header border-bottom">
                        <div>
                            <h2 id="correction-modal-title" class="modal-title h5 fw-bold">Koreksi penerimaan</h2>
                            <div class="small text-secondary">Nilai di bawah adalah jumlah kumulatif setelah koreksi. Koreksi baru akan tersimpan sebagai kejadian tambahan.</div>
                        </div>
                        <button type="button" class="btn-close" x-on:click="close()" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body p-4">
                        @foreach ($correctionLines as $key => $line)
                            <fieldset class="border rounded-3 p-3 mb-3" wire:key="correction-line-{{ $key }}">
                                <legend class="float-none w-auto px-2 fs-6 fw-semibold">{{ $line['label'] }}</legend>
                                <div class="small text-secondary mb-2">Dikirim {{ number_format((float) $line['shipped_quantity'], 2, ',', '.') }}</div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label" for="correct-received-{{ $key }}">Jumlah diterima setelah koreksi</label>
                                        <input id="correct-received-{{ $key }}" type="number" min="0" max="{{ $line['shipped_quantity'] }}" step="0.01" class="form-control" wire:model="correctionLines.{{ $key }}.received_quantity" />
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="correct-damaged-{{ $key }}">Jumlah rusak setelah koreksi</label>
                                        <input id="correct-damaged-{{ $key }}" type="number" min="0" max="{{ $line['shipped_quantity'] }}" step="0.01" class="form-control" wire:model="correctionLines.{{ $key }}.damaged_quantity" />
                                    </div>
                                </div>
                            </fieldset>
                        @endforeach
                        @error('correctionLines')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror
                        <div class="mb-3">
                            <label for="correction-proof" class="form-label fw-semibold">Foto bukti</label>
                            <input id="correction-proof" type="file" accept="image/*" class="form-control" wire:model="correctionProofImage" />
                            @error('correctionProofImage')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label for="correction-notes" class="form-label fw-semibold">Alasan koreksi</label>
                            <textarea id="correction-notes" class="form-control" rows="2" maxlength="500" wire:model="correctionNotes"></textarea>
                            @error('correctionNotes')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-outline-secondary" x-on:click="close()">Batal</button>
                        <button type="button" class="btn btn-primary fw-semibold" x-on:click="submit('submitCorrection', 'receipt-correction-{{ $correctionReceiptId }}', '')" wire:loading.attr="disabled" wire:target="submitCorrection,correctionProofImage">Simpan koreksi</button>
                    </div>
                </div>
            </div>
        </div>
        @endteleport
    @endif

    @if ($showResolutionModal)
        @teleport('body')
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,.5);" role="dialog" aria-modal="true" aria-labelledby="resolution-modal-title"
            x-data="{
                opener: document.activeElement,
                init() { this.$nextTick(() => this.focusables()[0]?.focus()); },
                focusables() { return [...this.$refs.dialog.querySelectorAll('button, input, select, textarea, a[href]')].filter(element => !element.disabled && element.offsetParent !== null); },
                async close() { const opener = this.opener; await this.$wire.set('showResolutionModal', false); window.requestAnimationFrame(() => opener?.focus()); },
                async submit(action, targetId, rowId) { await this.$wire[action](); window.requestAnimationFrame(() => { if (this.$el.isConnected) return; const target = document.getElementById(targetId) || document.getElementById(rowId); const details = target?.closest('details'); if (details) details.open = true; target?.focus(); }); },
                trap(event) { const items = this.focusables(); if (!items.length) return; const first = items[0], last = items[items.length - 1]; if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); } if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); } }
            }"
            x-ref="dialog" @keydown.escape.stop="close()" @keydown.tab="trap($event)">
            <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                <div class="modal-content border-0 shadow-lg rounded-4 bg-body">
                    <div class="modal-header border-bottom">
                        <div>
                            <h2 id="resolution-modal-title" class="modal-title h5 fw-bold">
                                @switch($resolutionEventType)
                                    @case('return_to_warehouse') Catat barang kembali @break
                                    @case('declare_lost') Selesaikan sebagai kehilangan @break
                                    @case('dispose_damaged') Buang material rusak @break
                                @endswitch
                            </h2>
                            <div class="small text-secondary">Jumlah, batch, pelaku, waktu, dan alasan akan disimpan sebagai riwayat baru.</div>
                        </div>
                        <button type="button" class="btn-close" x-on:click="close()" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body p-4">
                        @if (count($resolutionChoices) > 1)
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="resolution-source">Batch dan gudang</label>
                                <select id="resolution-source" class="form-select" wire:model.live="resolutionLineKey">
                                    @foreach ($resolutionChoices as $key => $choice)
                                        <option value="{{ $key }}">{{ $choice['label'] }} · maks. {{ number_format($choice['quantity'], 2, ',', '.') }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <div class="small text-secondary mb-3">{{ $resolutionChoices[$resolutionLineKey]['label'] ?? '' }}</div>
                        @endif
                        @if ($resolutionEventType === 'return_to_warehouse' && $resolutionWarehouseChoices !== [])
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="resolution-warehouse">Gudang penerima (sumber lama tidak tercatat)</label>
                                <select id="resolution-warehouse" class="form-select" wire:model="resolutionWarehouseId">
                                    <option value="">Pilih gudang tempat barang benar-benar kembali</option>
                                    @foreach ($resolutionWarehouseChoices as $warehouseId => $warehouseName)
                                        <option value="{{ $warehouseId }}">{{ $warehouseName }}</option>
                                    @endforeach
                                </select>
                                @error('resolutionWarehouseId')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                        @endif
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="resolution-quantity">Jumlah (maks. {{ number_format((float) ($resolutionChoices[$resolutionLineKey]['quantity'] ?? 0), 2, ',', '.') }})</label>
                            <input id="resolution-quantity" type="number" min="{{ $resolutionItemType === 'material' ? '0.01' : '1' }}" max="{{ $resolutionChoices[$resolutionLineKey]['quantity'] ?? 0 }}" step="{{ $resolutionItemType === 'material' ? '0.01' : '1' }}" class="form-control" wire:model="resolutionQuantity" />
                            @error('resolutionQuantity')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label class="form-label fw-semibold" for="resolution-notes">Alasan dan bukti singkat</label>
                            <textarea id="resolution-notes" rows="3" maxlength="1000" class="form-control" wire:model="resolutionNotes" placeholder="Contoh: jumlah tidak ditemukan saat pemeriksaan lokasi"></textarea>
                            @error('resolutionNotes')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-outline-secondary" x-on:click="close()">Batal</button>
                        <button type="button" class="btn btn-success fw-semibold" x-on:click="submit('submitResolution', '', 'dispatch-row-{{ $resolutionRequestId }}')" wire:loading.attr="disabled" wire:target="submitResolution">
                            <span wire:loading.remove wire:target="submitResolution">Simpan penyelesaian</span>
                            <span wire:loading wire:target="submitResolution">Menyimpan…</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endteleport
    @endif
</div>

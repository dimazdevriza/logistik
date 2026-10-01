@php
    $warrantyStartsAt = now();
    $warrantyEndsAt = $warrantyStartsAt->copy()->addYear();
@endphp

<div>
    @if ($completionToast)
        @teleport('body')
            <div wire:key="transaction-feedback-{{ $completionToastSequence }}" class="transaction-feedback transaction-feedback--error" x-data="{ visible: true }" x-show="visible" role="alert" aria-live="assertive">
                <div class="transaction-feedback-content">
                    <strong>Periksa isian</strong>
                    <p class="mb-0">{{ $completionToast }}</p>
                </div>
                <button type="button" class="transaction-feedback-close" x-on:click="$wire.set('completionToast', '')" aria-label="Tutup pemberitahuan">Tutup</button>
            </div>
        @endteleport
    @endif

    <div class="container-fluid p-0">
        <!-- Hero Header -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-body-tertiary workflow-page-header house-finish-header">
            <div class="card-body p-4 p-md-5 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <div>
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <a href="{{ route('logistik.house-detail', $house) }}" wire:navigate class="back-link">
                            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8"/></svg>
                            Kembali
                        </a>
                        @if($house->house_code)
                            <span class="badge bg-secondary-subtle text-secondary font-mono">{{ $house->house_code }}</span>
                        @endif
                    </div>
                    <h1 class="display-5 fw-black text-body mb-2 font-outfit">Selesaikan <span>Proyek</span></h1>
                    <p class="text-secondary mb-1">{{ $house->name }}{{ $house->type ? ' · ' . $house->type : '' }}</p>
                    <p class="house-finish-warranty-note small mb-0">Masa garansi satu tahun dimulai setelah proyek selesai.</p>
                </div>
            </div>
        </div>

        @if ($errors->has('completion'))
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                {{ $errors->first('completion') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if ($errors->has('toolSelections'))
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                {{ $errors->first('toolSelections') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- STEP 1: Material Summary -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 p-3 p-md-4 bg-body-tertiary house-finish-section">
            <div class="house-finish-section-heading">
                <div class="house-finish-section-title">
                    <span class="house-finish-step-number">1</span>
                    <div>
                        <h2 class="fw-bold mb-1 font-outfit text-body">Ringkasan material digunakan</h2>
                        <p class="text-secondary small mb-0">Periksa jumlah dan biaya sebelum menutup catatan proyek.</p>
                    </div>
                </div>
                <span class="house-finish-section-count">{{ $materialUsages->total() }} catatan</span>
            </div>

            @if ($materialUsages->isEmpty())
                <div class="house-finish-material-empty-state border rounded-3 p-3 bg-body mb-3">
                    <p class="text-secondary mb-0">Tidak ada material yang digunakan.</p>
                    <div class="house-finish-material-empty-total">
                        <span class="fw-bold text-body">Total Biaya Material</span>
                        <span class="font-mono fw-black text-warning">Rp {{ number_format($totalMaterialCost, 0, ',', '.') }}</span>
                    </div>
                </div>
            @else
                <div class="table-responsive rounded-3 border mb-3 overflow-hidden house-finish-material-scroll">
                    <table class="table table-hover align-middle mb-0 house-finish-material-table">
                        <thead class="bg-body-secondary border-bottom">
                            <tr class="text-secondary extra-small text-uppercase font-geist tracking-wider">
                                <th class="text-center py-3 px-3" style="width: 50px;">No.</th>
                                <x-sortable-th field="material" :sort="$sort" class="py-3 px-3">Material</x-sortable-th>
                                <x-sortable-th field="quantity" :sort="$sort" class="text-end py-3 px-3">Jumlah</x-sortable-th>
                                <x-sortable-th field="total" :sort="$sort" class="text-end py-3 px-3">Total Biaya</x-sortable-th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach($materialUsages as $usage)
                            <tr wire:key="finish-m-{{ $usage->id }}">
                                <td class="text-center text-secondary small py-3 px-3">{{ ($materialUsages->currentPage() - 1) * $materialUsages->perPage() + $loop->iteration }}</td>
                                <td class="fw-bold text-body py-3 px-3">{{ $usage->material->name }}</td>
                                <td class="text-end fw-bold text-body py-3 px-3">{{ str_replace('.', ',', (float) rtrim(rtrim($usage->quantity, '0'), '.')) }} <span class="text-secondary small font-normal">{{ $usage->material->unit }}</span></td>
                                <td class="text-end font-mono fw-bold text-warning py-3 px-3">Rp {{ number_format($usage->total_cost, 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-body-tertiary border-top">
                            <tr>
                                <td class="fw-bold text-body py-3 px-3" colspan="3">Total Biaya Material</td>
                                <td class="text-end font-mono fw-black text-warning fs-5 py-3 px-3">Rp {{ number_format($totalMaterialCost, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

            <div class="d-flex justify-content-end">{{ $materialUsages->links() }}</div>
            @endif
        </div>

        <!-- STEP 2: Tool Accountability -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 p-3 p-md-4 bg-body-tertiary house-finish-section">
            <div class="house-finish-section-heading">
                <div class="house-finish-section-title">
                    <span class="house-finish-step-number">2</span>
                    <div>
                        <h2 class="fw-bold mb-1 font-outfit text-body">Pertanggungjawaban alat pinjaman</h2>
                        <p class="text-secondary small mb-0">Bagi jumlah dipinjam menurut kondisinya, lalu tentukan gudang penerima.</p>
                    </div>
                </div>
                <span class="house-finish-section-count">{{ $activeToolUsages->count() }} pinjaman aktif</span>
            </div>

            @if($activeToolUsages->isEmpty())
                <div class="house-finish-empty-tools">
                    <span class="house-finish-empty-tools-mark" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path fill-rule="evenodd" d="M13.854 3.646a.5.5 0 0 1 0 .708l-7 7a.5.5 0 0 1-.708 0l-3-3a.5.5 0 1 1 .708-.708L6.5 10.293l6.646-6.647a.5.5 0 0 1 .708 0"/></svg></span>
                    <div>
                        <h3 class="fw-semibold mb-1">Tidak ada pinjaman aktif</h3>
                        <p class="text-secondary small mb-0">Semua alat sudah dikembalikan. Anda dapat melanjutkan ke konfirmasi penyelesaian.</p>
                    </div>
                </div>
            @else
                <div class="house-finish-tool-list">
                    @foreach($activeToolUsages as $usage)
                        @php $selection = $toolSelections[$usage->id] ?? []; @endphp
                        <article class="house-finish-tool-entry" wire:key="tool-{{ $usage->id }}">
                            <header class="house-finish-tool-heading">
                                <div class="min-w-0">
                                    <span class="house-finish-tool-index">Alat {{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                    <h3 class="fw-bold text-body mb-1">{{ $usage->tool->name }}</h3>
                                    <span class="house-finish-tool-code">{{ $usage->tool->code }}</span>
                                </div>
                                <div class="house-finish-tool-quantity">
                                    <span>Jumlah dipinjam</span>
                                    <strong>{{ str_replace('.', ',', (float) $usage->quantity) }} <small>unit</small></strong>
                                </div>
                            </header>

                            <div class="house-finish-tool-fields">
                                <fieldset class="house-finish-condition-group">
                                    <legend>Pembagian kondisi <span>Total harus {{ str_replace('.', ',', (float) $usage->quantity) }} unit</span></legend>
                                    <div class="house-finish-condition-inputs">
                                        <label class="house-finish-condition-field text-success" for="tool-good-{{ $usage->id }}">
                                            <span>Baik</span>
                                            <input id="tool-good-{{ $usage->id }}" type="number" min="0" max="{{ $usage->quantity }}" wire:model.live="toolSelections.{{ $usage->id }}.qty_good" class="form-control font-mono" />
                                        </label>
                                        <label class="house-finish-condition-field text-danger" for="tool-broken-{{ $usage->id }}">
                                            <span>Rusak</span>
                                            <input id="tool-broken-{{ $usage->id }}" type="number" min="0" max="{{ $usage->quantity }}" wire:model.live="toolSelections.{{ $usage->id }}.qty_broken" class="form-control font-mono" />
                                        </label>
                                        <label class="house-finish-condition-field text-secondary" for="tool-lost-{{ $usage->id }}">
                                            <span>Hilang</span>
                                            <input id="tool-lost-{{ $usage->id }}" type="number" min="0" max="{{ $usage->quantity }}" wire:model.live="toolSelections.{{ $usage->id }}.qty_lost" class="form-control font-mono" />
                                        </label>
                                    </div>
                                </fieldset>

                                <div class="house-finish-field">
                                    <label for="tool-warehouse-{{ $usage->id }}" class="form-label">Gudang penerima</label>
                                    <select id="tool-warehouse-{{ $usage->id }}" wire:model.live="toolSelections.{{ $usage->id }}.receiving_warehouse_id" class="form-select">
                                        <option value="">Pilih gudang</option>
                                        @foreach($warehouses as $warehouse)
                                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="house-finish-field">
                                    <label for="tool-notes-{{ $usage->id }}" class="form-label">Keterangan <span>(opsional)</span></label>
                                    <input id="tool-notes-{{ $usage->id }}" type="text" wire:model="toolSelections.{{ $usage->id }}.notes" placeholder="Catatan kondisi alat..." class="form-control" />
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- STEP 3: Confirmation -->
        <div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 bg-body-tertiary house-finish-section">
            <div class="house-finish-section-title mb-3">
                <span class="house-finish-step-number">3</span>
                <div>
                    <h2 class="fw-bold mb-1 font-outfit text-body">Konfirmasi penyelesaian</h2>
                    <p class="text-secondary small mb-0">Pastikan seluruh catatan dan pengembalian alat sudah benar.</p>
                </div>
            </div>

            <div class="house-finish-final-action">
                <div>
                    <strong>Setelah proyek selesai</strong>
                    <p class="mb-0 small">Setelah ditandai Selesai, rumah masuk masa garansi selama 1 tahun, mulai {{ $warrantyStartsAt->format('d/m/Y') }} sampai {{ $warrantyEndsAt->format('d/m/Y') }}. Catatan material yang ada dikunci.</p>
                </div>
                <button type="button" wire:click="confirm('processCompletion', null, 'Selesaikan Proyek?')" class="btn btn-success font-semibold text-nowrap d-inline-flex align-items-center gap-2 {{ $completionBlocked ? 'house-finish-button-blocked' : '' }}" aria-disabled="{{ $completionBlocked ? 'true' : 'false' }}" x-data="{ shaking: false }" x-on:house-finish-invalid.window="shaking = false; setTimeout(() => shaking = true, 10); setTimeout(() => shaking = false, 650)" x-bind:class="{ 'house-finish-button-jiggle': shaking }" @if($activeToolUsages->isNotEmpty()) aria-describedby="house-finish-return-requirement" @endif>
                    <svg width="15" height="15" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M3 1.5a.5.5 0 0 1 .5.5v.73c1.8-1.04 3.7-1.04 5.5 0 1.47.85 2.93.85 4.4 0V9.5c-1.8 1.04-3.7 1.04-5.5 0-1.47-.85-2.93-.85-4.4 0v5a.5.5 0 0 1-1 0V2a.5.5 0 0 1 .5-.5Z"/></svg>
                    Selesaikan Proyek
                </button>
            </div>
            @if ($activeToolUsages->isNotEmpty())
                <p id="house-finish-return-requirement" class="house-finish-return-requirement small mb-0">Isi jumlah baik, rusak, atau hilang sesuai jumlah pinjaman, lalu pilih gudang penerima.</p>
            @endif
        </div>
    </div>

    <!-- Confirmation Modal -->
    @if($showConfirmation)
    @teleport('body')
    <div class="modal fade show d-block house-finish-confirmation-backdrop" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title font-outfit fw-bold">{{ $confirmTitle ?? 'Konfirmasi' }}</h5>
                    <button type="button" class="btn-close" wire:click="$set('showConfirmation', false)"></button>
                </div>
                <div class="modal-body py-4">
                    @if($confirmingAction === 'processCompletion')
                        <div class="d-grid gap-3 house-finish-confirmation-copy">
                            <p class="text-secondary mb-0 lh-base">Rumah akan ditandai <strong class="text-body">Selesai</strong> dan menerima masa garansi berikut.</p>
                            <div class="border rounded-3 p-3 bg-body-tertiary">
                                <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                                    <span class="small text-secondary">Masa garansi</span>
                                    <strong class="text-success">1 tahun</strong>
                                </div>
                                <div class="d-flex align-items-center justify-content-between gap-3">
                                    <div>
                                        <span class="d-block small text-secondary">Mulai</span>
                                        <strong>{{ $warrantyStartsOn }}</strong>
                                    </div>
                                    <div class="text-end">
                                        <span class="d-block small text-secondary">Berakhir</span>
                                        <strong>{{ $warrantyEndsOn }}</strong>
                                    </div>
                                </div>
                            </div>
                            <p class="text-secondary mb-0 lh-base">Alat baik atau rusak masuk ke gudang penerima. Catatan material yang ada akan dikunci.</p>
                        </div>
                    @else
                        <p class="text-secondary mb-0 small lh-base">{{ $confirmMessage ?? 'Apakah Anda yakin ingin melakukan tindakan ini?' }}</p>
                    @endif
                </div>
                <div class="modal-footer border-top bg-body-tertiary rounded-bottom-4">
                    <button type="button" class="btn btn-secondary btn-sm fw-semibold" wire:click="$set('showConfirmation', false)">Batal</button>
                    <button type="button" class="btn btn-success btn-sm fw-semibold" wire:click="executeConfirmedAction" wire:loading.attr="disabled" wire:target="executeConfirmedAction">Ya, Selesaikan</button>
                </div>
            </div>
        </div>
    </div>
    @endteleport
    @endif
</div>

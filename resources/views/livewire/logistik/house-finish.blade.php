<div>
    <div class="container-fluid p-0">
        <!-- Hero Header -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-body-tertiary">
            <div class="card-body p-4 p-md-5 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <a href="{{ route('logistik.house-detail', $house) }}" wire:navigate class="btn btn-outline-secondary btn-sm font-semibold">
                            ← Kembali
                        </a>
                        <span class="badge bg-warning-subtle text-warning">Penyelesaian Proyek</span>
                    </div>
                    <h1 class="display-5 fw-black text-body mb-1 font-outfit">Selesaikan Proyek</h1>
                    <p class="text-secondary mb-0">Unit: {{ $house->name }}{{ $house->type ? ' — ' . $house->type : '' }}</p>
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
        <div class="card border-0 shadow-sm rounded-4 mb-4 p-4 bg-body-tertiary">
            <div class="d-flex align-items-center gap-2 mb-3">
                <span class="badge bg-warning rounded-circle p-2">1</span>
                <h5 class="fw-bold mb-0 font-outfit">Ringkasan Material Digunakan</h5>
            </div>

            <div class="table-responsive mb-3">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-uppercase small font-geist">
                        <tr>
                            <th class="text-center" style="width: 50px;">No.</th>
                            <th>Material</th>
                            <th class="text-end">Jumlah</th>
                            <th class="text-end">Total Biaya</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($materialUsages as $usage)
                        <tr wire:key="finish-m-{{ $usage->id }}">
                            <td class="text-center text-secondary small">{{ ($materialUsages->currentPage() - 1) * $materialUsages->perPage() + $loop->iteration }}</td>
                            <td class="fw-bold text-body">{{ $usage->material->name }}</td>
                            <td class="text-end fw-bold">{{ str_replace('.', ',', (float) rtrim(rtrim($usage->quantity, '0'), '.')) }} <span class="text-secondary small font-normal">{{ $usage->material->unit }}</span></td>
                            <td class="text-end font-mono fw-bold text-warning">Rp {{ number_format($usage->total_cost, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-secondary">Tidak ada material yang digunakan.</td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td class="fw-bold" colspan="3">Total Biaya Material</td>
                            <td class="text-end font-mono fw-black text-warning fs-5">Rp {{ number_format($totalMaterialCost, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="d-flex justify-content-end">{{ $materialUsages->links() }}</div>
        </div>

        <!-- STEP 2: Tool Accountability -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 p-4 bg-body-tertiary">
            <div class="d-flex align-items-center gap-2 mb-3">
                <span class="badge bg-primary rounded-circle p-2">2</span>
                <h5 class="fw-bold mb-0 font-outfit">Pertanggungjawaban Peminjaman Alat</h5>
                <span class="text-secondary small">— Tentukan kondisi setiap unit alat yang dikembalikan.</span>
            </div>

            @if($activeToolUsages->isEmpty())
                <div class="text-center py-4 text-success">
                    <h6>✓ Semua alat telah dikembalikan sebelumnya.</h6>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-uppercase small font-geist">
                            <tr>
                                <th class="text-center" style="width: 50px;">No.</th>
                                <th>Alat</th>
                                <th class="text-center" style="width: 60px;">Qty</th>
                                <th>Status Pengembalian</th>
                                <th>Keterangan &amp; Ganti Rugi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($activeToolUsages as $usage)
                            @php $currentAction = $toolSelections[$usage->id]['action'] ?? 'normal'; @endphp
                            <tr wire:key="tool-{{ $usage->id }}">
                                <td class="text-center text-secondary small">{{ $loop->iteration }}</td>
                                <td>
                                    <div class="fw-bold">{{ $usage->tool->name }}</div>
                                    <span class="badge bg-secondary-subtle text-secondary font-mono extra-small">{{ $usage->tool->code }}</span>
                                </td>
                                <td class="text-center font-mono fw-bold">{{ str_replace('.', ',', (float) $usage->quantity) }}</td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <input type="radio" class="btn-check" wire:model.live="toolSelections.{{ $usage->id }}.action" id="action-normal-{{ $usage->id }}" value="normal" autocomplete="off">
                                        <label class="btn btn-outline-success" for="action-normal-{{ $usage->id }}">✓ Baik</label>

                                        <input type="radio" class="btn-check" wire:model.live="toolSelections.{{ $usage->id }}.action" id="action-broken-{{ $usage->id }}" value="broken" autocomplete="off">
                                        <label class="btn btn-outline-danger" for="action-broken-{{ $usage->id }}">✕ Rusak</label>

                                        <input type="radio" class="btn-check" wire:model.live="toolSelections.{{ $usage->id }}.action" id="action-lost-{{ $usage->id }}" value="lost" autocomplete="off">
                                        <label class="btn btn-outline-secondary" for="action-lost-{{ $usage->id }}">? Hilang</label>
                                    </div>
                                </td>
                                <td>
                                    @if(in_array($currentAction, ['broken', 'lost']))
                                    <div class="vstack gap-2" style="max-width: 280px;">
                                        <input type="text" wire:model="toolSelections.{{ $usage->id }}.notes" placeholder="Keterangan..." class="form-control form-control-sm" />
                                        <div class="form-check">
                                            <input type="checkbox" wire:model.live="toolSelections.{{ $usage->id }}.has_charge" class="form-check-input" id="charge-{{ $usage->id }}">
                                            <label class="form-check-label extra-small fw-bold text-danger" for="charge-{{ $usage->id }}">Terapkan ganti rugi</label>
                                        </div>
                                    </div>
                                    @else
                                    <span class="text-secondary extra-small">—</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <!-- STEP 3: Confirmation -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-body-tertiary">
            <div class="d-flex align-items-center gap-2 mb-3">
                <span class="badge bg-warning rounded-circle p-2">3</span>
                <h5 class="fw-bold mb-0 font-outfit">Konfirmasi Penyelesaian</h5>
            </div>

            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 p-3 bg-warning-subtle rounded-3">
                <p class="text-warning-emphasis mb-0 small">
                    Status rumah akan berubah menjadi <strong>Selesai</strong> dan semua data transaksi akan dikunci.
                </p>
                <button type="button" wire:click="confirm('processCompletion', null, 'Selesaikan Proyek?', 'Apakah Anda yakin ingin menyelesaikan proyek ini? Status rumah akan berubah menjadi Selesai dan penggunaan material akan dikunci. Tindakan ini tidak dapat dibatalkan.')" class="btn btn-success font-semibold text-nowrap">
                    🚩 Selesaikan Proyek
                </button>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    @if($showConfirmation)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title font-outfit fw-bold">{{ $confirmTitle ?? 'Konfirmasi' }}</h5>
                    <button type="button" class="btn-close" wire:click="$set('showConfirmation', false)"></button>
                </div>
                <div class="modal-body py-3">
                    <p class="text-secondary mb-0 small">{{ $confirmMessage ?? 'Apakah Anda yakin ingin melakukan tindakan ini?' }}</p>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-secondary btn-sm font-semibold" wire:click="$set('showConfirmation', false)">Batal</button>
                    <button type="button" class="btn btn-success btn-sm font-semibold" wire:click="executeConfirmedAction">Ya, Selesaikan</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

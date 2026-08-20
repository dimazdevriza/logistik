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
                <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-warning text-dark fw-bold font-mono flex-shrink-0" style="width: 28px; height: 28px; font-size: 0.85rem;">1</span>
                <h5 class="fw-bold mb-0 font-outfit text-body">Ringkasan Material Digunakan</h5>
            </div>

            <div class="table-responsive rounded-3 border mb-3 overflow-hidden">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-body-secondary border-bottom">
                        <tr class="text-secondary extra-small text-uppercase font-geist tracking-wider">
                            <th class="text-center py-3 px-3" style="width: 50px;">No.</th>
                            <th class="py-3 px-3">Material</th>
                            <th class="text-end py-3 px-3">Jumlah</th>
                            <th class="text-end py-3 px-3">Total Biaya</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($materialUsages as $usage)
                        <tr wire:key="finish-m-{{ $usage->id }}">
                            <td class="text-center text-secondary small py-3 px-3">{{ ($materialUsages->currentPage() - 1) * $materialUsages->perPage() + $loop->iteration }}</td>
                            <td class="fw-bold text-body py-3 px-3">{{ $usage->material->name }}</td>
                            <td class="text-end fw-bold text-body py-3 px-3">{{ str_replace('.', ',', (float) rtrim(rtrim($usage->quantity, '0'), '.')) }} <span class="text-secondary small font-normal">{{ $usage->material->unit }}</span></td>
                            <td class="text-end font-mono fw-bold text-warning py-3 px-3">Rp {{ number_format($usage->total_cost, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-secondary extra-small">Tidak ada material yang digunakan.</td>
                        </tr>
                        @endforelse
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
        </div>

        <!-- STEP 2: Tool Accountability -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 p-4 bg-body-tertiary">
            <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center gap-2 mb-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary text-white fw-bold font-mono flex-shrink-0" style="width: 28px; height: 28px; font-size: 0.85rem;">2</span>
                    <h5 class="fw-bold mb-0 font-outfit text-body">Pertanggungjawaban Peminjaman Alat</h5>
                </div>
                <span class="text-secondary extra-small">— Tentukan kondisi setiap unit alat yang dikembalikan.</span>
            </div>

            @if($activeToolUsages->isEmpty())
                <div class="text-center py-4 text-success border rounded-3 bg-body p-3">
                    <h6 class="mb-0 fw-bold">✓ Semua alat telah dikembalikan sebelumnya.</h6>
                </div>
            @else
                <div class="table-responsive rounded-3 border overflow-hidden">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-body-secondary border-bottom">
                            <tr class="text-secondary extra-small text-uppercase font-geist tracking-wider">
                                <th class="text-center py-3 px-3" style="width: 50px;">No.</th>
                                <th class="py-3 px-3">Alat</th>
                                <th class="text-center py-3 px-3" style="width: 60px;">Qty</th>
                                <th class="py-3 px-3">Status Pengembalian</th>
                                <th class="py-3 px-3">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach($activeToolUsages as $usage)
                            @php $currentAction = $toolSelections[$usage->id]['action'] ?? 'normal'; @endphp
                            <tr wire:key="tool-{{ $usage->id }}">
                                <td class="text-center text-secondary small py-3 px-3">{{ $loop->iteration }}</td>
                                <td class="py-3 px-3">
                                    <div class="fw-bold text-body small">{{ $usage->tool->name }}</div>
                                    <span class="badge bg-secondary-subtle text-secondary font-mono extra-small">{{ $usage->tool->code }}</span>
                                </td>
                                <td class="text-center font-mono fw-bold text-body py-3 px-3">{{ str_replace('.', ',', (float) $usage->quantity) }}</td>
                                <td class="py-3 px-3">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <input type="radio" class="btn-check" wire:model.live="toolSelections.{{ $usage->id }}.action" id="action-normal-{{ $usage->id }}" value="normal" autocomplete="off">
                                        <label class="btn btn-outline-success font-semibold px-2.5 py-1" for="action-normal-{{ $usage->id }}">✓ Baik</label>

                                        <input type="radio" class="btn-check" wire:model.live="toolSelections.{{ $usage->id }}.action" id="action-broken-{{ $usage->id }}" value="broken" autocomplete="off">
                                        <label class="btn btn-outline-danger font-semibold px-2.5 py-1" for="action-broken-{{ $usage->id }}">✕ Rusak</label>

                                        <input type="radio" class="btn-check" wire:model.live="toolSelections.{{ $usage->id }}.action" id="action-lost-{{ $usage->id }}" value="lost" autocomplete="off">
                                        <label class="btn btn-outline-secondary font-semibold px-2.5 py-1" for="action-lost-{{ $usage->id }}">? Hilang</label>
                                    </div>
                                </td>
                                <td class="py-3 px-3">
                                    @if(in_array($currentAction, ['broken', 'lost']))
                                    <div style="max-width: 280px;">
                                        <input type="text" wire:model="toolSelections.{{ $usage->id }}.notes" placeholder="Catatan kondisi..." class="form-control form-control-sm" />
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
                <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-warning text-dark fw-bold font-mono flex-shrink-0" style="width: 28px; height: 28px; font-size: 0.85rem;">3</span>
                <h5 class="fw-bold mb-0 font-outfit text-body">Konfirmasi Penyelesaian</h5>
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
    @teleport('body')
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title font-outfit fw-bold">{{ $confirmTitle ?? 'Konfirmasi' }}</h5>
                    <button type="button" class="btn-close" wire:click="$set('showConfirmation', false)"></button>
                </div>
                <div class="modal-body py-4">
                    <p class="text-secondary mb-0 small">{{ $confirmMessage ?? 'Apakah Anda yakin ingin melakukan tindakan ini?' }}</p>
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

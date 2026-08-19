<div>
    <div class="container-fluid p-0">
        <!-- Hero Header -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-body-tertiary">
            <div class="card-body p-4 p-md-5">
                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 text-uppercase mb-2 font-geist small">Pusat Kendali Logistik</span>
                <h1 class="display-5 fw-black text-body mb-2 font-outfit">
                    Antrean Pengiriman & <span class="text-success">Approval Barang</span>
                </h1>
                <p class="text-secondary mb-0 max-w-xl">
                    Kelola pengajuan dari Mandor, proses pengiriman paket ke lokasi pembangunan, dan verifikasi foto bukti penerimaan barang.
                </p>
            </div>
        </div>

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

        <!-- Filter tabs -->
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-body-tertiary mb-4">
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-sm {{ $filterStatus === 'all' ? 'btn-success' : 'btn-outline-secondary' }}" wire:click="$set('filterStatus', 'all')">Semua Transaksi</button>
                <button type="button" class="btn btn-sm {{ $filterStatus === 'pending' ? 'btn-warning text-dark' : 'btn-outline-secondary' }}" wire:click="$set('filterStatus', 'pending')">Perlu Dikirim (Pending)</button>
                <button type="button" class="btn btn-sm {{ $filterStatus === 'arrived' ? 'btn-info text-dark' : 'btn-outline-secondary' }}" wire:click="$set('filterStatus', 'arrived')">Perlu Approval (Barang Tiba)</button>
                <button type="button" class="btn btn-sm {{ $filterStatus === 'approved' ? 'btn-success' : 'btn-outline-secondary' }}" wire:click="$set('filterStatus', 'approved')">Selesai (Approved)</button>
            </div>
        </div>

        <!-- Requests table -->
        <div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 bg-body-tertiary">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr class="text-secondary extra-small text-uppercase">
                            <th>Kode & Waktu</th>
                            <th>Mandor (Requester)</th>
                            <th>Unit Rumah & Item</th>
                            <th class="text-end">Jumlah (Qty)</th>
                            <th>Status & Bukti Tiba</th>
                            <th class="text-end">Tindakan Logistik</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($requests as $req)
                            <tr>
                                <td>
                                    <div class="fw-bold text-body small font-mono">{{ $req->request_code }}</div>
                                    <div class="extra-small text-secondary">{{ $req->created_at->format('d/m/Y H:i') }}</div>
                                </td>
                                <td>
                                    <div class="fw-bold text-body">{{ $req->requester->name ?? '-' }}</div>
                                    <div class="extra-small text-secondary">{{ ucfirst($req->requester->role ?? '') }}</div>
                                </td>
                                <td>
                                    <div class="fw-bold text-body">{{ $req->house->name ?? '-' }} ({{ $req->house->block ?? '-' }})</div>
                                    <div class="extra-small text-secondary">
                                        <span class="badge {{ $req->type === 'material' ? 'bg-primary-subtle text-primary' : 'bg-secondary-subtle text-secondary' }} me-1">
                                            {{ strtoupper($req->type) }}
                                        </span>
                                        {{ $req->type === 'material' ? ($req->material->name ?? '-') : ($req->tool->name ?? '-') }}
                                    </div>
                                    @if ($req->notes)
                                        <div class="extra-small text-secondary fst-italic">Peruntukkan: {{ $req->notes }}</div>
                                    @endif
                                </td>
                                <td class="text-end font-mono fw-bold">
                                    {{ number_format($req->quantity, 0, ',', '.') }}
                                </td>
                                <td>
                                    @if ($req->status === 'pending')
                                        <span class="badge bg-warning-subtle text-warning">Menunggu Kirim</span>
                                    @elseif ($req->status === 'dispatched')
                                        <span class="badge bg-primary-subtle text-primary">Dikirim (Menunggu Mandor)</span>
                                    @elseif ($req->status === 'arrived')
                                        <span class="badge bg-info-subtle text-info">Barang Tiba</span>
                                    @elseif ($req->status === 'approved')
                                        <span class="badge bg-success-subtle text-success">Selesai (Approved)</span>
                                    @elseif ($req->status === 'rejected')
                                        <span class="badge bg-danger-subtle text-danger">Ditolak</span>
                                    @endif

                                    @if ($req->arrival_proof_image)
                                        <div class="mt-1">
                                            <a href="{{ asset('storage/' . $req->arrival_proof_image) }}" target="_blank" class="badge bg-info text-dark text-decoration-none d-inline-flex align-items-center gap-1" title="Lihat Foto Bukti Arrival">
                                                <svg width="12" height="12" fill="currentColor"><use href="#i-camera"/></svg> Foto Bukti Mandor
                                            </a>
                                        </div>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if ($req->status === 'pending')
                                        <button type="button" class="btn btn-sm btn-primary rounded-3 font-semibold d-inline-flex align-items-center gap-1" wire:click="dispatchRequest({{ $req->id }})">
                                            <svg width="14" height="14" fill="currentColor"><use href="#i-truck"/></svg> Kirim Barang
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger rounded-3 ms-1" wire:click="rejectRequest({{ $req->id }})">
                                            Tolak
                                        </button>
                                    @elseif ($req->status === 'arrived')
                                        <button type="button" class="btn btn-sm btn-success rounded-3 font-semibold" wire:click="approveRequest({{ $req->id }})">
                                            ✓ Approve & Potong Stok
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger rounded-3 ms-1" wire:click="rejectRequest({{ $req->id }})">
                                            Tolak
                                        </button>
                                    @elseif ($req->status === 'approved')
                                        <span class="extra-small text-success fw-bold">✓ Approved</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-secondary">Belum ada antrean pengiriman barang.</td>
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
</div>

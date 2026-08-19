<div>
    <div class="container-fluid p-0">
        <!-- Hero Header -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-body-tertiary">
            <div class="card-body p-4 p-md-5">
                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 text-uppercase mb-2 font-geist small">Area Mandor</span>
                <h1 class="display-5 fw-black text-body mb-2 font-outfit">
                    Pengajuan & <span class="text-success">Penerimaan Barang</span>
                </h1>
                <p class="text-secondary mb-0 max-w-xl">
                    Ajukan permintaan material/alat untuk unit rumah dan unggah foto bukti saat barang tiba di lapangan.
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

        <div class="row g-4">
            <!-- Left Form: Request Item -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 bg-body-tertiary">
                    <h5 class="fw-bold font-outfit text-body mb-3">Buat Permintaan Baru</h5>

                    <form wire:submit.prevent="submitRequest" class="vstack gap-3">
                        <div>
                            <label class="form-label fw-semibold">Jenis Item</label>
                            <div class="btn-group w-100" role="group">
                                <button type="button" class="btn {{ $type === 'material' ? 'btn-success' : 'btn-outline-secondary' }}" wire:click="$set('type', 'material')">Material</button>
                                <button type="button" class="btn {{ $type === 'tool' ? 'btn-success' : 'btn-outline-secondary' }}" wire:click="$set('type', 'tool')">Alat</button>
                            </div>
                        </div>

                        <div>
                            <label class="form-label fw-semibold">Unit Rumah Tujuan</label>
                            <select wire:model="house_id" class="form-select">
                                <option value="">-- Pilih Unit Rumah --</option>
                                @foreach ($houses as $h)
                                    <option value="{{ $h->id }}">{{ $h->name }} ({{ $h->block }})</option>
                                @endforeach
                            </select>
                            @error('house_id') <span class="text-danger small fw-semibold">{{ $message }}</span> @enderror
                        </div>

                        @if ($type === 'material')
                            <div>
                                <label class="form-label fw-semibold">Material</label>
                                <select wire:model="material_id" class="form-select">
                                    <option value="">-- Pilih Material --</option>
                                    @foreach ($materials as $m)
                                        <option value="{{ $m->id }}">{{ $m->name }} (stok: {{ $m->stock }} {{ $m->unit }})</option>
                                    @endforeach
                                </select>
                                @error('material_id') <span class="text-danger small fw-semibold">{{ $message }}</span> @enderror
                            </div>
                        @else
                            <div>
                                <label class="form-label fw-semibold">Alat</label>
                                <select wire:model="tool_id" class="form-select">
                                    <option value="">-- Pilih Alat --</option>
                                    @foreach ($tools as $t)
                                        <option value="{{ $t->id }}">{{ $t->name }} (tersedia: {{ $t->available_qty }})</option>
                                    @endforeach
                                </select>
                                @error('tool_id') <span class="text-danger small fw-semibold">{{ $message }}</span> @enderror
                            </div>
                        @endif

                        <div>
                            <label class="form-label fw-semibold">Jumlah (Qty)</label>
                            <input type="number" step="0.01" wire:model="quantity" class="form-control font-mono" placeholder="1.00" />
                            @error('quantity') <span class="text-danger small fw-semibold">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="form-label fw-semibold">Peruntukkan / Catatan</label>
                            <input type="text" wire:model="notes" class="form-control" placeholder="Contoh: Pekerjaan Cor Sloof Blok A" />
                            @error('notes') <span class="text-danger small fw-semibold">{{ $message }}</span> @enderror
                        </div>

                        <button type="submit" class="btn btn-success fw-bold py-2.5 rounded-3">Kirim Permintaan ke Logistik</button>
                    </form>
                </div>
            </div>

            <!-- Right Table: My Requests -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 bg-body-tertiary">
                    <h5 class="fw-bold font-outfit text-body mb-3">Daftar Permintaan Saya</h5>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr class="text-secondary extra-small text-uppercase">
                                    <th>Kode & Tanggal</th>
                                    <th>Rumah & Item</th>
                                    <th class="text-end">Qty</th>
                                    <th>Status</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($myRequests as $req)
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-body small font-mono">{{ $req->request_code }}</div>
                                            <div class="extra-small text-secondary">{{ $req->created_at->format('d/m/Y H:i') }}</div>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-body">{{ $req->house->name ?? '-' }}</div>
                                            <div class="extra-small text-secondary">
                                                {{ $req->type === 'material' ? ($req->material->name ?? '-') : ($req->tool->name ?? '-') }}
                                            </div>
                                        </td>
                                        <td class="text-end font-mono fw-bold">
                                            {{ number_format($req->quantity, 0, ',', '.') }}
                                        </td>
                                        <td>
                                            @if ($req->status === 'pending')
                                                <span class="badge bg-warning-subtle text-warning">Menunggu Logistik</span>
                                            @elseif ($req->status === 'dispatched')
                                                <span class="badge bg-primary-subtle text-primary">Dalam Pengiriman</span>
                                            @elseif ($req->status === 'arrived')
                                                <span class="badge bg-info-subtle text-info">Barang Tiba (Menunggu Approval)</span>
                                            @elseif ($req->status === 'approved')
                                                <span class="badge bg-success-subtle text-success">Selesai (Approved)</span>
                                            @elseif ($req->status === 'rejected')
                                                <span class="badge bg-danger-subtle text-danger">Ditolak</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if ($req->status === 'dispatched')
                                                <button type="button" class="btn btn-sm btn-primary rounded-3 font-semibold d-inline-flex align-items-center gap-1" wire:click="openProofModal({{ $req->id }})">
                                                    <svg width="14" height="14" fill="currentColor"><use href="#i-camera"/></svg> Barang Tiba
                                                </button>
                                            @elseif ($req->arrival_proof_image)
                                                <a href="{{ asset('storage/' . $req->arrival_proof_image) }}" target="_blank" class="btn btn-sm btn-outline-info rounded-3">
                                                    Lihat Bukti
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-secondary">Belum ada pengajuan permintaan.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $myRequests->links() }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Upload Arrival Proof Modal -->
        @if ($showProofModal)
            <div class="modal fade show d-block" style="background-color: rgba(0,0,0,0.5);" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content rounded-4 border-0 shadow-lg bg-body">
                        <div class="modal-header border-bottom">
                            <h5 class="modal-title fw-bold font-outfit">Unggah Foto Bukti Penerimaan Barang</h5>
                            <button type="button" class="btn-close" wire:click="$set('showProofModal', false)"></button>
                        </div>
                        <form wire:submit.prevent="submitArrivalProof">
                            <div class="modal-body py-4">
                                <p class="text-secondary small mb-3">Ambil atau unggah foto barang yang sudah sampai di unit lokasi pembangunan sebagai verifikasi.</p>
                                <div>
                                    <label class="form-label fw-semibold">Foto Bukti Barang Tiba</label>
                                    <input type="file" wire:model="arrivalProofImage" accept="image/*" capture="environment" class="form-control" />
                                    @error('arrivalProofImage') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="modal-footer border-top">
                                <button type="button" class="btn btn-secondary rounded-3" wire:click="$set('showProofModal', false)">Batal</button>
                                <button type="submit" class="btn btn-success rounded-3 fw-bold">Konfirmasi Barang Tiba</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

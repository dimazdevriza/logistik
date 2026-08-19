<div>
    <div class="container-fluid p-0">
        <!-- Hero Header -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-body-tertiary">
            <div class="card-body p-4 p-md-5 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 text-uppercase mb-2 font-geist small">Laporan Finansial</span>
                    <h1 class="display-5 fw-black text-body mb-2 font-outfit">
                        Monitoring Biaya <span class="text-success">Pembangunan Rumah</span>
                    </h1>
                    <p class="text-secondary mb-0 max-w-xl">
                        Monitor biaya pembangunan dan total alokasi material setiap unit rumah.
                    </p>
                </div>
                <div>
                    <button type="button" wire:click="exportExcel" class="btn btn-outline-success font-semibold">Export Excel</button>
                </div>
            </div>
        </div>

        <!-- Summary Card -->
        <div class="row g-4 mb-4">
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 border-start border-4 border-warning shadow-sm rounded-4 p-4 bg-body-tertiary">
                    <span class="small fw-bold text-secondary text-uppercase tracking-wider mb-2 d-block">Total Biaya Material (Semua Rumah)</span>
                    <h2 class="fw-black text-warning font-mono mb-0">Rp {{ number_format($totalSpent, 0, ',', '.') }}</h2>
                </div>
            </div>
        </div>

        <!-- Search & Filter Controls -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 p-3 bg-body-tertiary">
            <div class="d-flex flex-column flex-md-row gap-3 align-items-md-center">
                <div class="w-100 max-w-sm">
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari rumah, kode, atau tipe..." class="form-control" />
                </div>
                <div class="max-w-[180px]">
                    <select wire:model.live="filterStatus" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="perencanaan">Perencanaan</option>
                        <option value="pembangunan">Pembangunan</option>
                        <option value="selesai">Selesai</option>
                    </select>
                </div>
                @if ($search || $filterStatus)
                    <button type="button" wire:click="resetFilters" class="btn btn-link text-secondary text-decoration-none btn-sm">✕ Reset Filter</button>
                @endif
            </div>
        </div>

        <!-- House Costs Table -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-uppercase small font-geist">
                        <tr>
                            <th class="text-center" style="width: 50px;">No.</th>
                            <th>Kode</th>
                            <th>Rumah</th>
                            <th>Tipe</th>
                            <th>Status</th>
                            <th class="text-end">Biaya Material</th>
                            <th class="text-center">Transaksi</th>
                            <th class="text-end" style="width: 100px;">Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($houses as $house)
                        @php
                            $spent = $house->material_usages_sum_total_cost ?? 0;
                            $statusClasses = ['perencanaan' => 'bg-warning-subtle text-warning', 'pembangunan' => 'bg-primary-subtle text-primary', 'selesai' => 'bg-success-subtle text-success'];
                        @endphp
                        <tr wire:key="h-cost-{{ $house->id }}" style="cursor: pointer;" x-on:click="if (!$event.target.closest('button') && !$event.target.closest('a')) { window.Livewire.navigate('{{ route('admin.house-costs.detail', $house) }}') }">
                            <td class="text-center text-secondary small">{{ $houses->firstItem() + $loop->index }}</td>
                            <td class="font-mono text-secondary small">{{ $house->house_code ?? '-' }}</td>
                            <td class="fw-bold text-body">{{ $house->name }}</td>
                            <td class="text-secondary small">{{ $house->type }}</td>
                            <td>
                                <span class="badge {{ $statusClasses[$house->status] ?? 'bg-secondary-subtle text-secondary' }}">{{ ucfirst($house->status) }}</span>
                            </td>
                            <td class="text-end font-mono fw-bold text-warning">Rp {{ number_format($spent, 0, ',', '.') }}</td>
                            <td class="text-center font-mono">{{ $house->material_usages_count ?? 0 }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.house-costs.detail', $house) }}" wire:navigate class="btn btn-outline-secondary btn-sm">Lihat</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-secondary">Belum ada data rumah.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="d-flex justify-content-end">{{ $houses->links() }}</div>
    </div>
</div>

<div>
    <div class="container-fluid p-0">
        <!-- Hero Header -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-body-tertiary">
            <div class="card-body p-4 p-md-5 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <div>
                    <h1 class="display-5 fw-black text-body mb-2 font-outfit">
                        Manajemen <span class="text-success">Pengguna</span>
                    </h1>
                    <p class="text-secondary mb-0 max-w-xl">
                        Kelola akun dan hak akses pengguna sistem logistik.
                    </p>
                </div>
                <div>
                    <button type="button" wire:click="create" class="btn btn-success font-semibold">+ Tambah User</button>
                </div>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Search Controls -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 p-3 bg-body-tertiary">
            <div class="d-flex flex-column flex-md-row gap-3 align-items-md-center">
                <div class="w-100 max-w-sm">
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari user..." class="form-control" />
                </div>
                @if ($search)
                    <button 
                        type="button" 
                        wire:click="resetFilters" 
                        class="btn btn-outline-danger px-3 d-inline-flex align-items-center justify-content-center font-semibold shadow-xs" 
                        style="height: 38px;"
                        title="Reset Filter"
                        aria-label="Reset Filter"
                    >
                        <svg width="15" height="15" fill="currentColor" aria-hidden="true"><use href="#i-x"/></svg>
                    </button>
                @endif
            </div>
        </div>

        <!-- Users Table -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="table-responsive management-table-scroll" tabindex="0" role="region" aria-label="Daftar pengguna">
                <table class="table table-hover align-middle mb-0 management-card-table">
                    <thead class="table-light text-uppercase small font-geist">
                        <tr>
                            <x-sortable-th field="name" :sort="$sort">Nama</x-sortable-th>
                            <x-sortable-th field="email" :sort="$sort">Email</x-sortable-th>
                            <x-sortable-th field="google" :sort="$sort">Google</x-sortable-th>
                            <x-sortable-th field="role" :sort="$sort">Role</x-sortable-th>
                            <x-sortable-th field="cluster" :sort="$sort">Cluster</x-sortable-th>
                            <x-sortable-th field="created_at" :sort="$sort">Dibuat</x-sortable-th>
                            <th class="text-end" style="width: 100px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                        @php
                            $roleClasses = ['admin' => 'bg-danger-subtle text-danger', 'logistik' => 'bg-primary-subtle text-primary', 'keuangan' => 'bg-success-subtle text-success', 'pengawas' => 'bg-warning-subtle text-warning-emphasis'];
                        @endphp
                        <tr wire:key="usr-{{ $user->id }}" style="cursor: pointer;" x-on:click="if (!$event.target.closest('button') && !$event.target.closest('a') && !$event.target.closest('input')) { $wire.edit({{ $user->id }}) }">
                            <td data-label="Nama" class="fw-bold text-body"><span class="management-cell-value">{{ $user->name }}</span></td>
                            <td data-label="Email" class="text-secondary small"><span class="management-cell-value">{{ $user->email }}</span></td>
                            <td data-label="Google"><span class="management-cell-value">
                                <span class="badge {{ $user->google_id ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                    {{ $user->google_id ? 'Terhubung' : 'Belum' }}
                                </span>
                            </span></td>
                            <td data-label="Role"><span class="management-cell-value">
                                <span class="badge {{ $roleClasses[$user->role] ?? 'bg-secondary-subtle text-secondary' }}">{{ $user->role === 'inactive' ? 'Nonaktif' : ucfirst($user->role) }}</span>
                            </span></td>
                            <td data-label="Cluster" class="text-secondary small"><span class="management-cell-value">{{ $user->cluster?->name ?? 'Belum ditetapkan' }}</span></td>
                            <td data-label="Dibuat" class="text-secondary small"><span class="management-cell-value">{{ $user->created_at->format('d/m/Y') }}</span></td>
                            <td data-label="Aksi" class="text-end"><span class="management-cell-value"><span class="btn-group btn-group-sm data-row-actions">
                                    <button type="button" wire:click="edit({{ $user->id }})" class="btn log-row-action log-row-action--edit d-inline-flex align-items-center justify-content-center" title="Edit" aria-label="Edit {{ $user->name }}">
                                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325"/></svg>
                                    </button>
                                    @if ($user->id !== auth()->id())
                                        <button type="button" wire:click="delete({{ $user->id }})" wire:confirm="Yakin ingin menghapus user ini?" class="btn log-row-action log-row-action--danger d-inline-flex align-items-center justify-content-center" title="Hapus" aria-label="Hapus {{ $user->name }}">
                                            <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/><path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/></svg>
                                        </button>
                                    @endif
                                </span></span></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="management-table-empty text-center py-4 text-secondary">Belum ada data user.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">{{ $users->links('vendor.livewire.bootstrap') }}</div>
    </div>

    <!-- Modal: Create / Edit User -->
    @if($showModal)
    @teleport('body')
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title font-outfit fw-bold">{{ $editMode ? 'Edit User' : 'Tambah User' }}</h5>
                    <button type="button" class="btn-close" wire:click="$set('showModal', false)"></button>
                </div>
                <div class="modal-body py-4">
                    @if ($reactivatingLegacyUser)
                        <div class="alert alert-warning" role="status">Akun lama nonaktif. Simpan akan mengaktifkannya sebagai Logistik dan memberi akses sesuai cluster.</div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label font-semibold">Nama</label>
                        <input type="text" wire:model="name" class="form-control" placeholder="Nama lengkap" />
                        @error('name') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-semibold">Email</label>
                        <input type="email" wire:model="email" class="form-control" placeholder="email@example.com" />
                        @error('email') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-semibold">{{ $editMode ? 'Password (kosongkan jika tidak diubah)' : 'Password' }}</label>
                        <input type="password" wire:model="password" class="form-control" />
                        @error('password') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-semibold">Role</label>
                        <select wire:model="role" class="form-select">
                            <option value="admin">Admin</option>
                            <option value="logistik">Logistik</option>
                            <option value="keuangan">Keuangan</option>
                            <option value="pengawas">Pengawas</option>
                        </select>
                        @error('role') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    @if (in_array($role, ['logistik', 'pengawas'], true))
                        <div class="mb-3">
                            <label class="form-label font-semibold">{{ $role === 'pengawas' ? 'Cluster tanggung jawab' : 'Cluster tugas' }}</label>
                            <select wire:model="cluster_id" class="form-select">
                                <option value="">{{ $role === 'pengawas' ? 'Pilih cluster tanggung jawab' : 'Pilih cluster tugas' }}</option>
                                @foreach ($clusters as $cluster)
                                    <option value="{{ $cluster->id }}">{{ $cluster->name }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">{{ $role === 'pengawas' ? 'Akses Pengawas dibatasi ke cluster ini.' : 'Operasi Logistik dibatasi ke rumah di cluster ini.' }}</div>
                            @error('cluster_id') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    @endif
                </div>
                <div class="modal-footer border-top bg-body-tertiary rounded-bottom-4">
                    <button type="button" class="btn btn-secondary fw-semibold" wire:click="$set('showModal', false)">Batal</button>
                    <button type="button" class="btn btn-success fw-semibold" wire:click="save" wire:loading.attr="disabled" wire:target="save"><span wire:loading.remove wire:target="save">{{ $editMode ? 'Perbarui' : 'Simpan' }}</span><span wire:loading wire:target="save">Menyimpan…</span></button>
                </div>
            </div>
        </div>
    </div>
    @endteleport
    @endif
</div>

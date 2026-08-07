<div>
    <div class="container-fluid p-0">
        <!-- Hero Header -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-body-tertiary">
            <div class="card-body p-4 p-md-5 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 text-uppercase mb-2 font-geist small">User Administration</span>
                    <h1 class="display-5 fw-black text-body mb-2 font-outfit">
                        Manajemen <span class="text-success">User</span>
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
                    <button type="button" wire:click="resetFilters" class="btn btn-link text-secondary text-decoration-none btn-sm">✕ Reset Filter</button>
                @endif
            </div>
        </div>

        <!-- Users Table -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-uppercase small font-geist">
                        <tr>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Dibuat</th>
                            <th class="text-end" style="width: 100px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                        @php
                            $roleClasses = ['admin' => 'bg-danger-subtle text-danger', 'logistik' => 'bg-primary-subtle text-primary'];
                        @endphp
                        <tr wire:key="usr-{{ $user->id }}" style="cursor: pointer;" x-on:click="if (!$event.target.closest('button') && !$event.target.closest('a') && !$event.target.closest('input')) { $wire.edit({{ $user->id }}) }">
                            <td class="fw-bold text-body">{{ $user->name }}</td>
                            <td class="text-secondary small">{{ $user->email }}</td>
                            <td>
                                <span class="badge {{ $roleClasses[$user->role] ?? 'bg-secondary-subtle text-secondary' }}">{{ ucfirst($user->role) }}</span>
                            </td>
                            <td class="text-secondary small">{{ $user->created_at->format('d/m/Y') }}</td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" wire:click="edit({{ $user->id }})" class="btn btn-outline-secondary" title="Edit">✏️</button>
                                    @if ($user->id !== auth()->id())
                                        <button type="button" wire:click="delete({{ $user->id }})" wire:confirm="Yakin ingin menghapus user ini?" class="btn btn-outline-danger" title="Hapus">🗑️</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-secondary">Belum ada data user.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="d-flex justify-content-end">{{ $users->links() }}</div>
    </div>

    <!-- Modal: Create / Edit User -->
    @if($showModal)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title font-outfit fw-bold">{{ $editMode ? 'Edit User' : 'Tambah User' }}</h5>
                    <button type="button" class="btn-close" wire:click="$set('showModal', false)"></button>
                </div>
                <div class="modal-body py-4">
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
                        </select>
                        @error('role') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-secondary font-semibold" wire:click="$set('showModal', false)">Batal</button>
                    <button type="button" class="btn btn-success font-semibold" wire:click="save">{{ $editMode ? 'Perbarui' : 'Simpan' }}</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

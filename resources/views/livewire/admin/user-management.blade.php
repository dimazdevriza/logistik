<div>
    <div class="flex h-full w-full flex-1 flex-col gap-6 p-4">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <flux:heading size="xl" class="font-bold">Manajemen User</flux:heading>
                <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">Kelola akun pengguna sistem logistik.</flux:text>
            </div>
            <flux:button wire:click="create" variant="primary" icon="plus">Tambah User</flux:button>
        </div>

        @if (session('success'))
            <flux:callout variant="success" icon="check-circle" dismissible>{{ session('success') }}</flux:callout>
        @endif
        @if (session('error'))
            <flux:callout variant="danger" icon="x-circle" dismissible>{{ session('error') }}</flux:callout>
        @endif

        <div class="flex gap-3">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari user..." icon="magnifying-glass" class="max-w-sm" />
            
            @if ($search)
                <flux:button wire:click="resetFilters" variant="ghost" size="sm" icon="x-mark" class="self-center">Reset Filter</flux:button>
            @endif
        </div>

        <div class="overflow-x-auto rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-zinc-800 shadow-sm">
            <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                <thead class="bg-zinc-50 dark:bg-zinc-900">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-200">Nama</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-200">Email</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-200">Role</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-200">Dibuat</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-200">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                    @forelse ($users as $user)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition cursor-pointer"
                        x-on:click="if (!$event.target.closest('button') && !$event.target.closest('a') && !$event.target.closest('input')) { $wire.edit({{ $user->id }}) }">
                        <td class="px-4 py-3 text-sm font-medium dark:text-zinc-100">{{ $user->name }}</td>
                        <td class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">{{ $user->email }}</td>
                        <td class="px-4 py-3 text-sm">
                            @php
                                $roleColors = ['admin' => 'danger', 'logistik' => 'primary'];
                            @endphp
                            <flux:badge :variant="$roleColors[$user->role] ?? 'default'" size="sm">
                                {{ ucfirst($user->role) }}
                            </flux:badge>
                        </td>
                        <td class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">{{ $user->created_at->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-2">
                                <flux:button wire:click="edit({{ $user->id }})" size="sm" variant="ghost" icon="pencil-square" />
                                @if ($user->id !== auth()->id())
                                    <flux:button wire:click="delete({{ $user->id }})" wire:confirm="Yakin ingin menghapus user ini?" size="sm" variant="ghost" icon="trash" class="text-red-500 hover:text-red-700" />
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-sm text-zinc-400 dark:text-zinc-500">Belum ada data user.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $users->links() }}</div>
    </div>

    {{-- Modal --}}
    <flux:modal wire:model="showModal" class="max-w-lg">
        <div class="space-y-6">
            <flux:heading size="lg">{{ $editMode ? 'Edit User' : 'Tambah User' }}</flux:heading>

            <flux:input wire:model="name" label="Nama" placeholder="Nama lengkap" :error="$errors->first('name')" />
            <flux:input wire:model="email" label="Email" type="email" placeholder="email@example.com" :error="$errors->first('email')" />
            <flux:input wire:model="password" label="{{ $editMode ? 'Password (kosongkan jika tidak diubah)' : 'Password' }}" type="password" :error="$errors->first('password')" />
            <flux:select wire:model="role" label="Role" :error="$errors->first('role')">
                <option value="admin">Admin</option>
                <option value="logistik">Logistik</option>

            </flux:select>

            <div class="flex justify-end gap-3">
                <flux:button wire:click="$set('showModal', false)" variant="ghost">Batal</flux:button>
                <flux:button wire:click="save" variant="primary">{{ $editMode ? 'Perbarui' : 'Simpan' }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>

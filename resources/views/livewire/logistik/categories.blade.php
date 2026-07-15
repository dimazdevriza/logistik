<div>
    <div class="flex h-full w-full flex-1 flex-col gap-6 p-4">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <flux:heading size="xl" class="font-bold">Kategori</flux:heading>
                <flux:text class="mt-1 text-zinc-700 dark:text-zinc-300">Kelola kategori untuk material dan alat.</flux:text>
            </div>
            <flux:button wire:click="create" variant="primary" icon="plus">Tambah Kategori</flux:button>
        </div>

        @if (session('success'))
            <flux:callout variant="success" icon="check-circle" dismissible>{{ session('success') }}</flux:callout>
        @endif

        <div class="flex gap-3">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari kategori..." icon="magnifying-glass" class="max-w-sm" />
            
            <flux:select wire:model.live="filterType" class="max-w-[180px]">
                <option value="">Semua Tipe</option>
                <option value="material">Material</option>
                <option value="tool">Alat (Tool)</option>
            </flux:select>
            
            @if ($search || $filterType)
                <flux:button wire:click="resetFilters" variant="ghost" size="sm" icon="x-mark" class="self-center">Reset Filter</flux:button>
            @endif
        </div>

        <div class="overflow-x-auto rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-zinc-800 shadow-sm">
            <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                <thead class="bg-zinc-50 dark:bg-zinc-900">
                    <tr>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-200 w-16">No.</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">Nama</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">Tipe</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                    @forelse ($categories as $category)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition cursor-pointer"
                        x-on:click="if (!$event.target.closest('button') && !$event.target.closest('a') && !$event.target.closest('input')) { $wire.edit({{ $category->id }}) }">
                        <td class="px-4 py-3 text-sm text-center text-zinc-700 dark:text-zinc-500">{{ ($categories->currentPage() - 1) * $categories->perPage() + $loop->iteration }}</td>
                        <td class="px-4 py-3 text-sm font-medium dark:text-zinc-100">{{ $category->name }}</td>
                        <td class="px-4 py-3 text-sm">
                            <flux:badge :variant="$category->type === 'material' ? 'primary' : 'warning'" size="sm">
                                {{ $category->type === 'material' ? 'Material' : 'Alat' }}
                            </flux:badge>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-2">
                                <flux:button wire:click="edit({{ $category->id }})" size="sm" variant="ghost" icon="pencil-square" title="Edit Data" />
                                <flux:button wire:click="confirm('delete', {{ $category->id }}, 'Hapus Kategori?', 'Yakin ingin menghapus kategori ini? Seluruh data material dan alat di dalamnya akan tetap ada namun referensi kategori akan hilang.')" size="sm" variant="ghost" icon="trash" class="text-red-500 hover:text-red-700" title="Hapus Data" />
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-sm text-zinc-600 dark:text-zinc-500">Belum ada data kategori.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $categories->links() }}</div>
    </div>

    {{-- Modal --}}
    <flux:modal wire:model="showModal" class="max-w-lg">
        <div class="space-y-6">
            <flux:heading size="lg">{{ $editMode ? 'Edit Kategori' : 'Tambah Kategori' }}</flux:heading>

            <flux:input wire:model="name" label="Nama Kategori" placeholder="Contoh: Semen & Beton" :error="$errors->first('name')" />
            <flux:select wire:model="type" label="Tipe" :error="$errors->first('type')">
                <option value="material">Material</option>
                <option value="tool">Alat</option>
            </flux:select>

            <div class="flex justify-end gap-3">
                <flux:button wire:click="$set('showModal', false)" variant="ghost">Batal</flux:button>
                <flux:button wire:click="save" variant="primary">{{ $editMode ? 'Perbarui' : 'Simpan' }}</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Confirmation Modal --}}
    <flux:modal wire:model="showConfirmation" class="max-w-sm">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $confirmTitle ?? 'Konfirmasi' }}</flux:heading>
                <flux:text>{{ $confirmMessage ?? 'Apakah Anda yakin ingin melakukan tindakan ini?' }}</flux:text>
            </div>
            <div class="flex gap-2 justify-end">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button wire:click="executeConfirmedAction" variant="danger">Ya, Hapus</flux:button>
            </div>
        </div>
    </flux:modal>
</div>

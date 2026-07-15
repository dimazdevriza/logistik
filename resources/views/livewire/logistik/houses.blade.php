<div>
    <div class="flex h-full w-full flex-1 flex-col gap-6 p-4">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <flux:heading size="xl" class="font-bold">Rumah</flux:heading>
                <flux:text class="mt-1 text-zinc-700 dark:text-zinc-300">Kelola data rumah yang sedang dibangun.</flux:text>
            </div>
            <div class="flex gap-2">
                @if(in_array(auth()->user()->role, ['admin', 'logistik']))
                    <flux:button wire:click="exportExcel" variant="filled" class="bg-emerald-600 hover:bg-emerald-700 text-white" icon="document-chart-bar">Export Excel</flux:button>
                @endif
                <flux:button wire:click="create" variant="primary" icon="plus">Tambah Rumah</flux:button>
            </div>
        </div>

        @if (session('success'))
            <flux:callout variant="success" icon="check-circle" dismissible>{{ session('success') }}</flux:callout>
        @endif

        <div class="flex flex-col md:flex-row md:items-center gap-3">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari rumah, kode, atau tipe..." icon="magnifying-glass" class="w-full md:max-w-[250px]" />
            
            <div class="flex items-center gap-2 w-full md:w-auto">
                <x-filter-modal :activeFiltersCount="$this->getActiveFiltersCount()">
                    {{-- Status Filter --}}
                    <div>
                        <flux:label>Status Rumah</flux:label>
                        <flux:select wire:model.live="filterStatus" class="mt-2">
                            <option value="">Semua Status</option>
                            <option value="perencanaan">Perencanaan</option>
                            <option value="pembangunan">Pembangunan</option>
                            <option value="selesai">Selesai</option>
                        </flux:select>
                    </div>
                </x-filter-modal>
            </div>
        </div>

        <div class="overflow-x-auto rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-zinc-800 shadow-sm">
            <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                <thead class="bg-zinc-50 dark:bg-zinc-900">
                    <tr>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-200 w-16">No.</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">Kode</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">Nama</th>

                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">Tipe</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">Total Biaya</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">Mulai</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">Target Selesai</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                    @forelse ($houses as $house)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition cursor-pointer" 
                        x-on:click="if (!$event.target.closest('button') && !$event.target.closest('a')) { window.Livewire.navigate('{{ route('logistik.house-detail', $house) }}') }">
                        <td class="px-4 py-3 text-sm text-center text-zinc-700 dark:text-zinc-500">{{ $houses->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-3 text-sm font-mono text-zinc-700 dark:text-zinc-400">{{ $house->house_code ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm font-medium dark:text-zinc-100">{{ $house->name }}</td>

                        <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-400">{{ $house->type }}</td>
                        <td class="px-4 py-3 text-sm">
                            @php
                                $statusColors = ['perencanaan' => 'warning', 'pembangunan' => 'primary', 'selesai' => 'success'];
                            @endphp
                            <flux:badge :variant="$statusColors[$house->status] ?? 'default'" size="sm">
                                {{ ucfirst($house->status) }}
                            </flux:badge>
                        </td>
                        <td class="px-4 py-3 text-sm text-right font-mono dark:text-zinc-200">Rp {{ number_format($house->total_material_cost, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-400">{{ $house->start_date?->format('d/m/Y') ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-400">{{ $house->target_end_date?->format('d/m/Y') ?? '-' }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-2">
                                <flux:button href="{{ route('logistik.house-detail', $house) }}" wire:navigate size="sm" variant="ghost" icon="eye" class="text-zinc-700 hover:text-zinc-900" title="Lihat Detail" />
                                <flux:button wire:click="edit({{ $house->id }})" size="sm" variant="ghost" icon="pencil-square" title="Edit Data" />
                                <flux:button wire:click="confirm('delete', {{ $house->id }}, 'Hapus Rumah?', 'Yakin ingin menghapus data rumah ini? Semua data penggunaan material dan peminjaman alat terkait akan ikut dihapus secara permanen.')" size="sm" variant="ghost" icon="trash" class="text-red-500 hover:text-red-700" title="Hapus Data" />
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-4 py-8 text-center text-sm text-zinc-600 dark:text-zinc-500">Belum ada data rumah.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $houses->links() }}</div>
    </div>

    {{-- Modal --}}
    <flux:modal wire:model="showModal" class="max-w-lg">
        <div class="space-y-6">
            <flux:heading size="lg">{{ $editMode ? 'Edit Rumah' : 'Tambah Rumah' }}</flux:heading>



            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="name" label="Nama / Blok" placeholder="Blok A-01" :error="$errors->first('name')" />
                <flux:input wire:model="type" label="Tipe Rumah" placeholder="Tipe 36/72" :error="$errors->first('type')" />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <flux:select wire:model="status" label="Status" :error="$errors->first('status')">
                    <option value="perencanaan">Perencanaan</option>
                    <option value="pembangunan">Pembangunan</option>
                    <option value="selesai">Selesai</option>
                </flux:select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="start_date" label="Tanggal Mulai" type="date" :error="$errors->first('start_date')" />
                <flux:input wire:model="target_end_date" label="Target Selesai" type="date" :error="$errors->first('target_end_date')" />
            </div>

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

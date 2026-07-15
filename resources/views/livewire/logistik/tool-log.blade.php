<div>
    <div class="flex h-full w-full flex-1 flex-col gap-6 p-4">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <flux:heading size="xl" class="font-bold">Catatan Alat</flux:heading>
                <flux:text class="mt-1 text-zinc-700 dark:text-zinc-300">Riwayat peminjaman dan pengembalian alat proyek.</flux:text>
            </div>
            @if(in_array(auth()->user()->role, ['admin', 'logistik']))
                <flux:button wire:click="exportExcel" variant="filled" class="bg-emerald-600 hover:bg-emerald-700 text-white" icon="document-chart-bar">Export Excel</flux:button>
            @endif
        </div>

        <div class="flex flex-col md:flex-row md:items-center gap-3">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari alat..." icon="magnifying-glass" class="w-full md:max-w-[200px]" />

            <div class="flex items-center gap-2 w-full md:w-auto">
                <flux:button 
                    wire:click="toggleSortDirection" 
                    variant="ghost" 
                    :icon="$sortDirection === 'asc' ? 'bars-arrow-up' : 'bars-arrow-down'"
                    title="Urutkan Tanggal"
                />

                <x-filter-modal :activeFiltersCount="$this->getActiveFiltersCount()">
                    {{-- Status Filter --}}
                    <div>
                        <flux:label>Status</flux:label>
                        <flux:select wire:model.live="filterStatus" class="mt-2">
                            <option value="">Semua</option>
                            <option value="dipinjam">Dipinjam</option>
                            <option value="dikembalikan">Dikembalikan</option>
                        </flux:select>
                    </div>

                    {{-- House Filter --}}
                    <div>
                        <flux:label>Rumah</flux:label>
                        <flux:select wire:model.live="filterHouse" class="mt-2">
                            <option value="">Semua Rumah</option>
                            @foreach ($houses as $house)
                                <option value="{{ $house->id }}">{{ $house->name }}</option>
                            @endforeach
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
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">Tgl Pinjam</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">Rumah</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">Alat</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">Qty</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">Tgl Kembali</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">Dicatat oleh</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                    @forelse ($usages as $usage)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition">
                        <td class="px-4 py-3 text-sm text-center text-zinc-700 dark:text-zinc-500">{{ ($usages->currentPage() - 1) * $usages->perPage() + $loop->iteration }}</td>
                        <td class="px-4 py-3 text-sm text-zinc-800 dark:text-zinc-400">{{ $usage->checkout_date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-zinc-800 dark:text-zinc-300">{{ $usage->house->name }}</td>
                        <td class="px-4 py-3 text-sm font-medium dark:text-zinc-100">{{ $usage->tool->name }} ({{ $usage->tool->code }})</td>
                        <td class="px-4 py-3 text-sm text-center font-bold text-zinc-900 dark:text-white">{{ $usage->quantity }}</td>
                        <td class="px-4 py-3 text-sm text-zinc-800 dark:text-zinc-400">{{ $usage->return_date?->format('d/m/Y') ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm">
                            @if ($usage->return_date)
                                <span class="text-xs font-bold text-emerald-500">✓ Dikembalikan</span>
                            @else
                                <span class="text-xs font-bold text-amber-500">⏳ Dipinjam</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-400 font-medium">{{ $usage->user->name }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-sm text-zinc-600 dark:text-zinc-500">Belum ada data penggunaan alat.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $usages->links() }}</div>
    </div>
</div>

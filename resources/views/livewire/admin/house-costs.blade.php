<div>
    <div class="flex h-full w-full flex-1 flex-col gap-6 p-4">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <flux:heading size="xl" class="font-bold">Biaya Rumah</flux:heading>
                <flux:text class="mt-1 text-zinc-700 dark:text-zinc-300">Monitor biaya pembangunan setiap unit rumah.</flux:text>
            </div>
            <div class="flex gap-2">
                <flux:button wire:click="exportExcel" variant="filled" class="bg-emerald-600 hover:bg-emerald-700 text-white" icon="document-chart-bar">Export Excel</flux:button>
            </div>
        </div>

        {{-- Summary cards --}}
        <div class="grid gap-4 md:grid-cols-1 max-w-md">
            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-zinc-800 p-5 flex flex-col gap-2 shadow-sm">
                <flux:text class="text-xs uppercase tracking-widest text-zinc-400">Total Biaya Material (Semua Rumah)</flux:text>
                <flux:heading size="xl" class="font-bold text-2xl text-orange-500">Rp {{ number_format($totalSpent, 0, ',', '.') }}</flux:heading>
            </div>
        </div>

        <div class="flex gap-3">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari rumah, kode, atau tipe..." icon="magnifying-glass" class="max-w-sm" />
            

            
            <flux:select wire:model.live="filterStatus" class="max-w-[180px]">
                <option value="">Semua Status</option>
                <option value="perencanaan">Perencanaan</option>
                <option value="pembangunan">Pembangunan</option>
                <option value="selesai">Selesai</option>
            </flux:select>

            @if ($search || $filterStatus)
                <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="resetFilters" class="self-center">
                    Reset Filter
                </flux:button>
            @endif
        </div>

        <div class="overflow-x-auto rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-zinc-800 shadow-sm">
            <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                <thead class="bg-zinc-50 dark:bg-zinc-900">
                    <tr>
                        <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wider text-zinc-800 dark:text-zinc-200 w-16">No.</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">Kode</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">Rumah</th>

                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">Tipe</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">Biaya Material</th>
                        <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">Transaksi</th>
                        <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                    @forelse ($houses as $house)
                    @php
                        $spent = $house->material_usages_sum_total_cost ?? 0;
                    @endphp
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition cursor-pointer"
                        x-on:click="if (!$event.target.closest('button') && !$event.target.closest('a')) { window.Livewire.navigate('{{ route('admin.house-costs.detail', $house) }}') }">
                        <td class="px-4 py-3 text-sm text-center text-zinc-500 dark:text-zinc-500">{{ $houses->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-3 text-sm font-mono text-zinc-500 dark:text-zinc-400">{{ $house->house_code ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm font-medium dark:text-zinc-100">{{ $house->name }}</td>

                        <td class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">{{ $house->type }}</td>
                        <td class="px-4 py-3 text-sm">
                            @php
                                $statusColors = ['perencanaan' => 'warning', 'pembangunan' => 'primary', 'selesai' => 'success'];
                            @endphp
                            <flux:badge :variant="$statusColors[$house->status] ?? 'default'" size="sm">{{ ucfirst($house->status) }}</flux:badge>
                        </td>
                        <td class="px-4 py-3 text-sm text-right font-mono font-semibold text-orange-600 dark:text-orange-400">Rp {{ number_format($spent, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-sm text-center dark:text-zinc-300">{{ $house->material_usages_count ?? 0 }}</td>
                        <td class="px-4 py-3 text-right">
                            <flux:button href="{{ route('admin.house-costs.detail', $house) }}" wire:navigate size="sm" variant="ghost" icon="eye" title="Lihat Detail">Lihat</flux:button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-600">Belum ada data rumah.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $houses->links() }}</div>
    </div>
</div>

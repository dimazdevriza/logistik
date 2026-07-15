<div>
    <div class="flex h-full w-full flex-1 flex-col gap-6 p-4">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <flux:heading size="xl" class="font-bold">Catatan Material</flux:heading>
                <flux:text class="mt-1 text-zinc-700 dark:text-zinc-300">Riwayat barang masuk dan barang keluar material.</flux:text>
            </div>
            @if(in_array(auth()->user()->role, ['admin', 'logistik']))
                <flux:button wire:click="exportExcel" variant="filled" class="bg-emerald-600 hover:bg-emerald-700 text-white" icon="document-chart-bar">Export Excel</flux:button>
            @endif
        </div>

        <div class="flex flex-col md:flex-row md:items-center gap-3">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari material..." icon="magnifying-glass" class="w-full md:max-w-[200px]" />

            <div class="flex items-center gap-2 w-full md:w-auto">
                <flux:button 
                    wire:click="toggleSortDirection" 
                    variant="ghost" 
                    :icon="$sortDirection === 'asc' ? 'bars-arrow-up' : 'bars-arrow-down'"
                    title="Urutkan Tanggal"
                />

                <x-filter-modal :activeFiltersCount="$this->getActiveFiltersCount()">
                    {{-- Type Filter --}}
                    <div>
                        <flux:label>Tipe Transaksi</flux:label>
                        <flux:select wire:model.live="filterType" class="mt-2">
                            <option value="">Semua</option>
                            <option value="masuk">Barang Masuk</option>
                            <option value="keluar">Barang Keluar</option>
                        </flux:select>
                    </div>

                    @if ($filterType === 'keluar')
                        {{-- House Filter (for Barang Keluar) --}}
                        <div>
                            <flux:label>Rumah</flux:label>
                            <flux:select wire:model.live="filterHouse" class="mt-2">
                                <option value="">Semua Rumah</option>
                                @foreach ($houses as $house)
                                    <option value="{{ $house->id }}">{{ $house->name }}</option>
                                @endforeach
                            </flux:select>
                        </div>
                    @elseif ($filterType === 'masuk')
                        {{-- Supplier Filter (for Barang Masuk) --}}
                        <div>
                            <flux:label>Supplier</flux:label>
                            <flux:select wire:model.live="filterSupplier" class="mt-2">
                                <option value="">Semua Supplier</option>
                                @foreach ($suppliers as $sup)
                                    <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                                @endforeach
                            </flux:select>
                        </div>
                    @endif
                </x-filter-modal>
            </div>
        </div>

        <div class="overflow-x-auto rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-zinc-800 shadow-sm">
            <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                <thead class="bg-zinc-50 dark:bg-zinc-900">
                    <tr>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-200 w-16">No.</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">Tanggal</th>
                        @if ($filterType === '')
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">Tipe</th>
                        @endif
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">
                            @if ($filterType === 'masuk') Supplier
                            @elseif ($filterType === 'keluar') Rumah
                            @else Referensi
                            @endif
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">Material</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">Qty</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">Harga Satuan</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">Total Biaya</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">Dicatat oleh</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                    {{-- Combined "Semua" view --}}
                    @if ($filterType === '')
                        @forelse ($records as $record)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition">
                            <td class="px-4 py-3 text-sm text-center text-zinc-700 dark:text-zinc-500">{{ ($records->currentPage() - 1) * $records->perPage() + $loop->iteration }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-400">{{ \Carbon\Carbon::parse($record->date)->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-sm">
                                @if ($record->type === 'masuk')
                                    <span class="text-xs font-bold text-emerald-500">▼ Masuk</span>
                                @else
                                    <span class="text-xs font-bold text-orange-500">▲ Keluar</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $record->reference }}</td>
                            <td class="px-4 py-3 text-sm font-medium dark:text-zinc-100">{{ $record->material_name }}</td>
                            <td class="px-4 py-3 text-sm text-right text-zinc-800 dark:text-zinc-100 font-bold">{{ number_format($record->quantity, 0, ',', '.') }} {{ $record->material_unit }}</td>
                            <td class="px-4 py-3 text-sm text-right font-mono text-zinc-700 dark:text-zinc-400">Rp {{ number_format($record->unit_price, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-sm text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">Rp {{ number_format($record->total_cost, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-400">{{ $record->user_name }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-sm text-zinc-600 dark:text-zinc-500">Belum ada catatan material.</td>
                        </tr>
                        @endforelse

                    {{-- Barang Masuk view --}}
                    @elseif ($filterType === 'masuk')
                        @forelse ($records as $record)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition">
                            <td class="px-4 py-3 text-sm text-center text-zinc-700 dark:text-zinc-500">{{ ($records->currentPage() - 1) * $records->perPage() + $loop->iteration }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-400">{{ $record->date->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $record->supplier->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm font-medium dark:text-zinc-100">{{ $record->material->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-right text-zinc-800 dark:text-zinc-100 font-bold">{{ number_format($record->quantity, 0, ',', '.') }} {{ $record->material->unit ?? '' }}</td>
                            <td class="px-4 py-3 text-sm text-right font-mono text-zinc-700 dark:text-zinc-400">Rp {{ number_format($record->unit_price, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-sm text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">Rp {{ number_format($record->total_cost, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-400">{{ $record->user->name ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-sm text-zinc-600 dark:text-zinc-500">Belum ada data barang masuk.</td>
                        </tr>
                        @endforelse

                    {{-- Barang Keluar view --}}
                    @else
                        @forelse ($records as $record)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition">
                            <td class="px-4 py-3 text-sm text-center text-zinc-700 dark:text-zinc-500">{{ ($records->currentPage() - 1) * $records->perPage() + $loop->iteration }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-400">{{ $record->usage_date->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $record->house->name }}</td>
                            <td class="px-4 py-3 text-sm font-medium dark:text-zinc-100">{{ $record->material->name }}</td>
                            <td class="px-4 py-3 text-sm text-right text-zinc-800 dark:text-zinc-100 font-bold">{{ str_replace('.', ',', (float) $record->quantity) }} {{ $record->material->unit }}</td>
                            <td class="px-4 py-3 text-sm text-right font-mono text-zinc-700 dark:text-zinc-400">Rp {{ number_format($record->unit_price_at_usage, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-sm text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">Rp {{ number_format($record->total_cost, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-400">{{ $record->user->name }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-sm text-zinc-600 dark:text-zinc-500">Belum ada data barang keluar.</td>
                        </tr>
                        @endforelse
                    @endif
                </tbody>
            </table>
        </div>

        <div>{{ $records->links() }}</div>
    </div>
</div>

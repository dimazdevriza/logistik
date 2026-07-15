<div>
    <div class="flex h-full w-full flex-1 flex-col gap-6 p-4">
        {{-- Header & Navigation --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-3 mb-2">
            <flux:button href="{{ route('logistik.houses') }}" wire:navigate variant="ghost" size="sm" icon="arrow-left" class="text-zinc-500 hover:text-zinc-700" />
                    <flux:badge :variant="['perencanaan' => 'warning', 'pembangunan' => 'primary', 'selesai' => 'success'][$house->status] ?? 'default'">
                        {{ ucfirst($house->status) }}
                    </flux:badge>
                </div>
                <div class="flex items-center gap-3">
                    <flux:heading size="xl" class="font-bold">{{ $house->name }}</flux:heading>
                    @if($house->house_code)
                    <span class="font-mono text-sm px-2 py-1 bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 rounded-md border border-zinc-200 dark:border-zinc-700">
                        {{ $house->house_code }}
                    </span>
                    @endif
                </div>
                <flux:text class="text-zinc-700 dark:text-zinc-300 font-medium">{{ $house->type }}</flux:text>
            </div>
            
            <div class="flex gap-3 flex-wrap">
                @if(in_array(auth()->user()->role, ['admin', 'logistik']))
                    <flux:button wire:click="exportExcel" variant="filled" class="bg-emerald-600 hover:bg-emerald-700 text-white" icon="document-chart-bar">Export Excel</flux:button>
                @endif
                @if($house->status !== 'selesai')
                    <flux:button href="{{ route('logistik.house-finish', $house) }}" wire:navigate variant="filled" class="bg-orange-600 hover:bg-orange-700 text-white" icon="flag">Selesaikan Rumah</flux:button>
                @else
                    <flux:badge variant="success" size="lg" icon="check-circle">Proyek Selesai — Data Terkunci</flux:badge>
                @endif
            </div>
        </div>

        {{-- Summary Cards --}}
        <div class="grid gap-4 md:grid-cols-2">
            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-zinc-800 p-5 flex flex-col gap-2 shadow-sm">
                <flux:text class="text-xs uppercase tracking-widest text-zinc-600 dark:text-zinc-400 font-bold">Total Biaya Material</flux:text>
                <flux:heading size="xl" class="font-bold text-2xl text-orange-600 dark:text-orange-400">Rp {{ number_format($house->total_material_cost, 0, ',', '.') }}</flux:heading>
            </div>
            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-zinc-800 p-5 flex flex-col gap-2 shadow-sm">
                <flux:text class="text-xs uppercase tracking-widest text-zinc-400 dark:text-zinc-500 font-bold">Total Transaksi</flux:text>
                <flux:heading size="xl" class="font-bold text-2xl text-indigo-600 dark:text-indigo-400">{{ $materialCount + $toolCount }}</flux:heading>
                <flux:text class="text-xs text-zinc-500 dark:text-zinc-600">Aktivitas Material & Alat</flux:text>
            </div>
        </div>

        {{-- Tabs --}}
        <div class="mt-4 border-b border-neutral-200 dark:border-neutral-700">
            <nav class="flex gap-4">
                <button wire:click="$set('activeTab', 'material')" 
                    class="py-3 px-1 border-b-2 font-medium text-sm transition-colors duration-200 {{ $activeTab === 'material' ? 'border-primary text-primary' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}">
                    <div class="flex items-center gap-2">
                        <flux:icon.cube class="size-4" />
                        Penggunaan Material ({{ $materialCount }})
                    </div>
                </button>
                <button wire:click="$set('activeTab', 'tool')" 
                    class="py-3 px-1 border-b-2 font-medium text-sm transition-colors duration-200 {{ $activeTab === 'tool' ? 'border-primary text-primary' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}">
                    <div class="flex items-center gap-2">
                        <flux:icon.wrench class="size-4" />
                        Peminjaman Alat ({{ $toolCount }})
                    </div>
                </button>
            </nav>
        </div>

        {{-- Tab Content: Material (Read-Only) --}}
        @if($activeTab === 'material')
        <div>
            <div class="flex justify-between items-center mb-4">
                <flux:heading size="lg">Log Penggunaan Material</flux:heading>
            </div>

            <div class="overflow-x-auto rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-zinc-800 shadow-sm mb-4">
                <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-900">
                        <tr>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-100 w-16">No.</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-100">Tanggal</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-100">Material</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-100">Jumlah</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-100">Total Biaya</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-100">Pencatat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                        @forelse ($materialUsages as $usage)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition">
                            <td class="px-4 py-3 text-sm text-center text-zinc-700 dark:text-zinc-500 font-medium">{{ ($materialUsages->currentPage() - 1) * $materialUsages->perPage() + $loop->iteration }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-400">{{ $usage->usage_date->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 text-sm font-medium dark:text-zinc-100">
                                {{ $usage->material->name }}
                                @if($usage->notes)
                                    <div class="text-xs text-zinc-600 dark:text-zinc-500 font-medium mt-1">{{ Str::limit($usage->notes, 30) }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-right font-bold text-zinc-800 dark:text-zinc-100">{{ str_replace('.', ',', (float) $usage->quantity) }} {{ $usage->material->unit }}</td>
                            <td class="px-4 py-3 text-sm text-right font-mono font-bold text-orange-600 dark:text-orange-400">Rp {{ number_format($usage->total_cost, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-400 font-medium">{{ $usage->user->name }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-zinc-600 dark:text-zinc-500">Belum ada data penggunaan material untuk rumah ini.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>
                {{ $materialUsages->links() }}
            </div>
        </div>
        @endif

        {{-- Tab Content: Tool (Read-Only) --}}
        @if($activeTab === 'tool')
        <div>
            <div class="flex justify-between items-center mb-4">
                <flux:heading size="lg">Log Peminjaman Alat</flux:heading>
            </div>

            <div class="overflow-x-auto rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-zinc-800 shadow-sm mb-4">
                <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-900">
                        <tr>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-100 w-16">No.</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-100">Tanggal Pinjam</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-100">Alat</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-100">Jumlah</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-100">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-100">Pencatat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                        @forelse ($toolUsages as $usage)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition">
                            <td class="px-4 py-3 text-sm text-center text-zinc-700 dark:text-zinc-500 font-medium">{{ ($toolUsages->currentPage() - 1) * $toolUsages->perPage() + $loop->iteration }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-400">{{ $usage->checkout_date->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 text-sm font-medium dark:text-zinc-100">
                                {{ $usage->tool->name }}
                                <div class="text-xs text-zinc-600 dark:text-zinc-500 font-bold mt-1">Kode: {{ $usage->tool->code }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-right font-bold text-zinc-800 dark:text-zinc-100">{{ number_format($usage->quantity) }}</td>
                            <td class="px-4 py-3 text-sm text-center">
                                @if($usage->return_date)
                                    <flux:badge variant="success" size="sm">Dikembalikan ({{ $usage->return_date->format('d/m') }})</flux:badge>
                                @else
                                    <flux:badge variant="warning" size="sm">Dipinjam</flux:badge>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-400 font-medium">{{ $usage->user->name }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-zinc-600 dark:text-zinc-500">Belum ada data peminjaman alat untuk rumah ini.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>
                {{ $toolUsages->links() }}
            </div>
        </div>
        @endif
    </div>
</div>

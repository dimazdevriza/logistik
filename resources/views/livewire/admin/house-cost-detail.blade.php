<div>
    <div class="flex h-full w-full flex-1 flex-col gap-6 p-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <flux:button href="{{ route('admin.house-costs') }}" wire:navigate variant="ghost" icon="arrow-left" size="sm" />
                <div>
                    <flux:heading size="xl" class="font-bold">{{ $house->name }} — {{ $house->type }}</flux:heading>
                    <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">Detail biaya pembangunan rumah.</flux:text>
                </div>
            </div>

            @if(auth()->user()->role === 'admin')
                <flux:button wire:click="exportExcel" variant="filled" class="bg-emerald-600 hover:bg-emerald-700 text-white" icon="document-chart-bar">Export Excel</flux:button>
            @endif
        </div>

        {{-- Summary card --}}
        <div class="grid gap-4 md:grid-cols-1 max-w-md">
            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-zinc-800 p-5 flex flex-col gap-2 shadow-sm">
                <flux:text class="text-xs uppercase tracking-widest text-zinc-400">Total Biaya Material</flux:text>
                <flux:heading size="xl" class="font-bold text-2xl text-orange-500">Rp {{ number_format($totalCost, 0, ',', '.') }}</flux:heading>
            </div>
        </div>

        {{-- Cost by Category --}}
        @if ($costByCategory->count() > 0)
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-zinc-800 p-5 shadow-sm">
            <flux:heading size="lg" class="mb-4 font-semibold">Biaya per Kategori</flux:heading>
            <div class="space-y-3">
                @foreach ($costByCategory as $cat)
                <div class="flex items-center justify-between">
                    <flux:text class="text-sm dark:text-zinc-300">{{ $cat->category_name }}</flux:text>
                    <flux:text class="text-sm font-mono font-semibold dark:text-zinc-100">Rp {{ number_format($cat->total, 0, ',', '.') }}</flux:text>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Usage table --}}
        <div class="overflow-x-auto rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-zinc-800 shadow-sm">
            <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                <thead class="bg-zinc-50 dark:bg-zinc-900">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-200">Tanggal</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-200">Material</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-200">Qty</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-200">Harga Satuan</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-200">Total</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-200">Dicatat oleh</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-200">Catatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                    @forelse ($materialUsages as $usage)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition">
                        <td class="px-4 py-3 text-sm dark:text-zinc-400">{{ $usage->usage_date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-sm font-medium dark:text-zinc-100">{{ $usage->material->name }}</td>
                        <td class="px-4 py-3 text-sm text-right dark:text-zinc-100 font-semibold">{{ str_replace('.', ',', (float) $usage->quantity) }} {{ $usage->material->unit }}</td>
                        <td class="px-4 py-3 text-sm text-right font-mono dark:text-zinc-400">Rp {{ number_format($usage->unit_price_at_usage, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-sm text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">Rp {{ number_format($usage->total_cost, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">{{ $usage->user->name }}</td>
                        <td class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-500 max-w-xs truncate">{{ $usage->notes ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-sm text-zinc-400 dark:text-zinc-500">Belum ada penggunaan material untuk rumah ini.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $materialUsages->links() }}</div>
    </div>
</div>

<x-layouts::app.sidebar title="Dashboard Logistik">
    <flux:main>
        <div class="flex h-full w-full flex-1 flex-col gap-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <flux:heading size="xl" class="font-bold">Dashboard Logistik</flux:heading>
                    <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">Selamat datang kembali, monitor stok dan penggunaan material di sini.</flux:text>
                </div>
            </div>

            {{-- Stats Grid --}}
            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-zinc-800 p-5 flex flex-col gap-2 shadow-sm transition-all duration-300">
                    <flux:text class="text-xs uppercase tracking-widest text-zinc-400 dark:text-zinc-500 font-bold">Total Material</flux:text>
                    <flux:heading size="xl" class="font-bold text-2xl dark:text-white">{{ $total_materials }} Item</flux:heading>
                    <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">Tersedia di Stok</flux:text>
                </div>
                <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-zinc-800 p-5 flex flex-col gap-2 shadow-sm transition-all duration-300">
                    <flux:text class="text-xs uppercase tracking-widest text-zinc-400 dark:text-zinc-500 font-bold">Stok Menipis</flux:text>
                    <flux:heading size="xl" class="font-bold text-2xl text-red-600 dark:text-red-400">{{ $low_stock_count }} Item</flux:heading>
                    <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">Stok di bawah 10 unit</flux:text>
                </div>
                <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-zinc-800 p-5 flex flex-col gap-2 shadow-sm transition-all duration-300">
                    <flux:text class="text-xs uppercase tracking-widest text-zinc-400 dark:text-zinc-500 font-bold">Alat Sedang Dipinjam</flux:text>
                    <flux:heading size="xl" class="font-bold text-2xl text-orange-600 dark:text-orange-400">{{ $tools_on_loan }} Transaksi</flux:heading>
                    <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">Menunggu pengembalian</flux:text>
                </div>
            </div>

            {{-- Recent Activities --}}
            <div class="flex flex-col gap-4">
                <flux:heading size="lg" class="font-semibold">Aktivitas Penggunaan Terbaru</flux:heading>
                <div class="overflow-x-auto rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-zinc-800 shadow-sm">
                    <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                        <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-200">Waktu</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-200">Rumah</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-200">Material</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-200">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                            @forelse ($recent_activities as $activity)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition">
                                <td class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">{{ $activity->created_at->diffForHumans() }}</td>
                                <td class="px-4 py-3 text-sm font-medium dark:text-zinc-100">{{ $activity->house->name }}</td>
                                <td class="px-4 py-3 text-sm dark:text-zinc-300">{{ $activity->material->name }}</td>
                                <td class="px-4 py-3 text-right font-mono text-sm dark:text-zinc-200">{{ str_replace('.', ',', (float) $activity->quantity) }} {{ $activity->material->unit }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-sm text-zinc-400">Belum ada aktivitas penggunaan.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="flex justify-end">
                    <flux:button variant="ghost" size="sm" icon-trailing="arrow-right" href="{{ route('logistik.houses') }}" wire:navigate>Buka Proyek Rumah</flux:button>
                </div>
            </div>
        </div>
    </flux:main>
</x-layouts::app.sidebar>

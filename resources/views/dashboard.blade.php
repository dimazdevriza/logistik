<x-layouts::app.sidebar title="Admin Dashboard">
    <flux:main>
        <div class="flex h-full w-full flex-1 flex-col gap-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <flux:heading size="xl" class="font-bold">Ringkasan Sistem</flux:heading>
                    <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">Ikhtisar operasional dan kesehatan sistem logistik.</flux:text>
                </div>
            </div>

            {{-- Admin Stats Grid --}}
            <div class="grid gap-4 md:grid-cols-4">
                <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-zinc-800 p-5 flex flex-col gap-2 shadow-sm transition-all duration-300">
                    <flux:text class="text-xs uppercase tracking-widest text-zinc-400 dark:text-zinc-500 font-bold">Total Rumah</flux:text>
                    <flux:heading size="xl" class="font-bold text-2xl text-primary dark:text-blue-400">{{ $total_houses }}</flux:heading>
                    <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">Unit dalam database</flux:text>
                </div>
                <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-zinc-800 p-5 flex flex-col gap-2 shadow-sm transition-all duration-300">
                    <flux:text class="text-xs uppercase tracking-widest text-zinc-400 dark:text-zinc-500 font-bold">Total User</flux:text>
                    <flux:heading size="xl" class="font-bold text-2xl text-green-600 dark:text-green-400">{{ $total_users }}</flux:heading>
                    <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">Akun terdaftar</flux:text>
                </div>
                <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-zinc-800 p-5 flex flex-col gap-2 shadow-sm transition-all duration-300">
                    <flux:text class="text-xs uppercase tracking-widest text-zinc-400 dark:text-zinc-500 font-bold">Total Supplier</flux:text>
                    <flux:heading size="xl" class="font-bold text-2xl text-indigo-600 dark:text-indigo-400">{{ $total_suppliers }}</flux:heading>
                    <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">Rekan bisnis aktif</flux:text>
                </div>
                <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-zinc-800 p-5 flex flex-col gap-2 shadow-sm transition-all duration-300">
                    <flux:text class="text-xs uppercase tracking-widest text-zinc-400 dark:text-zinc-500 font-bold">Total Pengeluaran</flux:text>
                    <flux:heading size="xl" class="font-bold text-lg text-orange-600 dark:text-orange-400">Rp {{ number_format($total_cost, 0, ',', '.') }}</flux:heading>
                    <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">Total biaya material</flux:text>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                {{-- Quick Links / Actions --}}
                <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-zinc-800 p-6 shadow-sm">
                    <flux:heading size="lg" class="mb-4 font-semibold">Akses Cepat</flux:heading>
                    <div class="grid grid-cols-2 gap-3">
                        <flux:button href="{{ route('admin.users') }}" wire:navigate variant="ghost" class="justify-start" icon="users">Kelola User</flux:button>
                        <flux:button href="{{ route('logistik.houses') }}" wire:navigate variant="ghost" class="justify-start" icon="home">Unit Rumah</flux:button>
                        <flux:button href="{{ route('logistik.materials') }}" wire:navigate variant="ghost" class="justify-start" icon="cube">Inventaris</flux:button>
                        <flux:button href="{{ route('admin.house-costs') }}" wire:navigate variant="ghost" class="justify-start" icon="banknotes">Laporan Biaya</flux:button>
                    </div>
                </div>

                {{-- System Info --}}
                <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-zinc-50 dark:bg-zinc-900/50 p-6 flex flex-col justify-center">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="p-3 bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-neutral-200 dark:border-neutral-700">
                            <flux:icon name="cpu-chip" class="text-zinc-400" />
                        </div>
                        <div>
                            <flux:heading>Status Sistem</flux:heading>
                            <flux:text size="sm">D'Royal Village v1.0.0 (PHP v{{ PHP_VERSION }})</flux:text>
                        </div>
                    </div>
                    <flux:text size="sm" class="text-zinc-500">Semua modul operasional berjalan normal. Database terkoneksi.</flux:text>
                </div>
            </div>
        </div>
    </flux:main>
</x-layouts::app.sidebar>

<x-layouts::app.sidebar title="Dashboard Logistik">
    <flux:main>
        {{-- Root layout container with Outfit font, ambient glows, and layout spacing --}}
        <div class="font-outfit text-zinc-900 dark:text-zinc-100 flex flex-col gap-8 py-4 relative overflow-hidden w-full max-w-full">
            
            {{-- Ambient glows --}}
            <div class="ambient-glow -top-20 -left-20 opacity-60"></div>
            <div class="ambient-glow -bottom-40 -right-20 opacity-40"></div>

            {{-- 1. ATTENTION (Hero/Header Section in Artistic Asymmetry) --}}
            <div class="animate-header flex flex-col lg:flex-row lg:items-center lg:justify-between gap-8 bg-linear-to-r from-zinc-50 to-zinc-100/50 dark:from-zinc-900 dark:to-zinc-900/50 p-8 rounded-3xl border border-zinc-200/80 dark:border-zinc-700/80 shadow-xs relative overflow-hidden">
                <div class="flex-1 z-10">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-green-500/10 text-green-600 dark:text-green-400 border border-green-500/20 backdrop-blur-md uppercase tracking-wider mb-3 font-geist">Logistics Module</span>
                    <h1 class="text-4xl md:text-5xl font-black tracking-tight leading-[1.1] max-w-3xl">
                        Logistics <span class="bg-linear-to-r from-green-500 to-emerald-600 bg-clip-text text-transparent">Operations Control</span>
                    </h1>
                    <p class="mt-3 text-zinc-500 dark:text-zinc-400 font-medium max-w-xl text-sm md:text-base">
                        Monitor stock levels, review low stock alerts, and manage tools currently on loan.
                    </p>
                </div>
                {{-- Floating Glass Widget on the right (Asymmetric layout) --}}
                <div class="lg:w-72 shrink-0 z-10 p-5 rounded-2xl border border-white/20 dark:border-zinc-700/50 bg-white/40 dark:bg-zinc-800/40 backdrop-blur-xl shadow-lg flex flex-col gap-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="size-2 rounded-full bg-green-500 animate-ping"></span>
                            <span class="text-xs font-bold font-geist tracking-wide text-zinc-500 dark:text-zinc-400 uppercase">Operational Hub</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 bg-green-500/10 text-green-600 dark:text-green-400 rounded-lg">
                            <flux:icon name="truck" class="size-5" />
                        </div>
                        <div>
                            <div class="text-xs text-zinc-400">Inventory Status</div>
                            <div class="text-sm font-bold">All Systems Nominal</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. INTEREST (Bento Stats Grid - 3 cols) --}}
            <div class="grid gap-6 md:grid-cols-3">
                
                {{-- Stats Card 1: Total Material --}}
                <div class="animate-card p-6 rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white/60 dark:bg-zinc-900/60 backdrop-blur-md relative overflow-hidden transition-all duration-500 hover:scale-[1.01] hover:border-zinc-300 dark:hover:border-zinc-600 shadow-md group">
                    <div class="absolute -right-4 -bottom-4 size-32 bg-green-500/5 rounded-full blur-2xl group-hover:bg-green-500/10 transition-all duration-500"></div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs font-bold tracking-widest text-zinc-400 dark:text-zinc-500 uppercase font-geist">Total Material</span>
                        <div class="p-2 bg-zinc-100 dark:bg-zinc-800 rounded-lg group-hover:text-green-500 transition-colors">
                            <flux:icon name="cube" class="size-5" />
                        </div>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-4xl font-extrabold tracking-tight text-zinc-900 dark:text-white">{{ $total_materials }}</span>
                        <span class="text-sm font-semibold text-zinc-500 dark:text-zinc-400">Item</span>
                    </div>
                    <div class="mt-4 pt-4 border-t border-zinc-100 dark:border-zinc-800 text-xs text-zinc-400">
                        Tersedia di Gudang
                    </div>
                </div>

                {{-- Stats Card 2: Low Stock (Rose glow outline) --}}
                <div class="animate-card p-6 rounded-2xl border border-rose-200/80 dark:border-rose-900/50 bg-white/60 dark:bg-zinc-900/60 backdrop-blur-md relative overflow-hidden transition-all duration-500 hover:scale-[1.01] shadow-md group border-l-4 border-l-rose-500">
                    <div class="absolute -right-4 -bottom-4 size-32 bg-rose-500/5 rounded-full blur-2xl group-hover:bg-rose-500/10 transition-all duration-500"></div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs font-bold tracking-widest text-zinc-400 dark:text-zinc-500 uppercase font-geist">Stok Menipis</span>
                        <div class="p-2 bg-rose-500/10 text-rose-600 dark:text-rose-400 rounded-lg">
                            <flux:icon name="exclamation-triangle" class="size-5" />
                        </div>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-4xl font-extrabold tracking-tight text-rose-600 dark:text-rose-400">{{ $low_stock_count }}</span>
                        <span class="text-sm font-semibold text-rose-500 dark:text-rose-400">Item</span>
                    </div>
                    <div class="mt-4 pt-4 border-t border-rose-100 dark:border-rose-950/30 text-xs text-rose-500/85">
                        Stok di bawah 10 unit
                    </div>
                </div>

                {{-- Stats Card 3: Tools on Loan (Orange glow outline) --}}
                <div class="animate-card p-6 rounded-2xl border border-orange-200/80 dark:border-orange-900/50 bg-white/60 dark:bg-zinc-900/60 backdrop-blur-md relative overflow-hidden transition-all duration-500 hover:scale-[1.01] shadow-md group border-l-4 border-l-orange-500">
                    <div class="absolute -right-4 -bottom-4 size-32 bg-orange-500/5 rounded-full blur-2xl group-hover:bg-orange-500/10 transition-all duration-500"></div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs font-bold tracking-widest text-zinc-400 dark:text-zinc-500 uppercase font-geist">Alat Sedang Dipinjam</span>
                        <div class="p-2 bg-orange-500/10 text-orange-600 dark:text-orange-400 rounded-lg">
                            <flux:icon name="wrench" class="size-5" />
                        </div>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-4xl font-extrabold tracking-tight text-orange-600 dark:text-orange-400">{{ $tools_on_loan }}</span>
                        <span class="text-sm font-semibold text-orange-500 dark:text-orange-400">Transaksi</span>
                    </div>
                    <div class="mt-4 pt-4 border-t border-orange-100 dark:border-orange-950/30 text-xs text-orange-500/85">
                        Menunggu pengembalian
                    </div>
                </div>

            </div>

            {{-- 3. DESIRE (Spacious Editorial Recent Activities Table) --}}
            <div class="animate-card flex flex-col gap-5 mt-4">
                <div class="flex items-center justify-between">
                    <flux:heading size="lg" class="font-extrabold tracking-tight">Aktivitas Penggunaan Terbaru</flux:heading>
                    <flux:button variant="ghost" size="sm" icon-trailing="arrow-right" class="hover:bg-zinc-100 dark:hover:bg-zinc-800 text-xs font-bold" href="{{ route('logistik.houses') }}" wire:navigate>Buka Proyek Rumah</flux:button>
                </div>
                
                <div class="overflow-x-auto rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white/60 dark:bg-zinc-900/60 backdrop-blur-md shadow-md">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
                        <thead class="bg-zinc-50/50 dark:bg-zinc-950/50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 font-geist">Waktu</th>
                                <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 font-geist">Rumah</th>
                                <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 font-geist">Material</th>
                                <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 font-geist">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800/80">
                            @forelse ($recent_activities as $activity)
                            <tr class="hover:bg-zinc-50/70 dark:hover:bg-zinc-800/30 transition-all duration-300">
                                <td class="px-6 py-4.5 text-sm text-zinc-500 dark:text-zinc-400 font-medium">{{ $activity->created_at->diffForHumans() }}</td>
                                <td class="px-6 py-4.5 text-sm font-bold text-zinc-900 dark:text-zinc-100">{{ $activity->house->name }}</td>
                                <td class="px-6 py-4.5 text-sm text-zinc-600 dark:text-zinc-300 font-medium">{{ $activity->material->name }}</td>
                                <td class="px-6 py-4.5 text-right font-mono text-sm text-zinc-900 dark:text-zinc-200 font-bold">
                                    {{ str_replace('.', ',', (float) $activity->quantity) }} <span class="text-xs text-zinc-400 font-sans font-medium">{{ $activity->material->unit }}</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="px-6 py-10 text-center text-sm text-zinc-400 font-medium">Belum ada aktivitas penggunaan.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </flux:main>

    {{-- Script references for GSAP loading --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            if (!window.gsap) return;
            gsap.set([".animate-header", ".animate-card"], { clearProps: "all" });
            gsap.from(".animate-header", {
                opacity: 0,
                y: -20,
                duration: 0.8,
                ease: "power3.out"
            });
            gsap.from(".animate-card", {
                opacity: 0,
                y: 30,
                duration: 0.8,
                stagger: 0.08,
                ease: "power3.out",
                delay: 0.15,
                clearProps: "transform,opacity"
            });
        });
    </script>
</x-layouts::app.sidebar>

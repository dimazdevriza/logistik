<div>
    <div class="flex h-full w-full flex-1 flex-col gap-6 p-4">

        {{-- Header --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <flux:button href="{{ route('logistik.house-detail', $house) }}" wire:navigate variant="ghost" size="sm" icon="arrow-left" class="text-zinc-500 hover:text-zinc-700" />
                    <flux:badge variant="warning">Penyelesaian Proyek</flux:badge>
                </div>
                <flux:heading size="xl" class="font-bold">Selesaikan Proyek</flux:heading>
                <flux:text class="text-zinc-700 dark:text-zinc-300 font-medium font-semibold">
                    {{ $house->name }}{{ $house->type ? ' — ' . $house->type : '' }}
                </flux:text>
            </div>
        </div>

        @if ($errors->has('completion'))
            <flux:callout variant="danger" icon="x-circle">{{ $errors->first('completion') }}</flux:callout>
        @endif
        @if ($errors->has('toolSelections'))
            <flux:callout variant="danger" icon="x-circle">{{ $errors->first('toolSelections') }}</flux:callout>
        @endif

        {{-- Step 1: Material Summary --}}
        <div>
            <div class="flex items-center gap-2 mb-2">
                <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-orange-500 text-white font-bold text-[10px] shrink-0">1</span>
                <flux:heading size="lg" class="font-semibold">Ringkasan Material Digunakan</flux:heading>
            </div>

            <div class="overflow-x-auto rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-zinc-800 shadow-sm">
                <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                        <tr>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-200 w-16">No.</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-200">Material</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-200">Jumlah</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-200">Total Biaya</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                        @forelse($materialUsages as $usage)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition">
                            <td class="px-4 py-3 text-sm text-center text-zinc-700 dark:text-zinc-500">{{ ($materialUsages->currentPage() - 1) * $materialUsages->perPage() + $loop->iteration }}</td>
                            <td class="px-4 py-3 text-sm font-medium dark:text-zinc-100">{{ $usage->material->name }}</td>
                            <td class="px-4 py-3 text-sm text-right text-zinc-700 dark:text-zinc-300 font-semibold">{{ str_replace('.', ',', (float) rtrim(rtrim($usage->quantity, '0'), '.')) }} {{ $usage->material->unit }}</td>
                            <td class="px-4 py-3 text-sm text-right font-mono font-bold text-orange-600 dark:text-orange-400">Rp {{ number_format($usage->total_cost, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-sm text-zinc-600 dark:text-zinc-500">Tidak ada material yang digunakan.</td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-orange-50 dark:bg-orange-900/20 border-t border-neutral-200 dark:border-neutral-700">
                        <tr>
                            <td class="px-4 py-3 text-sm font-semibold text-zinc-700 dark:text-zinc-300" colspan="3">Total Biaya Material</td>
                            <td class="px-4 py-3 text-right font-mono font-bold text-orange-600 dark:text-orange-400 text-base">Rp {{ number_format($totalMaterialCost, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="mt-4">
                {{ $materialUsages->links() }}
            </div>
        </div>

        {{-- Step 2: Tool Accountability --}}
        <div>
            <div class="flex items-center gap-2 mb-2">
                <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-indigo-500 text-white font-bold text-[10px] shrink-0">2</span>
                <flux:heading size="lg" class="font-semibold">Pertanggungjawaban Alat</flux:heading>
                <flux:text class="text-sm text-zinc-500">— Tentukan kondisi setiap alat yang dikembalikan.</flux:text>
            </div>

            @if($activeToolUsages->isEmpty())
                <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-zinc-800 shadow-sm px-4 py-10 text-center">
                    <flux:icon.check-circle class="w-8 h-8 text-emerald-400 mx-auto mb-2" />
                    <flux:text class="text-sm text-zinc-400">Semua alat telah dikembalikan sebelumnya.</flux:text>
                </div>
            @else
                <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-zinc-800 shadow-sm overflow-hidden">
                    <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                        <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                            <tr>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-200 w-16">No.</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">Alat</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">Qty</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">Status Pengembalian</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                            @foreach($activeToolUsages as $usage)
                            @php $currentAction = $toolSelections[$usage->id]['action'] ?? 'normal'; @endphp
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition" wire:key="tool-{{ $usage->id }}">
                                <td class="px-4 py-4 text-sm text-center text-zinc-700 dark:text-zinc-500">{{ $loop->iteration }}</td>

                                {{-- Tool Name + Code --}}
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ $usage->tool->name }}</span>
                                    <span class="text-[10px] font-mono px-1.5 py-0.5 bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded text-zinc-600 dark:text-zinc-400 font-bold">{{ $usage->tool->code }}</span>
                                    </div>
                                </td>

                                {{-- Qty --}}
                                <td class="px-4 py-4 text-center text-sm font-bold text-zinc-800 dark:text-zinc-200">{{ str_replace('.', ',', (float) $usage->quantity) }}</td>

                                {{-- Status Radio Buttons --}}
                                <td class="px-4 py-4">
                                    <div class="flex gap-2">

                                        <label class="cursor-pointer">
                                            <input type="radio" wire:model.live="toolSelections.{{ $usage->id }}.action" value="normal" class="sr-only">
                                            <div class="px-3 py-1.5 rounded-lg border text-xs font-semibold transition-all select-none flex items-center gap-1"
                                                @if($currentAction === 'normal') style="border-color:#10b981;background:#10b981;color:white;" @else style="border-color:#e4e4e7;background:transparent;color:#a1a1aa;" @endif>
                                                <flux:icon.check class="size-3" /> Baik
                                            </div>
                                        </label>



                                        <label class="cursor-pointer">
                                            <input type="radio" wire:model.live="toolSelections.{{ $usage->id }}.action" value="broken" class="sr-only">
                                            <div class="px-3 py-1.5 rounded-lg border text-xs font-semibold transition-all select-none flex items-center gap-1"
                                                @if($currentAction === 'broken') style="border-color:#ef4444;background:#ef4444;color:white;" @else style="border-color:#e4e4e7;background:transparent;color:#a1a1aa;" @endif>
                                                <flux:icon.x-mark class="size-3" /> Rusak
                                            </div>
                                        </label>

                                        <label class="cursor-pointer">
                                            <input type="radio" wire:model.live="toolSelections.{{ $usage->id }}.action" value="lost" class="sr-only">
                                            <div class="px-3 py-1.5 rounded-lg border text-xs font-semibold transition-all select-none flex items-center gap-1"
                                                @if($currentAction === 'lost') style="border-color:#71717a;background:#71717a;color:white;" @else style="border-color:#e4e4e7;background:transparent;color:#a1a1aa;" @endif>
                                                <flux:icon.question-mark-circle class="size-3" /> Hilang
                                            </div>
                                        </label>

                                    </div>
                                </td>

                                {{-- Notes / Cost --}}
                                <td class="px-4 py-4">
                                    @if(in_array($currentAction, ['broken', 'lost']))
                                    <div class="flex flex-col gap-2">
                                        <input
                                            type="text"
                                            wire:model="toolSelections.{{ $usage->id }}.notes"
                                            placeholder="Keterangan..."
                                            class="w-full text-xs px-2.5 py-1.5 rounded-lg border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300 placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-orange-400"
                                        />
                                        <label class="inline-flex items-center gap-2 mt-1 cursor-pointer select-none">
                                            <input type="checkbox" wire:model.live="toolSelections.{{ $usage->id }}.has_charge" class="rounded border-neutral-300 dark:border-neutral-700 bg-white dark:bg-zinc-900 text-red-500 focus:ring-red-400">
                                            <span class="text-[11px] font-medium text-zinc-600 dark:text-zinc-400">Terapkan ganti rugi</span>
                                        </label>

                                        @if($toolSelections[$usage->id]['has_charge'] ?? false)
                                        <div x-data="{ 
                                            display: '',
                                            init() {
                                                this.display = this.format($wire.get('toolSelections.{{ $usage->id }}.replacement_cost'));
                                                this.$watch('display', val => {
                                                    // Parse: only allow digits and one comma for decimal
                                                    let clean = val.replace(/[^\d]/g, '');
                                                    if (clean === '') { 
                                                        $wire.set('toolSelections.{{ $usage->id }}.replacement_cost', null); 
                                                        this.display = ''; 
                                                        return; 
                                                    }
                                                    
                                                    let num = parseInt(clean, 10);
                                                    let formatted = this.format(num);
                                                    if (this.display !== formatted) { this.display = formatted; }
                                                    $wire.set('toolSelections.{{ $usage->id }}.replacement_cost', num);
                                                });
                                                $wire.$watch('toolSelections.{{ $usage->id }}.replacement_cost', val => {
                                                    if (document.activeElement !== this.$refs.input) {
                                                        this.display = this.format(val);
                                                    }
                                                });
                                            },
                                            format(num) {
                                                if (num === null || num === undefined || num === '') return '';
                                                // Format: Rp 1.000.000 (Indonesian style)
                                                return 'Rp ' + num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                                            }
                                        }">
                                            <input
                                                type="text"
                                                x-ref="input"
                                                x-model="display"
                                                placeholder="Estimasi ganti rugi (Rp)"
                                                class="w-full text-xs px-2.5 py-1.5 rounded-lg border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300 placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-red-400"
                                            />
                                        </div>
                                        @endif
                                    </div>
                                    @else
                                    <span class="text-xs text-zinc-400 italic">—</span>
                                    @endif
                                </td>

                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Step 3: Confirmation --}}
        <div>
            <div class="flex items-center gap-2 mb-2">
                <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-orange-500 text-white font-bold text-[10px] shrink-0">3</span>
                <flux:heading size="lg" class="font-semibold">Konfirmasi Penyelesaian</flux:heading>
            </div>

            <div class="rounded-xl border border-orange-200 dark:border-orange-800 bg-orange-50 dark:bg-orange-900/20 p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <flux:text class="text-sm text-orange-700 dark:text-orange-400">
                    Status rumah akan berubah menjadi <strong>Selesai</strong> dan semua data akan dikunci. Pastikan semua alat sudah dipertanggungjawabkan.
                </flux:text>
                <flux:button
                    wire:click="confirm('processCompletion', null, 'Selesaikan Proyek?', 'Apakah Anda yakin ingin menyelesaikan proyek ini? Status rumah akan berubah menjadi Selesai dan penggunaan material akan dikunci. Tindakan ini tidak dapat dibatalkan.')"
                    wire:loading.attr="disabled"
                    variant="primary"
                    icon="flag"
                    class="shrink-0"
                >
                    <span wire:loading.remove>Selesaikan Proyek</span>
                    <span wire:loading class="flex items-center gap-2">
                        <flux:icon.arrow-path class="size-4 animate-spin" />
                        Memproses...
                    </span>
                </flux:button>
            </div>
        </div>

    </div>

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
                <flux:button wire:click="executeConfirmedAction" variant="primary">Ya, Selesaikan</flux:button>
            </div>
        </div>
    </flux:modal>
</div>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-6 p-4">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <flux:heading size="xl" class="font-bold">Alat</flux:heading>
                <flux:text class="mt-1 text-zinc-700 dark:text-zinc-300">Kelola inventaris alat dan peralatan proyek.</flux:text>
            </div>
            <div class="flex gap-2">
                @if(in_array(auth()->user()->role, ['admin', 'logistik']))
                    <flux:button wire:click="exportExcel" variant="filled" class="bg-emerald-600 hover:bg-emerald-700 text-white" icon="document-chart-bar">Export Excel</flux:button>
                @endif
                <flux:button wire:click="create" variant="primary" icon="plus">Tambah Alat</flux:button>
            </div>
        </div>

        @if (session('success'))
            <flux:callout variant="success" icon="check-circle" dismissible>{{ session('success') }}</flux:callout>
        @endif
        @if (session('error'))
            <flux:callout variant="danger" icon="x-circle" dismissible>{{ session('error') }}</flux:callout>
        @endif

        {{-- Stats Cards --}}
        <div class="grid grid-cols-2 gap-4">
            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-zinc-800 p-4 shadow-sm">
                <flux:text class="text-xs uppercase tracking-widest text-zinc-600 dark:text-zinc-400">Total Alat</flux:text>
                <flux:heading size="xl" class="font-bold text-2xl mt-1">{{ number_format($totalTools) }}</flux:heading>
            </div>
            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-zinc-800 p-4 shadow-sm">
                <flux:text class="text-xs uppercase tracking-widest text-zinc-600 dark:text-zinc-400">Tersedia di Stok</flux:text>
                <flux:heading size="xl" class="font-bold text-2xl mt-1 text-emerald-600">{{ number_format($totalAvailable) }}</flux:heading>
            </div>
        </div>


        <div class="flex flex-col md:flex-row md:items-center gap-3">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari nama atau kode alat..." icon="magnifying-glass" class="w-full md:max-w-[250px]" />
            
            <div class="flex items-center gap-2 w-full md:w-auto">
                <x-filter-modal :activeFiltersCount="$this->getActiveFiltersCount()">
                    {{-- Category Filter --}}
                    <div>
                        <flux:label>Kategori</flux:label>
                        <flux:select wire:model.live="filterCategory" class="mt-2">
                            <option value="">Semua Kategori</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                    
                    {{-- Condition Filter --}}
                    <div>
                        <flux:label>Kondisi</flux:label>
                        <flux:select wire:model.live="filterCondition" class="mt-2">
                            <option value="">Semua Kondisi</option>
                            <option value="baik">Baik</option>
                            <option value="rusak">Rusak</option>
                            <option value="hilang">Hilang</option>
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
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">Kategori</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">Kondisi</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">Harga Beli</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">Total</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">Tersedia</th>

                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                    @forelse ($tools as $tool)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition">
                        <td class="px-4 py-3 text-sm text-center text-zinc-700 dark:text-zinc-500">{{ ($tools->currentPage() - 1) * $tools->perPage() + $loop->iteration }}</td>
                        <td class="px-4 py-3 text-sm font-mono font-medium dark:text-zinc-300">{{ $tool->code }}</td>
                        <td class="px-4 py-3 text-sm font-medium dark:text-zinc-100">{{ $tool->name }}</td>
                        <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-400">{{ $tool->category?->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm">
                            @php $conditionColors = ['baik' => 'success', 'rusak' => 'danger', 'hilang' => 'warning']; @endphp
                            <flux:badge :variant="$conditionColors[$tool->condition] ?? 'default'" size="sm">{{ ucfirst($tool->condition) }}</flux:badge>
                        </td>
                        <td class="px-4 py-3 text-sm text-right font-mono dark:text-zinc-200">Rp {{ number_format($tool->purchase_price, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-sm text-center dark:text-zinc-300">{{ $tool->total_qty }}</td>
                        <td class="px-4 py-3 text-sm text-center">
                            <span class="{{ $tool->available_qty === 0 ? 'text-red-500 dark:text-red-400 font-semibold' : 'dark:text-zinc-100' }}">{{ $tool->available_qty }}</span>
                        </td>

                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-2">
                                <flux:button wire:click="edit({{ $tool->id }})" size="sm" variant="ghost" icon="pencil-square" title="Edit Data" />
                                <flux:button wire:click="confirm('delete', {{ $tool->id }}, 'Hapus Alat?', 'Apakah Anda yakin ingin menghapus alat ini dari inventaris?')" size="sm" variant="ghost" icon="trash" class="text-red-500 hover:text-red-700" title="Hapus Data" />
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-4 py-8 text-center text-sm text-zinc-400">Belum ada data alat.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $tools->links() }}</div>



    </div>

    {{-- Modal --}}
    <flux:modal wire:model="showModal" class="max-w-xl">
        <div class="space-y-6">
            <flux:heading size="lg">{{ $editMode ? 'Edit Alat' : 'Tambah Alat' }}</flux:heading>

            <flux:input wire:model="name" label="Nama Alat" placeholder="Contoh: Molen Beton" :error="$errors->first('name')" />

            <div class="grid grid-cols-2 gap-4">
                <flux:select wire:model.live="category_id" label="Kategori" :error="$errors->first('category_id')">
                    <option value="">-- Pilih Kategori --</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="code" label="Kode Aset" placeholder="Pilih kategori..." readonly class="bg-zinc-50 dark:bg-zinc-900 pointer-events-none" :error="$errors->first('code')">
                    <x-slot name="append">
                        <span class="text-[10px] font-bold text-emerald-600 uppercase pr-3 tracking-widest">Otomatis</span>
                    </x-slot>
                </flux:input>
            </div>

            <flux:select wire:model="condition" label="Kondisi" :error="$errors->first('condition')">
                <option value="baik">Baik</option>
                <option value="rusak">Rusak</option>
                <option value="hilang">Hilang</option>
            </flux:select>

            <div class="grid grid-cols-3 gap-4">
                <div x-data="{ 
                    display: '',
                    init() {
                        this.display = this.format($wire.purchase_price);
                        this.$watch('display', val => {
                            let clean = val.replace(/[^\d]/g, '');
                            if (clean === '') { $wire.purchase_price = null; this.display = ''; return; }
                            let num = parseInt(clean, 10);
                            let formatted = this.format(num);
                            if (this.display !== formatted) { this.display = formatted; }
                            $wire.purchase_price = num;
                        });
                        $wire.$watch('purchase_price', val => {
                            if (document.activeElement !== this.$refs.input) {
                                this.display = this.format(val);
                            }
                        });
                    },
                    format(num) {
                        if (num === null || num === undefined || num === '') return '';
                        return 'Rp ' + num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                    }
                }">
                    <flux:input x-ref="input" x-model="display" label="Harga Beli" type="text" :error="$errors->first('purchase_price')" />
                </div>
                <flux:input wire:model="total_qty" label="Total Qty" type="number" :error="$errors->first('total_qty')" />
                <flux:input wire:model="available_qty" label="Tersedia" type="number" :error="$errors->first('available_qty')" />
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
                <flux:button wire:click="executeConfirmedAction" variant="danger">{{ $confirmingAction === 'fixTool' ? 'Ya, Konfirmasi' : 'Ya, Hapus' }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>

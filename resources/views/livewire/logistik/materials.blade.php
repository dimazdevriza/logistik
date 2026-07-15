<div>
    <div class="flex h-full w-full flex-1 flex-col gap-6 p-4">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <flux:heading size="xl" class="font-bold">Material</flux:heading>
                <flux:text class="mt-1 text-zinc-700 dark:text-zinc-300">Kelola stok material bangunan.</flux:text>
            </div>
            <div class="flex gap-2">
                @if(in_array(auth()->user()->role, ['admin', 'logistik']))
                    <flux:button wire:click="exportExcel" variant="filled" class="bg-emerald-600 hover:bg-emerald-700 text-white" icon="document-chart-bar">Export Excel</flux:button>
                @endif
                <flux:button wire:click="create" variant="primary" icon="plus">Tambah Material</flux:button>
            </div>
        </div>

        @if (session('success'))
            <flux:callout variant="success" icon="check-circle" dismissible>{{ session('success') }}</flux:callout>
        @endif

        <div class="grid gap-4 md:grid-cols-2">
            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-zinc-800 p-5 flex flex-col gap-2 shadow-sm">
                <flux:text class="text-xs uppercase tracking-widest text-zinc-600 dark:text-zinc-400 font-bold">Total Nilai Material</flux:text>
                <flux:heading size="xl" class="font-bold text-2xl text-orange-600 dark:text-orange-400">Rp {{ number_format($totalValue, 0, ',', '.') }}</flux:heading>
                <flux:text class="text-xs text-zinc-500 dark:text-zinc-500">Jumlah harga × stok seluruh material</flux:text>
            </div>
            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-zinc-800 p-5 flex flex-col gap-2 shadow-sm">
                <flux:text class="text-xs uppercase tracking-widest text-zinc-600 dark:text-zinc-400 font-bold">Total Jenis Material</flux:text>
                <flux:heading size="xl" class="font-bold text-2xl text-indigo-600 dark:text-indigo-400">{{ $totalItems }} Item</flux:heading>
                <flux:text class="text-xs text-zinc-500 dark:text-zinc-500">Material dengan stok tersedia</flux:text>
            </div>
        </div>

        <div class="flex flex-col md:flex-row md:items-center gap-3">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari material..." icon="magnifying-glass" class="w-full md:max-w-[200px]" />
            
            <div class="flex items-center gap-2 w-full md:w-auto">
                <x-filter-modal :activeFiltersCount="$this->getActiveFiltersCount()">
                    {{-- Sort --}}
                    <div>
                        <flux:label>Urutkan Berdasarkan</flux:label>
                        <flux:select wire:model.live="sort" class="mt-2">
                            <option value="name_asc">Nama A-Z</option>
                            <option value="name_desc">Nama Z-A</option>
                            <option value="stock_asc">Stok Terendah</option>
                            <option value="stock_desc">Stok Tertinggi</option>
                            <option value="unit_price_asc">Harga Terendah</option>
                            <option value="unit_price_desc">Harga Tertinggi</option>
                        </flux:select>
                    </div>

                    {{-- Category Filter --}}
                    <div>
                        <flux:label>Kategori</flux:label>
                        <flux:select wire:model.live="filterCategory" class="mt-2">
                            <option value="">Semua Kategori</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </flux:select>
                    </div>

                    {{-- Stock Filter --}}
                    <div>
                        <flux:label>Status Stok</flux:label>
                        <flux:select wire:model.live="filterStock" class="mt-2">
                            <option value="">Semua Stok</option>
                            <option value="safe">Aman (&gt; 10)</option>
                            <option value="low">Menipis (&le; 10)</option>
                            <option value="empty">Habis (0)</option>
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
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">Nama</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">Kategori</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">Supplier</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">Stok + Satuan</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">Harga Satuan</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">Total Nilai</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                    @forelse ($materials as $material)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition cursor-pointer"
                        x-on:click="if (!$event.target.closest('button') && !$event.target.closest('a') && !$event.target.closest('input')) { $wire.edit({{ $material->id }}) }">
                        <td class="px-4 py-3 text-sm text-center text-zinc-700 dark:text-zinc-500">{{ ($materials->currentPage() - 1) * $materials->perPage() + $loop->iteration }}</td>
                        <td class="px-4 py-3 text-sm font-medium dark:text-zinc-100">{{ $material->name }}</td>
                        <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-400">{{ $material->category?->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-400">{{ $material->supplier?->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-right dark:text-zinc-100">
                            <span class="{{ $material->stock <= 10 ? 'text-red-600 dark:text-red-400 font-bold' : '' }}">
                                {{ number_format($material->stock, 0, ',', '.') }}
                            </span>
                            <span class="text-xs text-zinc-600 dark:text-zinc-500 ml-1">{{ $material->unit }}</span>
                        </td>
                        <td class="px-4 py-3 text-sm text-right font-mono dark:text-zinc-200">Rp {{ number_format($material->unit_price, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-sm text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">Rp {{ number_format($material->unit_price * $material->stock, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-2">
                                <flux:button wire:click="restock({{ $material->id }})" size="sm" variant="ghost" icon="arrow-path" class="text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300" title="Restock" />
                                <flux:button wire:click="edit({{ $material->id }})" size="sm" variant="ghost" icon="pencil-square" title="Edit Data" />
                                <flux:button wire:click="confirm('delete', {{ $material->id }}, 'Hapus Material?', 'Apakah Anda yakin ingin menghapus material ini? Seluruh data stok terkait akan dihapus permanen.')" size="sm" variant="ghost" icon="trash" class="text-red-500 hover:text-red-700" title="Hapus Data" />
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-sm text-zinc-600 dark:text-zinc-500">Belum ada data material.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $materials->links() }}</div>
    </div>

    {{-- Modal: Create / Edit Material --}}
    <flux:modal wire:model="showModal" class="max-w-lg">
        <div class="space-y-6">
            <flux:heading size="lg">{{ $editMode ? 'Edit Material' : 'Tambah Material' }}</flux:heading>

            <flux:input wire:model="name" label="Nama Material" placeholder="Contoh: Semen Portland 50kg" :error="$errors->first('name')" />

            <div class="grid grid-cols-2 gap-4">
                <flux:select wire:model="category_id" label="Kategori">
                    <option value="">-- Pilih --</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </flux:select>

                <div>
                    <flux:input wire:model="supplier_name" label="Supplier" list="suppliers-list" placeholder="Pilih atau ketik nama baru..." :error="$errors->first('supplier_name')" />
                    <datalist id="suppliers-list">
                        @foreach ($suppliers as $sup)
                            <option value="{{ $sup->name }}"></option>
                        @endforeach
                    </datalist>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <flux:select wire:model="unit" label="Satuan" :error="$errors->first('unit')">
                    <option value="">-- Pilih --</option>
                    <option value="sak">Sak / Zak</option>
                    <option value="batang">Batang</option>
                    <option value="buah">Buah / Pcs</option>
                    <option value="lembar">Lembar</option>
                    <option value="kg">Kilogram (kg)</option>
                    <option value="meter">Meter (m)</option>
                    <option value="m²">Meter Persegi (m²)</option>
                    <option value="m³">Meter Kubik (m³)</option>
                    <option value="liter">Liter (L)</option>
                    <option value="kaleng">Kaleng</option>
                    <option value="dus">Dus / Kotak</option>
                    <option value="rol">Rol</option>
                    <option value="set">Set</option>
                    <option value="ton">Ton</option>
                    <option value="rit">Rit / Truk</option>
                </flux:select>
                <div x-data="{
                    display: '',
                    init() {
                        this.display = this.format($wire.unit_price);
                        this.$watch('display', val => {
                            let digits = val.replace(/\D/g, '');
                            if (digits === '') {
                                $wire.unit_price = null;
                                this.display = '';
                                return;
                            }
                            let num = parseInt(digits, 10);
                            let formatted = this.format(num);
                            if (this.display !== formatted) {
                                this.display = formatted;
                            }
                            $wire.unit_price = num;
                        });
                        $wire.$watch('unit_price', val => {
                            if (document.activeElement !== this.$refs.input) {
                                this.display = this.format(val);
                            }
                        });
                    },
                    format(num) {
                        if (num === null || num === undefined || num === '') return '';
                        return 'Rp' + num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                    }
                }">
                    <flux:input x-ref="input" x-model="display" label="Harga Satuan" type="text" :error="$errors->first('unit_price')" />
                </div>
                <flux:input wire:model="stock" label="Stok Awal" type="number" :error="$errors->first('stock')" />
            </div>

            <div class="flex justify-end gap-3">
                <flux:button wire:click="$set('showModal', false)" variant="ghost">Batal</flux:button>
                <flux:button wire:click="save" variant="primary">{{ $editMode ? 'Perbarui' : 'Simpan' }}</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Modal: Restock Material --}}
    <flux:modal wire:model="showRestockModal" class="max-w-lg">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Restock Material</flux:heading>
                <flux:text class="mt-1">Tambah stok untuk <strong>{{ $restockMaterialName }}</strong></flux:text>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="restockQuantity" label="Jumlah ({{ $restockMaterialUnit }})" type="number" min="1" :error="$errors->first('restockQuantity')" />

                <div x-data="{
                    display: '',
                    init() {
                        this.display = this.format($wire.restockUnitPrice);
                        this.$watch('display', val => {
                            let digits = val.replace(/\D/g, '');
                            if (digits === '') {
                                $wire.restockUnitPrice = null;
                                this.display = '';
                                return;
                            }
                            let num = parseInt(digits, 10);
                            let formatted = this.format(num);
                            if (this.display !== formatted) {
                                this.display = formatted;
                            }
                            $wire.restockUnitPrice = num;
                        });
                        $wire.$watch('restockUnitPrice', val => {
                            if (document.activeElement !== this.$refs.restockPriceInput) {
                                this.display = this.format(val);
                            }
                        });
                    },
                    format(num) {
                        if (num === null || num === undefined || num === '') return '';
                        return 'Rp' + num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                    }
                }">
                    <flux:input x-ref="restockPriceInput" x-model="display" label="Harga Satuan" type="text" :error="$errors->first('restockUnitPrice')" />
                </div>
            </div>

            <div>
                <flux:input wire:model="restockSupplierName" label="Supplier" list="restock-suppliers-list" placeholder="Pilih atau ketik nama baru..." :error="$errors->first('restockSupplierName')" />
                <datalist id="restock-suppliers-list">
                    @foreach ($suppliers as $sup)
                        <option value="{{ $sup->name }}"></option>
                    @endforeach
                </datalist>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="restockDate" label="Tanggal" type="date" :error="$errors->first('restockDate')" />
                <flux:input wire:model="restockNotes" label="Catatan (opsional)" placeholder="No. nota, keterangan..." :error="$errors->first('restockNotes')" />
            </div>

            {{-- Live total cost preview --}}
            <div class="rounded-lg border border-emerald-200 dark:border-emerald-700 bg-emerald-50 dark:bg-emerald-900/30 p-4"
                x-data="{
                    get totalCost() {
                        let qty = parseInt($wire.restockQuantity) || 0;
                        let price = parseFloat($wire.restockUnitPrice) || 0;
                        return qty * price;
                    },
                    formatRp(num) {
                        return 'Rp' + num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                    }
                }">
                <div class="flex items-center justify-between">
                    <flux:text class="text-sm font-semibold text-emerald-700 dark:text-emerald-300">Total Biaya Restock</flux:text>
                    <span class="text-lg font-bold text-emerald-700 dark:text-emerald-300 font-mono" x-text="formatRp(totalCost)"></span>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <flux:button wire:click="$set('showRestockModal', false)" variant="ghost">Batal</flux:button>
                <flux:button wire:click="saveRestock" variant="primary" class="bg-emerald-600 hover:bg-emerald-700">Simpan Restock</flux:button>
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
                <flux:button wire:click="executeConfirmedAction" variant="danger">Ya, Hapus</flux:button>
            </div>
        </div>
    </flux:modal>
</div>

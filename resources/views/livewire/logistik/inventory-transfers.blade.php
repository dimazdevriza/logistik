<div>
    <div class="container-fluid p-0">
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-body-tertiary">
            <div class="card-body p-4 p-md-5">
                <h1 class="display-5 fw-black text-body mb-2 font-outfit">Transfer <span class="text-success">Gudang</span></h1>
                <p class="text-secondary mb-0">Pindahkan stok antar gudang. Riwayat material dan alat tersedia di catatan masing-masing.</p>
            </div>
        </div>

        @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif

        <div class="card border-0 shadow-sm rounded-4 p-4 bg-body-tertiary mb-4">
            <div class="d-flex align-items-center justify-content-between mb-3"><div><h2 class="h5 fw-bold mb-1">Catat transfer</h2><p class="small text-secondary mb-0">Material dan unit alat tersedia dapat dipindahkan sebagian dengan jejak gudang asal dan tujuan.</p></div></div>
            <div class="row g-3">
                <div class="col-md-3"><label for="transfer-inventory-type" class="form-label fw-semibold">Jenis inventaris</label><select id="transfer-inventory-type" wire:model.live="inventory_type" class="form-select"><option value="material">Material</option><option value="tool">Alat</option></select></div>
                <div class="col-md-3"><label for="transfer-source-warehouse" class="form-label fw-semibold">Gudang asal</label><select id="transfer-source-warehouse" wire:model.live="source_warehouse_id" class="form-select"><option value="">Pilih gudang asal</option>@foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>@endforeach</select></div>
                <div class="col-md-3"><label for="transfer-destination-warehouse" class="form-label fw-semibold">Gudang tujuan</label><select id="transfer-destination-warehouse" wire:model="destination_warehouse_id" class="form-select"><option value="">Pilih gudang tujuan</option>@foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>@endforeach</select></div>
                <div class="col-md-3"><label for="transfer-item" class="form-label fw-semibold">{{ $inventory_type === 'material' ? 'Material / lot harga' : 'Alat' }}</label><select id="transfer-item" wire:model.live="item_id" class="form-select"><option value="">Pilih item</option>@foreach($items as $item)<option value="{{ $item->id }}">{{ $item->name }} · {{ $inventory_type === 'material' ? 'Rp '.number_format((float) $item->unit_price, 0, ',', '.').' · '.$item->stock.' '.$item->unit : $item->code.' ('.($item->warehouseBalances->first()?->available_qty ?? 0).'/'.$item->total_qty.')' }}</option>@endforeach</select>@if($inventory_type === 'material')<div class="form-text">Pilih lot harga, lalu batch masuk yang akan dipindahkan.</div>@endif</div>
                @if($inventory_type === 'material')
                    <div class="col-md-6"><label for="transfer-stock-in" class="form-label fw-semibold">Batch sumber</label><select id="transfer-stock-in" wire:model="stock_in_id" class="form-select"><option value="">Pilih batch sumber</option>@foreach($batches as $batch)<option value="{{ $batch->id }}">{{ $batch->entry_code }} · tersedia {{ rtrim(rtrim(number_format($batch->available_quantity, 2, ',', '.'), '0'), ',') }} · Rp {{ number_format((float) $batch->unit_price, 0, ',', '.') }} · {{ $batch->received_at?->format('d/m/Y H:i') ?? ($batch->entry_type === 'transfer' ? 'transfer '.($batch->date?->format('d/m/Y') ?? '') : 'waktu tiba tidak tercatat') }}</option>@endforeach</select>@error('stock_in_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror</div>
                @endif
                <div class="col-md-3"><label for="transfer-quantity" class="form-label fw-semibold">Jumlah</label><input id="transfer-quantity" type="number" min="0.01" step="0.01" wire:model="quantity" class="form-control font-mono" /></div>
                <div class="col-md-3"><label for="transfer-date" class="form-label fw-semibold">Tanggal transfer</label><input id="transfer-date" type="date" wire:model="transferred_at" class="form-control" /></div>
                <div class="col-md-6"><label for="transfer-notes" class="form-label fw-semibold">Catatan</label><input id="transfer-notes" type="text" wire:model="notes" class="form-control" placeholder="Contoh: pindah stok ke lokasi proyek barat" /></div>
            </div>
            @if($errors->any()) <div class="alert alert-danger mt-3 mb-0"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            <div class="d-flex justify-content-end mt-3"><button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save" class="btn btn-success fw-semibold"><span wire:loading.remove wire:target="save">Simpan transfer</span><span wire:loading wire:target="save">Menyimpan transfer…</span></button></div>
        </div>

    </div>
</div>

<section class="spreadsheet-workspace" x-show="activeTab === 'spreadsheet'" x-cloak aria-labelledby="spreadsheet-title">
            <div class="spreadsheet-heading">
                <div>
                    <h2 id="spreadsheet-title" class="h5 fw-bold mb-1">Alokasi per rumah / pekerjaan umum</h2>
                    <p class="small text-secondary mb-0">Susun material, alat, dan pengembalian untuk setiap rumah atau pekerjaan umum.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" @click="housePickerOpen = true" wire:click="openHousePicker('house')" wire:loading.attr="disabled" wire:target="activeTab" class="btn transaction-house-button flex-shrink-0" aria-haspopup="dialog" aria-controls="house-dialog">
                        {{ $houses->where('category', \App\Models\House::CATEGORY_HOUSE)->whereIn('id', $house_ids)->isNotEmpty() ? 'Ubah rumah' : 'Pilih rumah' }}
                    </button>
                    <button type="button" @click="housePickerOpen = true" wire:click="openHousePicker('public_work')" wire:loading.attr="disabled" wire:target="activeTab" class="btn transaction-house-button flex-shrink-0" aria-haspopup="dialog" aria-controls="house-dialog">
                        {{ $houses->where('category', \App\Models\House::CATEGORY_PUBLIC_WORK)->whereIn('id', $house_ids)->isNotEmpty() ? 'Ubah pekerjaan umum' : 'Pilih pekerjaan umum' }}
                    </button>
                </div>
            </div>

            @php
                $spreadsheetMaterialOptions = $materials->map(fn ($material) => [
                    'id' => (string) $material->id,
                    'label' => $material->name,
                    'meta' => ($material->code ?: 'MAT-'.$material->id).' · '.$material->unit.' · stok '.number_format((float) $material->stock, 2, ',', '.'),
                ])->values()->all();
                $spreadsheetToolOptions = $tools->map(fn ($tool) => [
                    'id' => (string) $tool->id,
                    'label' => $tool->name,
                    'meta' => $tool->code.' · '.$tool->available_qty.' unit tersedia',
                ])->values()->all();
                $spreadsheetWarehouseOptions = $warehouses->map(fn ($warehouse) => [
                    'id' => (string) $warehouse->id,
                    'label' => $warehouse->name,
                    'meta' => '',
                ])->values()->all();
                $spreadsheetBatchUsage = collect($spreadsheetRows)
                    ->flatMap(fn ($row) => collect($row['materials'] ?? []))
                    ->filter(fn ($line) => ! empty($line['batch_id']))
                    ->groupBy(fn ($line) => (int) $line['batch_id'])
                    ->map(fn ($lines) => $lines->sum(fn ($line) => (float) ($line['quantity'] ?? 0)));
            @endphp

            <div class="table-responsive spreadsheet-table-scroll" x-data="{ pickerOpen: false }" :class="{ 'has-open-picker': pickerOpen }" @spreadsheet-picker-open="pickerOpen = $event.detail" tabindex="0" role="region" aria-label="Tabel alokasi per rumah dan pekerjaan umum">
                <table class="table table-hover align-middle mb-0 data-table">
                    <thead>
                        <tr>
                            <th scope="col">Rumah / Pekerjaan umum</th>
                            <th scope="col">Material</th>
                            <th scope="col">Alat</th>
                            <th scope="col">Pengembalian alat</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($spreadsheetRows as $rowIndex => $row)
                            @php
                                $houseId = (int) ($row['house_id'] ?? 0);
                                $materialLines = ! empty($row['materials']) ? $row['materials'] : [['material_id' => '', 'batch_id' => '', 'quantity' => 1, 'notes' => '']];
                                $toolLines = ! empty($row['tools']) ? $row['tools'] : [['tool_id' => '', 'warehouse_id' => '', 'quantity' => 1, 'notes' => '']];
                                $returnLines = ! empty($row['returns']) ? $row['returns'] : [['usage_id' => '', 'qty_normal' => 0, 'qty_broken' => 0, 'qty_lost' => 0, 'receiving_warehouse_id' => '', 'notes' => '']];
                                $houseUsages = $spreadsheetUsages->get($houseId, collect());
                                $pricedMaterialLines = collect($row['materials'] ?? [])->filter(fn ($line) => ! empty($line['batch_id']));
                                $houseMaterialTotal = $pricedMaterialLines->sum(function ($line) use ($spreadsheetBatches) {
                                    $batches = $spreadsheetBatches->get((int) ($line['material_id'] ?? 0), collect());
                                    $batch = $batches->firstWhere('id', (int) $line['batch_id']);

                                    return (float) ($line['quantity'] ?? 0) * (float) ($batch?->unit_price ?? 0);
                                });
                                $selectedHouseIdsElsewhere = collect($spreadsheetRows)
                                    ->reject(fn ($candidateRow, $candidateIndex) => (int) $candidateIndex === (int) $rowIndex)
                                    ->pluck('house_id')
                                    ->filter()
                                    ->map(fn ($selectedHouseId) => (string) $selectedHouseId)
                                    ->all();
                                $spreadsheetHouseOptions = $houses
                                    ->reject(fn ($house) => in_array((string) $house->id, $selectedHouseIdsElsewhere, true))
                                    ->map(fn ($house) => [
                                        'id' => (string) $house->id,
                                        'label' => $house->name,
                                        'meta' => $house->isPublicWork() ? $house->house_code.' · Pekerjaan Umum' : $house->house_code,
                                    ])->values()->all();
                                $materialCount = collect($row['materials'] ?? [])->filter(fn ($l) => ! empty($l['material_id']))->count();
                                $toolCount = collect($row['tools'] ?? [])->filter(fn ($l) => ! empty($l['tool_id']))->count();
                                $returnCount = collect($row['returns'] ?? [])->filter(fn ($l) => ! empty($l['usage_id']))->count();
                            @endphp
                            <tr wire:key="spreadsheet-house-row-{{ $rowIndex }}-{{ $houseId }}" class="spreadsheet-house-row">
                                <th scope="row" class="spreadsheet-house-cell align-top">
                                    @include('livewire.logistik.partials.spreadsheet-picker', [
                                        'pickerId' => 'spreadsheet-house-'.$rowIndex,
                                        'pickerItems' => $spreadsheetHouseOptions,
                                        'pickerValue' => $row['house_id'] ?? '',
                                        'pickerPlaceholder' => 'Pilih rumah / pekerjaan umum',
                                        'pickerEmptyMessage' => 'Tidak ada rumah atau pekerjaan umum tersedia yang cocok.',
                                        'pickerError' => "spreadsheetRows.$rowIndex.house_id",
                                        'pickerTarget' => null,
                                        'pickerModel' => "spreadsheetRows.{$rowIndex}.house_id",
                                    ])
                                    @if ($houseId && ($materialCount > 0 || $toolCount > 0 || $returnCount > 0))
                                        <div class="spreadsheet-house-meta-tags mt-2 d-flex flex-wrap gap-1">
                                            @if ($materialCount > 0)
                                                <span class="spreadsheet-tag">{{ $materialCount }} material</span>
                                            @endif
                                            @if ($toolCount > 0)
                                                <span class="spreadsheet-tag">{{ $toolCount }} alat</span>
                                            @endif
                                            @if ($returnCount > 0)
                                                <span class="spreadsheet-tag">{{ $returnCount }} kembali</span>
                                            @endif
                                        </div>
                                    @endif
                                </th>
                                @if (empty($row['house_id']))
                                    <td colspan="3" class="spreadsheet-empty-cell align-middle">
                                        <div class="spreadsheet-empty-pill">
                                            <svg width="15" height="15" viewBox="0 0 16 16" fill="currentColor" class="opacity-50 flex-shrink-0" aria-hidden="true">
                                                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                                                <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0"/>
                                            </svg>
                                            <span>Pilih rumah atau pekerjaan umum pada kolom kiri untuk mulai mengisi alokasi.</span>
                                        </div>
                                    </td>
                                @else
                                    <td class="spreadsheet-action-cell spreadsheet-material-column align-top">
                                        @foreach ($materialLines as $lineIndex => $line)
                                            @php
                                                $selectedMaterial = $materials->firstWhere('id', (int) ($line['material_id'] ?? 0));
                                                $availableBatches = $spreadsheetBatches->get((int) ($line['material_id'] ?? 0), collect());
                                                $selectedBatchId = (int) ($line['batch_id'] ?? 0);
                                                $batchOptions = $availableBatches->map(function ($batch) use ($line, $selectedBatchId, $spreadsheetBatchUsage) {
                                                    $ownQuantity = $selectedBatchId === (int) $batch->id ? (float) ($line['quantity'] ?? 0) : 0;
                                                    $reservedElsewhere = max(0, (float) $spreadsheetBatchUsage->get((int) $batch->id, 0) - $ownQuantity);
                                                    $availableQuantity = max(0, round((float) $batch->available_quantity - $reservedElsewhere, 2));

                                                    return [
                                                        'id' => (string) $batch->id,
                                                        'label' => 'Sisa '.number_format($availableQuantity, 2, ',', '.').' · Rp '.number_format((float) $batch->unit_price, 0, ',', '.'),
                                                        'meta' => ($batch->warehouse?->name ?? 'Gudang tidak diketahui').' · '.$batch->entry_code,
                                                        'available_quantity' => $availableQuantity,
                                                    ];
                                                })->filter(fn ($option) => $option['available_quantity'] >= 0.01 || (int) $option['id'] === $selectedBatchId)->values()->all();
                                                $selectedBatch = $availableBatches->firstWhere('id', $selectedBatchId);
                                                $selectedBatchAvailableQuantity = (float) (collect($batchOptions)->firstWhere('id', (string) $selectedBatchId)['available_quantity'] ?? 0);
                                            @endphp
                                            <div class="spreadsheet-entry" wire:key="spreadsheet-material-{{ $rowIndex }}-{{ $lineIndex }}-entry">
                                                @include('livewire.logistik.partials.spreadsheet-picker', [
                                                    'pickerId' => 'spreadsheet-material-'.$rowIndex.'-'.$lineIndex,
                                                    'pickerItems' => $spreadsheetMaterialOptions,
                                                    'pickerValue' => $line['material_id'] ?? '',
                                                    'pickerPlaceholder' => 'Pilih material',
                                                    'pickerEmptyMessage' => 'Tidak ada material dengan stok tersedia.',
                                                    'pickerError' => "spreadsheetRows.$rowIndex.materials.$lineIndex.material_id",
                                                    'pickerTarget' => ['row' => $rowIndex, 'type' => 'materials', 'line' => $lineIndex],
                                                    'pickerModel' => null,
                                                ])
                                                @if ($selectedMaterial)
                                                    <div class="spreadsheet-entry-fields">
                                                        @include('livewire.logistik.partials.spreadsheet-picker', [
                                                            'pickerId' => 'spreadsheet-batch-'.$rowIndex.'-'.$lineIndex.'-'.($line['material_id'] ?? 'new'),
                                                            'pickerItems' => $batchOptions,
                                                            'pickerValue' => $line['batch_id'] ?? '',
                                                            'pickerPlaceholder' => 'Pilih batch',
                                                            'pickerEmptyMessage' => 'Batch ini tidak memiliki sisa stok.',
                                                            'pickerError' => "spreadsheetRows.$rowIndex.materials.$lineIndex.batch_id",
                                                            'pickerTwoLine' => true,
                                                            'pickerTarget' => null,
                                                            'pickerModel' => "spreadsheetRows.{$rowIndex}.materials.{$lineIndex}.batch_id",
                                                        ])
                                                        @if ($selectedBatch)
                                                            <div class="spreadsheet-material-cost" aria-live="polite">
                                                                <div class="spreadsheet-material-cost-line">
                                                                    <span>Harga</span>
                                                                    <strong><span class="font-mono">Rp {{ number_format((float) $selectedBatch->unit_price, 0, ',', '.') }}</span> <small>/ {{ $selectedMaterial->unit }}</small></strong>
                                                                </div>
                                                                <div class="spreadsheet-material-cost-line">
                                                                    <span>Subtotal</span>
                                                                    <strong class="font-mono">Rp {{ number_format((float) $selectedBatch->unit_price * (float) ($line['quantity'] ?? 0), 0, ',', '.') }}</strong>
                                                                </div>
                                                            </div>
                                                        @endif
                                                        <label class="visually-hidden" for="spreadsheet-material-quantity-{{ $rowIndex }}-{{ $lineIndex }}">Jumlah material</label>
                                                        <div class="input-group input-group-sm spreadsheet-quantity-group">
                                                            <span class="input-group-text spreadsheet-input-addon">Jumlah</span>
                                                            <input id="spreadsheet-material-quantity-{{ $rowIndex }}-{{ $lineIndex }}" type="number" min="0.01" max="{{ $selectedBatchId > 0 ? number_format($selectedBatchAvailableQuantity, 2, '.', '') : '' }}" step="0.01" x-on:input="$el.max !== '' && $el.value !== '' && Number($el.value) > Number($el.max) && ($el.value = $el.max)" @disabled($selectedBatchId > 0 && $selectedBatchAvailableQuantity < 0.01) wire:model.live="spreadsheetRows.{{ $rowIndex }}.materials.{{ $lineIndex }}.quantity" class="form-control form-control-sm font-mono fw-semibold text-end" aria-label="Jumlah {{ $selectedMaterial->unit }}" placeholder="0.00">
                                                            <span class="input-group-text spreadsheet-input-addon font-mono">{{ $selectedMaterial->unit }}</span>
                                                        </div>
                                                        @if ($selectedBatchId > 0 && $selectedBatchAvailableQuantity < 0.01)
                                                            <span class="text-danger small" role="alert">Sisa batch sudah dipakai di baris lain. Ubah batch atau hapus alokasi lain.</span>
                                                        @endif
                                                        @error("spreadsheetRows.$rowIndex.materials.$lineIndex.quantity") <span class="text-danger small" role="alert">{{ $message }}</span> @enderror
                                                        @include('livewire.logistik.partials.spreadsheet-purpose-picker', [
                                                            'purposeId' => 'spreadsheet-material-notes-'.$rowIndex.'-'.$lineIndex,
                                                            'purposeValue' => $line['notes'] ?? '',
                                                            'purposeModel' => "spreadsheetRows.{$rowIndex}.materials.{$lineIndex}.notes",
                                                            'purposeLabel' => 'Peruntukan material',
                                                            'purposePlaceholder' => 'Pilih / ketik peruntukan',
                                                        ])
                                                        @error("spreadsheetRows.$rowIndex.materials.$lineIndex.notes") <span class="text-danger small" role="alert">{{ $message }}</span> @enderror
                                                        @include('livewire.logistik.partials.spreadsheet-purpose-picker', [
                                                            'purposeId' => 'spreadsheet-material-taker-'.$rowIndex.'-'.$lineIndex,
                                                            'purposeValue' => $line['taken_by'] ?? '',
                                                            'purposeModel' => "spreadsheetRows.{$rowIndex}.materials.{$lineIndex}.taken_by",
                                                            'purposeLabel' => 'Pengambil',
                                                            'noteOptions' => $pengambilOptions,
                                                            'purposePlaceholder' => 'Pilih atau ketik pengambil',
                                                            'purposeToggleLabel' => 'Tampilkan pilihan pengambil',
                                                            'purposeOptionsLabel' => 'Pilihan pengambil',
                                                            'purposeNewOptionMessage' => 'Gunakan sebagai pengambil baru.',
                                                            'purposeEmptyMessage' => 'Belum ada riwayat nama pengambil. Ketik nama di kolom ini untuk mengisi.',
                                                            'purposeMaxlength' => 120,
                                                        ])
                                                        @error("spreadsheetRows.$rowIndex.materials.$lineIndex.taken_by") <span class="text-danger small" role="alert">{{ $message }}</span> @enderror
                                                        <div class="d-flex justify-content-end pt-1">
                                                            <button type="button" wire:click="removeSpreadsheetLine({{ $rowIndex }}, 'materials', {{ $lineIndex }})" class="spreadsheet-remove-line">Hapus material</button>
                                                        </div>
                                                    </div>
                                                @elseif (! empty($line['material_id']))
                                                    <span class="text-danger small" role="alert">Material sudah tidak tersedia. Pilih material lain.</span>
                                                @endif
                                            </div>
                                        @endforeach
                                        @if ($pricedMaterialLines->isNotEmpty())
                                            <div class="spreadsheet-entry-actions">
                                                <div class="spreadsheet-house-material-total">
                                                    <span>Total material</span>
                                                    <strong class="font-mono">Rp {{ number_format((float) $houseMaterialTotal, 0, ',', '.') }}</strong>
                                                </div>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="spreadsheet-action-cell spreadsheet-tool-column align-top">
                                        @foreach ($toolLines as $lineIndex => $line)
                                            @php
                                                $selectedTool = $tools->firstWhere('id', (int) ($line['tool_id'] ?? 0));
                                            @endphp
                                            <div class="spreadsheet-entry" wire:key="spreadsheet-tool-{{ $rowIndex }}-{{ $lineIndex }}-entry">
                                                @include('livewire.logistik.partials.spreadsheet-picker', [
                                                    'pickerId' => 'spreadsheet-tool-'.$rowIndex.'-'.$lineIndex,
                                                    'pickerItems' => $spreadsheetToolOptions,
                                                    'pickerValue' => $line['tool_id'] ?? '',
                                                    'pickerPlaceholder' => 'Pilih alat',
                                                    'pickerEmptyMessage' => 'Tidak ada alat dengan stok tersedia.',
                                                    'pickerError' => "spreadsheetRows.$rowIndex.tools.$lineIndex.tool_id",
                                                    'pickerTarget' => ['row' => $rowIndex, 'type' => 'tools', 'line' => $lineIndex],
                                                    'pickerModel' => null,
                                                ])
                                                @if ($selectedTool)
                                                    @php
                                                        $toolWarehouseOptionsForLine = $selectedTool->warehouseBalances->map(fn ($balance) => ['id' => (string) $balance->warehouse_id, 'label' => $balance->warehouse?->name ?? 'Gudang tidak tersedia', 'meta' => $balance->allocation_available_qty.' unit tersedia'])->values()->all();
                                                    @endphp
                                                    <div class="spreadsheet-entry-fields">
                                                        @include('livewire.logistik.partials.spreadsheet-picker', [
                                                            'pickerId' => 'spreadsheet-tool-warehouse-'.$rowIndex.'-'.$lineIndex.'-'.($line['tool_id'] ?? 'new'),
                                                            'pickerItems' => $toolWarehouseOptionsForLine,
                                                            'pickerValue' => $line['warehouse_id'] ?? '',
                                                            'pickerPlaceholder' => 'Pilih gudang asal',
                                                            'pickerEmptyMessage' => 'Tidak ada gudang dengan alat tersedia.',
                                                            'pickerError' => "spreadsheetRows.$rowIndex.tools.$lineIndex.warehouse_id",
                                                            'pickerTwoLine' => true,
                                                            'pickerTarget' => null,
                                                            'pickerModel' => "spreadsheetRows.{$rowIndex}.tools.{$lineIndex}.warehouse_id",
                                                        ])
                                                        <label class="visually-hidden" for="spreadsheet-tool-quantity-{{ $rowIndex }}-{{ $lineIndex }}">Jumlah alat</label>
                                                        <div class="input-group input-group-sm spreadsheet-quantity-group">
                                                            <span class="input-group-text spreadsheet-input-addon">Jumlah</span>
                                                            <input id="spreadsheet-tool-quantity-{{ $rowIndex }}-{{ $lineIndex }}" type="number" min="1" max="{{ $selectedTool->available_qty }}" step="1" wire:model.live="spreadsheetRows.{{ $rowIndex }}.tools.{{ $lineIndex }}.quantity" class="form-control form-control-sm font-mono fw-semibold text-end" aria-label="Jumlah alat" placeholder="1">
                                                            <span class="input-group-text spreadsheet-input-addon font-mono">unit</span>
                                                        </div>
                                                        @error("spreadsheetRows.$rowIndex.tools.$lineIndex.quantity") <span class="text-danger small" role="alert">{{ $message }}</span> @enderror
                                                        @include('livewire.logistik.partials.spreadsheet-purpose-picker', [
                                                            'purposeId' => 'spreadsheet-tool-notes-'.$rowIndex.'-'.$lineIndex,
                                                            'purposeValue' => $line['notes'] ?? '',
                                                            'purposeModel' => "spreadsheetRows.{$rowIndex}.tools.{$lineIndex}.notes",
                                                            'purposeLabel' => 'Peruntukan alat',
                                                            'purposePlaceholder' => 'Pilih / ketik peruntukan',
                                                        ])
                                                        @error("spreadsheetRows.$rowIndex.tools.$lineIndex.notes") <span class="text-danger small" role="alert">{{ $message }}</span> @enderror
                                                        <div class="d-flex justify-content-end pt-1">
                                                            <button type="button" wire:click="removeSpreadsheetLine({{ $rowIndex }}, 'tools', {{ $lineIndex }})" class="spreadsheet-remove-line">Hapus alat</button>
                                                        </div>
                                                    </div>
                                                @elseif (! empty($line['tool_id']))
                                                    <span class="text-danger small" role="alert">Alat sudah tidak tersedia. Pilih alat lain.</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </td>
                                    <td class="spreadsheet-action-cell spreadsheet-return-column align-top">
                                        @foreach ($returnLines as $lineIndex => $line)
                                            @php
                                                $selectedUsage = $houseUsages->firstWhere('id', (int) ($line['usage_id'] ?? 0));
                                                $selectedReturnUsageIds = collect($returnLines)
                                                    ->reject(fn ($returnLine, $returnIndex) => (int) $returnIndex === $lineIndex)
                                                    ->pluck('usage_id')
                                                    ->filter()
                                                    ->map(fn ($usageId) => (int) $usageId)
                                                    ->all();
                                                $selectedReturnToolIds = $houseUsages
                                                    ->whereIn('id', $selectedReturnUsageIds)
                                                    ->pluck('tool_id')
                                                    ->map(fn ($toolId) => (int) $toolId)
                                                    ->all();
                                                $returnOptions = $houseUsages
                                                    ->reject(fn ($usage) => in_array((int) $usage->tool_id, $selectedReturnToolIds, true))
                                                    ->map(fn ($usage) => [
                                                        'id' => (string) $usage->id,
                                                        'label' => $usage->tool->name,
                                                        'meta' => $usage->quantity.' unit terpinjam · '.$usage->tool->code,
                                                    ])->values()->all();
                                                $returnEmptyMessage = $houseUsages->isNotEmpty()
                                                    ? 'Semua peminjaman aktif sudah dipilih.'
                                                    : 'Tidak ada peminjaman aktif di unit ini.';
                                            @endphp
                                            <div class="spreadsheet-entry" wire:key="spreadsheet-return-{{ $rowIndex }}-{{ $lineIndex }}">
                                                @include('livewire.logistik.partials.spreadsheet-picker', [
                                                    'pickerId' => 'spreadsheet-return-'.$rowIndex.'-'.$houseId.'-'.$lineIndex,
                                                    'pickerItems' => $returnOptions,
                                                    'pickerValue' => $line['usage_id'] ?? '',
                                                    'pickerPlaceholder' => 'Pilih alat kembali',
                                                    'pickerEmptyMessage' => $returnEmptyMessage,
                                                    'pickerError' => "spreadsheetRows.$rowIndex.returns.$lineIndex.usage_id",
                                                    'pickerTwoLine' => true,
                                                    'pickerTarget' => ['row' => $rowIndex, 'type' => 'returns', 'line' => $lineIndex],
                                                    'pickerModel' => null,
                                                ])
                                                @if ($selectedUsage)
                                                    <div class="spreadsheet-entry-fields">
                                                        <div class="spreadsheet-return-quantity">
                                                            <div class="spreadsheet-return-pill is-good">
                                                                <label class="spreadsheet-field-label" for="spreadsheet-return-normal-{{ $rowIndex }}-{{ $lineIndex }}">Baik</label>
                                                                <input id="spreadsheet-return-normal-{{ $rowIndex }}-{{ $lineIndex }}" type="number" min="0" max="{{ $selectedUsage->quantity }}" step="1" wire:model.live="spreadsheetRows.{{ $rowIndex }}.returns.{{ $lineIndex }}.qty_normal" class="form-control form-control-sm font-mono text-end" placeholder="0">
                                                            </div>
                                                            <div class="spreadsheet-return-pill is-broken">
                                                                <label class="spreadsheet-field-label" for="spreadsheet-return-broken-{{ $rowIndex }}-{{ $lineIndex }}">Rusak</label>
                                                                <input id="spreadsheet-return-broken-{{ $rowIndex }}-{{ $lineIndex }}" type="number" min="0" max="{{ $selectedUsage->quantity }}" step="1" wire:model.live="spreadsheetRows.{{ $rowIndex }}.returns.{{ $lineIndex }}.qty_broken" class="form-control form-control-sm font-mono text-end" placeholder="0">
                                                            </div>
                                                            <div class="spreadsheet-return-pill is-lost">
                                                                <label class="spreadsheet-field-label" for="spreadsheet-return-lost-{{ $rowIndex }}-{{ $lineIndex }}">Hilang</label>
                                                                <input id="spreadsheet-return-lost-{{ $rowIndex }}-{{ $lineIndex }}" type="number" min="0" max="{{ $selectedUsage->quantity }}" step="1" wire:model.live="spreadsheetRows.{{ $rowIndex }}.returns.{{ $lineIndex }}.qty_lost" class="form-control form-control-sm font-mono text-end" placeholder="0">
                                                            </div>
                                                        </div>
                                                        @foreach (['qty_normal', 'qty_broken', 'qty_lost'] as $returnQuantityField)
                                                            @error("spreadsheetRows.$rowIndex.returns.$lineIndex.$returnQuantityField") <span class="text-danger small d-block mt-1" role="alert">{{ $message }}</span> @enderror
                                                        @endforeach
                                                        @include('livewire.logistik.partials.spreadsheet-picker', [
                                                            'pickerId' => 'spreadsheet-return-warehouse-'.$rowIndex.'-'.$lineIndex.'-'.($line['usage_id'] ?? 'new'),
                                                            'pickerItems' => $spreadsheetWarehouseOptions,
                                                            'pickerValue' => $line['receiving_warehouse_id'] ?? '',
                                                            'pickerPlaceholder' => 'Gudang penerima',
                                                            'pickerEmptyMessage' => 'Belum ada gudang.',
                                                            'pickerError' => "spreadsheetRows.$rowIndex.returns.$lineIndex.receiving_warehouse_id",
                                                            'pickerTarget' => null,
                                                            'pickerModel' => "spreadsheetRows.{$rowIndex}.returns.{$lineIndex}.receiving_warehouse_id",
                                                        ])
                                                        <label class="visually-hidden" for="spreadsheet-return-notes-{{ $rowIndex }}-{{ $lineIndex }}">Catatan pengembalian</label>
                                                        <input id="spreadsheet-return-notes-{{ $rowIndex }}-{{ $lineIndex }}" type="text" maxlength="500" wire:model.live="spreadsheetRows.{{ $rowIndex }}.returns.{{ $lineIndex }}.notes" class="form-control form-control-sm" placeholder="Catatan kondisi alat">
                                                        <div class="d-flex justify-content-end pt-1">
                                                            <button type="button" wire:click="removeSpreadsheetLine({{ $rowIndex }}, 'returns', {{ $lineIndex }})" class="spreadsheet-remove-line">Hapus pengembalian</button>
                                                        </div>
                                                    </div>
                                                @endif
                                                @if (! empty($line['usage_id']) && ! $selectedUsage)
                                                    <span class="text-danger small" role="alert">Peminjaman sudah berubah. Pilih alat yang masih dipinjam.</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="spreadsheet-submit-bar">
                <div class="spreadsheet-submit-actions">
                    @error('spreadsheetRows') <span class="text-danger small" role="alert">{{ $message }}</span> @enderror
                    <button type="button" class="btn transaction-house-button" wire:click="reviewSpreadsheet" wire:loading.attr="disabled" wire:target="reviewSpreadsheet">
                        <span wire:loading.remove wire:target="reviewSpreadsheet">Pratinjau alokasi</span>
                        <span wire:loading wire:target="reviewSpreadsheet">Memeriksa alokasi…</span>
                    </button>
                </div>
            </div>
        </section>

        @if ($showSpreadsheetReview)
            @teleport('body')
                <dialog class="transaction-confirmation transaction-confirmation--return" x-data x-init="if ($el.showModal) $el.showModal(); else $el.setAttribute('open', '')" @cancel="$wire.set('showSpreadsheetReview', false)" aria-labelledby="spreadsheet-preview-title">
                    <header class="return-confirmation-header">
                        <div>
                            <h2 id="spreadsheet-preview-title" class="font-outfit fw-bold mb-1">Pratinjau alokasi</h2>
                            <p class="small text-secondary mb-0">Periksa semua item sebelum melanjutkan.</p>
                        </div>
                        <button type="button" class="btn-close flex-shrink-0" aria-label="Tutup pratinjau" wire:click="$set('showSpreadsheetReview', false)"></button>
                    </header>
                    @php
                        $reviewCategories = collect($spreadsheetReviewRows)->pluck('category')->filter();
                        $isAllReviewPublicWork = $reviewCategories->isNotEmpty() && $reviewCategories->every(fn ($c) => $c === \App\Models\House::CATEGORY_PUBLIC_WORK);
                        $isAllReviewHouse = $reviewCategories->isNotEmpty() && $reviewCategories->every(fn ($c) => $c === \App\Models\House::CATEGORY_HOUSE);
                        $spreadsheetReviewTargetLabel = $isAllReviewPublicWork ? 'pekerjaan umum' : ($isAllReviewHouse ? 'rumah' : 'target');
                    @endphp
                    <div class="return-confirmation-body">
                        <div class="small fw-semibold mb-3">{{ count($spreadsheetReviewRows) }} {{ $spreadsheetReviewTargetLabel }} · {{ $spreadsheetReviewTotals['materials'] ?? 0 }} material · {{ $spreadsheetReviewTotals['tools'] ?? 0 }} alat · {{ $spreadsheetReviewTotals['returns'] ?? 0 }} pengembalian</div>
                        @foreach ($spreadsheetReviewRows as $reviewRow)
                            <section class="return-confirmation-item">
                                <h3 class="small fw-bold mb-2">{{ $reviewRow['house'] }}</h3>
                                <ul class="small text-secondary mb-0 ps-3">
                                    @foreach ($reviewRow['details'] as $detail)
                                        <li class="mb-1">{{ $detail }}</li>
                                    @endforeach
                                </ul>
                                @if (($reviewRow['material_count'] ?? 0) > 0)
                                    <p class="spreadsheet-house-material-total mb-0"><span>Total material {{ ($reviewRow['category'] ?? '') === \App\Models\House::CATEGORY_PUBLIC_WORK ? 'pekerjaan umum' : 'rumah' }}</span><strong class="font-mono">Rp {{ number_format((float) $reviewRow['material_total'], 0, ',', '.') }}</strong></p>
                                @endif
                            </section>
                        @endforeach
                    </div>
                    <footer class="return-confirmation-footer">
                        <button type="button" class="btn btn-outline-secondary fw-semibold" wire:click="$set('showSpreadsheetReview', false)">Kembali ke tabel</button>
                        <button type="button" class="btn btn-success fw-semibold" wire:click="confirmSpreadsheetReview">Lanjut ke konfirmasi</button>
                    </footer>
                </dialog>
            @endteleport
        @endif

        @if ($showSpreadsheetConfirmation)
            @teleport('body')
                @php
                    $confirmCategories = collect($spreadsheetReviewRows)->pluck('category')->filter();
                    $isAllConfirmPublicWork = $confirmCategories->isNotEmpty() && $confirmCategories->every(fn ($c) => $c === \App\Models\House::CATEGORY_PUBLIC_WORK);
                    $isAllConfirmHouse = $confirmCategories->isNotEmpty() && $confirmCategories->every(fn ($c) => $c === \App\Models\House::CATEGORY_HOUSE);
                    $spreadsheetConfirmTargetLabel = $isAllConfirmPublicWork ? 'pekerjaan umum' : ($isAllConfirmHouse ? 'rumah' : 'target');
                @endphp
                <dialog class="transaction-confirmation transaction-confirmation--return" x-data x-init="if ($el.showModal) $el.showModal(); else $el.setAttribute('open', '')" @cancel="$wire.set('showSpreadsheetConfirmation', false)" aria-labelledby="spreadsheet-confirmation-title">
                    <header class="return-confirmation-header">
                        <div>
                            <h2 id="spreadsheet-confirmation-title" class="font-outfit fw-bold mb-1">Yakin ingin menyimpan alokasi ini?</h2>
                            <p class="small text-secondary mb-0">{{ count($spreadsheetReviewRows) }} {{ $spreadsheetConfirmTargetLabel }} akan menerima perubahan sesuai pratinjau.</p>
                        </div>
                        <button type="button" class="btn-close flex-shrink-0" aria-label="Tutup konfirmasi" wire:click="$set('showSpreadsheetConfirmation', false)"></button>
                    </header>
                    <footer class="return-confirmation-footer">
                        <button type="button" class="btn btn-outline-secondary fw-semibold" wire:click="$set('showSpreadsheetConfirmation', false)">Batal</button>
                        <button type="button" class="btn btn-success fw-semibold" wire:click="saveSpreadsheet" wire:loading.attr="disabled" wire:target="saveSpreadsheet">
                            <span wire:loading.remove wire:target="saveSpreadsheet">Ya, simpan alokasi</span>
                            <span wire:loading wire:target="saveSpreadsheet">Menyimpan alokasi…</span>
                        </button>
                    </footer>
                </dialog>
            @endteleport
        @endif

<?php

namespace App\Livewire\Logistik;

use App\Models\House;
use App\Models\Cluster;
use App\Models\ClusterExpense;
use App\Models\Material;
use App\Models\MaterialUsage as MaterialUsageModel;
use App\Models\StockIn;
use App\Models\Supplier;
use App\Models\Tool;
use App\Models\ToolReturnLog;
use App\Models\ToolUsage as ToolUsageModel;
use App\Models\Warehouse;
use App\Models\ToolWarehouseBalance;
use App\Support\ToolInventory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

class TransaksiLogistik extends Component
{
    use WithFileUploads;

    // Shared state
    public $house_ids = [];

    public bool $housePickerOpen = false;

    public string $houseSearch = '';

    public string $houseCluster = 'all';

    public bool $selectedHousesOnly = false;

    public int $housePage = 1;

    // Material section state
    public $material_id = '';

    public $material_batch_id = '';

    public array $materialBatchMap = [];

    public $material_quantity = 1;

    public $material_notes = '';

    public string $material_taken_by = '';

    public bool $showMaterialConfirmation = false;

    public array $materialConfirmationData = [];

    public bool $materialPickerOpen = false;

    public $materialAllocationProofImage = null;

    // Tool section state
    public $tool_id = '';

    public $tool_warehouse_id = '';

    public $tool_quantity = 1;

    public $tool_notes = '';

    public bool $showToolConfirmation = false;

    public array $toolConfirmationData = [];

    public bool $toolPickerOpen = false;

    public $toolAllocationProofImage = null;

    public string $rental_description = '';

    public string $rental_vendor = '';

    public string $rental_quantity = '1';

    public string $rental_start_date = '';

    public string $rental_due_date = '';

    public string $rental_amount = '';

    public string $rental_notes = '';

    public $rental_bill_image = null;

    public string $vendor_service_description = '';

    public string $vendor_service_target = 'cluster';

    public string $vendor_service_cluster_id = '';

    public bool $showVendorServiceConfirmation = false;

    public array $vendorServiceConfirmationData = [];

    public string $vendor_service_vendor = '';

    public string $vendor_service_date = '';

    public string $vendor_service_amount = '';

    public string $vendor_service_notes = '';

    public $vendor_service_bill_image = null;

    public int $noticeSequence = 0;

    public string $noticeMessage = '';

    public string $noticeHistoryType = '';

    public string $noticeType = 'success';

    // Save idempotency guard (C) — blocks re-entrant double-submit
    public bool $saving = false;

    // Return tab state
    public string $activeTab = 'spreadsheet';

    public array $returnSelections = []; // Keyed by tool_usage_id

    public array $spreadsheetRows = [['house_id' => '', 'materials' => [], 'tools' => [], 'returns' => []]];

    public bool $showSpreadsheetReview = false;

    public bool $showSpreadsheetConfirmation = false;

    public array $spreadsheetReviewRows = [];

    public array $spreadsheetReviewTotals = [];

    public bool $showReturnConfirmation = false;

    public array $returnConfirmationData = [];

    protected function materialRules(): array
    {
        return [
            'house_ids' => 'required|array|min:1',
            'house_ids.*' => 'integer|distinct|exists:houses,id',
            'material_id' => 'required|exists:materials,id',
            'material_batch_id' => 'required|integer|exists:stock_ins,id',
            'material_quantity' => 'required|numeric|min:0.01',
            'material_notes' => 'required|string|max:500',
            'material_taken_by' => 'nullable|string|max:120',
        ];
    }

    protected function toolRules(): array
    {
        return [
            'house_ids' => 'required|array|min:1',
            'house_ids.*' => 'integer|distinct|exists:houses,id',
            'tool_id' => 'required|exists:tools,id',
            'tool_warehouse_id' => 'nullable|integer|exists:warehouses,id',
            'tool_quantity' => 'required|integer|min:1',
            'tool_notes' => 'required|string|max:500',
        ];
    }

    public function mount(): void
    {
        $this->rental_start_date = now()->toDateString();
        $this->vendor_service_date = now()->toDateString();
        $this->vendor_service_cluster_id = auth()->user()->role === 'admin'
            ? ''
            : (string) (auth()->user()->cluster_id ?? '');
    }

    public function updatedVendorServiceTarget(string $target): void
    {
        $this->house_ids = [];
        $this->vendor_service_cluster_id = $target === 'cluster' && auth()->user()->role !== 'admin'
            ? (string) (auth()->user()->cluster_id ?? '')
            : '';
        $this->reset('houseSearch', 'houseCluster', 'selectedHousesOnly', 'housePage');
        $this->resetValidation();
    }

    public function addSpreadsheetHouseRow(): void
    {
        $this->syncSpreadsheetHouseRows();
    }

    private function emptySpreadsheetHouseRow(): array
    {
        return ['house_id' => '', 'materials' => [], 'tools' => [], 'returns' => []];
    }

    private function syncSpreadsheetHouseRows(): void
    {
        $existingRows = collect($this->spreadsheetRows)
            ->filter(fn ($row) => ! empty($row['house_id']))
            ->keyBy(fn ($row) => (int) $row['house_id']);
        $emptyRows = collect($this->spreadsheetRows)->filter(fn ($row) => empty($row['house_id']))->all();
        $rows = [];

        foreach (array_slice(array_unique(array_map('intval', $this->house_ids)), 0, 100) as $houseId) {
            if ($houseId <= 0) {
                continue;
            }

            $rows[] = $existingRows->pull($houseId) ?? array_merge($this->emptySpreadsheetHouseRow(), ['house_id' => (string) $houseId]);
        }

        $this->spreadsheetRows = array_slice($rows, 0, 100);
        if (count($this->spreadsheetRows) < 100) {
            $this->spreadsheetRows[] = $emptyRows[0] ?? $this->emptySpreadsheetHouseRow();
        }
        if ($this->spreadsheetRows === []) {
            $this->spreadsheetRows[] = $this->emptySpreadsheetHouseRow();
        }
    }

    private function addSpreadsheetLine(int $row, string $type): void
    {
        if (! isset($this->spreadsheetRows[$row]) || ! in_array($type, ['materials', 'tools', 'returns'], true)) {
            return;
        }

        $itemKey = ['materials' => 'material_id', 'tools' => 'tool_id', 'returns' => 'usage_id'][$type];
        $lines = $this->spreadsheetRows[$row][$type];
        $lastLine = $lines ? $lines[array_key_last($lines)] : null;
        if ($lastLine !== null && empty($lastLine[$itemKey])) {
            return;
        }

        $line = match ($type) {
            'materials' => ['material_id' => '', 'batch_id' => '', 'quantity' => 1, 'notes' => ''],
            'tools' => ['tool_id' => '', 'warehouse_id' => '', 'quantity' => 1, 'notes' => ''],
            'returns' => ['usage_id' => '', 'qty_normal' => 0, 'qty_broken' => 0, 'qty_lost' => 0, 'receiving_warehouse_id' => '', 'notes' => ''],
        };

        if (count($this->spreadsheetRows[$row][$type]) < 50) {
            $this->spreadsheetRows[$row][$type][] = $line;
        }
    }

    public function selectSpreadsheetItem(int $row, string $type, int $line, int $itemId): void
    {
        if (! isset($this->spreadsheetRows[$row]) || empty($this->spreadsheetRows[$row]['house_id']) || ! in_array($type, ['materials', 'tools', 'returns'], true) || $line < 0 || $line >= 50 || $itemId < 1) {
            return;
        }

        if (! isset($this->spreadsheetRows[$row][$type][$line])) {
            if (count($this->spreadsheetRows[$row][$type]) >= 50) {
                return;
            }
            $blankLine = match ($type) {
                'materials' => ['material_id' => '', 'batch_id' => '', 'quantity' => 1, 'notes' => ''],
                'tools' => ['tool_id' => '', 'warehouse_id' => '', 'quantity' => 1, 'notes' => ''],
                'returns' => ['usage_id' => '', 'qty_normal' => 0, 'qty_broken' => 0, 'qty_lost' => 0, 'receiving_warehouse_id' => '', 'notes' => ''],
            };
            $this->spreadsheetRows[$row][$type] = array_pad($this->spreadsheetRows[$row][$type], $line + 1, $blankLine);
        }

        if ($type === 'materials') {
            $available = Material::whereKey($itemId)->where('stock', '>', 0)
                ->whereHas('stockIns', fn ($query) => $query->whereNotNull('warehouse_id')->whereAvailableQuantity())
                ->exists();
            if (! $available) {
                return;
            }
            $this->spreadsheetRows[$row]['materials'][$line]['material_id'] = (string) $itemId;
            $this->spreadsheetRows[$row]['materials'][$line]['batch_id'] = '';

            if ($line === array_key_last($this->spreadsheetRows[$row]['materials'])) {
                $this->addSpreadsheetLine($row, 'materials');
            }

            return;
        }

        if ($type === 'tools') {
            $balance = $this->availableToolBalances($itemId)->first();
            if (! $balance) {
                return;
            }
            $this->spreadsheetRows[$row]['tools'][$line]['tool_id'] = (string) $itemId;
            $this->spreadsheetRows[$row]['tools'][$line]['warehouse_id'] = (string) $balance->warehouse_id;

            if ($line === array_key_last($this->spreadsheetRows[$row]['tools'])) {
                $this->addSpreadsheetLine($row, 'tools');
            }

            return;
        }

        $usage = ToolUsageModel::with('tool:id,warehouse_id')
            ->whereKey($itemId)->where('house_id', $this->spreadsheetRows[$row]['house_id'])
            ->whereNull('return_date')->whereNull('voided_at')
            ->whereHas('house', fn ($query) => $query->forUser(auth()->user()))
            ->first();
        if (! $usage) {
            return;
        }

        $selectedUsageIds = collect($this->spreadsheetRows[$row]['returns'] ?? [])
            ->reject(fn ($returnLine, $returnLineIndex) => (int) $returnLineIndex === $line)
            ->pluck('usage_id')
            ->filter();
        if ($selectedUsageIds->isNotEmpty() && ToolUsageModel::whereIn('id', $selectedUsageIds)
            ->where('house_id', $this->spreadsheetRows[$row]['house_id'])
            ->where('tool_id', $usage->tool_id)->exists()) {
            $this->addError("spreadsheetRows.$row.returns.$line.usage_id", 'Alat ini sudah dipilih untuk dikembalikan.');

            return;
        }

        $this->resetValidation("spreadsheetRows.$row.returns.$line.usage_id");

        $this->spreadsheetRows[$row]['returns'][$line] = [
            'usage_id' => (string) $usage->id,
            'qty_normal' => 0,
            'qty_broken' => 0,
            'qty_lost' => 0,
            'receiving_warehouse_id' => (string) (($usage->warehouse_source_recorded ? $usage->warehouse_id : null) ?? $usage->tool->warehouse_id ?? ''),
            'notes' => '',
        ];

        if ($line === array_key_last($this->spreadsheetRows[$row]['returns'])) {
            $this->addSpreadsheetLine($row, 'returns');
        }
    }

    public function removeSpreadsheetLine(int $row, string $type, int $line): void
    {
        if (isset($this->spreadsheetRows[$row][$type][$line]) && in_array($type, ['materials', 'tools', 'returns'], true)) {
            unset($this->spreadsheetRows[$row][$type][$line]);
            $this->spreadsheetRows[$row][$type] = array_values($this->spreadsheetRows[$row][$type]);
            $this->resetValidation();
        }
    }

    private function availableSpreadsheetBatchQuantity(int $row, int $line, int $batchId): float
    {
        $batch = StockIn::withReservedQuantity()->find($batchId);
        if (! $batch) {
            return 0;
        }

        $reservedElsewhere = 0.0;
        foreach ($this->spreadsheetRows as $rowIndex => $spreadsheetRow) {
            foreach ($spreadsheetRow['materials'] ?? [] as $lineIndex => $materialLine) {
                if ((int) $rowIndex === $row && (int) $lineIndex === $line) {
                    continue;
                }
                if ((int) ($materialLine['batch_id'] ?? 0) === $batchId) {
                    $reservedElsewhere += max(0, (float) ($materialLine['quantity'] ?? 0));
                }
            }
        }

        return max(0, round((float) $batch->available_quantity - $reservedElsewhere, 2));
    }

    public function reviewSpreadsheet(): void
    {
        try {
            $rows = $this->validateSpreadsheetInput();
        } catch (ValidationException $e) {
            $this->showSpreadsheetReview = false;
            throw $e;
        }
        $houseIds = collect($rows)->pluck('house_id')->map(fn ($id) => (int) $id)->all();
        $houses = House::forUser(auth()->user())->whereIn('id', $houseIds)->get(['id', 'name', 'house_code'])->keyBy('id');
        if ($houses->count() !== count($houseIds)) {
            throw ValidationException::withMessages(['spreadsheetRows' => 'Pilih rumah yang termasuk cluster tugas akun Anda.']);
        }
        $materialIds = collect($rows)->flatMap(fn ($row) => collect($row['materials'] ?? [])->pluck('material_id'))->map(fn ($id) => (int) $id)->unique();
        $batchIds = collect($rows)->flatMap(fn ($row) => collect($row['materials'] ?? [])->pluck('batch_id'))->map(fn ($id) => (int) $id)->unique();
        $toolIds = collect($rows)->flatMap(fn ($row) => collect($row['tools'] ?? [])->pluck('tool_id'))->map(fn ($id) => (int) $id)->unique();
        $usageIds = collect($rows)->flatMap(fn ($row) => collect($row['returns'] ?? [])->pluck('usage_id'))->map(fn ($id) => (int) $id)->unique();
        $warehouseIds = collect($rows)->flatMap(fn ($row) => collect($row['tools'] ?? [])->pluck('warehouse_id')->merge(collect($row['returns'] ?? [])->pluck('receiving_warehouse_id')))->filter()->map(fn ($id) => (int) $id)->unique();
        $materialMap = Material::whereIn('id', $materialIds)->get(['id', 'name', 'unit'])->keyBy('id');
        $batchMap = StockIn::whereIn('id', $batchIds)->get(['id', 'entry_code', 'unit_price'])->keyBy('id');
        $toolMap = Tool::whereIn('id', $toolIds)->get(['id', 'name'])->keyBy('id');
        $usageMap = ToolUsageModel::with('tool:id,name')->whereIn('id', $usageIds)
            ->whereHas('house', fn ($query) => $query->forUser(auth()->user()))->get()->keyBy('id');
        $warehouseMap = Warehouse::whereIn('id', $warehouseIds)->pluck('name', 'id');
        $this->spreadsheetReviewRows = [];
        foreach ($rows as $row) {
            $details = [];
            $materialTotal = 0;
            foreach ($row['materials'] ?? [] as $line) {
                $material = $materialMap[(int) $line['material_id']] ?? null;
                $batch = $batchMap[(int) $line['batch_id']] ?? null;
                $lineTotal = (float) $line['quantity'] * (float) ($batch?->unit_price ?? 0);
                $materialTotal += $lineTotal;
                $details[] = 'Material · '.($material?->name ?? 'Material tidak tersedia').' · '.$line['quantity'].' '.($material?->unit ?? '').' · '.($batch?->entry_code ?? 'Batch').' · Rp '.number_format((float) ($batch?->unit_price ?? 0), 0, ',', '.').'/'.($material?->unit ?? 'unit').' · subtotal Rp '.number_format($lineTotal, 0, ',', '.').' · '.$line['notes'];
            }
            foreach ($row['tools'] ?? [] as $line) {
                $tool = $toolMap[(int) $line['tool_id']] ?? null;
                $details[] = 'Alat · '.($tool?->name ?? 'Alat tidak tersedia').' · '.$line['quantity'].' unit · '.($warehouseMap[(int) $line['warehouse_id']] ?? 'Gudang').' · '.$line['notes'];
            }
            foreach ($row['returns'] ?? [] as $line) {
                $usage = $usageMap[(int) $line['usage_id']] ?? null;
                if (! $usage || (int) $usage->house_id !== (int) $row['house_id'] || $usage->return_date || $usage->voided_at) {
                    throw ValidationException::withMessages(["spreadsheetRows.{$rowIndex}.returns.{$lineIndex}.usage_id" => 'Pilihan pengembalian sudah berubah. Pilih ulang alat pada rumah tersebut.']);
                }
                $returnQuantity = (int) $line['qty_normal'] + (int) $line['qty_broken'] + (int) $line['qty_lost'];
                if ($returnQuantity > $usage->quantity) {
                    throw ValidationException::withMessages(["spreadsheetRows.{$rowIndex}.returns.{$lineIndex}.qty_normal" => 'Jumlah pengembalian '.$usage->tool->name.' melebihi jumlah yang dipinjam.']);
                }
                $warehouse = ! empty($line['receiving_warehouse_id']) ? ' · '.($warehouseMap[(int) $line['receiving_warehouse_id']] ?? 'Gudang') : '';
                $details[] = 'Kembali · '.$usage->tool->name.' · baik '.$line['qty_normal'].' / rusak '.$line['qty_broken'].' / hilang '.$line['qty_lost'].$warehouse;
            }
            $house = $houses[(int) $row['house_id']];
            $this->spreadsheetReviewRows[] = ['house' => implode(' · ', array_filter([$house->name, $house->house_code])), 'details' => $details, 'material_total' => $materialTotal, 'material_count' => count($row['materials'] ?? [])];
        }
        $this->spreadsheetReviewTotals = [
            'materials' => collect($rows)->sum(fn ($row) => count($row['materials'] ?? [])),
            'tools' => collect($rows)->sum(fn ($row) => count($row['tools'] ?? [])),
            'returns' => collect($rows)->sum(fn ($row) => count($row['returns'] ?? [])),
        ];
        $this->showSpreadsheetConfirmation = false;
        $this->showSpreadsheetReview = true;
    }

    public function confirmSpreadsheetReview(): void
    {
        if ($this->showSpreadsheetReview) {
            $this->showSpreadsheetReview = false;
            $this->showSpreadsheetConfirmation = true;
        }
    }

    private function validateSpreadsheetInput(): array
    {
        $rowsForValidation = collect($this->spreadsheetRows)
            ->filter(fn ($row) => is_array($row) && ! empty($row['house_id']))
            ->map(function ($row) {
                foreach (['materials' => 'material_id', 'tools' => 'tool_id', 'returns' => 'usage_id'] as $type => $key) {
                    $row[$type] = collect($row[$type] ?? [])->filter(fn ($line) => is_array($line) && ! empty($line[$key]))->values()->all();
                }

                return $row;
            })
            ->filter(fn ($row) => ! empty($row['materials']) || ! empty($row['tools']) || ! empty($row['returns']))
            ->all();
        if ($rowsForValidation === []) {
            throw ValidationException::withMessages(['spreadsheetRows' => 'Pilih rumah dan tambahkan material, alat, atau pengembalian.']);
        }

        $originalRows = $this->spreadsheetRows;
        $this->spreadsheetRows = $rowsForValidation;
        try {
            $rows = $this->validate([
            'spreadsheetRows' => 'required|array|min:1|max:100',
            'spreadsheetRows.*.house_id' => 'required|integer|distinct|exists:houses,id',
            'spreadsheetRows.*.materials' => 'array|max:50',
            'spreadsheetRows.*.materials.*.material_id' => 'required|integer|exists:materials,id',
            'spreadsheetRows.*.materials.*.batch_id' => 'required|integer|exists:stock_ins,id',
            'spreadsheetRows.*.materials.*.quantity' => 'required|numeric|min:0.01',
            'spreadsheetRows.*.materials.*.notes' => 'required|string|max:500',
            'spreadsheetRows.*.materials.*.taken_by' => 'nullable|string|max:120',
            'spreadsheetRows.*.tools' => 'array|max:50',
            'spreadsheetRows.*.tools.*.tool_id' => 'required|integer|exists:tools,id',
            'spreadsheetRows.*.tools.*.warehouse_id' => 'required|integer|exists:warehouses,id',
            'spreadsheetRows.*.tools.*.quantity' => 'required|integer|min:1',
            'spreadsheetRows.*.tools.*.notes' => 'required|string|max:500',
            'spreadsheetRows.*.returns' => 'array|max:50',
            'spreadsheetRows.*.returns.*.usage_id' => 'required|integer|distinct|exists:tool_usages,id',
            'spreadsheetRows.*.returns.*.qty_normal' => 'required|integer|min:0',
            'spreadsheetRows.*.returns.*.qty_broken' => 'required|integer|min:0',
            'spreadsheetRows.*.returns.*.qty_lost' => 'required|integer|min:0',
            'spreadsheetRows.*.returns.*.receiving_warehouse_id' => 'nullable|integer|exists:warehouses,id',
            'spreadsheetRows.*.returns.*.notes' => 'nullable|string|max:500',
            ], [
                'spreadsheetRows.*.materials.*.material_id.required' => 'Pilih material.',
                'spreadsheetRows.*.materials.*.material_id.exists' => 'Material tidak tersedia. Pilih material lain.',
                'spreadsheetRows.*.materials.*.batch_id.required' => 'Pilih batch material.',
                'spreadsheetRows.*.materials.*.batch_id.exists' => 'Batch material tidak tersedia. Pilih batch lain.',
                'spreadsheetRows.*.materials.*.quantity.required' => 'Isi jumlah material.',
                'spreadsheetRows.*.materials.*.quantity.numeric' => 'Jumlah material harus berupa angka.',
                'spreadsheetRows.*.materials.*.quantity.min' => 'Jumlah material minimal 0,01.',
                'spreadsheetRows.*.materials.*.notes.required' => 'Isi peruntukan material.',
                'spreadsheetRows.*.tools.*.tool_id.required' => 'Pilih alat.',
                'spreadsheetRows.*.tools.*.tool_id.exists' => 'Alat tidak tersedia. Pilih alat lain.',
                'spreadsheetRows.*.tools.*.warehouse_id.required' => 'Pilih gudang asal alat.',
                'spreadsheetRows.*.tools.*.quantity.required' => 'Isi jumlah alat.',
                'spreadsheetRows.*.tools.*.quantity.integer' => 'Jumlah alat harus bilangan bulat.',
                'spreadsheetRows.*.tools.*.quantity.min' => 'Jumlah alat minimal 1.',
                'spreadsheetRows.*.tools.*.notes.required' => 'Isi peruntukan alat.',
                'spreadsheetRows.*.returns.*.usage_id.required' => 'Pilih alat yang akan dikembalikan.',
                'spreadsheetRows.*.returns.*.usage_id.exists' => 'Peminjaman tidak tersedia. Pilih alat yang masih dipinjam.',
                'spreadsheetRows.*.returns.*.qty_normal.required' => 'Isi jumlah alat dalam kondisi baik.',
                'spreadsheetRows.*.returns.*.qty_broken.required' => 'Isi jumlah alat dalam kondisi rusak.',
                'spreadsheetRows.*.returns.*.qty_lost.required' => 'Isi jumlah alat yang hilang.',
            ])['spreadsheetRows'];
        } finally {
            $this->spreadsheetRows = $originalRows;
        }

        $batchAllocations = collect($rows)
            ->flatMap(fn ($row, $rowIndex) => collect($row['materials'] ?? [])->map(fn ($line, $lineIndex) => [
                'row' => $rowIndex,
                'line' => $lineIndex,
                'batch_id' => (int) $line['batch_id'],
                'quantity' => (float) $line['quantity'],
            ]))
            ->groupBy('batch_id');
        $batches = StockIn::withReservedQuantity()->whereIn('id', $batchAllocations->keys())
            ->get(['id', 'remaining_quantity'])->keyBy('id');
        foreach ($batchAllocations as $batchId => $allocations) {
            $batch = $batches->get((int) $batchId);
            if (! $batch) {
                $allocation = $allocations->first();
                throw ValidationException::withMessages(["spreadsheetRows.{$allocation['row']}.materials.{$allocation['line']}.batch_id" => 'Batch material sudah tidak tersedia. Pilih batch lain.']);
            }

            $remainingQuantity = (float) $batch->available_quantity;
            foreach ($allocations as $allocation) {
                $quantity = (float) $allocation['quantity'];
                if ($quantity > $remainingQuantity + 0.001) {
                    throw ValidationException::withMessages(["spreadsheetRows.{$allocation['row']}.materials.{$allocation['line']}.quantity" => 'Sisa batch hanya '.number_format(max(0, $remainingQuantity), 2, ',', '.').'. Kurangi jumlah atau pilih batch lain.']);
                }
                $remainingQuantity = max(0, $remainingQuantity - $quantity);
            }
        }

        $hasActions = false;
        foreach ($rows as $rowIndex => $row) {
            $rowHasActions = ! empty($row['materials']) || ! empty($row['tools']) || ! empty($row['returns']);
            if (! $rowHasActions) {
                throw ValidationException::withMessages(["spreadsheetRows.$rowIndex.house_id" => 'Isi transaksi untuk rumah ini atau hapus barisnya.']);
            }
            $hasActions = $hasActions || $rowHasActions;
            foreach ($row['returns'] ?? [] as $lineIndex => $line) {
                $quantity = (int) $line['qty_normal'] + (int) $line['qty_broken'] + (int) $line['qty_lost'];
                if ($quantity < 1) {
                    throw ValidationException::withMessages(["spreadsheetRows.$rowIndex.returns.$lineIndex.qty_normal" => 'Isi jumlah kondisi alat yang dikembalikan.']);
                }
                if ((int) $line['qty_normal'] + (int) $line['qty_broken'] > 0 && empty($line['receiving_warehouse_id'])) {
                    throw ValidationException::withMessages(["spreadsheetRows.$rowIndex.returns.$lineIndex.receiving_warehouse_id" => 'Pilih gudang penerima untuk alat yang kembali.']);
                }
            }
        }
        $returnUsageIds = collect($rows)->flatMap(fn ($row) => collect($row['returns'] ?? [])->pluck('usage_id'))->map(fn ($id) => (int) $id);
        if ($returnUsageIds->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages(['spreadsheetRows' => 'Satu alat pinjaman hanya dapat dikembalikan satu kali dalam satu penyimpanan.']);
        }
        if (! $hasActions) {
            throw ValidationException::withMessages(['spreadsheetRows' => 'Tambahkan material, alat, atau pengembalian sebelum menyimpan.']);
        }

        return $rows;
    }

    public function saveSpreadsheet(): void
    {
        if ($this->saving || ! $this->showSpreadsheetConfirmation) {
            return;
        }
        $this->showSpreadsheetConfirmation = false;

        try {
            $rows = $this->validateSpreadsheetInput();
        } catch (ValidationException $e) {
            $this->showSpreadsheetReview = false;
            $this->showSpreadsheetConfirmation = false;
            throw $e;
        }
        $houseIds = collect($rows)->pluck('house_id')->map(fn ($id) => (int) $id)->all();
        $this->saving = true;
        try {
            DB::transaction(function () use ($rows, $houseIds) {
                $houses = House::forUser(auth()->user())->whereIn('id', $houseIds)->orderBy('id')->lockForUpdate()->get(['id', 'name', 'status', 'warranty_expires_at']);
                if ($houses->count() !== count($houseIds)) {
                    throw ValidationException::withMessages(['spreadsheetRows' => 'Salah satu rumah tidak lagi tersedia untuk akun Anda. Muat ulang lalu pilih ulang rumah.']);
                }
                $houseById = $houses->keyBy('id');
                $allocationHouseIds = collect($rows)->filter(fn ($row) => ! empty($row['materials']) || ! empty($row['tools']))->pluck('house_id')->map(fn ($id) => (int) $id);
                if ($houses->whereIn('id', $allocationHouseIds)->contains(fn ($house) => ! $house->canReceiveAllocations())) {
                    throw ValidationException::withMessages(['spreadsheetRows' => 'Rumah yang sudah selesai masa garansinya tidak dapat menerima material atau alat.']);
                }

                foreach ($rows as $rowIndex => $row) {
                    $house = $houseById[(int) $row['house_id']];

                    foreach ($row['materials'] ?? [] as $lineIndex => $line) {
                        $material = Material::whereKey($line['material_id'])->lockForUpdate()->firstOrFail();
                        $quantity = (float) $line['quantity'];
                        if ((float) $material->stock + 0.001 < $quantity) {
                            throw ValidationException::withMessages(["spreadsheetRows.$rowIndex.materials.$lineIndex.quantity" => 'Stok '.$material->name.' tidak mencukupi untuk alokasi ini.']);
                        }

                        $batch = StockIn::withReservedQuantity()->whereKey($line['batch_id'])->where('material_id', $material->id)->whereNotNull('warehouse_id')->lockForUpdate()->first();
                        if (! $batch || (float) $batch->available_quantity + 0.001 < $quantity) {
                            throw ValidationException::withMessages(["spreadsheetRows.$rowIndex.materials.$lineIndex.batch_id" => 'Saldo batch '.$material->name.' berubah atau tidak cukup. Pilih batch lain.']);
                        }

                        $this->recordMaterialUsage($house, $material, $batch->id, $quantity, (float) $batch->unit_price, $line['notes'] ?? null, takenBy: $line['taken_by'] ?? null);
                        $batch->decrement('remaining_quantity', $quantity);
                        $material->decrement('stock', $quantity);
                    }

                    foreach ($row['tools'] ?? [] as $lineIndex => $line) {
                        $tool = Tool::whereKey($line['tool_id'])->lockForUpdate()->firstOrFail();
                        $warehouseId = (int) $line['warehouse_id'];
                        $quantity = (int) $line['quantity'];
                        $usageExists = ToolUsageModel::where('tool_id', $tool->id)->where('house_id', $house->id)->whereNull('return_date')->whereNull('voided_at')->exists();
                        if ($usageExists) {
                            throw ValidationException::withMessages(["spreadsheetRows.$rowIndex.tools.$lineIndex.tool_id" => $tool->name.' masih dipinjam oleh '.$house->name.'.']);
                        }

                        $balance = ToolInventory::lockBalance($tool, $warehouseId);
                        if ((int) $balance->available_qty < $quantity) {
                            throw ValidationException::withMessages(["spreadsheetRows.$rowIndex.tools.$lineIndex.quantity" => 'Stok '.$tool->name.' di gudang pilihan tidak mencukupi.']);
                        }

                        ToolInventory::checkout($tool, $warehouseId, $quantity);
                        $this->recordToolUsage($house, $tool, $warehouseId, $quantity, $line['notes'] ?? null);
                    }

                    foreach ($row['returns'] ?? [] as $lineIndex => $line) {
                        $usage = ToolUsageModel::where('house_id', $house->id)
                            ->whereHas('house', fn ($query) => $query->forUser(auth()->user()))
                            ->lockForUpdate()->find($line['usage_id']);
                        if (! $usage || $usage->return_date || $usage->voided_at) {
                            throw ValidationException::withMessages(["spreadsheetRows.$rowIndex.returns.$lineIndex.usage_id" => 'Alat ini sudah dikembalikan atau bukan milik rumah yang dipilih.']);
                        }
                        $returnQty = (int) $line['qty_normal'] + (int) $line['qty_broken'] + (int) $line['qty_lost'];
                        if ($returnQty > $usage->quantity) {
                            throw ValidationException::withMessages(["spreadsheetRows.$rowIndex.returns.$lineIndex.qty_normal" => 'Jumlah pengembalian melebihi jumlah alat yang tercatat dipinjam.']);
                        }
                        if (((int) $line['qty_normal'] + (int) $line['qty_broken']) > 0 && empty($line['receiving_warehouse_id'])) {
                            throw ValidationException::withMessages(["spreadsheetRows.$rowIndex.returns.$lineIndex.receiving_warehouse_id" => 'Pilih gudang penerima untuk alat yang kembali.']);
                        }
                        $this->recordToolReturn([
                            'usage_id' => $usage->id,
                            'qty_normal' => (int) $line['qty_normal'], 'qty_broken' => (int) $line['qty_broken'], 'qty_lost' => (int) $line['qty_lost'],
                            'receiving_warehouse_id' => (int) ($line['receiving_warehouse_id'] ?? 0), 'notes' => $line['notes'] ?? '',
                        ]);
                    }
                }
            });

            $this->showSpreadsheetReview = false;
            $this->spreadsheetRows = [['house_id' => '', 'materials' => [], 'tools' => [], 'returns' => []]];
            $this->house_ids = [];
            $this->spreadsheetReviewRows = [];
            $this->spreadsheetReviewTotals = [];
            $this->resetValidation();
            $hasMaterialHistory = collect($rows)->contains(fn ($row) => ! empty($row['materials']));
            $hasToolHistory = collect($rows)->contains(fn ($row) => ! empty($row['tools']) || ! empty($row['returns']));
            $historyType = $hasMaterialHistory && $hasToolHistory ? 'both' : ($hasMaterialHistory ? 'material' : ($hasToolHistory ? 'tool' : ''));
            $this->showSuccess('Alokasi spreadsheet berhasil disimpan untuk '.count($rows).' rumah.', $historyType);
        } catch (ValidationException $e) {
            $this->showSpreadsheetReview = false;
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            $this->showSpreadsheetReview = false;
            $this->addError('spreadsheetRows', 'Alokasi tidak tersimpan. Muat ulang data lalu coba kembali.');
        } finally {
            $this->saving = false;
        }
    }

    public function saveRental(): void
    {
        if ($this->saving) {
            return;
        }

        $this->noticeSequence++;
        $this->noticeMessage = '';
        $this->noticeHistoryType = '';

        try {
            $validated = $this->validate([
                'house_ids' => 'required|array|min:1',
                'house_ids.*' => 'required|integer|distinct|exists:houses,id',
                'rental_description' => 'required|string|max:255',
                'rental_vendor' => 'required|string|max:255',
                'rental_quantity' => 'required|integer|min:1',
                'rental_start_date' => 'required|date',
                'rental_due_date' => 'required|date|after_or_equal:rental_start_date',
                'rental_amount' => 'required|numeric|min:0',
                'rental_notes' => 'nullable|string|max:2000',
                'rental_bill_image' => 'required|image|max:5120',
            ], [
                'house_ids.required' => 'Pilih minimal satu rumah tujuan.',
                'house_ids.min' => 'Pilih minimal satu rumah tujuan.',
            ], [
                'house_ids' => 'rumah tujuan',
                'rental_description' => 'nama alat',
                'rental_vendor' => 'vendor',
                'rental_quantity' => 'total unit sewa',
                'rental_start_date' => 'tanggal mulai sewa',
                'rental_due_date' => 'tanggal akhir sewa',
                'rental_amount' => 'total biaya sewa',
                'rental_bill_image' => 'foto tagihan',
                'rental_notes' => 'catatan',
            ]);

            $houses = House::query()->forUser(auth()->user())
                ->where('status', '!=', 'selesai')
                ->whereIn('id', $validated['house_ids'])->get(['id', 'cluster_id', 'name']);
            if ($houses->count() !== count($validated['house_ids']) || $houses->contains(fn ($house) => ! $house->cluster_id)) {
                throw ValidationException::withMessages(['house_ids' => 'Pilih rumah aktif dalam cluster tugas.']);
            }
            if ($houses->pluck('cluster_id')->unique()->count() !== 1) {
                throw ValidationException::withMessages(['house_ids' => 'Pilih rumah dari satu cluster untuk satu sewa vendor.']);
            }
        } catch (ValidationException $e) {
            $this->noticeType = 'error';
            $this->noticeMessage = $e->validator->errors()->first();
            throw $e;
        }

        $this->saving = true;
        $path = null;
        try {
            $path = $this->rental_bill_image->store('cluster-expense-bills', 'public');
            DB::transaction(function () use ($houses, $validated, $path) {
                $vendorName = trim($validated['rental_vendor']);
                Supplier::firstOrCreate(['name' => $vendorName]);
                $rental = ClusterExpense::create([
                    'cluster_id' => $houses->first()->cluster_id,
                    'created_by' => auth()->id(),
                    'type' => 'rental',
                    'description' => $validated['rental_description'],
                    'vendor' => $vendorName,
                    'quantity' => $validated['rental_quantity'],
                    'start_date' => $validated['rental_start_date'],
                    'due_date' => $validated['rental_due_date'],
                    'amount' => $validated['rental_amount'],
                    'notes' => $validated['rental_notes'] ?? null,
                    'bill_image' => $path,
                    'status' => 'active',
                ]);
                $rental->houses()->attach($houses->pluck('id')->all());
            });
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }
            throw $e;
        } finally {
            $this->saving = false;
        }

        $this->resetRentalForm();
        $this->showSuccess('Sewa alat dari vendor berhasil dicatat untuk '.$houses->count().' rumah.');
    }

    public function resetRentalForm(): void
    {
        $this->reset(['rental_description', 'rental_vendor', 'rental_quantity', 'rental_start_date', 'rental_due_date', 'rental_amount', 'rental_notes', 'rental_bill_image']);
        $this->noticeMessage = '';
        $this->rental_quantity = '1';
        $this->rental_start_date = now()->toDateString();
        $this->resetValidation();
    }

    private function validateVendorService(): array
    {
        $houseTarget = $this->vendor_service_target === 'house';

        return $this->validate([
            'vendor_service_target' => 'required|in:cluster,house',
            'vendor_service_cluster_id' => $houseTarget ? 'prohibited' : 'required|integer|exists:clusters,id',
            'house_ids' => $houseTarget ? 'required|array|size:1' : 'prohibited',
            'house_ids.*' => $houseTarget ? 'required|integer|distinct|exists:houses,id' : 'prohibited',
            'vendor_service_description' => 'required|string|max:255',
            'vendor_service_vendor' => 'required|string|max:255',
            'vendor_service_date' => 'required|date',
            'vendor_service_amount' => 'required|numeric|min:0',
            'vendor_service_bill_image' => 'nullable|image|max:5120',
            'vendor_service_notes' => 'nullable|string|max:2000',
        ], [], [
            'vendor_service_target' => 'target jasa',
            'vendor_service_cluster_id' => 'cluster tujuan',
            'house_ids' => 'rumah tujuan',
            'vendor_service_description' => 'nama jasa',
            'vendor_service_vendor' => 'vendor',
            'vendor_service_date' => 'tanggal layanan',
            'vendor_service_amount' => 'total biaya jasa',
            'vendor_service_bill_image' => 'foto tagihan',
            'vendor_service_notes' => 'catatan',
        ]);
    }

    private function resolveVendorServiceTarget(array $validated): array
    {
        $user = auth()->user();
        if ($this->vendor_service_target === 'house') {
            $house = House::query()->forUser($user)->find($validated['house_ids'][0], ['id', 'cluster_id', 'name']);
            if (! $house || ! $house->cluster_id) {
                throw ValidationException::withMessages(['house_ids' => 'Pilih rumah dalam cluster yang dapat Anda akses.']);
            }

            return ['house' => $house, 'clusterId' => (int) $house->cluster_id, 'label' => 'rumah '.$house->name, 'display' => 'Rumah '.$house->name];
        }

        $cluster = Cluster::query()
            ->when($user->role !== 'admin', fn ($query) => $query->whereKey($user->cluster_id ?? 0))
            ->find($validated['vendor_service_cluster_id']);
        if (! $cluster) {
            throw ValidationException::withMessages(['vendor_service_cluster_id' => 'Pilih cluster yang dapat Anda akses.']);
        }

        return ['house' => null, 'clusterId' => (int) $cluster->id, 'label' => 'cluster '.$cluster->name, 'display' => $cluster->name];
    }

    public function showVendorServiceConfirmationModal(): void
    {
        if ($this->saving) {
            return;
        }

        $this->noticeSequence++;
        $this->noticeMessage = '';
        $this->noticeHistoryType = '';

        try {
            $validated = $this->validateVendorService();
            $target = $this->resolveVendorServiceTarget($validated);
        } catch (ValidationException $e) {
            $this->noticeType = 'error';
            $this->noticeMessage = $e->validator->errors()->first();
            throw $e;
        }

        $this->vendorServiceConfirmationData = [
            'target' => $target['display'],
            'description' => $validated['vendor_service_description'],
            'vendor' => trim($validated['vendor_service_vendor']),
            'date' => $validated['vendor_service_date'],
            'amount' => (float) $validated['vendor_service_amount'],
            'billAttached' => $this->vendor_service_bill_image !== null,
        ];
        $this->showVendorServiceConfirmation = true;
    }

    public function saveVendorService(): void
    {
        if ($this->saving || ! $this->showVendorServiceConfirmation) {
            return;
        }

        $this->showVendorServiceConfirmation = false;
        $this->noticeSequence++;
        $this->noticeMessage = '';
        $this->noticeHistoryType = '';

        try {
            $validated = $this->validateVendorService();
            $target = $this->resolveVendorServiceTarget($validated);
            $house = $target['house'];
            $clusterId = $target['clusterId'];
            $targetLabel = $target['label'];
        } catch (ValidationException $e) {
            $this->noticeType = 'error';
            $this->noticeMessage = $e->validator->errors()->first();
            throw $e;
        }

        $this->saving = true;
        $path = null;
        try {
            $path = $this->vendor_service_bill_image?->store('cluster-expense-bills', 'public');
            DB::transaction(function () use ($house, $clusterId, $validated, $path) {
                $vendorName = trim($validated['vendor_service_vendor']);
                Supplier::firstOrCreate(['name' => $vendorName]);
                $service = ClusterExpense::create([
                    'cluster_id' => $clusterId,
                    'house_id' => $house?->id,
                    'created_by' => auth()->id(),
                    'type' => 'vendor_service',
                    'description' => $validated['vendor_service_description'],
                    'vendor' => $vendorName,
                    'start_date' => $validated['vendor_service_date'],
                    'amount' => $validated['vendor_service_amount'],
                    'notes' => $validated['vendor_service_notes'] ?? null,
                    'bill_image' => $path,
                    'status' => 'active',
                ]);
                if ($house) {
                    $service->houses()->attach($house->id);
                }
            });
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }
            throw $e;
        } finally {
            $this->saving = false;
        }

        $this->resetVendorServiceForm();
        $this->showSuccess('Jasa vendor berhasil dicatat untuk '.$targetLabel.'.');
    }

    public function resetVendorServiceForm(): void
    {
        $this->reset(['vendor_service_description', 'vendor_service_vendor', 'vendor_service_amount', 'vendor_service_notes', 'vendor_service_bill_image', 'house_ids']);
        $this->vendor_service_date = now()->toDateString();
        $this->showVendorServiceConfirmation = false;
        $this->vendorServiceConfirmationData = [];
        $this->noticeMessage = '';
        $this->resetValidation();
    }

    public function showMaterialConfirmationModal(): void
    {
        $validated = $this->validate($this->materialRules());
        $houseIds = array_map('intval', $validated['house_ids']);

        $material = Material::with('supplier:id,name')->findOrFail($validated['material_id']);
        $totalQuantity = (float) $validated['material_quantity'] * count($houseIds);
        $batch = StockIn::with(['warehouse:id,name', 'supplier:id,name'])
            ->withReservedQuantity()
            ->whereKey($validated['material_batch_id'])
            ->where('material_id', $material->id)
            ->whereNotNull('warehouse_id')
            ->first();
        if (! $batch) {
            $this->addError('material_batch_id', 'Pilih batch masuk untuk material ini.');

            return;
        }
        $houses = House::with('cluster:id,name')->forUser(auth()->user())->whereIn('id', $houseIds)->get();
        if ($houses->count() !== count($houseIds)) {
            $this->addError('house_ids', 'Pilih rumah di cluster yang ditugaskan kepada Anda.');

            return;
        }
        $availableMaterialStock = max(0, (float) $material->stock);
        $availableBatchStock = (float) $batch->remaining_quantity;

        if ($availableMaterialStock + 0.001 < $totalQuantity || $availableBatchStock + 0.001 < $totalQuantity) {
            $available = min($availableMaterialStock, $availableBatchStock);
            $this->addError('material_quantity', 'Stok bebas tidak mencukupi. Total dibutuhkan: '.$totalQuantity.' '.$material->unit.'. Tersedia: '.$available.' '.$material->unit);

            return;
        }

        $completedHouses = $houses->filter(fn ($house) => ! $house->canReceiveAllocations());
        if ($completedHouses->isNotEmpty()) {
            $names = $completedHouses->pluck('name')->join(', ');
            $this->addError('house_ids', "Rumah berikut sudah selesai masa garansinya: {$names}");

            return;
        }

        $this->materialConfirmationData = [
            'houses' => $houses->pluck('name')->join(', '),
            'houseRows' => $houses->map(fn ($house) => [
                'name' => $house->name,
                'code' => $house->house_code,
                'cluster' => $house->cluster?->name ?? 'Tanpa cluster',
            ])->values()->all(),
            'houseCount' => count($houseIds),
            'clusters' => $houses->map(fn ($house) => $house->cluster?->name ?? 'Tanpa cluster')->unique()->join(', '),
            'materialName' => $material->name,
            'materialCode' => $material->code ?? '-',
            'materialSupplier' => $batch->supplier?->name ?? $material->supplier?->name ?? 'Tanpa supplier',
            'batchCode' => $batch->entry_code,
            'warehouseName' => $batch->warehouse?->name ?? 'Belum ada gudang',
            'materialUnit' => $material->unit,
            'quantityPerHouse' => $validated['material_quantity'],
            'totalQuantity' => $totalQuantity,
            'unitPrice' => $batch->unit_price,
            'totalCost' => $totalQuantity * (float) $batch->unit_price,
            'notes' => $validated['material_notes'],
            'takenBy' => trim((string) ($validated['material_taken_by'] ?? '')) ?: null,
        ];

        $this->showMaterialConfirmation = true;
    }

    public function saveMaterial(): void
    {
        if ($this->saving) {
            return;
        }

        $validated = $this->validate([
            ...$this->materialRules(),
            'materialAllocationProofImage' => 'nullable|image|max:5120',
        ], [
            'materialAllocationProofImage.image' => 'Berkas harus berupa foto.',
            'materialAllocationProofImage.max' => 'Ukuran foto maksimal 5 MB.',
        ]);
        $houseIds = array_map('intval', $validated['house_ids']);
        $quantity = (float) $validated['material_quantity'];
        $proofPath = $this->materialAllocationProofImage?->store('allocation-proofs', 'public');
        $this->saving = true;

        try {
            $takenBy = trim((string) ($validated['material_taken_by'] ?? '')) ?: null;

            DB::transaction(function () use ($validated, $houseIds, $quantity, $proofPath, $takenBy) {
                $material = Material::lockForUpdate()->findOrFail($validated['material_id']);
                $houses = House::forUser(auth()->user())->whereIn('id', $houseIds)->orderBy('id')->lockForUpdate()->get(['id', 'name', 'status', 'warranty_expires_at']);

                if ($houses->count() !== count($houseIds)) {
                    throw ValidationException::withMessages(['house_ids' => 'Salah satu rumah tujuan sudah tidak tersedia. Pilih ulang rumah.']);
                }

                $completedHouses = $houses->filter(fn ($house) => ! $house->canReceiveAllocations());
                if ($completedHouses->isNotEmpty()) {
                    throw ValidationException::withMessages([
                        'house_ids' => 'Rumah berikut sudah selesai masa garansinya: '.$completedHouses->pluck('name')->join(', '),
                    ]);
                }

                $totalQuantityRequired = $quantity * count($houseIds);
                if ((float) $material->stock + 0.001 < $totalQuantityRequired) {
                    throw ValidationException::withMessages([
                        'material_quantity' => 'Stok bebas material berubah atau tidak mencukupi. Pilih ulang jumlah.',
                    ]);
                }

                $batch = StockIn::whereKey($validated['material_batch_id'])
                    ->where('material_id', $material->id)
                    ->whereNotNull('warehouse_id')
                    ->lockForUpdate()
                    ->first();
                if (! $batch) {
                    throw ValidationException::withMessages([
                        'material_batch_id' => 'Batch material berubah. Pilih ulang batch.',
                    ]);
                }
                if ((float) $batch->remaining_quantity + 0.001 < $totalQuantityRequired) {
                    throw ValidationException::withMessages([
                        'material_quantity' => 'Saldo batch berubah atau tidak mencukupi. Pilih ulang jumlah material.',
                    ]);
                }

                $houseById = $houses->keyBy('id');
                foreach ($houseIds as $houseId) {
                    $this->recordMaterialUsage($houseById[$houseId], $material, $batch->id, $quantity, (float) $batch->unit_price, $validated['material_notes'], $proofPath, $takenBy);
                }

                $batch->decrement('remaining_quantity', $totalQuantityRequired);
                $material->decrement('stock', $totalQuantityRequired);
            });

            $this->showSuccess('Material langsung dialokasikan ke '.count($houseIds).' rumah.', 'material');
            $this->resetMaterialForm();
        } catch (ValidationException $e) {
            if ($proofPath) Storage::disk('public')->delete($proofPath);
            throw $e;
        } catch (\Throwable $e) {
            if ($proofPath) Storage::disk('public')->delete($proofPath);
            $this->addError('material_quantity', $e->getMessage());
        } finally {
            $this->saving = false;
        }
    }

    public function showToolConfirmationModal(): void
    {
        $this->setDefaultToolWarehouse();
        $validated = $this->validate($this->toolRules());
        $houseIds = array_map('intval', $validated['house_ids']);

        $tool = Tool::with('warehouse:id,name')->findOrFail($validated['tool_id']);
        $sourceWarehouseId = (int) ($validated['tool_warehouse_id'] ?: $tool->warehouse_id);
        $sourceBalance = $this->availableToolBalances($tool->id)->firstWhere('warehouse_id', $sourceWarehouseId);
        if (! $sourceBalance) {
            $this->addError('tool_warehouse_id', 'Pilih gudang asal alat yang tersedia.');

            return;
        }
        // D2: condition is display-only now — available_qty is the single source of truth
        $houses = House::with('cluster:id,name')->forUser(auth()->user())->whereIn('id', $houseIds)->get();
        if ($houses->count() !== count($houseIds)) {
            $this->addError('house_ids', 'Pilih rumah di cluster yang ditugaskan kepada Anda.');

            return;
        }
        $totalQuantity = (int) $validated['tool_quantity'] * count($houseIds);

        if ($sourceBalance->allocation_available_qty < $totalQuantity) {
            $this->addError('tool_quantity', 'Jumlah alat bebas di gudang asal tidak mencukupi. Total dibutuhkan: '.$totalQuantity.'. Tersedia: '.$sourceBalance->allocation_available_qty);

            return;
        }

        $completedHouses = $houses->filter(fn ($house) => ! $house->canReceiveAllocations());
        if ($completedHouses->isNotEmpty()) {
            $names = $completedHouses->pluck('name')->join(', ');
            $this->addError('house_ids', "Rumah berikut sudah selesai masa garansinya: {$names}");

            return;
        }

        $this->toolConfirmationData = [
            'houses' => $houses->pluck('name')->join(', '),
            'houseRows' => $houses->map(fn ($house) => [
                'name' => $house->name,
                'code' => $house->house_code,
                'cluster' => $house->cluster?->name ?? 'Tanpa cluster',
            ])->values()->all(),
            'houseCount' => count($houseIds),
            'clusters' => $houses->map(fn ($house) => $house->cluster?->name ?? 'Tanpa cluster')->unique()->join(', '),
            'toolName' => $tool->name,
            'toolCode' => $tool->code ?? '-',
            'warehouseName' => $sourceBalance->warehouse?->name ?? 'Belum ada gudang',
            'quantityPerHouse' => $validated['tool_quantity'],
            'totalQuantity' => $totalQuantity,
            'availableQty' => $sourceBalance->allocation_available_qty,
            'availableAfter' => $sourceBalance->allocation_available_qty - $totalQuantity,
            'notes' => $validated['tool_notes'],
        ];

        $this->showToolConfirmation = true;
    }

    public function saveTool(): void
    {
        if ($this->saving) {
            return;
        }

        $this->setDefaultToolWarehouse();
        $validated = $this->validate([
            ...$this->toolRules(),
            'toolAllocationProofImage' => 'required|image|max:5120',
        ], [
            'toolAllocationProofImage.required' => 'Foto bukti alokasi alat wajib diunggah.',
            'toolAllocationProofImage.image' => 'Berkas harus berupa foto.',
            'toolAllocationProofImage.max' => 'Ukuran foto maksimal 5 MB.',
        ]);
        $houseIds = array_map('intval', $validated['house_ids']);
        $quantity = (int) $validated['tool_quantity'];
        $proofPath = $this->toolAllocationProofImage->store('allocation-proofs', 'public');
        $this->saving = true;

        try {
            DB::transaction(function () use ($validated, $houseIds, $quantity, $proofPath) {
                $tool = Tool::lockForUpdate()->findOrFail($validated['tool_id']);
                $houses = House::forUser(auth()->user())->whereIn('id', $houseIds)->orderBy('id')->lockForUpdate()->get(['id', 'name', 'status', 'warranty_expires_at']);

                if ($houses->count() !== count($houseIds)) {
                    throw ValidationException::withMessages(['house_ids' => 'Salah satu rumah tujuan sudah tidak tersedia. Pilih ulang rumah.']);
                }

                $completedHouses = $houses->filter(fn ($house) => ! $house->canReceiveAllocations());
                if ($completedHouses->isNotEmpty()) {
                    throw ValidationException::withMessages([
                        'house_ids' => 'Rumah berikut sudah selesai masa garansinya: '.$completedHouses->pluck('name')->join(', '),
                    ]);
                }

                $totalQuantityRequired = $quantity * count($houseIds);

                $sourceWarehouseId = (int) ($validated['tool_warehouse_id'] ?: $tool->warehouse_id);
                $sourceBalance = ToolInventory::lockBalance($tool, $sourceWarehouseId);
                if ((int) $sourceBalance->available_qty < $totalQuantityRequired) {
                    throw ValidationException::withMessages([
                        'tool_quantity' => 'Jumlah alat bebas di gudang asal berubah atau tidak mencukupi.',
                    ]);
                }

                // C: reject duplicate active checkout for same tool + house (D5 void filter included)
                $existing = ToolUsageModel::where('tool_id', $validated['tool_id'])
                    ->whereIn('house_id', $houseIds)
                    ->whereNull('return_date')
                    ->whereNull('voided_at')
                    ->pluck('house_id');
                if ($existing->isNotEmpty()) {
                    throw ValidationException::withMessages([
                        'tool_quantity' => 'Alat ini masih dipinjam oleh rumah: '.House::whereIn('id', $existing)->pluck('name')->join(', '),
                    ]);
                }

                ToolInventory::checkout($tool, $sourceWarehouseId, $totalQuantityRequired);
                $houseById = $houses->keyBy('id');
                foreach ($houseIds as $houseId) {
                    $this->recordToolUsage($houseById[$houseId], $tool, $sourceWarehouseId, $quantity, $validated['tool_notes'], $proofPath);
                }

            });

            $this->showSuccess('Alat langsung dialokasikan ke '.count($houseIds).' rumah.', 'tool');
            $this->resetToolForm();
        } catch (ValidationException $e) {
            Storage::disk('public')->delete($proofPath);
            throw $e;
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($proofPath);
            $this->addError('tool_quantity', $e->getMessage());
        } finally {
            $this->saving = false;
        }
    }

    public function resetMaterialForm(): void
    {
        $this->material_id = '';
        $this->material_batch_id = '';
        $this->materialBatchMap = [];
        $this->material_quantity = 1;
        $this->material_notes = '';
        $this->material_taken_by = '';
        $this->materialPickerOpen = false;
        $this->showMaterialConfirmation = false;
        $this->materialConfirmationData = [];
        $this->materialAllocationProofImage = null;
        $this->resetValidation();
    }

    public function selectMaterial(int $materialId): void
    {
        $material = Material::query()->where('stock', '>', 0)->findOrFail($materialId);
        $this->material_id = (string) $material->id;
        $this->material_batch_id = '';
        $this->materialBatchMap = $this->getMaterialBatches()
            ->mapWithKeys(fn (StockIn $batch) => [$batch->id => [
                'remaining_quantity' => (float) $batch->remaining_quantity,
                'unit_price' => (float) $batch->unit_price,
                'entry_code' => $batch->entry_code,
                'warehouse' => $batch->warehouse?->name ?? 'Gudang tidak tersedia',
                'supplier' => $batch->supplier?->name ?? 'Tanpa supplier',
                'received_at' => $batch->received_at?->format('d/m/Y H:i') ?? '-',
            ]])
            ->all();
        $this->resetValidation();
    }

    public function resetToolForm(): void
    {
        $this->tool_id = '';
        $this->tool_warehouse_id = '';
        $this->tool_quantity = 1;
        $this->tool_notes = '';
        $this->showToolConfirmation = false;
        $this->toolConfirmationData = [];
        $this->toolAllocationProofImage = null;
        $this->toolPickerOpen = false;
        $this->resetValidation();
    }

    public function getActiveToolUsages()
    {
        if (empty($this->house_ids)) {
            return collect();
        }

        return ToolUsageModel::with(['house', 'tool', 'warehouse'])
            ->whereIn('house_id', $this->house_ids)
            ->whereHas('house', fn ($query) => $query->forUser(auth()->user()))
            ->whereNull('return_date')
            ->whereNull('voided_at')
            ->get();
    }

    public function showReturnConfirmationModal()
    {
        $activeUsages = $this->getActiveToolUsages();
        $selectedItems = [];

        foreach ($this->returnSelections as $usageId => $sel) {
            if (empty($sel['selected'])) {
                continue;
            }

            $usage = $activeUsages->firstWhere('id', $usageId);
            if (! $usage) {
                continue;
            }

            $qtyNormal = intval($sel['qty_normal'] ?? 0);
            $qtyBroken = intval($sel['qty_broken'] ?? 0);
            $qtyLost = intval($sel['qty_lost'] ?? 0);
            $total = $qtyNormal + $qtyBroken + $qtyLost;

            if ($total <= 0) {
                continue; // Nothing to return for this selection
            }

            if ($total > $usage->quantity) {
                $this->addError('returnSelections', 'Jumlah kondisi untuk '.$usage->tool->name.' melebihi jumlah dipinjam ('.$usage->quantity.').');

                return;
            }

            if ($qtyNormal < 0 || $qtyBroken < 0 || $qtyLost < 0) {
                $this->addError('returnSelections', 'Jumlah kondisi tidak boleh negatif untuk '.$usage->tool->name.'.');

                return;
            }

            $receivingWarehouseId = (int) ($sel['receiving_warehouse_id'] ?? ($usage->warehouse_source_recorded ? $usage->warehouse_id : null) ?? $usage->tool->warehouse_id);
            $receivingWarehouse = ($qtyNormal + $qtyBroken) > 0 ? Warehouse::find($receivingWarehouseId) : null;
            if (($qtyNormal + $qtyBroken) > 0 && ! $receivingWarehouse) {
                $this->addError('returnSelections', 'Pilih gudang penerima untuk alat '.$usage->tool->name.'.');

                return;
            }

            $selectedItems[] = [
                'usage_id' => $usage->id,
                'house_name' => $usage->house->name,
                'tool_name' => $usage->tool->name,
                'quantity' => $usage->quantity,
                'return_qty' => $total,
                'qty_normal' => $qtyNormal,
                'qty_broken' => $qtyBroken,
                'qty_lost' => $qtyLost,
                'receiving_warehouse_id' => $receivingWarehouseId,
                'receiving_warehouse_name' => $receivingWarehouse?->name,
                'notes' => $sel['notes'] ?? '',
            ];
        }

        if (empty($selectedItems)) {
            $this->addError('returnSelections', 'Pilih minimal satu alat untuk dikembalikan.');

            return;
        }

        $this->returnConfirmationData = $selectedItems;
        $this->showReturnConfirmation = true;
    }

    public function saveReturn()
    {
        if ($this->saving) {
            return;
        }
        $this->saving = true;

        try {
            DB::transaction(function () {
                foreach ($this->returnConfirmationData as $item) {
                    $this->recordToolReturn($item);
                }

                $this->showSuccess('Pengembalian alat berhasil dicatat.', 'tool');
            });

            $this->showReturnConfirmation = false;
            $this->resetReturnForm();
        } catch (\Exception $e) {
            $this->addError('returnSelections', $e->getMessage());
        } finally {
            $this->saving = false;
        }
    }

    private function recordToolReturn(array $item): void
    {
        $usage = ToolUsageModel::whereHas('house', fn ($query) => $query->forUser(auth()->user()))
            ->lockForUpdate()->findOrFail($item['usage_id']);
        if ($usage->return_date) {
            return;
        }

        $tool = Tool::lockForUpdate()->findOrFail($usage->tool_id);
        $qtyNormal = (int) $item['qty_normal'];
        $qtyBroken = (int) $item['qty_broken'];
        $qtyLost = (int) $item['qty_lost'];
        $receivingWarehouseId = (int) ($item['receiving_warehouse_id'] ?? 0);
        $returnQty = $qtyNormal + $qtyBroken + $qtyLost;
        $remainingQty = $usage->quantity - $returnQty;

        if ($remainingQty === 0) {
            $usage->update(['return_date' => now()->format('Y-m-d')]);
        } else {
            $usage->update(['quantity' => $returnQty, 'return_date' => now()->format('Y-m-d')]);
            ToolUsageModel::create([
                'transaction_code' => 'KLR-'.Str::ulid(),
                'dispatch_code' => $usage->dispatch_code,
                'dispatch_line_id' => $usage->dispatch_line_id,
                'house_id' => $usage->house_id,
                'tool_id' => $usage->tool_id,
                'warehouse_id' => $usage->warehouse_id,
                'warehouse_source_recorded' => $usage->warehouse_source_recorded,
                'user_id' => $usage->user_id,
                'quantity' => $remainingQty,
                'checkout_date' => $usage->checkout_date,
                'return_date' => null,
                'parent_usage_id' => $usage->id,
                'notes' => $usage->notes,
            ]);
        }

        ToolInventory::receive($tool, $receivingWarehouseId, $qtyNormal, $qtyBroken);
        foreach ([['quantity' => $qtyNormal, 'report_type' => 'normal', 'status' => 'fixed'], ['quantity' => $qtyBroken, 'report_type' => 'broken', 'status' => 'received'], ['quantity' => $qtyLost, 'report_type' => 'lost', 'status' => 'discarded']] as $log) {
            if ($log['quantity'] < 1) {
                continue;
            }
            if ($log['report_type'] === 'lost' && $tool->total_qty < $log['quantity']) {
                throw new \RuntimeException('Jumlah alat hilang melebihi total alat tercatat.');
            }
            if ($log['report_type'] === 'broken') {
                $tool->condition = 'rusak';
            }
            if ($log['report_type'] === 'lost') {
                $tool->total_qty -= $log['quantity'];
            }

            ToolReturnLog::create([
                'tool_id' => $tool->id,
                'house_id' => $usage->house_id,
                'tool_usage_id' => $usage->id,
                'reported_by' => auth()->id(),
                'receiving_warehouse_id' => $log['report_type'] === 'lost' ? null : $receivingWarehouseId,
                'received_at' => $log['report_type'] === 'lost' ? null : now(),
                'received_by_id' => $log['report_type'] === 'lost' ? null : auth()->id(),
                'resolved_by_id' => $log['report_type'] === 'lost' ? auth()->id() : null,
                'resolved_at' => $log['report_type'] === 'lost' ? now() : null,
                'quantity' => $log['quantity'],
                'report_type' => $log['report_type'],
                'status' => $log['status'],
                'notes' => $item['notes'] ?: null,
            ]);
        }
        $tool->save();
    }

    public function resetReturnForm()
    {
        $this->returnSelections = [];
        $this->showReturnConfirmation = false;
        $this->returnConfirmationData = [];
        $this->resetValidation();
    }

    public function toggleHouse($id)
    {
        $id = (int) $id;

        if ($this->activeTab === 'vendor-service' && $this->vendor_service_target === 'house') {
            $this->house_ids = in_array($id, $this->house_ids)
                ? []
                : ($this->getHouses()->contains('id', $id) ? [$id] : []);
            $this->resetValidation('house_ids');

            return;
        }

        if (in_array($id, $this->house_ids)) {
            $this->house_ids = array_values(array_diff($this->house_ids, [$id]));
        } elseif ($this->getHouses()->contains('id', $id)) {
            $this->house_ids[] = $id;
        }
        $this->syncMaterialBatchSelection();
    }

    public function openHousePicker(): void
    {
        if ($this->activeTab === 'spreadsheet') {
            $this->house_ids = collect($this->spreadsheetRows)
                ->pluck('house_id')->filter()->map(fn ($id) => (int) $id)->unique()->take(100)->values()->all();
        }

        $this->housePickerOpen = true;
    }

    public function closeHousePicker(): void
    {
        $this->housePickerOpen = false;

        if ($this->activeTab === 'spreadsheet') {
            $this->syncSpreadsheetHouseRows();
        }
    }

    public function selectVisibleHouses()
    {
        $ids = $this->housePickerData($this->getHouses())['houseResults']->pluck('id')->all();
        $this->house_ids = $this->activeTab === 'vendor-service' && $this->vendor_service_target === 'house'
            ? array_slice($ids, 0, 1)
            : array_values(array_unique(array_merge($this->house_ids, $ids)));
        $this->syncMaterialBatchSelection();
    }

    public function updated($property)
    {
        if (in_array($property, ['houseSearch', 'houseCluster', 'selectedHousesOnly'])) {
            $this->housePage = 1;
        }

        if ($property === 'material_quantity') {
            $this->syncMaterialBatchSelection();
        }

        if ($property === 'material_batch_id') {
            $this->resetValidation('material_batch_id');
        }

        if (preg_match('/^spreadsheetRows\.\d+\.house_id$/', $property)) {
            preg_match('/^spreadsheetRows\.(\d+)\.house_id$/', $property, $matches);
            $row = (int) $matches[1];

            $houseId = (string) ($this->spreadsheetRows[$row]['house_id'] ?? '');
            $duplicateHouse = $houseId !== '' && collect($this->spreadsheetRows)->contains(
                fn ($candidate, $candidateRow) => $candidateRow !== $row && (string) ($candidate['house_id'] ?? '') === $houseId,
            );
            if ($duplicateHouse) {
                $this->spreadsheetRows[$row]['house_id'] = '';
                $this->addError('spreadsheetRows', 'Rumah ini sudah dipilih pada baris lain.');
            } else {
                $this->resetValidation('spreadsheetRows');
            }

            $this->spreadsheetRows[$row]['materials'] = [];
            $this->spreadsheetRows[$row]['tools'] = [];
            $this->spreadsheetRows[$row]['returns'] = [];

            if ($this->activeTab === 'spreadsheet') {
                $this->house_ids = collect($this->spreadsheetRows)
                    ->pluck('house_id')->filter()->map(fn ($id) => (int) $id)->unique()->take(100)->values()->all();
                $this->syncSpreadsheetHouseRows();
            }
        }

        if (preg_match('/^spreadsheetRows\.(\d+)\.materials\.(\d+)\.material_id$/', $property, $matches)) {
            $this->spreadsheetRows[(int) $matches[1]]['materials'][(int) $matches[2]]['batch_id'] = '';
        }

        if (preg_match('/^spreadsheetRows\.(\d+)\.materials\.(\d+)\.(batch_id|quantity)$/', $property, $matches)) {
            $row = (int) $matches[1];
            $line = (int) $matches[2];
            $batchId = (int) ($this->spreadsheetRows[$row]['materials'][$line]['batch_id'] ?? 0);
            if ($batchId > 0) {
                $availableQuantity = $this->availableSpreadsheetBatchQuantity($row, $line, $batchId);
                $quantity = (float) ($this->spreadsheetRows[$row]['materials'][$line]['quantity'] ?? 0);
                if ($quantity > $availableQuantity + 0.001) {
                    $this->spreadsheetRows[$row]['materials'][$line]['quantity'] = number_format($availableQuantity, 2, '.', '');
                }
            }
        }
    }

    public function updatedActiveTab()
    {
        $this->noticeMessage = '';
        $this->reset('houseSearch', 'houseCluster', 'selectedHousesOnly', 'housePage');
        $this->house_ids = $this->getHouses()->whereIn('id', $this->house_ids)->pluck('id')->all();
        if ($this->activeTab === 'vendor-service' && $this->vendor_service_target === 'cluster') {
            $this->house_ids = [];
        } elseif ($this->activeTab === 'vendor-service' && $this->vendor_service_target === 'house') {
            $this->house_ids = array_slice($this->house_ids, 0, 1);
        }
        $this->syncMaterialBatchSelection();
        $this->resetValidation();
    }

    public function clearHouses()
    {
        $this->house_ids = [];
        $this->syncMaterialBatchSelection();
    }

    public function resetAll()
    {
        $this->house_ids = [];
        $this->housePickerOpen = false;
        $this->resetMaterialForm();
        $this->resetToolForm();
        $this->resetReturnForm();
    }

    // C: all 10 dead dispatch handlers removed (HEAD L429-477):
    // updatedMaterialQuantity, updatedMaterialId, updatedHouseIds, updatedMaterialNotes,
    // updatedUsageDate, updatedToolId, updatedToolQuantity, updatedToolNotes,
    // updatedCheckoutDate, updatedReturnDate — fired cost-calculated / tool-info-updated
    // with zero listeners; also made every keystroke on .live fields a network round-trip.

    public function getHouses()
    {
        $activeLoans = fn ($query) => $query->whereNull('return_date')->whereNull('voided_at');

        return House::query()
            ->forUser(auth()->user())
            ->with('cluster:id,name')
            ->withCount(['toolUsages as active_loans_count' => $activeLoans])
            ->when($this->activeTab === 'return', fn ($query) => $query->whereHas('toolUsages', $activeLoans))
            ->when($this->activeTab === 'spreadsheet', fn ($query) => $query->where(fn ($query) => $query->eligibleForAllocation()->orWhereHas('toolUsages', $activeLoans)))
            ->when(in_array($this->activeTab, ['allocation', 'material', 'tool'], true), fn ($query) => $query->eligibleForAllocation())
            ->when(! in_array($this->activeTab, ['return', 'spreadsheet', 'allocation', 'material', 'tool', 'vendor-service'], true), fn ($query) => $query->where('status', '!=', 'selesai'))
            ->get(['id', 'cluster_id', 'name', 'house_code', 'type', 'status', 'warranty_expires_at'])
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values();
    }

    protected function housePickerData($houses): array
    {
        // ponytail: metadata loads per render; move search and cluster filtering to SQL for larger catalogs.
        $clusters = $houses
            ->groupBy(fn ($house) => $house->cluster_id ? (string) $house->cluster_id : 'unassigned')
            ->map(fn ($rows, $id) => [
                'id' => $id,
                'name' => $rows->first()->cluster?->name ?? 'Tanpa cluster',
                'count' => $rows->count(),
            ])
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
        $normalize = fn ($value) => mb_strtolower(preg_replace('/[\s-]+/u', '', $value));
        $terms = preg_split('/\s+/u', trim($this->houseSearch), -1, PREG_SPLIT_NO_EMPTY);
        $filtered = ($this->houseCluster === 'all'
                ? $houses
                : $houses->filter(fn ($house) => ($house->cluster_id ? (string) $house->cluster_id : 'unassigned') === $this->houseCluster))
            ->filter(function ($house) use ($normalize, $terms) {
                $text = $normalize($house->name.' '.$house->house_code.' '.$house->type.' '.($house->cluster?->name ?? 'tanpa cluster'));

                return (! $this->selectedHousesOnly || in_array($house->id, $this->house_ids))
                    && collect($terms)->every(fn ($term) => str_contains($text, $normalize($term)));
            });
        $pages = max(1, (int) ceil($filtered->count() / 8));
        $this->housePage = min(max(1, $this->housePage), $pages);

        return [
            'houseClusters' => $clusters,
            'houseResults' => $filtered->forPage($this->housePage, 8),
            'houseResultCount' => $filtered->count(),
            'housePages' => $pages,
        ];
    }

    public function getMaterials()
    {
        return Material::with(['warehouse:id,name', 'supplier:id,name'])
            ->where('stock', '>', 0)
            ->whereHas('stockIns', fn ($query) => $query->whereNotNull('warehouse_id')->whereAvailableQuantity())
            ->orderBy('name')
            ->orderBy('unit_price')
            ->orderBy('id')
            ->get();
    }

    public function getMaterialBatches()
    {
        return $this->material_id
            ? StockIn::with(['warehouse:id,name', 'supplier:id,name', 'material:id,unit'])
                ->withReservedQuantity()
                ->where('material_id', $this->material_id)
                ->whereNotNull('warehouse_id')
                ->whereAvailableQuantity()
                ->orderBy('unit_price')
                ->orderBy('id')
                ->get()
            : collect();
    }

    private function syncMaterialBatchSelection(): void
    {
        $batches = collect($this->materialBatchMap);
        $required = (float) $this->material_quantity * count($this->house_ids);
        $current = (string) $this->material_batch_id;
        if ($current === '' || ! isset($batches[$current])) {
            return;
        }

        $this->resetValidation('material_batch_id');
        if ((float) $batches[$current]['remaining_quantity'] + 0.001 < $required) {
            $this->addError('material_batch_id', 'Batch terpilih tidak cukup untuk jumlah ini. Kurangi jumlah atau pilih batch lain.');
        }
    }

    public function getTools()
    {
        $tools = Tool::with(['warehouse:id,name', 'warehouseBalances.warehouse'])
            ->whereHas('warehouseBalances', fn ($query) => $query->where('available_qty', '>', 0))
            ->orderBy('name')
            ->get();

        foreach ($tools as $tool) {
            $balances = $this->availableToolBalances($tool->id);
            $tool->setAttribute('available_qty', (int) $balances->sum('allocation_available_qty'));
            $tool->setRelation('warehouseBalances', $balances);
        }

        return $tools->filter(fn ($tool) => $tool->available_qty > 0)->values();
    }

    public function updatedToolId($value): void
    {
        $this->tool_warehouse_id = (string) ($this->availableToolBalances((int) $value)->first()?->warehouse_id ?? '');
    }

    private function setDefaultToolWarehouse(): void
    {
        if (! $this->tool_id || $this->tool_warehouse_id) {
            return;
        }

        $this->updatedToolId($this->tool_id);
    }

    private function availableToolBalances(int $toolId)
    {
        $balances = ToolWarehouseBalance::with('warehouse:id,name')
            ->where('tool_id', $toolId)
            ->where('available_qty', '>', 0)
            ->orderBy('warehouse_id')
            ->get();
        return $balances->map(function ($balance) {
            $balance->setAttribute('allocation_available_qty', (int) $balance->available_qty);

            return $balance;
        })->filter(fn ($balance) => $balance->allocation_available_qty > 0)->values();
    }

    private function recordMaterialUsage(House $house, Material $material, ?int $stockInId, float $quantity, float $unitPrice, ?string $notes, ?string $proofPath = null, ?string $takenBy = null): void
    {
        MaterialUsageModel::create([
            'transaction_code' => 'KLR-'.Str::ulid(),
            'house_id' => $house->id,
            'material_id' => $material->id,
            'stock_in_id' => $stockInId,
            'user_id' => auth()->id(),
            'quantity' => $quantity,
            'unit_price_at_usage' => $unitPrice,
            'total_cost' => round($quantity * $unitPrice, 2),
            'usage_date' => now()->toDateString(),
            'notes' => $notes,
            'taken_by' => $takenBy,
            'proof_image' => $proofPath,
            'is_warranty' => $house->status === 'selesai' && $house->canReceiveAllocations(),
        ]);
    }

    private function recordToolUsage(House $house, Tool $tool, int $warehouseId, int $quantity, ?string $notes, ?string $proofPath = null): void
    {
        ToolUsageModel::create([
            'transaction_code' => 'KLR-'.Str::ulid(),
            'house_id' => $house->id,
            'tool_id' => $tool->id,
            'warehouse_id' => $warehouseId,
            'warehouse_source_recorded' => true,
            'user_id' => auth()->id(),
            'quantity' => $quantity,
            'checkout_date' => now()->toDateString(),
            'notes' => $notes,
            'proof_image' => $proofPath,
            'is_warranty' => $house->status === 'selesai' && $house->canReceiveAllocations(),
        ]);
    }

    private function showSuccess(string $message, string $historyType = ''): void
    {
        $this->noticeType = 'success';
        $this->noticeMessage = $message;
        $this->noticeHistoryType = $historyType;
        $this->noticeSequence++;
        session()->flash('success', $message);
    }

    public function render()
    {
        $houses = $this->getHouses();
        $materials = $this->getMaterials();
        $tools = $this->getTools();

        // Only query active tool usages when on the return tab
        $activeUsages = collect();
        if ($this->activeTab === 'return') {
            $this->house_ids = $houses->whereIn('id', $this->house_ids)->pluck('id')->all();
            $activeUsages = $this->getActiveToolUsages();

            // C: prune stale selections (usages returned/voided since last render)
            $activeIds = $activeUsages->pluck('id')->map(fn ($id) => (int) $id)->all();
            foreach (array_keys($this->returnSelections) as $usageId) {
                if (! in_array((int) $usageId, $activeIds, true)) {
                    unset($this->returnSelections[$usageId]);
                }
            }

            // C: default qty_normal to 0 (operator opts in per row)
            foreach ($activeUsages as $usage) {
                if (! isset($this->returnSelections[$usage->id])) {
                    $this->returnSelections[$usage->id] = [
                        'selected' => false,
                        'qty_normal' => 0,
                        'qty_broken' => 0,
                        'qty_lost' => 0,
                        'receiving_warehouse_id' => ($usage->warehouse_source_recorded ? $usage->warehouse_id : null) ?? $usage->tool->warehouse_id,
                        'notes' => '',
                    ];
                }
            }
        }

        $matNotes = MaterialUsageModel::whereHas('house', fn ($query) => $query->forUser(auth()->user()))
            ->whereNotNull('notes')->where('notes', '!=', '')->pluck('notes');
        $toolNotes = ToolUsageModel::whereHas('house', fn ($query) => $query->forUser(auth()->user()))
            ->whereNotNull('notes')->where('notes', '!=', '')->pluck('notes');
        $peruntukkanOptions = $matNotes->merge($toolNotes)->unique()->filter()->values()->all();
        $pengambilOptions = MaterialUsageModel::whereHas('house', fn ($query) => $query->forUser(auth()->user()))
            ->whereNotNull('taken_by')->where('taken_by', '!=', '')
            ->distinct()->orderBy('taken_by')->pluck('taken_by')->all();
        $toolWarehouseOptions = $this->tool_id
            ? $this->availableToolBalances((int) $this->tool_id)
            : collect();
        $spreadsheetMaterialIds = collect($this->spreadsheetRows)
            ->flatMap(fn ($row) => collect($row['materials'] ?? [])->pluck('material_id'))
            ->filter()->map(fn ($id) => (int) $id)->unique()->values();
        $spreadsheetBatches = $spreadsheetMaterialIds->isEmpty()
            ? collect()
            : StockIn::with(['warehouse:id,name', 'supplier:id,name'])
                ->withReservedQuantity()
                ->whereIn('material_id', $spreadsheetMaterialIds)
                ->whereNotNull('warehouse_id')->whereAvailableQuantity()->orderBy('id')->get()->groupBy('material_id');
        $spreadsheetHouseIds = collect($this->spreadsheetRows)->pluck('house_id')->filter()->map(fn ($id) => (int) $id)->unique()->values();
        $spreadsheetUsages = $this->activeTab !== 'spreadsheet' || $spreadsheetHouseIds->isEmpty()
            ? collect()
            : ToolUsageModel::with(['tool:id,name,code', 'warehouse:id,name'])
                ->whereIn('house_id', $spreadsheetHouseIds)
                ->whereNull('return_date')->whereNull('voided_at')
                ->whereHas('house', fn ($query) => $query->forUser(auth()->user()))
                ->orderBy('checkout_date')->get()->groupBy('house_id');
        $supplierNames = Supplier::orderBy('name')->pluck('name')->unique()->values()->all();
        $clusters = Cluster::query()
            ->when(auth()->user()->role !== 'admin', fn ($query) => $query->whereKey(auth()->user()->cluster_id ?? 0))
            ->orderBy('name')->get(['id', 'name']);

        return view('livewire.logistik.transaksi-logistik', array_merge(
            compact('houses', 'materials', 'tools', 'activeUsages', 'peruntukkanOptions', 'pengambilOptions', 'toolWarehouseOptions', 'supplierNames', 'clusters', 'spreadsheetBatches', 'spreadsheetUsages'),
            $this->housePickerData($houses),
            [
                'warehouses' => Warehouse::orderBy('name')->get(['id', 'name']),
                'clusterAssignmentMissing' => auth()->user()->role === 'logistik' && ! auth()->user()->cluster_id,
            ],
        ))
            ->layout('layouts.app', ['title' => 'Alokasi Material & Alat']);
    }
}

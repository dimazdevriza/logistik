<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\Material;
use App\Models\MaterialUsage;
use App\Models\StockIn;
use App\Models\Supplier;
use App\Support\ImportTransaction;
use App\Support\ImportHouseIdentity;
use App\Support\ImportWarehouse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\HasReferencesToOtherSheets;
use Maatwebsite\Excel\Concerns\SkipsUnknownSheets;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\IOFactory;

class MaterialImport implements ToCollection, WithHeadingRow, WithMultipleSheets, SkipsUnknownSheets, WithCalculatedFormulas, HasReferencesToOtherSheets
{
    private const MASUK_SHEETS = ['barang masuk', 'material masuk', 'm.masuk', 'rincian masuk'];

    private const KELUAR_SHEETS = ['barang keluar', 'm.keluar', 'pemakaian material', 'pemakaian', 'pengeluaran'];

    public int $totalRows = 0;

    public int $successfulRows = 0;

    public int $skippedRows = 0;

    public int $materialsImported = 0;

    public int $transactionsImported = 0;

    public array $rowLogs = [];

    public function __construct(
        public ?string $filePath = null,
        public ?string $targetSheet = null,
    ) {}

    public function sheets(): array
    {
        if ($this->targetSheet) {
            return [$this->targetSheet => new MaterialSheetImport($this, self::classifySheet($this->targetSheet) ?? 'auto')];
        }

        if ($this->filePath && file_exists($this->filePath) && ($detected = $this->detectSheetsInFile())) {
            return $detected;
        }

        return [
            'BARANG MASUK' => new MaterialSheetImport($this, 'masuk'),
            'Material Masuk' => new MaterialSheetImport($this, 'masuk'),
            'M.MASUK' => new MaterialSheetImport($this, 'masuk'),
            'Rincian Masuk' => new MaterialSheetImport($this, 'masuk'),
            'BARANG KELUAR' => new MaterialSheetImport($this, 'keluar'),
            'Barang Keluar' => new MaterialSheetImport($this, 'keluar'),
            'M.KELUAR' => new MaterialSheetImport($this, 'keluar'),
            'm.keluar' => new MaterialSheetImport($this, 'keluar'),
            'Pemakaian Material' => new MaterialSheetImport($this, 'keluar'),
            'PEMAKAIAN' => new MaterialSheetImport($this, 'keluar'),
            'Pengeluaran' => new MaterialSheetImport($this, 'keluar'),
            0 => new MaterialSheetImport($this, 'auto'),
        ];
    }

    public function onUnknownSheet($sheetName) {}

    private static function classifySheet(string $name, bool $partial = false): ?string
    {
        $lower = strtolower(trim($name));
        if (in_array($lower, self::MASUK_SHEETS, true) || ($partial && str_contains($lower, 'masuk'))) {
            return 'masuk';
        }
        if (in_array($lower, self::KELUAR_SHEETS, true) || ($partial && (str_contains($lower, 'keluar') || str_contains($lower, 'pemakaian')))) {
            return 'keluar';
        }

        return null;
    }

    public function detectSheetsInFile(): array
    {
        if (! $this->filePath || ! file_exists($this->filePath)) {
            return [];
        }

        try {
            $reader = IOFactory::createReaderForFile($this->filePath);
            $names = method_exists($reader, 'listWorksheetNames') ? $reader->listWorksheetNames($this->filePath) : [];
            if (empty($names)) {
                return [];
            }

            $sheets = [];
            foreach (['masuk', 'keluar'] as $targetType) {
                foreach ($names as $name) {
                    if (self::classifySheet($name) === $targetType) {
                        $sheets[$name] = new MaterialSheetImport($this, $targetType);
                        break;
                    }
                }
            }

            if (empty($sheets)) {
                foreach ($names as $name) {
                    if ($type = self::classifySheet($name, true)) {
                        $sheets[$name] = new MaterialSheetImport($this, $type);
                    }
                }
            }

            return $sheets ?: [$names[0] => new MaterialSheetImport($this, 'auto')];
        } catch (\Throwable) {
            return [];
        }
    }

    public static function parseNumber(mixed $val): float
    {
        if (is_numeric($val)) {
            return (float) $val;
        }
        if (! is_string($val)) {
            return 0.0;
        }
        $val = trim($val);
        if ($val === '' || $val === '-' || $val === '—') {
            return 0.0;
        }
        $hasCurrency = (bool) preg_match('/\b(rp|idr)\b/i', $val);
        $cleaned = preg_replace('/[^\d,.-]/', '', $val);
        if ($cleaned === '') {
            return 0.0;
        }
        if (str_contains($cleaned, '.') && str_contains($cleaned, ',')) {
            $cleaned = str_replace(['.', ','], ['', '.'], $cleaned);
        } elseif (str_contains($cleaned, ',')) {
            $cleaned = str_replace(',', '.', $cleaned);
        } elseif (substr_count($cleaned, '.') > 1 || ($hasCurrency && preg_match('/^-?\d{1,3}(\.\d{3})+$/', $cleaned))) {
            $cleaned = str_replace('.', '', $cleaned);
        }

        return (float) $cleaned;
    }

    public function collection(Collection $rows)
    {
        $this->processCollection($rows, 'auto');
    }

    public function processCollection(Collection $rows, string $sheetType = 'auto')
    {
        if ($rows->isEmpty()) {
            return;
        }

        $userId = Auth::id() ?? 1;
        DB::transaction(function () use ($rows, $userId, $sheetType) {
            $customHeaders = null;
            foreach ($rows as $index => $row) {
                $rowNum = $index + 2; // Accounting for 1-based index + header row

                // Convert row to array & sanitize keys to lower case trimmed without spaces/underscores
                $rowData = [];
                $rawValues = [];
                foreach ($row as $key => $val) {
                    $cleanKey = strtolower(str_replace(['_', ' ', '/', '-', '.', '(', ')'], '', trim((string) $key)));
                    $trimmedVal = is_string($val) ? trim($val) : $val;
                    $rowData[$cleanKey] = $trimmedVal;
                    $rawValues[] = $trimmedVal;
                }

                $nonEmptyCount = count(array_filter($rawValues, static fn ($v) => $v !== null && trim((string) $v) !== ''));
                if ($nonEmptyCount === 0) {
                    continue;
                }

                if ($customHeaders !== null) {
                    $rowData = [];
                    foreach ($customHeaders as $colIdx => $headerKey) {
                        if ($headerKey !== '') {
                            $rowData[$headerKey] = $rawValues[$colIdx] ?? null;
                        }
                    }
                } else {
                    $normalizedVals = array_map(
                        static fn ($v) => is_string($v) ? strtolower(str_replace(['_', ' ', '/', '-', '.', '(', ')'], '', $v)) : '',
                        $rawValues
                    );
                    if (in_array('tanggal', $normalizedVals, true) && (in_array('kodebarang', $normalizedVals, true) || in_array('namabarang', $normalizedVals, true) || in_array('namamaterial', $normalizedVals, true))) {
                        $customHeaders = $normalizedVals;
                        continue;
                    }
                    $firstCell = strtolower(trim((string) ($rawValues[0] ?? '')));
                    if ($firstCell === '' || str_starts_with($firstCell, 'format impor') || str_starts_with($firstCell, 'laporan catatan') || str_starts_with($firstCell, 'catatan material') || str_starts_with($firstCell, 'diekspor pada') || str_starts_with($firstCell, 'filter aktif')) {
                        if ($nonEmptyCount <= 1) {
                            continue;
                        }
                    }
                }

                $isKeluarFormat = array_key_exists('namabarang', $rowData)
                    || array_key_exists('volume', $rowData)
                    || array_key_exists('blokrumah', $rowData)
                    || array_key_exists('pengambil', $rowData)
                    || array_key_exists('keteranganpekerjaan', $rowData)
                    || array_key_exists('suratjalan', $rowData)
                    || array_key_exists('nonota', $rowData);

                // Detect and silently skip template guide row (*DI ISI / *LINK) or export footer row (TOTAL / Total Biaya)
                $isGuideRow = false;
                foreach ($rawValues as $val) {
                    if (is_string($val) && in_array(strtolower(trim($val)), ['*di isi', '*link', 'total', 'total biaya'], true)) {
                        $isGuideRow = true;
                        break;
                    }
                }
                if ($isGuideRow) {
                    $this->skippedRows++;
                    continue;
                }

                $normalizeText = static function ($value) {
                    if ($value === null) {
                        return null;
                    }
                    $value = is_string($value) ? trim($value) : $value;
                    if (is_string($value) && (str_starts_with($value, '=') || in_array(strtolower($value), ['', '-', '—', '*di isi', '*link', '0'], true))) {
                        return null;
                    }

                    return $value;
                };

                // Header detection based on normalized keys
                // Mode A: Material Inventory item
                $name = $normalizeText($rowData['namamaterial'] ?? $rowData['namabarang'] ?? $rowData['nama'] ?? $rowData['material'] ?? null);
                $code = $normalizeText($rowData['kodematerial'] ?? $rowData['kodebarang'] ?? $rowData['kode'] ?? null);
                $categoryName = $normalizeText($rowData['kategori'] ?? (($isKeluarFormat || $sheetType === 'masuk') ? 'Material Umum' : null));
                $unit = $normalizeText($rowData['satuan'] ?? 'pcs') ?: 'pcs';
                $unitPrice = self::parseNumber($rowData['hargasatuan'] ?? $rowData['harga'] ?? 0);
                $stock = ($isKeluarFormat || $sheetType === 'masuk') ? 0 : self::parseNumber($rowData['sisastok'] ?? $rowData['stok'] ?? $rowData['jumlah'] ?? 0);
                $supplierName = $normalizeText($rowData['supplier'] ?? $rowData['pemasok'] ?? $rowData['tokosupplier'] ?? null);

                // Mode B: Transaction record (Keluar / Masuk)
                $txType = strtolower((string) ($rowData['jenis'] ?? $rowData['tipe'] ?? $rowData['tipetransaksi'] ?? ''));
                $rawHouse = $rowData['unitrumah'] ?? $rowData['rumah'] ?? $rowData['unit'] ?? $rowData['referensi'] ?? $rowData['blokrumah'] ?? null;
                if ($rawHouse === null && isset($rowData['referensirumahsupplier'])) {
                    if (in_array($txType, ['keluar', 'barang keluar', 'alokasi', 'out', 'usage'], true)) {
                        $rawHouse = $rowData['referensirumahsupplier'];
                    } elseif ($supplierName === null) {
                        $supplierName = $normalizeText($rowData['referensirumahsupplier']);
                    }
                }
                $houseName = is_string($rawHouse) ? trim($rawHouse) : (is_numeric($rawHouse) ? (string) $rawHouse : null);
                if ($houseName !== null && in_array(strtolower($houseName), ['*di isi', '*link', ''], true)) {
                    $houseName = null;
                }
                $clusterName = $normalizeText($rowData['cluster'] ?? $rowData['namacluster'] ?? null);
                $takenBy = $normalizeText($rowData['pengambil'] ?? $rowData['penanggungjawab'] ?? $rowData['mandor'] ?? null);
                $txNotes = $normalizeText($rowData['catatan'] ?? $rowData['keterangan'] ?? $rowData['keteranganpekerjaan'] ?? $rowData['suratjalan'] ?? $rowData['nonota'] ?? null)
                    ?? ($sheetType === 'masuk' ? 'Stok Masuk dari Excel' : 'Import dari Excel');
                $txDateRaw = $rowData['tanggal'] ?? $rowData['waktu'] ?? $rowData['waktumasuk'] ?? $rowData['waktuterima'] ?? $rowData['tanggalmasuk'] ?? $rowData['tanggalterima'] ?? $rowData['0'] ?? $rowData[''] ?? null;
                $transactionCodeInput = $normalizeText($rowData['kodetransaksi'] ?? $rowData['kodepenerimaantransaksi'] ?? null);

                if (! $name && ! $code) {
                    $this->skippedRows++;
                    $this->rowLogs[] = [
                        'row' => $rowNum,
                        'status' => 'skipped',
                        'item' => '—',
                        'message' => 'Baris kosong / Nama material tidak ditemukan.',
                    ];

                    continue;
                }

                // Transaction resolution
                if ($sheetType === 'masuk') {
                    $isIncoming = true;
                    $isOutgoing = false;
                } elseif ($sheetType === 'keluar') {
                    $isIncoming = false;
                    $isOutgoing = true;
                } else {
                    $isIncoming = in_array($txType, ['masuk', 'barang masuk', 'saldo warisan', 'restock', 'in'], true)
                        || array_key_exists('qtymasuk', $rowData)
                        || (! $houseName && array_key_exists('tanggal', $rowData) && (array_key_exists('totalbiaya', $rowData) || array_key_exists('suratjalan', $rowData) || array_key_exists('nonota', $rowData)));
                    $isOutgoing = in_array($txType, ['keluar', 'barang keluar', 'alokasi', 'out', 'usage'], true)
                        || ($houseName && ! $isIncoming);
                    if (! $isIncoming && ! $isOutgoing && $isKeluarFormat) {
                        $isOutgoing = true;
                    }
                }

                $isTransaction = $isIncoming || $isOutgoing;
                $warehouseId = ImportWarehouse::resolve($rowData, $rowNum, (string) ($name ?? $code), $isTransaction);

                if (! $name && $code) {
                    $existingByCode = Material::where('warehouse_id', $warehouseId)->where('code', $code)->first();
                    if (! $existingByCode) {
                        $this->skippedRows++;
                        $this->rowLogs[] = [
                            'row' => $rowNum,
                            'status' => 'skipped',
                            'item' => $code,
                            'message' => "Kode material '{$code}' belum terdaftar dan Nama Barang kosong.",
                        ];

                        continue;
                    }
                    $name = $existingByCode->name;
                }

                $this->totalRows++;

                if (! $isTransaction && $stock < 0) {
                    throw new \RuntimeException("Baris {$rowNum} ({$name}): Saldo awal negatif perlu direkonsiliasi sebelum impor.");
                }
                $txDate = $isTransaction
                    ? ImportTransaction::date($txDateRaw, $rowNum, (string) $name)
                    : null;

                // Resolve or create Category
                $category = null;
                if ($categoryName) {
                    $category = Category::firstOrCreate(
                        ['name' => $categoryName],
                        ['type' => 'material']
                    );
                }

                // Resolve or create Supplier
                $supplier = null;
                if ($supplierName) {
                    $supplier = Supplier::firstOrCreate(['name' => $supplierName]);
                }

                // Keep differently priced purchases as separate inventory rows.
                $materialQuery = Material::where('warehouse_id', $warehouseId)
                    ->where('name', $name)
                    ->where('unit', $unit);
                if ($unitPrice > 0) {
                    $materialQuery->where('unit_price', $unitPrice);
                }
                if ($supplier) {
                    $materialQuery->where('supplier_id', $supplier->id);
                } else {
                    $materialQuery->whereNull('supplier_id');
                }

                // Historical outgoing rows may carry their historical price rather than
                // the current batch price. Use the matching code if unique item in warehouse.
                $material = $materialQuery->lockForUpdate()->first();
                if (! $material && $code) {
                    $material = Material::where('warehouse_id', $warehouseId)
                        ->where('code', $code)
                        ->lockForUpdate()
                        ->first();
                }
                if (! $material) {
                    $sameName = Material::where('warehouse_id', $warehouseId)
                        ->where('name', $name)
                        ->lockForUpdate()
                        ->get();
                    if ($sameName->count() === 1) {
                        $material = $sameName->first();
                    } elseif ($sameName->count() > 1 && $isOutgoing) {
                        throw new \RuntimeException("Baris {$rowNum} ({$name}): Beberapa harga material ditemukan. Isi Kode Material atau Harga Satuan yang tepat.");
                    }
                }
                $isNewMaterial = false;

                if (! $material) {
                    $material = Material::create([
                        'code' => $code,
                        'name' => $name,
                        'category_id' => $category?->id,
                        'supplier_id' => $supplier?->id,
                        'unit' => $unit ?: 'pcs',
                        'unit_price' => $unitPrice,
                        'stock' => 0,
                        'warehouse_id' => $warehouseId,
                    ]);
                    $this->materialsImported++;
                    $isNewMaterial = true;
                } else {
                    // Update existing material price if provided
                    if ($unitPrice > 0) {
                        $material->unit_price = $unitPrice;
                    }
                    if ($category && ! $material->category_id) {
                        $material->category_id = $category->id;
                    }
                    if ($supplier && ! $material->supplier_id) {
                        $material->supplier_id = $supplier->id;
                    }
                    if ($code && ! $material->code) {
                        $material->code = $code;
                    }
                    $material->save();
                }

                $logDetail = $isNewMaterial ? 'Material baru ditambahkan' : 'Material diperbarui';

                // If this row represents a transaction (Catatan Log: Restock / Allocation)
                if ($isIncoming) {
                    $hasVolumeCol = array_key_exists('volume', $rowData) || array_key_exists('qtymasuk', $rowData) || array_key_exists('qty', $rowData);
                    $volumeRaw = $hasVolumeCol
                        ? ($rowData['volume'] ?? $rowData['qtymasuk'] ?? $rowData['qty'] ?? null)
                        : ($rowData['jumlah'] ?? $rowData['sisastok'] ?? $rowData['stok'] ?? null);
                    if (! $hasVolumeCol && $volumeRaw === null && ! $isKeluarFormat && $sheetType !== 'masuk') {
                        $qty = max(0.01, $stock > 0 ? $stock : 1);
                    } else {
                        $qty = self::parseNumber($volumeRaw);
                        if ($qty < 0.01) {
                            throw new \RuntimeException(
                                "Baris {$rowNum} ({$material->name}): Volume stok masuk wajib berupa angka minimal 0.01. Seluruh impor dibatalkan."
                            );
                        }
                        $qty = round($qty, 2);
                    }
                    $price = $unitPrice > 0 ? $unitPrice : floatval($material->unit_price ?? 0);
                    $total = $qty * $price;

                    $transactionCode = ImportTransaction::code('MSK', $transactionCodeInput, [
                        'type' => 'masuk',
                        'material_id' => $material->id,
                        'supplier_id' => $supplier?->id ?? $material->supplier_id,
                        'date' => $txDate,
                        'quantity' => number_format($qty, 2, '.', ''),
                        'unit_price' => number_format($price, 2, '.', ''),
                        'row' => $rowNum,
                    ], $rowNum, $material->name);
                    $receivedAt = ImportTransaction::dateTime(
                        $rowData['waktumasuk'] ?? $rowData['waktuterima'] ?? $rowData['tanggalterima'] ?? $rowData['tanggalmasuk'] ?? $txDateRaw,
                        $rowNum,
                        $material->name,
                    );

                    $stockIn = StockIn::firstOrCreate([
                        'transaction_code' => $transactionCode,
                        'material_id' => $material->id,
                    ], [
                        'warehouse_id' => $warehouseId,
                        'supplier_id' => $supplier?->id ?? $material->supplier_id,
                        'user_id' => $userId,
                        'quantity' => $qty,
                        'remaining_quantity' => $qty,
                        'unit_price' => $price,
                        'total_cost' => $total,
                        'date' => $txDate,
                        'received_at' => $receivedAt,
                        'notes' => $txNotes,
                    ]);

                    if (! $stockIn->wasRecentlyCreated) {
                        $this->skipDuplicate($rowNum, $material->name);

                        continue;
                    }

                    $material->increment('stock', $qty);
                    $this->transactionsImported++;
                    $logDetail .= " + Restock ({$qty} {$material->unit})";
                } elseif ($isOutgoing) {
                    if (! $houseName) {
                        throw new \RuntimeException("Baris {$rowNum} ({$material->name}): Blok rumah wajib diisi untuk transaksi keluar.");
                    }

                    $house = ImportHouseIdentity::resolve((string) $houseName, $rowNum, $material->name, $clusterName ? (string) $clusterName : null);
                    $hasVolumeCol = array_key_exists('volume', $rowData) || array_key_exists('qtykeluar', $rowData) || array_key_exists('qty', $rowData);
                    $qty = max(0.01, $hasVolumeCol
                        ? self::parseNumber($rowData['volume'] ?? $rowData['qtykeluar'] ?? $rowData['qty'] ?? 1)
                        : ($stock > 0 ? $stock : self::parseNumber($rowData['jumlah'] ?? $rowData['sisastok'] ?? $rowData['stok'] ?? 1)));
                    $qty = round($qty, 2);
                    $price = $unitPrice > 0 ? $unitPrice : floatval($material->unit_price ?? 0);
                    $total = $qty * $price;

                    $transactionCode = ImportTransaction::code('KLR', $transactionCodeInput, [
                        'type' => 'keluar',
                        'material_id' => $material->id,
                        'house_id' => $house->id,
                        'date' => $txDate,
                        'quantity' => number_format($qty, 2, '.', ''),
                        'unit_price' => number_format($price, 2, '.', ''),
                    ], $rowNum, $material->name);

                    if (MaterialUsage::where('transaction_code', $transactionCode)->where('house_id', $house->id)->exists()) {
                        $this->skipDuplicate($rowNum, $material->name);

                        continue;
                    }

                    $autoStockIn = null;
                    if ((float) $material->stock < $qty) {
                        $shortfall = round($qty - max(0.0, (float) $material->stock), 2);
                        $autoMasukCode = ImportTransaction::code('MSK', null, [
                            'type' => 'auto_masuk',
                            'keluar_code' => $transactionCode,
                            'material_id' => $material->id,
                            'house_id' => $house->id,
                            'date' => $txDate,
                            'quantity' => number_format($shortfall, 2, '.', ''),
                            'unit_price' => number_format($price, 2, '.', ''),
                        ], $rowNum, $material->name);
                        $receivedAt = ImportTransaction::dateTime(
                            $rowData['waktumasuk'] ?? $rowData['waktuterima'] ?? $rowData['tanggalterima'] ?? $rowData['tanggalmasuk'] ?? $txDateRaw,
                            $rowNum,
                            $material->name,
                        );
                        $autoStockIn = StockIn::firstOrCreate([
                            'transaction_code' => $autoMasukCode,
                            'material_id' => $material->id,
                        ], [
                            'entry_type' => 'receipt',
                            'warehouse_id' => $warehouseId,
                            'supplier_id' => $supplier?->id ?? $material->supplier_id,
                            'user_id' => $userId,
                            'quantity' => $shortfall,
                            'remaining_quantity' => $shortfall,
                            'unit_price' => $price,
                            'total_cost' => round($shortfall * $price, 2),
                            'date' => $txDate,
                            'received_at' => $receivedAt,
                            'notes' => "Stok masuk otomatis dari impor Barang Keluar ({$house->name})",
                        ]);

                        if ($autoStockIn->wasRecentlyCreated) {
                            $material->increment('stock', $shortfall);
                            $this->transactionsImported++;
                            $logDetail .= " + Stok Masuk Otomatis ({$shortfall} {$material->unit})";
                        }
                    }

                    $remainingToDeduct = $qty;
                    $primaryBatchId = null;
                    $activeBatches = StockIn::where('material_id', $material->id)
                        ->where('remaining_quantity', '>', 0)
                        ->orderBy('id')
                        ->lockForUpdate()
                        ->get();
                    foreach ($activeBatches as $batch) {
                        if ($remainingToDeduct <= 0) {
                            break;
                        }
                        $deduct = min((float) $batch->remaining_quantity, $remainingToDeduct);
                        if ($deduct > 0) {
                            $batch->decrement('remaining_quantity', $deduct);
                            $remainingToDeduct = round($remainingToDeduct - $deduct, 2);
                            $primaryBatchId ??= $batch->id;
                        }
                    }
                    $primaryBatchId ??= $autoStockIn?->id;

                    $usage = MaterialUsage::firstOrCreate([
                        'transaction_code' => $transactionCode,
                        'house_id' => $house->id,
                    ], [
                        'material_id' => $material->id,
                        'stock_in_id' => $primaryBatchId,
                        'user_id' => $userId,
                        'quantity' => $qty,
                        'unit_price_at_usage' => $price,
                        'total_cost' => $total,
                        'usage_date' => $txDate,
                        'notes' => $txNotes,
                        'taken_by' => $takenBy,
                    ]);

                    if (! $usage->wasRecentlyCreated) {
                        $this->skipDuplicate($rowNum, $material->name);

                        continue;
                    }

                    $material->decrement('stock', $qty);
                    $this->transactionsImported++;
                    $logDetail .= " + Alokasi ke {$house->name} ({$qty} {$material->unit})";
                } elseif ($stock > 0 && (float) $material->stock === 0.0) {
                    $price = $unitPrice > 0 ? $unitPrice : (float) $material->unit_price;
                    $transactionCode = ImportTransaction::code('MAT', $transactionCodeInput, [
                        'type' => 'opening_balance',
                        'material_id' => $material->id,
                        'warehouse_id' => $warehouseId,
                        'supplier_id' => $supplier?->id ?? $material->supplier_id,
                        'quantity' => number_format($stock, 2, '.', ''),
                        'unit_price' => number_format($price, 2, '.', ''),
                    ], $rowNum, $material->name);
                    $entryCode = 'OPEN-MAT-'.strtoupper(substr(hash('sha256', $material->id.':'.$transactionCode), 0, 26));
                    $openingBatch = StockIn::firstOrCreate([
                        'transaction_code' => $transactionCode,
                        'material_id' => $material->id,
                    ], [
                        'entry_code' => $entryCode,
                        'entry_type' => 'opening_balance',
                        'warehouse_id' => $warehouseId,
                        'supplier_id' => $supplier?->id ?? $material->supplier_id,
                        'user_id' => $userId,
                        'quantity' => $stock,
                        'remaining_quantity' => $stock,
                        'unit_price' => $price,
                        'total_cost' => 0,
                        'date' => config('logistics.migration_cutoff_date', now()->toDateString()),
                        'received_at' => null,
                        'notes' => 'Saldo awal hasil impor; waktu dan bukti kedatangan tidak tercatat.',
                    ]);

                    if (! $openingBatch->wasRecentlyCreated) {
                        $this->skipDuplicate($rowNum, $material->name);

                        continue;
                    }

                    $material->increment('stock', $stock);
                    $this->transactionsImported++;
                    $logDetail .= " + Saldo awal ({$stock} {$material->unit})";
                }

                $this->successfulRows++;
                $this->rowLogs[] = [
                    'row' => $rowNum,
                    'status' => 'success',
                    'item' => $name,
                    'message' => $logDetail,
                ];
            }
        });
    }

    private function skipDuplicate(int $row, string $item): void
    {
        $this->skippedRows++;
        $this->rowLogs[] = [
            'row' => $row,
            'status' => 'skipped',
            'item' => $item,
            'message' => 'Transaksi sudah tercatat. Stok tidak diubah.',
        ];
    }
}

class MaterialSheetImport implements ToCollection, WithHeadingRow, WithCalculatedFormulas, HasReferencesToOtherSheets
{
    public function __construct(
        private MaterialImport $parent,
        private string $sheetType = 'auto'
    ) {}

    public function collection(Collection $rows)
    {
        $this->parent->processCollection($rows, $this->sheetType);
    }
}

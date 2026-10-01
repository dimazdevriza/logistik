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
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class MaterialImport implements ToCollection, WithHeadingRow
{
    public int $totalRows = 0;

    public int $successfulRows = 0;

    public int $skippedRows = 0;

    public int $materialsImported = 0;

    public int $transactionsImported = 0;

    public array $rowLogs = [];

    public function collection(Collection $rows)
    {
        if ($rows->isEmpty()) {
            return;
        }

        $userId = Auth::id() ?? 1;
        DB::transaction(function () use ($rows, $userId) {
            foreach ($rows as $index => $row) {
                $rowNum = $index + 2; // Accounting for 1-based index + header row

                // Convert row to array & sanitize keys to lower case trimmed without spaces/underscores
                $rowData = [];
                foreach ($row as $key => $val) {
                    $cleanKey = strtolower(str_replace(['_', ' ', '/', '-', '.'], '', trim((string) $key)));
                    $rowData[$cleanKey] = is_string($val) ? trim($val) : $val;
                }

                $isKeluarFormat = array_key_exists('namabarang', $rowData)
                    || array_key_exists('volume', $rowData)
                    || array_key_exists('blokrumah', $rowData);
                $normalizeText = static function ($value) {
                    if ($value === null) {
                        return null;
                    }
                    $value = is_string($value) ? trim($value) : $value;
                    if (is_string($value) && in_array(strtolower($value), ['', '-', '—', '*di isi', '*link', '0'], true)) {
                        return null;
                    }

                    return $value;
                };

                // Header detection based on normalized keys
                // Mode A: Material Inventory item
                $name = $normalizeText($rowData['namamaterial'] ?? $rowData['namabarang'] ?? $rowData['nama'] ?? $rowData['material'] ?? null);
                $code = $normalizeText($rowData['kodematerial'] ?? $rowData['kodebarang'] ?? $rowData['kode'] ?? null);
                $categoryName = $normalizeText($rowData['kategori'] ?? ($isKeluarFormat ? 'Material Umum' : null));
                $unit = $normalizeText($rowData['satuan'] ?? 'pcs') ?: 'pcs';
                $unitPrice = floatval($rowData['hargasatuan'] ?? $rowData['harga'] ?? 0);
                $stock = $isKeluarFormat ? 0 : floatval($rowData['sisastok'] ?? $rowData['stok'] ?? $rowData['jumlah'] ?? 0);
                $supplierName = $normalizeText($rowData['supplier'] ?? $rowData['pemasok'] ?? $rowData['tokosupplier'] ?? null);

                // Mode B: Transaction record (Keluar / Masuk)
                $txType = strtolower((string) ($rowData['jenis'] ?? $rowData['tipe'] ?? $rowData['tipetransaksi'] ?? ($isKeluarFormat ? 'keluar' : '')));
                $houseName = $normalizeText($rowData['unitrumah'] ?? $rowData['rumah'] ?? $rowData['unit'] ?? $rowData['referensi'] ?? $rowData['blokrumah'] ?? null);
                $clusterName = $normalizeText($rowData['cluster'] ?? $rowData['namacluster'] ?? null);
                $txNotes = $normalizeText($rowData['catatan'] ?? $rowData['keterangan'] ?? $rowData['keteranganpekerjaan'] ?? null) ?? 'Import dari Excel';
                $txDateRaw = $rowData['tanggal'] ?? $rowData['waktu'] ?? $rowData['waktumasuk'] ?? $rowData['waktuterima'] ?? $rowData['tanggalmasuk'] ?? $rowData['tanggalterima'] ?? $rowData['0'] ?? $rowData[''] ?? null;
                $transactionCodeInput = $normalizeText($rowData['kodetransaksi'] ?? null);

                if (! $name) {
                    $this->skippedRows++;
                    $this->rowLogs[] = [
                        'row' => $rowNum,
                        'status' => 'skipped',
                        'item' => '—',
                        'message' => 'Baris kosong / Nama material tidak ditemukan.',
                    ];

                    continue;
                }

                $warehouseId = ImportWarehouse::resolve($rowData, $rowNum, (string) $name);

                $this->totalRows++;
                $isIncoming = in_array($txType, ['masuk', 'restock', 'in'], true);
                $isOutgoing = in_array($txType, ['keluar', 'alokasi', 'out', 'usage'], true) || $houseName;
                $isTransaction = $isIncoming || $isOutgoing;
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
                // the current batch price. Use the only matching item in that case.
                $material = $materialQuery->lockForUpdate()->first();
                if (! $material && $isOutgoing && $code) {
                    $material = Material::where('warehouse_id', $warehouseId)
                        ->where('code', $code)
                        ->lockForUpdate()
                        ->first();
                }
                if (! $material && $isOutgoing) {
                    $sameName = Material::where('warehouse_id', $warehouseId)
                        ->where('name', $name)
                        ->lockForUpdate()
                        ->get();
                    if ($sameName->count() === 1) {
                        $material = $sameName->first();
                    } elseif ($sameName->count() > 1) {
                        throw new \RuntimeException("Baris {$rowNum} ({$name}): Beberapa harga material ditemukan. Isi Kode Material atau Harga Satuan yang tepat.");
                    }
                }
                if (! $material && ! $isTransaction) {
                    $sameName = Material::where('warehouse_id', $warehouseId)
                        ->where('name', $name)
                        ->lockForUpdate()
                        ->get();
                    if ($sameName->count() === 1) {
                        $material = $sameName->first();
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
                    if ($isKeluarFormat) {
                        // In the transaction template, Jumlah is money; Volume is quantity.
                        $volume = $rowData['volume'] ?? null;
                        if (! is_numeric($volume) || ! is_finite((float) $volume) || (float) $volume < 0.01) {
                            throw new \RuntimeException(
                                "Baris {$rowNum} ({$material->name}): Volume stok masuk wajib berupa angka minimal 0.01. Seluruh impor dibatalkan."
                            );
                        }
                        $qty = round((float) $volume, 2);
                    } else {
                        $qty = max(0.01, $stock > 0 ? $stock : floatval($rowData['jumlah'] ?? 1));
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
                        'supplier_id' => $supplier?->id ?? $material->supplier_id,
                        'user_id' => $userId,
                        'quantity' => $qty,
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
                    $qty = max(0.01, $isKeluarFormat
                        ? floatval($rowData['volume'] ?? 1)
                        : ($stock > 0 ? $stock : floatval($rowData['jumlah'] ?? 1)));
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

                    if ((float) $material->stock < $qty) {
                        $available = (float) $material->stock;
                        throw new \RuntimeException(
                            "Baris {$rowNum} ({$material->name}): Stok tidak mencukupi. Tersedia: {$available} {$material->unit}, diminta: {$qty} {$material->unit}. Seluruh impor dibatalkan."
                        );
                    }

                    $usage = MaterialUsage::firstOrCreate([
                        'transaction_code' => $transactionCode,
                        'house_id' => $house->id,
                    ], [
                        'material_id' => $material->id,
                        'user_id' => $userId,
                        'quantity' => $qty,
                        'unit_price_at_usage' => $price,
                        'total_cost' => $total,
                        'usage_date' => $txDate,
                        'notes' => $txNotes,
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

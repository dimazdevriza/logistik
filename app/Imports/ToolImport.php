<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\Tool;
use App\Models\ToolReturnLog;
use App\Models\ToolUsage;
use App\Support\ToolInventory;
use App\Support\ImportHouseIdentity;
use App\Support\ImportTransaction;
use App\Support\ImportWarehouse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ToolImport implements ToCollection, WithHeadingRow
{
    public int $totalRows = 0;

    public int $successfulRows = 0;

    public int $skippedRows = 0;

    public int $toolsImported = 0;

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
                $rowNum = $index + 2;

                // Convert row to array & sanitize keys to lower case trimmed without separators
                $rowData = [];
                foreach ($row as $key => $val) {
                    $cleanKey = strtolower(str_replace(['_', ' ', '/', '-', '.'], '', trim((string) $key)));
                    $rowData[$cleanKey] = is_string($val) ? trim($val) : $val;
                }

                $isKeluarFormat = array_key_exists('volume', $rowData)
                    || array_key_exists('blokrumah', $rowData)
                    || array_key_exists('keteranganpekerjaan', $rowData);
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

                // Field extraction
                $code = $normalizeText($rowData['kode'] ?? $rowData['kodealat'] ?? $rowData['kodebarang'] ?? null);
                $name = $normalizeText($rowData['namaalat'] ?? $rowData['namabarang'] ?? $rowData['nama'] ?? $rowData['alat'] ?? null);
                $categoryName = $normalizeText($rowData['kategori'] ?? ($isKeluarFormat ? 'Alat Umum' : null));
                $condition = match (strtolower((string) ($rowData['kondisi'] ?? 'baik'))) {
                    'rusak', 'perbaikan', 'repair', 'broken' => 'rusak',
                    'hilang', 'lost' => 'hilang',
                    default => 'baik',
                };

                $totalQty = max(1, intval($isKeluarFormat
                    ? ($rowData['volume'] ?? 1)
                    : ($rowData['totalqty'] ?? $rowData['total'] ?? $rowData['jumlah'] ?? 1)));
                $availableQty = intval($rowData['tersedia'] ?? $rowData['stok'] ?? $totalQty);
                $purchasePrice = floatval($rowData['hargabeli'] ?? $rowData['hargasatuan'] ?? $rowData['harga'] ?? 0);

                // Transaction data (Checkout / Return)
                $txType = strtolower((string) ($rowData['jenis'] ?? $rowData['tipe'] ?? $rowData['tipetransaksi'] ?? ($isKeluarFormat ? 'pinjam' : '')));
                $isIncoming = in_array($txType, ['masuk', 'restock', 'in', 'penerimaan', 'receipt'], true);
                $houseName = $normalizeText($rowData['unitrumah'] ?? $rowData['rumah'] ?? $rowData['unit'] ?? $rowData['peminjam'] ?? $rowData['blokrumah'] ?? null);
                $clusterName = $normalizeText($rowData['cluster'] ?? $rowData['namacluster'] ?? null);
                $txNotes = $normalizeText($rowData['catatan'] ?? $rowData['keterangan'] ?? $rowData['keteranganpekerjaan'] ?? null) ?? 'Import dari Excel';
                $txDateRaw = $rowData['tanggal'] ?? $rowData['waktu'] ?? $rowData['waktumasuk'] ?? $rowData['waktuterima'] ?? $rowData['tanggalmasuk'] ?? $rowData['tanggalterima'] ?? $rowData['0'] ?? $rowData[''] ?? null;
                $transactionCodeInput = $normalizeText($rowData['kodetransaksi'] ?? null);

                if (! $name && ! $code) {
                    $this->skippedRows++;
                    $this->rowLogs[] = [
                        'row' => $rowNum,
                        'status' => 'skipped',
                        'item' => '—',
                        'message' => 'Baris kosong / Kode & Nama alat tidak ditemukan.',
                    ];

                    continue;
                }

                $warehouseId = ImportWarehouse::resolve($rowData, $rowNum, (string) ($name ?: $code));

                $this->totalRows++;
                $isReturn = in_array($txType, ['kembali', 'return', 'pengembalian'], true);
                $isCheckout = in_array($txType, ['pinjam', 'checkout', 'peminjaman', 'keluar'], true)
                    || ($houseName && ! $isReturn);
                $txDate = ($isCheckout || $isReturn || $isIncoming)
                    ? ImportTransaction::date($txDateRaw, $rowNum, (string) ($name ?: $code))
                    : null;
                $receivedAt = $isIncoming
                    ? ImportTransaction::dateTime(
                        $rowData['waktumasuk'] ?? $rowData['waktuterima'] ?? $rowData['tanggalterima'] ?? $rowData['tanggalmasuk'] ?? $txDateRaw,
                        $rowNum,
                        (string) ($name ?: $code),
                    )
                    : null;

                // Resolve or create Category
                $category = null;
                if ($categoryName) {
                    $category = Category::firstOrCreate(
                        ['name' => $categoryName],
                        ['type' => 'tool']
                    );
                }

                $hasProvidedCode = (bool) $code;

                if (! $hasProvidedCode && ($isCheckout || $isReturn)) {
                    throw new \RuntimeException("Baris {$rowNum} ({$name}): Kode alat wajib diisi untuk memilih batch alat yang benar.");
                }

                if (! $code) {
                    $code = 'ALT-'.Str::ulid();
                }

                $tool = $isIncoming ? null : Tool::where('code', $code)->lockForUpdate()->first();
                $isNewTool = false;

                if (! $tool) {
                    $toolCode = Tool::where('code', $code)->exists() ? 'ALT-'.Str::ulid() : $code;
                    $tool = Tool::create([
                        'code' => $toolCode,
                        'name' => $name ?: $code,
                        'entry_code' => $isIncoming ? null : 'ALT-OPEN-'.Str::ulid(),
                        'category_id' => $category?->id,
                        'condition' => $condition,
                        'purchase_price' => $purchasePrice,
                        'total_qty' => max(1, $totalQty),
                        'available_qty' => max(0, min($totalQty, $availableQty)),
                        'qty_broken' => $condition === 'rusak' ? 1 : 0,
                        'warehouse_id' => $warehouseId,
                        'entry_type' => $isIncoming ? 'receipt' : 'opening_balance',
                        'submission_key' => $isIncoming ? (string) Str::uuid() : null,
                        'received_at' => $receivedAt,
                        'received_date' => $isIncoming ? $txDate : null,
                        'recorded_by_id' => $userId,
                    ]);
                    $this->toolsImported++;
                    $isNewTool = true;
                } else {
                    ToolInventory::ensureBalance(
                        $tool,
                        (int) $warehouseId,
                        $isCheckout || $isReturn ? 0 : max(0, min($totalQty, $availableQty)),
                        $isCheckout || $isReturn ? 0 : ($condition === 'rusak' ? 1 : 0),
                    );
                    ToolInventory::refreshAggregates($tool);
                    if ($category && ! $tool->category_id) {
                        $tool->category_id = $category->id;
                    }
                    if ($purchasePrice > 0) {
                        $tool->purchase_price = $purchasePrice;
                    }
                    $tool->save();
                }

                $logDetail = $isNewTool ? 'Alat baru diregistrasi' : 'Data alat diperbarui';

                $house = null;
                if ($houseName) {
                    $house = ImportHouseIdentity::resolve((string) $houseName, $rowNum, (string) ($name ?: $code), $clusterName ? (string) $clusterName : null);
                }

                if ($isCheckout) {
                    if (! $house) {
                        throw new \RuntimeException("Baris {$rowNum} ({$tool->name}): Unit Rumah wajib diisi untuk peminjaman alat.");
                    }

                    $qty = $this->transactionQuantity($rowData, $isKeluarFormat, $rowNum, $tool->name);
                    $transactionCode = ImportTransaction::code('KLR', $transactionCodeInput, [
                        'type' => 'pinjam',
                        'tool_id' => $tool->id,
                        'house_id' => $house->id,
                        'date' => $txDate,
                        'quantity' => $qty,
                    ], $rowNum, $tool->name);
                    $tool = Tool::lockForUpdate()->findOrFail($tool->id);

                    if (ToolUsage::where('transaction_code', $transactionCode)->where('house_id', $house->id)->exists()) {
                        $this->skipDuplicate($rowNum, $tool->name);

                        continue;
                    }

                    $sourceBalance = ToolInventory::lockBalance($tool, (int) $warehouseId);
                    if ($sourceBalance->available_qty < $qty) {
                        throw new \RuntimeException("Baris {$rowNum} ({$tool->name}): Stok alat tidak mencukupi di gudang asal. Tersedia: {$sourceBalance->available_qty} unit, diminta: {$qty} unit. Seluruh impor dibatalkan.");
                    }

                    if (ToolUsage::where('tool_id', $tool->id)
                        ->where('house_id', $house->id)
                        ->whereNull('return_date')
                        ->whereNull('voided_at')
                        ->exists()) {
                        throw new \RuntimeException("Baris {$rowNum} ({$tool->name}): Alat ini masih dipinjam oleh {$house->name}. Kembalikan alat sebelum mencatat peminjaman baru.");
                    }

                    ToolUsage::create([
                        'transaction_code' => $transactionCode,
                        'house_id' => $house->id,
                        'tool_id' => $tool->id,
                        'warehouse_id' => $warehouseId,
                        'warehouse_source_recorded' => true,
                        'user_id' => $userId,
                        'quantity' => $qty,
                        'checkout_date' => $txDate,
                        'notes' => $txNotes,
                    ]);

                    ToolInventory::checkout($tool, (int) $warehouseId, $qty);
                    $this->transactionsImported++;
                    $logDetail .= " + Peminjaman ke {$house->name} ({$qty} unit)";
                } elseif ($isReturn) {
                    if (! $house) {
                        throw new \RuntimeException("Baris {$rowNum} ({$tool->name}): Unit Rumah wajib diisi untuk pengembalian alat.");
                    }

                    $qty = $this->transactionQuantity($rowData, $isKeluarFormat, $rowNum, $tool->name);
                    $transactionCode = ImportTransaction::code('MSK', $transactionCodeInput, [
                        'type' => 'kembali',
                        'tool_id' => $tool->id,
                        'house_id' => $house->id,
                        'date' => $txDate,
                        'quantity' => $qty,
                    ], $rowNum, $tool->name);
                    $tool = Tool::lockForUpdate()->findOrFail($tool->id);

                    if (ToolReturnLog::where('transaction_code', $transactionCode)
                        ->where('tool_id', $tool->id)
                        ->where('house_id', $house->id)
                        ->exists()) {
                        $this->skipDuplicate($rowNum, $tool->name);

                        continue;
                    }

                    $activeUsage = ToolUsage::where('tool_id', $tool->id)
                        ->where('house_id', $house->id)
                        ->whereNull('return_date')
                        ->whereNull('voided_at')
                        ->lockForUpdate()
                        ->first();

                    if (! $activeUsage) {
                        throw new \RuntimeException("Baris {$rowNum} ({$tool->name}): Tidak ada peminjaman aktif untuk {$house->name}.");
                    }

                    if ($qty > $activeUsage->quantity) {
                        throw new \RuntimeException("Baris {$rowNum} ({$tool->name}): Jumlah pengembalian melebihi jumlah yang dipinjam ({$activeUsage->quantity} unit).");
                    }

                    $remainingQty = $activeUsage->quantity - $qty;
                    if ($remainingQty === 0) {
                        $activeUsage->update(['return_date' => $txDate]);
                    } else {
                        $activeUsage->update(['quantity' => $qty, 'return_date' => $txDate]);

                        ToolUsage::create([
                            'transaction_code' => ImportTransaction::code('KLR', null, [
                                'type' => 'sisa-pinjaman',
                                'parent_usage_id' => $activeUsage->id,
                                'return_transaction_code' => $transactionCode,
                            ], $rowNum, $tool->name),
                            'house_id' => $activeUsage->house_id,
                            'tool_id' => $activeUsage->tool_id,
                            'warehouse_id' => $activeUsage->warehouse_id,
                            'warehouse_source_recorded' => $activeUsage->warehouse_source_recorded,
                            'user_id' => $activeUsage->user_id,
                            'quantity' => $remainingQty,
                            'checkout_date' => $activeUsage->checkout_date,
                            'parent_usage_id' => $activeUsage->id,
                            'notes' => $activeUsage->notes,
                        ]);
                    }

                    ToolReturnLog::create([
                        'transaction_code' => $transactionCode,
                        'tool_id' => $tool->id,
                        'house_id' => $activeUsage->house_id,
                        'tool_usage_id' => $activeUsage->id,
                        'reported_by' => $userId,
                        'receiving_warehouse_id' => $warehouseId,
                        'quantity' => $qty,
                        'report_type' => 'normal',
                        'status' => 'pending',
                        'notes' => $txNotes,
                    ]);

                    ToolInventory::receive($tool, (int) $warehouseId, $qty, 0);
                    $this->transactionsImported++;
                    $logDetail .= " + Pengembalian ({$qty} unit)";
                }

                $this->successfulRows++;
                $this->rowLogs[] = [
                    'row' => $rowNum,
                    'status' => 'success',
                    'item' => $tool->name,
                    'message' => $logDetail,
                ];
            }
        });
    }

    private function transactionQuantity(array $rowData, bool $isKeluarFormat, int $row, string $item): int
    {
        $value = $isKeluarFormat ? ($rowData['volume'] ?? null) : ($rowData['jumlah'] ?? 1);

        if (! is_numeric($value) || (int) $value < 1 || (float) $value !== (float) (int) $value) {
            throw new \RuntimeException("Baris {$row} ({$item}): Jumlah alat wajib berupa bilangan bulat minimal 1.");
        }

        return (int) $value;
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

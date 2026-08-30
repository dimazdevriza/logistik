<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\House;
use App\Models\Tool;
use App\Models\ToolUsage;
use App\Models\ToolReturnLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
                    if ($value === null) return null;
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
                $condition = strtolower((string) ($rowData['kondisi'] ?? 'baik'));
                if (!in_array($condition, ['baik', 'rusak', 'perbaikan'])) {
                    $condition = 'baik';
                }

                $totalQty = max(1, intval($isKeluarFormat
                    ? ($rowData['volume'] ?? 1)
                    : ($rowData['totalqty'] ?? $rowData['total'] ?? $rowData['jumlah'] ?? 1)));
                $availableQty = intval($rowData['tersedia'] ?? $rowData['stok'] ?? $totalQty);
                $purchasePrice = floatval($rowData['hargabeli'] ?? $rowData['hargasatuan'] ?? $rowData['harga'] ?? 0);

                // Transaction data (Checkout / Return)
                $txType = strtolower((string) ($rowData['jenis'] ?? $rowData['tipe'] ?? $rowData['tipetransaksi'] ?? ($isKeluarFormat ? 'pinjam' : '')));
                $houseName = $normalizeText($rowData['unitrumah'] ?? $rowData['rumah'] ?? $rowData['unit'] ?? $rowData['peminjam'] ?? $rowData['blokrumah'] ?? null);
                $txNotes = $normalizeText($rowData['catatan'] ?? $rowData['keterangan'] ?? $rowData['keteranganpekerjaan'] ?? null) ?? 'Import dari Excel';
                $txDateRaw = $rowData['tanggal'] ?? $rowData['waktu'] ?? $rowData['0'] ?? $rowData[''] ?? now()->toDateString();

                try {
                    if (is_numeric($txDateRaw) && (float) $txDateRaw > 20000) {
                        $txDate = \Carbon\Carbon::instance(
                            \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $txDateRaw)
                        )->toDateString();
                    } else {
                        $txDate = \Carbon\Carbon::parse($txDateRaw)->toDateString();
                    }
                } catch (\Exception $e) {
                    $txDate = now()->toDateString();
                }

                if (!$name && !$code) {
                    $this->skippedRows++;
                    $this->rowLogs[] = [
                        'row' => $rowNum,
                        'status' => 'skipped',
                        'item' => '—',
                        'message' => 'Baris kosong / Kode & Nama alat tidak ditemukan.'
                    ];
                    continue;
                }

                $this->totalRows++;

                // Resolve or create Category
                $category = null;
                if ($categoryName) {
                    $category = Category::firstOrCreate(
                        ['name' => $categoryName],
                        ['type' => 'tool']
                    );
                }

                // Auto-generate code if missing
                if (!$code) {
                    $code = 'ALT-' . strtoupper(substr(md5($name), 0, 5));
                }

                // Resolve or create Tool
                $tool = Tool::where('code', $code)->orWhere('name', $name)->first();
                $isNewTool = false;
                
                if (!$tool) {
                    $tool = Tool::create([
                        'code' => $code,
                        'name' => $name ?: $code,
                        'category_id' => $category?->id,
                        'condition' => $condition,
                        'purchase_price' => $purchasePrice,
                        'total_qty' => max(1, $totalQty),
                        'available_qty' => max(0, min($totalQty, $availableQty)),
                        'qty_broken' => $condition === 'rusak' ? 1 : 0,
                    ]);
                    $this->toolsImported++;
                    $isNewTool = true;
                } else {
                    if ($category && !$tool->category_id) {
                        $tool->category_id = $category->id;
                    }
                    if ($purchasePrice > 0) {
                        $tool->purchase_price = $purchasePrice;
                    }
                    $tool->save();
                }

                $logDetail = $isNewTool ? "Alat baru diregistrasi" : "Data alat diperbarui";

                $house = null;
                if ($houseName) {
                    $house = House::where('name', 'like', "%{$houseName}%")
                        ->orWhere('house_code', 'like', "%{$houseName}%")
                        ->first();

                    if (!$house) {
                        $house = House::create([
                            'house_code' => House::generateCode($houseName),
                            'name' => $houseName,
                            'type' => 'Tipe Standar',
                            'status' => 'pembangunan',
                        ]);
                    }
                }

                // If this row represents a tool transaction (Pinjam / Kembali)
                if (in_array($txType, ['pinjam', 'checkout', 'peminjaman', 'keluar']) || ($houseName && !in_array($txType, ['kembali', 'return', 'pengembalian']))) {
                    if ($house) {
                        $qty = max(1, intval($isKeluarFormat ? ($rowData['volume'] ?? 1) : ($rowData['jumlah'] ?? 1)));
                        
                        ToolUsage::create([
                            'house_id' => $house->id,
                            'tool_id' => $tool->id,
                            'user_id' => $userId,
                            'quantity' => $qty,
                            'checkout_date' => $txDate,
                            'notes' => $txNotes,
                        ]);

                        if ($tool->available_qty >= $qty) {
                            $tool->decrement('available_qty', $qty);
                        }
                        $this->transactionsImported++;
                        $logDetail .= " + Peminjaman ke {$house->name} ({$qty} unit)";
                    }
                } elseif (in_array($txType, ['kembali', 'return', 'pengembalian'])) {
                    $qty = max(1, intval($rowData['jumlah'] ?? 1));
                    
                    // Find active checkout usage for this tool if any
                    $activeUsage = ToolUsage::where('tool_id', $tool->id)
                        ->when($house, fn ($query) => $query->where('house_id', $house->id))
                        ->whereNull('return_date')
                        ->whereNull('voided_at')
                        ->first();

                    if (!$activeUsage && $house) {
                        $activeUsage = ToolUsage::create([
                            'house_id' => $house->id,
                            'tool_id' => $tool->id,
                            'user_id' => $userId,
                            'quantity' => $qty,
                            'checkout_date' => $txDate,
                            'return_date' => $txDate,
                            'notes' => $txNotes,
                        ]);
                    } elseif ($activeUsage) {
                        $activeUsage->update(['return_date' => $txDate]);
                    }

                    if ($activeUsage) {
                        ToolReturnLog::create([
                            'tool_id' => $tool->id,
                            'house_id' => $activeUsage->house_id,
                            'tool_usage_id' => $activeUsage->id,
                            'reported_by' => $userId,
                            'quantity' => $qty,
                            'report_type' => 'normal',
                            'status' => 'pending',
                            'notes' => $txNotes,
                        ]);
                    }

                    $tool->increment('available_qty', $qty);
                    if ($tool->available_qty > $tool->total_qty) {
                        $tool->total_qty = $tool->available_qty;
                        $tool->save();
                    }
                    $this->transactionsImported++;
                    $logDetail .= " + Pengembalian ({$qty} unit)";
                }

                $this->successfulRows++;
                $this->rowLogs[] = [
                    'row' => $rowNum,
                    'status' => 'success',
                    'item' => $tool->name,
                    'message' => $logDetail
                ];
            }
        });
    }
}

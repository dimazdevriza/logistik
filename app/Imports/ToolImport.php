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

class ToolImport implements ToCollection
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

                // Convert row to array & sanitize keys to lower case trimmed without spaces/underscores
                $rowData = [];
                foreach ($row as $key => $val) {
                    $cleanKey = strtolower(str_replace(['_', ' '], '', trim((string) $key)));
                    $rowData[$cleanKey] = is_string($val) ? trim($val) : $val;
                }

                // Field extraction
                $code = $rowData['kode'] ?? $rowData['kodealat'] ?? null;
                $name = $rowData['namaalat'] ?? $rowData['nama'] ?? $rowData['alat'] ?? null;
                $categoryName = $rowData['kategori'] ?? null;
                $condition = strtolower((string) ($rowData['kondisi'] ?? 'baik'));
                if (!in_array($condition, ['baik', 'rusak', 'perbaikan'])) {
                    $condition = 'baik';
                }

                $totalQty = intval($rowData['totalqty'] ?? $rowData['total'] ?? $rowData['jumlah'] ?? 1);
                $availableQty = intval($rowData['tersedia'] ?? $rowData['stok'] ?? $totalQty);
                $purchasePrice = floatval($rowData['hargabeli'] ?? $rowData['harga'] ?? 0);

                // Transaction data (Checkout / Return)
                $txType = strtolower((string) ($rowData['jenis'] ?? $rowData['tipe'] ?? $rowData['tipetransaksi'] ?? ''));
                $houseName = $rowData['unitrumah'] ?? $rowData['rumah'] ?? $rowData['unit'] ?? $rowData['peminjam'] ?? null;
                $txNotes = $rowData['catatan'] ?? $rowData['keterangan'] ?? 'Import dari Excel';
                $txDateRaw = $rowData['tanggal'] ?? $rowData['waktu'] ?? now()->toDateString();

                try {
                    $txDate = \Carbon\Carbon::parse($txDateRaw)->toDateString();
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

                // If this row represents a tool transaction (Pinjam / Kembali)
                if (in_array($txType, ['pinjam', 'checkout', 'peminjaman', 'keluar']) || ($houseName && !in_array($txType, ['kembali', 'return']))) {
                    $house = null;
                    if ($houseName) {
                        $house = House::where('name', 'like', "%{$houseName}%")
                            ->orWhere('code', 'like', "%{$houseName}%")
                            ->first();
                        if (!$house) {
                            $house = House::create([
                                'name' => $houseName,
                                'type' => 'Tipe Standar',
                                'status' => 'pembangunan',
                            ]);
                        }
                    }

                    if ($house) {
                        $qty = max(1, intval($rowData['jumlah'] ?? 1));
                        
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
                        ->whereNull('return_date')
                        ->whereNull('voided_at')
                        ->first();

                    if ($activeUsage) {
                        $activeUsage->update(['return_date' => $txDate]);
                    }

                    ToolReturnLog::create([
                        'tool_id' => $tool->id,
                        'user_id' => $userId,
                        'quantity' => $qty,
                        'return_date' => $txDate,
                        'condition_on_return' => $condition,
                        'notes' => $txNotes,
                    ]);

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

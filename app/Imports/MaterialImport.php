<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\House;
use App\Models\Material;
use App\Models\MaterialUsage;
use App\Models\StockIn;
use App\Models\Supplier;
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
                    if ($value === null) return null;
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
                $txNotes = $normalizeText($rowData['catatan'] ?? $rowData['keterangan'] ?? $rowData['keteranganpekerjaan'] ?? null) ?? 'Import dari Excel';
                $txDateRaw = $rowData['tanggal'] ?? $rowData['waktu'] ?? $rowData['0'] ?? $rowData[''] ?? now()->toDateString();
                
                // Parse date
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

                if (!$name) {
                    $this->skippedRows++;
                    $this->rowLogs[] = [
                        'row' => $rowNum,
                        'status' => 'skipped',
                        'item' => '—',
                        'message' => 'Baris kosong / Nama material tidak ditemukan.'
                    ];
                    continue;
                }

                $this->totalRows++;

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

                // Resolve or create Material
                $material = Material::where('name', $name)->first();
                $isNewMaterial = false;

                if (!$material) {
                    $isTransaction = in_array($txType, ['masuk', 'restock', 'in', 'keluar', 'alokasi', 'out', 'usage'], true)
                        || $houseName;

                    $material = Material::create([
                        'code' => $code,
                        'name' => $name,
                        'category_id' => $category?->id,
                        'supplier_id' => $supplier?->id,
                        'unit' => $unit ?: 'pcs',
                        'unit_price' => $unitPrice,
                        'stock' => $isTransaction ? 0 : max(0, $stock),
                    ]);
                    $this->materialsImported++;
                    $isNewMaterial = true;
                } else {
                    // Update existing material price if provided
                    if ($unitPrice > 0) {
                        $material->unit_price = $unitPrice;
                    }
                    if ($category && !$material->category_id) {
                        $material->category_id = $category->id;
                    }
                    if ($supplier && !$material->supplier_id) {
                        $material->supplier_id = $supplier->id;
                    }
                    if ($code && !$material->code) {
                        $material->code = $code;
                    }
                    // If strictly importing inventory stock, update stock if given
                    if (!in_array($txType, ['masuk', 'keluar', 'restock', 'alokasi'])) {
                        if ($stock > 0 && $material->stock == 0) {
                            $material->stock = $stock;
                        }
                    }
                    $material->save();
                }

                $logDetail = $isNewMaterial ? "Material baru ditambahkan" : "Material diperbarui";

                // If this row represents a transaction (Catatan Log: Restock / Allocation)
                if (in_array($txType, ['masuk', 'restock', 'in'])) {
                    $qty = max(0.01, $stock > 0 ? $stock : floatval($rowData['jumlah'] ?? 1));
                    $price = $unitPrice > 0 ? $unitPrice : floatval($material->unit_price ?? 0);
                    $total = $qty * $price;

                    StockIn::create([
                        'material_id' => $material->id,
                        'supplier_id' => $supplier?->id ?? $material->supplier_id,
                        'user_id' => $userId,
                        'quantity' => $qty,
                        'unit_price' => $price,
                        'total_cost' => $total,
                        'date' => $txDate,
                        'notes' => $txNotes,
                    ]);

                    $material->increment('stock', $qty);
                    $this->transactionsImported++;
                    $logDetail .= " + Restock ({$qty} {$material->unit})";
                } elseif (in_array($txType, ['keluar', 'alokasi', 'out', 'usage']) || $houseName) {
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

                    if ($house) {
                        $qty = max(0.01, $isKeluarFormat
                            ? floatval($rowData['volume'] ?? 1)
                            : ($stock > 0 ? $stock : floatval($rowData['jumlah'] ?? 1)));
                        $price = $unitPrice > 0 ? $unitPrice : floatval($material->unit_price ?? 0);
                        $total = $qty * $price;

                        MaterialUsage::create([
                            'house_id' => $house->id,
                            'material_id' => $material->id,
                            'user_id' => $userId,
                            'quantity' => $qty,
                            'unit_price_at_usage' => $price,
                            'total_cost' => $total,
                            'usage_date' => $txDate,
                            'notes' => $txNotes,
                        ]);

                        // Reduce stock if available
                        $material->decrement('stock', min($material->stock, $qty));
                        $this->transactionsImported++;
                        $logDetail .= " + Alokasi ke {$house->name} ({$qty} {$material->unit})";
                    }
                }

                $this->successfulRows++;
                $this->rowLogs[] = [
                    'row' => $rowNum,
                    'status' => 'success',
                    'item' => $name,
                    'message' => $logDetail
                ];
            }
        });
    }
}

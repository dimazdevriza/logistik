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

class MaterialImport implements ToCollection
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
                    $cleanKey = strtolower(str_replace(['_', ' '], '', trim((string) $key)));
                    $rowData[$cleanKey] = is_string($val) ? trim($val) : $val;
                }

                // Header detection based on normalized keys
                // Mode A: Material Inventory item
                $name = $rowData['namamaterial'] ?? $rowData['nama'] ?? $rowData['material'] ?? null;
                $categoryName = $rowData['kategori'] ?? null;
                $unit = $rowData['satuan'] ?? 'pcs';
                $unitPrice = floatval($rowData['hargasatuan'] ?? $rowData['harga'] ?? 0);
                $stock = floatval($rowData['sisastok'] ?? $rowData['stok'] ?? $rowData['jumlah'] ?? 0);
                $supplierName = $rowData['supplier'] ?? $rowData['pemasok'] ?? null;

                // Mode B: Transaction record (Keluar / Masuk)
                $txType = strtolower((string) ($rowData['jenis'] ?? $rowData['tipe'] ?? $rowData['tipetransaksi'] ?? ''));
                $houseName = $rowData['unitrumah'] ?? $rowData['rumah'] ?? $rowData['unit'] ?? $rowData['referensi'] ?? null;
                $txNotes = $rowData['catatan'] ?? $rowData['keterangan'] ?? 'Import dari Excel';
                $txDateRaw = $rowData['tanggal'] ?? $rowData['waktu'] ?? now()->toDateString();
                
                // Parse date
                try {
                    $txDate = \Carbon\Carbon::parse($txDateRaw)->toDateString();
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
                    $material = Material::create([
                        'name' => $name,
                        'category_id' => $category?->id,
                        'supplier_id' => $supplier?->id,
                        'unit' => $unit ?: 'pcs',
                        'unit_price' => $unitPrice,
                        'stock' => max(0, $stock),
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
                        $qty = max(0.01, $stock > 0 ? $stock : floatval($rowData['jumlah'] ?? 1));
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

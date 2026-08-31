<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\House;
use App\Models\Material;
use App\Models\MaterialUsage;
use App\Models\Tool;
use App\Models\ToolUsage;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class HouseImport implements WithMultipleSheets
{
    public int $totalRows = 0;
    public int $successfulRows = 0;
    public int $skippedRows = 0;
    public int $housesImported = 0;
    public int $materialsImported = 0;
    public int $toolsImported = 0;
    public array $rowLogs = [];

    public static function parseDate(mixed $value): ?string
    {
        if (!$value) return null;

        try {
            return is_numeric($value) && (float) $value > 20000
                ? Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->toDateString()
                : Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    public function sheets(): array
    {
        return [
            0 => new HouseUnitsSheetImport($this),
            1 => new HouseMaterialsSheetImport($this),
            2 => new HouseToolsSheetImport($this),
        ];
    }
}

class HouseUnitsSheetImport implements ToCollection, WithHeadingRow
{
    public function __construct(private HouseImport $parent) {}

    public function collection(Collection $rows)
    {
        if ($rows->isEmpty()) return;

        DB::transaction(function () use ($rows) {
            foreach ($rows as $index => $row) {
                $rowNum = $index + 2;

                $rowData = [];
                foreach ($row as $key => $val) {
                    $cleanKey = strtolower(str_replace(['_', ' ', '-', '.'], '', trim((string) $key)));
                    $rowData[$cleanKey] = is_string($val) ? trim($val) : $val;
                }

                $code = $rowData['kode'] ?? $rowData['koderumah'] ?? $rowData['housecode'] ?? null;
                $name = $rowData['namarumah'] ?? $rowData['nama'] ?? $rowData['blok'] ?? $rowData['unit'] ?? $rowData['namablok'] ?? null;
                $type = $rowData['tipe'] ?? $rowData['tiperumah'] ?? $rowData['type'] ?? 'Standar';
                $status = strtolower((string) ($rowData['status'] ?? $rowData['statusproyek'] ?? 'perencanaan'));

                if (in_array($status, ['perencanaan', 'rencana', 'plan', 'planning'])) {
                    $status = 'perencanaan';
                } elseif (in_array($status, ['pembangunan', 'proses', 'progress', 'konstruksi', 'sedang dibangun', 'building'])) {
                    $status = 'pembangunan';
                } elseif (in_array($status, ['selesai', 'finish', 'completed', 'done'])) {
                    $status = 'selesai';
                } else {
                    $status = 'perencanaan';
                }

                $startDateRaw = $rowData['mulai'] ?? $rowData['tanggalmulai'] ?? $rowData['startdate'] ?? null;
                $targetEndDateRaw = $rowData['targetselesai'] ?? $rowData['selesai'] ?? $rowData['target'] ?? $rowData['targetenddate'] ?? null;

                $startDate = HouseImport::parseDate($startDateRaw);
                $targetEndDate = HouseImport::parseDate($targetEndDateRaw);

                if (!$name && !$code) {
                    $this->parent->skippedRows++;
                    $this->parent->rowLogs[] = [
                        'row' => $rowNum,
                        'sheet' => 'Unit Rumah',
                        'status' => 'skipped',
                        'item' => '—',
                        'message' => 'Baris dilewati: Nama / Blok rumah tidak ditemukan.'
                    ];
                    continue;
                }

                $this->parent->totalRows++;

                if (!$name && $code) {
                    $name = $code;
                }

                if (!$code) {
                    $code = House::generateCode($name);
                }

                $house = House::where('house_code', $code)->orWhere('name', $name)->first();

                if ($house) {
                    $house->update([
                        'name' => $name,
                        'type' => $type ?: $house->type,
                        'status' => $status ?: $house->status,
                        'start_date' => $startDate ?: $house->start_date,
                        'target_end_date' => $targetEndDate ?: $house->target_end_date,
                    ]);

                    $this->parent->successfulRows++;
                    $this->parent->rowLogs[] = [
                        'row' => $rowNum,
                        'sheet' => 'Unit Rumah',
                        'status' => 'success',
                        'item' => "{$house->name} ({$house->house_code})",
                        'message' => "Data unit diperbarui. Tipe: {$house->type}, Status: " . ucfirst($house->status)
                    ];
                } else {
                    $house = House::create([
                        'house_code' => $code,
                        'name' => $name,
                        'type' => $type,
                        'status' => $status,
                        'start_date' => $startDate,
                        'target_end_date' => $targetEndDate,
                    ]);

                    $this->parent->successfulRows++;
                    $this->parent->housesImported++;
                    $this->parent->rowLogs[] = [
                        'row' => $rowNum,
                        'sheet' => 'Unit Rumah',
                        'status' => 'success',
                        'item' => "{$house->name} ({$house->house_code})",
                        'message' => "Unit baru didaftarkan. Tipe: {$house->type}, Status: " . ucfirst($house->status)
                    ];
                }
            }
        });
    }
}

class HouseMaterialsSheetImport implements ToCollection, WithHeadingRow
{
    public function __construct(private HouseImport $parent) {}

    public function collection(Collection $rows)
    {
        if ($rows->isEmpty()) return;

        $userId = Auth::id() ?? 1;

        DB::transaction(function () use ($rows, $userId) {
            foreach ($rows as $index => $row) {
                $rowNum = $index + 2;

                $rowData = [];
                foreach ($row as $key => $val) {
                    $cleanKey = strtolower(str_replace(['_', ' ', '-', '.'], '', trim((string) $key)));
                    $rowData[$cleanKey] = is_string($val) ? trim($val) : $val;
                }

                $houseRef = $rowData['unitrumah'] ?? $rowData['rumah'] ?? $rowData['blok'] ?? $rowData['unit'] ?? $rowData['koderumah'] ?? null;
                $matName = $rowData['namamaterial'] ?? $rowData['material'] ?? $rowData['nama'] ?? null;
                $qty = floatval($rowData['qty'] ?? $rowData['jumlah'] ?? $rowData['kuantitas'] ?? 0);
                $unit = $rowData['satuan'] ?? 'buah';
                $unitPrice = floatval($rowData['hargasatuan'] ?? $rowData['harga'] ?? 0);
                $categoryName = $rowData['kategori'] ?? 'Material Umum';
                $notes = $rowData['peruntukkan'] ?? $rowData['catatan'] ?? $rowData['keterangan'] ?? 'Alokasi Proyek (Import)';
                $dateRaw = $rowData['tanggal'] ?? $rowData['waktu'] ?? now()->toDateString();

                $usageDate = HouseImport::parseDate($dateRaw) ?? now()->toDateString();

                if (!$houseRef || !$matName || $qty <= 0) {
                    if ($houseRef || $matName) {
                        $this->parent->skippedRows++;
                        $this->parent->rowLogs[] = [
                            'row' => $rowNum,
                            'sheet' => 'Pemakaian Material',
                            'status' => 'skipped',
                            'item' => $matName ?? '—',
                            'message' => 'Baris dilewati: Unit rumah, nama material, atau Qty tidak valid.'
                        ];
                    }
                    continue;
                }

                $this->parent->totalRows++;

                // Resolve house
                $house = House::where('name', $houseRef)->orWhere('house_code', $houseRef)->first();
                if (!$house) {
                    // Create house on the fly if needed
                    $house = House::create([
                        'house_code' => House::generateCode($houseRef),
                        'name' => $houseRef,
                        'type' => 'Standar',
                        'status' => 'pembangunan',
                    ]);
                    $this->parent->housesImported++;
                }

                // Resolve material
                $material = Material::where('name', $matName)->first();
                if (!$material) {
                    $category = Category::firstOrCreate(['name' => $categoryName], ['type' => 'material']);
                    $material = Material::create([
                        'name' => $matName,
                        'unit' => $unit,
                        'unit_price' => $unitPrice,
                        'stock' => 0,
                        'category_id' => $category->id,
                    ]);
                }

                if ($unitPrice <= 0 && $material->unit_price > 0) {
                    $unitPrice = (float) $material->unit_price;
                }

                $totalCost = $qty * $unitPrice;

                // Create Material Usage
                MaterialUsage::create([
                    'house_id' => $house->id,
                    'material_id' => $material->id,
                    'user_id' => $userId,
                    'quantity' => $qty,
                    'unit_price_at_usage' => $unitPrice,
                    'total_cost' => $totalCost,
                    'usage_date' => $usageDate,
                    'notes' => $notes,
                ]);

                $this->parent->successfulRows++;
                $this->parent->materialsImported++;
                $this->parent->rowLogs[] = [
                    'row' => $rowNum,
                    'sheet' => 'Pemakaian Material',
                    'status' => 'success',
                    'item' => "{$matName} ({$qty} {$unit})",
                    'message' => "Alokasi material dicatat ke {$house->name}. Biaya: Rp " . number_format($totalCost, 0, ',', '.')
                ];
            }
        });
    }
}

class HouseToolsSheetImport implements ToCollection, WithHeadingRow
{
    public function __construct(private HouseImport $parent) {}

    public function collection(Collection $rows)
    {
        if ($rows->isEmpty()) return;

        $userId = Auth::id() ?? 1;

        DB::transaction(function () use ($rows, $userId) {
            foreach ($rows as $index => $row) {
                $rowNum = $index + 2;

                $rowData = [];
                foreach ($row as $key => $val) {
                    $cleanKey = strtolower(str_replace(['_', ' ', '-', '.'], '', trim((string) $key)));
                    $rowData[$cleanKey] = is_string($val) ? trim($val) : $val;
                }

                $houseRef = $rowData['unitrumah'] ?? $rowData['rumah'] ?? $rowData['blok'] ?? $rowData['unit'] ?? $rowData['koderumah'] ?? null;
                $toolCode = $rowData['kodealat'] ?? $rowData['kode'] ?? null;
                $toolName = $rowData['namaalat'] ?? $rowData['alat'] ?? $rowData['nama'] ?? null;
                $qty = intval($rowData['qty'] ?? $rowData['jumlah'] ?? $rowData['kuantitas'] ?? 1);
                $statusPinjam = strtolower((string) ($rowData['status'] ?? $rowData['statuspinjam'] ?? 'dipinjam'));
                $categoryName = $rowData['kategori'] ?? 'Peralatan Lapangan';
                $notes = $rowData['peruntukkan'] ?? $rowData['catatan'] ?? $rowData['keterangan'] ?? 'Peminjaman Alat (Import)';
                $checkoutDateRaw = $rowData['tanggalpinjam'] ?? $rowData['tanggal'] ?? $rowData['mulai'] ?? now()->toDateString();
                $returnDateRaw = $rowData['tanggalkembali'] ?? $rowData['kembali'] ?? null;

                $checkoutDate = HouseImport::parseDate($checkoutDateRaw) ?? now()->toDateString();
                
                $returnDate = null;
                if ($statusPinjam === 'kembali' || $statusPinjam === 'selesai' || $returnDateRaw) {
                    $returnDate = HouseImport::parseDate($returnDateRaw ?: now()) ?? now()->toDateString();
                }

                if (!$houseRef || (!$toolName && !$toolCode) || $qty <= 0) {
                    if ($houseRef || $toolName || $toolCode) {
                        $this->parent->skippedRows++;
                        $this->parent->rowLogs[] = [
                            'row' => $rowNum,
                            'sheet' => 'Peminjaman Alat',
                            'status' => 'skipped',
                            'item' => $toolName ?? $toolCode ?? '—',
                            'message' => 'Baris dilewati: Unit rumah, nama/kode alat, atau Qty tidak valid.'
                        ];
                    }
                    continue;
                }

                $this->parent->totalRows++;

                // Resolve house
                $house = House::where('name', $houseRef)->orWhere('house_code', $houseRef)->first();
                if (!$house) {
                    $house = House::create([
                        'house_code' => House::generateCode($houseRef),
                        'name' => $houseRef,
                        'type' => 'Standar',
                        'status' => 'pembangunan',
                    ]);
                    $this->parent->housesImported++;
                }

                // Resolve tool
                $tool = null;
                if ($toolCode) {
                    $tool = Tool::where('code', $toolCode)->first();
                }
                if (!$tool && $toolName) {
                    $tool = Tool::where('name', $toolName)->first();
                }

                if (!$tool) {
                    $category = Category::firstOrCreate(['name' => $categoryName], ['type' => 'tool']);
                    $code = $toolCode ?: ('ALT-' . strtoupper(substr(md5($toolName), 0, 5)));
                    $tool = Tool::create([
                        'code' => $code,
                        'name' => $toolName ?: $code,
                        'category_id' => $category->id,
                        'condition' => 'baik',
                        'total_qty' => $qty,
                        'available_qty' => ($returnDate ? $qty : 0),
                        'purchase_price' => 0,
                    ]);
                }

                // Create Tool Usage
                ToolUsage::create([
                    'house_id' => $house->id,
                    'tool_id' => $tool->id,
                    'user_id' => $userId,
                    'quantity' => $qty,
                    'checkout_date' => $checkoutDate,
                    'return_date' => $returnDate,
                    'notes' => $notes,
                ]);

                $this->parent->successfulRows++;
                $this->parent->toolsImported++;
                $this->parent->rowLogs[] = [
                    'row' => $rowNum,
                    'sheet' => 'Peminjaman Alat',
                    'status' => 'success',
                    'item' => "{$tool->name} ({$qty} unit)",
                    'message' => "Peminjaman alat dicatat ke {$house->name}. Status: " . ($returnDate ? 'Dikembalikan' : 'Dipinjam')
                ];
            }
        });
    }
}

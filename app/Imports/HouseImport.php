<?php

namespace App\Imports;

use App\Models\Cluster;
use App\Models\House;
use App\Models\Material;
use App\Models\MaterialToolRequest;
use App\Models\MaterialUsage;
use App\Models\Tool;
use App\Models\ToolReturnLog;
use App\Models\ToolUsage;
use App\Support\ToolInventory;
use App\Support\ImportTransaction;
use App\Support\ImportWarehouse;
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
    public function __construct(public readonly ?int $clusterId = null) {}

    public int $totalRows = 0;

    public int $successfulRows = 0;

    public int $skippedRows = 0;

    public int $housesImported = 0;

    public int $materialsImported = 0;

    public int $toolsImported = 0;

    public array $rowLogs = [];

    public function findHouse(string $reference): ?House
    {
        return House::query()
            ->where(fn ($query) => $query->where('name', $reference)->orWhere('house_code', $reference))
            ->when($this->clusterId !== null, fn ($query) => $query->where('cluster_id', $this->clusterId))
            ->first();
    }

    public function houseExistsOutsideCluster(string $reference): bool
    {
        return $this->clusterId !== null && House::query()
            ->where(fn ($query) => $query->where('name', $reference)->orWhere('house_code', $reference))
            ->where(fn ($query) => $query->whereNull('cluster_id')->orWhere('cluster_id', '!=', $this->clusterId))
            ->exists();
    }

    public static function parseDate(mixed $value): ?string
    {
        if (! $value) {
            return null;
        }

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
        if ($rows->isEmpty()) {
            return;
        }

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
                $clusterName = trim((string) ($rowData['cluster'] ?? $rowData['namacluster'] ?? ''));
                $cluster = $this->parent->clusterId !== null
                    ? Cluster::findOrFail($this->parent->clusterId)
                    : ($clusterName !== '' ? Cluster::firstOrCreate(['name' => $clusterName]) : null);
                $statusRaw = $rowData['status'] ?? $rowData['statusproyek'] ?? null;
                $status = null;
                if ($statusRaw !== null && trim((string) $statusRaw) !== '') {
                    $statusValue = strtolower(trim((string) $statusRaw));
                    if (in_array($statusValue, ['perencanaan', 'rencana', 'plan', 'planning'], true)) {
                        $status = 'perencanaan';
                    } elseif (in_array($statusValue, ['pembangunan', 'proses', 'progress', 'konstruksi', 'sedang dibangun', 'building'], true)) {
                        $status = 'pembangunan';
                    } elseif (in_array($statusValue, ['selesai', 'finish', 'completed', 'done'], true)) {
                        $status = 'selesai';
                    } else {
                        throw new \RuntimeException("Baris {$rowNum}: Status rumah tidak valid: {$statusRaw}.");
                    }
                }

                $startDateRaw = $rowData['mulai'] ?? $rowData['tanggalmulai'] ?? $rowData['startdate'] ?? null;
                $targetEndDateRaw = $rowData['targetselesai'] ?? $rowData['selesai'] ?? $rowData['target'] ?? $rowData['targetenddate'] ?? null;

                $startDate = HouseImport::parseDate($startDateRaw);
                $targetEndDate = HouseImport::parseDate($targetEndDateRaw);

                if ($startDateRaw !== null && trim((string) $startDateRaw) !== '' && $startDate === null) {
                    throw new \RuntimeException("Baris {$rowNum}: Tanggal mulai rumah tidak valid.");
                }
                if ($targetEndDateRaw !== null && trim((string) $targetEndDateRaw) !== '' && $targetEndDate === null) {
                    throw new \RuntimeException("Baris {$rowNum}: Target selesai rumah tidak valid.");
                }

                if (! $name && ! $code) {
                    $this->parent->skippedRows++;
                    $this->parent->rowLogs[] = [
                        'row' => $rowNum,
                        'sheet' => 'Unit Rumah',
                        'status' => 'skipped',
                        'item' => '—',
                        'message' => 'Baris dilewati: Nama / Blok rumah tidak ditemukan.',
                    ];

                    continue;
                }

                $this->parent->totalRows++;

                if (! $name && $code) {
                    $name = $code;
                }

                if (! $code) {
                    $code = House::generateCode($name);
                }

                $houseQuery = House::where(function ($query) use ($code, $name) {
                    $query->where('house_code', $code)->orWhere('name', $name);
                })->when($this->parent->clusterId !== null, fn ($query) => $query->where('cluster_id', $this->parent->clusterId));
                $house = (clone $houseQuery)->lockForUpdate()->first();

                if ($house) {
                    $nextStatus = $status ?? $house->status;
                    if ($house->status === 'selesai') {
                        $sameIdentity = $house->name === $name
                            && $house->type === ($type ?: $house->type)
                            && (int) $house->cluster_id === (int) ($cluster?->id ?? $house->cluster_id)
                            && ($startDate === null || $house->start_date?->toDateString() === $startDate)
                            && ($targetEndDate === null || $house->target_end_date?->toDateString() === $targetEndDate);
                        if (! $sameIdentity || $nextStatus !== 'selesai') {
                            throw new \RuntimeException("Baris {$rowNum}: Rumah {$house->name} sudah selesai dan tidak dapat dibuka kembali atau diubah melalui impor.");
                        }

                        $this->parent->skippedRows++;
                        $this->parent->rowLogs[] = [
                            'row' => $rowNum,
                            'sheet' => 'Unit Rumah',
                            'status' => 'skipped',
                            'item' => "{$house->name} ({$house->house_code})",
                            'message' => 'Rumah selesai sudah tercatat dan dilewati tanpa perubahan.',
                        ];

                        continue;
                    }
                    if ($nextStatus === 'selesai'
                        && ToolUsage::where('house_id', $house->id)
                            ->whereNull('return_date')
                            ->whereNull('voided_at')
                            ->exists()) {
                        throw new \RuntimeException("Baris {$rowNum}: Rumah {$house->name} masih memiliki alat yang belum dikembalikan.");
                    }
                    if ($nextStatus === 'selesai'
                        && MaterialToolRequest::where('house_id', $house->id)
                            ->whereIn('status', ['pending', 'dispatched', 'partially_arrived'])
                            ->exists()) {
                        throw new \RuntimeException("Baris {$rowNum}: Rumah {$house->name} masih memiliki permintaan yang belum selesai.");
                    }
                    if ($nextStatus === 'selesai') {
                        throw new \RuntimeException("Baris {$rowNum}: Rumah {$house->name} hanya dapat diselesaikan melalui alur pertanggungjawaban alat.");
                    }

                    $house->update([
                        'name' => $name,
                        'type' => $type ?: $house->type,
                        'status' => $nextStatus,
                        'cluster_id' => $this->parent->clusterId ?? $cluster?->id ?? $house->cluster_id,
                        'start_date' => $startDate ?: $house->start_date,
                        'target_end_date' => $targetEndDate ?: $house->target_end_date,
                    ]);

                    $this->parent->successfulRows++;
                    $this->parent->rowLogs[] = [
                        'row' => $rowNum,
                        'sheet' => 'Unit Rumah',
                        'status' => 'success',
                        'item' => "{$house->name} ({$house->house_code})",
                        'message' => "Data unit diperbarui. Tipe: {$house->type}, Status: ".ucfirst($house->status),
                    ];
                } else {
                    if ($this->parent->houseExistsOutsideCluster((string) $code)
                        || $this->parent->houseExistsOutsideCluster((string) $name)) {
                        throw new \RuntimeException("Baris {$rowNum}: Rumah {$name} berada di luar cluster tugas Anda.");
                    }
                    if ($status === 'selesai') {
                        throw new \RuntimeException("Baris {$rowNum}: Rumah baru tidak dapat langsung berstatus selesai. Gunakan alur penyelesaian proyek.");
                    }
                    $house = House::create([
                        'house_code' => $code,
                        'name' => $name,
                        'type' => $type,
                        'status' => $status ?? 'perencanaan',
                        'cluster_id' => $this->parent->clusterId ?? $cluster?->id,
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
                        'message' => "Unit baru didaftarkan. Tipe: {$house->type}, Status: ".ucfirst($house->status),
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
        if ($rows->isEmpty()) {
            return;
        }

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
                $notes = $rowData['peruntukkan'] ?? $rowData['catatan'] ?? $rowData['keterangan'] ?? 'Alokasi Proyek (Import)';
                $dateRaw = array_key_exists('tanggal', $rowData)
                    ? $rowData['tanggal']
                    : ($rowData['waktu'] ?? now()->toDateString());
                $usageDate = ImportTransaction::date($dateRaw, $rowNum, (string) ($matName ?: 'material'));

                if (! $houseRef || ! $matName || $qty <= 0) {
                    if ($houseRef || $matName) {
                        $this->parent->skippedRows++;
                        $this->parent->rowLogs[] = [
                            'row' => $rowNum,
                            'sheet' => 'Pemakaian Material',
                            'status' => 'skipped',
                            'item' => $matName ?? '—',
                            'message' => 'Baris dilewati: Unit rumah, nama material, atau Qty tidak valid.',
                        ];
                    }

                    continue;
                }

                $this->parent->totalRows++;
                $warehouseId = ImportWarehouse::resolve($rowData, $rowNum, (string) $matName);

                // Resolve house
                $house = $this->parent->findHouse((string) $houseRef);
                if (! $house) {
                    if ($this->parent->houseExistsOutsideCluster((string) $houseRef)) {
                        throw new \RuntimeException("Baris {$rowNum}: Rumah {$houseRef} berada di luar cluster tugas Anda.");
                    }
                    // Create house on the fly if needed
                    $house = House::create([
                        'house_code' => House::generateCode($houseRef),
                        'name' => $houseRef,
                        'type' => 'Standar',
                        'status' => 'pembangunan',
                        'cluster_id' => $this->parent->clusterId,
                    ]);
                    $this->parent->housesImported++;
                }
                if ($house->status === 'selesai') {
                    throw new \RuntimeException("Baris {$rowNum} ({$matName}): Rumah {$house->name} sudah selesai dan tidak menerima alokasi baru.");
                }

                // Resolve material
                $material = Material::where('warehouse_id', $warehouseId)->where('name', $matName)->lockForUpdate()->first();
                if (! $material) {
                    $otherWarehouseMaterial = Material::where('name', $matName)->first();
                    if ($otherWarehouseMaterial) {
                        throw new \RuntimeException("Baris {$rowNum} ({$matName}): Material terdaftar di gudang lain.");
                    }
                    throw new \RuntimeException("Baris {$rowNum} ({$matName}): Material belum terdaftar di gudang. Impor stok material terlebih dahulu.");
                }

                if ($unitPrice <= 0 && $material->unit_price > 0) {
                    $unitPrice = (float) $material->unit_price;
                }

                $totalCost = $qty * $unitPrice;

                $transactionCode = ImportTransaction::code('KLR', $rowData['kodetransaksi'] ?? null, [
                    'type' => 'keluar',
                    'material_id' => $material->id,
                    'house_id' => $house->id,
                    'date' => $usageDate,
                    'quantity' => number_format($qty, 2, '.', ''),
                    'unit_price' => number_format($unitPrice, 2, '.', ''),
                ], $rowNum, $material->name);

                if (MaterialUsage::where('transaction_code', $transactionCode)->where('house_id', $house->id)->exists()) {
                    $this->parent->skippedRows++;
                    $this->parent->rowLogs[] = [
                        'row' => $rowNum,
                        'sheet' => 'Pemakaian Material',
                        'status' => 'skipped',
                        'item' => $material->name,
                        'message' => 'Transaksi sudah tercatat. Stok tidak diubah.',
                    ];

                    continue;
                }

                if ((float) $material->stock < $qty) {
                    throw new \RuntimeException("Baris {$rowNum} ({$material->name}): Stok tidak mencukupi. Tersedia: {$material->stock} {$material->unit}, diminta: {$qty} {$material->unit}. Seluruh impor dibatalkan.");
                }

                // Create Material Usage
                MaterialUsage::create([
                    'transaction_code' => $transactionCode,
                    'house_id' => $house->id,
                    'material_id' => $material->id,
                    'user_id' => $userId,
                    'quantity' => $qty,
                    'unit_price_at_usage' => $unitPrice,
                    'total_cost' => $totalCost,
                    'usage_date' => $usageDate,
                    'notes' => $notes,
                ]);

                $material->decrement('stock', $qty);

                $this->parent->successfulRows++;
                $this->parent->materialsImported++;
                $this->parent->rowLogs[] = [
                    'row' => $rowNum,
                    'sheet' => 'Pemakaian Material',
                    'status' => 'success',
                    'item' => "{$matName} ({$qty} {$unit})",
                    'message' => "Alokasi material dicatat ke {$house->name}. Biaya: Rp ".number_format($totalCost, 0, ',', '.'),
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
        if ($rows->isEmpty()) {
            return;
        }

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
                $qtyRaw = $rowData['qty'] ?? $rowData['jumlah'] ?? $rowData['kuantitas'] ?? null;
                $qty = 1;
                if ($qtyRaw !== null && trim((string) $qtyRaw) !== '') {
                    if (! is_numeric($qtyRaw) || (float) $qtyRaw <= 0 || (float) $qtyRaw !== (float) (int) $qtyRaw) {
                        throw new \RuntimeException("Baris {$rowNum} ({$toolName}): Qty alat harus berupa bilangan bulat minimal 1.");
                    }
                    $qty = (int) $qtyRaw;
                }
                $statusPinjam = strtolower((string) ($rowData['status'] ?? $rowData['statuspinjam'] ?? 'dipinjam'));
                $notes = $rowData['peruntukkan'] ?? $rowData['catatan'] ?? $rowData['keterangan'] ?? 'Peminjaman Alat (Import)';
                $checkoutDateRaw = array_key_exists('tanggalpinjam', $rowData)
                    ? $rowData['tanggalpinjam']
                    : ($rowData['tanggal'] ?? $rowData['mulai'] ?? now()->toDateString());
                $returnDateRaw = $rowData['tanggalkembali'] ?? $rowData['kembali'] ?? null;

                $checkoutDate = ImportTransaction::date($checkoutDateRaw, $rowNum, (string) ($toolName ?: $toolCode ?: 'alat'));
                $returnDate = null;
                if ($statusPinjam === 'kembali' || $statusPinjam === 'selesai' || $returnDateRaw) {
                    if ($returnDateRaw === null || trim((string) $returnDateRaw) === '') {
                        throw new \RuntimeException("Baris {$rowNum} ({$toolName}): Tanggal kembali wajib diisi untuk alat berstatus kembali.");
                    }
                    $returnDate = ImportTransaction::date($returnDateRaw, $rowNum, (string) ($toolName ?: $toolCode ?: 'alat'));
                    if ($returnDate < $checkoutDate) {
                        throw new \RuntimeException("Baris {$rowNum} ({$toolName}): Tanggal kembali tidak boleh lebih awal dari tanggal pinjam.");
                    }
                }

                if (! $houseRef || (! $toolName && ! $toolCode) || $qty <= 0) {
                    if ($houseRef || $toolName || $toolCode) {
                        $this->parent->skippedRows++;
                        $this->parent->rowLogs[] = [
                            'row' => $rowNum,
                            'sheet' => 'Peminjaman Alat',
                            'status' => 'skipped',
                            'item' => $toolName ?? $toolCode ?? '—',
                            'message' => 'Baris dilewati: Unit rumah, nama/kode alat, atau Qty tidak valid.',
                        ];
                    }

                    continue;
                }

                $this->parent->totalRows++;
                $warehouseId = ImportWarehouse::resolve($rowData, $rowNum, (string) ($toolName ?: $toolCode ?: 'alat'));

                // Resolve house
                $house = $this->parent->findHouse((string) $houseRef);
                if (! $house) {
                    if ($this->parent->houseExistsOutsideCluster((string) $houseRef)) {
                        throw new \RuntimeException("Baris {$rowNum}: Rumah {$houseRef} berada di luar cluster tugas Anda.");
                    }
                    $house = House::create([
                        'house_code' => House::generateCode($houseRef),
                        'name' => $houseRef,
                        'type' => 'Standar',
                        'status' => 'pembangunan',
                        'cluster_id' => $this->parent->clusterId,
                    ]);
                    $this->parent->housesImported++;
                }
                if ($house->status === 'selesai') {
                    throw new \RuntimeException("Baris {$rowNum} ({$toolName}): Rumah {$house->name} sudah selesai dan tidak menerima peminjaman baru.");
                }

                // Resolve tool
                $tool = null;
                if ($toolCode) {
                    $tool = Tool::where('code', $toolCode)->lockForUpdate()->first();
                }
                if (! $tool && $toolName) {
                    $tool = Tool::where('name', $toolName)
                        ->whereHas('warehouseBalances', fn ($query) => $query->where('warehouse_id', $warehouseId))
                        ->lockForUpdate()
                        ->first();
                }
                if (! $tool) {
                    throw new \RuntimeException("Baris {$rowNum} ({$toolName}): Alat belum terdaftar di gudang. Impor data alat terlebih dahulu.");
                }
                $tool = Tool::lockForUpdate()->findOrFail($tool->id);

                $transactionCode = ImportTransaction::code($returnDate ? 'MSK' : 'KLR', $rowData['kodetransaksi'] ?? null, [
                    'type' => $returnDate ? 'kembali' : 'pinjam',
                    'tool_id' => $tool->id,
                    'house_id' => $house->id,
                    'date' => $returnDate ?: $checkoutDate,
                    'quantity' => $qty,
                ], $rowNum, $tool->name);

                if ($returnDate && ToolReturnLog::where('transaction_code', $transactionCode)
                    ->where('tool_id', $tool->id)
                    ->where('house_id', $house->id)
                    ->exists()) {
                    $this->parent->skippedRows++;
                    $this->parent->rowLogs[] = [
                        'row' => $rowNum,
                        'sheet' => 'Peminjaman Alat',
                        'status' => 'skipped',
                        'item' => $tool->name,
                        'message' => 'Transaksi sudah tercatat. Stok tidak diubah.',
                    ];

                    continue;
                }
                if (! $returnDate && ToolUsage::where('transaction_code', $transactionCode)->where('house_id', $house->id)->exists()) {
                    $this->parent->skippedRows++;
                    $this->parent->rowLogs[] = [
                        'row' => $rowNum,
                        'sheet' => 'Peminjaman Alat',
                        'status' => 'skipped',
                        'item' => $tool->name,
                        'message' => 'Transaksi sudah tercatat. Stok tidak diubah.',
                    ];

                    continue;
                }

                $activeUsage = null;
                if (! $returnDate) {
                    $sourceBalance = ToolInventory::lockBalance($tool, (int) $warehouseId);
                    if ((int) $sourceBalance->available_qty < $qty) {
                        throw new \RuntimeException("Baris {$rowNum} ({$tool->name}): Stok alat tidak mencukupi di gudang asal. Tersedia: {$sourceBalance->available_qty} unit, diminta: {$qty} unit. Seluruh impor dibatalkan.");
                    }
                    if (ToolUsage::where('tool_id', $tool->id)->where('house_id', $house->id)->whereNull('return_date')->whereNull('voided_at')->exists()) {
                        throw new \RuntimeException("Baris {$rowNum} ({$tool->name}): Alat ini masih dipinjam oleh {$house->name}.");
                    }
                } else {
                    $activeUsage = ToolUsage::where('tool_id', $tool->id)
                        ->where('house_id', $house->id)
                        ->whereNull('return_date')
                        ->whereNull('voided_at')
                        ->lockForUpdate()
                        ->first();
                    if (! $activeUsage) {
                        throw new \RuntimeException("Baris {$rowNum} ({$tool->name}): Tidak ada peminjaman aktif untuk dikembalikan oleh {$house->name}.");
                    }
                    if ($qty > (int) $activeUsage->quantity) {
                        throw new \RuntimeException("Baris {$rowNum} ({$tool->name}): Qty pengembalian melebihi peminjaman aktif ({$activeUsage->quantity} unit).");
                    }
                }

                if (! $returnDate) {
                    ToolUsage::create([
                        'transaction_code' => $transactionCode,
                        'house_id' => $house->id,
                        'tool_id' => $tool->id,
                        'warehouse_id' => $warehouseId,
                        'warehouse_source_recorded' => true,
                        'user_id' => $userId,
                        'quantity' => $qty,
                        'checkout_date' => $checkoutDate,
                        'return_date' => null,
                        'notes' => $notes,
                    ]);
                    ToolInventory::checkout($tool, (int) $warehouseId, $qty);
                } else {
                    $remaining = (int) $activeUsage->quantity - $qty;
                    $activeUsage->update([
                        'quantity' => $qty,
                        'return_date' => $returnDate,
                    ]);
                    if ($remaining > 0) {
                        ToolUsage::create([
                            'transaction_code' => ImportTransaction::code('KLR', null, [
                                'type' => 'sisa-pinjaman',
                                'parent_usage_id' => $activeUsage->id,
                                'return_transaction_code' => $transactionCode,
                            ], $rowNum, $tool->name),
                            'house_id' => $house->id,
                            'tool_id' => $tool->id,
                            'warehouse_id' => $activeUsage->warehouse_id,
                            'warehouse_source_recorded' => $activeUsage->warehouse_source_recorded,
                            'user_id' => $activeUsage->user_id,
                            'quantity' => $remaining,
                            'checkout_date' => $activeUsage->checkout_date,
                            'return_date' => null,
                            'parent_usage_id' => $activeUsage->id,
                            'notes' => $activeUsage->notes,
                        ]);
                    }
                    ToolInventory::receive($tool, (int) $warehouseId, $qty, 0);
                    ToolReturnLog::create([
                        'transaction_code' => $transactionCode,
                        'tool_id' => $tool->id,
                        'house_id' => $house->id,
                        'tool_usage_id' => $activeUsage->id,
                        'reported_by' => $userId,
                        'receiving_warehouse_id' => $warehouseId,
                        'quantity' => $qty,
                        'report_type' => 'normal',
                        'status' => 'pending',
                        'notes' => $notes,
                    ]);
                }

                $this->parent->successfulRows++;
                $this->parent->toolsImported++;
                $this->parent->rowLogs[] = [
                    'row' => $rowNum,
                    'sheet' => 'Peminjaman Alat',
                    'status' => 'success',
                    'item' => "{$tool->name} ({$qty} unit)",
                    'message' => "Peminjaman alat dicatat ke {$house->name}. Status: ".($returnDate ? 'Dikembalikan' : 'Dipinjam'),
                ];
            }
        });
    }
}

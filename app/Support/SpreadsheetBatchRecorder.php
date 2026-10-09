<?php

namespace App\Support;

use App\Models\House;
use App\Models\Material;
use App\Models\StockIn;
use App\Models\Tool;
use App\Models\ToolUsage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class SpreadsheetBatchRecorder
{
    /**
     * Process multi-house spreadsheet transaction rows atomically.
     *
     * @param  array<int, array>  $rows
     * @return array{materials: int, tools: int, returns: int}
     */
    public static function process(array $rows, ?User $actor = null): array
    {
        $actor = $actor ?? auth()->user();
        $houseIds = collect($rows)->pluck('house_id')->map(fn ($id) => (int) $id)->all();

        return DB::transaction(function () use ($rows, $houseIds, $actor) {
            $houses = House::forUser($actor)
                ->whereIn('id', $houseIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id', 'name', 'status', 'warranty_expires_at']);

            if ($houses->count() !== count($houseIds)) {
                throw ValidationException::withMessages([
                    'spreadsheetRows' => 'Salah satu rumah tidak lagi tersedia untuk akun Anda. Muat ulang lalu pilih ulang rumah.',
                ]);
            }

            $houseById = $houses->keyBy('id');
            $allocationHouseIds = collect($rows)
                ->filter(fn ($row) => ! empty($row['materials']) || ! empty($row['tools']))
                ->pluck('house_id')
                ->map(fn ($id) => (int) $id);

            if ($houses->whereIn('id', $allocationHouseIds)->contains(fn ($house) => ! $house->canReceiveAllocations())) {
                throw ValidationException::withMessages([
                    'spreadsheetRows' => 'Rumah yang sudah selesai masa garansinya tidak dapat menerima material atau alat.',
                ]);
            }

            $materialCount = 0;
            $toolCount = 0;
            $returnCount = 0;

            foreach ($rows as $rowIndex => $row) {
                $house = $houseById[(int) $row['house_id']];

                foreach ($row['materials'] ?? [] as $lineIndex => $line) {
                    $material = Material::whereKey($line['material_id'])->lockForUpdate()->firstOrFail();
                    $quantity = (float) $line['quantity'];
                    if ((float) $material->stock + 0.001 < $quantity) {
                        throw ValidationException::withMessages([
                            "spreadsheetRows.$rowIndex.materials.$lineIndex.quantity" => 'Stok '.$material->name.' tidak mencukupi untuk alokasi ini.',
                        ]);
                    }

                    $batch = StockIn::withReservedQuantity()
                        ->whereKey($line['batch_id'])
                        ->where('material_id', $material->id)
                        ->whereNotNull('warehouse_id')
                        ->lockForUpdate()
                        ->first();

                    if (! $batch || (float) $batch->available_quantity + 0.001 < $quantity) {
                        throw ValidationException::withMessages([
                            "spreadsheetRows.$rowIndex.materials.$lineIndex.batch_id" => 'Saldo batch '.$material->name.' berubah atau tidak cukup. Pilih batch lain.',
                        ]);
                    }

                    MaterialAllocationRecorder::allocateLine(
                        $house,
                        $material,
                        $batch->id,
                        $quantity,
                        (float) $batch->unit_price,
                        $line['notes'] ?? null,
                        takenBy: $line['taken_by'] ?? null,
                        actor: $actor
                    );

                    $batch->decrement('remaining_quantity', $quantity);
                    $material->decrement('stock', $quantity);
                    $materialCount++;
                }

                foreach ($row['tools'] ?? [] as $lineIndex => $line) {
                    $tool = Tool::whereKey($line['tool_id'])->lockForUpdate()->firstOrFail();
                    $warehouseId = (int) $line['warehouse_id'];
                    $quantity = (int) $line['quantity'];

                    $usageExists = ToolUsage::where('tool_id', $tool->id)
                        ->where('house_id', $house->id)
                        ->whereNull('return_date')
                        ->whereNull('voided_at')
                        ->exists();

                    if ($usageExists) {
                        throw ValidationException::withMessages([
                            "spreadsheetRows.$rowIndex.tools.$lineIndex.tool_id" => $tool->name.' masih dipinjam oleh '.$house->name.'.',
                        ]);
                    }

                    $balance = ToolInventory::lockBalance($tool, $warehouseId);
                    if ((int) $balance->available_qty < $quantity) {
                        throw ValidationException::withMessages([
                            "spreadsheetRows.$rowIndex.tools.$lineIndex.quantity" => 'Stok '.$tool->name.' di gudang pilihan tidak mencukupi.',
                        ]);
                    }

                    ToolInventory::checkout($tool, $warehouseId, $quantity);

                    ToolLoanRecorder::loanLine(
                        $house,
                        $tool,
                        $warehouseId,
                        $quantity,
                        $line['notes'] ?? null,
                        actor: $actor
                    );
                    $toolCount++;
                }

                foreach ($row['returns'] ?? [] as $lineIndex => $line) {
                    $usage = ToolUsage::where('house_id', $house->id)
                        ->whereHas('house', fn ($query) => $query->forUser($actor))
                        ->lockForUpdate()
                        ->find($line['usage_id']);

                    if (! $usage || $usage->return_date || $usage->voided_at) {
                        throw ValidationException::withMessages([
                            "spreadsheetRows.$rowIndex.returns.$lineIndex.usage_id" => 'Alat ini sudah dikembalikan atau bukan milik rumah yang dipilih.',
                        ]);
                    }

                    $returnQty = (int) $line['qty_normal'] + (int) $line['qty_broken'] + (int) $line['qty_lost'];
                    if ($returnQty > $usage->quantity) {
                        throw ValidationException::withMessages([
                            "spreadsheetRows.$rowIndex.returns.$lineIndex.qty_normal" => 'Jumlah pengembalian melebihi jumlah alat yang tercatat dipinjam.']);
                    }

                    if (((int) $line['qty_normal'] + (int) $line['qty_broken']) > 0 && empty($line['receiving_warehouse_id'])) {
                        throw ValidationException::withMessages([
                            "spreadsheetRows.$rowIndex.returns.$lineIndex.receiving_warehouse_id" => 'Pilih gudang penerima untuk alat yang kembali.',
                        ]);
                    }

                    ToolReturnRecorder::recordReturn(
                        $usage,
                        (int) $line['qty_normal'],
                        (int) $line['qty_broken'],
                        (int) $line['qty_lost'],
                        (int) ($line['receiving_warehouse_id'] ?? 0),
                        $line['notes'] ?? null,
                        $actor
                    );
                    $returnCount++;
                }
            }

            return [
                'materials' => $materialCount,
                'tools' => $toolCount,
                'returns' => $returnCount,
            ];
        });
    }
}

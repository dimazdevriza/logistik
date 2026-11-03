<?php

namespace App\Support;

use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

final class ImportWarehouse
{
    public static function resolve(array $row, int $rowNumber, string $item): int
    {
        $user = Auth::user();
        $id = $row['warehouseid'] ?? $row['gudangid'] ?? null;
        $name = $row['warehouse'] ?? $row['gudang'] ?? $row['namagudang'] ?? $row['warehousename'] ?? null;

        if ($id !== null && trim((string) $id) !== '') {
            $warehouse = Warehouse::find((int) $id);
            if (! $warehouse) {
                throw new RuntimeException("Baris {$rowNumber} ({$item}): Gudang dengan ID {$id} tidak ditemukan.");
            }
            if ($user && ! $user->canAccessWarehouse($warehouse->id)) {
                throw new RuntimeException("Baris {$rowNumber} ({$item}): Gudang berada di luar gudang tugas Anda.");
            }

            return $warehouse->id;
        }

        if ($name !== null && trim((string) $name) !== '') {
            $warehouse = Warehouse::whereRaw('LOWER(name) = ?', [mb_strtolower(trim((string) $name))])->first();
            if (! $warehouse) {
                throw new RuntimeException("Baris {$rowNumber} ({$item}): Gudang '{$name}' tidak ditemukan. Gunakan nama gudang yang sudah terdaftar.");
            }
            if ($user && ! $user->canAccessWarehouse($warehouse->id)) {
                throw new RuntimeException("Baris {$rowNumber} ({$item}): Gudang berada di luar gudang tugas Anda.");
            }

            return $warehouse->id;
        }

        if ($user && $user->assignedWarehouseId()) {
            return $user->assignedWarehouseId();
        }

        $warehouses = Warehouse::orderBy('id')->get(['id', 'name']);
        if ($warehouses->count() === 1) {
            return $warehouses->first()->id;
        }

        if ($warehouses->isEmpty()) {
            throw new RuntimeException('Gudang belum tersedia. Buat gudang terlebih dahulu sebelum mengimpor data.');
        }

        throw new RuntimeException("Baris {$rowNumber} ({$item}): Kolom Gudang wajib diisi karena terdapat lebih dari satu gudang.");
    }
}

<?php

namespace App\Support;

use App\Models\Material;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

final class ImportWarehouse
{
    public static function resolve(array $row, int $rowNumber, string $item, bool $allowDefault = false): int
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

        $code = trim((string) ($row['kodematerial'] ?? $row['kodebarang'] ?? $row['kode'] ?? ''));
        $matchedWarehouses = collect();
        if ($code !== '') {
            $matchedWarehouses = Material::where('code', $code)->whereNotNull('warehouse_id')->distinct()->pluck('warehouse_id');
        }
        if ($matchedWarehouses->isEmpty() && $item !== '') {
            $matchedWarehouses = Material::where('name', $item)->whereNotNull('warehouse_id')->distinct()->pluck('warehouse_id');
        }
        if ($matchedWarehouses->count() === 1) {
            $warehouseId = (int) $matchedWarehouses->first();
            if ($user && ! $user->canAccessWarehouse($warehouseId)) {
                throw new RuntimeException("Baris {$rowNumber} ({$item}): Gudang berada di luar gudang tugas Anda.");
            }

            return $warehouseId;
        }

        $rawHouse = trim((string) ($row['blokrumah'] ?? $row['unitrumah'] ?? $row['rumah'] ?? $row['unit'] ?? $row['referensi'] ?? $row['referensirumahsupplier'] ?? ''));
        $rawCluster = trim((string) ($row['cluster'] ?? $row['namacluster'] ?? ''));
        if ($rawHouse !== '' || $rawCluster !== '') {
            try {
                $house = $rawHouse !== ''
                    ? ImportHouseIdentity::resolve($rawHouse, $rowNumber, $item, $rawCluster !== '' ? $rawCluster : null)
                    : null;
                $clusterName = $rawCluster !== '' ? $rawCluster : ($house?->cluster?->name ?? '');
                if ($clusterName !== '') {
                    $clusterWarehouse = $warehouses->first(
                        fn ($w) => str_contains(mb_strtolower($w->name), mb_strtolower($clusterName))
                    );
                    if ($clusterWarehouse && (! $user || $user->canAccessWarehouse($clusterWarehouse->id))) {
                        return (int) $clusterWarehouse->id;
                    }
                }
            } catch (\Throwable) {
                // Fall through if house resolution fails here; MaterialImport will validate house where required.
            }
        }

        if ($allowDefault) {
            $accessibleIds = $warehouses
                ->filter(fn ($w) => ! $user || $user->canAccessWarehouse($w->id))
                ->pluck('id');
            if ($accessibleIds->isNotEmpty()) {
                $primaryId = Material::whereIn('warehouse_id', $accessibleIds)
                    ->selectRaw('warehouse_id, COUNT(*) as total')
                    ->groupBy('warehouse_id')
                    ->orderByDesc('total')
                    ->value('warehouse_id');

                return (int) ($primaryId ?: $accessibleIds->first());
            }
        }

        throw new RuntimeException("Baris {$rowNumber} ({$item}): Kolom Gudang wajib diisi karena terdapat lebih dari satu gudang.");
    }
}

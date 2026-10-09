<?php

namespace App\Support;

use App\Models\House;
use App\Models\Material;
use App\Models\MaterialUsage;
use App\Models\StockIn;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class MaterialAllocationRecorder
{
    /**
     * Allocate a single line of material to a house.
     * Expects rows to be locked by the caller within a DB transaction.
     */
    public static function allocateLine(
        House $house,
        Material $material,
        ?int $stockInId,
        float $quantity,
        float $unitPrice,
        ?string $notes = null,
        ?string $proofPath = null,
        ?string $takenBy = null,
        ?User $actor = null,
    ): MaterialUsage {
        $actorId = $actor?->id ?? auth()->id();

        return MaterialUsage::create([
            'transaction_code' => 'KLR-'.Str::ulid(),
            'house_id' => $house->id,
            'material_id' => $material->id,
            'stock_in_id' => $stockInId,
            'user_id' => $actorId,
            'quantity' => $quantity,
            'unit_price_at_usage' => $unitPrice,
            'total_cost' => round($quantity * $unitPrice, 2),
            'usage_date' => now()->toDateString(),
            'notes' => $notes,
            'taken_by' => $takenBy,
            'proof_image' => $proofPath,
            'is_warranty' => $house->status === 'selesai' && $house->canReceiveAllocations(),
        ]);
    }

    /**
     * Allocate material to one or more houses from a selected batch.
     * Executes atomically inside a DB transaction with pessimistic row locking.
     *
     * @param  array<int>|Collection<int, House>  $houses
     * @return Collection<int, MaterialUsage>
     */
    public static function allocate(
        array|Collection $houses,
        int|Material $materialOrId,
        int|StockIn $batchOrId,
        float $quantityPerHouse,
        ?string $notes = null,
        ?string $proofPath = null,
        ?string $takenBy = null,
        ?User $actor = null,
    ): Collection {
        $actor = $actor ?? auth()->user();

        return DB::transaction(function () use ($houses, $materialOrId, $batchOrId, $quantityPerHouse, $notes, $proofPath, $takenBy, $actor) {
            $houseIds = $houses instanceof Collection
                ? $houses->pluck('id')->all()
                : array_map('intval', (array) $houses);

            $houseModels = House::forUser($actor)
                ->whereIn('id', $houseIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($houseModels->count() !== count($houseIds)) {
                throw ValidationException::withMessages([
                    'house_ids' => 'Salah satu rumah tujuan sudah tidak tersedia. Pilih ulang rumah.',
                ]);
            }

            $completedHouses = $houseModels->filter(fn ($h) => ! $h->canReceiveAllocations());
            if ($completedHouses->isNotEmpty()) {
                $names = $completedHouses->pluck('name')->join(', ');
                throw ValidationException::withMessages([
                    'house_ids' => "Rumah berikut sudah selesai masa garansinya: {$names}",
                ]);
            }

            $materialId = $materialOrId instanceof Material ? $materialOrId->id : (int) $materialOrId;
            $material = Material::whereKey($materialId)->lockForUpdate()->firstOrFail();

            $totalQuantityRequired = $quantityPerHouse * count($houseIds);
            if ((float) $material->stock + 0.001 < $totalQuantityRequired) {
                throw ValidationException::withMessages([
                    'material_quantity' => 'Stok bebas material berubah atau tidak mencukupi. Pilih ulang jumlah.',
                ]);
            }

            $batchId = $batchOrId instanceof StockIn ? $batchOrId->id : (int) $batchOrId;
            $batch = StockIn::whereKey($batchId)
                ->where('material_id', $material->id)
                ->whereNotNull('warehouse_id')
                ->lockForUpdate()
                ->first();

            if (! $batch) {
                throw ValidationException::withMessages([
                    'material_batch_id' => 'Batch material berubah. Pilih ulang batch.',
                ]);
            }

            if ((float) $batch->remaining_quantity + 0.001 < $totalQuantityRequired) {
                throw ValidationException::withMessages([
                    'material_quantity' => 'Saldo batch berubah atau tidak mencukupi. Pilih ulang jumlah material.',
                ]);
            }

            $usages = collect();
            $houseById = $houseModels->keyBy('id');
            foreach ($houseIds as $houseId) {
                $usages->push(self::allocateLine(
                    $houseById[$houseId],
                    $material,
                    $batch->id,
                    $quantityPerHouse,
                    (float) $batch->unit_price,
                    $notes,
                    $proofPath,
                    $takenBy,
                    $actor
                ));
            }

            $batch->decrement('remaining_quantity', $totalQuantityRequired);
            $material->decrement('stock', $totalQuantityRequired);

            return $usages;
        });
    }
}

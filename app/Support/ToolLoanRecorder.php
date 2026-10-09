<?php

namespace App\Support;

use App\Models\House;
use App\Models\Tool;
use App\Models\ToolUsage;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ToolLoanRecorder
{
    /**
     * Record a single tool checkout line for a house.
     * Expects stock and constraints already verified by the caller within a DB transaction.
     */
    public static function loanLine(
        House $house,
        Tool $tool,
        int $warehouseId,
        int $quantity,
        ?string $notes = null,
        ?string $proofPath = null,
        ?User $actor = null,
    ): ToolUsage {
        $actorId = $actor?->id ?? auth()->id();

        return ToolUsage::create([
            'transaction_code' => 'KLR-'.Str::ulid(),
            'house_id' => $house->id,
            'tool_id' => $tool->id,
            'warehouse_id' => $warehouseId,
            'warehouse_source_recorded' => true,
            'user_id' => $actorId,
            'quantity' => $quantity,
            'checkout_date' => now()->toDateString(),
            'notes' => $notes,
            'proof_image' => $proofPath,
            'is_warranty' => $house->status === 'selesai' && $house->canReceiveAllocations(),
        ]);
    }

    /**
     * Check out a tool to one or more houses from a specified warehouse.
     * Executes atomically inside a DB transaction with pessimistic balance locking.
     *
     * @param  array<int>|Collection<int, House>  $houses
     * @return Collection<int, ToolUsage>
     */
    public static function loan(
        array|Collection $houses,
        int|Tool $toolOrId,
        int $warehouseId,
        int $quantityPerHouse,
        ?string $notes = null,
        ?string $proofPath = null,
        ?User $actor = null,
    ): Collection {
        $actor = $actor ?? auth()->user();

        return DB::transaction(function () use ($houses, $toolOrId, $warehouseId, $quantityPerHouse, $notes, $proofPath, $actor) {
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

            $toolId = $toolOrId instanceof Tool ? $toolOrId->id : (int) $toolOrId;
            $tool = Tool::whereKey($toolId)->lockForUpdate()->firstOrFail();

            $totalQuantityRequired = $quantityPerHouse * count($houseIds);

            $balance = ToolInventory::lockBalance($tool, $warehouseId);
            if ((int) $balance->available_qty < $totalQuantityRequired) {
                throw ValidationException::withMessages([
                    'tool_quantity' => 'Jumlah alat bebas di gudang asal berubah atau tidak mencukupi.',
                ]);
            }

            // Reject duplicate active checkout for same tool + house
            $existing = ToolUsage::where('tool_id', $tool->id)
                ->whereIn('house_id', $houseIds)
                ->whereNull('return_date')
                ->whereNull('voided_at')
                ->pluck('house_id');

            if ($existing->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'tool_quantity' => 'Alat ini masih dipinjam oleh rumah: '.House::whereIn('id', $existing)->pluck('name')->join(', '),
                ]);
            }

            ToolInventory::checkout($tool, $warehouseId, $totalQuantityRequired);

            $usages = collect();
            $houseById = $houseModels->keyBy('id');
            foreach ($houseIds as $houseId) {
                $usages->push(self::loanLine(
                    $houseById[$houseId],
                    $tool,
                    $warehouseId,
                    $quantityPerHouse,
                    $notes,
                    $proofPath,
                    $actor
                ));
            }

            return $usages;
        });
    }
}

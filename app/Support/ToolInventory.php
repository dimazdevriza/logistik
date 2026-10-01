<?php

namespace App\Support;

use App\Models\Tool;
use App\Models\ToolWarehouseBalance;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class ToolInventory
{
    public static function ensureBalance(Tool $tool, int $warehouseId, ?int $available = null, ?int $broken = null): ToolWarehouseBalance
    {
        return ToolWarehouseBalance::firstOrCreate(
            ['tool_id' => $tool->id, 'warehouse_id' => $warehouseId],
            [
                'available_qty' => $available ?? 0,
                'qty_broken' => $broken ?? 0,
            ],
        );
    }

    public static function lockBalance(Tool $tool, int $warehouseId): ToolWarehouseBalance
    {
        return ToolWarehouseBalance::where('tool_id', $tool->id)
            ->where('warehouse_id', $warehouseId)
            ->lockForUpdate()
            ->first() ?? self::ensureBalance($tool, $warehouseId);
    }

    public static function checkout(Tool $tool, int $warehouseId, int $quantity): void
    {
        $balance = self::lockBalance($tool, $warehouseId);
        if ($balance->available_qty < $quantity) {
            throw ValidationException::withMessages([
                'tool_quantity' => 'Jumlah alat tersedia di gudang asal tidak mencukupi. Tersedia: '.$balance->available_qty,
            ]);
        }

        $balance->decrement('available_qty', $quantity);
        self::refreshAggregates($tool);
    }

    public static function receive(Tool $tool, int $warehouseId, int $good, int $broken): void
    {
        if ($good === 0 && $broken === 0) {
            return;
        }

        $balance = self::lockBalance($tool, $warehouseId);
        $balance->increment('available_qty', $good);
        $balance->increment('qty_broken', $broken);
        self::refreshAggregates($tool);
    }

    public static function transferAvailable(Tool $tool, int $sourceWarehouseId, int $destinationWarehouseId, int $quantity): void
    {
        $source = self::lockBalance($tool, $sourceWarehouseId);
        if ($source->available_qty < $quantity) {
            throw ValidationException::withMessages([
                'quantity' => 'Stok alat tersedia di gudang asal tidak mencukupi. Tersedia: '.$source->available_qty,
            ]);
        }

        $destination = self::lockBalance($tool, $destinationWarehouseId);
        $source->decrement('available_qty', $quantity);
        $destination->increment('available_qty', $quantity);
        self::refreshAggregates($tool);
    }

    public static function refreshAggregates(Tool $tool): void
    {
        $totals = ToolWarehouseBalance::where('tool_id', $tool->id)
            ->selectRaw('COALESCE(SUM(available_qty), 0) as available_qty, COALESCE(SUM(qty_broken), 0) as qty_broken')
            ->first();

        $tool->forceFill([
            'available_qty' => (int) $totals->available_qty,
            'qty_broken' => (int) $totals->qty_broken,
        ])->saveQuietly();
    }

    public static function balances(Tool $tool): Collection
    {
        return $tool->warehouseBalances()->with('warehouse:id,name')->orderBy('warehouse_id')->get();
    }
}

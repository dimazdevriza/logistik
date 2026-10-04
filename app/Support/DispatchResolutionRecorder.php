<?php

namespace App\Support;

use App\Models\DispatchReceiptLine;
use App\Models\DispatchResolutionEvent;
use App\Models\House;
use App\Models\Material;
use App\Models\MaterialToolRequest;
use App\Models\StockIn;
use App\Models\Tool;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class DispatchResolutionRecorder
{
    public static function record(
        int $requestId,
        User $actor,
        ?int $dispatchLineId,
        string $eventType,
        float $quantity,
        string $submissionKey,
        string $notes,
        ?int $warehouseId = null,
    ): DispatchResolutionEvent {
        return DB::transaction(function () use ($requestId, $actor, $dispatchLineId, $eventType, $quantity, $submissionKey, $notes, $warehouseId): DispatchResolutionEvent {
            $request = MaterialToolRequest::lockForUpdate()->findOrFail($requestId);
            $house = House::lockForUpdate()->findOrFail($request->house_id);
            abort_unless($house->isAccessibleBy($actor), 403);
            abort_unless(in_array($actor->role, ['admin', 'logistik', 'pengawas'], true), 403);
            if (! in_array($request->status, ['dispatched', 'partially_arrived', 'arrived', 'resolved'], true)) {
                throw ValidationException::withMessages(['resolutionNotes' => 'Pengiriman ini tidak dapat menerima penyelesaian selisih.']);
            }

            if ($event = DispatchResolutionEvent::where('submission_key', $submissionKey)->first()) {
                if ((int) $event->material_tool_request_id !== $requestId) {
                    throw ValidationException::withMessages(['resolutionNotes' => 'Kode formulir ini sudah dipakai untuk pengiriman lain. Muat ulang formulir.']);
                }

                return $event;
            }

            if (! in_array($eventType, ['declare_lost', 'return_to_warehouse', 'dispose_damaged'], true)
                || ! is_finite($quantity) || $quantity <= 0 || blank($notes)) {
                throw ValidationException::withMessages(['resolutionNotes' => 'Pilih tindakan, jumlah, dan alasan yang valid.']);
            }

            $lines = $request->dispatchLines()->orderBy('id')->lockForUpdate()->get();
            $line = $dispatchLineId ? $lines->firstWhere('id', $dispatchLineId) : null;
            if (($dispatchLineId && ! $line) || (! $dispatchLineId && $lines->isNotEmpty())) {
                throw ValidationException::withMessages(['resolutionLines' => 'Sumber pengiriman tidak cocok. Muat ulang formulir.']);
            }
            $sourceKey = $dispatchLineId ? (string) $dispatchLineId : 'legacy';
            $shipped = $line ? (float) $line->quantity : (float) $request->quantity;
            $unitPrice = $line ? (float) ($line->unit_price ?? 0) : (float) ($request->unit_price_at_dispatch ?? 0);
            $sourceWarehouseId = (int) ($line?->warehouse_id ?: $request->source_warehouse_id);
            if ($request->type === 'tool' && (floor($quantity) !== $quantity)) {
                throw ValidationException::withMessages(['resolutionQuantity' => 'Jumlah alat harus berupa unit bulat.']);
            }

            $receiptQuery = DispatchReceiptLine::query()->whereHas('receipt', fn ($query) => $query->where('material_tool_request_id', $requestId));
            $dispatchLineId ? $receiptQuery->where('dispatch_line_id', $dispatchLineId) : $receiptQuery->whereNull('dispatch_line_id');
            $received = (float) (clone $receiptQuery)->sum('received_quantity');
            $damaged = (float) (clone $receiptQuery)->sum('damaged_quantity');

            $priorEvents = DispatchResolutionEvent::where('material_tool_request_id', $requestId);
            $dispatchLineId ? $priorEvents->where('dispatch_line_id', $dispatchLineId) : $priorEvents->whereNull('dispatch_line_id');
            $returned = (float) (clone $priorEvents)->where('event_type', 'return_to_warehouse')->sum('quantity');
            $lost = (float) (clone $priorEvents)->where('event_type', 'declare_lost')->sum('quantity');
            $disposed = (float) (clone $priorEvents)->where('event_type', 'dispose_damaged')->sum('quantity');

            if ($eventType === 'dispose_damaged') {
                if ($request->type !== 'material' || $quantity - ($damaged - $disposed) > 0.001) {
                    throw ValidationException::withMessages(['resolutionQuantity' => 'Jumlah pembuangan melebihi material rusak yang belum diselesaikan.']);
                }
            } elseif ($quantity - ($shipped - $received - $returned - $lost) > 0.001) {
                throw ValidationException::withMessages(['resolutionQuantity' => 'Jumlah melebihi selisih pengiriman yang masih terbuka.']);
            }

            $savedWarehouseId = null;
            if ($eventType === 'return_to_warehouse') {
                $savedWarehouseId = $line ? (int) $line->warehouse_id : ($warehouseId ?: $sourceWarehouseId);
                if (! $savedWarehouseId || ($dispatchLineId && (int) $savedWarehouseId !== (int) $line->warehouse_id)) {
                    throw ValidationException::withMessages(['resolutionWarehouseId' => 'Barang harus dikembalikan ke gudang asal pengiriman.']);
                }
                Warehouse::whereKey($savedWarehouseId)->firstOrFail();

                if ($request->type === 'material') {
                    $material = Material::lockForUpdate()->findOrFail($request->material_id);
                    if ($line?->stock_in_id) {
                        $batch = StockIn::lockForUpdate()->findOrFail($line->stock_in_id);
                        if ($batch->remaining_quantity === null || (float) $batch->remaining_quantity + $quantity - (float) $batch->quantity > 0.001) {
                            throw ValidationException::withMessages(['resolutionQuantity' => 'Pengembalian melebihi saldo batch sumber.']);
                        }
                        $batch->increment('remaining_quantity', $quantity);
                    }
                    $material->increment('stock', $quantity);
                } else {
                    $tool = Tool::lockForUpdate()->findOrFail($request->tool_id);
                    ToolInventory::receive($tool, $savedWarehouseId, (int) $quantity, 0);
                }
            }

            if ($eventType === 'declare_lost' && $request->type === 'tool') {
                $tool = Tool::lockForUpdate()->findOrFail($request->tool_id);
                if ((int) $tool->total_qty < (int) $quantity) {
                    throw ValidationException::withMessages(['resolutionQuantity' => 'Jumlah kehilangan melebihi total alat tercatat.']);
                }
                $tool->decrement('total_qty', (int) $quantity);
            }

            $event = DispatchResolutionEvent::create([
                'material_tool_request_id' => $requestId,
                'dispatch_line_id' => $dispatchLineId,
                'warehouse_id' => $savedWarehouseId,
                'recorded_by_id' => $actor->id,
                'event_type' => $eventType,
                'submission_key' => $submissionKey,
                'quantity' => $quantity,
                'unit_price' => $request->type === 'material' && in_array($eventType, ['declare_lost'], true) ? $unitPrice : null,
                'total_cost' => $request->type === 'material' && $eventType === 'declare_lost' ? round($quantity * $unitPrice, 2) : null,
                'notes' => trim($notes),
                'recorded_at' => now(),
            ]);

            self::syncStatus($request);

            return $event;
        });
    }

    public static function recordMaterialDamage(
        MaterialToolRequest $request,
        DispatchReceiptLine $receiptLine,
        User $actor,
        float $quantity,
        float $unitPrice,
        string $submissionKey,
        string $notes,
    ): void {
        if (abs($quantity) < 0.001) {
            return;
        }

        DispatchResolutionEvent::create([
            'material_tool_request_id' => $request->id,
            'dispatch_line_id' => $receiptLine->dispatch_line_id,
            'dispatch_receipt_line_id' => $receiptLine->id,
            'recorded_by_id' => $actor->id,
            'event_type' => 'material_damage_loss',
            'submission_key' => $submissionKey,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_cost' => round($quantity * $unitPrice, 2),
            'notes' => $notes,
            'recorded_at' => now(),
        ]);
    }

    public static function syncStatus(MaterialToolRequest $request): void
    {
        $sources = $request->dispatchLines;
        $dispatched = $sources->isEmpty() ? (float) $request->quantity : (float) $sources->sum('quantity');
        $received = (float) DispatchReceiptLine::whereHas('receipt', fn ($query) => $query->where('material_tool_request_id', $request->id))->sum('received_quantity');
        $returned = (float) $request->resolutionEvents()->where('event_type', 'return_to_warehouse')->sum('quantity');
        $lost = (float) $request->resolutionEvents()->where('event_type', 'declare_lost')->sum('quantity');
        $accounted = $received + $returned + $lost;

        $status = $received >= $dispatched - 0.001
            ? 'arrived'
            : ($accounted >= $dispatched - 0.001
                ? 'resolved'
                : ($received > 0.001 ? 'partially_arrived' : 'dispatched'));

        $request->update(['status' => $status]);
    }
}

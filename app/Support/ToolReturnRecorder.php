<?php

namespace App\Support;

use App\Models\Tool;
use App\Models\ToolReturnLog;
use App\Models\ToolUsage;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ToolReturnRecorder
{
    /**
     * Record a tool return with tri-state condition triage (normal, broken, lost).
     * Handles remainder split for partial returns, inventory receipt, lost reduction, and audit logging.
     * Expects execution within a DB transaction.
     */
    public static function recordReturn(
        int|ToolUsage $usageOrId,
        int $qtyNormal,
        int $qtyBroken,
        int $qtyLost,
        int $receivingWarehouseId,
        ?string $notes = null,
        ?User $actor = null,
    ): ?ToolUsage {
        $actorId = $actor?->id ?? auth()->id();

        $usage = $usageOrId instanceof ToolUsage
            ? $usageOrId
            : ToolUsage::lockForUpdate()->findOrFail($usageOrId);

        if ($usage->return_date) {
            return null;
        }

        $tool = Tool::lockForUpdate()->findOrFail($usage->tool_id);
        $returnQty = $qtyNormal + $qtyBroken + $qtyLost;

        if ($returnQty > $usage->quantity) {
            throw ValidationException::withMessages([
                'returnSelections' => 'Jumlah kondisi untuk '.$tool->name.' melebihi jumlah dipinjam ('.$usage->quantity.').',
            ]);
        }

        $remainingQty = $usage->quantity - $returnQty;

        if ($remainingQty === 0) {
            $usage->update(['return_date' => now()->format('Y-m-d')]);
        } else {
            $usage->update(['quantity' => $returnQty, 'return_date' => now()->format('Y-m-d')]);
            ToolUsage::create([
                'transaction_code' => 'KLR-'.Str::ulid(),
                'dispatch_code' => $usage->dispatch_code,
                'dispatch_line_id' => $usage->dispatch_line_id,
                'house_id' => $usage->house_id,
                'tool_id' => $usage->tool_id,
                'warehouse_id' => $usage->warehouse_id,
                'warehouse_source_recorded' => $usage->warehouse_source_recorded,
                'user_id' => $usage->user_id,
                'quantity' => $remainingQty,
                'checkout_date' => $usage->checkout_date,
                'return_date' => null,
                'parent_usage_id' => $usage->id,
                'notes' => $usage->notes,
            ]);
        }

        if ($qtyNormal + $qtyBroken > 0) {
            ToolInventory::receive($tool, $receivingWarehouseId, $qtyNormal, $qtyBroken);
        }

        $conditions = [
            ['quantity' => $qtyNormal, 'report_type' => 'normal', 'status' => 'fixed'],
            ['quantity' => $qtyBroken, 'report_type' => 'broken', 'status' => 'received'],
            ['quantity' => $qtyLost, 'report_type' => 'lost', 'status' => 'discarded'],
        ];

        foreach ($conditions as $log) {
            if ($log['quantity'] < 1) {
                continue;
            }

            if ($log['report_type'] === 'lost' && $tool->total_qty < $log['quantity']) {
                throw new \RuntimeException('Jumlah alat hilang melebihi total alat tercatat.');
            }

            if ($log['report_type'] === 'broken') {
                $tool->condition = 'rusak';
            }

            if ($log['report_type'] === 'lost') {
                $tool->total_qty -= $log['quantity'];
            }

            ToolReturnLog::create([
                'tool_id' => $tool->id,
                'house_id' => $usage->house_id,
                'tool_usage_id' => $usage->id,
                'reported_by' => $actorId,
                'receiving_warehouse_id' => $log['report_type'] === 'lost' ? null : $receivingWarehouseId,
                'received_at' => $log['report_type'] === 'lost' ? null : now(),
                'received_by_id' => $log['report_type'] === 'lost' ? null : $actorId,
                'resolved_by_id' => $log['report_type'] === 'lost' ? $actorId : null,
                'resolved_at' => $log['report_type'] === 'lost' ? now() : null,
                'quantity' => $log['quantity'],
                'report_type' => $log['report_type'],
                'status' => $log['status'],
                'notes' => $notes ?: null,
            ]);
        }

        $tool->save();

        return $usage;
    }
}

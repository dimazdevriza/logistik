<?php

namespace App\Support;

use App\Models\DispatchReceipt;
use App\Models\DispatchReceiptLine;
use App\Models\DispatchResolutionEvent;
use App\Models\House;
use App\Models\Material;
use App\Models\MaterialToolRequest;
use App\Models\MaterialUsage;
use App\Models\Tool;
use App\Models\ToolUsage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class DispatchReceiptRecorder
{
    public static function confirm(
        int $requestId,
        User $actor,
        array $lines,
        ?UploadedFile $proof,
        ?string $notes,
        string $submissionKey,
        ?int $correctionReceiptId = null,
    ): DispatchReceipt {
        return DB::transaction(function () use ($requestId, $actor, $lines, $proof, $notes, $submissionKey, $correctionReceiptId): DispatchReceipt {
            $request = MaterialToolRequest::lockForUpdate()->findOrFail($requestId);
            $house = House::lockForUpdate()->findOrFail($request->house_id);
            abort_unless($house->isAccessibleBy($actor), 403);
            abort_unless(in_array($actor->role, ['admin', 'logistik', 'pengawas'], true), 403);
            $isCorrection = $correctionReceiptId !== null;
            if ($isCorrection && $actor->role !== 'admin') {
                abort(403);
            }

            if ($receipt = DispatchReceipt::where('submission_key', $submissionKey)->first()) {
                if ((int) $receipt->material_tool_request_id !== (int) $request->id) {
                    throw ValidationException::withMessages(['receiptLines' => 'Kode formulir ini sudah dipakai untuk pengiriman lain. Muat ulang formulir.']);
                }

                return $receipt;
            }

            if ($request->status === 'approved') {
                throw ValidationException::withMessages(['receiptLines' => 'Transaksi lama yang sudah disetujui tidak dapat menerima konfirmasi baru.']);
            }
            if (! $isCorrection && ! in_array($request->status, ['dispatched', 'partially_arrived'], true)) {
                throw ValidationException::withMessages(['receiptLines' => 'Pengiriman ini tidak lagi menunggu penerimaan.']);
            }
            if ($request->status === 'rejected') {
                throw ValidationException::withMessages(['receiptLines' => 'Pengiriman yang ditolak tidak dapat menerima barang.']);
            }

            $dispatchLines = $request->dispatchLines()->orderBy('id')->lockForUpdate()->get();
            $legacy = $dispatchLines->isEmpty();
            $sources = $legacy
                ? ['legacy' => ['id' => null, 'quantity' => (float) $request->quantity, 'unit_price' => (float) ($request->unit_price_at_dispatch ?? 0)]]
                : $dispatchLines->mapWithKeys(fn ($line) => [(string) $line->id => ['id' => $line->id, 'quantity' => (float) $line->quantity, 'unit_price' => (float) $line->unit_price]])->all();

            $correctionOf = null;
            if ($isCorrection) {
                $correctionOf = DispatchReceipt::with('lines')->whereKey($correctionReceiptId)
                    ->where('material_tool_request_id', $request->id)
                    ->lockForUpdate()
                    ->firstOrFail();
                $allowed = $correctionOf->lines->map(fn ($line) => $line->dispatch_line_id ? (string) $line->dispatch_line_id : 'legacy')->all();
                if (array_diff(array_map('strval', array_keys($lines)), $allowed)) {
                    throw ValidationException::withMessages(['receiptLines' => 'Koreksi hanya dapat mengubah baris pada konfirmasi yang dipilih.']);
                }
                if (blank($notes)) {
                    throw ValidationException::withMessages(['receiptNotes' => 'Alasan koreksi wajib diisi.']);
                }
            }

            $submitted = [];
            foreach ($lines as $key => $value) {
                $key = (string) $key;
                if (! array_key_exists($key, $sources) || ! is_array($value)) {
                    throw ValidationException::withMessages(['receiptLines' => 'Salah satu baris sumber tidak valid. Muat ulang formulir.']);
                }

                $received = self::number($value['received_quantity'] ?? null, $key, 'received_quantity');
                $damaged = self::number($value['damaged_quantity'] ?? 0, $key, 'damaged_quantity');
                if ($damaged < 0 || $received < 0) {
                    throw ValidationException::withMessages(['receiptLines.'.$key => 'Jumlah diterima dan rusak tidak boleh negatif.']);
                }
                if ($damaged > $received) {
                    throw ValidationException::withMessages(['receiptLines.'.$key.'.damaged_quantity' => 'Jumlah rusak harus menjadi bagian dari jumlah diterima.']);
                }
                if ($request->type === 'tool' && (floor($received) !== $received || floor($damaged) !== $damaged)) {
                    throw ValidationException::withMessages(['receiptLines.'.$key => 'Jumlah alat harus berupa unit bulat.']);
                }

                $current = self::totals($request, $sources[$key]['id']);
                if ($isCorrection) {
                    $receivedDelta = $received - $current['received'];
                    $damagedDelta = $damaged - $current['damaged'];
                } else {
                    $receivedDelta = $received;
                    $damagedDelta = $damaged;
                    if (round($received, 2) === 0.0) {
                        continue;
                    }
                }

                $nextReceived = $current['received'] + $receivedDelta;
                $nextDamaged = $current['damaged'] + $damagedDelta;
                if ($nextReceived < -0.001 || $nextDamaged < -0.001 || $nextDamaged - $nextReceived > 0.001
                    || $nextReceived - $sources[$key]['quantity'] > 0.001) {
                    throw ValidationException::withMessages(['receiptLines.'.$key => 'Jumlah koreksi melebihi jumlah kirim atau membuat saldo penerimaan tidak valid.']);
                }

                if ($isCorrection) {
                    $resolutionQuery = DispatchResolutionEvent::where('material_tool_request_id', $request->id);
                    $sourceId = $sources[$key]['id'];
                    $sourceId ? $resolutionQuery->where('dispatch_line_id', $sourceId) : $resolutionQuery->whereNull('dispatch_line_id');
                    $resolvedTransit = (float) (clone $resolutionQuery)->whereIn('event_type', ['return_to_warehouse', 'declare_lost'])->sum('quantity');
                    $disposedDamage = (float) (clone $resolutionQuery)->where('event_type', 'dispose_damaged')->sum('quantity');
                    if ($nextReceived < $resolvedTransit - 0.001 || $nextDamaged < $disposedDamage - 0.001) {
                        throw ValidationException::withMessages(['receiptLines.'.$key => 'Koreksi tidak dapat mengurangi jumlah yang sudah dikembalikan, hilang, atau dibuang.']);
                    }
                }

                $submitted[$key] = [
                    'source' => $sources[$key],
                    'received' => $isCorrection ? $received : $receivedDelta,
                    'damaged' => $isCorrection ? $damaged : $damagedDelta,
                    'received_delta' => $receivedDelta,
                    'damaged_delta' => $damagedDelta,
                    'current' => $current,
                    'next' => ['received' => $nextReceived, 'damaged' => $nextDamaged],
                ];
            }

            if ($submitted === []) {
                throw ValidationException::withMessages(['receiptLines' => 'Masukkan jumlah barang yang benar-benar tiba.']);
            }

            $totalDispatched = array_sum(array_column($sources, 'quantity'));
            $totalAfter = 0.0;
            $damagedAfter = 0.0;
            foreach ($sources as $key => $source) {
                $totals = isset($submitted[$key])
                    ? $submitted[$key]['next']
                    : self::totals($request, $source['id']);
                $totalAfter += $totals['received'];
                $damagedAfter += $totals['damaged'];
            }

            $eventDamaged = array_sum(array_map(fn ($entry) => $entry['damaged_delta'], $submitted));
            $needsEvidence = ($isCorrection ? $damagedAfter > 0.001 : $eventDamaged > 0.001)
                || $totalAfter < $totalDispatched - 0.001;
            if ($needsEvidence && ! $proof) {
                throw ValidationException::withMessages(['arrivalProofImage' => 'Foto bukti wajib untuk barang rusak atau penerimaan yang belum sesuai jumlah kirim.']);
            }

            $receipt = DispatchReceipt::create([
                'material_tool_request_id' => $request->id,
                'received_by_id' => $actor->id,
                'correction_of_id' => $correctionOf?->id,
                'event_type' => $isCorrection ? 'correction' : 'confirmation',
                'submission_key' => $submissionKey,
                'received_at' => now(),
                'proof_image' => $proof?->store('arrival-proofs', 'public'),
                'notes' => $notes,
            ]);
            $dispatchReference = $request->dispatch_code ?? $request->request_code;

            foreach ($submitted as $entry) {
                $sourceId = $entry['source']['id'];
                $receiptLine = DispatchReceiptLine::create([
                    'dispatch_receipt_id' => $receipt->id,
                    'dispatch_line_id' => $sourceId,
                    'received_quantity' => $isCorrection ? $entry['received_delta'] : $entry['received'],
                    'damaged_quantity' => $isCorrection ? $entry['damaged_delta'] : $entry['damaged'],
                ]);

                if ($request->type === 'material') {
                    $usableDelta = $entry['received_delta'] - $entry['damaged_delta'];
                    if (abs($usableDelta) > 0.001) {
                        MaterialUsage::create([
                            'transaction_code' => 'KLR-'.Str::ulid(),
                            'dispatch_code' => $dispatchReference,
                            'house_id' => $request->house_id,
                            'material_id' => $request->material_id,
                            'stock_in_id' => $sourceId ? $dispatchLines->firstWhere('id', $sourceId)?->stock_in_id : null,
                            'user_id' => $request->requester_id,
                            'quantity' => $usableDelta,
                            'unit_price_at_usage' => $entry['source']['unit_price'],
                            'total_cost' => $usableDelta * $entry['source']['unit_price'],
                            'usage_date' => $receipt->received_at->toDateString(),
                            'notes' => $isCorrection ? 'Koreksi penerimaan: '.$notes : ($notes ?: $request->notes),
                        ]);
                    }

                    DispatchResolutionRecorder::recordMaterialDamage(
                        $request,
                        $receiptLine,
                        $actor,
                        $entry['damaged_delta'],
                        $entry['source']['unit_price'],
                        (string) Str::uuid(),
                        $isCorrection ? 'Koreksi kerugian rusak: '.$notes : ($notes ?: 'Barang rusak saat penerimaan.'),
                    );
                }
            }

            if ($request->type === 'tool') {
                self::recordToolUsage($request, $actor, $receipt, $dispatchLines, $sources, $submitted, $isCorrection, $notes, $dispatchReference);
            }

            $request->update([
                'arrival_proof_image' => $receipt->proof_image ?: $request->arrival_proof_image,
                'arrived_at' => $request->arrived_at ?: $receipt->received_at,
            ]);
            DispatchResolutionRecorder::syncStatus($request->fresh('dispatchLines'));

            return $receipt;
        });
    }

    private static function recordToolUsage(
        MaterialToolRequest $request,
        User $actor,
        DispatchReceipt $receipt,
        $dispatchLines,
        array $sources,
        array $submitted,
        bool $isCorrection,
        ?string $notes,
        string $dispatchReference,
    ): void {
        $tool = Tool::lockForUpdate()->findOrFail($request->tool_id);
        if ($isCorrection) {
            if (ToolUsage::where('dispatch_code', $dispatchReference)->whereNotNull('return_date')->exists()) {
                throw ValidationException::withMessages(['receiptLines' => 'Peminjaman alat yang sudah dikembalikan tidak dapat dikoreksi.']);
            }
            ToolUsage::where('dispatch_code', $dispatchReference)
                ->whereNull('voided_at')
                ->update(['voided_at' => now(), 'voided_by' => $actor->id]);
        } else {
            $otherActiveLoan = ToolUsage::where('tool_id', $tool->id)
                ->where('house_id', $request->house_id)
                ->whereNull('return_date')
                ->whereNull('voided_at')
                ->where(fn ($query) => $query->whereNull('dispatch_code')->orWhere('dispatch_code', '!=', $dispatchReference))
                ->exists();
            if ($otherActiveLoan) {
                throw ValidationException::withMessages(['receiptLines' => 'Alat ini masih dipinjam oleh rumah tersebut.']);
            }
        }

        foreach ($sources as $key => $source) {
            $totals = isset($submitted[$key]) ? $submitted[$key]['next'] : self::totals($request, $source['id']);
            $quantity = $isCorrection ? $totals['received'] : ($submitted[$key]['received'] ?? 0);
            if ($quantity < 1) {
                continue;
            }
            $sourceLine = $source['id'] ? $dispatchLines->firstWhere('id', $source['id']) : null;
            $warehouseId = $sourceLine?->warehouse_id ?: $request->source_warehouse_id;
            $damaged = $totals['damaged'];
            ToolUsage::create([
                'transaction_code' => 'KLR-'.Str::ulid(),
                'dispatch_code' => $dispatchReference,
                'dispatch_line_id' => $sourceLine?->id,
                'house_id' => $request->house_id,
                'tool_id' => $tool->id,
                'warehouse_id' => $warehouseId,
                'warehouse_source_recorded' => (bool) $warehouseId,
                'user_id' => $request->requester_id,
                'quantity' => (int) $quantity,
                'checkout_date' => $receipt->received_at->toDateString(),
                'notes' => trim(($isCorrection ? 'Koreksi penerimaan: '.$notes : ($notes ?: $request->notes)).($damaged > 0 ? ' | Diterima rusak: '.$damaged : '')),
            ]);
        }
    }

    private static function totals(MaterialToolRequest $request, ?int $dispatchLineId): array
    {
        $query = DispatchReceiptLine::whereHas('receipt', fn ($receipt) => $receipt->where('material_tool_request_id', $request->id));
        $dispatchLineId === null
            ? $query->whereNull('dispatch_line_id')
            : $query->where('dispatch_line_id', $dispatchLineId);

        return [
            'received' => (float) $query->sum('received_quantity'),
            'damaged' => (float) $query->sum('damaged_quantity'),
        ];
    }

    private static function number(mixed $value, string $key, string $field): float
    {
        if ($value === null || $value === '' || ! is_numeric($value) || ! is_finite((float) $value)) {
            throw ValidationException::withMessages(['receiptLines.'.$key.'.'.$field => 'Masukkan jumlah yang valid.']);
        }

        return round((float) $value, 2);
    }
}

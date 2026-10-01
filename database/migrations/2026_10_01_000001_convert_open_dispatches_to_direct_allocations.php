<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            if (DB::table('material_tool_requests')->where('status', 'pending')->exists()) {
                throw new RuntimeException('Resolve pending allocation requests before removing the dispatch queue.');
            }

            $requests = DB::table('material_tool_requests')
                ->whereIn('status', ['dispatched', 'partially_arrived'])
                ->orderBy('id')
                ->get();

            foreach ($requests as $request) {
                $sources = DB::table('dispatch_lines')
                    ->where('material_tool_request_id', $request->id)
                    ->orderBy('id')
                    ->get();

                if ($sources->isEmpty()) {
                    $sources = collect([(object) [
                        'id' => null,
                        'stock_in_id' => $request->stock_in_id,
                        'tool_id' => $request->tool_id,
                        'warehouse_id' => $request->source_warehouse_id,
                        'quantity' => $request->quantity,
                        'unit_price' => $request->unit_price_at_dispatch,
                    ]]);
                }

                $receiptLines = DB::table('dispatch_receipt_lines')
                    ->join('dispatch_receipts', 'dispatch_receipts.id', '=', 'dispatch_receipt_lines.dispatch_receipt_id')
                    ->where('dispatch_receipts.material_tool_request_id', $request->id)
                    ->get(['dispatch_receipt_lines.dispatch_line_id', 'dispatch_receipt_lines.received_quantity', 'dispatch_receipt_lines.damaged_quantity']);
                $resolutionEvents = DB::table('dispatch_resolution_events')
                    ->where('material_tool_request_id', $request->id)
                    ->whereIn('event_type', ['return_to_warehouse', 'declare_lost'])
                    ->get(['dispatch_line_id', 'event_type', 'quantity']);

                $materialTargets = [];
                $toolOutstanding = [];
                $conversionLines = [];

                foreach ($sources as $source) {
                    $lineKey = $source->id === null ? 'legacy' : (string) $source->id;
                    $lineReceipts = $receiptLines->filter(fn ($line) => ($line->dispatch_line_id === null ? 'legacy' : (string) $line->dispatch_line_id) === $lineKey);
                    $lineEvents = $resolutionEvents->filter(fn ($event) => ($event->dispatch_line_id === null ? 'legacy' : (string) $event->dispatch_line_id) === $lineKey);
                    $received = (float) $lineReceipts->sum('received_quantity');
                    $damaged = (float) $lineReceipts->sum('damaged_quantity');
                    $returned = (float) $lineEvents->where('event_type', 'return_to_warehouse')->sum('quantity');
                    $lost = (float) $lineEvents->where('event_type', 'declare_lost')->sum('quantity');
                    $quantity = (float) $source->quantity;
                    $outstanding = round($quantity - $received - $returned - $lost, 2);

                    if ($outstanding < -0.001) {
                        throw new RuntimeException("Allocation {$request->id} has more accounted quantity than was allocated.");
                    }

                    if ($outstanding > 0.001) {
                        $conversionLines[] = [
                            'dispatch_line_id' => $source->id,
                            'received_quantity' => $outstanding,
                            'damaged_quantity' => 0,
                        ];
                    }

                    if ($request->type === 'material') {
                        $batchKey = $source->stock_in_id === null ? 'legacy' : (string) $source->stock_in_id;
                        $materialTargets[$batchKey] ??= [
                            'stock_in_id' => $source->stock_in_id,
                            'unit_price' => (float) ($source->unit_price ?? $request->unit_price_at_dispatch ?? 0),
                            'quantity' => 0.0,
                        ];
                        $materialTargets[$batchKey]['quantity'] += max(0, $quantity - $damaged - $returned - $lost);
                    } else {
                        $toolOutstanding[] = [
                            'source' => $source,
                            'quantity' => max(0, $outstanding),
                        ];
                    }
                }

                if ($request->type === 'material') {
                    $existingUsages = DB::table('material_usages')
                        ->where('dispatch_code', $request->dispatch_code ?? $request->request_code)
                        ->whereNull('voided_at')
                        ->get(['stock_in_id', 'quantity'])
                        ->groupBy(fn ($usage) => $usage->stock_in_id === null ? 'legacy' : (string) $usage->stock_in_id);

                    foreach ($materialTargets as $key => $target) {
                        $existing = (float) ($existingUsages->get($key)?->sum('quantity') ?? 0);
                        $missing = round($target['quantity'] - $existing, 2);
                        if ($missing < -0.001) {
                            throw new RuntimeException("Allocation {$request->id} already has more material usage than its usable quantity.");
                        }
                        if ($missing <= 0.001) {
                            continue;
                        }

                        DB::table('material_usages')->insert([
                            'transaction_code' => 'KLR-'.Str::ulid(),
                            'dispatch_code' => $request->dispatch_code ?? $request->request_code,
                            'house_id' => $request->house_id,
                            'material_id' => $request->material_id,
                            'stock_in_id' => $target['stock_in_id'],
                            'user_id' => $request->requester_id,
                            'quantity' => $missing,
                            'unit_price_at_usage' => $target['unit_price'],
                            'total_cost' => round($missing * $target['unit_price'], 2),
                            'usage_date' => substr((string) ($request->dispatched_at ?? $request->created_at ?? now()), 0, 10),
                            'notes' => trim(($request->notes ?? '').' · Konversi alokasi lama ke rumah'),
                            'proof_image' => $request->dispatch_proof_image,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                } else {
                    foreach ($toolOutstanding as $entry) {
                        $source = $entry['source'];
                        if ($entry['quantity'] <= 0.001) {
                            continue;
                        }

                        DB::table('tool_usages')->insert([
                            'transaction_code' => 'KLR-'.Str::ulid(),
                            'dispatch_code' => $request->dispatch_code ?? $request->request_code,
                            'dispatch_line_id' => $source->id,
                            'house_id' => $request->house_id,
                            'tool_id' => $request->tool_id,
                            'warehouse_id' => $source->warehouse_id ?: $request->source_warehouse_id,
                            'warehouse_source_recorded' => true,
                            'user_id' => $request->requester_id,
                            'quantity' => (int) $entry['quantity'],
                            'checkout_date' => substr((string) ($request->dispatched_at ?? $request->created_at ?? now()), 0, 10),
                            'notes' => trim(($request->notes ?? '').' · Konversi alokasi lama ke rumah'),
                            'proof_image' => $request->dispatch_proof_image,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                if ($conversionLines !== []) {
                    $receiptId = DB::table('dispatch_receipts')->insertGetId([
                        'material_tool_request_id' => $request->id,
                        'received_by_id' => null,
                        'event_type' => 'conversion',
                        'submission_key' => (string) Str::uuid(),
                        'received_at' => now(),
                        'notes' => 'Sisa alokasi lama dikonversi menjadi alokasi langsung ke rumah.',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    foreach ($conversionLines as $line) {
                        DB::table('dispatch_receipt_lines')->insert([
                            'dispatch_receipt_id' => $receiptId,
                            'dispatch_line_id' => $line['dispatch_line_id'],
                            'received_quantity' => $line['received_quantity'],
                            'damaged_quantity' => $line['damaged_quantity'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                DB::table('material_tool_requests')->where('id', $request->id)->update([
                    'status' => 'approved',
                    'approved_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Converted house allocations are ledger records and cannot be safely rolled back.');
    }
};

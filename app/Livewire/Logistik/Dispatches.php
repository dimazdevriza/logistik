<?php

namespace App\Livewire\Logistik;

use App\Models\House;
use App\Models\DispatchLine;
use App\Models\DispatchReceipt;
use App\Models\DispatchReceiptLine;
use App\Models\DispatchResolutionEvent;
use App\Models\Material;
use App\Models\MaterialToolRequest;
use App\Models\MaterialUsage;
use App\Models\StockIn;
use App\Models\Tool;
use App\Models\ToolWarehouseBalance;
use App\Models\ToolUsage;
use App\Models\User;
use App\Support\ToolInventory;
use App\Support\DispatchReceiptRecorder;
use App\Support\DispatchResolutionRecorder;
use App\Traits\WithTableSorting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Dispatches extends Component
{
    use WithFileUploads, WithPagination, WithTableSorting;

    public string $queueView = 'transit';

    public string $search = '';

    public $sort = 'date_desc';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function showQueue(string $queueView): void
    {
        if (! in_array($queueView, ['transit', 'history'], true)) {
            return;
        }

        $this->queueView = $queueView;
        $this->resetPage();
    }

    public bool $showDispatchModal = false;

    public ?int $selectedRequestId = null;

    public array $dispatchLines = [['source_id' => '', 'quantity' => '']];

    public $dispatchProofImage = null;

    public bool $showReceiptModal = false;

    public ?int $receiptRequestId = null;

    public array $receiptLines = [];

    public $arrivalProofImage = null;

    public string $receiptNotes = '';

    public string $receiptSubmissionKey = '';

    public bool $showCorrectionModal = false;

    public ?int $correctionReceiptId = null;

    public array $correctionLines = [];

    public $correctionProofImage = null;

    public string $correctionNotes = '';

    public string $correctionSubmissionKey = '';

    public bool $showResolutionModal = false;

    public ?int $resolutionRequestId = null;

    public string $resolutionEventType = '';

    public string $resolutionItemType = '';

    public string $resolutionLineKey = '';

    public array $resolutionChoices = [];

    public array $resolutionWarehouseChoices = [];

    public $resolutionWarehouseId = '';

    public $resolutionQuantity = '';

    public string $resolutionNotes = '';

    public string $resolutionSubmissionKey = '';

    protected function sortableColumns(): array
    {
        return [
            'date' => 'material_tool_requests.created_at',
            'requester' => fn ($query, $direction) => $query->orderBy(
                User::select('name')->whereColumn('users.id', 'material_tool_requests.requester_id'),
                $direction
            ),
            'house' => fn ($query, $direction) => $query->orderBy(
                House::select('name')->whereColumn('houses.id', 'material_tool_requests.house_id'),
                $direction
            ),
            'quantity' => 'material_tool_requests.quantity',
            'status' => 'material_tool_requests.status',
        ];
    }

    public function dispatchRequest($requestId)
    {
        $request = MaterialToolRequest::with(['house', 'stockIn'])->findOrFail($requestId);
        $this->authorizeHouse($request->house);

        if ($request->status !== 'pending') {
            session()->flash('error', 'Alokasi ini sudah diproses.');

            return;
        }

        if ($request->house->status === 'selesai') {
            session()->flash('error', 'Rumah yang sudah selesai tidak dapat menerima pengiriman baru.');

            return;
        }

        $this->selectedRequestId = (int) $request->id;
        $this->dispatchLines = $this->allocateDispatchLines($request);
        if (abs(array_sum(array_column($this->dispatchLines, 'quantity')) - (float) $request->quantity) > 0.001) {
            $this->selectedRequestId = null;
            $this->dispatchLines = [];
            session()->flash('error', 'Stok batch yang tercatat tidak mencukupi untuk memenuhi permintaan.');

            return;
        }

        $this->dispatchProofImage = null;
        $this->resetValidation();
        $this->showDispatchModal = true;
    }

    public function updatedDispatchProofImage(): void
    {
        $this->resetValidation('dispatchProofImage');
    }

    public function submitDispatch(): void
    {
        $this->validate([
            'selectedRequestId' => ['required', 'integer'],
            'dispatchProofImage' => ['required', 'image', 'max:5120'],
        ], [
            'dispatchProofImage.required' => 'Foto barang yang dikirim wajib diunggah.',
            'dispatchProofImage.image' => 'Berkas harus berupa foto.',
            'dispatchProofImage.max' => 'Ukuran foto maksimal 5 MB.',
        ]);

        $proofPath = $this->dispatchProofImage->store('dispatch-proofs', 'public');

        try {
            $requestCode = DB::transaction(function () use ($proofPath): string {
            $requestSnapshot = MaterialToolRequest::findOrFail($this->selectedRequestId);
            if ($requestSnapshot->status !== 'pending') {
                throw ValidationException::withMessages(['dispatchProofImage' => 'Alokasi ini sudah diproses.']);
            }

            $item = $requestSnapshot->type === 'material'
                ? Material::lockForUpdate()->findOrFail($requestSnapshot->material_id)
                : null;
            $house = House::lockForUpdate()->findOrFail($requestSnapshot->house_id);
            $this->authorizeHouse($house);
            if ($house->status === 'selesai') {
                throw ValidationException::withMessages(['dispatchProofImage' => 'Rumah yang sudah selesai tidak dapat menerima pengiriman baru.']);
            }

            $lines = $this->allocateDispatchLines($requestSnapshot, true);
            $req = MaterialToolRequest::lockForUpdate()->findOrFail($requestSnapshot->id);
            if ($req->status !== 'pending'
                || (int) $req->house_id !== (int) $requestSnapshot->house_id
                || $req->type !== $requestSnapshot->type
                || (int) $req->material_id !== (int) $requestSnapshot->material_id
                || (int) $req->tool_id !== (int) $requestSnapshot->tool_id
                || (int) $req->stock_in_id !== (int) $requestSnapshot->stock_in_id
                || (int) $req->source_warehouse_id !== (int) $requestSnapshot->source_warehouse_id
                || (float) $req->quantity !== (float) $requestSnapshot->quantity) {
                throw ValidationException::withMessages(['dispatchProofImage' => 'Alokasi berubah saat pengiriman diproses. Muat ulang lalu coba lagi.']);
            }

            $totalQuantity = round((float) $req->quantity, 2);
            if (abs(array_sum(array_column($lines, 'quantity')) - $totalQuantity) > 0.001) {
                throw ValidationException::withMessages(['dispatchProofImage' => 'Stok batch berubah dan tidak lagi mencukupi. Muat ulang lalu coba lagi.']);
            }
            $normalizeLines = static fn (array $sourceLines): array => array_map(
                fn ($line) => [(int) $line['source_id'], round((float) $line['quantity'], 2)],
                array_values($sourceLines),
            );
            if ($normalizeLines($lines) !== $normalizeLines($this->dispatchLines)) {
                throw ValidationException::withMessages(['dispatchProofImage' => 'Rencana batch berubah. Tutup lalu buka kembali sebelum mengirim.']);
            }

            $dispatchCode = 'DSP-'.Str::ulid();
            $warehouseIds = [];
            $weightedCost = 0.0;

            if ($req->type === 'material') {
                if (! $item || (int) $item->id !== (int) $req->material_id) {
                    throw ValidationException::withMessages(['dispatchProofImage' => 'Material alokasi berubah. Muat ulang lalu coba lagi.']);
                }
                if ((float) $item->stock < $totalQuantity) {
                    throw ValidationException::withMessages(['dispatchProofImage' => 'Stok material gudang tidak mencukupi.']);
                }

                foreach ($lines as $line) {
                    $batch = StockIn::whereKey((int) $line['source_id'])
                        ->where('material_id', $item->id)
                        ->lockForUpdate()
                        ->first();
                    $quantity = round((float) $line['quantity'], 2);

                    if (! $batch || ! $batch->warehouse_id || $batch->remaining_quantity === null || (float) $batch->remaining_quantity < $quantity) {
                        throw ValidationException::withMessages(['dispatchProofImage' => 'Batch material berubah dan tidak lagi mencukupi. Muat ulang lalu coba lagi.']);
                    }

                    $batch->decrement('remaining_quantity', $quantity);
                    $unitPrice = (float) $batch->unit_price;
                    $weightedCost += $quantity * $unitPrice;
                    $warehouseIds[] = (int) $batch->warehouse_id;

                    DispatchLine::create([
                        'material_tool_request_id' => $req->id,
                        'stock_in_id' => $batch->id,
                        'warehouse_id' => $batch->warehouse_id,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                    ]);
                }

                $item->decrement('stock', $totalQuantity);
            } else {
                if ($totalQuantity < 1 || floor($totalQuantity) !== $totalQuantity) {
                    throw ValidationException::withMessages(['dispatchProofImage' => 'Jumlah alat harus berupa bilangan bulat minimal 1.']);
                }

                $tool = Tool::lockForUpdate()->findOrFail($req->tool_id);
                foreach ($lines as $line) {
                    $warehouseId = (int) $line['source_id'];
                    $quantity = (float) $line['quantity'];
                    if ($quantity < 1 || floor($quantity) !== $quantity) {
                        throw ValidationException::withMessages(['dispatchProofImage' => 'Jumlah alat harus berupa bilangan bulat minimal 1.']);
                    }

                    ToolInventory::checkout($tool, $warehouseId, (int) $quantity);
                    $warehouseIds[] = $warehouseId;
                    DispatchLine::create([
                        'material_tool_request_id' => $req->id,
                        'tool_id' => $tool->id,
                        'warehouse_id' => $warehouseId,
                        'quantity' => $quantity,
                        'unit_price' => $tool->purchase_price,
                    ]);
                }
            }

            $warehouseIds = array_values(array_unique($warehouseIds));
            $req->update([
                'dispatch_code' => $dispatchCode,
                'status' => 'dispatched',
                'dispatcher_id' => auth()->id(),
                'dispatched_at' => now(),
                'unit_price_at_dispatch' => $req->type === 'material' ? round($weightedCost / $totalQuantity, 2) : null,
                'source_warehouse_id' => count($warehouseIds) === 1 ? $warehouseIds[0] : null,
                'dispatch_proof_image' => $proofPath,
            ]);

            return $dispatchCode;
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($proofPath);
            throw $exception;
        }

        $this->showDispatchModal = false;
        $this->selectedRequestId = null;
        $this->dispatchLines = [['source_id' => '', 'quantity' => '']];
        $this->dispatchProofImage = null;
        session()->flash('success', 'Pengiriman '.$requestCode.' berhasil dicatat.');
    }

    private function allocateDispatchLines(MaterialToolRequest $request, bool $lockForUpdate = false): array
    {
        $remaining = round((float) $request->quantity, 2);
        $lines = [];

        if ($request->type === 'material') {
            $query = StockIn::query()
                ->where('material_id', $request->material_id)
                ->whereNotNull('warehouse_id');
            if ($request->stock_in_id) {
                if ($lockForUpdate) {
                    $query->lockForUpdate();
                }
                $source = $query->whereKey($request->stock_in_id)->first();
                $reservedByOthers = MaterialToolRequest::query()
                    ->where('type', 'material')
                    ->where('status', 'pending')
                    ->where('stock_in_id', $request->stock_in_id)
                    ->whereKeyNot($request->id)
                    ->sum('quantity');

                if (! $source || $source->remaining_quantity === null || (float) $source->remaining_quantity - (float) $reservedByOthers + 0.001 < (float) $request->quantity) {
                    return [];
                }

                return [[
                    'source_id' => (int) $source->id,
                    'quantity' => round((float) $request->quantity, 2),
                ]];
            }

            $query->whereAvailableQuantity()
                ->withReservedQuantity()
                ->orderByRaw('received_at IS NULL')
                ->orderBy('received_at')
                ->orderBy('id');
        } else {
            if ($lockForUpdate) {
                Tool::lockForUpdate()->findOrFail($request->tool_id);
            }

            $query = ToolWarehouseBalance::query()
                ->where('tool_id', $request->tool_id)
                ->where('available_qty', '>', 0)
                ->orderBy('warehouse_id');
            if ($request->source_warehouse_id) {
                $query->where('warehouse_id', $request->source_warehouse_id);
            }
        }

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        $reservedByWarehouse = $request->type === 'tool'
            ? MaterialToolRequest::query()
                ->where('type', 'tool')
                ->where('status', 'pending')
                ->where('tool_id', $request->tool_id)
                ->whereNotNull('source_warehouse_id')
                ->whereKeyNot($request->id)
                ->selectRaw('source_warehouse_id, SUM(quantity) as quantity')
                ->groupBy('source_warehouse_id')
                ->pluck('quantity', 'source_warehouse_id')
            : collect();

        foreach ($query->get() as $source) {
            if ($remaining <= 0.001) {
                break;
            }

            $available = $request->type === 'material'
                ? max(0, (float) $source->remaining_quantity - $source->reservedQuantityForUpdate())
                : max(0, (float) $source->available_qty - (float) ($reservedByWarehouse[$source->warehouse_id] ?? 0));
            $quantity = min($remaining, $available);
            $lines[] = [
                'source_id' => (int) ($request->type === 'material' ? $source->id : $source->warehouse_id),
                'quantity' => $request->type === 'material' ? round($quantity, 2) : (int) $quantity,
            ];
            $remaining = round($remaining - $quantity, 2);
        }

        return $lines;
    }

    public function openReceiptModal(int $requestId): void
    {
        $request = MaterialToolRequest::with(['house', 'dispatchLines.stockIn', 'dispatchLines.tool', 'dispatchLines.warehouse'])
            ->whereHas('house', fn ($query) => $query->forUser(auth()->user()))
            ->findOrFail($requestId);
        $this->authorizeHouse($request->house);
        if (! in_array($request->status, ['dispatched', 'partially_arrived'], true)) {
            session()->flash('error', 'Pengiriman ini tidak lagi menunggu penerimaan.');

            return;
        }

        $this->receiptRequestId = $request->id;
        $this->receiptLines = [];
        if ($request->dispatchLines->isEmpty()) {
            $totals = $this->receiptTotals($request->id, null);
            $this->receiptLines['legacy'] = [
                'label' => 'Sumber warisan (tidak tercatat)',
                'shipped_quantity' => (float) $request->quantity,
                'received_quantity' => 0,
                'damaged_quantity' => 0,
                'remaining_quantity' => max(0, (float) $request->quantity - $totals['received']),
            ];
        } else {
            foreach ($request->dispatchLines as $line) {
                $totals = $this->receiptTotals($request->id, $line->id);
                $source = $request->type === 'material'
                    ? ($line->stockIn?->entry_code ?? 'Batch tidak tercatat')
                    : ($line->tool?->entry_code ?? 'Kode alat tidak tercatat');
                $this->receiptLines[(string) $line->id] = [
                    'label' => $source.' · '.($line->warehouse?->name ?? 'Gudang tidak tercatat'),
                    'shipped_quantity' => (float) $line->quantity,
                    'received_quantity' => 0,
                    'damaged_quantity' => 0,
                    'remaining_quantity' => max(0, (float) $line->quantity - $totals['received']),
                ];
            }
        }

        $this->arrivalProofImage = null;
        $this->receiptNotes = '';
        $this->receiptSubmissionKey = (string) Str::uuid();
        $this->resetValidation();
        $this->showReceiptModal = true;
    }

    public function submitReceipt(): void
    {
        $this->validate([
            'receiptRequestId' => ['required', 'integer', 'exists:material_tool_requests,id'],
            'receiptLines' => ['required', 'array', 'min:1'],
            'receiptLines.*.received_quantity' => ['required', 'numeric', 'min:0'],
            'receiptLines.*.damaged_quantity' => ['required', 'numeric', 'min:0'],
            'arrivalProofImage' => ['nullable', 'image', 'max:5120'],
            'receiptNotes' => ['nullable', 'string', 'max:500'],
            'receiptSubmissionKey' => ['required', 'uuid'],
        ]);

        try {
            DispatchReceiptRecorder::confirm(
                $this->receiptRequestId,
                auth()->user(),
                collect($this->receiptLines)->map(fn ($line) => [
                    'received_quantity' => $line['received_quantity'],
                    'damaged_quantity' => $line['damaged_quantity'],
                ])->all(),
                $this->arrivalProofImage,
                $this->receiptNotes,
                $this->receiptSubmissionKey,
            );
            $this->showReceiptModal = false;
            $this->receiptRequestId = null;
            $this->receiptLines = [];
            $this->arrivalProofImage = null;
            session()->flash('success', 'Konfirmasi penerimaan tercatat. Biaya hanya dihitung untuk jumlah layak pakai.');
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            $this->addError('receiptLines', $exception->getMessage());
        }
    }

    public function openCorrectionModal(int $receiptId): void
    {
        abort_unless(auth()->user()->role === 'admin', 403);
        $receipt = DispatchReceipt::with(['request.dispatchLines', 'lines'])
            ->whereHas('request.house', fn ($query) => $query->forUser(auth()->user()))
            ->findOrFail($receiptId);
        $this->correctionReceiptId = $receipt->id;
        $this->correctionLines = [];
        foreach ($receipt->lines as $receiptLine) {
            $key = $receiptLine->dispatch_line_id ? (string) $receiptLine->dispatch_line_id : 'legacy';
            $totals = $this->receiptTotals($receipt->material_tool_request_id, $receiptLine->dispatch_line_id);
            $this->correctionLines[$key] = [
                'label' => $receiptLine->dispatch_line_id
                    ? ($receipt->request->dispatchLines->firstWhere('id', $receiptLine->dispatch_line_id)?->stockIn?->entry_code
                        ?? $receipt->request->dispatchLines->firstWhere('id', $receiptLine->dispatch_line_id)?->tool?->entry_code
                        ?? 'Sumber pengiriman')
                    : 'Sumber warisan (tidak tercatat)',
                'received_quantity' => $totals['received'],
                'damaged_quantity' => $totals['damaged'],
                'shipped_quantity' => $receiptLine->dispatch_line_id
                    ? (float) $receipt->request->dispatchLines->firstWhere('id', $receiptLine->dispatch_line_id)?->quantity
                    : (float) $receipt->request->quantity,
            ];
        }
        $this->correctionProofImage = null;
        $this->correctionNotes = '';
        $this->correctionSubmissionKey = (string) Str::uuid();
        $this->resetValidation();
        $this->showCorrectionModal = true;
    }

    public function submitCorrection(): void
    {
        abort_unless(auth()->user()->role === 'admin', 403);
        $this->validate([
            'correctionReceiptId' => ['required', 'integer', 'exists:dispatch_receipts,id'],
            'correctionLines' => ['required', 'array', 'min:1'],
            'correctionLines.*.received_quantity' => ['required', 'numeric', 'min:0'],
            'correctionLines.*.damaged_quantity' => ['required', 'numeric', 'min:0'],
            'correctionProofImage' => ['nullable', 'image', 'max:5120'],
            'correctionNotes' => ['required', 'string', 'max:500'],
            'correctionSubmissionKey' => ['required', 'uuid'],
        ]);

        try {
            $receipt = DispatchReceipt::findOrFail($this->correctionReceiptId);
            DispatchReceiptRecorder::confirm(
                $receipt->material_tool_request_id,
                auth()->user(),
                collect($this->correctionLines)->map(fn ($line) => [
                    'received_quantity' => $line['received_quantity'],
                    'damaged_quantity' => $line['damaged_quantity'],
                ])->all(),
                $this->correctionProofImage,
                $this->correctionNotes,
                $this->correctionSubmissionKey,
                $receipt->id,
            );
            $this->showCorrectionModal = false;
            $this->correctionReceiptId = null;
            $this->correctionLines = [];
            $this->correctionProofImage = null;
            session()->flash('success', 'Koreksi penerimaan ditambahkan ke riwayat.');
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            $this->addError('correctionLines', $exception->getMessage());
        }
    }

    public function openResolutionModal(int $requestId, string $eventType): void
    {
        abort_unless(in_array(auth()->user()->role, ['admin', 'logistik'], true), 403);
        abort_unless(in_array($eventType, ['declare_lost', 'return_to_warehouse', 'dispose_damaged'], true), 404);

        $request = MaterialToolRequest::with(['house', 'sourceWarehouse', 'material', 'tool', 'dispatchLines.stockIn', 'dispatchLines.warehouse', 'dispatchLines.tool', 'receipts.lines', 'resolutionEvents'])
            ->whereHas('house', fn ($query) => $query->forUser(auth()->user()))
            ->findOrFail($requestId);
        $this->authorizeHouse($request->house);
        if (! in_array($request->status, ['dispatched', 'partially_arrived', 'arrived', 'resolved'], true)) {
            throw ValidationException::withMessages(['resolutionNotes' => 'Pengiriman belum memiliki selisih yang dapat diselesaikan.']);
        }

        $this->resolutionChoices = [];
        $this->resolutionWarehouseChoices = [];
        $this->resolutionWarehouseId = '';
        $sources = $request->dispatchLines->isEmpty()
            ? collect([null])
            : $request->dispatchLines;

        foreach ($sources as $line) {
            $lineKey = $line ? (string) $line->id : 'legacy';
            $receiptLines = $request->receipts->flatMap->lines
                ->filter(fn ($receiptLine) => $receiptLine->dispatch_line_id === $line?->id);
            $events = $request->resolutionEvents->filter(fn ($event) => $event->dispatch_line_id === $line?->id);
            $received = (float) $receiptLines->sum('received_quantity');
            $damaged = (float) $receiptLines->sum('damaged_quantity');
            $returnedOrLost = (float) $events->whereIn('event_type', ['return_to_warehouse', 'declare_lost'])->sum('quantity');
            $disposed = (float) $events->where('event_type', 'dispose_damaged')->sum('quantity');
            $available = $eventType === 'dispose_damaged'
                ? $damaged - $disposed
                : (float) ($line?->quantity ?? $request->quantity) - $received - $returnedOrLost;
            if ($available <= 0.001) {
                continue;
            }

            $sourceName = $line?->stockIn?->entry_code ?? $line?->tool?->entry_code ?? 'Sumber warisan tidak tercatat';
            $warehouseName = $line?->warehouse?->name ?? $request->sourceWarehouse?->name;
            $this->resolutionChoices[$lineKey] = [
                'label' => $sourceName.' · '.($warehouseName ?? 'Gudang tidak tercatat'),
                'quantity' => round($available, 2),
            ];
        }

        if ($this->resolutionChoices === []) {
            session()->flash('error', 'Tidak ada jumlah yang memenuhi syarat untuk tindakan ini.');

            return;
        }

        if ($eventType === 'return_to_warehouse' && $request->dispatchLines->isEmpty()) {
            $this->resolutionWarehouseChoices = Warehouse::query()->orderBy('name')->pluck('name', 'id')->all();
            $this->resolutionWarehouseId = $request->source_warehouse_id ? (string) $request->source_warehouse_id : '';
        }

        $this->resolutionRequestId = $request->id;
        $this->resolutionEventType = $eventType;
        $this->resolutionItemType = $request->type;
        $this->resolutionLineKey = (string) array_key_first($this->resolutionChoices);
        $this->resolutionQuantity = $this->resolutionChoices[$this->resolutionLineKey]['quantity'];
        $this->resolutionNotes = '';
        $this->resolutionSubmissionKey = (string) Str::uuid();
        $this->resetValidation();
        $this->showResolutionModal = true;
    }

    public function updatedResolutionLineKey(string $lineKey): void
    {
        if (isset($this->resolutionChoices[$lineKey])) {
            $this->resolutionQuantity = $this->resolutionChoices[$lineKey]['quantity'];
        }
    }

    public function submitResolution(): void
    {
        abort_unless(in_array(auth()->user()->role, ['admin', 'logistik'], true), 403);
        $this->validate([
            'resolutionRequestId' => ['required', 'integer', 'exists:material_tool_requests,id'],
            'resolutionEventType' => ['required', 'in:declare_lost,return_to_warehouse,dispose_damaged'],
            'resolutionLineKey' => ['required', 'string'],
            'resolutionQuantity' => ['required', 'numeric', 'gt:0'],
            'resolutionNotes' => ['required', 'string', 'max:1000'],
            'resolutionSubmissionKey' => ['required', 'uuid'],
            'resolutionWarehouseId' => [$this->resolutionEventType === 'return_to_warehouse' && $this->resolutionWarehouseChoices !== [] ? 'required' : 'nullable', 'integer', 'exists:warehouses,id'],
        ]);

        $key = $this->resolutionLineKey;
        DispatchResolutionRecorder::record(
            (int) $this->resolutionRequestId,
            auth()->user(),
            $key === 'legacy' ? null : (int) $key,
            $this->resolutionEventType,
            (float) $this->resolutionQuantity,
            $this->resolutionSubmissionKey,
            $this->resolutionNotes,
            $this->resolutionWarehouseId !== '' ? (int) $this->resolutionWarehouseId : null,
        );

        $this->showResolutionModal = false;
        $this->resolutionChoices = [];
        $this->resolutionWarehouseChoices = [];
        $this->resolutionRequestId = null;
        session()->flash('success', 'Penyelesaian selisih tercatat beserta alasan, jumlah, dan pengguna.');
    }

    private function receiptTotals(int $requestId, ?int $dispatchLineId): array
    {
        $query = DispatchReceiptLine::whereHas('receipt', fn ($receipt) => $receipt->where('material_tool_request_id', $requestId));
        $dispatchLineId === null ? $query->whereNull('dispatch_line_id') : $query->where('dispatch_line_id', $dispatchLineId);

        return ['received' => (float) $query->sum('received_quantity'), 'damaged' => (float) $query->sum('damaged_quantity')];
    }

    public function approveRequest($requestId)
    {
        try {
            $requestCode = DB::transaction(function () use ($requestId) {
                $req = MaterialToolRequest::lockForUpdate()->findOrFail($requestId);
                if ($req->dispatch_code) {
                    throw new \RuntimeException('Pengiriman baru dicatat langsung saat konfirmasi penerimaan; persetujuan terpisah tidak diperlukan.');
                }
                if ($req->status !== 'arrived') {
                    throw new \RuntimeException('Hanya pengiriman yang sudah tiba di lapangan (dengan foto bukti) yang dapat diapprove.');
                }

                $house = House::lockForUpdate()->findOrFail($req->house_id);
                $this->authorizeHouse($house);
                if ($house->status === 'selesai') {
                    throw new \RuntimeException('Rumah yang sudah selesai tidak dapat menerima transaksi baru.');
                }

                $eventDate = ($req->arrived_at ?? $req->dispatched_at ?? now())->toDateString();
                $dispatchLines = $req->dispatchLines()->orderBy('id')->lockForUpdate()->get();

                if ($dispatchLines->isNotEmpty()
                    && abs(round((float) $dispatchLines->sum('quantity'), 2) - (float) $req->quantity) > 0.001) {
                    throw new \RuntimeException('Jumlah rincian pengiriman tidak sesuai dengan alokasi. Periksa riwayat sebelum menyetujui.');
                }
                if ($dispatchLines->isEmpty() && $req->dispatch_code) {
                    throw new \RuntimeException('Rincian sumber pengiriman tidak ditemukan. Transaksi tidak dapat disetujui.');
                }

                if ($req->type === 'material') {
                    $mat = Material::lockForUpdate()->findOrFail($req->material_id);
                    if ($dispatchLines->isNotEmpty()) {
                        foreach ($dispatchLines as $line) {
                            if ((int) $line->stock_in_id < 1) {
                                throw new \RuntimeException('Baris pengiriman material tidak memiliki batch sumber.');
                            }

                            MaterialUsage::create([
                                'transaction_code' => 'KLR-'.Str::ulid(),
                                'dispatch_code' => $req->dispatch_code,
                                'house_id' => $req->house_id,
                                'material_id' => $mat->id,
                                'stock_in_id' => $line->stock_in_id,
                                'user_id' => $req->requester_id,
                                'quantity' => $line->quantity,
                                'unit_price_at_usage' => $line->unit_price,
                                'total_cost' => $line->quantity * $line->unit_price,
                                'usage_date' => $eventDate,
                                'notes' => $req->notes,
                            ]);
                        }
                    } else {
                        $unitPrice = $req->unit_price_at_dispatch ?? $mat->unit_price;
                        MaterialUsage::create([
                            'transaction_code' => 'KLR-'.(str_starts_with((string) $req->request_code, 'REQ-') ? substr((string) $req->request_code, 4) : Str::ulid()),
                            'house_id' => $req->house_id,
                            'material_id' => $req->material_id,
                            'user_id' => $req->requester_id,
                            'quantity' => $req->quantity,
                            'unit_price_at_usage' => $unitPrice,
                            'total_cost' => $req->quantity * $unitPrice,
                            'usage_date' => $eventDate,
                            'notes' => $req->notes,
                        ]);
                    }
                } else {
                    $tool = Tool::lockForUpdate()->findOrFail($req->tool_id);
                    if ((int) $req->quantity < 1 || (float) $req->quantity !== (float) (int) $req->quantity) {
                        throw new \RuntimeException('Jumlah alat harus berupa bilangan bulat minimal 1.');
                    }
                    if (ToolUsage::where('tool_id', $tool->id)
                        ->where('house_id', $house->id)
                        ->whereNull('return_date')
                        ->whereNull('voided_at')
                        ->exists()) {
                        throw new \RuntimeException('Alat ini masih dipinjam oleh rumah tersebut.');
                    }

                    if ($dispatchLines->isNotEmpty()) {
                        foreach ($dispatchLines as $line) {
                            if ((int) $line->tool_id !== (int) $tool->id || (int) $line->warehouse_id < 1
                                || (float) $line->quantity < 1 || floor((float) $line->quantity) !== (float) $line->quantity) {
                                throw new \RuntimeException('Rincian sumber alat tidak valid.');
                            }

                            ToolUsage::create([
                                'transaction_code' => 'KLR-'.Str::ulid(),
                                'dispatch_code' => $req->dispatch_code,
                                'house_id' => $req->house_id,
                                'tool_id' => $tool->id,
                                'warehouse_id' => $line->warehouse_id,
                                'warehouse_source_recorded' => true,
                                'user_id' => $req->requester_id,
                                'quantity' => $line->quantity,
                                'checkout_date' => $eventDate,
                                'notes' => $req->notes,
                            ]);
                        }
                    } else {
                        $sourceWarehouseId = (int) $req->source_warehouse_id;
                        ToolUsage::create([
                            'transaction_code' => 'KLR-'.(str_starts_with((string) $req->request_code, 'REQ-') ? substr((string) $req->request_code, 4) : Str::ulid()),
                            'house_id' => $req->house_id,
                            'tool_id' => $req->tool_id,
                            'warehouse_id' => $sourceWarehouseId ?: null,
                            'warehouse_source_recorded' => (bool) $sourceWarehouseId,
                            'user_id' => $req->requester_id,
                            'quantity' => $req->quantity,
                            'checkout_date' => $eventDate,
                            'notes' => $req->notes,
                        ]);
                    }
                }

                $req->update([
                    'status' => 'approved',
                    'approver_id' => auth()->id(),
                    'approved_at' => now(),
                ]);

                return $req->request_code;
            });

            session()->flash('success', 'Transaksi '.$requestCode.' telah disetujui dan dicatat. Stok sudah dicadangkan saat pengiriman.');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function rejectRequest($requestId)
    {
        try {
            $requestCode = DB::transaction(function () use ($requestId) {
                $snapshot = MaterialToolRequest::findOrFail($requestId);
                if ($snapshot->status === 'pending') {
                    if ($snapshot->type === 'material') {
                        Material::lockForUpdate()->findOrFail($snapshot->material_id);
                    }
                    $house = House::lockForUpdate()->findOrFail($snapshot->house_id);
                    if ($snapshot->type === 'material' && $snapshot->stock_in_id) {
                        StockIn::whereKey($snapshot->stock_in_id)->lockForUpdate()->firstOrFail();
                    }
                } else {
                    $house = null;
                }

                $req = MaterialToolRequest::lockForUpdate()->findOrFail($requestId);
                if ($snapshot->status === 'pending'
                    && ((int) $req->house_id !== (int) $snapshot->house_id
                        || $req->type !== $snapshot->type
                        || (int) $req->material_id !== (int) $snapshot->material_id
                        || (int) $req->stock_in_id !== (int) $snapshot->stock_in_id)) {
                    throw new \RuntimeException('Alokasi berubah saat pembatalan diproses. Muat ulang lalu coba lagi.');
                }

                $house ??= House::lockForUpdate()->findOrFail($req->house_id);
                $this->authorizeHouse($house);
                if ($req->receipts()->exists()) {
                    throw new \RuntimeException('Penerimaan sudah tercatat. Selesaikan selisih melalui koreksi Admin atau tindak lanjut pengiriman.');
                }
                if (! in_array($req->status, ['pending', 'dispatched', 'arrived'], true)) {
                    throw new \RuntimeException('Alokasi ini tidak dapat ditolak pada status saat ini.');
                }

                if ($req->status === 'pending') {
                    $req->rejected_returned_at = now();
                    $req->rejected_returned_by_id = auth()->id();
                }

                $req->update([
                    'status' => 'rejected',
                    'approver_id' => auth()->id(),
                ]);

                return $req->request_code;
            });
            session()->flash('success', 'Alokasi '.$requestCode.' ditolak.');
        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function receiveRejectedReturn($requestId): void
    {
        try {
            $requestCode = DB::transaction(function () use ($requestId) {
                $req = MaterialToolRequest::lockForUpdate()->findOrFail($requestId);
                $this->authorizeHouse(House::lockForUpdate()->findOrFail($req->house_id));
                if ($req->status !== 'rejected') {
                    throw new \RuntimeException('Hanya barang dari alokasi yang ditolak yang dapat dicatat kembali.');
                }
                if ($req->rejected_returned_at) {
                    throw new \RuntimeException('Barang untuk alokasi ini sudah dicatat kembali.');
                }

                if ($req->dispatched_at) {
                    $lines = $req->dispatchLines()->lockForUpdate()->get();
                    if ($lines->isNotEmpty()) {
                        if ($req->type === 'material') {
                            Material::lockForUpdate()->findOrFail($req->material_id)->increment('stock', $lines->sum('quantity'));
                            foreach ($lines as $line) {
                                $batch = StockIn::lockForUpdate()->findOrFail($line->stock_in_id);
                                if ($batch->remaining_quantity === null || (float) $batch->remaining_quantity + (float) $line->quantity > (float) $batch->quantity + 0.001) {
                                    throw new \RuntimeException('Pengembalian melebihi saldo batch sumber. Periksa stok sebelum mencatat kembali.');
                                }
                                $batch->increment('remaining_quantity', $line->quantity);
                            }
                        } else {
                            $tool = Tool::lockForUpdate()->findOrFail($req->tool_id);
                            foreach ($lines as $line) {
                                ToolInventory::receive($tool, (int) $line->warehouse_id, (int) $line->quantity, 0);
                            }
                        }
                    } elseif ($req->type === 'material') {
                        Material::lockForUpdate()->findOrFail($req->material_id)->increment('stock', $req->quantity);
                    } else {
                        $tool = Tool::lockForUpdate()->findOrFail($req->tool_id);
                        $warehouseId = (int) ($req->source_warehouse_id ?: $tool->warehouse_id);
                        if (! $warehouseId) {
                            throw new \RuntimeException('Alat yang kembali belum memiliki gudang sumber yang terdaftar.');
                        }
                        ToolInventory::receive($tool, $warehouseId, (int) $req->quantity, 0);
                    }
                }

                $returnSources = isset($lines) && $lines->isNotEmpty() ? $lines : collect([null]);
                foreach ($returnSources as $line) {
                    DispatchResolutionEvent::create([
                        'material_tool_request_id' => $req->id,
                        'dispatch_line_id' => $line?->id,
                        'warehouse_id' => $line?->warehouse_id ?: $req->source_warehouse_id,
                        'recorded_by_id' => auth()->id(),
                        'event_type' => 'return_to_warehouse',
                        'submission_key' => (string) Str::uuid(),
                        'quantity' => $line?->quantity ?? $req->quantity,
                        'unit_price' => null,
                        'total_cost' => null,
                        'notes' => 'Pengiriman ditolak; barang kembali dan diverifikasi di gudang.',
                        'recorded_at' => now(),
                    ]);
                }

                $req->update([
                    'rejected_returned_at' => now(),
                    'rejected_returned_by_id' => auth()->id(),
                ]);

                return $req->request_code;
            });

            session()->flash('success', 'Barang dari '.$requestCode.' sudah dicatat kembali ke gudang.');
        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        $damageFollowUpIds = $this->damageFollowUpRequestIds();
        $queueCounts = collect(['transit', 'history'])
            ->mapWithKeys(fn ($queue) => [$queue => $this->requestsForQueue($queue, $damageFollowUpIds)->count()])
            ->all();

        $requests = $this->requestsForQueue($this->queueView, $damageFollowUpIds)
            ->with(['requester', 'dispatcher', 'approver', 'house', 'material', 'stockIn.warehouse', 'tool', 'sourceWarehouse', 'dispatchLines.stockIn', 'dispatchLines.tool', 'dispatchLines.warehouse', 'receipts.receivedBy', 'receipts.lines.dispatchLine.stockIn', 'receipts.lines.dispatchLine.tool', 'resolutionEvents.recordedBy', 'resolutionEvents.dispatchLine.stockIn'])
            ->tap(fn ($query) => $this->applyTableSort($query))
            ->orderByDesc('material_tool_requests.id')
            ->paginate(15);

        foreach ($requests as $request) {
            $shipped = $request->dispatchLines->isEmpty() ? (float) $request->quantity : (float) $request->dispatchLines->sum('quantity');
            $received = (float) $request->receipts->flatMap->lines->sum('received_quantity');
            $damaged = (float) $request->receipts->flatMap->lines->sum('damaged_quantity');
            $returned = (float) $request->resolutionEvents->where('event_type', 'return_to_warehouse')->sum('quantity');
            $lost = (float) $request->resolutionEvents->where('event_type', 'declare_lost')->sum('quantity');
            $settledTransit = $returned + $lost;
            $disposed = (float) $request->resolutionEvents->where('event_type', 'dispose_damaged')->sum('quantity');
            $request->dispatchQuantity = $shipped;
            $request->usableReceivedQuantity = max(0, $received - $damaged);
            $request->damagedReceivedQuantity = max(0, $damaged);
            $request->inTransitQuantity = max(0, $shipped - $received - $settledTransit);
            $request->returnedQuantity = $returned;
            $request->lostQuantity = $lost;
            $request->undisposedDamageQuantity = $request->type === 'material' ? max(0, $damaged - $disposed) : 0;
            $request->canResolveTransit = $request->inTransitQuantity > 0.001;
            $request->canDisposeDamage = $request->type === 'material' && $damaged - $disposed > 0.001;
        }

        $selectedRequest = $this->selectedRequestId
            ? MaterialToolRequest::with(['material', 'stockIn.warehouse', 'tool', 'house'])
                ->whereHas('house', fn ($house) => $house->forUser(auth()->user()))
                ->find($this->selectedRequestId)
            : null;

        $availableDispatchSources = collect();
        if ($selectedRequest?->type === 'material') {
            $sourceQuery = StockIn::with('warehouse:id,name')
                ->where('material_id', $selectedRequest->material_id)
                ->whereNotNull('warehouse_id');
            $availableDispatchSources = $selectedRequest->stock_in_id
                ? $sourceQuery->withReservedQuantity()->whereKey($selectedRequest->stock_in_id)->get()
                : $sourceQuery->whereAvailableQuantity()->withReservedQuantity()->orderBy('id')->get();
            $availableDispatchSources->each(function (StockIn $source) use ($selectedRequest): void {
                $reservedByOthers = $source->reservations()
                    ->whereKeyNot($selectedRequest->id)
                    ->sum('quantity');
                $source->setAttribute('dispatch_available_qty', max(0, (float) $source->remaining_quantity - (float) $reservedByOthers));
            });
        } elseif ($selectedRequest?->type === 'tool') {
            $sourceQuery = ToolWarehouseBalance::with('warehouse:id,name')
                ->where('tool_id', $selectedRequest->tool_id)
                ->where('available_qty', '>', 0)
                ->orderBy('warehouse_id');
            $availableDispatchSources = $selectedRequest->source_warehouse_id
                ? $sourceQuery->where('warehouse_id', $selectedRequest->source_warehouse_id)->get()
                : $sourceQuery->get();
            $reservedByWarehouse = MaterialToolRequest::query()
                ->where('type', 'tool')
                ->where('status', 'pending')
                ->where('tool_id', $selectedRequest->tool_id)
                ->whereNotNull('source_warehouse_id')
                ->whereKeyNot($selectedRequest->id)
                ->selectRaw('source_warehouse_id, SUM(quantity) as quantity')
                ->groupBy('source_warehouse_id')
                ->pluck('quantity', 'source_warehouse_id');
            $availableDispatchSources->each(fn (ToolWarehouseBalance $balance) => $balance->setAttribute(
                'dispatch_available_qty',
                max(0, (int) $balance->available_qty - (int) ($reservedByWarehouse[$balance->warehouse_id] ?? 0)),
            ));
        }

        return view('livewire.logistik.dispatches', [
            'requests' => $requests,
            'queueCounts' => $queueCounts,
            'clusterAssignmentMissing' => auth()->user()->role === 'logistik' && ! auth()->user()->cluster_id,
            'selectedRequest' => $selectedRequest,
            'availableDispatchSources' => $availableDispatchSources,
        ])
            ->layout('layouts.app', ['title' => 'Dalam Pengiriman']);
    }

    private function requestsForQueue(string $queueView, array $damageFollowUpIds)
    {
        $search = trim($this->search);

        $query = MaterialToolRequest::query()
            ->whereHas('house', fn ($house) => $house->forUser(auth()->user()))
            ->when($search !== '', function ($query) use ($search): void {
                $term = '%'.$search.'%';

                $query->where(function ($query) use ($term): void {
                    $query->where('material_tool_requests.request_code', 'like', $term)
                        ->orWhere('material_tool_requests.dispatch_code', 'like', $term)
                        ->orWhereHas('requester', fn ($requester) => $requester->where('name', 'like', $term))
                        ->orWhereHas('house', fn ($house) => $house->where('name', 'like', $term))
                        ->orWhereHas('material', fn ($material) => $material->where('name', 'like', $term))
                        ->orWhereHas('tool', fn ($tool) => $tool->where('name', 'like', $term));
                });
            });

        if ($queueView === 'transit') {
            return $query->where(function ($query) use ($damageFollowUpIds): void {
                $query->whereIn('material_tool_requests.status', ['dispatched', 'partially_arrived'])
                    ->orWhere(function ($query): void {
                        $query->where('material_tool_requests.status', 'rejected')
                            ->whereNotNull('material_tool_requests.dispatched_at')
                            ->whereNull('material_tool_requests.rejected_returned_at');
                    })
                    ->orWhereIn('material_tool_requests.id', $damageFollowUpIds);
            });
        }

        return $query->whereNotIn('material_tool_requests.status', ['dispatched', 'partially_arrived'])
            ->whereNotIn('material_tool_requests.id', $damageFollowUpIds)
            ->where(function ($query): void {
                $query->where('material_tool_requests.status', '!=', 'rejected')
                    ->orWhereNull('material_tool_requests.dispatched_at')
                    ->orWhereNotNull('material_tool_requests.rejected_returned_at');
            });
    }

    private function damageFollowUpRequestIds(): array
    {
        return MaterialToolRequest::with(['receipts.lines', 'resolutionEvents'])
            ->whereHas('house', fn ($house) => $house->forUser(auth()->user()))
            ->where('type', 'material')
            ->whereIn('status', ['arrived', 'resolved'])
            ->whereHas('receipts.lines', fn ($line) => $line->where('damaged_quantity', '>', 0))
            ->get()
            ->filter(function (MaterialToolRequest $request): bool {
                $damagedBySource = $request->receipts->flatMap->lines
                    ->groupBy(fn ($line) => $line->dispatch_line_id === null ? 'legacy' : (string) $line->dispatch_line_id);
                $disposedBySource = $request->resolutionEvents
                    ->where('event_type', 'dispose_damaged')
                    ->groupBy(fn ($event) => $event->dispatch_line_id === null ? 'legacy' : (string) $event->dispatch_line_id)
                    ->map(fn ($events) => (float) $events->sum('quantity'));

                foreach ($damagedBySource as $source => $receiptLines) {
                    if ((float) $receiptLines->sum('damaged_quantity') - (float) $disposedBySource->get($source, 0) > 0.001) {
                        return true;
                    }
                }

                return false;
            })
            ->modelKeys();
    }

    private function authorizeHouse(House $house): void
    {
        abort_unless($house->isAccessibleBy(auth()->user()), 403);
    }
}

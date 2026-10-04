<?php

namespace App\Livewire\Logistik;

use App\Exports\ToolInventoryExport;
use App\Imports\ToolImport;
use App\Models\Category;
use App\Models\ImportBatch;
use App\Models\InventoryAdjustment;
use App\Models\Tool;
use App\Models\ToolUsage;
use App\Models\Warehouse;
use App\Support\ToolInventory;
use App\Traits\WithFilterModal;
use App\Traits\WithTableSorting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class Tools extends Component
{
    use WithFileUploads, WithFilterModal, WithPagination, WithTableSorting;

    public $search = '';

    public $activeTab = 'inventory'; // inventory | maintenance

    public $showModal = false;

    public $saveSuccess = '';

    public $editMode = false;

    public $toolId;

    public $name = '';

    public $category_id = '';

    public $code = '';

    public $condition = 'baik';

    public $purchase_price = 0;

    public $total_qty = 1;

    public $available_qty = 1;

    public $qty_broken = 0;

    public $inventoryAdjustmentReason = '';

    public $hasMultipleWarehouseBalances = false;

    public $warehouse_id = '';

    public $receivedAt = '';

    public $submissionKey = '';

    public $image = null;

    public $existingImage = null;

    // Filters
    public $sort = 'code_asc';

    public $filterCategory = '';

    public $filterCondition = '';

    public $filterStock = '';

    public $filterPhoto = '';

    public $filterWarehouse = '';

    // Import Modal State
    public $showImportModal = false;

    public $importFile = null;

    public $importResultSummary = null;

    // Image Viewer Modal State
    public $showImageModal = false;

    public $viewingImageUrl = '';

    public $viewingImageToolName = '';

    // Confirmation Modal State
    public $showConfirmation = false;

    public $confirmingAction = '';

    public $confirmingId = null;

    public $confirmTitle = '';

    public $confirmMessage = '';

    public function confirm($action, $id = null, $title = '', $message = '')
    {
        $this->confirmingAction = $action;
        $this->confirmingId = $id;
        $this->confirmTitle = $title;
        $this->confirmMessage = $message;
        $this->showConfirmation = true;
    }

    public function executeConfirmedAction()
    {
        match ($this->confirmingAction) {
            'delete' => $this->delete($this->confirmingId),
            default => null,
        };

        $this->showConfirmation = false;
        $this->confirmingAction = '';
        $this->confirmingId = null;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSort(): void
    {
        $this->resetPage();
    }

    public function updatingFilterCategory(): void
    {
        $this->resetPage();
    }

    public function updatingFilterCondition(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStock(): void
    {
        $this->resetPage();
    }

    public function updatingFilterPhoto(): void
    {
        $this->resetPage();
    }

    public function updatingFilterWarehouse(): void
    {
        $this->resetPage();
    }

    protected function sortableColumns(): array
    {
        return [
            'code' => 'tools.code',
            'name' => 'tools.name',
            'category' => fn ($query, $direction) => $query->orderBy(
                Category::select('name')->whereColumn('categories.id', 'tools.category_id'),
                $direction
            ),
            'warehouse' => fn ($query, $direction) => $query->orderBy(
                Warehouse::select('name')->whereColumn('warehouses.id', 'tools.warehouse_id'),
                $direction
            ),
            'condition' => 'tools.condition',
            'price' => 'tools.purchase_price',
            'qty' => 'tools.total_qty',
            'broken' => 'tools.qty_broken',
            'available' => 'tools.available_qty',
            'date' => 'tools.created_at',
        ];
    }

    public function resetFilters(): void
    {
        $this->sort = 'code_asc';
        $this->filterCategory = '';
        $this->filterCondition = '';
        $this->filterStock = '';
        $this->filterPhoto = '';
        $this->filterWarehouse = '';
        $this->resetPage();
    }

    public function updatedCategoryId($value)
    {
        if (! $this->editMode && $value) {
            $this->code = $this->generateCode($value);
        }
    }

    private function generateCode($categoryId)
    {
        $category = Category::find($categoryId);
        if (! $category) {
            return '';
        }

        // Logical prefix from name: AB- (Alat Berat), AT- (Alat Tangan), etc.
        $words = explode(' ', $category->name);
        $prefix = (count($words) > 1 && strtolower($words[0]) === 'alat')
            ? 'A'.strtoupper(substr($words[1], 0, 1))
            : strtoupper(substr($category->name, 0, 2));

        $prefix .= '-';

        $lastTool = Tool::where('code', 'LIKE', $prefix.'%')
            ->orderByRaw('LENGTH(code) DESC')
            ->orderBy('code', 'DESC')
            ->first();

        if (! $lastTool) {
            return $prefix.'001';
        }

        // Extract numeric part Safely
        $parts = explode('-', $lastTool->code);
        $lastNumber = isset($parts[1]) ? (int) $parts[1] : 0;
        $nextNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);

        return $prefix.$nextNumber;
    }

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'code' => 'required|string|max:50|unique:tools,code,'.($this->toolId ?? 'NULL'),
            'condition' => 'required|in:baik,rusak,hilang',
            'purchase_price' => 'required|numeric|min:0',
            'total_qty' => 'required|integer|min:1',
            'available_qty' => 'required|integer|min:0',
            'qty_broken' => 'required|integer|min:0',
            'warehouse_id' => 'required|exists:warehouses,id',
            'receivedAt' => $this->editMode ? 'nullable|date' : 'required|date|before_or_equal:now',
            'submissionKey' => $this->editMode ? 'nullable|uuid' : 'required|uuid',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ];
    }

    public function create()
    {
        $this->resetForm();
        $this->receivedAt = now()->format('Y-m-d\\TH:i');
        $this->editMode = false;
        $this->showModal = true;
    }

    public function edit($id)
    {
        $tool = Tool::findOrFail($id);
        $this->toolId = $tool->id;
        $this->name = $tool->name;
        $this->category_id = $tool->category_id ?? '';
        $this->code = $tool->code;
        $this->condition = $tool->condition;
        $this->purchase_price = $tool->purchase_price;
        $this->total_qty = $tool->total_qty;
        $this->available_qty = $tool->available_qty;
        $this->qty_broken = $tool->qty_broken;
        $this->inventoryAdjustmentReason = '';
        $this->warehouse_id = $tool->warehouse_id ?? '';
        $this->receivedAt = '';
        $this->hasMultipleWarehouseBalances = $tool->warehouseBalances()->count() > 1;
        $this->existingImage = $tool->image;
        $this->image = null;
        $this->saveSuccess = '';
        $this->editMode = true;
        $this->showModal = true;
    }

    public function save()
    {
        if (! $this->editMode && $this->submissionKey && Tool::where('submission_key', $this->submissionKey)->exists()) {
            $this->resetForm();
            $this->receivedAt = now()->format('Y-m-d\\TH:i');
            $this->saveSuccess = 'Penerimaan alat sudah tercatat.';
            $this->showModal = true;

            return;
        }

        $this->validate();
        DB::transaction(fn () => $this->saveInventory());
    }

    private function saveInventory(): void
    {
        $existing = $this->editMode ? Tool::lockForUpdate()->findOrFail($this->toolId) : null;
        $balances = $existing ? $existing->warehouseBalances()->lockForUpdate()->get() : collect();
        $warehouseChanged = $existing && (int) $existing->warehouse_id !== (int) $this->warehouse_id;
        $distributionChanged = $existing && collect(['available_qty', 'qty_broken'])->contains(
            fn ($field) => (int) $existing->{$field} !== (int) $this->{$field}
        );
        if ($existing && (int) $existing->warehouse_id !== (int) $this->warehouse_id && $existing->usages()->exists()) {
            $this->addError('warehouse_id', 'Gudang tidak dapat diubah karena alat sudah memiliki riwayat peminjaman.');

            return;
        }
        if ($existing && $balances->count() > 1 && ($warehouseChanged || $distributionChanged)) {
            $this->addError('available_qty', 'Alat ini tersebar di beberapa gudang. Gunakan Transfer Gudang atau alur pengembalian untuk mengubah sebaran unit.');

            return;
        }
        $loaned = $existing ? (int) $existing->usages()->whereNull('return_date')->whereNull('voided_at')->sum('quantity') : 0;
        $inventoryFields = ['total_qty', 'available_qty', 'qty_broken'];
        $inventoryChanged = $existing && collect($inventoryFields)->contains(
            fn ($field) => (int) $existing->{$field} !== (int) $this->{$field}
        );
        if (($this->available_qty + $this->qty_broken + $loaned) > $this->total_qty) {
            $this->addError('available_qty', 'Jumlah unit tersedia ('.$this->available_qty.'), rusak ('.$this->qty_broken.'), dan dipinjam ('.$loaned.') tidak boleh melebihi Total Qty ('.$this->total_qty.').');

            return;
        }

        $data = [
            'name' => $this->name,
            'category_id' => $this->category_id ?: null,
            'code' => $this->code,
            'condition' => $this->condition,
            'purchase_price' => $this->purchase_price,
            'total_qty' => $this->total_qty,
            'available_qty' => $this->available_qty,
            'qty_broken' => $this->qty_broken,
            'warehouse_id' => $this->warehouse_id,
        ];

        if (! $this->editMode) {
            $receivedAt = Carbon::parse($this->receivedAt);
            $data['received_at'] = $receivedAt;
            $data['received_date'] = $receivedAt->toDateString();
            $data['submission_key'] = $this->submissionKey;
            $data['recorded_by_id'] = auth()->id();
        }

        if ($this->image) {
            $data['image'] = $this->image->store('tools', 'public');
        }

        if ($this->editMode) {
            if ($existing->image && isset($data['image'])) {
                // If new image uploaded, replace
            }
            $existing->update($data);
            if ($balances->count() <= 1) {
                $balance = $balances->first();
                if ($balance) {
                    $balance->update([
                        'warehouse_id' => $this->warehouse_id,
                        'available_qty' => $this->available_qty,
                        'qty_broken' => $this->qty_broken,
                    ]);
                } else {
                    $balance = ToolInventory::ensureBalance($existing, (int) $this->warehouse_id);
                    $balance->update([
                        'available_qty' => $this->available_qty,
                        'qty_broken' => $this->qty_broken,
                    ]);
                }
                ToolInventory::refreshAggregates($existing);
            }
            if ($inventoryChanged) {
                foreach ($inventoryFields as $field) {
                    $before = (int) $existing->getOriginal($field);
                    $after = (int) $this->{$field};
                    if ($before === $after) {
                        continue;
                    }
                    InventoryAdjustment::create([
                        'user_id' => auth()->id(),
                        'adjustable' => $existing,
                        'field' => $field,
                        'before_value' => $before,
                        'after_value' => $after,
                        'delta' => $after - $before,
                        'reason' => trim($this->inventoryAdjustmentReason) ?: 'Koreksi manual melalui formulir',
                    ]);
                }
            }
            cache()->forget('dashboard_tools_on_loan');
            session()->flash('success', 'Data alat kerja dan kuantitas inventaris berhasil diperbarui.');
            $this->showModal = false;
            $this->resetForm();

            return;
        }

        $tool = Tool::firstOrCreate(['submission_key' => $this->submissionKey], $data);
        if (! $tool->wasRecentlyCreated) {
            $this->resetForm();
            $this->receivedAt = now()->format('Y-m-d\\TH:i');
            $this->saveSuccess = 'Penerimaan alat sudah tercatat.';
            $this->showModal = true;

            return;
        }

        cache()->forget('dashboard_tools_on_loan');
        $this->resetForm();
        $this->receivedAt = now()->format('Y-m-d\\TH:i');
        $this->saveSuccess = 'Alat kerja baru berhasil ditambahkan.';
        $this->showModal = true;
    }

    public function showToolImage($toolId)
    {
        $tool = Tool::find($toolId);
        if ($tool && $tool->image) {
            $this->viewingImageUrl = asset('storage/'.$tool->image);
            $this->viewingImageToolName = $tool->name;
            $this->showImageModal = true;
        }
    }

    public function delete($id)
    {
        DB::transaction(function () use ($id) {
            $tool = Tool::lockForUpdate()->findOrFail($id);
            if ($tool->hasTransactionHistory()) {
                $this->addError('delete', 'Alat tidak dapat dihapus karena memiliki riwayat peminjaman, pengembalian, permintaan, atau transfer gudang. Riwayat transaksi harus tetap tersimpan.');

                return;
            }
            $tool->delete();
            session()->flash('success', 'Alat berhasil dihapus.');
        });
    }

    public function resetForm()
    {
        $this->toolId = null;
        $this->name = '';
        $this->category_id = '';
        $this->code = '';
        $this->condition = 'baik';
        $this->purchase_price = 0;
        $this->total_qty = 1;
        $this->available_qty = 1;
        $this->qty_broken = 0;
        $this->inventoryAdjustmentReason = '';
        $this->hasMultipleWarehouseBalances = false;
        $this->warehouse_id = Warehouse::orderBy('id')->value('id') ?? '';
        $this->receivedAt = '';
        $this->submissionKey = (string) Str::uuid();
        $this->saveSuccess = '';
        $this->image = null;
        $this->existingImage = null;
        $this->resetValidation();
    }

    public function openImportModal()
    {
        if (! in_array(auth()->user()->role, ['admin', 'logistik', 'keuangan', 'pengawas'], true)) {
            return;
        }
        $this->importFile = null;
        $this->importResultSummary = null;
        $this->resetValidation();
        $this->showImportModal = true;
    }

    public function importExcel()
    {
        if (! in_array(auth()->user()->role, ['admin', 'logistik', 'keuangan', 'pengawas'], true)) {
            return;
        }

        $this->validate([
            'importFile' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ], [
            'importFile.required' => 'Pilih berkas Excel (.xlsx / .xls) terlebih dahulu.',
            'importFile.mimes' => 'Berkas harus berupa format Excel (.xlsx, .xls) atau CSV.',
            'importFile.max' => 'Ukuran berkas maksimal 10MB.',
        ]);

        try {
            $import = ImportBatch::run('tool', $this->importFile, function (string $path) {
                $import = new ToolImport;
                Excel::import($import, $path);

                return $import;
            });

            $this->importResultSummary = [
                'totalRows' => $import->totalRows,
                'successfulRows' => $import->successfulRows,
                'skippedRows' => $import->skippedRows,
                'toolsImported' => $import->toolsImported,
                'transactionsImported' => $import->transactionsImported,
                'logs' => $import->rowLogs,
            ];

            session()->flash('success', "Proses validasi & impor selesai: {$import->successfulRows} dari {$import->totalRows} baris data berhasil diproses.");
            $this->resetPage();
        } catch (\Throwable $e) {
            $this->addError('importFile', 'Gagal memproses berkas Excel: '.$e->getMessage());
        }
    }

    public function exportExcel()
    {
        if (! in_array(auth()->user()->role, ['admin', 'logistik', 'keuangan', 'pengawas'], true)) {
            return;
        }

        $export = new ToolInventoryExport(
            $this->search,
            $this->filterCategory,
            $this->filterCondition,
            $this->filterWarehouse
        );
        $filename = 'tool-inventory-'.now()->format('Ymd').'.xlsx';

        return response()->streamDownload(function () use ($export) {
            echo Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);
        }, $filename);
    }

    public function render()
    {
        $query = Tool::with(['category', 'warehouse', 'warehouseBalances.warehouse', 'recordedBy'])
            ->when($this->search, fn ($q) => $q->where(function ($sub) {
                $sub->where('name', 'like', "%{$this->search}%")
                    ->orWhere('code', 'like', "%{$this->search}%")
                    ->orWhere('entry_code', 'like', "%{$this->search}%");
            }))
            ->when($this->filterCategory, fn ($q) => $q->where('category_id', $this->filterCategory))
            ->when($this->filterCondition, fn ($q) => $q->where('condition', $this->filterCondition))
            ->when($this->filterWarehouse, fn ($q) => $q->whereHas('warehouseBalances', fn ($balance) => $balance->where('warehouse_id', $this->filterWarehouse)))
            ->when($this->filterStock, function ($q) {
                match ($this->filterStock) {
                    'available' => $q->where('available_qty', '>', 0),
                    'empty' => $q->where('available_qty', '<=', 0),
                    'broken' => $q->where('qty_broken', '>', 0),
                    default => null,
                };
            })
            ->when($this->filterPhoto, function ($q) {
                match ($this->filterPhoto) {
                    'has_photo' => $q->whereNotNull('image')->where('image', '!=', ''),
                    'no_photo' => $q->where(fn ($sub) => $sub->whereNull('image')->orWhere('image', '')),
                    default => null,
                };
            });

        $this->applyTableSort($query)->orderBy('tools.id');

        $tools = $query->paginate(10);

        $categories = Category::where('type', 'tool')->orderBy('name')->get()->unique('name');
        $warehouses = Warehouse::orderBy('name')->get();

        // Stats
        $totalAvailable = Tool::sum('available_qty');
        $totalTools = Tool::sum('total_qty');
        $loanedCount = $this->showModal && $this->editMode
            ? (int) ToolUsage::where('tool_id', $this->toolId)->whereNull('return_date')->whereNull('voided_at')->sum('quantity')
            : 0;

        return view('livewire.logistik.tools', compact(
            'tools', 'categories', 'warehouses',
            'totalAvailable', 'totalTools', 'loanedCount'
        ))->layout('layouts.app', ['title' => 'Alat']);
    }
}

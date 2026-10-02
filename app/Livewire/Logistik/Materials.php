<?php

namespace App\Livewire\Logistik;

use App\Models\Material;
use App\Models\MaterialToolRequest;
use App\Models\ImportBatch;
use App\Models\StockIn;
use App\Models\Supplier;
use App\Models\Category;
use App\Models\Warehouse;
use App\Models\InventoryAdjustment;
use App\Exports\MaterialInventoryExport;
use App\Imports\MaterialImport;
use App\Traits\WithFilterModal;
use App\Traits\WithTableSorting;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Materials extends Component
{
    use WithPagination, WithFilterModal, WithFileUploads, WithTableSorting;

    public $search = '';
    public $filterCategory = '';
    public $filterSupplier = '';
    public $filterStock = '';
    public $filterPhoto = '';
    public $filterWarehouse = '';
    public $sort = 'name_asc';

    public $showModal = false;
    public $editMode = false;
    public string $createMode = 'new';
    public $existingMaterialId = '';
    public $materialId;
    public $saveSuccess = '';

    // Form: Create/Edit Material
    public $name = '';
    public $code = '';
    public $supplier_name = '';
    public $category_id = '';
    public $unit = '';
    public $unit_price = 0;
    public $stock = 0;
    public $receiptReceivedAt = '';
    public $receiptSubmissionKey = '';
    public $stockAdjustmentReason = '';
    public $warehouse_id = '';
    public $image = null;
    public $existingImage = null;

    // Restock Modal State
    public $restockMaterialId = null;
    public $restockQuantity = 1;
    public $restockUnitPrice = 0;
    public $restockSupplierName = '';
    public $restockReceivedAt = '';
    public $restockSubmissionKey = '';
    public $restockNotes = '';
    public $restockProofImage = null;

    // View Image Modal State
    public $showImageModal = false;
    public $viewingImageMaterialName = '';
    public $viewingImageUrl = '';

    // Import Modal State
    public $showImportModal = false;
    public $importFile = null;
    public $importResultSummary = null;

    // Confirmation Modal State
    public $showConfirmation = false;
    public $confirmingAction = '';
    public $confirmingId = null;
    public $confirmTitle = '';
    public $confirmMessage = '';

    public function confirm($action, $id = null, $title = '', $message = '')
    {
        $this->resetValidation('delete');
        if ($action === 'delete' && Material::findOrFail($id)->hasTransactionHistory()) {
            $this->addError('delete', 'Material tidak dapat dihapus karena memiliki riwayat stok, pemakaian, permintaan, atau transfer gudang. Riwayat transaksi harus tetap tersimpan.');
            return;
        }
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

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingSort(): void { $this->resetPage(); }
    public function updatingFilterCategory(): void { $this->resetPage(); }
    public function updatingFilterSupplier(): void { $this->resetPage(); }
    public function updatingFilterStock(): void { $this->resetPage(); }
    public function updatingFilterPhoto(): void { $this->resetPage(); }
    public function updatingFilterWarehouse(): void { $this->resetPage(); }

    protected function sortableColumns(): array
    {
        return [
            'name' => 'materials.name',
            'code' => 'materials.code',
            'category' => fn ($query, $direction) => $query->orderBy(
                Category::select('name')->whereColumn('categories.id', 'materials.category_id'),
                $direction
            ),
            'supplier' => fn ($query, $direction) => $query->orderBy(
                Supplier::select('name')->whereColumn('suppliers.id', 'stock_ins.supplier_id'),
                $direction
            ),
            'warehouse' => fn ($query, $direction) => $query->orderBy(
                Warehouse::select('name')->whereColumn('warehouses.id', 'stock_ins.warehouse_id'),
                $direction
            ),
            'stock' => 'stock_ins.remaining_quantity',
            'unit_price' => 'stock_ins.unit_price',
            'value' => fn ($query, $direction) => $query->orderByRaw('(stock_ins.remaining_quantity * stock_ins.unit_price) ' . $direction),
            'date' => fn ($query, $direction) => $query->orderByRaw('COALESCE(stock_ins.received_at, stock_ins.date) ' . $direction),
        ];
    }

    public function resetFilters(): void
    {
        $this->sort = 'name_asc';
        $this->filterCategory = '';
        $this->filterSupplier = '';
        $this->filterStock = '';
        $this->filterPhoto = '';
        $this->filterWarehouse = '';
        $this->showFilterModal = false;
        $this->resetPage();
    }

    public function updatedCategoryId($value)
    {
        if (!$this->editMode && $value) {
            $this->code = $this->generateCode($value);
        }
    }

    private function generateCode($categoryId)
    {
        $category = Category::find($categoryId);
        if (!$category) return '';

        $words = explode(' ', trim($category->name));
        $prefix = (count($words) > 1)
            ? strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1))
            : strtoupper(substr($category->name, 0, 2));

        $prefix .= '-';

        $lastMaterial = Material::where('code', 'LIKE', $prefix . '%')
            ->orderByRaw('LENGTH(code) DESC')
            ->orderBy('code', 'DESC')
            ->first();

        if (!$lastMaterial) {
            return $prefix . '001';
        }

        $parts = explode('-', $lastMaterial->code);
        $lastNumber = isset($parts[1]) ? (int) $parts[1] : 0;
        $nextNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);

        return $prefix . $nextNumber;
    }

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:materials,code,' . ($this->materialId ?? 'NULL'),
            'supplier_name' => 'nullable|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'unit' => 'required|string|max:50',
            'unit_price' => 'required|numeric|min:0',
            'stock' => 'required|numeric|min:' . ($this->editMode ? '0' : '0.01'),
            'warehouse_id' => 'required|exists:warehouses,id',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ];
    }

    public function create()
    {
        $this->resetForm();
        $this->resetRestockForm();
        $this->createMode = 'new';
        $this->existingMaterialId = '';
        $this->stock = 1;
        $this->receiptReceivedAt = now()->format('Y-m-d\\TH:i');
        $this->editMode = false;
        $this->showModal = true;
    }

    public function setCreateMode(string $mode): void
    {
        abort_unless(in_array($mode, ['new', 'existing'], true), 400);
        if ($this->createMode === $mode) {
            return;
        }
        $this->resetForm();
        $this->resetRestockForm();
        $this->createMode = $mode;
        $this->existingMaterialId = '';
        $this->editMode = false;
        $this->stock = 1;
        $this->receiptReceivedAt = now()->format('Y-m-d\\TH:i');
    }

    public function updatedExistingMaterialId($id): void
    {
        $this->selectExistingMaterial($id);
    }

    public function selectExistingMaterial($id): void
    {
        $this->resetValidation();
        $this->saveSuccess = '';
        if (! $id) {
            $this->resetForm();
            $this->resetRestockForm();
            $this->existingMaterialId = '';
            $this->stock = 1;
            $this->receiptReceivedAt = now()->format('Y-m-d\\TH:i');
            return;
        }

        $material = Material::with('supplier')->findOrFail($id);
        $this->existingMaterialId = (string) $material->id;
        $this->name = $material->name;
        $this->code = $material->code ?? '';
        $this->supplier_name = $material->supplier?->name ?? '';
        $this->category_id = $material->category_id ?? '';
        $this->unit = $material->unit;
        $this->unit_price = $material->unit_price;
        $this->warehouse_id = $material->warehouse_id ?? '';
        $this->stock = 1;
        $this->receiptReceivedAt = now()->format('Y-m-d\\TH:i');
        $this->receiptSubmissionKey = (string) Str::uuid();
        $this->image = null;
        $this->restockProofImage = null;
        $this->restockNotes = '';
        $this->restockMaterialId = $material->id;
        $this->restockQuantity = 1;
        $this->restockUnitPrice = $material->unit_price;
        $this->restockSupplierName = $material->supplier?->name ?? '';
        $this->restockReceivedAt = $this->receiptReceivedAt;
        $this->restockSubmissionKey = $this->receiptSubmissionKey;
    }

    public function showMaterialImage($id)
    {
        $material = Material::findOrFail($id);
        $this->viewingImageMaterialName = $material->name;
        $this->viewingImageUrl = asset('storage/' . $material->image);
        $this->showImageModal = true;
    }

    public function edit($id)
    {
        $material = Material::findOrFail($id);
        $this->materialId = $material->id;
        $this->name = $material->name;
        $this->code = $material->code ?? '';
        $this->supplier_name = $material->supplier?->name ?? '';
        $this->category_id = $material->category_id ?? '';
        $this->unit = $material->unit;
        $this->unit_price = $material->unit_price;
        $this->stock = $material->stock;
        $this->receiptReceivedAt = '';
        $this->stockAdjustmentReason = '';
        $this->warehouse_id = $material->warehouse_id ?? '';
        $this->image = null;
        $this->existingImage = $material->image;
        $this->saveSuccess = '';
        $this->editMode = true;
        $this->showModal = true;
    }

    public function save()
    {
        if (! $this->editMode && $this->createMode === 'existing') {
            $this->validate([
                'existingMaterialId' => 'required|exists:materials,id',
                'warehouse_id' => 'required|exists:warehouses,id',
                'stock' => 'required|numeric|min:0.01',
                'unit_price' => 'required|numeric|min:0',
                'supplier_name' => 'nullable|string|max:255',
                'receiptReceivedAt' => 'required|date|before_or_equal:now',
                'receiptSubmissionKey' => 'required|uuid',
                'restockNotes' => 'nullable|string|max:500',
                'restockProofImage' => 'nullable|image|max:5120',
            ]);
            $this->restockMaterialId = $this->existingMaterialId;
            $this->restockQuantity = $this->stock;
            $this->restockUnitPrice = $this->unit_price;
            $this->restockSupplierName = $this->supplier_name;
            $this->restockReceivedAt = $this->receiptReceivedAt;
            $this->restockSubmissionKey = $this->receiptSubmissionKey;
            $this->saveRestock();
            return;
        }

        if (! $this->editMode) {
            $this->validate([
                'receiptReceivedAt' => 'required|date|before_or_equal:now',
                'receiptSubmissionKey' => 'required|uuid',
            ]);
        }
        $this->validate();
        $existing = $this->editMode ? Material::findOrFail($this->materialId) : null;
        $stockChanged = $existing && abs((float) $existing->stock - (float) $this->stock) > 0.0001;
        if ($existing && (int) $existing->warehouse_id !== (int) $this->warehouse_id && $existing->hasTransactionHistory()) {
            $this->addError('warehouse_id', 'Gudang tidak dapat diubah karena material sudah memiliki riwayat transaksi.');
            return;
        }

        $final_supplier_id = null;
        if (!empty(trim($this->supplier_name))) {
            $supplier = Supplier::firstOrCreate(['name' => trim($this->supplier_name)]);
            $final_supplier_id = $supplier->id;
        }

        $code = $this->code;
        if (empty($code) && $this->category_id) {
            $code = $this->generateCode($this->category_id);
        }

        $data = [
            'name' => $this->name,
            'code' => $code ?: null,
            'supplier_id' => $final_supplier_id,
            'category_id' => $this->category_id ?: null,
            'unit' => $this->unit,
            'unit_price' => $this->unit_price,
            'stock' => $this->stock,
            'warehouse_id' => $this->warehouse_id,
        ];

        if ($this->image) {
            $data['image'] = $this->image->store('materials', 'public');
        }

        if ($this->editMode) {
            // Protect existing proof image from being overwritten once recorded
            if ($existing->image && isset($data['image'])) {
                unset($data['image']);
            }

            // Receipt and usage records retain their original quantities and prices.
            DB::transaction(function () use ($existing, $data): void {
                $material = Material::lockForUpdate()->findOrFail($existing->id);
                $beforeStock = (float) $material->stock;
                $stockChanged = abs($beforeStock - (float) $data['stock']) > 0.0001;

                if ($stockChanged) {
                    $reserved = MaterialToolRequest::where('material_id', $material->id)
                        ->where('type', 'material')
                        ->where('status', 'pending')
                        ->sum('quantity');
                    if ((float) $data['stock'] + 0.001 < (float) $reserved) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'stock' => 'Stok tidak dapat dikoreksi di bawah '.number_format((float) $reserved, 2, ',', '.').' unit yang sedang dicadangkan.',
                        ]);
                    }
                }

                $material->update($data);

                if ($stockChanged) {
                    InventoryAdjustment::create([
                        'user_id' => auth()->id(),
                        'adjustable' => $material,
                        'field' => 'stock',
                        'before_value' => $beforeStock,
                        'after_value' => $data['stock'],
                        'delta' => (float) $data['stock'] - $beforeStock,
                        'reason' => trim($this->stockAdjustmentReason) ?: 'Koreksi manual melalui formulir',
                    ]);
                }
            });

            // Bersihkan cache agregasi biaya agar dashboard & laporan langsung terperbarui
            cache()->forget('total_material_spent');
            cache()->forget('dashboard_total_cost');
            cache()->forget('dashboard_low_stock_count');

            session()->flash('success', 'Data material berhasil diperbarui. Riwayat transaksi tetap tersimpan tanpa perubahan.');
            $this->showModal = false;
            $this->resetForm();

            return;
        }

        $created = DB::transaction(function () use ($data, $final_supplier_id): bool {
            if (StockIn::where('submission_key', $this->receiptSubmissionKey)->exists()) {
                return false;
            }

            $data['stock'] = 0;
            $material = Material::create($data);
            $receivedAt = \Illuminate\Support\Carbon::parse($this->receiptReceivedAt);
            $receipt = StockIn::firstOrCreate([
                'submission_key' => $this->receiptSubmissionKey,
            ], [
                'material_id' => $material->id,
                'warehouse_id' => $this->warehouse_id,
                'supplier_id' => $final_supplier_id,
                'user_id' => auth()->id(),
                'quantity' => $this->stock,
                'unit_price' => $this->unit_price,
                'total_cost' => $this->stock * $this->unit_price,
                'date' => $receivedAt->toDateString(),
                'received_at' => $receivedAt,
                'notes' => 'Penerimaan awal',
            ]);

            if (! $receipt->wasRecentlyCreated) {
                $material->delete();
                return false;
            }

            $material->update(['stock' => $this->stock]);

            return true;
        });

        if (! $created) {
            $this->resetForm();
            $this->stock = 1;
            $this->receiptReceivedAt = now()->format('Y-m-d\\TH:i');
            $this->saveSuccess = 'Penerimaan material sudah tercatat.';
            $this->showModal = true;

            return;
        }

        cache()->forget('dashboard_low_stock_count');
        $this->resetForm();
        $this->stock = 1;
        $this->receiptReceivedAt = now()->format('Y-m-d\\TH:i');
        $this->saveSuccess = 'Material berhasil ditambahkan.';
        $this->showModal = true;
    }

    public function delete($id)
    {
        $this->resetValidation('delete');
        DB::transaction(function () use ($id) {
            $material = Material::lockForUpdate()->findOrFail($id);
            if ($material->hasTransactionHistory()) {
                $this->addError('delete', 'Material tidak dapat dihapus karena memiliki riwayat stok, pemakaian, permintaan, atau transfer gudang. Riwayat transaksi harus tetap tersimpan.');
                return;
            }
            $material->delete();
            session()->flash('success', 'Material berhasil dihapus.');
        });
    }

    public function resetForm()
    {
        $this->materialId = null;
        $this->name = '';
        $this->code = '';
        $this->supplier_name = '';
        $this->category_id = '';
        $this->unit = '';
        $this->unit_price = 0;
        $this->stock = 0;
        $this->receiptReceivedAt = '';
        $this->receiptSubmissionKey = (string) Str::uuid();
        $this->saveSuccess = '';
        $this->stockAdjustmentReason = '';
        $this->warehouse_id = Warehouse::orderBy('id')->value('id') ?? '';
        $this->image = null;
        $this->existingImage = null;
        $this->resetValidation();
    }

    public function restock($id)
    {
        $this->create();
        $this->createMode = 'existing';
        $this->selectExistingMaterial($id);
    }

    public function saveRestock()
    {
        $this->validate([
            'restockMaterialId' => 'required|exists:materials,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'restockQuantity' => 'required|numeric|min:0.01',
            'restockUnitPrice' => 'required|numeric|min:0',
            'restockSupplierName' => 'nullable|string|max:255',
            'restockReceivedAt' => 'required|date|before_or_equal:now',
            'restockSubmissionKey' => 'required|uuid',
            'restockNotes' => 'nullable|string|max:500',
            'restockProofImage' => 'nullable|image|max:5120',
        ]);

        $proofImagePath = null;
        if ($this->restockProofImage) {
            $proofImagePath = $this->restockProofImage->store('stock-in-proofs', 'public');
        }

        try {
            $created = DB::transaction(function () use ($proofImagePath): bool {
                if (StockIn::where('submission_key', $this->restockSubmissionKey)->exists()) {
                    return false;
                }

                $sourceMaterial = Material::lockForUpdate()->findOrFail($this->restockMaterialId);

                $supplierId = null;
                if (!empty(trim($this->restockSupplierName))) {
                    $supplier = Supplier::firstOrCreate(['name' => trim($this->restockSupplierName)]);
                    $supplierId = $supplier->id;
                }

                $destinationMaterial = Material::where('warehouse_id', $this->warehouse_id)
                    ->where('name', $sourceMaterial->name)
                    ->where('unit', $sourceMaterial->unit)
                    ->where('category_id', $sourceMaterial->category_id)
                    ->where('code', $sourceMaterial->code)
                    ->lockForUpdate()
                    ->first();

                if (! $destinationMaterial) {
                    $destinationMaterial = Material::create([
                        'warehouse_id' => $this->warehouse_id,
                        'code' => $sourceMaterial->code,
                        'supplier_id' => $supplierId,
                        'category_id' => $sourceMaterial->category_id,
                        'name' => $sourceMaterial->name,
                        'unit' => $sourceMaterial->unit,
                        'unit_price' => $this->restockUnitPrice,
                        'stock' => 0,
                        'image' => $sourceMaterial->image,
                    ]);
                }

                $totalCost = $this->restockQuantity * $this->restockUnitPrice;

                $receivedAt = \Illuminate\Support\Carbon::parse($this->restockReceivedAt);
                $receipt = StockIn::firstOrCreate(['submission_key' => $this->restockSubmissionKey], [
                    'material_id' => $destinationMaterial->id,
                    'warehouse_id' => $this->warehouse_id,
                    'supplier_id' => $supplierId,
                    'user_id' => auth()->id(),
                    'quantity' => $this->restockQuantity,
                    'unit_price' => $this->restockUnitPrice,
                    'total_cost' => $totalCost,
                    'date' => $receivedAt->toDateString(),
                    'received_at' => $receivedAt,
                    'notes' => $this->restockNotes,
                    'proof_image' => $proofImagePath,
                ]);

                if (! $receipt->wasRecentlyCreated) {
                    return false;
                }

                $destinationMaterial->increment('stock', $this->restockQuantity);
                return true;
            });

            if (! $created && $proofImagePath) {
                Storage::disk('public')->delete($proofImagePath);
            }
            $materialId = StockIn::where('submission_key', $this->restockSubmissionKey)->value('material_id')
                ?? $this->restockMaterialId;
            $message = $created ? 'Stok masuk berhasil dicatat. Kode masuk baru sudah dibuat.' : 'Penerimaan material sudah tercatat.';
            if ($this->showModal && $this->createMode === 'existing') {
                $this->selectExistingMaterial($materialId);
                $this->saveSuccess = $message;
            } else {
                session()->flash('success', $message);
                $this->resetRestockForm();
            }
            cache()->forget('dashboard_low_stock_count');
        } catch (\Exception $e) {
            if ($proofImagePath) {
                Storage::disk('public')->delete($proofImagePath);
            }
            $this->addError($this->showModal ? 'stock' : 'restockQuantity', 'Gagal menyimpan stok masuk: ' . $e->getMessage());
        }
    }

    public function resetRestockForm()
    {
        $this->restockMaterialId = null;
        $this->restockQuantity = 1;
        $this->restockUnitPrice = 0;
        $this->restockSupplierName = '';
        $this->restockReceivedAt = '';
        $this->restockSubmissionKey = (string) Str::uuid();
        $this->restockNotes = '';
        $this->restockProofImage = null;
        $this->resetValidation();
    }

    public function openImportModal()
    {
        if (!in_array(auth()->user()->role, ['admin', 'logistik', 'keuangan'], true)) return;
        $this->importFile = null;
        $this->importResultSummary = null;
        $this->resetValidation();
        $this->showImportModal = true;
    }

    public function importExcel()
    {
        if (!in_array(auth()->user()->role, ['admin', 'logistik', 'keuangan'], true)) return;

        $this->validate([
            'importFile' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ], [
            'importFile.required' => 'Pilih berkas Excel (.xlsx / .xls) terlebih dahulu.',
            'importFile.mimes' => 'Berkas harus berupa format Excel (.xlsx, .xls) atau CSV.',
            'importFile.max' => 'Ukuran berkas maksimal 10MB.',
        ]);

        try {
            $import = ImportBatch::run('material', $this->importFile, function (string $path) {
                $import = new MaterialImport();
                Excel::import($import, $path);

                return $import;
            });

            $this->importResultSummary = [
                'totalRows' => $import->totalRows,
                'successfulRows' => $import->successfulRows,
                'skippedRows' => $import->skippedRows,
                'materialsImported' => $import->materialsImported,
                'transactionsImported' => $import->transactionsImported,
                'logs' => $import->rowLogs,
            ];

            session()->flash('success', "Proses validasi & impor selesai: {$import->successfulRows} dari {$import->totalRows} baris data berhasil diproses.");
            $this->resetPage();
        } catch (\Throwable $e) {
            $this->addError('importFile', 'Gagal memproses berkas Excel: ' . $e->getMessage());
        }
    }

    public function exportExcel()
    {
        if (!in_array(auth()->user()->role, ['admin', 'logistik', 'keuangan'], true)) return;

        $export = new MaterialInventoryExport($this->search, $this->filterCategory, $this->filterWarehouse);
        $filename = 'material-inventory-' . now()->format('Ymd') . '.xlsx';

        return response()->streamDownload(function () use ($export) {
            echo Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);
        }, $filename);
    }

    public function render()
    {
        $materialBatches = StockIn::query()
            ->join('materials', 'materials.id', '=', 'stock_ins.material_id')
            ->select('stock_ins.*')
            ->with(['material.supplier', 'material.category', 'material.warehouse', 'supplier', 'warehouse'])
            ->when($this->search, fn ($q) => $q->where(fn ($sub) => $sub
                ->whereHas('material', fn ($material) => $material
                    ->where('name', 'like', "%{$this->search}%")
                    ->orWhere('code', 'like', "%{$this->search}%"))
                ->orWhere('entry_code', 'like', "%{$this->search}%")))
            ->when($this->filterCategory, fn ($q) => $q->whereHas('material', fn ($material) => $material->where('category_id', $this->filterCategory)))
            ->when($this->filterSupplier, fn ($q) => $q->where(fn ($sub) => $sub
                ->where('stock_ins.supplier_id', $this->filterSupplier)
                ->orWhere(fn ($fallback) => $fallback->whereNull('stock_ins.supplier_id')
                    ->whereHas('material', fn ($material) => $material->where('supplier_id', $this->filterSupplier)))))
            ->when($this->filterWarehouse, fn ($q) => $q->where(fn ($sub) => $sub
                ->where('stock_ins.warehouse_id', $this->filterWarehouse)
                ->orWhere(fn ($fallback) => $fallback->whereNull('stock_ins.warehouse_id')
                    ->whereHas('material', fn ($material) => $material->where('warehouse_id', $this->filterWarehouse)))))
            ->when($this->filterPhoto === 'has_photo', fn ($q) => $q->whereHas('material', fn ($material) => $material->whereNotNull('image')->where('image', '!=', '')))
            ->when($this->filterPhoto === 'no_photo', fn ($q) => $q->whereHas('material', fn ($material) => $material->where(fn ($sub) => $sub->whereNull('image')->orWhere('image', ''))))
            ->when($this->filterStock, function ($q) {
                if ($this->filterStock === 'low') {
                    $q->where('stock_ins.remaining_quantity', '<=', 10)->where('stock_ins.remaining_quantity', '>', 0);
                } elseif ($this->filterStock === 'safe') {
                    $q->where('stock_ins.remaining_quantity', '>', 10);
                } elseif ($this->filterStock === 'empty') {
                    $q->where(fn ($sub) => $sub->whereNull('stock_ins.remaining_quantity')->orWhere('stock_ins.remaining_quantity', '<=', 0));
                }
            })
            ->tap(fn ($query) => $this->applyTableSort($query))
            ->orderBy('materials.id')
            ->orderBy('stock_ins.id')
            ->get();

        $suppliers = Supplier::select('id', 'name')->orderBy('name')->get()->unique('name');
        $categories = Category::where('type', 'material')->orderBy('name')->get()->unique('name');
        $warehouses = Warehouse::orderBy('name')->get();
        $materialChoices = $this->showModal && ! $this->editMode && $this->createMode === 'existing'
            ? Material::with('warehouse')->orderBy('name')->orderBy('code')->get(['id', 'name', 'code', 'warehouse_id'])
            : collect();

        // Summary stats
        $totalValue = StockIn::where('remaining_quantity', '>', 0)
            ->selectRaw('SUM(unit_price * remaining_quantity) as total')
            ->value('total') ?? 0;
        $totalItems = Material::where('stock', '>', 0)->count();

        return view('livewire.logistik.materials', compact('materialBatches', 'suppliers', 'categories', 'warehouses', 'materialChoices', 'totalValue', 'totalItems'))
            ->layout('layouts.app', ['title' => 'Material']);
    }
}

<?php

namespace App\Livewire\Logistik;

use App\Models\Material;
use App\Models\StockIn;
use App\Models\Supplier;
use App\Models\Category;
use App\Exports\MaterialInventoryExport;
use App\Traits\WithFilterModal;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Materials extends Component
{
    use WithPagination, WithFilterModal, WithFileUploads;

    public $search = '';
    public $filterCategory = '';
    public $filterSupplier = '';
    public $filterStock = '';
    public $filterPhoto = '';
    public $sort = 'name_asc';

    public $showModal = false;
    public $editMode = false;
    public $materialId;

    // Form: Create/Edit Material
    public $name = '';
    public $code = '';
    public $supplier_name = '';
    public $category_id = '';
    public $unit = '';
    public $unit_price = 0;
    public $stock = 0;
    public $image = null;
    public $existingImage = null;

    // Restock Modal State
    public $showRestockModal = false;
    public $restockMaterialId = null;
    public $restockMaterialName = '';
    public $restockMaterialUnit = '';
    public $restockQuantity = 1;
    public $restockUnitPrice = 0;
    public $restockSupplierName = '';
    public $restockDate = '';
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

    public function toggleSortDirection(): void
    {
        if (str_ends_with($this->sort, '_asc')) {
            $this->sort = substr($this->sort, 0, -4) . '_desc';
        } elseif (str_ends_with($this->sort, '_desc')) {
            $this->sort = substr($this->sort, 0, -5) . '_asc';
        } else {
            $this->sort = 'name_desc';
        }
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->sort = 'name_asc';
        $this->filterCategory = '';
        $this->filterSupplier = '';
        $this->filterStock = '';
        $this->filterPhoto = '';
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
            'stock' => 'required|numeric|min:0',
            'image' => ($this->editMode && $this->existingImage)
                ? 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120'
                : 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ];
    }

    public function create()
    {
        $this->resetForm();
        $this->editMode = false;
        $this->showModal = true;
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
        $this->image = null;
        $this->existingImage = $material->image;
        $this->editMode = true;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

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
        ];

        if ($this->image) {
            $data['image'] = $this->image->store('materials', 'public');
        }

        if ($this->editMode) {
            $existing = Material::findOrFail($this->materialId);
            // Protect existing proof image from being overwritten once recorded
            if ($existing->image && isset($data['image'])) {
                unset($data['image']);
            }
            $existing->update($data);
            session()->flash('success', 'Material berhasil diperbarui.');
        } else {
            $material = Material::create($data);

            // Log initial stock as stock_in
            if ($this->stock > 0) {
                StockIn::create([
                    'material_id' => $material->id,
                    'supplier_id' => $final_supplier_id,
                    'user_id' => auth()->id(),
                    'quantity' => $this->stock,
                    'unit_price' => $this->unit_price,
                    'total_cost' => $this->stock * $this->unit_price,
                    'date' => now()->toDateString(),
                    'notes' => 'Stok awal',
                ]);
            }

            session()->flash('success', 'Material berhasil ditambahkan.');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function delete($id)
    {
        Material::findOrFail($id)->delete();
        session()->flash('success', 'Material berhasil dihapus.');
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
        $this->image = null;
        $this->existingImage = null;
        $this->resetValidation();
    }

    public function restock($id)
    {
        $material = Material::with('supplier')->findOrFail($id);
        $this->restockMaterialId = $material->id;
        $this->restockMaterialName = $material->name;
        $this->restockMaterialUnit = $material->unit;
        $this->restockQuantity = 1;
        $this->restockUnitPrice = $material->unit_price;
        $this->restockSupplierName = $material->supplier?->name ?? '';
        $this->restockDate = now()->format('Y-m-d');
        $this->restockNotes = '';
        $this->resetValidation();
        $this->showRestockModal = true;
    }

    public function saveRestock()
    {
        $this->validate([
            'restockMaterialId' => 'required|exists:materials,id',
            'restockQuantity' => 'required|numeric|min:0.01',
            'restockUnitPrice' => 'required|numeric|min:0',
            'restockSupplierName' => 'nullable|string|max:255',
            'restockDate' => 'required|date',
            'restockNotes' => 'nullable|string|max:500',
            'restockProofImage' => 'nullable|image|max:5120',
        ]);

        $proofImagePath = null;
        if ($this->restockProofImage) {
            $proofImagePath = $this->restockProofImage->store('stock-in-proofs', 'public');
        }

        try {
            DB::transaction(function () use ($proofImagePath) {
                $sourceMaterial = Material::findOrFail($this->restockMaterialId);

                // Resolve supplier
                $supplierId = null;
                if (!empty(trim($this->restockSupplierName))) {
                    $supplier = Supplier::firstOrCreate(['name' => trim($this->restockSupplierName)]);
                    $supplierId = $supplier->id;
                }

                $totalCost = $this->restockQuantity * $this->restockUnitPrice;

                // Check if a matching material row exists (same name, unit, category, supplier, price)
                $existingMaterial = Material::where('name', $sourceMaterial->name)
                    ->where('unit', $sourceMaterial->unit)
                    ->where('category_id', $sourceMaterial->category_id)
                    ->where('unit_price', $this->restockUnitPrice)
                    ->when($supplierId, fn ($q) => $q->where('supplier_id', $supplierId), fn ($q) => $q->whereNull('supplier_id'))
                    ->first();

                if ($existingMaterial) {
                    // Same price + supplier combo exists: just add stock
                    $existingMaterial->increment('stock', $this->restockQuantity);
                    $targetMaterialId = $existingMaterial->id;
                } else {
                    // Different price or supplier: create a new material row
                    $newMaterial = Material::create([
                        'name' => $sourceMaterial->name,
                        'unit' => $sourceMaterial->unit,
                        'category_id' => $sourceMaterial->category_id,
                        'supplier_id' => $supplierId,
                        'unit_price' => $this->restockUnitPrice,
                        'stock' => $this->restockQuantity,
                    ]);
                    $targetMaterialId = $newMaterial->id;
                }

                // Log the restock
                StockIn::create([
                    'material_id' => $targetMaterialId,
                    'supplier_id' => $supplierId,
                    'user_id' => auth()->id(),
                    'quantity' => $this->restockQuantity,
                    'unit_price' => $this->restockUnitPrice,
                    'total_cost' => $totalCost,
                    'date' => $this->restockDate,
                    'notes' => $this->restockNotes,
                    'proof_image' => $proofImagePath,
                ]);

                session()->flash('success', 'Restock berhasil dicatat: ' . $this->restockQuantity . ' ' . $sourceMaterial->unit . ' ' . $sourceMaterial->name . '.');
            });

            $this->showRestockModal = false;
            $this->resetRestockForm();
        } catch (\Exception $e) {
            $this->addError('restockQuantity', 'Gagal menyimpan restock: ' . $e->getMessage());
        }
    }

    public function resetRestockForm()
    {
        $this->restockMaterialId = null;
        $this->restockMaterialName = '';
        $this->restockMaterialUnit = '';
        $this->restockQuantity = 1;
        $this->restockUnitPrice = 0;
        $this->restockSupplierName = '';
        $this->restockDate = '';
        $this->restockNotes = '';
        $this->resetValidation();
    }

    public function openImportModal()
    {
        if (!in_array(auth()->user()->role, ['admin', 'logistik'])) return;
        $this->importFile = null;
        $this->importResultSummary = null;
        $this->resetValidation();
        $this->showImportModal = true;
    }

    public function importExcel()
    {
        if (!in_array(auth()->user()->role, ['admin', 'logistik'])) return;

        $this->validate([
            'importFile' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ], [
            'importFile.required' => 'Pilih berkas Excel (.xlsx / .xls) terlebih dahulu.',
            'importFile.mimes' => 'Berkas harus berupa format Excel (.xlsx, .xls) atau CSV.',
            'importFile.max' => 'Ukuran berkas maksimal 10MB.',
        ]);

        try {
            $import = new \App\Imports\MaterialImport();
            Excel::import($import, $this->importFile->getRealPath());

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
        } catch (\Exception $e) {
            $this->addError('importFile', 'Gagal memproses berkas Excel: ' . $e->getMessage());
        }
    }

    public function exportExcel()
    {
        if (!in_array(auth()->user()->role, ['admin', 'logistik'])) return;

        $export = new MaterialInventoryExport($this->search, $this->filterCategory);
        $filename = 'material-inventory-' . now()->format('Ymd') . '.xlsx';

        return response()->streamDownload(function () use ($export) {
            echo Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);
        }, $filename);
    }

    public function render()
    {
        $materials = Material::with(['supplier', 'category'])
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->when($this->filterCategory, fn ($q) => $q->where('category_id', $this->filterCategory))
            ->when($this->filterSupplier, fn ($q) => $q->where('supplier_id', $this->filterSupplier))
            ->when($this->filterPhoto === 'has_photo', fn ($q) => $q->whereNotNull('image')->where('image', '!=', ''))
            ->when($this->filterPhoto === 'no_photo', fn ($q) => $q->where(fn ($sub) => $sub->whereNull('image')->orWhere('image', '')))
            ->when($this->filterStock, function ($q) {
                if ($this->filterStock === 'low') {
                    $q->where('stock', '<=', 10)->where('stock', '>', 0);
                } elseif ($this->filterStock === 'safe') {
                    $q->where('stock', '>', 10);
                } elseif ($this->filterStock === 'empty') {
                    $q->where('stock', '<=', 0);
                }
            }, fn ($q) => $q->where('stock', '>', 0)) // Default: hide depleted
            ->when($this->sort, function ($q) {
                match ($this->sort) {
                    'name_asc' => $q->orderBy('name', 'asc'),
                    'name_desc' => $q->orderBy('name', 'desc'),
                    'stock_desc' => $q->orderBy('stock', 'desc'),
                    'stock_asc' => $q->orderBy('stock', 'asc'),
                    'unit_price_desc' => $q->orderBy('unit_price', 'desc'),
                    'unit_price_asc' => $q->orderBy('unit_price', 'asc'),
                    'date_desc' => $q->orderBy('created_at', 'desc'),
                    'date_asc' => $q->orderBy('created_at', 'asc'),
                    default => $q->orderBy('name', 'asc'),
                };
            }, fn($q) => $q->orderBy('name', 'asc'))
            ->paginate(10);

        $suppliers = Supplier::select('id', 'name')->orderBy('name')->get()->unique('name');
        $categories = Category::where('type', 'material')->orderBy('name')->get()->unique('name');

        // Summary stats
        $totalValue = Material::where('stock', '>', 0)
            ->selectRaw('SUM(unit_price * stock) as total')
            ->value('total') ?? 0;
        $totalItems = Material::where('stock', '>', 0)->count();

        return view('livewire.logistik.materials', compact('materials', 'suppliers', 'categories', 'totalValue', 'totalItems'))
            ->layout('layouts.app', ['title' => 'Material']);
    }
}

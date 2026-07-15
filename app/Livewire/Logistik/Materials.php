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
use Livewire\WithPagination;

class Materials extends Component
{
    use WithPagination, WithFilterModal;

    public $search = '';
    public $filterCategory = '';
    public $filterStock = '';
    public $sort = 'name_asc';

    public $showModal = false;
    public $editMode = false;
    public $materialId;

    // Form: Create/Edit Material
    public $name = '';
    public $supplier_name = '';
    public $category_id = '';
    public $unit = '';
    public $unit_price = 0;
    public $stock = 0;

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

    public function updatingSearch() { $this->resetPage(); }
    public function updatingFilterCategory() { $this->resetPage(); }
    public function updatingFilterStock() { $this->resetPage(); }
    public function updatingSort() { $this->resetPage(); }

    public function resetFilters()
    {
        $this->reset(['search', 'filterCategory', 'filterStock', 'sort']);
        $this->showFilterModal = false;
        $this->resetPage();
    }

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'supplier_name' => 'nullable|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'unit' => 'required|string|max:50',
            'unit_price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
        ];
    }

    public function create()
    {
        $this->resetForm();
        $this->editMode = false;
        $this->showModal = true;
    }

    public function edit($id)
    {
        $material = Material::findOrFail($id);
        $this->materialId = $material->id;
        $this->name = $material->name;
        $this->supplier_name = $material->supplier?->name ?? '';
        $this->category_id = $material->category_id ?? '';
        $this->unit = $material->unit;
        $this->unit_price = $material->unit_price;
        $this->stock = $material->stock;
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

        $data = [
            'name' => $this->name,
            'supplier_id' => $final_supplier_id,
            'category_id' => $this->category_id ?: null,
            'unit' => $this->unit,
            'unit_price' => $this->unit_price,
            'stock' => $this->stock,
        ];

        if ($this->editMode) {
            Material::findOrFail($this->materialId)->update($data);
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
        $this->supplier_name = '';
        $this->category_id = '';
        $this->unit = '';
        $this->unit_price = 0;
        $this->stock = 0;
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
            'restockQuantity' => 'required|integer|min:1',
            'restockUnitPrice' => 'required|numeric|min:0',
            'restockSupplierName' => 'nullable|string|max:255',
            'restockDate' => 'required|date',
            'restockNotes' => 'nullable|string|max:500',
        ]);

        try {
            DB::transaction(function () {
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
                $parts = explode('_', $this->sort);
                $column = $parts[0];
                $direction = $parts[1] ?? 'asc';

                $whitelist = ['name', 'stock', 'unit_price'];
                if (in_array($column, $whitelist) && in_array($direction, ['asc', 'desc'])) {
                    $q->orderBy($column, $direction);
                } else {
                    $q->orderBy('name', 'asc');
                }
            }, fn($q) => $q->orderBy('name', 'asc'))
            ->paginate(10);

        $suppliers = Supplier::orderBy('name')->get();
        $categories = Category::where('type', 'material')->orderBy('name')->get();

        // Summary stats
        $totalValue = Material::where('stock', '>', 0)
            ->selectRaw('SUM(unit_price * stock) as total')
            ->value('total') ?? 0;
        $totalItems = Material::where('stock', '>', 0)->count();

        return view('livewire.logistik.materials', compact('materials', 'suppliers', 'categories', 'totalValue', 'totalItems'))
            ->layout('layouts.app', ['title' => 'Material']);
    }
}

<?php

namespace App\Livewire\Logistik;

use App\Models\Tool;
use App\Models\Category;
use App\Exports\ToolInventoryExport;
use App\Traits\WithFilterModal;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Tools extends Component
{
    use WithPagination, WithFilterModal, WithFileUploads;

    public $search = '';
    public $activeTab = 'inventory'; // inventory | maintenance
    public $showModal = false;
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
    public $image = null;
    public $existingImage = null;
    
    // Filters
    public $sort = 'code_asc';
    public $filterCategory = '';
    public $filterCondition = '';
    public $filterStock = '';
    public $filterPhoto = '';

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

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingSort(): void { $this->resetPage(); }
    public function updatingFilterCategory(): void { $this->resetPage(); }
    public function updatingFilterCondition(): void { $this->resetPage(); }
    public function updatingFilterStock(): void { $this->resetPage(); }
    public function updatingFilterPhoto(): void { $this->resetPage(); }

    public function toggleSortDirection(): void
    {
        if (str_ends_with($this->sort, '_asc')) {
            $this->sort = substr($this->sort, 0, -4) . '_desc';
        } elseif (str_ends_with($this->sort, '_desc')) {
            $this->sort = substr($this->sort, 0, -5) . '_asc';
        } else {
            $this->sort = 'code_desc';
        }
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->sort = 'code_asc';
        $this->filterCategory = '';
        $this->filterCondition = '';
        $this->filterStock = '';
        $this->filterPhoto = '';
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

        // Logical prefix from name: AB- (Alat Berat), AT- (Alat Tangan), etc.
        $words = explode(' ', $category->name);
        $prefix = (count($words) > 1 && strtolower($words[0]) === 'alat')
            ? 'A' . strtoupper(substr($words[1], 0, 1))
            : strtoupper(substr($category->name, 0, 2));

        $prefix .= '-';

        $lastTool = Tool::where('code', 'LIKE', $prefix . '%')
            ->orderByRaw('LENGTH(code) DESC')
            ->orderBy('code', 'DESC')
            ->first();

        if (!$lastTool) {
            return $prefix . '001';
        }

        // Extract numeric part Safely
        $parts = explode('-', $lastTool->code);
        $lastNumber = isset($parts[1]) ? (int) $parts[1] : 0;
        $nextNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);

        return $prefix . $nextNumber;
    }

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'code' => 'required|string|max:50|unique:tools,code,' . ($this->toolId ?? 'NULL'),
            'condition' => 'required|in:baik,rusak,hilang',
            'purchase_price' => 'required|numeric|min:0',
            'total_qty' => 'required|integer|min:1',
            'available_qty' => 'required|integer|min:0',
            'qty_broken' => 'required|integer|min:0',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
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
        $this->existingImage = $tool->image;
        $this->image = null;
        $this->editMode = true;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        if (($this->available_qty + $this->qty_broken) > $this->total_qty) {
            $this->addError('available_qty', 'Jumlah unit tersedia (' . $this->available_qty . ') dan rusak (' . $this->qty_broken . ') tidak boleh melebihi Total Qty (' . $this->total_qty . ').');
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
        ];

        if ($this->image) {
            $data['image'] = $this->image->store('tools', 'public');
        }

        if ($this->editMode) {
            $existing = Tool::findOrFail($this->toolId);
            if ($existing->image && isset($data['image'])) {
                // If new image uploaded, replace
            }
            $existing->update($data);
            cache()->forget('dashboard_tools_on_loan');
            session()->flash('success', 'Data alat kerja dan kuantitas inventaris berhasil diperbarui.');
        } else {
            Tool::create($data);
            cache()->forget('dashboard_tools_on_loan');
            session()->flash('success', 'Alat kerja baru berhasil ditambahkan.');
        }

        $this->showModal = false;
        $this->resetForm();
        $this->resetValidation();
    }

    public function showToolImage($toolId)
    {
        $tool = Tool::find($toolId);
        if ($tool && $tool->image) {
            $this->viewingImageUrl = asset('storage/' . $tool->image);
            $this->viewingImageToolName = $tool->name;
            $this->showImageModal = true;
        }
    }

    public function delete($id)
    {
        Tool::findOrFail($id)->delete();
        session()->flash('success', 'Alat berhasil dihapus.');
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
        $this->image = null;
        $this->existingImage = null;
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
            $import = new \App\Imports\ToolImport();
            Excel::import($import, $this->importFile->getRealPath());

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
        } catch (\Exception $e) {
            $this->addError('importFile', 'Gagal memproses berkas Excel: ' . $e->getMessage());
        }
    }

    public function exportExcel()
    {
        if (!in_array(auth()->user()->role, ['admin', 'logistik'])) return;

        $export = new ToolInventoryExport(
            $this->search,
            $this->filterCategory,
            $this->filterCondition
        );
        $filename = 'tool-inventory-' . now()->format('Ymd') . '.xlsx';

        return response()->streamDownload(function () use ($export) {
            echo Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);
        }, $filename);
    }

    public function render()
    {
        $query = Tool::with(['category'])
            ->when($this->search, fn ($q) => $q->where(function ($sub) {
                $sub->where('name', 'like', "%{$this->search}%")
                    ->orWhere('code', 'like', "%{$this->search}%");
            }))
            ->when($this->filterCategory, fn ($q) => $q->where('category_id', $this->filterCategory))
            ->when($this->filterCondition, fn ($q) => $q->where('condition', $this->filterCondition))
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
                    'no_photo' => $q->where(fn($sub) => $sub->whereNull('image')->orWhere('image', '')),
                    default => null,
                };
            });

        match ($this->sort) {
            'date_desc' => $query->orderBy('created_at', 'desc'),
            'date_asc' => $query->orderBy('created_at', 'asc'),
            'name_asc' => $query->orderBy('name', 'asc'),
            'name_desc' => $query->orderBy('name', 'desc'),
            'qty_desc' => $query->orderBy('total_qty', 'desc'),
            'qty_asc' => $query->orderBy('total_qty', 'asc'),
            'price_desc' => $query->orderBy('purchase_price', 'desc'),
            'price_asc' => $query->orderBy('purchase_price', 'asc'),
            'code_desc' => $query->orderBy('code', 'desc'),
            default => $query->orderBy('code', 'asc'),
        };

        $tools = $query->paginate(10);

        $categories = Category::where('type', 'tool')->orderBy('name')->get()->unique('name');

        // Stats
        $totalAvailable = Tool::sum('available_qty');
        $totalTools = Tool::sum('total_qty');

        return view('livewire.logistik.tools', compact(
            'tools', 'categories',
            'totalAvailable', 'totalTools'
        ))->layout('layouts.app', ['title' => 'Alat']);
    }
}

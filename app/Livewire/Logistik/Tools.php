<?php

namespace App\Livewire\Logistik;

use App\Models\Tool;
use App\Models\Category;
use App\Exports\ToolInventoryExport;
use App\Traits\WithFilterModal;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Tools extends Component
{
    use WithPagination, WithFilterModal;

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
    
    // Filters
    public $filterCategory = '';
    public $filterCondition = '';

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

    public function updatingFilterCategory(): void { $this->resetPage(); }
    public function updatingFilterCondition(): void { $this->resetPage(); }

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

    public function resetFilters()
    {
        $this->reset(['search', 'filterCategory', 'filterCondition']);
        $this->showFilterModal = false;
        $this->resetPage();
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
        $this->editMode = true;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'category_id' => $this->category_id ?: null,
            'code' => $this->code,
            'condition' => $this->condition,
            'purchase_price' => $this->purchase_price,
            'total_qty' => $this->total_qty,
            'available_qty' => $this->available_qty,
        ];

        if ($this->editMode) {
            Tool::findOrFail($this->toolId)->update($data);
            session()->flash('success', 'Alat berhasil diperbarui.');
        } else {
            Tool::create($data);
            session()->flash('success', 'Alat berhasil ditambahkan.');
        }

        $this->showModal = false;
        $this->resetForm();
        $this->resetValidation();
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
        $this->resetValidation();
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
        $tools = Tool::with(['category'])
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('code', 'like', "%{$this->search}%"))
            ->when($this->filterCategory, fn ($q) => $q->where('category_id', $this->filterCategory))
            ->when($this->filterCondition, fn ($q) => $q->where('condition', $this->filterCondition))
            ->orderBy('code')
            ->paginate(10);

        $categories = Category::where('type', 'tool')->orderBy('name')->get();

        // Stats
        $totalAvailable = Tool::sum('available_qty');
        $totalTools = Tool::sum('total_qty');

        return view('livewire.logistik.tools', compact(
            'tools', 'categories',
            'totalAvailable', 'totalTools'
        ))->layout('layouts.app', ['title' => 'Alat']);
    }
}

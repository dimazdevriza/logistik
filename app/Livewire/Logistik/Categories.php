<?php

namespace App\Livewire\Logistik;

use App\Models\Category;
use App\Traits\WithFilterModal;
use Livewire\Component;
use Livewire\WithPagination;

class Categories extends Component
{
    use WithPagination, WithFilterModal;

    public $search = '';
    public $filterType = '';
    public $showModal = false;
    public $editMode = false;
    public $categoryId;

    public $name = '';
    public $type = 'material';



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
    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'type' => 'required|in:material,tool',
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
        $category = Category::findOrFail($id);
        $this->categoryId = $category->id;
        $this->name = $category->name;
        $this->type = $category->type;
        $this->editMode = true;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        $data = ['name' => $this->name, 'type' => $this->type];

        if ($this->editMode) {
            Category::findOrFail($this->categoryId)->update($data);
            session()->flash('success', 'Kategori berhasil diperbarui.');
        } else {
            Category::create($data);
            session()->flash('success', 'Kategori berhasil ditambahkan.');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function delete($id)
    {
        Category::findOrFail($id)->delete();
        session()->flash('success', 'Kategori berhasil dihapus.');
    }

    public function resetForm()
    {
        $this->categoryId = null;
        $this->name = '';
        $this->type = 'material';
        $this->resetValidation();
    }

    public function resetFilters()
    {
        $this->reset(['search', 'filterType']);
        $this->showFilterModal = false;
        $this->resetPage();
    }

    public function render()
    {
        $categories = Category::query()
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->when($this->filterType, fn ($q) => $q->where('type', $this->filterType))
            ->orderBy('type')
            ->orderBy('name')
            ->paginate(10);

        return view('livewire.logistik.categories', compact('categories'))
            ->layout('layouts.app', ['title' => 'Kategori']);
    }
}

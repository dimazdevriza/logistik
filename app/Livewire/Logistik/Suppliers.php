<?php

namespace App\Livewire\Logistik;

use App\Models\Supplier;
use App\Traits\WithFilterModal;
use Livewire\Component;
use Livewire\WithPagination;

class Suppliers extends Component
{
    use WithPagination, WithFilterModal;

    public $search = '';
    public $showModal = false;
    public $editMode = false;
    public $supplierId;

    public $name = '';
    public $contact_person = '';
    public $phone = '';
    public $address = '';

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
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
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
        $supplier = Supplier::findOrFail($id);
        $this->supplierId = $supplier->id;
        $this->name = $supplier->name;
        $this->contact_person = $supplier->contact_person ?? '';
        $this->phone = $supplier->phone ?? '';
        $this->address = $supplier->address ?? '';
        $this->editMode = true;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'contact_person' => $this->contact_person,
            'phone' => $this->phone,
            'address' => $this->address,
        ];

        if ($this->editMode) {
            Supplier::findOrFail($this->supplierId)->update($data);
            session()->flash('success', 'Supplier berhasil diperbarui.');
        } else {
            Supplier::create($data);
            session()->flash('success', 'Supplier berhasil ditambahkan.');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function delete($id)
    {
        Supplier::findOrFail($id)->delete();
        session()->flash('success', 'Supplier berhasil dihapus.');
    }

    public function resetForm()
    {
        $this->supplierId = null;
        $this->name = '';
        $this->contact_person = '';
        $this->phone = '';
        $this->address = '';
        $this->resetValidation();
    }

    public function resetFilters()
    {
        $this->reset(['search']);
        $this->showFilterModal = false;
        $this->resetPage();
    }

    public function render()
    {
        $suppliers = Supplier::query()
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate(10);

        return view('livewire.logistik.suppliers', compact('suppliers'))
            ->layout('layouts.app', ['title' => 'Supplier']);
    }
}

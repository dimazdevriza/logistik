<?php

namespace App\Livewire\Logistik;

use App\Models\Warehouse;
use App\Models\ToolWarehouseBalance;
use App\Traits\WithTableSorting;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Warehouses extends Component
{
    use WithPagination, WithTableSorting;

    public string $search = '';
    public string $sort = 'name_asc';
    public bool $showModal = false;
    public bool $showConfirmation = false;
    public bool $editMode = false;
    public ?int $warehouseId = null;
    public string $name = '';
    public string $address = '';
    public ?int $confirmingId = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('warehouses', 'name')->ignore($this->warehouseId)],
            'address' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function create(): void
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $warehouse = Warehouse::findOrFail($id);
        $this->warehouseId = $warehouse->id;
        $this->name = $warehouse->name;
        $this->address = $warehouse->address ?? '';
        $this->editMode = true;
        $this->showModal = true;
    }

    public function save(): void
    {
        if (! $this->editMode) {
            abort_unless(auth()->user()?->role === 'admin', 403, 'Hanya Admin yang dapat menambahkan gudang.');
        }

        $data = $this->validate();
        if ($this->editMode) {
            Warehouse::findOrFail($this->warehouseId)->update($data);
            session()->flash('success', 'Gudang berhasil diperbarui.');
        } else {
            Warehouse::create($data);
            session()->flash('success', 'Gudang berhasil ditambahkan.');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $this->resetValidation('delete');
        $warehouse = Warehouse::findOrFail($id);
        if (Warehouse::count() === 1) {
            $this->addError('delete', 'Minimal satu gudang harus tetap tersedia.');
            return;
        }
        if ($warehouse->hasInventory()) {
            $this->addError('delete', 'Gudang tidak dapat dihapus selama masih menyimpan material atau alat.');
            return;
        }

        $this->confirmingId = $warehouse->id;
        $this->showConfirmation = true;
    }

    public function delete(): void
    {
        $warehouse = Warehouse::findOrFail($this->confirmingId);
        if (Warehouse::count() === 1 || $warehouse->hasInventory()) {
            $this->addError('delete', 'Gudang tidak dapat dihapus selama masih menyimpan inventaris atau menjadi satu-satunya gudang.');
            $this->showConfirmation = false;
            return;
        }

        $warehouse->delete();
        $this->showConfirmation = false;
        $this->confirmingId = null;
        session()->flash('success', 'Gudang berhasil dihapus.');
    }

    public function resetForm(): void
    {
        $this->reset(['warehouseId', 'name', 'address', 'editMode']);
        $this->resetValidation();
    }

    protected function sortableColumns(): array
    {
        return [
            'name' => 'warehouses.name',
            'address' => 'warehouses.address',
            'materials' => 'materials_count',
            'tools' => 'tools_sum_total_qty',
        ];
    }

    public function render()
    {
        $warehouses = Warehouse::query()
            ->withCount(['materials', 'toolBalances as tools_count'])
            ->addSelect([
                'tools_sum_total_qty' => ToolWarehouseBalance::query()
                    ->selectRaw('COALESCE(SUM(available_qty + qty_broken), 0)')
                    ->whereColumn('warehouse_id', 'warehouses.id'),
            ])
            ->when($this->search, fn ($query) => $query->where(function ($sub) {
                $sub->where('name', 'like', "%{$this->search}%")
                    ->orWhere('address', 'like', "%{$this->search}%");
            }))
            ->tap(fn ($query) => $this->applyTableSort($query))
            ->orderBy('warehouses.id')
            ->paginate(10);

        return view('livewire.logistik.warehouses', compact('warehouses'))
            ->layout('layouts.app', ['title' => 'Gudang']);
    }
}

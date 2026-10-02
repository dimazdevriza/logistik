<?php

namespace App\Livewire\Logistik;

use App\Models\Cluster;
use App\Traits\WithTableSorting;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Clusters extends Component
{
    use WithPagination, WithTableSorting;

    public string $search = '';
    public string $sort = 'name_asc';
    public bool $isCostPage = false;
    public bool $showModal = false;
    public bool $showConfirmation = false;
    public bool $editMode = false;
    public ?int $clusterId = null;
    public string $name = '';
    public string $description = '';
    public ?int $confirmingId = null;

    public function mount(): void
    {
        abort_unless(in_array(auth()->user()?->role, ['admin', 'keuangan'], true), 403);
        $this->isCostPage = request()->routeIs('logistik.cluster-costs') || auth()->user()->role === 'keuangan';
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('clusters', 'name')->ignore($this->clusterId)],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function create(): void
    {
        $this->authorizeManagement();
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $this->authorizeManagement();
        $cluster = Cluster::findOrFail($id);
        $this->clusterId = $cluster->id;
        $this->name = $cluster->name;
        $this->description = $cluster->description ?? '';
        $this->editMode = true;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->authorizeManagement();
        $data = $this->validate();

        if ($this->editMode) {
            Cluster::findOrFail($this->clusterId)->update($data);
            session()->flash('success', 'Cluster berhasil diperbarui.');
        } else {
            Cluster::create($data);
            session()->flash('success', 'Cluster berhasil ditambahkan.');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $this->authorizeManagement();
        $this->resetValidation('delete');
        $cluster = Cluster::findOrFail($id);
        if ($cluster->houses()->exists()) {
            $this->addError('delete', 'Cluster tidak dapat dihapus selama masih memiliki rumah. Pindahkan rumah terlebih dahulu.');
            return;
        }

        $this->confirmingId = $cluster->id;
        $this->showConfirmation = true;
    }

    public function delete(): void
    {
        $this->authorizeManagement();
        $cluster = Cluster::findOrFail($this->confirmingId);
        if ($cluster->houses()->exists()) {
            $this->addError('delete', 'Cluster tidak dapat dihapus selama masih memiliki rumah. Pindahkan rumah terlebih dahulu.');
            $this->showConfirmation = false;
            return;
        }

        $cluster->delete();
        $this->showConfirmation = false;
        $this->confirmingId = null;
        session()->flash('success', 'Cluster berhasil dihapus.');
    }

    public function resetForm(): void
    {
        $this->reset(['clusterId', 'name', 'description', 'editMode']);
        $this->resetValidation();
    }

    protected function sortableColumns(): array
    {
        return [
            'name' => 'clusters.name',
            'houses' => 'houses_count',
            'description' => 'clusters.description',
        ];
    }

    private function authorizeManagement(): void
    {
        abort_unless(auth()->user()?->role === 'admin', 403);
    }

    public function render()
    {
        $clusters = Cluster::query()
            ->withCount('houses')
            ->with('houses:id,cluster_id,name')
            ->when($this->search, fn ($query) => $query->where('name', 'like', "%{$this->search}%"))
            ->tap(fn ($query) => $this->applyTableSort($query))
            ->orderBy('clusters.id')
            ->paginate(10);

        return view('livewire.logistik.clusters', ['clusters' => $clusters, 'isCostPage' => $this->isCostPage])
            ->layout('layouts.app', ['title' => $this->isCostPage ? 'Biaya Cluster' : 'Cluster']);
    }
}

<?php

namespace App\Livewire\Admin;

use App\Models\Cluster;
use App\Models\User;
use App\Models\Warehouse;
use App\Traits\WithTableSorting;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class UserManagement extends Component
{
    use WithPagination, WithTableSorting;

    public $search = '';

    public $sort = 'name_asc';

    public $showModal = false;

    public $editMode = false;

    public $userId;

    public $name = '';

    public $email = '';

    public $password = '';

    public $role = 'logistik';

    public $cluster_id = '';

    public $warehouse_id = '';

    /** @var array<int|string> */
    public array $cluster_ids = [];

    /** @var array<int|string> */
    public array $warehouse_ids = [];

    public bool $reactivatingLegacyUser = false;

    public bool $showConfirmation = false;
    public string $confirmingAction = '';
    public ?int $confirmingId = null;
    public string $confirmTitle = '';
    public string $confirmMessage = '';

    public function confirm(string $action, ?int $id = null, string $title = '', string $message = ''): void
    {
        $this->confirmingAction = $action;
        $this->confirmingId = $id;
        $this->confirmTitle = $title;
        $this->confirmMessage = $message;
        $this->showConfirmation = true;
    }

    public function executeConfirmedAction(): void
    {
        match ($this->confirmingAction) {
            'delete' => $this->delete((int) $this->confirmingId),
            default => null,
        };

        $this->showConfirmation = false;
        $this->confirmingAction = '';
        $this->confirmingId = null;
    }

    protected function rules()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.($this->userId ?? 'NULL'),
            'role' => 'required|in:admin,logistik,keuangan,pengawas',
            'cluster_id' => [Rule::requiredIf(in_array($this->role, ['logistik', 'pengawas'], true)), 'nullable', 'exists:clusters,id'],
            'warehouse_id' => [Rule::requiredIf(in_array($this->role, ['logistik', 'pengawas'], true)), 'nullable', 'exists:warehouses,id'],
        ];

        if (! $this->editMode) {
            $rules['password'] = 'required|string|min:8';
        } else {
            $rules['password'] = 'nullable|string|min:8';
        }

        return $rules;
    }

    public function create()
    {
        $this->resetForm();
        $this->editMode = false;
        $this->showModal = true;
    }

    public function edit($id)
    {
        $user = User::with(['clusters:id,name', 'warehouses:id,name'])->findOrFail($id);
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role === 'inactive' ? 'logistik' : $user->role;
        $this->reactivatingLegacyUser = $user->role === 'inactive';
        $this->cluster_ids = array_map('strval', $user->assignedClusterIds());
        $this->warehouse_ids = array_map('strval', $user->assignedWarehouseIds());
        $this->cluster_id = (string) ($user->cluster_id ?? ($this->cluster_ids[0] ?? ''));
        $this->warehouse_id = (string) ($user->warehouse_id ?? ($this->warehouse_ids[0] ?? ''));
        $this->password = '';
        $this->editMode = true;
        $this->showModal = true;
    }

    public function save()
    {
        $this->email = strtolower(trim($this->email));
        $isFieldRole = in_array($this->role, ['logistik', 'pengawas'], true);

        if ($isFieldRole) {
            if ($this->cluster_id !== '' && empty($this->cluster_ids)) {
                $this->cluster_ids = [(string) $this->cluster_id];
            } elseif (! empty($this->cluster_ids) && $this->cluster_id === '') {
                $this->cluster_id = (string) $this->cluster_ids[0];
            }

            if ($this->warehouse_id !== '' && empty($this->warehouse_ids)) {
                $this->warehouse_ids = [(string) $this->warehouse_id];
            } elseif (! empty($this->warehouse_ids) && $this->warehouse_id === '') {
                $this->warehouse_id = (string) $this->warehouse_ids[0];
            }
        }

        $this->validate();

        $selectedClusters = array_values(array_filter(array_unique(array_map('intval', array_merge($this->cluster_ids, $this->cluster_id ? [(int) $this->cluster_id] : [])))));
        $selectedWarehouses = array_values(array_filter(array_unique(array_map('intval', array_merge($this->warehouse_ids, $this->warehouse_id ? [(int) $this->warehouse_id] : [])))));

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'cluster_id' => $isFieldRole ? ($selectedClusters[0] ?? null) : null,
            'warehouse_id' => $isFieldRole ? ($selectedWarehouses[0] ?? null) : null,
        ];

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->editMode) {
            $user = User::findOrFail($this->userId);

            if (strcasecmp($user->email, $this->email) !== 0) {
                $user->forceFill([
                    'google_id' => null,
                    'google_linked_at' => null,
                ])->save();
            }

            $user->update($data);

            if ($isFieldRole) {
                $user->clusters()->sync($selectedClusters);
                $user->warehouses()->sync($selectedWarehouses);
            } else {
                $user->clusters()->detach();
                $user->warehouses()->detach();
            }

            session()->flash('success', 'User berhasil diperbarui.');
        } else {
            $data['password'] = Hash::make($this->password);
            $user = User::create($data);

            if ($isFieldRole) {
                $user->clusters()->sync($selectedClusters);
                $user->warehouses()->sync($selectedWarehouses);
            }

            session()->flash('success', 'User berhasil ditambahkan.');
        }

        Role::findOrCreate($this->role, 'web');
        $user->syncRoles($this->role);

        $this->showModal = false;
        $this->resetForm();
    }

    public function delete($id)
    {
        if ($id === auth()->id()) {
            session()->flash('error', 'Anda tidak dapat menghapus akun sendiri.');

            return;
        }
        $user = User::findOrFail($id);
        if ($user->hasOperationalHistory()) {
            session()->flash('error', 'User tidak dapat dihapus karena memiliki riwayat operasional. Riwayat transaksi harus tetap tersimpan.');

            return;
        }
        $user->delete();
        session()->flash('success', 'User berhasil dihapus.');
    }

    public function resetForm()
    {
        $this->userId = null;
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->role = 'logistik';
        $this->cluster_id = '';
        $this->warehouse_id = '';
        $this->cluster_ids = [];
        $this->warehouse_ids = [];
        $this->reactivatingLegacyUser = false;
        $this->resetValidation();
    }

    public function resetFilters()
    {
        $this->reset(['search']);
        $this->resetPage();
    }

    protected function sortableColumns(): array
    {
        return [
            'name' => 'users.name',
            'email' => 'users.email',
            'google' => 'users.google_id',
            'role' => 'users.role',
            'cluster' => fn ($query, $direction) => $query->orderBy(
                Cluster::select('name')->whereColumn('clusters.id', 'users.cluster_id'),
                $direction
            ),
            'warehouse' => fn ($query, $direction) => $query->orderBy(
                Warehouse::select('name')->whereColumn('warehouses.id', 'users.warehouse_id'),
                $direction
            ),
            'created_at' => 'users.created_at',
        ];
    }

    public function render()
    {
        $users = User::with(['cluster:id,name', 'warehouse:id,name', 'clusters:id,name', 'warehouses:id,name'])
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%"))
            ->tap(fn ($query) => $this->applyTableSort($query))
            ->orderBy('users.id')
            ->paginate(10);

        $clusters = Cluster::query()->orderBy('name')->get(['id', 'name']);
        $warehouses = Warehouse::query()->orderBy('name')->get(['id', 'name']);

        return view('livewire.admin.user-management', compact('users', 'clusters', 'warehouses'))
            ->layout('layouts.app', ['title' => 'Manajemen User']);
    }
}

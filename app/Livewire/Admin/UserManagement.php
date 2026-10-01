<?php

namespace App\Livewire\Admin;

use App\Models\Cluster;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Traits\WithTableSorting;
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
    public bool $reactivatingLegacyUser = false;

    protected function rules()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . ($this->userId ?? 'NULL'),
            'role' => 'required|in:admin,logistik,keuangan',
            'cluster_id' => [Rule::requiredIf($this->role === 'logistik'), 'nullable', 'exists:clusters,id'],
        ];

        if (!$this->editMode) {
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
        $user = User::findOrFail($id);
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role === 'inactive' ? 'logistik' : $user->role;
        $this->reactivatingLegacyUser = $user->role === 'inactive';
        $this->cluster_id = $user->cluster_id ?? '';
        $this->password = '';
        $this->editMode = true;
        $this->showModal = true;
    }

    public function save()
    {
        $this->email = strtolower(trim($this->email));
        $this->validate();

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'cluster_id' => $this->role === 'logistik' ? ($this->cluster_id ?: null) : null,
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
            session()->flash('success', 'User berhasil diperbarui.');
        } else {
            $data['password'] = Hash::make($this->password);
            $user = User::create($data);
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
            'created_at' => 'users.created_at',
        ];
    }

    public function render()
    {
        $users = User::with('cluster:id,name')
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%"))
            ->tap(fn ($query) => $this->applyTableSort($query))
            ->orderBy('users.id')
            ->paginate(10);

        $clusters = Cluster::query()->orderBy('name')->get(['id', 'name']);

        return view('livewire.admin.user-management', compact('users', 'clusters'))
            ->layout('layouts.app', ['title' => 'Manajemen User']);
    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = collect([
            'view dashboard',
            'view inventory',
            'manage inventory',
            'import inventory',
            'view logs',
            'manage houses',
            'manage users',
            'request materials',
            'request tools',
        ])->mapWithKeys(fn (string $name) => [
            $name => Permission::findOrCreate($name, 'web'),
        ]);

        $rolePermissions = [
            'admin' => $permissions->values(),
            'logistik' => $permissions->only([
                'view dashboard',
                'view inventory',
                'manage inventory',
                'import inventory',
                'view logs',
                'manage houses',
                'request materials',
                'request tools',
            ])->values(),
            'keuangan' => $permissions->only([
                'view dashboard',
                'view inventory',
                'manage inventory',
                'import inventory',
                'view logs',
            ])->values(),
            'inactive' => collect(),
            'user' => collect(),
        ];

        foreach ($rolePermissions as $name => $rolePermissionsForUser) {
            Role::findOrCreate($name, 'web')->syncPermissions($rolePermissionsForUser);
        }

        User::query()
            ->whereNotNull('role')
            ->each(fn (User $user) => $user->syncRoles($user->role));
    }
}

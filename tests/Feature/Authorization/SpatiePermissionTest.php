<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('legacy user roles are synchronized with Spatie', function () {
    $user = User::factory()->create(['role' => 'admin']);

    expect($user->fresh()->hasRole('admin'))->toBeTrue();
});

test('Spatie permissions are available through Laravel authorization', function () {
    $role = Role::findOrCreate('admin', 'web');
    $permission = Permission::findOrCreate('manage users', 'web');
    $role->givePermissionTo($permission);

    $user = User::factory()->create(['role' => 'admin']);

    expect($user->can('manage users'))->toBeTrue();
});

test('the custom login screen remains in place with Jetstream installed', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('Masuk ke Portal')
        ->assertDontSee('Authentication Card');
});

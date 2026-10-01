<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $inactive = Role::findOrCreate('inactive', 'web');

        User::where('role', 'mandor')->each(function (User $user) use ($inactive): void {
            $user->forceFill(['role' => 'inactive'])->save();
            $user->syncRoles($inactive);
        });

        Role::where('name', 'mandor')->where('guard_name', 'web')->delete();
    }

    public function down(): void
    {
        $mandor = Role::findOrCreate('mandor', 'web');

        User::where('role', 'inactive')->each(function (User $user) use ($mandor): void {
            $user->forceFill(['role' => 'mandor'])->save();
            $user->syncRoles($mandor);
        });

        Role::where('name', 'inactive')->where('guard_name', 'web')->delete();
    }
};

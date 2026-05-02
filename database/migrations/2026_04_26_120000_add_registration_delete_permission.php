<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $guard = config('auth.defaults.guard');
        $permission = Permission::firstOrCreate(
            ['name' => 'registration.delete', 'guard_name' => $guard]
        );

        $admin = Role::query()->where('name', 'admin')->where('guard_name', $guard)->first();
        if ($admin && ! $admin->hasPermissionTo($permission)) {
            $admin->givePermissionTo($permission);
        }
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $guard = config('auth.defaults.guard');
        $permission = Permission::query()
            ->where('name', 'registration.delete')
            ->where('guard_name', $guard)
            ->first();
        if (! $permission) {
            return;
        }

        $admin = Role::query()->where('name', 'admin')->where('guard_name', $guard)->first();
        if ($admin) {
            $admin->revokePermissionTo($permission);
        }
    }
};

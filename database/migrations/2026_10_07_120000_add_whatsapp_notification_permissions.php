<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /** @var list<string> */
    private const PERMISSIONS = [
        'whatsapp_notification.read',
        'whatsapp_notification.send',
    ];

    /** @var list<string> */
    private const ROLES = ['super_admin', 'admin', 'organizer'];

    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $guard = config('auth.defaults.guard');

        foreach (self::PERMISSIONS as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => $guard]);
        }

        foreach (self::ROLES as $roleName) {
            $role = Role::query()->where('name', $roleName)->where('guard_name', $guard)->first();
            $role?->givePermissionTo(self::PERMISSIONS);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $guard = config('auth.defaults.guard');

        Permission::query()
            ->whereIn('name', self::PERMISSIONS)
            ->where('guard_name', $guard)
            ->get()
            ->each->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};

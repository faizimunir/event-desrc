<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $guard = 'web';
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => $guard]);
    Permission::firstOrCreate(['name' => 'read.rider', 'guard_name' => $guard]);
});

test('super_admin can access roles index', function () {
    $user = User::factory()->create();
    $user->assignRole('super_admin');
    $user->setActiveRole('super_admin');

    $response = $this->actingAs($user)->get(route('roles.index'));

    $response->assertOk();
    $response->assertSee(__('Roles'), false);
});

test('non super_admin cannot access roles index', function () {
    $role = Role::firstOrCreate(['name' => 'member', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($role);
    $user->setActiveRole('member');

    $response = $this->actingAs($user)->get(route('roles.index'));

    $response->assertForbidden();
});

test('super_admin can create role with permissions', function () {
    $user = User::factory()->create();
    $user->assignRole('super_admin');
    $user->setActiveRole('super_admin');

    $response = $this->actingAs($user)->post(route('roles.store'), [
        'name' => 'editor',
        'permissions' => ['read.rider'],
        '_token' => csrf_token(),
    ]);

    $response->assertRedirect(route('roles.index'));
    $response->assertSessionHas('status');

    $role = Role::where('name', 'editor')->where('guard_name', 'web')->first();
    expect($role)->not->toBeNull();
    expect($role->hasPermissionTo('read.rider'))->toBeTrue();
});

test('super_admin can update role and sync permissions', function () {
    $role = Role::create(['name' => 'custom_role', 'guard_name' => 'web']);
    $role->givePermissionTo('read.rider');
    $user = User::factory()->create();
    $user->assignRole('super_admin');
    $user->setActiveRole('super_admin');

    $response = $this->actingAs($user)->put(route('roles.update', $role), [
        'name' => 'custom_role',
        'permissions' => [],
        '_token' => csrf_token(),
        '_method' => 'PUT',
    ]);

    $response->assertRedirect(route('roles.index'));
    $role->refresh();
    expect($role->permissions)->toHaveCount(0);
});

test('super_admin cannot delete super_admin role', function () {
    $role = Role::findByName('super_admin', 'web');
    $user = User::factory()->create();
    $user->assignRole('super_admin');
    $user->setActiveRole('super_admin');

    $response = $this->actingAs($user)->delete(route('roles.destroy', $role), [
        '_token' => csrf_token(),
        '_method' => 'DELETE',
    ]);

    $response->assertRedirect(route('roles.index'));
    $response->assertSessionHas('error');
    $this->assertDatabaseHas('roles', ['name' => 'super_admin']);
});

test('super_admin can delete non super_admin role', function () {
    $role = Role::create(['name' => 'deletable_role', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole('super_admin');
    $user->setActiveRole('super_admin');

    $response = $this->actingAs($user)->delete(route('roles.destroy', $role), [
        '_token' => csrf_token(),
        '_method' => 'DELETE',
    ]);

    $response->assertRedirect(route('roles.index'));
    $response->assertSessionHas('status');
    $this->assertDatabaseMissing('roles', ['name' => 'deletable_role']);
});

test('super_admin can access permissions index', function () {
    $user = User::factory()->create();
    $user->assignRole('super_admin');
    $user->setActiveRole('super_admin');

    $response = $this->actingAs($user)->get(route('permissions.index'));

    $response->assertOk();
    $response->assertSee(__('Permissions'), false);
});

test('super_admin can create permission', function () {
    $user = User::factory()->create();
    $user->assignRole('super_admin');
    $user->setActiveRole('super_admin');

    $response = $this->actingAs($user)->post(route('permissions.store'), [
        'name' => 'create.event',
        '_token' => csrf_token(),
    ]);

    $response->assertRedirect(route('permissions.index'));
    $response->assertSessionHas('status');
    $this->assertDatabaseHas('permissions', ['name' => 'create.event', 'guard_name' => 'web']);
});

test('super_admin can update permission', function () {
    $permission = Permission::create(['name' => 'old.name', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole('super_admin');
    $user->setActiveRole('super_admin');

    $response = $this->actingAs($user)->put(route('permissions.update', $permission), [
        'name' => 'new.name',
        '_token' => csrf_token(),
        '_method' => 'PUT',
    ]);

    $response->assertRedirect(route('permissions.index'));
    $permission->refresh();
    expect($permission->name)->toBe('new.name');
});

test('super_admin can delete permission', function () {
    $permission = Permission::create(['name' => 'delete.me', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole('super_admin');
    $user->setActiveRole('super_admin');

    $response = $this->actingAs($user)->delete(route('permissions.destroy', $permission), [
        '_token' => csrf_token(),
        '_method' => 'DELETE',
    ]);

    $response->assertRedirect(route('permissions.index'));
    $response->assertSessionHas('status');
    $this->assertDatabaseMissing('permissions', ['name' => 'delete.me']);
});

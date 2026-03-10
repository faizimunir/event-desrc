<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    Role::create(['name' => 'coach', 'guard_name' => 'web']);
    Role::create(['name' => 'member', 'guard_name' => 'web']);
    Permission::create(['name' => 'update.rider', 'guard_name' => 'web']);
    Permission::create(['name' => 'read.rider', 'guard_name' => 'web']);
    Permission::create(['name' => 'create.event', 'guard_name' => 'web']);
    $admin = Role::findByName('admin');
    $member = Role::findByName('member');
    $admin->givePermissionTo(['update.rider', 'read.rider', 'create.event']);
    $member->givePermissionTo('read.rider');
});

test('user with multiple roles can switch active role', function () {
    $user = User::factory()->create();
    $user->assignRole(['admin', 'coach']);
    $this->actingAs($user);

    $user->resolveDefaultActiveRole();
    expect(session('active_role'))->toBeIn(['admin', 'coach']);

    $response = $this->post(route('switch-role'), [
        'role' => 'coach',
        '_token' => csrf_token(),
    ]);

    $response->assertRedirect();
    expect(session('active_role'))->toBe('coach');
});

test('user cannot switch to role they do not have', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');
    $this->actingAs($user);

    $response = $this->post(route('switch-role'), [
        'role' => 'coach',
        '_token' => csrf_token(),
    ]);

    $response->assertSessionHasErrors('role');
});

test('switch role requires authentication', function () {
    $response = $this->post(route('switch-role'), [
        'role' => 'admin',
        '_token' => csrf_token(),
    ]);

    $response->assertRedirect(route('login'));
});

test('canAs checks permission based on active role only', function () {
    $user = User::factory()->create();
    $user->assignRole(['admin', 'member']);
    $this->actingAs($user);

    $user->setActiveRole('admin');
    expect($user->canAs('update.rider'))->toBeTrue();
    expect($user->canAs('read.rider'))->toBeTrue();

    $user->setActiveRole('member');
    expect($user->canAs('update.rider'))->toBeFalse();
    expect($user->canAs('read.rider'))->toBeTrue();
});

test('super_admin bypasses canAs', function () {
    Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole('super_admin');
    $this->actingAs($user);

    $user->setActiveRole('super_admin');
    expect($user->canAs('update.rider'))->toBeTrue();
});

test('Gate and Blade use canAs for permission strings in HTTP context', function () {
    $user = User::factory()->create();
    $user->assignRole(['admin', 'member']);

    $response = $this->actingAs($user)
        ->withSession(['active_role' => 'member'])
        ->get(route('dashboard'));

    $response->assertOk();
    $response->assertDontSee(__('Can edit rider'), false);
    $response->assertSee(__('Cannot edit rider'), false);
    $response->assertSee(__('Cannot create event'), false);

    $response = $this->actingAs($user)
        ->withSession(['active_role' => 'admin'])
        ->get(route('dashboard'));

    $response->assertOk();
    $response->assertSee(__('Can edit rider'), false);
    $response->assertSee(__('Can create event'), false);
});

<?php

use App\Models\Rider;
use App\Models\User;
use App\Services\UserMergeService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $guard = 'web';
    foreach (['user.read', 'user.create', 'user.update', 'user.delete'] as $name) {
        Permission::firstOrCreate(['name' => $name, 'guard_name' => $guard]);
    }
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => $guard]);
    Role::firstOrCreate(['name' => 'member', 'guard_name' => $guard]);
    $superAdmin = Role::findByName('super_admin', $guard);
    $superAdmin->syncPermissions(Permission::where('guard_name', $guard)->get());
});

test('super_admin can access users index', function () {
    $user = User::factory()->create();
    $user->assignRole('super_admin');
    $user->setActiveRole('super_admin');

    $response = $this->actingAs($user)->get(route('users.index'));

    $response->assertOk();
    $response->assertSee(__('Users Management'), false);
});

test('user without user.read cannot access users index', function () {
    $member = Role::firstOrCreate(['name' => 'member', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($member);
    $user->setActiveRole('member');

    $response = $this->actingAs($user)->get(route('users.index'));

    $response->assertForbidden();
});

test('super_admin can create user with roles', function () {
    $user = User::factory()->create();
    $user->assignRole('super_admin');
    $user->setActiveRole('super_admin');

    $response = $this->actingAs($user)->post(route('users.store'), [
        'name' => 'New User',
        'email' => 'new@example.com',
        'whatsapp' => '08123456789',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'roles' => ['member'],
        '_token' => csrf_token(),
    ]);

    $response->assertRedirect(route('users.index'));
    $response->assertSessionHas('status');

    $this->assertDatabaseHas('users', ['email' => 'new@example.com']);
    $created = User::where('email', 'new@example.com')->first();
    expect($created->hasRole('member'))->toBeTrue();
});

test('super_admin cannot delete own account', function () {
    $user = User::factory()->create();
    $user->assignRole('super_admin');
    $user->setActiveRole('super_admin');

    $response = $this->actingAs($user)->delete(route('users.destroy', $user), [
        '_token' => csrf_token(),
        '_method' => 'DELETE',
    ]);

    $response->assertRedirect(route('users.index'));
    $response->assertSessionHas('error');
    $this->assertDatabaseHas('users', ['id' => $user->id]);
});

test('user merge reassigns riders and deletes secondary account', function () {
    $primary = User::factory()->create(['email' => 'primary-merge@example.com']);
    $secondary = User::factory()->create(['email' => 'secondary-merge@example.com']);
    $primary->assignRole('member');
    $secondary->assignRole('member');

    $rider = Rider::query()->create([
        'user_id' => $secondary->id,
        'name' => 'Merged Rider',
    ]);

    app(UserMergeService::class)->mergeIntoPrimary([(int) $primary->id, (int) $secondary->id], (int) $primary->id);

    expect(User::query()->find($secondary->id))->toBeNull();
    expect($rider->fresh()->user_id)->toBe($primary->id);
    expect($primary->fresh()->hasRole('member'))->toBeTrue();
});

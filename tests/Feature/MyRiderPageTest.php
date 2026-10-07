<?php

use App\Livewire\Riders\RiderForm;
use App\Models\Rider;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'myrider.manage', 'guard_name' => 'web']);
});

test('user with myrider.manage on active role can view my rider page', function () {
    $role = Role::firstOrCreate(['name' => 'member', 'guard_name' => 'web']);
    $role->givePermissionTo('myrider.manage');
    $user = User::factory()->create();
    $user->assignRole($role);
    $user->setActiveRole('member');

    $response = $this->actingAs($user)->get(route('my-rider.index'));

    $response->assertOk();
    $response->assertSee(__('My Rider'), false);
});

test('user without myrider.manage cannot view my rider page', function () {
    $role = Role::firstOrCreate(['name' => 'committee', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($role);
    $user->setActiveRole('committee');

    $response = $this->actingAs($user)->get(route('my-rider.index'));

    $response->assertForbidden();
});

test('user with myrider.manage can view add rider page', function () {
    $role = Role::firstOrCreate(['name' => 'member', 'guard_name' => 'web']);
    $role->givePermissionTo('myrider.manage');
    $user = User::factory()->create();
    $user->assignRole($role);
    $user->setActiveRole('member');

    $response = $this->actingAs($user)->get(route('my-rider.create'));

    $response->assertOk();
    $response->assertSee(__('Add Rider'), false);
});

test('user without myrider.manage cannot view add rider page', function () {
    $role = Role::firstOrCreate(['name' => 'committee', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($role);
    $user->setActiveRole('committee');

    $response = $this->actingAs($user)->get(route('my-rider.create'));

    $response->assertForbidden();
});

test('creating rider from my rider form assigns current user', function () {
    $role = Role::firstOrCreate(['name' => 'member', 'guard_name' => 'web']);
    $role->givePermissionTo('myrider.manage');
    $user = User::factory()->create();
    $user->assignRole($role);
    $user->setActiveRole('member');

    Livewire::actingAs($user)
        ->test(RiderForm::class, ['forMyRider' => true])
        ->set('name', 'Test Rider')
        ->call('save')
        ->assertRedirect(route('my-rider.index', absolute: false));

    $rider = Rider::where('name', 'Test Rider')->first();
    expect($rider)->not->toBeNull();
    expect($rider->user_id)->toBe($user->id);
});

test('member can open edit page and update own rider', function () {
    $role = Role::firstOrCreate(['name' => 'member', 'guard_name' => 'web']);
    $role->givePermissionTo('myrider.manage');
    $user = User::factory()->create();
    $user->assignRole($role);
    $user->setActiveRole('member');
    $rider = Rider::create(['name' => 'Old Name', 'user_id' => $user->id]);

    $this->actingAs($user)->get(route('my-rider.edit', $rider))->assertOk();

    Livewire::actingAs($user)
        ->test(RiderForm::class, ['rider' => $rider, 'forMyRider' => true])
        ->set('name', 'New Name')
        ->call('save')
        ->assertRedirect(route('my-rider.index', absolute: false));

    expect($rider->fresh()->name)->toBe('New Name');
});

test('member cannot edit or delete rider owned by someone else', function () {
    $role = Role::firstOrCreate(['name' => 'member', 'guard_name' => 'web']);
    $role->givePermissionTo('myrider.manage');
    $user = User::factory()->create();
    $user->assignRole($role);
    $user->setActiveRole('member');
    $other = User::factory()->create();
    $rider = Rider::create(['name' => 'Other', 'user_id' => $other->id]);

    $this->actingAs($user)->get(route('my-rider.edit', $rider))->assertForbidden();
});

test('member cannot delete own rider', function () {
    $role = Role::firstOrCreate(['name' => 'member', 'guard_name' => 'web']);
    $role->givePermissionTo('myrider.manage');
    $user = User::factory()->create();
    $user->assignRole($role);
    $user->setActiveRole('member');
    $rider = Rider::create(['name' => 'Mine', 'user_id' => $user->id]);

    $this->actingAs($user)->delete(route('riders.destroy', $rider))->assertForbidden();
    expect(Rider::find($rider->id))->not->toBeNull();
});

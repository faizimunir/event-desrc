<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $guard = 'web';
    Permission::firstOrCreate(['name' => 'access_drag_race_timer', 'guard_name' => $guard]);
});

test('api time returns server time in milliseconds', function () {
    $this->getJson('/api/time')
        ->assertOk()
        ->assertJsonStructure(['serverTime']);

    $serverTime = $this->getJson('/api/time')->json('serverTime');
    expect($serverTime)->toBeInt();
    expect($serverTime)->toBeGreaterThan(1_700_000_000_000);
});

test('drag race timer index is forbidden without permission', function () {
    $role = Role::firstOrCreate(['name' => 'member_plain', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($role);
    $user->setActiveRole('member_plain');

    $this->actingAs($user)->get(route('drag-race-timer.index'))->assertForbidden();
});

test('drag race timer index is allowed with permission on active role', function () {
    $role = Role::firstOrCreate(['name' => 'timer_operator', 'guard_name' => 'web']);
    $role->givePermissionTo('access_drag_race_timer');

    $user = User::factory()->create();
    $user->assignRole($role);
    $user->setActiveRole('timer_operator');

    $this->actingAs($user)->get(route('drag-race-timer.index'))->assertOk();
});

test('super admin can open drag race timer', function () {
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole('super_admin');
    $user->setActiveRole('super_admin');

    $this->actingAs($user)->get(route('drag-race-timer.index'))->assertOk();
});

test('start race returns running state', function () {
    $role = Role::firstOrCreate(['name' => 'timer_operator', 'guard_name' => 'web']);
    $role->givePermissionTo('access_drag_race_timer');
    $user = User::factory()->create();
    $user->assignRole($role);
    $user->setActiveRole('timer_operator');

    $this->actingAs($user)
        ->postJson(route('drag-race-timer.start'), ['countdown' => false])
        ->assertOk()
        ->assertJsonPath('phase', 'running')
        ->assertJsonStructure(['start_time_ms', 'finish_a_ms', 'finish_b_ms', 'history']);
});

test('clear history returns empty history', function () {
    $role = Role::firstOrCreate(['name' => 'timer_operator', 'guard_name' => 'web']);
    $role->givePermissionTo('access_drag_race_timer');
    $user = User::factory()->create();
    $user->assignRole($role);
    $user->setActiveRole('timer_operator');

    $this->actingAs($user)
        ->postJson(route('drag-race-timer.clear-history'))
        ->assertOk()
        ->assertJsonPath('history', []);
});

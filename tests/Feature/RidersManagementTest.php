<?php

use App\Models\Bracket;
use App\Models\Event;
use App\Models\Package;
use App\Models\Registration;
use App\Models\Rider;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $guard = 'web';
    foreach (['rider.read', 'rider.create', 'rider.update', 'rider.delete'] as $name) {
        Permission::firstOrCreate(['name' => $name, 'guard_name' => $guard]);
    }
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => $guard]);
    Role::firstOrCreate(['name' => 'member', 'guard_name' => $guard]);
    $superAdmin = Role::findByName('super_admin', $guard);
    $superAdmin->syncPermissions(Permission::where('guard_name', $guard)->get());
});

test('super_admin can view rider show page with parent and registrations', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    $admin->setActiveRole('super_admin');

    $parent = User::factory()->create(['name' => 'Budi Parent', 'whatsapp' => '08122223333']);
    $parent->assignRole('member');

    $rider = Rider::query()->create([
        'user_id' => $parent->id,
        'name' => 'Raka Rider',
        'nickname' => 'Raka',
        'number_plate' => 'B 88 RC',
        'gender' => 'boys',
    ]);

    $event = Event::query()->create([
        'title' => 'Sunday Race',
        'start_at' => now()->addDay(),
        'end_at' => now()->addDays(2),
        'status' => Event::STATUS_OPEN_REGIST,
    ]);

    $bracket = Bracket::query()->create([
        'event_id' => $event->id,
        'name' => 'Junior',
        'quota' => 16,
    ]);

    $package = Package::query()->create([
        'event_id' => $event->id,
        'name' => 'Regular',
        'price' => 150_000,
        'status' => Package::STATUS_ACTIVE,
    ]);

    Registration::query()->create([
        'event_id' => $event->id,
        'rider_id' => $rider->id,
        'bracket_id' => $bracket->id,
        'package_id' => $package->id,
        'status' => Registration::STATUS_PENDING,
    ]);

    $response = $this->actingAs($admin)->get(route('riders.show', $rider));

    $response->assertOk();
    $response->assertSee('Raka Rider', false);
    $response->assertSee('Budi Parent', false);
    $response->assertSee('Sunday Race', false);
    $response->assertSee(__('Parent'), false);
    $response->assertSee(__('Events'), false);
});

test('user without rider.read cannot view rider show page', function () {
    $member = Role::firstOrCreate(['name' => 'member', 'guard_name' => 'web']);
    $viewer = User::factory()->create();
    $viewer->assignRole($member);
    $viewer->setActiveRole('member');

    $rider = Rider::query()->create([
        'name' => 'Hidden Rider',
    ]);

    $response = $this->actingAs($viewer)->get(route('riders.show', $rider));

    $response->assertForbidden();
});

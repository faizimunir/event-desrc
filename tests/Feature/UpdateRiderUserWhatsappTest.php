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
    foreach (['event.update', 'event.read'] as $name) {
        Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
});

function waAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole('super_admin');
    $user->setActiveRole('super_admin');

    return $user;
}

/**
 * @return array{parent: User, rider: Rider, event: Event, registration: Registration}
 */
function waRegistrationSetup(array $userAttrs = []): array
{
    $parent = User::factory()->create(array_merge([
        'name' => 'Andre susanto',
        'whatsapp' => '628885307728',
    ], $userAttrs));

    $rider = Rider::query()->create([
        'user_id' => $parent->id,
        'name' => 'Rider One',
        'dob' => '2015-01-01',
        'gender' => 'boys',
    ]);

    $event = Event::query()->create([
        'title' => 'Test Event WA',
        'start_at' => now()->addDay(),
        'end_at' => now()->addDays(2),
        'status' => Event::STATUS_OPEN_REGIST,
    ]);

    $bracket = Bracket::query()->create([
        'event_id' => $event->id,
        'name' => 'Open',
        'quota' => 32,
    ]);

    $package = Package::query()->create([
        'event_id' => $event->id,
        'name' => 'Regular',
        'price' => 100_000,
        'status' => Package::STATUS_ACTIVE,
    ]);

    $registration = Registration::query()->create([
        'event_id' => $event->id,
        'rider_id' => $rider->id,
        'bracket_id' => $bracket->id,
        'package_id' => $package->id,
        'status' => Registration::STATUS_APPROVED,
    ]);

    return compact('parent', 'rider', 'event', 'registration');
}

test('admin can update rider user whatsapp and normalizes to 62', function () {
    $admin = waAdmin();
    extract(waRegistrationSetup());

    $response = $this->actingAs($admin)->from(route('events.registrations.show', [$event, $registration]))->post(
        route('events.registrations.update-rider-user-whatsapp', [$event, $registration]),
        ['whatsapp' => '081234567890']
    );

    $response->assertRedirect(route('events.registrations.show', [$event, $registration]));
    $response->assertSessionHas('status');
    expect($parent->fresh()->whatsapp)->toBe('6281234567890');
});

test('updating whatsapp to an existing number returns validation error instead of 500', function () {
    $admin = waAdmin();
    extract(waRegistrationSetup());

    User::factory()->create(['whatsapp' => '628111111111']);

    $response = $this->actingAs($admin)->from(route('events.registrations.show', [$event, $registration]))->post(
        route('events.registrations.update-rider-user-whatsapp', [$event, $registration]),
        ['whatsapp' => '628111111111']
    );

    $response->assertRedirect(route('events.registrations.show', [$event, $registration]));
    $response->assertSessionHasErrors('whatsapp');
    expect($parent->fresh()->whatsapp)->toBe('628885307728');
});

test('saving same whatsapp succeeds', function () {
    $admin = waAdmin();
    extract(waRegistrationSetup());

    $response = $this->actingAs($admin)->post(
        route('events.registrations.update-rider-user-whatsapp', [$event, $registration]),
        ['whatsapp' => '628885307728']
    );

    $response->assertRedirect(route('events.registrations.show', [$event, $registration]));
    expect($parent->fresh()->whatsapp)->toBe('628885307728');
});

test('whatsapp longer than 20 chars is rejected by validation', function () {
    $admin = waAdmin();
    extract(waRegistrationSetup());

    $response = $this->actingAs($admin)->from(route('events.registrations.show', [$event, $registration]))->post(
        route('events.registrations.update-rider-user-whatsapp', [$event, $registration]),
        ['whatsapp' => str_repeat('1', 25)]
    );

    $response->assertRedirect(route('events.registrations.show', [$event, $registration]));
    $response->assertSessionHasErrors('whatsapp');
    expect($parent->fresh()->whatsapp)->toBe('628885307728');
});

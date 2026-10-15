<?php

use App\Exceptions\ComponentHasRegistrationsException;
use App\Models\Bracket;
use App\Models\Event;
use App\Models\Order;
use App\Models\Package;
use App\Models\Registration;
use App\Models\Rider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (['bracket.read', 'bracket.update', 'bracket.delete', 'package.read', 'package.update', 'package.delete', 'event.read', 'event.update'] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    Role::findOrCreate('super_admin', 'web');
    Role::findOrCreate('admin', 'web')->syncPermissions(Permission::all());
    Role::findOrCreate('member', 'web');
});

function guardUser(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);
    $user->setActiveRole($role);

    return $user;
}

function guardEvent(): Event
{
    return Event::create([
        'title' => 'Guard Event',
        'category' => Event::CATEGORY_UMUR,
        'start_at' => now()->addMonth(),
        'status' => Event::STATUS_OPEN_REGIST,
        'payment_methods' => [Event::PAYMENT_MANUAL],
        'registration_opens_at' => now()->subDay(),
        'registration_closes_at' => now()->addDay(),
    ]);
}

function guardBracket(Event $event, array $attributes = []): Bracket
{
    return Bracket::create(array_merge(['event_id' => $event->id, 'name' => 'Bracket '.fake()->unique()->word()], $attributes));
}

function guardPackage(Event $event, array $attributes = []): Package
{
    return Package::create(array_merge([
        'event_id' => $event->id,
        'name' => 'Package '.fake()->unique()->word(),
        'price' => 100000,
        'admin_fee' => 0,
        'admin_fee_included_in_price' => true,
        'status' => Package::STATUS_ACTIVE,
    ], $attributes));
}

function guardRegistration(Event $event, Bracket $bracket, Package $package, array $overrides = [], string $orderStatus = Order::STATUS_PAID, string $dob = '2018-05-05'): Registration
{
    $rider = Rider::create([
        'user_id' => User::factory()->create()->id,
        'name' => 'Rider '.fake()->unique()->firstName(),
        'dob' => $dob,
        'gender' => 'boys',
    ]);

    $registration = Registration::create(array_merge([
        'event_id' => $event->id,
        'rider_id' => $rider->id,
        'bracket_id' => $bracket->id,
        'package_id' => $package->id,
        'status' => Registration::STATUS_APPROVED,
    ], $overrides));

    Order::create(['registration_id' => $registration->id, 'status' => $orderStatus]);

    return $registration;
}

// ---------------------------------------------------------------- delete: bracket

test('admin can delete a bracket that has no registrants', function () {
    $event = guardEvent();
    $bracket = guardBracket($event);

    $this->actingAs(guardUser('admin'))
        ->delete(route('events.brackets.destroy', [$event, $bracket]))
        ->assertRedirect(route('events.show', [$event, 'tab' => 'brackets']));

    expect(Bracket::find($bracket->id))->toBeNull();
});

test('admin cannot delete a bracket that has registrants and nothing is lost', function () {
    $event = guardEvent();
    $bracket = guardBracket($event);
    $package = guardPackage($event);
    $registration = guardRegistration($event, $bracket, $package);

    $this->actingAs(guardUser('admin'))
        ->delete(route('events.brackets.destroy', [$event, $bracket]))
        ->assertRedirect(route('events.brackets.edit', [$event, $bracket]))
        ->assertSessionHas('error');

    expect(Bracket::find($bracket->id))->not->toBeNull()
        ->and(Registration::find($registration->id))->not->toBeNull()
        ->and(Order::where('registration_id', $registration->id)->exists())->toBeTrue();
});

test('cancelled registrations also block bracket deletion', function () {
    $event = guardEvent();
    $bracket = guardBracket($event);
    guardRegistration($event, $bracket, guardPackage($event), ['status' => Registration::STATUS_CANCELLED], Order::STATUS_CANCELLED);

    $this->actingAs(guardUser('admin'))
        ->delete(route('events.brackets.destroy', [$event, $bracket]))
        ->assertSessionHas('error');

    expect(Bracket::find($bracket->id))->not->toBeNull();
});

test('admin cannot bypass the guard with force_delete', function () {
    $event = guardEvent();
    $bracket = guardBracket($event, ['name' => 'U7']);
    $registration = guardRegistration($event, $bracket, guardPackage($event));

    $this->actingAs(guardUser('admin'))
        ->delete(route('events.brackets.destroy', [$event, $bracket]), ['force_delete' => 1, 'confirm_name' => 'U7'])
        ->assertSessionHas('error');

    expect(Bracket::find($bracket->id))->not->toBeNull()
        ->and(Registration::find($registration->id))->not->toBeNull();
});

test('super admin force delete requires the exact bracket name', function () {
    $event = guardEvent();
    $bracket = guardBracket($event, ['name' => 'U7']);
    $registration = guardRegistration($event, $bracket, guardPackage($event));

    $this->actingAs(guardUser('super_admin'))
        ->delete(route('events.brackets.destroy', [$event, $bracket]), ['force_delete' => 1, 'confirm_name' => 'wrong'])
        ->assertSessionHas('error');

    expect(Bracket::find($bracket->id))->not->toBeNull()
        ->and(Registration::find($registration->id))->not->toBeNull();
});

test('super admin without force_delete is blocked too', function () {
    $event = guardEvent();
    $bracket = guardBracket($event);
    guardRegistration($event, $bracket, guardPackage($event));

    $this->actingAs(guardUser('super_admin'))
        ->delete(route('events.brackets.destroy', [$event, $bracket]))
        ->assertSessionHas('error');

    expect(Bracket::find($bracket->id))->not->toBeNull();
});

test('super admin can force delete a bracket with the right confirmation name', function () {
    $event = guardEvent();
    $bracket = guardBracket($event, ['name' => 'U7']);
    $registration = guardRegistration($event, $bracket, guardPackage($event));

    $this->actingAs(guardUser('super_admin'))
        ->delete(route('events.brackets.destroy', [$event, $bracket]), ['force_delete' => 1, 'confirm_name' => 'U7'])
        ->assertRedirect(route('events.show', [$event, 'tab' => 'brackets']))
        ->assertSessionHas('status');

    expect(Bracket::find($bracket->id))->toBeNull()
        ->and(Registration::find($registration->id))->toBeNull();
});

test('model level guard throws when deleting a bracket or package with registrants', function () {
    $event = guardEvent();
    $bracket = guardBracket($event);
    $package = guardPackage($event);
    guardRegistration($event, $bracket, $package);

    expect(fn () => $bracket->delete())->toThrow(ComponentHasRegistrationsException::class)
        ->and(fn () => $package->delete())->toThrow(ComponentHasRegistrationsException::class)
        ->and(Bracket::find($bracket->id))->not->toBeNull()
        ->and(Package::find($package->id))->not->toBeNull();
});

// ---------------------------------------------------------------- delete: package

test('admin can delete a package that has no registrants', function () {
    $event = guardEvent();
    $package = guardPackage($event);

    $this->actingAs(guardUser('admin'))
        ->delete(route('events.packages.destroy', [$event, $package]))
        ->assertRedirect(route('events.show', [$event, 'tab' => 'packages']));

    expect(Package::find($package->id))->toBeNull();
});

test('admin cannot delete a package that has registrants and nothing is lost', function () {
    $event = guardEvent();
    $package = guardPackage($event);
    $registration = guardRegistration($event, guardBracket($event), $package);

    $this->actingAs(guardUser('admin'))
        ->delete(route('events.packages.destroy', [$event, $package]))
        ->assertRedirect(route('events.packages.edit', [$event, $package]))
        ->assertSessionHas('error');

    expect(Package::find($package->id))->not->toBeNull()
        ->and(Registration::find($registration->id))->not->toBeNull();
});

test('super admin can force delete a package with the right confirmation name', function () {
    $event = guardEvent();
    $package = guardPackage($event, ['name' => 'Early Bird']);
    $registration = guardRegistration($event, guardBracket($event), $package);

    $this->actingAs(guardUser('super_admin'))
        ->delete(route('events.packages.destroy', [$event, $package]), ['force_delete' => 1, 'confirm_name' => 'Early Bird'])
        ->assertSessionHas('status');

    expect(Package::find($package->id))->toBeNull()
        ->and(Registration::find($registration->id))->toBeNull();
});

test('user without permission cannot delete or transfer', function () {
    $event = guardEvent();
    $bracket = guardBracket($event);
    $other = guardBracket($event);
    $member = guardUser('member');

    $this->actingAs($member)->delete(route('events.brackets.destroy', [$event, $bracket]))->assertForbidden();
    $this->actingAs($member)
        ->post(route('events.brackets.transfer', [$event, $bracket]), ['target_bracket_id' => $other->id])
        ->assertForbidden();
});

// ---------------------------------------------------------------- transfer: bracket

test('transferring a bracket moves eligible riders and reports the ones that do not qualify', function () {
    $event = guardEvent();
    $source = guardBracket($event, ['name' => 'Open']);
    $target = guardBracket($event, ['name' => 'U7 2018', 'rule_type' => 'birth_year', 'birth_year_start' => 2018, 'birth_year_end' => 2018]);
    $package = guardPackage($event);

    $eligible = guardRegistration($event, $source, $package, [], Order::STATUS_PAID, '2018-03-03');
    $tooOld = guardRegistration($event, $source, $package, [], Order::STATUS_PAID, '2012-03-03');

    $this->actingAs(guardUser('admin'))
        ->post(route('events.brackets.transfer', [$event, $source]), ['target_bracket_id' => $target->id])
        ->assertRedirect(route('events.brackets.edit', [$event, $source]))
        ->assertSessionHas('status')
        ->assertSessionHas('transfer_failures', fn (array $failures) => count($failures) === 1 && $failures[0]['registration_id'] === $tooOld->id);

    expect($eligible->fresh()->bracket_id)->toBe($target->id)
        ->and($tooOld->fresh()->bracket_id)->toBe($source->id);

    // Masih ada pendaftar -> belum boleh dihapus.
    $this->actingAs(guardUser('admin'))
        ->delete(route('events.brackets.destroy', [$event, $source]))
        ->assertSessionHas('error');
    expect(Bracket::find($source->id))->not->toBeNull();
});

test('bracket can be deleted once every registrant has been transferred', function () {
    $event = guardEvent();
    $source = guardBracket($event);
    $target = guardBracket($event);
    $package = guardPackage($event);
    $a = guardRegistration($event, $source, $package);
    $b = guardRegistration($event, $source, $package);
    $admin = guardUser('admin');

    $this->actingAs($admin)
        ->post(route('events.brackets.transfer', [$event, $source]), ['target_bracket_id' => $target->id])
        ->assertSessionMissing('transfer_failures');

    expect($a->fresh()->bracket_id)->toBe($target->id)
        ->and($b->fresh()->bracket_id)->toBe($target->id);

    $this->actingAs($admin)->delete(route('events.brackets.destroy', [$event, $source]))->assertSessionHas('status');

    expect(Bracket::find($source->id))->toBeNull()
        ->and(Registration::count())->toBe(2);
});

test('bracket transfer respects the target quota', function () {
    $event = guardEvent();
    $source = guardBracket($event);
    $target = guardBracket($event, ['quota' => 1]);
    $package = guardPackage($event);
    guardRegistration($event, $source, $package);
    guardRegistration($event, $source, $package);

    $this->actingAs(guardUser('admin'))
        ->post(route('events.brackets.transfer', [$event, $source]), ['target_bracket_id' => $target->id])
        ->assertSessionHas('transfer_failures', fn (array $failures) => count($failures) === 1);

    expect(Registration::where('bracket_id', $target->id)->count())->toBe(1)
        ->and(Registration::where('bracket_id', $source->id)->count())->toBe(1);
});

test('cancelled registrations are moved without eligibility or quota checks', function () {
    $event = guardEvent();
    $source = guardBracket($event);
    $target = guardBracket($event, ['quota' => 1, 'rule_type' => 'birth_year', 'birth_year_start' => 2018, 'birth_year_end' => 2018]);
    $cancelled = guardRegistration($event, $source, guardPackage($event), ['status' => Registration::STATUS_CANCELLED], Order::STATUS_CANCELLED, '2010-01-01');

    $this->actingAs(guardUser('admin'))
        ->post(route('events.brackets.transfer', [$event, $source]), ['target_bracket_id' => $target->id])
        ->assertSessionMissing('transfer_failures');

    expect($cancelled->fresh()->bracket_id)->toBe($target->id);
});

test('bracket transfer rejects a target from another event or the same bracket', function () {
    $event = guardEvent();
    $source = guardBracket($event);
    $foreign = guardBracket(guardEvent());
    $admin = guardUser('admin');

    $this->actingAs($admin)
        ->post(route('events.brackets.transfer', [$event, $source]), ['target_bracket_id' => $foreign->id])
        ->assertSessionHasErrors('target_bracket_id');
    $this->actingAs($admin)
        ->post(route('events.brackets.transfer', [$event, $source]), ['target_bracket_id' => $source->id])
        ->assertSessionHasErrors('target_bracket_id');
});

// ---------------------------------------------------------------- transfer: package

test('transferring a package moves registrants and warns when the price differs', function () {
    $event = guardEvent();
    $bracket = guardBracket($event);
    $source = guardPackage($event, ['price' => 100000]);
    $target = guardPackage($event, ['price' => 150000]);
    $registration = guardRegistration($event, $bracket, $source);

    $this->actingAs(guardUser('admin'))
        ->post(route('events.packages.transfer', [$event, $source]), ['target_package_id' => $target->id])
        ->assertSessionHas('status', fn (string $status) => str_contains($status, 'different price'));

    expect($registration->fresh()->package_id)->toBe($target->id);
});

test('package transfer only accepts an active target in the same event', function () {
    $event = guardEvent();
    $bracket = guardBracket($event);
    $source = guardPackage($event);
    $inactive = guardPackage($event, ['status' => Package::STATUS_NOT_ACTIVE]);
    $registration = guardRegistration($event, $bracket, $source);

    $this->actingAs(guardUser('admin'))
        ->post(route('events.packages.transfer', [$event, $source]), ['target_package_id' => $inactive->id])
        ->assertSessionHasErrors('target_package_id');

    expect($registration->fresh()->package_id)->toBe($source->id);
});

test('package transfer respects the target quota', function () {
    $event = guardEvent();
    $bracket = guardBracket($event);
    $source = guardPackage($event);
    $target = guardPackage($event, ['quota' => 1]);
    guardRegistration($event, $bracket, $source);
    guardRegistration($event, $bracket, $source);

    $this->actingAs(guardUser('admin'))
        ->post(route('events.packages.transfer', [$event, $source]), ['target_package_id' => $target->id])
        ->assertSessionHas('transfer_failures', fn (array $failures) => count($failures) === 1);

    expect(Registration::where('package_id', $target->id)->count())->toBe(1)
        ->and(Registration::where('package_id', $source->id)->count())->toBe(1);
});

// ---------------------------------------------------------------- pages

test('edit pages render the empty and the blocked states', function () {
    $event = guardEvent();
    $emptyBracket = guardBracket($event);
    $usedBracket = guardBracket($event);
    $emptyPackage = guardPackage($event);
    $usedPackage = guardPackage($event);
    guardRegistration($event, $usedBracket, $usedPackage);
    $admin = guardUser('admin');

    $this->actingAs($admin)->get(route('events.brackets.edit', [$event, $emptyBracket]))
        ->assertOk()->assertSee('safe to delete')->assertDontSee('Force delete');
    $this->actingAs($admin)->get(route('events.brackets.edit', [$event, $usedBracket]))
        ->assertOk()->assertSee('cannot be deleted yet')->assertSee('Transfer registrants')->assertDontSee('Force delete');
    $this->actingAs($admin)->get(route('events.packages.edit', [$event, $emptyPackage]))
        ->assertOk()->assertSee('safe to delete');
    $this->actingAs($admin)->get(route('events.packages.edit', [$event, $usedPackage]))
        ->assertOk()->assertSee('cannot be deleted yet')->assertSee('Transfer registrants');

    $this->actingAs(guardUser('super_admin'))->get(route('events.brackets.edit', [$event, $usedBracket]))
        ->assertOk()->assertSee('Force delete');
});

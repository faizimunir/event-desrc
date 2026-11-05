<?php

use App\Livewire\Registrations\EventRegistrationForm;
use App\Models\Bracket;
use App\Models\Event;
use App\Models\Package;
use App\Models\Registration;
use App\Models\Rider;
use App\Models\Team;
use App\Models\User;
use App\Services\RegistrationParentClaim;
use App\Services\WhacenterService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Queue::fake();
    Storage::fake('public');
    Storage::fake('local');
    foreach (['event.update', 'event.read', 'user.read'] as $name) {
        Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'member', 'guard_name' => 'web']);
});

function claimEvent(): array
{
    $event = Event::query()->create([
        'title' => 'Claim Event',
        'start_at' => now()->addDay(),
        'end_at' => now()->addDays(2),
        'status' => Event::STATUS_OPEN_REGIST,
        'uses_system_registration' => true,
    ]);

    $bracket = Bracket::query()->create([
        'event_id' => $event->id,
        'name' => 'Open',
        'quota' => 50,
    ]);

    $package = Package::query()->create([
        'event_id' => $event->id,
        'name' => 'Regular',
        'price' => 100_000,
        'status' => Package::STATUS_ACTIVE,
    ]);

    $team = Team::query()->create(['name' => 'Team Claim']);

    return compact('event', 'bracket', 'package', 'team');
}

test('user masked display name keeps first word and masks the rest', function () {
    $user = new User(['name' => 'Andre susanto']);

    expect($user->maskedDisplayName())->toBe('Andre S******');
});

test('registration parent claim session verifies matching whatsapp only', function () {
    $user = User::factory()->create(['whatsapp' => '628885307728']);

    expect(RegistrationParentClaim::isVerified('628885307728'))->toBeFalse();

    RegistrationParentClaim::markVerified($user, '628885307728');

    expect(RegistrationParentClaim::isVerified('628885307728'))->toBeTrue()
        ->and(RegistrationParentClaim::isVerified('628111111111'))->toBeFalse();
});

test('existing whatsapp opens parent claim modal instead of silent reuse', function () {
    extract(claimEvent());

    User::factory()->create([
        'name' => 'Andre susanto',
        'whatsapp' => '628885307728',
    ]);

    Livewire::test(EventRegistrationForm::class, ['event' => $event->fresh(['brackets', 'packages'])])
        ->set('bracket_id', $bracket->id)
        ->set('package_id', $package->id)
        ->set('parent_name', 'Someone Else')
        ->set('whatsapp', '08885307728')
        ->set('name', 'Rider Baru')
        ->set('nickname', 'Baru')
        ->set('pob', 'Jakarta')
        ->set('dob', '2016-01-01')
        ->set('gender', 'boys')
        ->set('number_plate', '12')
        ->set('selectedTeamIds', [$team->id])
        ->set('photo_kia', UploadedFile::fake()->image('kia.jpg'))
        ->call('submit')
        ->assertSet('showParentClaimModal', true)
        ->assertSet('parentClaimStep', 'confirm')
        ->assertSet('claimedParentMaskedName', 'Andre S******');
});

test('verified otp allows registration under existing parent account', function () {
    extract(claimEvent());

    $parent = User::factory()->create([
        'name' => 'Andre susanto',
        'whatsapp' => '628885307728',
    ]);
    $parent->assignRole('member');

    $this->mock(WhacenterService::class, function ($mock) {
        $mock->shouldReceive('requestOtp')
            ->once()
            ->andReturn(['sent' => true, 'reason' => null, 'retry_after' => 0]);
        $mock->shouldReceive('verifyOtp')
            ->once()
            ->withArgs(fn ($wa, $otp) => (string) $wa === '628885307728' && $otp === '123456')
            ->andReturn(true);
    });

    Livewire::test(EventRegistrationForm::class, ['event' => $event->fresh(['brackets', 'packages'])])
        ->set('bracket_id', $bracket->id)
        ->set('package_id', $package->id)
        ->set('parent_name', 'Andre susanto')
        ->set('whatsapp', '628885307728')
        ->set('name', 'Rider Kedua')
        ->set('nickname', 'Kedua')
        ->set('pob', 'Bandung')
        ->set('dob', '2017-02-02')
        ->set('gender', 'boys')
        ->set('number_plate', '7')
        ->set('selectedTeamIds', [$team->id])
        ->set('photo_kia', UploadedFile::fake()->image('kia2.jpg'))
        ->call('submit')
        ->assertSet('showParentClaimModal', true)
        ->call('confirmParentClaim')
        ->assertSet('parentClaimStep', 'otp')
        ->set('otp', '123456')
        ->call('verifyParentClaimOtp')
        ->assertRedirect();

    $rider = Rider::query()->where('name', 'Rider Kedua')->first();
    expect($rider)->not->toBeNull()
        ->and($rider->user_id)->toBe($parent->id)
        ->and(RegistrationParentClaim::isVerified('628885307728'))->toBeTrue();
});

test('session prefill skips claim on second registration attempt', function () {
    extract(claimEvent());

    $parent = User::factory()->create([
        'name' => 'Andre susanto',
        'whatsapp' => '628885307728',
    ]);
    RegistrationParentClaim::rememberPrefill($parent, $event, '628885307728');

    Livewire::test(EventRegistrationForm::class, ['event' => $event->fresh(['brackets', 'packages'])])
        ->assertSet('whatsapp', '628885307728')
        ->assertSet('parent_name', 'Andre susanto')
        ->assertSet('whatsappIdentityVerified', true)
        ->set('bracket_id', $bracket->id)
        ->set('package_id', $package->id)
        ->set('name', 'Rider Ketiga')
        ->set('nickname', 'Tiga')
        ->set('pob', 'Surabaya')
        ->set('dob', '2018-03-03')
        ->set('gender', 'boys')
        ->set('number_plate', '3')
        ->set('selectedTeamIds', [$team->id])
        ->set('photo_kia', UploadedFile::fake()->image('kia3.jpg'))
        ->call('submit')
        ->assertSet('showParentClaimModal', false)
        ->assertRedirect();

    expect(Rider::query()->where('name', 'Rider Ketiga')->where('user_id', $parent->id)->exists())->toBeTrue();
});

test('admin can reassign rider to existing whatsapp owner instead of overwriting', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    $admin->setActiveRole('super_admin');

    extract(claimEvent());

    $owner = User::factory()->create([
        'name' => 'Owner WA',
        'whatsapp' => '628111111111',
    ]);
    $orphan = User::factory()->create([
        'name' => 'Orphan Parent',
        'whatsapp' => '628222222222',
    ]);
    $rider = Rider::query()->create([
        'user_id' => $orphan->id,
        'name' => 'Move Me',
        'dob' => '2015-01-01',
        'gender' => 'boys',
    ]);
    $registration = Registration::query()->create([
        'event_id' => $event->id,
        'rider_id' => $rider->id,
        'bracket_id' => $bracket->id,
        'package_id' => $package->id,
        'status' => Registration::STATUS_APPROVED,
    ]);

    $this->actingAs($admin)
        ->from(route('events.registrations.show', [$event, $registration]))
        ->post(route('events.registrations.update-rider-user-whatsapp', [$event, $registration]), [
            'whatsapp' => '628111111111',
        ])
        ->assertRedirect(route('events.registrations.show', [$event, $registration]))
        ->assertSessionHasErrors('whatsapp')
        ->assertSessionHas('whatsapp_owner_id', $owner->id);

    $this->actingAs($admin)
        ->post(route('events.registrations.reassign-rider-whatsapp', [$event, $registration]), [
            'whatsapp' => '628111111111',
        ])
        ->assertRedirect(route('events.registrations.show', [$event, $registration]))
        ->assertSessionHas('status');

    expect($rider->fresh()->user_id)->toBe($owner->id)
        ->and($owner->fresh()->whatsapp)->toBe('628111111111')
        ->and($orphan->fresh()->whatsapp)->toBe('628222222222');
});

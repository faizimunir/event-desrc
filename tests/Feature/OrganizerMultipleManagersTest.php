<?php

use App\Livewire\Organizers\OrganizerForm;
use App\Livewire\Organizers\OrganizerList;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\User;
use App\Services\TicketWhatsappBroadcast;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function managerUser(string $role = 'organizer'): User
{
    $user = User::factory()->create();
    $user->assignRole($role);
    $user->setActiveRole($role);

    return $user;
}

function managedEvent(Organizer $organizer): Event
{
    return Event::query()->create([
        'title' => 'Event '.uniqid(),
        'start_at' => now()->addDay(),
        'end_at' => now()->addDays(2),
        'status' => Event::STATUS_DRAFT,
        'organizer_id' => $organizer->id,
    ]);
}

test('multiple organizer users can manage the same event', function () {
    $first = managerUser();
    $second = managerUser();
    $outsider = managerUser();

    $organizer = Organizer::query()->create(['name' => 'Shared']);
    $organizer->users()->sync([$first->id, $second->id]);
    $event = managedEvent($organizer);

    foreach ([$first, $second] as $manager) {
        expect($manager->can('view', $event))->toBeTrue()
            ->and($manager->can('update', $event))->toBeTrue()
            ->and($manager->can('delete', $event))->toBeTrue()
            ->and($manager->can('update', $organizer))->toBeTrue()
            ->and(TicketWhatsappBroadcast::accessibleEvents($manager)->pluck('id')->all())->toContain($event->id);
    }

    expect($outsider->can('update', $event))->toBeFalse()
        ->and($outsider->can('update', $organizer))->toBeFalse()
        ->and(TicketWhatsappBroadcast::accessibleEvents($outsider)->pluck('id')->all())->not->toContain($event->id);
});

test('organizer list only shows organizers managed by the user', function () {
    // Role organizer secara default tidak punya akses menu Organizer; beri izin khusus untuk test ini.
    Role::findByName('organizer', 'web')->givePermissionTo('organizer.read');
    $first = managerUser();
    $second = managerUser();

    $shared = Organizer::query()->create(['name' => 'Shared']);
    $shared->users()->sync([$first->id, $second->id]);
    $other = Organizer::query()->create(['name' => 'Other']);
    $other->users()->sync([$second->id]);

    Livewire::actingAs($first)
        ->test(OrganizerList::class)
        ->assertSee('Shared')
        ->assertDontSee('Other');
});

test('admin can assign several users to an organizer', function () {
    $first = managerUser();
    $second = managerUser();
    // Dibuat terakhir karena active role disimpan di session.
    $admin = managerUser('admin');

    Livewire::actingAs($admin)
        ->test(OrganizerForm::class)
        ->set('name', 'Bhinneka')
        ->set('user_ids', [(string) $first->id, (string) $second->id])
        ->call('save')
        ->assertHasNoErrors();

    $organizer = Organizer::query()->where('name', 'Bhinneka')->firstOrFail();

    expect($organizer->users->pluck('id')->sort()->values()->all())->toBe([$first->id, $second->id]);

    Livewire::actingAs($admin)
        ->test(OrganizerForm::class, ['organizer' => $organizer])
        ->set('user_ids', [(string) $second->id])
        ->call('save')
        ->assertHasNoErrors();

    expect($organizer->users()->pluck('users.id')->all())->toBe([$second->id]);
});

test('organizer creating an organizer is assigned as its manager', function () {
    Role::findByName('organizer', 'web')->givePermissionTo('organizer.create');
    $organizerUser = managerUser();

    Livewire::actingAs($organizerUser)
        ->test(OrganizerForm::class)
        ->set('name', 'Mine')
        ->call('save')
        ->assertHasNoErrors();

    $organizer = Organizer::query()->where('name', 'Mine')->firstOrFail();

    expect($organizer->isManagedBy($organizerUser))->toBeTrue();
});

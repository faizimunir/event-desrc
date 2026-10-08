<?php

use App\Jobs\BulkSendTicketWhatsappJob;
use App\Jobs\SendWhacenterMessageJob;
use App\Livewire\WhatsappNotifications\TicketNotificationCenter;
use App\Models\Bracket;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\Package;
use App\Models\Registration;
use App\Models\Rider;
use App\Models\User;
use App\Models\WhatsappNotificationLog;
use App\Services\TicketWhatsappBroadcast;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Livewire\Livewire;

beforeEach(function () {
    config([
        'services.whacenter.device_id' => 'test-device',
        'services.whacenter.queue_connection' => 'sync',
    ]);
    Cache::flush();
    waDevice(true);
    $this->seed(RolesAndPermissionsSeeder::class);
});

function waUser(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);
    $user->setActiveRole($role);

    return $user;
}

/**
 * @param  User|list<User>  $managers
 */
function waOrganizer(string $name, User|array $managers): Organizer
{
    $organizer = Organizer::query()->create(['name' => $name]);
    $organizer->users()->sync(collect(is_array($managers) ? $managers : [$managers])->pluck('id')->all());

    return $organizer;
}

function waEvent(?Organizer $organizer = null): Event
{
    return Event::query()->create([
        'title' => 'Event '.Str::random(5),
        'slug' => 'ev-'.Str::lower(Str::random(10)),
        'start_at' => now()->addDay(),
        'end_at' => now()->addDays(2),
        'status' => Event::STATUS_OPEN_REGIST,
        'organizer_id' => $organizer?->id,
    ]);
}

function waRegistration(Event $event, ?string $whatsapp = 'auto', string $status = Registration::STATUS_APPROVED, bool $withTicket = true): Registration
{
    $user = User::factory()->create([
        'whatsapp' => $whatsapp === 'auto' ? '08'.random_int(1_000_000_000, 9_999_999_999) : $whatsapp,
    ]);
    $rider = Rider::query()->create(['user_id' => $user->id, 'name' => 'Rider '.Str::random(4), 'nickname' => 'R']);
    $bracket = Bracket::query()->firstOrCreate(
        ['event_id' => $event->id, 'name' => 'Open'],
        ['quota' => 1000]
    );
    $package = Package::query()->firstOrCreate(
        ['event_id' => $event->id, 'name' => 'Paket'],
        ['price' => 100_000, 'status' => Package::STATUS_ACTIVE]
    );
    $registration = Registration::query()->create([
        'event_id' => $event->id,
        'rider_id' => $rider->id,
        'bracket_id' => $bracket->id,
        'package_id' => $package->id,
        'status' => $status,
    ]);

    if ($withTicket) {
        $registration->ticket()->create([]);
    }

    return $registration;
}

function waDevice(bool $connected): void
{
    Cache::forget('whacenter:device_status');
    Cache::forever('test:device-connected', $connected);

    // Stub pertama yang cocok menang, jadi stub membaca flag terkini agar bisa diubah di tengah test.
    Http::fake([
        '*/api/statusDevice*' => fn () => Http::response([
            'status' => (bool) Cache::get('test:device-connected'),
            'data' => ['status' => Cache::get('test:device-connected') ? 'CONNECTED' : 'NOT CONNECTED'],
        ]),
    ]);
}

function waLog(Registration $registration, string $status, string $type = WhatsappNotificationLog::TYPE_TICKET_ISSUED): WhatsappNotificationLog
{
    return $registration->whatsappNotificationLogs()->create([
        'type' => $type,
        'recipient' => '6281234567890',
        'status' => $status,
    ]);
}

test('permissions are granted to super_admin, admin and organizer only', function () {
    foreach (['whatsapp_notification.read', 'whatsapp_notification.send'] as $permission) {
        foreach (['super_admin', 'admin', 'organizer'] as $role) {
            expect(\Spatie\Permission\Models\Role::findByName($role, 'web')->hasPermissionTo($permission))->toBeTrue();
        }
        foreach (['committee', 'member'] as $role) {
            expect(\Spatie\Permission\Models\Role::findByName($role, 'web')->hasPermissionTo($permission))->toBeFalse();
        }
    }
});

test('menu and page are available to allowed roles only', function (string $role, bool $allowed) {
    $response = $this->actingAs(waUser($role))->get(route('whatsapp-notifications.index'));

    $allowed ? $response->assertOk()->assertSee(__('WhatsApp Notifications')) : $response->assertForbidden();
})->with([
    'super_admin' => ['super_admin', true],
    'admin' => ['admin', true],
    'organizer' => ['organizer', true],
    'committee' => ['committee', false],
    'member' => ['member', false],
]);

test('organizer only sees events they own', function () {
    $organizerUser = waUser('organizer');
    $own = waEvent(waOrganizer('Mine', $organizerUser));
    $other = waEvent(waOrganizer('Theirs', User::factory()->create()));

    $ids = TicketWhatsappBroadcast::accessibleEvents($organizerUser)->pluck('id')->all();

    expect($ids)->toContain($own->id)->not->toContain($other->id);

    Livewire::actingAs($organizerUser)
        ->test(TicketNotificationCenter::class, ['eventId' => $other->id])
        ->assertSet('eventId', $own->id);
});

test('summary classifies participants by their latest e-ticket log', function () {
    $event = waEvent();
    $notSent = waRegistration($event);
    $failedThenSent = waRegistration($event);
    $failed = waRegistration($event);
    $queued = waRegistration($event);
    waRegistration($event, null);
    waRegistration($event, '08111', Registration::STATUS_CANCELLED);
    waRegistration($event, '08222', withTicket: false);

    waLog($failedThenSent, 'failed');
    waLog($failedThenSent, 'sent', WhatsappNotificationLog::TYPE_TICKET_RESENT);
    waLog($failed, 'sent');
    waLog($failed, 'failed', WhatsappNotificationLog::TYPE_TICKET_RESENT);
    waLog($queued, 'queued');

    $summary = TicketWhatsappBroadcast::summary($event->id);

    expect($summary['counts'])->toBe(['total' => 5, 'not_sent' => 2, 'failed' => 1, 'queued' => 1, 'sent' => 1])
        ->and($summary['no_whatsapp'])->toBe(1)
        ->and($summary['sendable'])->toBe(['not_sent' => 1, 'failed' => 1, 'all' => 3]);

    expect(TicketWhatsappBroadcast::bulkQuery($event->id, 'failed')->pluck('registrations.id')->all())->toBe([$failed->id])
        ->and(TicketWhatsappBroadcast::bulkQuery($event->id, 'not_sent')->pluck('registrations.id')->all())->toBe([$notSent->id])
        ->and(TicketWhatsappBroadcast::bulkQuery($event->id, 'all')->pluck('registrations.id')->all())
        ->not->toContain($queued->id);
});

test('bulk send queues messages in chunks and finishes', function () {
    Queue::fake();
    $event = waEvent();
    foreach (range(1, 30) as $i) {
        waRegistration($event);
    }

    $admin = waUser('admin');
    $result = TicketWhatsappBroadcast::start($event, 'not_sent', $admin);

    expect($result)->toMatchArray(['ok' => true, 'total' => 30]);
    Queue::assertPushed(BulkSendTicketWhatsappJob::class, 1);

    $run = TicketWhatsappBroadcast::progress($event->id);

    (new BulkSendTicketWhatsappJob($event->id, $run['run_id'], 'not_sent'))
        ->handle(app(\App\Services\WhacenterService::class));
    Queue::assertPushed(BulkSendTicketWhatsappJob::class, 2);

    (new BulkSendTicketWhatsappJob($event->id, $run['run_id'], 'not_sent', afterId: Registration::query()->orderBy('id')->skip(24)->value('id')))
        ->handle(app(\App\Services\WhacenterService::class));

    $progress = TicketWhatsappBroadcast::progress($event->id);

    expect($progress['status'])->toBe(TicketWhatsappBroadcast::RUN_DONE)
        ->and($progress['queued'])->toBe(30)
        ->and(WhatsappNotificationLog::query()->where('status', 'queued')->count())->toBe(30);
    Queue::assertPushed(SendWhacenterMessageJob::class, 30);
});

test('bulk send refuses a second run while one is active and when device is down', function () {
    Queue::fake();
    $event = waEvent();
    waRegistration($event);
    $admin = waUser('admin');

    waDevice(false);
    expect(TicketWhatsappBroadcast::start($event, 'not_sent', $admin)['ok'])->toBeFalse();
    Queue::assertNothingPushed();

    waDevice(true);
    expect(TicketWhatsappBroadcast::start($event, 'not_sent', $admin)['ok'])->toBeTrue()
        ->and(TicketWhatsappBroadcast::start($event, 'not_sent', $admin)['ok'])->toBeFalse();
    Queue::assertPushed(BulkSendTicketWhatsappJob::class, 1);
});

test('cancelled run stops queueing further messages', function () {
    Queue::fake();
    $event = waEvent();
    waRegistration($event);

    TicketWhatsappBroadcast::start($event, 'not_sent', waUser('admin'));
    $run = TicketWhatsappBroadcast::progress($event->id);

    expect(TicketWhatsappBroadcast::requestCancel($event->id))->toBeTrue();

    (new BulkSendTicketWhatsappJob($event->id, $run['run_id'], 'not_sent'))
        ->handle(app(\App\Services\WhacenterService::class));

    expect(TicketWhatsappBroadcast::progress($event->id)['status'])->toBe(TicketWhatsappBroadcast::RUN_CANCELLED)
        ->and(WhatsappNotificationLog::query()->count())->toBe(0);
});

test('resend of a single participant is permission and ownership guarded', function () {
    Queue::fake();
    $organizerUser = waUser('organizer');
    $event = waEvent(waOrganizer('Mine', $organizerUser));
    $registration = waRegistration($event);
    waLog($registration, 'failed');

    Livewire::actingAs($organizerUser)
        ->test(TicketNotificationCenter::class, ['eventId' => $event->id])
        ->call('resendOne', $registration->id);

    expect(WhatsappNotificationLog::query()->where('status', 'queued')->count())->toBe(1);
    Queue::assertPushed(SendWhacenterMessageJob::class, 1);

    $otherEvent = waEvent(waOrganizer('Theirs', User::factory()->create()));
    $foreign = waRegistration($otherEvent);

    Livewire::actingAs($organizerUser)
        ->test(TicketNotificationCenter::class, ['eventId' => $event->id])
        ->call('resendOne', $foreign->id);

    expect(WhatsappNotificationLog::query()->where('registration_id', $foreign->id)->count())->toBe(0);

    $readOnly = waUser('admin');
    $readOnly->getRoleNames();
    \Spatie\Permission\Models\Role::findByName('admin', 'web')->revokePermissionTo('whatsapp_notification.send');
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    Livewire::actingAs($readOnly)
        ->test(TicketNotificationCenter::class, ['eventId' => $event->id])
        ->call('resendOne', $registration->id)
        ->assertForbidden();
});

test('center renders participants, states and bulk controls', function () {
    Queue::fake();
    $event = waEvent();
    $failed = waRegistration($event);
    waLog($failed, 'failed')->update(['failed_reason' => 'HTTP 500']);
    $sent = waRegistration($event);
    waLog($sent, 'sent');
    waRegistration($event, null);

    Livewire::actingAs(waUser('admin'))
        ->test(TicketNotificationCenter::class, ['eventId' => $event->id])
        ->assertSee($failed->rider->name)
        ->assertSee('HTTP 500')
        ->assertSee(__('Resend failed'))
        ->assertSee(__('No WhatsApp number on file.'))
        ->call('setStatusFilter', 'failed')
        ->assertSee($failed->rider->name)
        ->assertDontSee($sent->rider->name)
        ->call('sendBulk', 'failed')
        ->assertSee(__('Running'));
});

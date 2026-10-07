<?php

use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeRegistrationEvent(array $attributes = []): Event
{
    return Event::create(array_merge([
        'title' => 'Test Event',
        'category' => Event::CATEGORY_UMUR,
        'start_at' => now()->addMonth(),
        'status' => Event::STATUS_OPEN_REGIST,
        'payment_methods' => [Event::PAYMENT_MANUAL, Event::PAYMENT_QRIS],
        'registration_opens_at' => now()->subDay(),
        'registration_closes_at' => now()->addDay(),
    ], $attributes));
}

test('events use system registration by default', function () {
    $event = makeRegistrationEvent();

    expect($event->fresh()->usesSystemRegistration())->toBeTrue()
        ->and($event->fresh()->isRegistrationOpen())->toBeTrue()
        ->and($event->fresh()->allowsQrisPayment())->toBeTrue();
});

test('events without system registration never open registration or allow payments', function () {
    $event = makeRegistrationEvent(['uses_system_registration' => false]);

    expect($event->usesSystemRegistration())->toBeFalse()
        ->and($event->isRegistrationOpen())->toBeFalse()
        ->and($event->effective_status)->toBe(Event::STATUS_PUBLISHED)
        ->and($event->eventCardStatus())->toBe(Event::STATUS_PUBLISHED)
        ->and($event->allowsManualPayment())->toBeFalse()
        ->and($event->allowsQrisPayment())->toBeFalse();
});

test('sync status command skips events without system registration', function () {
    $off = makeRegistrationEvent([
        'title' => 'Off',
        'status' => Event::STATUS_PUBLISHED,
        'uses_system_registration' => false,
    ]);
    $on = makeRegistrationEvent([
        'title' => 'On',
        'status' => Event::STATUS_PUBLISHED,
    ]);

    $this->artisan('events:sync-status')->assertSuccessful();

    expect($off->fresh()->status)->toBe(Event::STATUS_PUBLISHED)
        ->and($on->fresh()->status)->toBe(Event::STATUS_OPEN_REGIST);
});

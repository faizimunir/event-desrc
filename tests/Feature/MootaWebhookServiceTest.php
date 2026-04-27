<?php

use App\Models\Bracket;
use App\Models\Event;
use App\Models\Order;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\Rider;
use App\Models\User;
use App\Services\MootaWebhookService;
use Illuminate\Support\Str;

/**
 * Uji fungsional webhook Moota (tanpa HTTP).
 *
 * Uji beban konkuren (dua proses, kunci DB nyata) tidak di-automasi di sini karena
 * PHPUnit memakai SQLite :memory: per proses. Untuk reproduksi deadlock historis di MySQL,
 * pakai dua terminal dengan DB yang sama: satu jalankan transaksi panjang di
 * `orders` lalu `payments`, satu panggil `MootaWebhookService::processMutation` —
 * setelah perbaikan urutan kunci, keduanya harus selesai tanpa error 1213.
 */
test('moota processMutation confirms pending qris payment and marks order paid', function () {
    $user = User::factory()->create();
    $rider = Rider::query()->create([
        'user_id' => $user->id,
        'name' => 'Stress Rider',
        'nickname' => 'SR',
    ]);

    $event = Event::query()->create([
        'title' => 'Stress Event',
        'slug' => 'stress-'.Str::lower(Str::random(10)),
        'start_at' => now()->addDay(),
        'end_at' => now()->addDays(2),
        'status' => Event::STATUS_OPEN_REGIST,
        'payment_methods' => [Event::PAYMENT_QRIS],
    ]);

    $bracket = Bracket::query()->create([
        'event_id' => $event->id,
        'name' => 'Open',
        'quota' => 100,
    ]);

    $package = Package::query()->create([
        'event_id' => $event->id,
        'name' => 'Paket',
        'price' => 100_000,
        'status' => Package::STATUS_ACTIVE,
    ]);

    $registration = Registration::query()->create([
        'event_id' => $event->id,
        'rider_id' => $rider->id,
        'bracket_id' => $bracket->id,
        'package_id' => $package->id,
        'status' => Registration::STATUS_PENDING,
    ]);

    $order = Order::query()->create([
        'registration_id' => $registration->id,
        'status' => Order::STATUS_UNPAID,
        'expired_at' => now()->addHour(),
        'confirmed_at' => now(),
    ]);

    $amount = '100015.00';
    $payment = Payment::query()->create([
        'order_id' => $order->id,
        'registration_id' => $registration->id,
        'amount' => $amount,
        'method' => Payment::METHOD_QRIS,
        'status' => Payment::STATUS_PENDING,
    ]);

    $mutationId = 'mut-test-'.Str::uuid()->toString();

    app(MootaWebhookService::class)->processMutation([
        'mutation_id' => $mutationId,
        'type' => 'CR',
        'amount' => $amount,
        'date' => now()->toIso8601String(),
    ]);

    $payment->refresh();
    $order->refresh();

    expect($payment->status)->toBe(Payment::STATUS_SUCCESS)
        ->and($payment->moota_mutation_id)->toBe($mutationId)
        ->and($order->status)->toBe(Order::STATUS_PAID)
        ->and($order->isPaid())->toBeTrue();
});

test('moota processMutation ignores duplicate mutation_id', function () {
    $user = User::factory()->create();
    $rider = Rider::query()->create([
        'user_id' => $user->id,
        'name' => 'R2',
        'nickname' => 'R2',
    ]);

    $event = Event::query()->create([
        'title' => 'E2',
        'slug' => 'e2-'.Str::lower(Str::random(10)),
        'start_at' => now()->addDay(),
        'end_at' => now()->addDays(2),
        'status' => Event::STATUS_OPEN_REGIST,
        'payment_methods' => [Event::PAYMENT_QRIS],
    ]);

    $bracket = Bracket::query()->create([
        'event_id' => $event->id,
        'name' => 'B2',
        'quota' => 50,
    ]);

    $package = Package::query()->create([
        'event_id' => $event->id,
        'name' => 'P2',
        'price' => 50_000,
        'status' => Package::STATUS_ACTIVE,
    ]);

    $registration = Registration::query()->create([
        'event_id' => $event->id,
        'rider_id' => $rider->id,
        'bracket_id' => $bracket->id,
        'package_id' => $package->id,
        'status' => Registration::STATUS_PENDING,
    ]);

    $order = Order::query()->create([
        'registration_id' => $registration->id,
        'status' => Order::STATUS_UNPAID,
        'expired_at' => now()->addHour(),
        'confirmed_at' => now(),
    ]);

    $amount = '500022.00';
    $payment = Payment::query()->create([
        'order_id' => $order->id,
        'registration_id' => $registration->id,
        'amount' => $amount,
        'method' => Payment::METHOD_QRIS,
        'status' => Payment::STATUS_PENDING,
    ]);

    $mutationId = 'mut-dup-'.Str::uuid()->toString();
    $service = app(MootaWebhookService::class);

    $service->processMutation([
        'mutation_id' => $mutationId,
        'type' => 'CR',
        'amount' => $amount,
        'date' => now()->toIso8601String(),
    ]);

    $paidAtFirst = $payment->fresh()->paid_at;

    $service->processMutation([
        'mutation_id' => $mutationId,
        'type' => 'CR',
        'amount' => $amount,
        'date' => now()->toIso8601String(),
    ]);

    $payment->refresh();

    expect($payment->moota_mutation_id)->toBe($mutationId)
        ->and($payment->paid_at?->timestamp)->toBe($paidAtFirst?->timestamp);
});

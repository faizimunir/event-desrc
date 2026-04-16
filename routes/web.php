<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\LiveResultCategoryController;
use App\Http\Controllers\BracketController;
use App\Http\Controllers\BracketLevelController;
use App\Http\Controllers\EventCodeAccessController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\Events\EventCheckinController;
use App\Http\Controllers\LevelController;
use App\Http\Controllers\LiveResultController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MasterOfCeremonyController;
use App\Http\Controllers\MootaPaymentController;
use App\Http\Controllers\MyRiderController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrganizerController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RacingCommitteeController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\RewardController;
use App\Http\Controllers\RiderController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SwitchRoleController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TrackController;
use App\Http\Controllers\UserController;
use App\Models\Event;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $events = \App\Models\Event::with('location')
        ->visibleOnHomePage()
        ->orderBy('start_at', 'desc')
        ->limit(12)
        ->get();

    return view('home', compact('events'));
})->name('home');

// Public events list (event cards)
Route::get('events-public', function () {
    return view('events.public.index');
})->name('events.public.index');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('switch-role', SwitchRoleController::class)->name('switch-role');
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::get('my-rider', [MyRiderController::class, 'index'])->name('my-rider.index');
    Route::get('my-rider/create', [MyRiderController::class, 'create'])->name('my-rider.create');
    Route::resource('users', UserController::class)->except(['show']);
    Route::resource('events', EventController::class);
    Route::resource('events.packages', PackageController::class)->except(['show', 'store', 'update'])->scoped();
    Route::resource('events.tracks', TrackController::class)->except(['show'])->scoped();
    Route::resource('events.brackets', BracketController::class)->except(['show'])->scoped();
    Route::resource('events.brackets.bracket-levels', BracketLevelController::class)->except(['show'])->scoped();
    Route::get('events/{event}/registrations/create', [RegistrationController::class, 'create'])->name('events.registrations.create');
    Route::get('events/{event}/registrations/export', [RegistrationController::class, 'export'])->name('events.registrations.export');
    Route::post('events/{event}/registrations', [RegistrationController::class, 'storeInternal'])->name('events.registrations.store');
    Route::get('events/{event}/registrations/{registration}', [RegistrationController::class, 'show'])->name('events.registrations.show');
    Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::post('payments/{payment}/approve', [PaymentController::class, 'approve'])->name('payments.approve');
    Route::post('payments/{payment}/reject', [PaymentController::class, 'reject'])->name('payments.reject');
    Route::post('payments/{payment}/expire', [PaymentController::class, 'expire'])->name('payments.expire');
    Route::get('events/{event}/code-access', [EventCodeAccessController::class, 'index'])->name('events.code-access.index');
    Route::post('events/{event}/code-access', [EventCodeAccessController::class, 'store'])->name('events.code-access.store');
    Route::delete('events/{event}/code-access/{codeAccess}', [EventCodeAccessController::class, 'destroy'])->name('events.code-access.destroy');
    Route::post('events/{event}/checkins', [EventCheckinController::class, 'store'])->name('events.checkins.store');
    Route::put('events/{event}/checkins/{checkin}', [EventCheckinController::class, 'update'])->name('events.checkins.update');
    Route::delete('events/{event}/checkins/{checkin}', [EventCheckinController::class, 'destroy'])->name('events.checkins.destroy');
    Route::get('events/{event}/live-result-categories', [LiveResultCategoryController::class, 'index'])->name('events.live-result-categories.index');
    Route::post('events/{event}/live-result-categories', [LiveResultCategoryController::class, 'store'])->name('events.live-result-categories.store');
    Route::post('events/{event}/live-result-categories/fetch-sheets', [LiveResultCategoryController::class, 'fetchSheets'])->name('events.live-result-categories.fetch-sheets');
    Route::put('events/{event}/live-result-categories/{liveResultCategory}', [LiveResultCategoryController::class, 'update'])->name('events.live-result-categories.update');
    Route::delete('events/{event}/live-result-categories/{liveResultCategory}', [LiveResultCategoryController::class, 'destroy'])->name('events.live-result-categories.destroy');
    Route::get('events/{event}/live-result-categories/{liveResultCategory}/print', [LiveResultCategoryController::class, 'printPreview'])->name('events.live-result-categories.print');
    Route::post('events/{event}/live-result-categories/{liveResultCategory}/sync', [LiveResultCategoryController::class, 'syncCategory'])->name('events.live-result-categories.sync');
    Route::post('events/{event}/live-result-categories-sync-all', [LiveResultCategoryController::class, 'syncAll'])->name('events.live-result-categories.sync-all');
    Route::post('events/{event}/live-result-flag', [EventController::class, 'updateLiveResultFlag'])->name('events.live-result.flag');
    Route::get('print-center', [LiveResultCategoryController::class, 'printCenter'])->name('print-center.index');
    Route::get('print-center/preview', [LiveResultCategoryController::class, 'printCenterPreview'])->name('print-center.preview');
    Route::get('print-center/export', [LiveResultCategoryController::class, 'printCenterExport'])->name('print-center.export');
    Route::post('registrations/{registration}/status', [RegistrationController::class, 'updateStatus'])->name('registrations.update-status');
    Route::post('registrations/{registration}/approve-all', [RegistrationController::class, 'approveAll'])->name('registrations.approve-all');
    Route::post('events/{event}/registrations/{registration}/reset-payment-deadline', [RegistrationController::class, 'resetPaymentDeadline'])->name('events.registrations.reset-payment-deadline');
    Route::post('events/{event}/registrations/{registration}/reopen-payment', [RegistrationController::class, 'reopenPayment'])->name('events.registrations.reopen-payment');
    Route::post('events/{event}/registrations/{registration}/generate-payment', [RegistrationController::class, 'generatePayment'])->name('events.registrations.generate-payment');
    Route::post('events/{event}/registrations/{registration}/resend-ticket-whatsapp', [RegistrationController::class, 'resendTicketWhatsapp'])->name('events.registrations.resend-ticket-whatsapp');
    Route::post('events/{event}/registrations/{registration}/rider-user-whatsapp', [RegistrationController::class, 'updateRiderUserWhatsapp'])->name('events.registrations.update-rider-user-whatsapp');
    Route::resource('accounts', AccountController::class)->except(['show']);
    Route::resource('locations', LocationController::class)->except(['show']);
    Route::resource('organizers', OrganizerController::class)->except(['show', 'store', 'update']);
    Route::resource('racing-committees', RacingCommitteeController::class)->except(['show']);
    Route::resource('teams', TeamController::class)->except(['show']);
    Route::resource('riders', RiderController::class)->except(['show']);
    Route::post('riders/{rider}/avatar', [RiderController::class, 'updateAvatar'])->name('riders.avatar');
    Route::resource('rewards', RewardController::class)->except(['show']);
    Route::resource('levels', LevelController::class)->except(['show']);
    Route::resource('master-of-ceremonies', MasterOfCeremonyController::class)->except(['show']);
    Route::resource('roles', RoleController::class)->except(['show']);
    Route::resource('permissions', PermissionController::class)->except(['show']);
});

require __DIR__.'/settings.php';

// Aktivasi akun (untuk user yang sudah daftar rider via WA, belum set email/password)
Route::get('activation', fn () => view('activation'))->name('activation.show');

// Orders (public): pesanan saya — by session (guest) atau user_id (logged-in)
Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
Route::get('orders/{order}/ticket', [TicketController::class, 'showFromOrder'])->name('orders.ticket');

// Tickets (public): e-ticket + QR
Route::get('tickets/verify/{ticket}', [TicketController::class, 'verify'])->name('tickets.verify');
Route::get('tickets/{ticket}/qr', [TicketController::class, 'qr'])->name('tickets.qr');
Route::get('tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');

// Payment (public): form upload bukti transfer manual
Route::get('payment', [PaymentController::class, 'create'])->name('payment.create');
Route::post('payment/verify', [PaymentController::class, 'verify'])->name('payment.verify');
Route::post('payment', [PaymentController::class, 'store'])->name('payment.store');

// Moota: konfirmasi nominal unik + webhook mutasi bank
Route::post('payment/moota/confirm', [MootaPaymentController::class, 'confirm'])->name('payment.moota.confirm');
Route::get('webhooks/moota', fn () => response()->json([
    'message' => 'ok',
    'method' => 'POST',
]));
Route::post('webhooks/moota', [MootaPaymentController::class, 'webhook'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('webhooks.moota');

// Public registration (no auth) — form hanya di halaman event (event-show)
Route::get('{event}/register', fn (Event $event) => redirect()->route('events.public.show', $event->slug))->name('registrations.create');
Route::post('{event}/register', [RegistrationController::class, 'store'])->name('registrations.store');

// Early registration: verify access code (modal on event-show)
Route::post('early-access/{event:slug}', [EventController::class, 'verifyEarlyAccess'])->name('events.early-access.verify');

// Live Result (public) — akses langsung via slug: desrc.id/{slug}
Route::get('live-result', [LiveResultController::class, 'index'])->name('live-result.index');
Route::get('{event:slug}/live-result/ping', [LiveResultController::class, 'ping'])->name('live-result.ping');
Route::get('/{event:slug}', [LiveResultController::class, 'show'])->name('live-result.show');

// Public event by slug: desrc.id/event/{slug} (diletakkan setelah live result agar slug utama dipakai untuk live result)
Route::get('event/{event:slug}', [EventController::class, 'showBySlug'])->name('events.public.show');

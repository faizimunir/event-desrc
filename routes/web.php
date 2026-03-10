<?php

use App\Models\Event;
use App\Http\Controllers\BracketLevelController;
use App\Http\Controllers\BracketController;
use App\Http\Controllers\EventCodeAccessController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\LevelController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MasterOfCeremonyController;
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
use App\Http\Controllers\TrackController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $events = \App\Models\Event::with('location')
        ->visibleOnHomePage()
        ->orderBy('start_at', 'desc')
        ->limit(12)
        ->get();
    return view('home', compact('events'));
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('switch-role', SwitchRoleController::class)->name('switch-role');
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::resource('users', UserController::class)->except(['show']);
    Route::resource('events', EventController::class);
    Route::resource('events.packages', PackageController::class)->except(['show', 'store', 'update'])->scoped();
    Route::resource('events.tracks', TrackController::class)->except(['show'])->scoped();
    Route::resource('events.brackets', BracketController::class)->except(['show'])->scoped();
    Route::resource('events.brackets.bracket-levels', BracketLevelController::class)->except(['show'])->scoped();
    Route::get('events/{event}/registrations', [RegistrationController::class, 'index'])->name('events.registrations.index');
    Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::post('payments/{payment}/approve', [PaymentController::class, 'approve'])->name('payments.approve');
    Route::post('payments/{payment}/reject', [PaymentController::class, 'reject'])->name('payments.reject');
    Route::get('events/{event}/code-access', [EventCodeAccessController::class, 'index'])->name('events.code-access.index');
    Route::post('events/{event}/code-access', [EventCodeAccessController::class, 'store'])->name('events.code-access.store');
    Route::delete('events/{event}/code-access/{codeAccess}', [EventCodeAccessController::class, 'destroy'])->name('events.code-access.destroy');
    Route::post('registrations/{registration}/status', [RegistrationController::class, 'updateStatus'])->name('registrations.update-status');
    Route::resource('locations', LocationController::class)->except(['show']);
    Route::resource('organizers', OrganizerController::class)->except(['show']);
    Route::resource('racing-committees', RacingCommitteeController::class)->except(['show']);
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

// Payment (public): form upload bukti transfer manual
Route::get('payment', [PaymentController::class, 'create'])->name('payment.create');
Route::post('payment/verify', [PaymentController::class, 'verify'])->name('payment.verify');
Route::post('payment', [PaymentController::class, 'store'])->name('payment.store');

// Public registration (no auth) — form hanya di halaman event (event-show)
Route::get('{event}/register', fn (Event $event) => redirect()->route('events.public.show', $event->slug))->name('registrations.create');
Route::post('{event}/register', [RegistrationController::class, 'store'])->name('registrations.store');

// Early registration: verify access code (modal on event-show)
Route::post('early-access/{event:slug}', [EventController::class, 'verifyEarlyAccess'])->name('events.early-access.verify');

// Public event by slug: desrc.id/{slug} (must be last so it doesn't override named routes)
Route::get('/{event:slug}', [EventController::class, 'showBySlug'])->name('events.public.show');

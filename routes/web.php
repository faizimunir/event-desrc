<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\LiveResultCategoryController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\LiveResultController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\ParticipantController;
use App\Http\Controllers\PaymentController;
use App\Http\Middleware\AdminAccess;
use Illuminate\Support\Facades\Route;

// Public Routes
Route::get('/', function () {
    return view('home');
})->name('home');

// Live Result Routes
Route::prefix('result')->name('result.')->group(function () {
    Route::get('/', [LiveResultController::class, 'index'])->name('index');
});

// Public Event Routes
Route::get('/event/{id}', function ($id) {
    return view('event-detail', ['id' => $id]);
})->name('event.detail');

// Registration Routes
Route::get('/registration/{packageId}/{categoryId?}', function ($packageId, $categoryId = null) {
    return view('registration', ['packageId' => $packageId, 'categoryId' => $categoryId]);
})->name('registration.show');

// Payment Routes
Route::get('/payment/{participantId}', function ($participantId) {
    return view('payment', ['participantId' => $participantId]);
})->name('payment.show');

// Public Category Routes
Route::prefix('categories')->name('categories.')->group(function () {
    Route::get('/', [CategoryController::class, 'index'])->name('index');
    Route::get('/{id}', [CategoryController::class, 'show'])->name('show');
});

// Public Package Routes
Route::prefix('packages')->name('packages.')->group(function () {
    Route::get('/', [PackageController::class, 'index'])->name('index');
    Route::get('/{id}', [PackageController::class, 'show'])->name('show');
});

// Public Participant Registration Routes
Route::prefix('participants')->name('participants.')->group(function () {
    Route::post('/', [ParticipantController::class, 'store'])->name('store');
    Route::get('/{registration_number}', [ParticipantController::class, 'show'])->name('show');
});

// Public Payment Routes
Route::prefix('payments')->name('payments.')->group(function () {
    Route::post('/', [PaymentController::class, 'store'])->name('store');
    Route::get('/{id}', [PaymentController::class, 'show'])->name('show');
    Route::post('/{id}/upload-proof', [PaymentController::class, 'uploadProof'])->name('upload-proof');
});

// Live Result Detail Route (must be after all specific routes to avoid conflicts)
// This route will match any slug that doesn't conflict with existing routes
Route::get('/{slug}', [LiveResultController::class, 'show'])
    ->name('result.show')
    ->where('slug', '[a-z0-9-]+');

// Admin Routes
Route::prefix('admin')->name('admin.')->middleware([AdminAccess::class])->group(function () {
    // Admin Dashboard
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('dashboard');

    // Admin Event Management (using Livewire)
    Route::get('/events', function () {
        return view('admin.events.index');
    })->name('events.index');
    Route::resource('events', EventController::class)->except(['index']);

    // Admin Category Management (using Livewire)
    Route::get('/categories', function () {
        return view('admin.categories.index');
    })->name('categories.index');
    Route::resource('categories', CategoryController::class)->except(['index']);

    // Admin Package Management (using Livewire)
    Route::get('/packages', function () {
        return view('admin.packages.index');
    })->name('packages.index');
    Route::resource('packages', PackageController::class)->except(['index']);

    // Admin Participant Management
    Route::get('/participants/{participant}/verify', [ParticipantController::class, 'verify'])->name('participants.verify');
    Route::resource('participants', ParticipantController::class)->except(['show']);

    // Admin Payment Management
    Route::post('/payments/{payment}/verify', [PaymentController::class, 'verify'])->name('payments.verify');
    Route::post('/payments/{payment}/reject', [PaymentController::class, 'reject'])->name('payments.reject');
    Route::resource('payments', PaymentController::class);

    // Admin User Management (Super Admin only)
    Route::resource('admins', AdminController::class)->middleware('admin.role:super_admin');

    // Form Builder
    Route::get('/form-builder', function () {
        return view('admin.form-builder');
    })->name('form-builder.index');
    Route::get('/form-builder/{packageId}', function ($packageId) {
        return view('admin.form-builder', ['packageId' => $packageId]);
    })->name('form-builder.show');

    // Payment Settings
    Route::get('/payment-settings', function () {
        return view('admin.payment-settings');
    })->name('payment-settings.index');

        // Registrations
        Route::get('/registrations', function () {
            return view('admin.registrations');
        })->name('registrations.index');
        
        // Participant Detail (custom route for Livewire component)
        Route::get('/participants/{id}', function ($id) {
            return view('admin.participants.show', ['id' => $id]);
        })->name('participants.show');

    // Payment Proofs
    Route::get('/payment-proofs', function () {
        return view('admin.payment-proofs');
    })->name('payment-proofs.index');

    // Notifications
    Route::get('/notifications', function () {
        return view('admin.notifications');
    })->name('notifications.index');

    // System Management
    Route::get('/system-management', function () {
        return view('admin.system-management');
    })->name('system-management.index');

    // Live Result Categories Management
    Route::prefix('events/{eventId}/live-result-categories')->name('live-result-categories.')->group(function () {
        Route::get('/', [LiveResultCategoryController::class, 'index'])->name('index');
        Route::post('/', [LiveResultCategoryController::class, 'store'])->name('store');
        Route::put('/{id}', [LiveResultCategoryController::class, 'update'])->name('update');
        Route::delete('/{id}', [LiveResultCategoryController::class, 'destroy'])->name('destroy');
        Route::post('/fetch-sheets', [LiveResultCategoryController::class, 'fetchSheets'])->name('fetch-sheets');
        Route::post('/sync-all', [LiveResultCategoryController::class, 'syncAll'])->name('sync-all');
        Route::post('/{id}/sync', [LiveResultCategoryController::class, 'syncCategory'])->name('sync');
        Route::get('/print/{categoryId}', [LiveResultCategoryController::class, 'printPreview'])->name('print');
    });

    // Print Center
    Route::get('/print-center', [LiveResultCategoryController::class, 'printCenter'])->name('print-center');
    Route::get('/print-center/preview', [LiveResultCategoryController::class, 'printCenterPreview'])->name('print-center.preview');
});

// Admin Auth Routes (outside middleware)
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', function () {
        return view('admin.login');
    })->name('login');

    Route::post('/login', [AdminController::class, 'login'])->name('login.post');
    Route::post('/logout', [AdminController::class, 'logout'])->name('logout')->middleware([AdminAccess::class]);
});


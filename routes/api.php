<?php

use App\Http\Controllers\Api\ServerTimeController;
use App\Http\Controllers\MootaPaymentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API routes (prefix: /api)
|--------------------------------------------------------------------------
|
| - POST /api/webhooks/moota — tetap dipakai integrasi yang mengarah ke URL API
|   (sama perilaku dengan POST /webhooks/moota di web.php).
| - GET /api/time — server time untuk drag race timer, dll.
|
*/
Route::post('webhooks/moota', [MootaPaymentController::class, 'webhook'])
    ->name('api.webhooks.moota');

Route::get('/time', [ServerTimeController::class, 'show'])->name('api.time');

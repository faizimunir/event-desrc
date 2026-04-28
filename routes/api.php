<?php

use App\Http\Controllers\MootaPaymentController;
use Illuminate\Support\Facades\Route;

// Moota webhook: /api/webhooks/moota (middleware group `api`, bukan `web`)
Route::get('webhooks/moota', fn () => response()->json([
    'message' => 'ok',
    'method' => 'POST',
]));

Route::post('webhooks/moota', [MootaPaymentController::class, 'webhook'])
    ->name('api.webhooks.moota');

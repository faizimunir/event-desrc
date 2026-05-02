<?php

use App\Http\Controllers\Api\ServerTimeController;
use App\Http\Controllers\MootaWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('webhooks/moota', [MootaWebhookController::class, 'handle']);

Route::get('/time', [ServerTimeController::class, 'show'])->name('api.time');

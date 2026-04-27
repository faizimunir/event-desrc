<?php

use App\Http\Controllers\MootaWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('webhooks/moota', [MootaWebhookController::class, 'handle']);

<?php

use App\Http\Controllers\Api\ServerTimeController;
use Illuminate\Support\Facades\Route;

Route::get('/time', [ServerTimeController::class, 'show'])->name('api.time');

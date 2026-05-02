<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ServerTimeController extends Controller
{
    /**
     * Millisecond-precision server time for client clock skew correction.
     */
    public function show(): JsonResponse
    {
        return response()->json([
            'serverTime' => (int) floor(microtime(true) * 1000),
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\DragRaceTimer\DragRaceTimerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DragRaceTimerController extends Controller
{
    public function __construct(
        protected DragRaceTimerService $timer,
    ) {}

    public function index(): View
    {
        $state = $this->timer->currentState();
        $pusher = config('broadcasting.connections.pusher', []);
        $customHost = trim((string) env('PUSHER_HOST', ''));

        return view('drag-race-timer.index', [
            'initialState' => $state,
            'initialHistory' => $this->timer->history(),
            'pusherKey' => $pusher['key'] ?? '',
            'pusherCluster' => $pusher['options']['cluster'] ?? env('PUSHER_APP_CLUSTER', 'mt1'),
            // Kosong = Pusher cloud: Echo pakai cluster saja (jangan set wsHost ke api-*.pusher.com).
            'pusherHost' => $customHost !== '' ? $customHost : '',
            'pusherPort' => (int) ($pusher['options']['port'] ?? 443),
            'pusherScheme' => $pusher['options']['scheme'] ?? 'https',
        ]);
    }

    public function state(): JsonResponse
    {
        return response()->json($this->timer->stateWithHistory());
    }

    public function start(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'countdown' => ['sometimes', 'boolean'],
        ]);

        $this->timer->start((bool) ($validated['countdown'] ?? false));

        return response()->json($this->timer->stateWithHistory());
    }

    public function stopA(): JsonResponse
    {
        $this->timer->stopLane('a');

        return response()->json($this->timer->stateWithHistory());
    }

    public function stopB(): JsonResponse
    {
        $this->timer->stopLane('b');

        return response()->json($this->timer->stateWithHistory());
    }

    public function reset(): JsonResponse
    {
        $this->timer->reset();

        return response()->json($this->timer->stateWithHistory());
    }

    public function clearHistory(): JsonResponse
    {
        $this->timer->clearHistory();

        return response()->json($this->timer->stateWithHistory());
    }
}

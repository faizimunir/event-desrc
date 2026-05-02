<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDragRaceTimerAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->canAs('access_drag_race_timer'), 403);

        return $next($request);
    }
}

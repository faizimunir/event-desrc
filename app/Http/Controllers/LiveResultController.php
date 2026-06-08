<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\LiveResultCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class LiveResultController extends Controller
{
    public function index()
    {
        $events = Event::with('location')
            ->visibleOnHomePage()
            ->where('has_live_result', true)
            ->orderBy('start_at', 'desc')
            ->get();

        return view('live-result.index', compact('events'));
    }

    public function show(Request $request, Event $event)
    {
        if ($event->isDraft() || ! $event->has_live_result) {
            abort(404);
        }

        $event->load('location');

        return view('live-result.show', compact('event'));
    }

    public function ping(Request $request, Event $event): JsonResponse
    {
        if ($event->isDraft() || ! $event->has_live_result) {
            abort(404);
        }

        $cacheKey = "live_result:event:{$event->id}:version";
        $version = Cache::get($cacheKey);

        if (! $version) {
            $lastSync = LiveResultCategory::where('event_id', $event->id)
                ->where('is_active', true)
                ->max('last_sync');
            $version = $lastSync ? (string) $lastSync : (string) $event->updated_at;
            Cache::put($cacheKey, $version, now()->addDays(30));
        }

        $etag = '"'.sha1((string) $version).'"';
        $ifNoneMatch = $request->header('If-None-Match');
        if ($ifNoneMatch && trim($ifNoneMatch) === $etag) {
            return response()
                ->json(null, 304)
                ->header('ETag', $etag)
                ->header('Cache-Control', 'no-cache, must-revalidate');
        }

        return response()
            ->json(['version' => (string) $version])
            ->header('ETag', $etag)
            ->header('Cache-Control', 'no-cache, must-revalidate');
    }
}

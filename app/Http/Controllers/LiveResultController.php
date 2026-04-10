<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\LiveResultCategory;
use App\Services\GoogleSheetsService;
use App\Services\LiveResultSheetParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class LiveResultController extends Controller
{
    public function __construct(
        protected GoogleSheetsService $googleSheetsService
    ) {}

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

        $categories = LiveResultCategory::where('event_id', $event->id)
            ->where('is_active', true)
            ->whereNotNull('selected_sheets')
            ->whereJsonLength('selected_sheets', '>', 0)
            ->orderByRaw('LOWER(title) ASC')
            ->get();

        $selectedCategoryId = $request->get('category');
        $selectedRound = $request->get('round');
        if ($selectedRound !== null) {
            $selectedRound = urldecode($selectedRound);
        }

        $selectedCategory = null;
        $sheetData = null;

        if ($selectedCategoryId) {
            $selectedCategory = $categories->find($selectedCategoryId);
            if ($selectedCategory && $selectedRound !== null && $selectedRound !== '') {
                $result = $this->googleSheetsService->getSheetData(
                    $selectedCategory->spreadsheet_id,
                    $selectedRound
                );
                if ($result['success'] && ! empty($result['values'])) {
                    $rawData = $result['values'];
                    $b1Range = $selectedRound.'!B1';
                    if (preg_match('/[^a-zA-Z0-9_]/', $selectedRound)) {
                        $escaped = str_replace("'", "''", $selectedRound);
                        $b1Range = "'".$escaped."'!B1";
                    }
                    $b1Result = $this->googleSheetsService->getSheetData(
                        $selectedCategory->spreadsheet_id,
                        $selectedRound,
                        $b1Range,
                        false
                    );
                    $b1Value = '';
                    if ($b1Result['success'] && isset($b1Result['values'][0][0])) {
                        $b1Value = trim((string) $b1Result['values'][0][0]);
                    }
                    $sheetData = LiveResultSheetParser::parse($rawData, $selectedRound, $b1Value);
                }
            }
        }

        return view('live-result.show', compact('event', 'categories', 'selectedCategory', 'selectedRound', 'sheetData'));
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

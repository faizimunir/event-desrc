<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\LiveResultCategory;
use App\Services\GoogleSheetsService;
use App\Services\LiveResultSheetParser;
use Illuminate\Http\Request;

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
}

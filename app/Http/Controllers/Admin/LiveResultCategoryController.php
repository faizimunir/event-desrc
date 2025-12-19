<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\LiveResultCategory;
use App\Services\GoogleSheetsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class LiveResultCategoryController extends Controller
{
    protected $googleSheetsService;

    public function __construct(GoogleSheetsService $googleSheetsService)
    {
        $this->googleSheetsService = $googleSheetsService;
    }

    /**
     * Display a listing of live result categories for an event
     */
    public function index($eventId)
    {
        $event = Event::findOrFail($eventId);
        $categories = LiveResultCategory::where('event_id', $eventId)
            ->orderBy('order')
            ->orderBy('title')
            ->get();

        return view('admin.live-result-categories.index', compact('event', 'categories'));
    }

    /**
     * Fetch sheets from Google Sheets API (AJAX)
     */
    public function fetchSheets(Request $request)
    {
        $request->validate([
            'spreadsheet_id' => 'required|string',
        ]);

        $metadata = $this->googleSheetsService->getSpreadsheetMetadata($request->spreadsheet_id, false);

        if (!$metadata['success']) {
            return response()->json([
                'success' => false,
                'error' => $metadata['error'] ?? 'Unknown error',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'sheets' => $metadata['sheets'],
            'spreadsheet_title' => $metadata['title'] ?? '',
        ]);
    }

    /**
     * Store a newly created category
     */
    public function store(Request $request, $eventId)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'spreadsheet_id' => 'required|string|max:255',
            'selected_sheets' => 'nullable|array',
            'selected_sheets.*' => 'string',
        ]);

        $event = Event::findOrFail($eventId);

        $category = LiveResultCategory::create([
            'event_id' => $eventId,
            'title' => $validated['title'],
            'spreadsheet_id' => $validated['spreadsheet_id'],
            'selected_sheets' => $validated['selected_sheets'] ?? [],
            'order' => LiveResultCategory::where('event_id', $eventId)->max('order') + 1,
        ]);

        return redirect()
            ->route('admin.live-result-categories.index', $eventId)
            ->with('success', 'Kategori berhasil ditambahkan.');
    }

    /**
     * Update the specified category
     */
    public function update(Request $request, $eventId, $id)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'spreadsheet_id' => 'required|string|max:255',
            'selected_sheets' => 'nullable|array',
            'selected_sheets.*' => 'string',
            'is_active' => 'boolean',
        ]);

        $category = LiveResultCategory::where('event_id', $eventId)->findOrFail($id);
        $category->update($validated);

        return redirect()
            ->route('admin.live-result-categories.index', $eventId)
            ->with('success', 'Kategori berhasil diperbarui.');
    }

    /**
     * Remove the specified category
     */
    public function destroy($eventId, $id)
    {
        $category = LiveResultCategory::where('event_id', $eventId)->findOrFail($id);
        $category->delete();

        return redirect()
            ->route('admin.live-result-categories.index', $eventId)
            ->with('success', 'Kategori berhasil dihapus.');
    }

    /**
     * Sync data for a specific category
     */
    public function syncCategory(Request $request, $eventId, $id)
    {
        $category = LiveResultCategory::where('event_id', $eventId)->findOrFail($id);

        if (empty($category->selected_sheets) || count($category->selected_sheets) === 0) {
            return redirect()
                ->route('admin.live-result-categories.index', $eventId)
                ->with('error', 'Tidak ada sheet yang dipilih. Silakan pilih sheet terlebih dahulu.');
        }

        // Clear cache for this category
        $this->googleSheetsService->clearAllCache($category->spreadsheet_id);

        // Fetch fresh data for all selected sheets
        $synced = 0;
        foreach ($category->selected_sheets as $sheetName) {
            $result = $this->googleSheetsService->getSheetData(
                $category->spreadsheet_id,
                $sheetName,
                null,
                false // Don't use cache, force fresh fetch
            );
            if ($result['success']) {
                $synced++;
            }
        }

        $category->update([
            'last_sync' => now(),
        ]);

        return redirect()
            ->route('admin.live-result-categories.index', $eventId)
            ->with('success', "Sync berhasil! Data dari {$synced} sheet telah diperbarui.");
    }

    /**
     * Sync all categories for an event
     */
    public function syncAll(Request $request, $eventId)
    {
        $categories = LiveResultCategory::where('event_id', $eventId)
            ->where('is_active', true)
            ->get();

        $totalSynced = 0;
        $totalSheets = 0;

        foreach ($categories as $category) {
            if (empty($category->selected_sheets) || count($category->selected_sheets) === 0) {
                continue;
            }

            // Clear cache
            $this->googleSheetsService->clearAllCache($category->spreadsheet_id);

            // Fetch fresh data
            foreach ($category->selected_sheets as $sheetName) {
                $result = $this->googleSheetsService->getSheetData(
                    $category->spreadsheet_id,
                    $sheetName,
                    null,
                    false
                );
                if ($result['success']) {
                    $totalSheets++;
                }
            }

            $category->update([
                'last_sync' => now(),
            ]);

            $totalSynced++;
        }

        return redirect()
            ->route('admin.live-result-categories.index', $eventId)
            ->with('success', "Sync All berhasil! {$totalSynced} kategori dan {$totalSheets} sheet telah diperbarui.");
    }

    /**
     * Print preview for live result category
     */
    public function printPreview($eventId, $categoryId, Request $request)
    {
        $event = Event::findOrFail($eventId);
        $category = LiveResultCategory::where('event_id', $eventId)->findOrFail($categoryId);
        
        $selectedRound = $request->get('round');
        
        if (!$selectedRound) {
            return redirect()
                ->route('admin.live-result-categories.index', $eventId)
                ->with('error', 'Silakan pilih round terlebih dahulu.');
        }
        
        // Fetch data from Google Sheets (similar to LiveResultController)
        $result = $this->googleSheetsService->getSheetData(
            $category->spreadsheet_id,
            $selectedRound
        );
        
        $sheetData = null;
        
        if ($result['success']) {
            $rawData = $result['values'];
            
            // Fetch B1 for keterangan
            $b1Range = $selectedRound . '!B1';
            if (preg_match('/[^a-zA-Z0-9_]/', $selectedRound)) {
                $escapedSheetName = str_replace("'", "''", $selectedRound);
                $b1Range = "'" . $escapedSheetName . "'!B1";
            }
            
            $b1Result = $this->googleSheetsService->getSheetData(
                $category->spreadsheet_id,
                $selectedRound,
                $b1Range,
                false
            );
            
            $b1Value = '';
            if ($b1Result['success'] && !empty($b1Result['values']) && isset($b1Result['values'][0][0])) {
                $b1Value = trim($b1Result['values'][0][0]);
            }
            
            // Parse data using LiveResultController's parse method
            $liveResultController = new \App\Http\Controllers\LiveResultController($this->googleSheetsService);
            $parsedData = $this->parseSheetDataForPrint($rawData, $category->spreadsheet_id, $selectedRound, $b1Value);
            $sheetData = $parsedData;
        }
        
        return view('admin.live-result-categories.print', compact('event', 'category', 'selectedRound', 'sheetData'));
    }

    /**
     * Parse sheet data for print (similar to LiveResultController but optimized)
     */
    protected function parseSheetDataForPrint(array $rawData, string $spreadsheetId, string $sheetName, string $b1Value = ''): array
    {
        // Create LiveResultController instance to reuse parsing logic
        $liveResultController = app(\App\Http\Controllers\LiveResultController::class);
        
        // Use reflection to call protected method
        $reflection = new \ReflectionClass($liveResultController);
        $method = $reflection->getMethod('parseSheetData');
        $method->setAccessible(true);
        
        return $method->invoke($liveResultController, $rawData, $spreadsheetId, $sheetName, $b1Value);
    }

    /**
     * Print Center - Dashboard untuk memilih kategori dan round
     */
    public function printCenter()
    {
        $admin = Auth::guard('admin')->user();
        
        // Get events based on admin role
        if ($admin->isSuperAdmin()) {
            $events = Event::with(['liveResultCategories' => function($query) {
                $query->where('is_active', true)
                      ->whereNotNull('selected_sheets')
                      ->whereJsonLength('selected_sheets', '>', 0)
                      ->orderBy('order')
                      ->orderBy('title');
            }])->where('status', 'published')->get();
        } else {
            $events = Event::with(['liveResultCategories' => function($query) {
                $query->where('is_active', true)
                      ->whereNotNull('selected_sheets')
                      ->whereJsonLength('selected_sheets', '>', 0)
                      ->orderBy('order')
                      ->orderBy('title');
            }])->where(function($query) use ($admin) {
                $query->where('created_by', $admin->id)
                      ->orWhere('id', $admin->event_id);
            })->where('status', 'published')->get();
        }

        return view('admin.print-center.index', compact('events'));
    }

    /**
     * Print Center Preview - Halaman preview print
     */
    public function printCenterPreview(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:live_result_categories,id',
            'round' => 'required|string',
        ]);

        $category = LiveResultCategory::with('event')->findOrFail($request->category_id);
        $event = $category->event;
        $selectedRound = $request->round;

        // Verify round exists in selected_sheets
        if (!in_array($selectedRound, $category->selected_sheets ?? [])) {
            return redirect()
                ->route('admin.print-center')
                ->with('error', 'Round yang dipilih tidak valid untuk kategori ini.');
        }

        // Fetch data from Google Sheets
        $result = $this->googleSheetsService->getSheetData(
            $category->spreadsheet_id,
            $selectedRound
        );
        
        $sheetData = null;
        
        if ($result['success']) {
            $rawData = $result['values'];
            
            // Fetch B1 for keterangan
            $b1Range = $selectedRound . '!B1';
            if (preg_match('/[^a-zA-Z0-9_]/', $selectedRound)) {
                $escapedSheetName = str_replace("'", "''", $selectedRound);
                $b1Range = "'" . $escapedSheetName . "'!B1";
            }
            
            $b1Result = $this->googleSheetsService->getSheetData(
                $category->spreadsheet_id,
                $selectedRound,
                $b1Range,
                false
            );
            
            $b1Value = '';
            if ($b1Result['success'] && !empty($b1Result['values']) && isset($b1Result['values'][0][0])) {
                $b1Value = trim($b1Result['values'][0][0]);
            }
            
            // Parse data
            $parsedData = $this->parseSheetDataForPrint($rawData, $category->spreadsheet_id, $selectedRound, $b1Value);
            $sheetData = $parsedData;
        } else {
            return redirect()
                ->route('admin.print-center')
                ->with('error', 'Gagal mengambil data dari Google Sheets. Silakan coba lagi.');
        }
        
        return view('admin.print-center.preview', compact('event', 'category', 'selectedRound', 'sheetData'));
    }
}

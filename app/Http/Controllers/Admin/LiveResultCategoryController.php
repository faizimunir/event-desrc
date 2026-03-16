<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\LiveResultCategory;
use App\Services\GoogleSheetsService;
use App\Services\LiveResultSheetParser;
use Illuminate\Http\Request;

class LiveResultCategoryController extends Controller
{
    public function __construct(
        protected GoogleSheetsService $googleSheetsService
    ) {}

    /**
     * Standalone page for Kelola Live Result (same content as tab panel).
     */
    public function index(Event $event)
    {
        abort_unless(auth()->user()->canAs('manage_live_results'), 403);
        $this->authorize('view', $event);

        $this->reorderCategories($event);

        $categories = LiveResultCategory::where('event_id', $event->id)
            ->orderBy('order')
            ->orderByRaw('LOWER(title) ASC')
            ->get();

        return view('admin.live-result-categories.index', compact('event', 'categories'));
    }

    public function fetchSheets(Request $request, Event $event)
    {
        abort_unless(auth()->user()->canAs('manage_live_results'), 403);

        $request->validate([
            'spreadsheet_id' => 'required|string',
        ]);

        $metadata = $this->googleSheetsService->getSpreadsheetMetadata($request->spreadsheet_id, false);

        if (! $metadata['success']) {
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

    public function store(Request $request, Event $event)
    {
        abort_unless(auth()->user()->canAs('manage_live_results'), 403);
        $this->authorize('update', $event);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'spreadsheet_id' => 'required|string|max:255',
            'selected_sheets' => 'nullable|array',
            'selected_sheets.*' => 'string',
        ]);

        LiveResultCategory::create([
            'event_id' => $event->id,
            'title' => $validated['title'],
            'spreadsheet_id' => $validated['spreadsheet_id'],
            'selected_sheets' => $validated['selected_sheets'] ?? [],
            'order' => 0,
        ]);

        $this->reorderCategories($event);

        return redirect()
            ->route('events.show', ['event' => $event, 'tab' => 'live-result'])
            ->with('status', __('Kategori berhasil ditambahkan.'));
    }

    public function update(Request $request, Event $event, LiveResultCategory $liveResultCategory)
    {
        abort_unless(auth()->user()->canAs('manage_live_results'), 403);
        $this->authorize('update', $event);

        if ($liveResultCategory->event_id !== $event->id) {
            abort(404);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'spreadsheet_id' => 'required|string|max:255',
            'selected_sheets' => 'nullable|array',
            'selected_sheets.*' => 'string',
            'is_active' => 'boolean',
        ]);

        $liveResultCategory->update($validated);
        $this->reorderCategories($event);

        return redirect()
            ->route('events.show', ['event' => $event, 'tab' => 'live-result'])
            ->with('status', __('Kategori berhasil diperbarui.'));
    }

    public function destroy(Event $event, LiveResultCategory $liveResultCategory)
    {
        abort_unless(auth()->user()->canAs('manage_live_results'), 403);
        $this->authorize('update', $event);

        if ($liveResultCategory->event_id !== $event->id) {
            abort(404);
        }

        $liveResultCategory->delete();
        $this->reorderCategories($event);

        return redirect()
            ->route('events.show', ['event' => $event, 'tab' => 'live-result'])
            ->with('status', __('Kategori berhasil dihapus.'));
    }

    public function syncCategory(Event $event, LiveResultCategory $liveResultCategory)
    {
        abort_unless(auth()->user()->canAs('manage_live_results'), 403);
        $this->authorize('update', $event);

        if ($liveResultCategory->event_id !== $event->id) {
            abort(404);
        }

        if (empty($liveResultCategory->selected_sheets) || count($liveResultCategory->selected_sheets) === 0) {
            return redirect()
                ->route('events.show', ['event' => $event, 'tab' => 'live-result'])
                ->with('error', __('Tidak ada sheet yang dipilih. Silakan pilih sheet terlebih dahulu.'));
        }

        $this->googleSheetsService->clearAllCache($liveResultCategory->spreadsheet_id);

        foreach ($liveResultCategory->selected_sheets as $sheetName) {
            $this->googleSheetsService->getSheetData(
                $liveResultCategory->spreadsheet_id,
                $sheetName,
                null,
                false
            );
        }

        $liveResultCategory->update(['last_sync' => now()]);

        return redirect()
            ->route('events.show', ['event' => $event, 'tab' => 'live-result'])
            ->with('status', __('Sync berhasil. Data telah diperbarui.'));
    }

    public function syncAll(Event $event)
    {
        abort_unless(auth()->user()->canAs('manage_live_results'), 403);
        $this->authorize('update', $event);

        $categories = LiveResultCategory::where('event_id', $event->id)
            ->where('is_active', true)
            ->get();

        $totalSheets = 0;
        foreach ($categories as $category) {
            if (empty($category->selected_sheets)) {
                continue;
            }
            $this->googleSheetsService->clearAllCache($category->spreadsheet_id);
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
            $category->update(['last_sync' => now()]);
        }

        return redirect()
            ->route('events.show', ['event' => $event, 'tab' => 'live-result'])
            ->with('status', __('Sync All berhasil. :count sheet telah diperbarui.', ['count' => $totalSheets]));
    }

    /**
     * Print preview for one category + round (opens in new tab).
     */
    public function printPreview(Request $request, Event $event, LiveResultCategory $liveResultCategory)
    {
        abort_unless(auth()->user()->canAs('manage_live_results'), 403);
        $this->authorize('view', $event);
        if ($liveResultCategory->event_id !== $event->id) {
            abort(404);
        }

        $selectedRound = $request->get('round');
        if (! $selectedRound) {
            return redirect()
                ->route('events.show', ['event' => $event, 'tab' => 'live-result'])
                ->with('error', __('Silakan pilih round terlebih dahulu.'));
        }

        $result = $this->googleSheetsService->getSheetData(
            $liveResultCategory->spreadsheet_id,
            $selectedRound
        );
        if (! $result['success']) {
            return redirect()
                ->route('events.show', ['event' => $event, 'tab' => 'live-result'])
                ->with('error', __('Gagal mengambil data dari Google Sheets. Silakan coba lagi.'));
        }

        $rawData = $result['values'];
        $b1Range = $selectedRound.'!B1';
        if (preg_match('/[^a-zA-Z0-9_]/', $selectedRound)) {
            $escaped = str_replace("'", "''", $selectedRound);
            $b1Range = "'".$escaped."'!B1";
        }
        $b1Result = $this->googleSheetsService->getSheetData(
            $liveResultCategory->spreadsheet_id,
            $selectedRound,
            $b1Range,
            false
        );
        $b1Value = '';
        if ($b1Result['success'] && isset($b1Result['values'][0][0])) {
            $b1Value = trim((string) $b1Result['values'][0][0]);
        }
        $sheetData = LiveResultSheetParser::parse($rawData, $selectedRound, $b1Value);
        $backUrl = route('events.show', ['event' => $event, 'tab' => 'live-result']);

        return view('admin.live-result-categories.print', compact('event', 'liveResultCategory', 'selectedRound', 'sheetData', 'backUrl'));
    }

    /**
     * Print Center - pilih event untuk cetak semua kategori (round final).
     */
    public function printCenter()
    {
        abort_unless(auth()->user()->canAs('access_print_center'), 403);

        $events = Event::with(['liveResultCategories' => function ($query) {
            $query->where('is_active', true)
                ->whereNotNull('selected_sheets')
                ->whereJsonLength('selected_sheets', '>', 0)
                ->orderBy('order')
                ->orderByRaw('LOWER(title) ASC');
        }])->visibleOnHomePage()->orderBy('start_at', 'desc')->get();

        return view('admin.print-center.index', compact('events'));
    }

    /**
     * Print Center Preview - semua kategori event pada round final.
     */
    public function printCenterPreview(Request $request)
    {
        abort_unless(auth()->user()->canAs('access_print_center'), 403);

        $request->validate(['event_id' => 'required|exists:events,id']);

        $event = Event::with('location')->findOrFail($request->event_id);
        $this->authorize('view', $event);

        $categories = LiveResultCategory::where('event_id', $event->id)
            ->where('is_active', true)
            ->whereNotNull('selected_sheets')
            ->whereJsonLength('selected_sheets', '>', 0)
            ->orderBy('order')
            ->orderByRaw('LOWER(title) ASC')
            ->get();

        if ($categories->isEmpty()) {
            return redirect()
                ->route('print-center.index')
                ->with('error', __('Tidak ada kategori tersedia untuk event ini.'));
        }

        $categoriesData = [];
        foreach ($categories as $category) {
            $finalRound = $this->resolveFinalRound($category->selected_sheets ?? []);
            if (! $finalRound) {
                continue;
            }
            $result = $this->googleSheetsService->getSheetData(
                $category->spreadsheet_id,
                $finalRound
            );
            if (! $result['success']) {
                continue;
            }
            $rawData = $result['values'];
            $b1Range = $finalRound.'!B1';
            if (preg_match('/[^a-zA-Z0-9_]/', $finalRound)) {
                $b1Range = "'".str_replace("'", "''", $finalRound)."'!B1";
            }
            $b1Result = $this->googleSheetsService->getSheetData(
                $category->spreadsheet_id,
                $finalRound,
                $b1Range,
                false
            );
            $b1Value = ($b1Result['success'] && isset($b1Result['values'][0][0])) ? trim((string) $b1Result['values'][0][0]) : '';
            $sheetData = LiveResultSheetParser::parse($rawData, $finalRound, $b1Value);
            $categoriesData[] = ['category' => $category, 'round' => $finalRound, 'sheetData' => $sheetData];
        }

        if (empty($categoriesData)) {
            return redirect()
                ->route('print-center.index')
                ->with('error', __('Tidak ada kategori dengan round final untuk event ini.'));
        }

        return view('admin.print-center.preview-all', compact('event', 'categoriesData'));
    }

    protected function resolveFinalRound(array $selectedSheets): ?string
    {
        foreach ($selectedSheets as $sheet) {
            $sheetLower = strtolower(trim($sheet));
            if (stripos($sheetLower, 'final') === false) {
                continue;
            }
            if (preg_match('/\b(semi[\s\-]?final|final[\s\-]?semi)\b/i', $sheetLower)) {
                continue;
            }
            if (preg_match('/\b(quarter[\s\-]?final|final[\s\-]?quarter)\b/i', $sheetLower)) {
                continue;
            }
            $finalPos = stripos($sheetLower, 'final');
            if ($finalPos > 0) {
                $before = trim(substr($sheetLower, 0, $finalPos));
                if (preg_match('/\b(semi|quarter)\s*$/i', $before)) {
                    continue;
                }
            }
            return $sheet;
        }
        return null;
    }

    protected function reorderCategories(Event $event): void
    {
        $categories = LiveResultCategory::where('event_id', $event->id)
            ->orderByRaw('LOWER(title) ASC')
            ->get();

        $order = 1;
        foreach ($categories as $category) {
            if ($category->order != $order) {
                $category->update(['order' => $order]);
            }
            $order++;
        }
    }
}

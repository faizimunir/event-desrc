<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\LiveResultCategory;
use App\Services\GoogleSheetsService;
use App\Services\LiveResultSheetParser;
use App\Services\LiveResultSyncService;
use App\Services\PrintCenterExcelExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class LiveResultCategoryController extends Controller
{
    public function __construct(
        protected GoogleSheetsService $googleSheetsService,
        protected LiveResultSyncService $liveResultSyncService,
        protected PrintCenterExcelExportService $printCenterExcelExportService
    ) {}

    /**
     * GET /events/{event}/live-result-categories redirects to the event tab.
     */
    public function index(Event $event)
    {
        abort_unless(auth()->user()->canAs('manage_live_results'), 403);
        $this->authorize('view', $event);

        return redirect()->route('events.show', [$event, 'tab' => 'live-result']);
    }

    public function create(Event $event)
    {
        abort_unless(auth()->user()->canAs('manage_live_results'), 403);
        $this->authorize('update', $event);

        if (! $event->has_live_result) {
            return redirect()->route('events.show', [$event, 'tab' => 'live-result']);
        }

        return view('events.live-result-categories.create', compact('event'));
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
            'bracket_id' => [
                'nullable',
                'integer',
                Rule::exists('event_brackets', 'id')->where('event_id', $event->id),
                Rule::unique('live_result_categories', 'bracket_id')->where('event_id', $event->id),
            ],
        ]);

        LiveResultCategory::create([
            'event_id' => $event->id,
            'bracket_id' => $validated['bracket_id'] ?? null,
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

    public function edit(Event $event, LiveResultCategory $liveResultCategory)
    {
        abort_unless(auth()->user()->canAs('manage_live_results'), 403);
        $this->authorize('update', $event);

        if ($liveResultCategory->event_id !== $event->id) {
            abort(404);
        }

        return view('events.live-result-categories.edit', [
            'event' => $event,
            'category' => $liveResultCategory,
        ]);
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
            'bracket_id' => [
                'nullable',
                'integer',
                Rule::exists('event_brackets', 'id')->where('event_id', $event->id),
                Rule::unique('live_result_categories', 'bracket_id')
                    ->where('event_id', $event->id)
                    ->ignore($liveResultCategory->id),
            ],
        ]);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['bracket_id'] = $validated['bracket_id'] ?? null;

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

        $result = $this->liveResultSyncService->syncCategory($event, $liveResultCategory);

        return redirect()
            ->route('events.show', ['event' => $event, 'tab' => 'live-result'])
            ->with($result['ok'] ? 'status' : 'error', $result['message']);
    }

    public function syncAll(Event $event)
    {
        abort_unless(auth()->user()->canAs('manage_live_results'), 403);
        $this->authorize('update', $event);

        $result = $this->liveResultSyncService->syncAll($event);

        return redirect()
            ->route('events.show', ['event' => $event, 'tab' => 'live-result'])
            ->with('status', $result['message']);
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
                ->orderedByRundown();
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

        $categoriesData = $this->resolvePrintCenterCategoriesData($event);

        if (empty($categoriesData)) {
            $hasCategory = LiveResultCategory::where('event_id', $event->id)
                ->where('is_active', true)
                ->whereNotNull('selected_sheets')
                ->whereJsonLength('selected_sheets', '>', 0)
                ->exists();

            return redirect()
                ->route('print-center.index')
                ->with(
                    'error',
                    $hasCategory
                        ? __('Tidak ada kategori dengan round final untuk event ini.')
                        : __('Tidak ada kategori tersedia untuk event ini.')
                );
        }

        return view('admin.print-center.preview-all', compact('event', 'categoriesData'));
    }

    /**
     * Export Print Center ke file Excel (.xlsx) — sama data dengan preview (round final per kategori).
     */
    public function printCenterExport(Request $request)
    {
        abort_unless(auth()->user()->canAs('access_print_center'), 403);

        $request->validate(['event_id' => 'required|exists:events,id']);

        $event = Event::with('location')->findOrFail($request->event_id);
        $this->authorize('view', $event);

        $categoriesData = $this->resolvePrintCenterCategoriesData($event);

        if (empty($categoriesData)) {
            $hasCategory = LiveResultCategory::where('event_id', $event->id)
                ->where('is_active', true)
                ->whereNotNull('selected_sheets')
                ->whereJsonLength('selected_sheets', '>', 0)
                ->exists();

            return redirect()
                ->route('print-center.index')
                ->with(
                    'error',
                    $hasCategory
                        ? __('Tidak ada kategori dengan round final untuk event ini.')
                        : __('Tidak ada kategori tersedia untuk event ini.')
                );
        }

        $spreadsheet = $this->printCenterExcelExportService->build($event, $categoriesData);
        $filename = 'print-center-'.Str::slug($event->slug).'-'.now()->format('Y-m-d_His').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @return array<int, array{category: LiveResultCategory, round: string, sheetData: array}>
     */
    protected function resolvePrintCenterCategoriesData(Event $event): array
    {
        $categories = LiveResultCategory::where('event_id', $event->id)
            ->where('is_active', true)
            ->whereNotNull('selected_sheets')
            ->whereJsonLength('selected_sheets', '>', 0)
            ->orderedByRundown()
            ->get();

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

        return $categoriesData;
    }

    /**
     * Sheet untuk Print Center: utamakan tab yang benar-benar bernama "Final",
     * lalu tab lain yang mengandung "final" dengan filter semi/quarter,
     * serta menolak nama seperti "replaycharge final" (bukan final balapan).
     */
    protected function resolveFinalRound(array $selectedSheets): ?string
    {
        foreach ($selectedSheets as $sheet) {
            if (strcasecmp(trim((string) $sheet), 'Final') === 0) {
                return $sheet;
            }
        }

        foreach ($selectedSheets as $sheet) {
            if ($this->sheetQualifiesAsPrintCenterFinal((string) $sheet)) {
                return $sheet;
            }
        }

        return null;
    }

    protected function sheetQualifiesAsPrintCenterFinal(string $sheet): bool
    {
        $sheetLower = strtolower(trim($sheet));
        if (stripos($sheetLower, 'final') === false) {
            return false;
        }
        if (preg_match('/\b(semi[\s\-]?final|final[\s\-]?semi)\b/i', $sheetLower)) {
            return false;
        }
        if (preg_match('/\b(quarter[\s\-]?final|final[\s\-]?quarter)\b/i', $sheetLower)) {
            return false;
        }

        $finalPos = stripos($sheetLower, 'final');
        if ($finalPos > 0) {
            $before = trim(substr($sheetLower, 0, $finalPos));
            if (preg_match('/\b(semi|quarter)\s*$/i', $before)) {
                return false;
            }
            if ($this->isPrintCenterFinalNoisePrefix($before)) {
                return false;
            }
        }

        if (str_contains($sheetLower, 'replaycharge') || str_contains($sheetLower, 'replay charge')) {
            return false;
        }

        return true;
    }

    /**
     * Prefiks sebelum kata "final" yang menandakan sheet bukan final balapan (tooling, replay, charge, dll.).
     */
    protected function isPrintCenterFinalNoisePrefix(string $beforeLower): bool
    {
        if ($beforeLower === '') {
            return false;
        }

        if (preg_match('/^(replaycharge|replay|recharge|re[\s\-]?charge|chargeback|sheet[\s\-]?copy|backup|admin|temp|test|draft)\b/i', $beforeLower)) {
            return true;
        }

        if (str_contains($beforeLower, 'replaycharge')) {
            return true;
        }

        return false;
    }

    protected function reorderCategories(Event $event): void
    {
        LiveResultCategory::syncOrderForEvent($event);
    }
}

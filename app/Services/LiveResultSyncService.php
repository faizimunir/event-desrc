<?php

namespace App\Services;

use App\Models\Event;
use App\Models\LiveResultCategory;
use Illuminate\Support\Facades\Cache;

class LiveResultSyncService
{
    public function __construct(
        protected GoogleSheetsService $googleSheetsService
    ) {}

    /**
     * @return array{ok: bool, message: string}
     */
    public function syncCategory(Event $event, LiveResultCategory $category): array
    {
        if ($category->event_id !== $event->id) {
            abort(404);
        }

        if (empty($category->selected_sheets) || count($category->selected_sheets) === 0) {
            return [
                'ok' => false,
                'message' => __('Tidak ada sheet yang dipilih. Silakan pilih sheet terlebih dahulu.'),
            ];
        }

        $this->googleSheetsService->clearAllCache($category->spreadsheet_id);

        foreach ($category->selected_sheets as $sheetName) {
            $this->googleSheetsService->getSheetData(
                $category->spreadsheet_id,
                $sheetName,
                null,
                false
            );
        }

        $category->update(['last_sync' => now()]);
        $this->bumpEventVersion($event);

        return [
            'ok' => true,
            'message' => __('Sync berhasil. Data telah diperbarui.'),
        ];
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function syncAll(Event $event): array
    {
        $categories = LiveResultCategory::query()
            ->where('event_id', $event->id)
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

        $this->bumpEventVersion($event);

        return [
            'ok' => true,
            'message' => __('Sync All berhasil. :count sheet telah diperbarui.', ['count' => $totalSheets]),
        ];
    }

    private function bumpEventVersion(Event $event): void
    {
        Cache::put(
            "live_result:event:{$event->id}:version",
            (string) now()->toISOString(),
            now()->addDays(30)
        );
    }
}

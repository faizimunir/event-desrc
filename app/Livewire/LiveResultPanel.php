<?php

namespace App\Livewire;

use App\Models\Event;
use App\Models\LiveResultCategory;
use App\Services\GoogleSheetsService;
use App\Services\LiveResultSheetParser;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

class LiveResultPanel extends Component
{
    #[Locked]
    public Event $event;

    #[Url(as: 'category', except: null)]
    public ?int $category = null;

    #[Url(as: 'round', except: null)]
    public ?string $round = null;

    public ?string $knownVersion = null;

    public function mount(Event $event): void
    {
        $this->event = $event;
        $this->knownVersion = $this->resolveVersion();
    }

    public function selectCategory(int $categoryId): void
    {
        $this->category = $categoryId;
        $this->round = null;
    }

    public function selectRound(string $round): void
    {
        $this->round = $round;
    }

    public function checkForUpdates(): void
    {
        $version = $this->resolveVersion();

        if ($this->knownVersion === $version) {
            $this->skipRender();

            return;
        }

        $this->knownVersion = $version;
    }

    public function render(GoogleSheetsService $googleSheetsService): View
    {
        $categories = LiveResultCategory::where('event_id', $this->event->id)
            ->where('is_active', true)
            ->whereNotNull('selected_sheets')
            ->whereJsonLength('selected_sheets', '>', 0)
            ->orderByRaw('LOWER(title) ASC')
            ->get();

        $selectedCategory = null;
        $sheetData = null;

        if ($this->category) {
            $selectedCategory = $categories->find($this->category);

            if ($selectedCategory && $this->round !== null && $this->round !== '') {
                $sheetData = $this->loadSheetData($googleSheetsService, $selectedCategory, $this->round);
            }
        }

        return view('livewire.live-result-panel', [
            'categories' => $categories,
            'selectedCategory' => $selectedCategory,
            'selectedRound' => $this->round,
            'sheetData' => $sheetData,
        ]);
    }

    private function loadSheetData(GoogleSheetsService $googleSheetsService, LiveResultCategory $category, string $round): ?array
    {
        $version = $this->resolveVersion();
        $cacheKey = sprintf(
            'live_result:parsed:%d:%d:%s:%s',
            $this->event->id,
            $category->id,
            md5($round),
            $version,
        );

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $sheetData = $this->fetchAndParseSheetData($googleSheetsService, $category, $round);

        if ($sheetData !== null) {
            Cache::put($cacheKey, $sheetData, now()->addDays(30));
        }

        return $sheetData;
    }

    private function fetchAndParseSheetData(GoogleSheetsService $googleSheetsService, LiveResultCategory $category, string $round): ?array
    {
        $result = $googleSheetsService->getSheetData(
            $category->spreadsheet_id,
            $round
        );

        if (! $result['success'] || empty($result['values'])) {
            return null;
        }

        $b1Result = $googleSheetsService->getSheetData(
            $category->spreadsheet_id,
            $round,
            $this->b1RangeForRound($round),
            false
        );

        $b1Value = '';

        if ($b1Result['success'] && isset($b1Result['values'][0][0])) {
            $b1Value = trim((string) $b1Result['values'][0][0]);
        }

        return LiveResultSheetParser::parse($result['values'], $round, $b1Value);
    }

    private function b1RangeForRound(string $round): string
    {
        if (preg_match('/[^a-zA-Z0-9_]/', $round)) {
            $escaped = str_replace("'", "''", $round);

            return "'".$escaped."'!B1";
        }

        return $round.'!B1';
    }

    private function resolveVersion(): string
    {
        $cacheKey = "live_result:event:{$this->event->id}:version";
        $version = Cache::get($cacheKey);

        if (! $version) {
            $lastSync = LiveResultCategory::where('event_id', $this->event->id)
                ->where('is_active', true)
                ->max('last_sync');
            $version = $lastSync ? (string) $lastSync : (string) $this->event->updated_at;
            Cache::put($cacheKey, $version, now()->addDays(30));
        }

        return (string) $version;
    }
}

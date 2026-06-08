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

        if ($this->knownVersion !== null && $this->knownVersion !== $version) {
            $this->knownVersion = $version;

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
        $result = $googleSheetsService->getSheetData(
            $category->spreadsheet_id,
            $round
        );

        if (! $result['success'] || empty($result['values'])) {
            return null;
        }

        $rawData = $result['values'];
        $b1Range = $round.'!B1';

        if (preg_match('/[^a-zA-Z0-9_]/', $round)) {
            $escaped = str_replace("'", "''", $round);
            $b1Range = "'".$escaped."'!B1";
        }

        $b1Result = $googleSheetsService->getSheetData(
            $category->spreadsheet_id,
            $round,
            $b1Range,
            false
        );

        $b1Value = '';

        if ($b1Result['success'] && isset($b1Result['values'][0][0])) {
            $b1Value = trim((string) $b1Result['values'][0][0]);
        }

        return LiveResultSheetParser::parse($rawData, $round, $b1Value);
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

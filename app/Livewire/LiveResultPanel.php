<?php

namespace App\Livewire;

use App\Models\Event;
use App\Models\LiveResultCategory;
use App\Models\Rundown;
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

    /** Refresh board + sheet data when sync version changes. */
    public function tick(): void
    {
        $version = $this->resolveVersion();

        if ($this->knownVersion !== $version) {
            $this->knownVersion = $version;
        }
    }

    public function render(GoogleSheetsService $googleSheetsService): View
    {
        $now = now();

        $categories = LiveResultCategory::where('event_id', $this->event->id)
            ->where('is_active', true)
            ->whereNotNull('selected_sheets')
            ->whereJsonLength('selected_sheets', '>', 0)
            ->orderedByRundown()
            ->get();

        $categoryGroups = LiveResultCategory::groupByRundown($this->event, $categories);

        $selectedCategory = null;
        $sheetData = null;

        if ($this->category) {
            $selectedCategory = $categories->find($this->category);

            if ($selectedCategory && $this->round !== null && $this->round !== '') {
                $sheetData = $this->loadSheetData($googleSheetsService, $selectedCategory, $this->round);
            }
        }

        $summary = [
            'live' => 0,
            'due' => 0,
            'overdue' => 0,
            'delayed' => 0,
            'ontime' => 0,
            'upcoming' => 0,
        ];

        foreach ($categoryGroups as $group) {
            /** @var Rundown|null $rundown */
            $rundown = $group['rundown'] ?? null;
            if (! $rundown) {
                continue;
            }
            $status = $rundown->monitorStatus($now);
            if (array_key_exists($status, $summary)) {
                $summary[$status]++;
            }
        }

        return view('livewire.live-result-panel', [
            'categories' => $categories,
            'categoryGroups' => $categoryGroups,
            'selectedCategory' => $selectedCategory,
            'selectedRound' => $this->round,
            'sheetData' => $sheetData,
            'now' => $now,
            'clockIso' => $now->toIso8601String(),
            'monitorSummary' => $summary,
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

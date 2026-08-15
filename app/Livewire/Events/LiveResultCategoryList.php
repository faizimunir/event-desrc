<?php

namespace App\Livewire\Events;

use App\Models\Event;
use App\Models\LiveResultCategory;
use App\Models\Rundown;
use App\Services\LiveResultSyncService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class LiveResultCategoryList extends Component
{
    public Event $event;

    public string $search = '';

    public ?int $justSyncedId = null;

    public bool $justSyncedAll = false;

    public function mount(Event $event): void
    {
        abort_unless(auth()->user()->canAs('manage_live_results'), 403);
        $this->event = $event;
    }

    public function syncCategory(int $categoryId): void
    {
        $this->authorizeSync();

        $category = LiveResultCategory::query()
            ->where('event_id', $this->event->id)
            ->whereKey($categoryId)
            ->firstOrFail();

        $this->justSyncedId = null;
        $this->justSyncedAll = false;

        $result = app(LiveResultSyncService::class)->syncCategory($this->event, $category);

        $this->refreshBoard();

        if (! $result['ok']) {
            $this->toast($result['message'], 'danger');

            return;
        }

        $this->justSyncedId = $category->id;
        $this->toast($result['message']);
        $this->js('setTimeout(() => $wire.clearSyncedState(), 700)');
    }

    public function syncAll(): void
    {
        $this->authorizeSync();

        $this->justSyncedId = null;
        $this->justSyncedAll = false;

        $result = app(LiveResultSyncService::class)->syncAll($this->event);

        $this->refreshBoard();

        $this->justSyncedAll = true;
        $this->toast($result['message']);
        $this->js('setTimeout(() => $wire.clearSyncedState(), 700)');
    }

    public function playRundown(int $rundownId): void
    {
        $this->authorizeSync();

        $rundown = $this->findEventRundown($rundownId);
        $rundown->play();

        $this->refreshBoard();
        $this->toast(__('Rundown started.'));
    }

    public function stopRundown(int $rundownId): void
    {
        $this->authorizeSync();

        $rundown = $this->findEventRundown($rundownId);

        if (! $rundown->isPlaying()) {
            $this->toast(__('Rundown is not playing.'), 'danger');

            return;
        }

        $rundown->stop();

        $this->refreshBoard();

        $status = $rundown->fresh(['event', 'brackets'])->timingStatusLabel();
        $this->toast(__('Rundown stopped (:status).', ['status' => $status]));
    }

    /** Keep board in sync with Play/Stop from other tabs/users. */
    public function refreshBoard(): void
    {
        unset($this->categoryGroups, $this->categoryTotal, $this->monitorSummary);
    }

    public function clearSyncedState(): void
    {
        $this->justSyncedId = null;
        $this->justSyncedAll = false;
    }

    public function updatedSearch(): void
    {
        $this->refreshBoard();
    }

    private function findEventRundown(int $rundownId): Rundown
    {
        return Rundown::query()
            ->where('event_id', $this->event->id)
            ->whereKey($rundownId)
            ->firstOrFail();
    }

    private function authorizeSync(): void
    {
        abort_unless(auth()->user()->canAs('manage_live_results'), 403);
        $this->authorize('update', $this->event);
    }

    private function toast(string $message, string $variant = 'success'): void
    {
        $this->dispatch('toast-show',
            duration: 5000,
            slots: ['text' => $message],
            dataset: ['variant' => $variant],
        );
    }

    #[Computed]
    public function categoryTotal(): int
    {
        return $this->categoryGroups->sum(fn (array $group) => $group['categories']->count());
    }

    /**
     * @return array{live: int, due: int, overdue: int, delayed: int, ontime: int, upcoming: int}
     */
    #[Computed]
    public function monitorSummary(): array
    {
        $now = now();
        $summary = [
            'live' => 0,
            'due' => 0,
            'overdue' => 0,
            'delayed' => 0,
            'ontime' => 0,
            'upcoming' => 0,
        ];

        foreach ($this->categoryGroups as $group) {
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

        return $summary;
    }

    /**
     * @return Collection<int, array{key: string, header: ?string, rundown: ?Rundown, categories: Collection<int, LiveResultCategory>}>
     */
    #[Computed]
    public function categoryGroups(): Collection
    {
        $categories = $this->event->liveResultCategories()
            ->with('bracket')
            ->when($this->search !== '', function ($q) {
                $q->where(function ($q) {
                    $q->where('title', 'like', '%'.$this->search.'%')
                        ->orWhere('spreadsheet_id', 'like', '%'.$this->search.'%')
                        ->orWhereHas('bracket', fn ($b) => $b->where('name', 'like', '%'.$this->search.'%'));
                });
            })
            ->orderedByRundown()
            ->get();

        return LiveResultCategory::groupByRundown($this->event, $categories);
    }

    public function render()
    {
        return view('livewire.events.live-result-category-list', [
            'now' => now(),
            'clockIso' => now()->toIso8601String(),
        ]);
    }
}

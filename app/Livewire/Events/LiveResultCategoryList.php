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

        unset($this->categoryGroups, $this->categoryTotal);

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

        unset($this->categoryGroups, $this->categoryTotal);

        $this->justSyncedAll = true;
        $this->toast($result['message']);
        $this->js('setTimeout(() => $wire.clearSyncedState(), 700)');
    }

    public function clearSyncedState(): void
    {
        $this->justSyncedId = null;
        $this->justSyncedAll = false;
    }

    public function updatedSearch(): void
    {
        unset($this->categoryGroups, $this->categoryTotal);
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
     * @return Collection<int, array{key: string, header: ?string, categories: Collection<int, LiveResultCategory>}>
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

        if ($categories->isEmpty()) {
            return collect();
        }

        $rundowns = $this->event->rundowns()->with('brackets')->get();

        /** @var array<int, Rundown> $bracketToRundown */
        $bracketToRundown = [];
        foreach ($rundowns as $rundown) {
            foreach ($rundown->brackets as $bracket) {
                $existing = $bracketToRundown[$bracket->id] ?? null;
                if (! $existing || (string) $rundown->start_time < (string) $existing->start_time) {
                    $bracketToRundown[$bracket->id] = $rundown;
                }
            }
        }

        $groups = collect();
        $assignedIds = [];

        foreach ($rundowns as $rundown) {
            $groupCategories = $categories->filter(function (LiveResultCategory $category) use ($rundown, $bracketToRundown) {
                return $category->bracket_id
                    && isset($bracketToRundown[$category->bracket_id])
                    && $bracketToRundown[$category->bracket_id]->id === $rundown->id;
            })->values();

            if ($groupCategories->isEmpty()) {
                continue;
            }

            $assignedIds = array_merge($assignedIds, $groupCategories->pluck('id')->all());

            $groups->push([
                'key' => 'rundown-'.$rundown->id,
                'header' => $rundown->formattedTimeRange().' '.$rundown->displayLabel(),
                'categories' => $groupCategories,
            ]);
        }

        $ungrouped = $categories->reject(fn (LiveResultCategory $category) => in_array($category->id, $assignedIds, true))->values();

        if ($ungrouped->isNotEmpty()) {
            $groups->push([
                'key' => 'other',
                'header' => $groups->isNotEmpty() ? __('Lainnya') : null,
                'categories' => $ungrouped,
            ]);
        }

        return $groups;
    }

    public function render()
    {
        return view('livewire.events.live-result-category-list');
    }
}

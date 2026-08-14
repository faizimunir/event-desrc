<?php

namespace App\Livewire\Events;

use App\Models\Event;
use App\Models\LiveResultCategory;
use App\Services\LiveResultSyncService;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class LiveResultCategoryList extends Component
{
    use WithPagination;

    public Event $event;

    public string $search = '';

    public ?int $justSyncedId = null;

    public bool $justSyncedAll = false;

    public function mount(Event $event): void
    {
        abort_unless(auth()->user()->canAs('manage_live_results'), 403);
        $this->event = $event;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
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

        unset($this->categories);

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

        unset($this->categories);

        $this->justSyncedAll = true;
        $this->toast($result['message']);
        $this->js('setTimeout(() => $wire.clearSyncedState(), 700)');
    }

    public function clearSyncedState(): void
    {
        $this->justSyncedId = null;
        $this->justSyncedAll = false;
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
    public function categories()
    {
        return $this->event->liveResultCategories()
            ->when($this->search !== '', function ($q) {
                $q->where(function ($q) {
                    $q->where('title', 'like', '%'.$this->search.'%')
                        ->orWhere('spreadsheet_id', 'like', '%'.$this->search.'%');
                });
            })
            ->orderBy('order')
            ->orderByRaw('LOWER(title) ASC')
            ->paginate(15);
    }

    public function render()
    {
        return view('livewire.events.live-result-category-list');
    }
}

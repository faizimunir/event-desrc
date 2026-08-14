<?php

namespace App\Livewire\Events;

use App\Models\Event;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class LiveResultCategoryList extends Component
{
    use WithPagination;

    public Event $event;

    public string $search = '';

    public function mount(Event $event): void
    {
        abort_unless(auth()->user()->canAs('manage_live_results'), 403);
        $this->event = $event;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
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

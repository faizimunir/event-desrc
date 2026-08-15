<?php

namespace App\Livewire\Rundowns;

use App\Models\Event;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class RundownList extends Component
{
    use WithPagination;

    public Event $event;

    public string $search = '';

    public function mount(Event $event): void
    {
        abort_unless(auth()->user()->canAs('rundown.read'), 403);
        $this->event = $event;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function rundowns()
    {
        return $this->event->rundowns()
            ->with('brackets')
            ->when($this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('title', 'like', $term)
                        ->orWhereHas('brackets', fn ($b) => $b->where('name', 'like', $term));
                });
            })
            ->paginate(20);
    }

    public function render()
    {
        return view('livewire.rundowns.rundown-list');
    }
}

<?php

namespace App\Livewire\Events;

use App\Models\Event;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class EventCodeAccessList extends Component
{
    use WithPagination;

    public Event $event;

    public string $search = '';

    public function mount(Event $event): void
    {
        abort_unless(auth()->user()->canAs('event.update'), 403);
        $this->event = $event;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function codes()
    {
        return $this->event->codeAccess()
            ->when($this->search !== '', function ($q) {
                $q->where(function ($q) {
                    $q->where('code', 'like', '%'.$this->search.'%')
                        ->orWhere('name', 'like', '%'.$this->search.'%');
                });
            })
            ->orderByDesc('created_at')
            ->paginate(15);
    }

    public function render()
    {
        return view('livewire.events.event-code-access-list');
    }
}

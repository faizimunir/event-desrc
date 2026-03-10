<?php

namespace App\Livewire\Brackets;

use App\Models\Event;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class BracketList extends Component
{
    use WithPagination;

    public Event $event;

    public string $search = '';

    public function mount(Event $event): void
    {
        abort_unless(auth()->user()->canAs('bracket.read'), 403);
        $this->event = $event;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function brackets()
    {
        return $this->event->brackets()
            ->when($this->search !== '', fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
            ->orderBy('name')
            ->paginate(15);
    }

    public function render()
    {
        return view('livewire.brackets.bracket-list');
    }
}

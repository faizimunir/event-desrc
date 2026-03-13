<?php

namespace App\Livewire\Events;

use App\Models\Event;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class EventList extends Component
{
    use WithPagination;

    public string $search = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->canAs('event.read'), 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function events()
    {
        $user = auth()->user();
        $query = Event::query()->with(['location', 'organizer']);

        if (! $user->hasRole('super_admin') && ! $user->hasRole('admin')) {
            $query->whereHas('organizer', fn ($q) => $q->where('user_id', $user->id));
        }

        return $query
            ->when($this->search !== '', function ($q) {
                $q->where(function ($q) {
                    $q->where('title', 'like', '%'.$this->search.'%')
                        ->orWhere('description', 'like', '%'.$this->search.'%')
                        ->orWhereHas('location', fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'));
                });
            })
            ->orderBy('start_at', 'desc')
            ->paginate(15);
    }

    public function render()
    {
        return view('livewire.events.event-list');
    }
}

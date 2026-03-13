<?php

namespace App\Livewire\Events;

use App\Models\Event;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class EventCheckinList extends Component
{
    use WithPagination;

    public Event $event;

    public string $search = '';

    public function mount(Event $event): void
    {
        abort_unless(auth()->user()->canAs('checkin.read'), 403);
        $this->event = $event;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function checkins()
    {
        return $this->event->checkins()
            ->with(['registration.rider', 'checkedInByUser'])
            ->when($this->search !== '', function ($q) {
                $q->whereHas('registration.rider', function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('nickname', 'like', '%'.$this->search.'%');
                });
            })
            ->orderByDesc('checked_in_at')
            ->paginate(15);
    }

    /** Registrations that can be checked in (approved, no check-in yet). */
    #[Computed]
    public function registrationsAvailableForCheckin()
    {
        return $this->event->registrations()
            ->where('status', \App\Models\Registration::STATUS_APPROVED)
            ->whereDoesntHave('checkin')
            ->with('rider')
            ->orderBy('number_plate')
            ->get();
    }

    public function render()
    {
        return view('livewire.events.event-checkin-list');
    }
}

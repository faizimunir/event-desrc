<?php

namespace App\Livewire;

use App\Models\Event;
use App\Models\Registration;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class EventParticipantList extends Component
{
    use WithPagination;

    public Event $event;

    #[Url(as: 'participant_search', history: true, keep: false)]
    public string $search = '';

    #[Url(as: 'participant_bracket', history: true, keep: false)]
    public string $bracket = '';

    public function updatedSearch(): void
    {
        $this->resetPage('participant_page');
    }

    public function updatedBracket(): void
    {
        $this->resetPage('participant_page');
    }

    #[Computed]
    public function bracketOptions()
    {
        return $this->event->brackets_sorted_for_display
            ->map(fn ($bracket) => [
                'id' => (string) $bracket->id,
                'name' => (string) $bracket->name,
            ])
            ->values();
    }

    #[Computed]
    public function registrations()
    {
        $query = Registration::query()
            ->with(['rider.teams', 'bracket', 'package'])
            ->where('event_id', $this->event->id)
            ->publiclyListed();

        if ($this->bracket !== '' && ctype_digit($this->bracket)) {
            $query->where('bracket_id', (int) $this->bracket);
        }

        if (trim($this->search) !== '') {
            $query->participantSearch($this->search);
        }

        return $query->latest('id')->paginate(20, ['*'], 'participant_page');
    }

    public function render(): View
    {
        return view('livewire.event-participant-list');
    }
}

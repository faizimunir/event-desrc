<?php

namespace App\Livewire\Events;

use App\Concerns\ShowsToast;
use App\Models\Event;
use App\Models\Registration;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class EventCheckinList extends Component
{
    use ShowsToast;
    use WithPagination;

    public Event $event;

    public string $search = '';

    public string $bracketFilter = '';

    public ?int $editingRegistrationId = null;

    public ?int $selectedRegistrationId = null;

    public bool $checkinModalOpen = false;

    public string $checkinNotes = '';

    public function mount(Event $event): void
    {
        abort_unless(auth()->user()->canAs('checkin.read'), 403);
        $this->event = $event;

        if (request()->filled('edit_registration')) {
            $this->editingRegistrationId = (int) request('edit_registration');
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function setBracketFilter(string $bracketId = ''): void
    {
        if ($bracketId !== '') {
            $allowed = $this->event->brackets()->whereKey((int) $bracketId)->exists();
            $bracketId = $allowed ? (string) (int) $bracketId : '';
        }

        $this->bracketFilter = $bracketId;
        $this->resetPage();
    }

    public function openRegistrationEdit(int $registrationId): void
    {
        abort_unless(auth()->user()->canAs('event.update'), 403);

        $registration = $this->event->registrations()->whereKey($registrationId)->exists();
        abort_unless($registration, 404);

        $this->editingRegistrationId = $registrationId;
        unset($this->editingRegistration);
        $this->js('$dispatch("modal-show", { name: "edit-checkin-registration" })');
    }

    public function openRegistrationCheckin(int $registrationId): void
    {
        abort_unless(auth()->user()->canAs('checkin.read'), 403);

        $exists = $this->event->registrations()
            ->publiclyListed()
            ->whereKey($registrationId)
            ->exists();
        abort_unless($exists, 404);

        $this->selectedRegistrationId = $registrationId;
        $this->checkinNotes = '';
        unset($this->selectedRegistration);
        $this->checkinModalOpen = true;
        $this->resetValidation();
    }

    public function closeCheckinModal(): void
    {
        $this->checkinModalOpen = false;
        $this->selectedRegistrationId = null;
        $this->checkinNotes = '';
        unset($this->selectedRegistration);
        $this->resetValidation();
    }

    public function confirmCheckin(): void
    {
        abort_unless(auth()->user()->canAs('checkin.create'), 403);

        $registration = $this->selectedRegistration;
        if (! $registration) {
            $this->closeCheckinModal();

            return;
        }

        $riderName = $registration->rider?->name ?? __('Rider');

        $eligible = $this->event->registrations()
            ->publiclyListed()
            ->whereKey($registration->id)
            ->exists();

        if (! $eligible) {
            $this->toast(__(':name registration is not approved or paid yet.', ['name' => $riderName]), 'danger');

            return;
        }

        if ($registration->checkin) {
            $this->toast(__(':name is already checked in.', ['name' => $riderName]), 'warning');

            return;
        }

        $this->validate([
            'checkinNotes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->event->checkins()->create([
            'registration_id' => $registration->id,
            'checked_in_at' => now(),
            'checked_in_by' => auth()->id(),
            'notes' => filled($this->checkinNotes) ? $this->checkinNotes : null,
        ]);

        $summary = $registration->checkinSummary();
        $parts = array_filter([
            $summary['name'] ?? null,
            filled($summary['number_plate'] ?? null) ? '#'.$summary['number_plate'] : null,
            $summary['teams'] ?? null,
            $summary['bracket'] ?? null,
        ]);

        $this->toast($parts !== [] ? implode(' · ', $parts) : __('Check-in recorded.'));
        $this->closeCheckinModal();
        $this->search = '';
        unset($this->checkins);
        unset($this->filteredRegistrations);
        unset($this->registrationSearchResults);
        unset($this->checkinStats);
    }

    #[Computed]
    public function editingRegistration(): ?Registration
    {
        if ($this->editingRegistrationId === null) {
            return null;
        }

        return $this->event->registrations()
            ->with(['rider', 'bracket'])
            ->find($this->editingRegistrationId);
    }

    #[Computed]
    public function selectedRegistration(): ?Registration
    {
        if ($this->selectedRegistrationId === null) {
            return null;
        }

        return $this->event->registrations()
            ->with(['rider', 'bracket', 'checkin.checkedInByUser'])
            ->find($this->selectedRegistrationId);
    }

    #[Computed]
    public function brackets()
    {
        return $this->event->brackets_sorted_for_display;
    }

    #[Computed]
    public function selectedBracketLabel(): ?string
    {
        if ($this->bracketFilter === '') {
            return null;
        }

        return $this->brackets
            ->firstWhere('id', (int) $this->bracketFilter)
            ?->name;
    }

    #[Computed]
    public function checkinStats(): array
    {
        $base = $this->event->registrations()
            ->publiclyListed()
            ->when($this->bracketFilter !== '', function ($query) {
                $query->where('bracket_id', (int) $this->bracketFilter);
            });

        $total = (clone $base)->count();
        $checkedIn = (clone $base)->whereHas('checkin')->count();

        return [
            'checked_in' => $checkedIn,
            'pending' => max(0, $total - $checkedIn),
            'total' => $total,
        ];
    }

    #[Computed]
    public function registrationSearchResults()
    {
        $term = trim($this->search);
        if ($term === '') {
            return collect();
        }

        return $this->eligibleRegistrationsQuery()
            ->with(['rider', 'bracket', 'checkin'])
            ->whereHas('rider', function ($query) use ($term) {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $query->where(function ($riderQuery) use ($like) {
                    $riderQuery
                        ->where('name', 'like', $like)
                        ->orWhere('nickname', 'like', $like);
                });
            })
            ->orderBy('number_plate')
            ->limit(20)
            ->get();
    }

    #[Computed]
    public function filteredRegistrations()
    {
        return $this->eligibleRegistrationsQuery()
            ->with(['rider', 'bracket', 'checkin.checkedInByUser'])
            ->withExists('checkin')
            ->orderBy('checkin_exists')
            ->orderBy('number_plate')
            ->paginate(15);
    }

    #[Computed]
    public function checkins()
    {
        return $this->event->checkins()
            ->with(['registration.rider', 'registration.bracket', 'checkedInByUser'])
            ->orderByDesc('checked_in_at')
            ->paginate(15);
    }

    protected function eligibleRegistrationsQuery()
    {
        return $this->event->registrations()
            ->publiclyListed()
            ->when($this->bracketFilter !== '', function ($query) {
                $query->where('bracket_id', (int) $this->bracketFilter);
            });
    }

    public function render()
    {
        return view('livewire.events.event-checkin-list');
    }
}

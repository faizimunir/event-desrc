<?php

namespace App\Livewire\Events;

use App\Models\Event;
use App\Models\Registration;
use App\Services\EventTicketCheckinScanService;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class EventCheckinList extends Component
{
    use WithPagination;

    public Event $event;

    public string $search = '';

    public ?string $scanMessage = null;

    public ?string $scanMessageType = null;

    /** @var ?array{name: string, number_plate: ?string, teams: ?string, bracket: ?string} */
    public ?array $scanSummary = null;

    public ?int $editingRegistrationId = null;

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
        $this->resetPage('checkinsPage');
    }

    public function processScannedCode(string $code): void
    {
        abort_unless(auth()->user()->canAs('checkin.create'), 403);

        $result = app(EventTicketCheckinScanService::class)->process(
            $this->event,
            $code,
            checkedInByUserId: (int) auth()->id(),
        );

        $this->scanMessage = $result['type'] === 'success' ? null : $result['message'];
        $this->scanMessageType = $result['type'] === 'success' ? null : $result['type'];
        $this->scanSummary = $result['summary'] ?? null;
        $this->event = $this->event->fresh();
        unset($this->checkins, $this->registrationsAvailableForCheckin);
        $this->resetPage('checkinsPage');
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
    public function checkins()
    {
        return $this->event->checkins()
            ->with(['registration.rider', 'registration.bracket', 'checkedInByUser'])
            ->when($this->search !== '', function ($q) {
                $q->whereHas('registration.rider', function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('nickname', 'like', '%'.$this->search.'%');
                });
            })
            ->orderByDesc('checked_in_at')
            ->paginate(15, ['*'], 'checkinsPage');
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

<?php

namespace App\Livewire\Events;

use App\Concerns\ShowsToast;
use App\Models\Event;
use App\Models\Registration;
use App\Services\EventTicketCheckinScanService;
use Livewire\Attributes\Computed;
use Livewire\Component;

class EventCheckinForm extends Component
{
    use ShowsToast;

    public Event $event;

    public function mount(Event $event): void
    {
        abort_unless(auth()->user()->canAs('checkin.create'), 403);
        $this->event = $event;
    }

    public function processScannedCode(string $code): void
    {
        abort_unless(auth()->user()->canAs('checkin.create'), 403);

        $result = app(EventTicketCheckinScanService::class)->process(
            $this->event,
            $code,
            checkedInByUserId: (int) auth()->id(),
        );

        $this->event = $this->event->fresh();
        unset($this->registrationsAvailableForCheckin);

        $variant = match ($result['type']) {
            'success' => 'success',
            'error' => 'danger',
            default => 'warning',
        };

        $this->toast($result['message'], $variant);
    }

    /** Registrations that can be checked in (approved, no check-in yet). */
    #[Computed]
    public function registrationsAvailableForCheckin()
    {
        return $this->event->registrations()
            ->where('status', Registration::STATUS_APPROVED)
            ->whereDoesntHave('checkin')
            ->with('rider')
            ->orderBy('number_plate')
            ->get();
    }

    public function render()
    {
        return view('livewire.events.event-checkin-form');
    }
}

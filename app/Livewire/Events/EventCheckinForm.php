<?php

namespace App\Livewire\Events;

use App\Concerns\ShowsToast;
use App\Models\Event;
use App\Services\EventTicketCheckinScanService;
use Livewire\Attributes\Computed;
use Livewire\Component;

class EventCheckinForm extends Component
{
    use ShowsToast;

    public Event $event;

    /** @var array{type: string, message: string, summary: ?array}|null */
    public ?array $lastScanResult = null;

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
        unset($this->recentCheckins);

        $summary = $result['summary'] ?? null;
        if ($summary === null && $result['checkin']) {
            $result['checkin']->loadMissing(['registration.rider', 'registration.bracket']);
            $summary = $result['checkin']->registration?->checkinSummary();
        }

        $this->lastScanResult = [
            'type' => $result['type'],
            'message' => $result['message'],
            'summary' => $summary,
        ];

        $variant = match ($result['type']) {
            'success' => 'success',
            'error' => 'danger',
            default => 'warning',
        };

        $this->toast($result['message'], $variant);
    }

    #[Computed]
    public function recentCheckins()
    {
        return $this->event->checkins()
            ->with(['registration.rider', 'registration.bracket', 'checkedInByUser'])
            ->orderByDesc('checked_in_at')
            ->limit(50)
            ->get();
    }

    public function render()
    {
        return view('livewire.events.event-checkin-form');
    }
}

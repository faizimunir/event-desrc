<?php

namespace App\Livewire\WhatsappNotifications;

use App\Concerns\ShowsToast;
use App\Models\Event;
use App\Models\WhatsappNotificationLog;
use App\Services\TicketService;
use App\Services\TicketWhatsappBroadcast;
use App\Services\WhacenterService;
use Carbon\CarbonInterval;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class TicketNotificationCenter extends Component
{
    use ShowsToast;
    use WithPagination;

    private const PER_PAGE = 20;

    private const RESEND_PER_MINUTE = 20;

    #[Url(as: 'event')]
    public ?int $eventId = null;

    #[Url(as: 'status')]
    public string $statusFilter = '';

    public string $search = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->canAs('whatsapp_notification.read'), 403);

        if ($this->selectedEvent === null) {
            $this->eventId = TicketWhatsappBroadcast::accessibleEvents(auth()->user())
                ->orderByDesc('start_at')
                ->value('id');

            unset($this->selectedEvent);
        }

        if ($this->statusFilter !== '' && ! in_array($this->statusFilter, TicketWhatsappBroadcast::STATES, true)) {
            $this->statusFilter = '';
        }
    }

    public function updatedEventId(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function setStatusFilter(string $status = ''): void
    {
        $this->statusFilter = in_array($status, TicketWhatsappBroadcast::STATES, true) ? $status : '';
        $this->resetPage();
    }

    public function sendBulk(string $target): void
    {
        $this->authorizeSend();
        $event = $this->requireEvent();

        $result = TicketWhatsappBroadcast::start($event, $target, auth()->user());

        if ($result['ok']) {
            $this->toast(__(':count e-ticket messages are being queued.', ['count' => number_format($result['total'])]));
        } else {
            $this->toast((string) $result['error'], 'danger');
        }
    }

    public function cancelBulk(): void
    {
        $this->authorizeSend();
        $event = $this->requireEvent();

        if (TicketWhatsappBroadcast::requestCancel($event->id)) {
            $this->toast(__('Stopping the bulk send. Messages already queued will still be delivered.'));
        }
    }

    public function resendOne(int $registrationId): void
    {
        $this->authorizeSend();
        $event = $this->requireEvent();

        $allowed = RateLimiter::attempt(
            'wa-ticket-resend:'.auth()->id(),
            self::RESEND_PER_MINUTE,
            fn () => true,
            60
        );
        if (! $allowed) {
            $this->toast(__('Too many resend attempts. Please wait a moment.'), 'danger');

            return;
        }

        $registration = TicketWhatsappBroadcast::eligibleQuery($event->id)
            ->with(['ticket', 'rider.user', 'event.organizer.user', 'bracket', 'package', 'order'])
            ->find($registrationId);

        if (! $registration) {
            $this->toast(__('This participant has no e-ticket to send.'), 'danger');

            return;
        }

        $alreadyQueued = TicketWhatsappBroadcast::whereState(
            TicketWhatsappBroadcast::eligibleQuery($event->id),
            TicketWhatsappBroadcast::STATE_QUEUED
        )->whereKey($registration->id)->exists();

        if ($alreadyQueued) {
            $this->toast(__('A message for this participant is already in the queue.'), 'danger');

            return;
        }

        $error = TicketService::resendTicketWhatsapp($registration);

        $error
            ? $this->toast($error, 'danger')
            : $this->toast(__('E-ticket WhatsApp message has been queued.'));
    }

    #[Computed]
    public function canSend(): bool
    {
        return auth()->user()->canAs('whatsapp_notification.send');
    }

    /** @return Collection<int, Event> */
    #[Computed]
    public function events(): Collection
    {
        return TicketWhatsappBroadcast::accessibleEvents(auth()->user())
            ->select(['id', 'title', 'start_at'])
            ->orderByDesc('start_at')
            ->limit(200)
            ->get();
    }

    #[Computed]
    public function selectedEvent(): ?Event
    {
        if ($this->eventId === null) {
            return null;
        }

        return TicketWhatsappBroadcast::accessibleEvents(auth()->user())->find($this->eventId);
    }

    #[Computed]
    public function summary(): ?array
    {
        return $this->selectedEvent ? TicketWhatsappBroadcast::summary($this->selectedEvent->id) : null;
    }

    #[Computed]
    public function progress(): ?array
    {
        return $this->selectedEvent ? TicketWhatsappBroadcast::progress($this->selectedEvent->id) : null;
    }

    #[Computed]
    public function runActive(): bool
    {
        return TicketWhatsappBroadcast::isRunActive($this->progress);
    }

    #[Computed]
    public function cancelRequested(): bool
    {
        return $this->runActive
            && TicketWhatsappBroadcast::isCancelRequested($this->selectedEvent->id, (string) $this->progress['run_id']);
    }

    /** @return array{connected: bool|null, status: string|null} */
    #[Computed]
    public function device(): array
    {
        return app(WhacenterService::class)->deviceStatus();
    }

    #[Computed]
    public function registrations(): LengthAwarePaginator
    {
        if (! $this->selectedEvent) {
            return new LengthAwarePaginator([], 0, self::PER_PAGE);
        }

        $query = TicketWhatsappBroadcast::eligibleQuery($this->selectedEvent->id)
            ->with(['rider.user', 'bracket']);

        if ($this->statusFilter !== '' && in_array($this->statusFilter, TicketWhatsappBroadcast::STATES, true)) {
            TicketWhatsappBroadcast::whereState($query, $this->statusFilter);
        }

        $term = trim($this->search);
        if ($term !== '') {
            $like = '%'.addcslashes($term, '%_\\').'%';

            $query->where(function ($search) use ($like) {
                $search->where('registrations.number_plate', 'like', $like)
                    ->orWhereHas('rider', fn ($rider) => $rider
                        ->where('name', 'like', $like)
                        ->orWhere('nickname', 'like', $like));
            });
        }

        return $query->orderByDesc('registrations.id')->paginate(self::PER_PAGE);
    }

    /**
     * Log e-ticket terbaru per pendaftar pada halaman ini (satu query untuk semua baris).
     *
     * @return Collection<int, WhatsappNotificationLog>
     */
    #[Computed]
    public function latestLogs(): Collection
    {
        $ids = $this->registrations->pluck('id');
        if ($ids->isEmpty()) {
            return collect();
        }

        return WhatsappNotificationLog::query()
            ->whereIn('registration_id', $ids)
            ->whereIn('type', TicketWhatsappBroadcast::ticketLogTypes())
            ->orderBy('id')
            ->get()
            ->groupBy('registration_id')
            ->map->last();
    }

    public function estimate(int $messages): string
    {
        $seconds = TicketWhatsappBroadcast::estimateSeconds($messages);

        return $seconds < 60
            ? __('under a minute')
            : CarbonInterval::seconds($seconds)->cascade()->forHumans(['parts' => 2, 'short' => true]);
    }

    public function render()
    {
        return view('livewire.whatsapp-notifications.ticket-notification-center');
    }

    private function authorizeSend(): void
    {
        abort_unless(auth()->user()->canAs('whatsapp_notification.send'), 403);
    }

    private function requireEvent(): Event
    {
        return $this->selectedEvent ?? abort(404);
    }
}

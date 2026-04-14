<?php

namespace App\Livewire\Events;

use App\Models\Event;
use App\Models\Order;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class EventRegistrationsList extends Component
{
    use WithPagination;

    public Event $event;

    public string $search = '';

    public array $statusFilter = [];

    public array $paymentStatusFilter = [];

    public function mount(Event $event): void
    {
        abort_unless(auth()->user()->canAs('event.read'), 403);
        $this->event = $event;
        Order::enforceExpiredDraftsForEvent($event->id);
        Order::enforceExpiredPaymentWindowsForEvent($event->id);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPaymentStatusFilter(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->statusFilter = [];
        $this->paymentStatusFilter = [];
        $this->resetPage();
    }

    public function baseRegistrationsQuery()
    {
        return $this->event->registrations()
            ->with(['rider.user', 'bracket', 'package', 'payment', 'order'])
            ->when($this->search !== '', function ($q) {
                $q->whereHas('rider', function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('nickname', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->statusFilter !== [], fn ($q) => $q->whereIn('status', $this->statusFilter))
            ->when($this->paymentStatusFilter !== [], function ($q) {
                $withStatus = array_diff($this->paymentStatusFilter, ['none']);
                $includeNone = in_array('none', $this->paymentStatusFilter);
                $q->where(function ($q) use ($withStatus, $includeNone) {
                    if ($withStatus !== []) {
                        $q->whereHas('payment', fn ($q) => $q->whereIn('status', $withStatus));
                    }
                    if ($includeNone) {
                        $q->orWhereDoesntHave('payment');
                    }
                });
            })
            ->latest();
    }

    #[Computed]
    public function registrations()
    {
        return $this->baseRegistrationsQuery()->paginate(20);
    }

    #[Computed]
    public function exportUrl(): string
    {
        $params = array_filter([
            'status' => $this->statusFilter,
            'payment_status' => $this->paymentStatusFilter,
        ], fn ($v) => is_array($v) ? $v !== [] : true);

        return route('events.registrations.export', $this->event).(empty($params) ? '' : '?'.http_build_query($params));
    }

    public function render()
    {
        return view('livewire.events.event-registrations-list');
    }
}

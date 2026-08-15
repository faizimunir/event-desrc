<?php

namespace App\Livewire\Payments;

use App\Models\Payment;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class PaymentList extends Component
{
    use WithPagination;

    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->canAs('event.read'), 403);

        if ($this->statusFilter !== '' && ! in_array($this->statusFilter, Payment::STATUSES, true)) {
            $this->statusFilter = '';
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function setStatusFilter(string $status = ''): void
    {
        if ($status !== '' && ! in_array($status, Payment::STATUSES, true)) {
            $status = '';
        }

        $this->statusFilter = $status;
        $this->resetPage();
    }

    #[Computed]
    public function payments()
    {
        $term = trim($this->search);

        return Payment::query()
            ->with([
                'registration.event',
                'registration.rider',
                'registration.bracket',
                'registration.package',
                'reviewedByUser',
            ])
            ->when($term !== '', function ($query) use ($term) {
                $like = '%'.addcslashes($term, '%_\\').'%';

                $query->where(function ($searchQuery) use ($like) {
                    $searchQuery
                        ->where('amount', 'like', $like)
                        ->orWhere('transfer_amount', 'like', $like)
                        ->orWhereHas('registration.event', fn ($eventQuery) => $eventQuery->where('title', 'like', $like))
                        ->orWhereHas('registration.rider', function ($riderQuery) use ($like) {
                            $riderQuery
                                ->where('name', 'like', $like)
                                ->orWhere('nickname', 'like', $like);
                        })
                        ->orWhereHas('registration.bracket', fn ($bracketQuery) => $bracketQuery->where('name', 'like', $like));
                });
            })
            ->when(
                $this->statusFilter !== '' && in_array($this->statusFilter, Payment::STATUSES, true),
                fn ($query) => $query->where('status', $this->statusFilter)
            )
            ->latest()
            ->paginate(20);
    }

    public function render()
    {
        return view('livewire.payments.payment-list');
    }
}

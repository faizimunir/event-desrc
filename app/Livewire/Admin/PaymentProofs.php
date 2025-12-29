<?php

namespace App\Livewire\Admin;

use App\Models\Payment;
use App\Models\Event;
use App\Jobs\SendConfirmNotificationJob;
use App\Jobs\SendCancelNotificationJob;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PaymentProofs extends Component
{
    public $eventFilter = '';
    public $statusFilter = 'pending';
    public $events;
    public $payments;
    public $selectedPayment = null;
    public $showModal = false;

    public function mount()
    {
        $this->loadEvents();
        $this->loadPayments();
    }

    public function loadEvents()
    {
        $admin = Auth::guard('admin')->user();
        
        if ($admin->isSuperAdmin()) {
            $this->events = Event::select('id', 'name')->orderBy('name')->get();
        } else {
            $this->events = Event::select('id', 'name')
                ->where('created_by', $admin->id)
                ->orWhere('id', $admin->event_id)
                ->orderBy('name')
                ->get();
        }
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['eventFilter', 'statusFilter'])) {
            $this->loadPayments();
        }
    }

    public function loadPayments()
    {
        $admin = Auth::guard('admin')->user();
        
        $query = Payment::select(
            'payments.id',
            'payments.participant_id',
            'payments.amount',
            'payments.payment_proof',
            'payments.status',
            'payments.payment_date',
            'payments.created_at'
        )
        ->with([
            'participant' => function ($query) {
                $query->select('id', 'package_id', 'category_id', 'name', 'email', 'unique_code');
            },
            'participant.package.event',
            'participant.category.event'
        ]);

        // Filter by admin access
        if (!$admin->isSuperAdmin()) {
            $query->whereHas('participant.package.event', function ($q) use ($admin) {
                $q->where('created_by', $admin->id)
                  ->orWhere('id', $admin->event_id);
            });
        }

        // Filter by event
        if ($this->eventFilter) {
            $query->whereHas('participant.package', function ($q) {
                $q->where('event_id', $this->eventFilter);
            });
        }

        // Filter by status
        if ($this->statusFilter) {
            $query->where('payments.status', $this->statusFilter);
        }

        $this->payments = $query->orderBy('payments.created_at', 'desc')->get();
    }

    public function viewProof($paymentId)
    {
        $this->selectedPayment = Payment::with([
            'participant' => function ($query) {
                $query->select('id', 'package_id', 'category_id', 'name', 'email', 'unique_code');
            },
            'participant.package.event',
            'participant.category.event'
        ])->findOrFail($paymentId);
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->selectedPayment = null;
    }

    public function verify($paymentId)
    {
        $admin = Auth::guard('admin')->user();
        $payment = Payment::with('participant')->findOrFail($paymentId);
        $payment->update([
            'status' => 'verified',
            'payment_date' => now(),
            'verified_by' => $admin->id,
        ]);
        
        $participant = $payment->participant;
        $participant->update([
            'status' => 'confirmed',
        ]);

        // Dispatch confirmation notification job (email + WhatsApp with QR Code)
        SendConfirmNotificationJob::dispatch($participant->fresh());

        session()->flash('success', 'Pembayaran berhasil diverifikasi. Notifikasi telah dikirim ke peserta.');
        $this->loadPayments();
        $this->closeModal();
    }

    public function reject($paymentId)
    {
        $payment = Payment::with('participant')->findOrFail($paymentId);
        $payment->update([
            'status' => 'rejected',
        ]);

        $participant = $payment->participant;
        $participant->update([
            'status' => 'cancelled',
        ]);

        // Dispatch cancellation notification job (email + WhatsApp)
        SendCancelNotificationJob::dispatch($participant->fresh());

        session()->flash('success', 'Pembayaran ditolak. Notifikasi telah dikirim ke peserta.');
        $this->loadPayments();
        $this->closeModal();
    }

    public function render()
    {
        return view('livewire.admin.payment-proofs');
    }
}


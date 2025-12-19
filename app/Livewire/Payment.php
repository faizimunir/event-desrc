<?php

namespace App\Livewire;

use App\Models\Participant;
use App\Models\Payment as PaymentModel;
use App\Models\FormField;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

class Payment extends Component
{
    use WithFileUploads;

    public $participantId;
    public $participant;
    public $payment;
    public $payment_proof;
    public $payment_proof_url = null;
    public $payment_confirmed = false;

    protected $rules = [
        'payment_proof' => 'nullable|image|max:10240', // 10MB max
    ];

    public function mount($participantId)
    {
        $this->participantId = $participantId;
        $this->loadParticipant();
    }

    public function loadParticipant()
    {
        $this->participant = Participant::select('id', 'package_id', 'category_id', 'registration_number', 'unique_code', 'name', 'nickname', 'number_plate', 'komunitas', 'email', 'phone', 'city', 'date_of_birth', 'form_data', 'status')
            ->with([
                'package' => function ($query) {
                    $query->select('id', 'event_id', 'name', 'price')
                        ->with(['event' => function ($eventQuery) {
                            $eventQuery->select('id', 'name', 'location', 'start_date');
                        }]);
                },
                'category' => function ($query) {
                    $query->select('id', 'event_id', 'name');
                },
                'payment'
            ])
            ->findOrFail($this->participantId);

        $this->payment = $this->participant->payment;
        
        if ($this->payment && $this->payment->payment_proof) {
            // Use asset() helper for public storage URL
            $this->payment_proof_url = asset('storage/' . $this->payment->payment_proof);
        }
        
        // Check if payment is already confirmed (participant status = 'registered')
        if ($this->participant->status === 'registered' && $this->payment && $this->payment->payment_proof) {
            $this->payment_confirmed = true;
        }
    }

    public function uploadPaymentProof()
    {
        $this->validate([
            'payment_proof' => 'required|image|max:10240', // 10MB max
        ]);

        // Delete old payment proof if exists
        if ($this->payment && $this->payment->payment_proof) {
            Storage::disk('public')->delete($this->payment->payment_proof);
        }

        // Store new payment proof
        $path = $this->payment_proof->store('payments', 'public');
        
        // Create or update payment
        if ($this->payment) {
            $this->payment->update([
                'payment_proof' => $path,
                'status' => 'paid',
                'payment_date' => now(),
            ]);
        } else {
            $this->payment = PaymentModel::create([
                'participant_id' => $this->participantId,
                'payment_method' => 'bank_transfer',
                'amount' => $this->participant->package->price,
                'payment_proof' => $path,
                'status' => 'paid',
                'payment_date' => now(),
            ]);
        }

        // Reload payment to get fresh data
        $this->payment->refresh();
        // Use asset() helper for public storage URL
        $this->payment_proof_url = asset('storage/' . $path);
        $this->payment_proof = null;

        session()->flash('success', 'Bukti pembayaran berhasil diupload.');
        $this->dispatch('payment-proof-uploaded');
    }

    public function removePaymentProof()
    {
        if ($this->payment && $this->payment->payment_proof) {
            Storage::disk('public')->delete($this->payment->payment_proof);
            $this->payment->update([
                'payment_proof' => null,
                'status' => 'pending',
                'payment_date' => null,
            ]);
            $this->payment_proof_url = null;
            session()->flash('success', 'Bukti pembayaran berhasil dihapus.');
        }
    }

    public function confirmPayment()
    {
        if (!$this->payment || !$this->payment->payment_proof) {
            session()->flash('error', 'Silakan upload bukti pembayaran terlebih dahulu.');
            return;
        }

        // Update payment status to 'paid' (already paid, just confirming)
        $this->payment->update([
            'status' => 'paid',
        ]);

        // Update participant status to 'registered' (waiting for admin verification)
        $this->participant->update([
            'status' => 'registered',
        ]);

        // Reload data
        $this->payment->refresh();
        $this->participant->refresh();

        // Set confirmation flag to show success message
        $this->payment_confirmed = true;

        // Here you can dispatch a job to notify admin
        // NotifyAdminPaymentJob::dispatch($this->payment);

        session()->flash('success', 'Pendaftaran telah diterima. Tunggu konfirmasi dari admin.');
        $this->dispatch('payment-confirmed');
    }

    public function getFormFieldsProperty()
    {
        if (!$this->participant || !$this->participant->package_id) {
            return collect([]);
        }
        
        return FormField::where('package_id', $this->participant->package_id)
            ->where('status', 'active')
            ->orderBy('order')
            ->get();
    }

    public function render()
    {
        return view('livewire.payment', [
            'formFields' => $this->formFields,
        ]);
    }
}

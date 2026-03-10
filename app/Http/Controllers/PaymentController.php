<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Registration;
use App\Services\WhacenterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    /**
     * Public: form bayar manual — verifikasi nomor pendaftaran + WhatsApp, tampilkan rekening & form upload bukti.
     */
    public function create(Request $request)
    {
        $registration = null;
        $bank = Payment::getManualBankInfo();
        $registrationId = $request->old('registration_id', $request->query('registration_id'));
        $whatsapp = $request->old('whatsapp', $request->query('whatsapp'));

        if ($registrationId && $whatsapp) {
            $reg = Registration::with(['event', 'rider.user', 'bracket', 'package'])
                ->find($registrationId);
            if ($reg) {
                $normalized = WhacenterService::normalizeWhatsApp($whatsapp);
                if ($reg->rider->user->whatsapp === $normalized) {
                    $registration = $reg;
                }
            }
        }

        return view('payments.create', compact('registration', 'bank', 'registrationId', 'whatsapp'));
    }

    /**
     * Public: verifikasi registration_id + whatsapp (POST untuk cek, lalu redirect ke form dengan data).
     */
    public function verify(Request $request)
    {
        $validated = $request->validate([
            'registration_id' => ['required', 'integer', 'exists:registrations,id'],
            'whatsapp' => ['required', 'string', 'max:20'],
        ]);

        $registration = Registration::with('rider.user')->findOrFail($validated['registration_id']);
        $normalized = WhacenterService::normalizeWhatsApp($validated['whatsapp']);
        if ($registration->rider->user->whatsapp !== $normalized) {
            return redirect()->route('payment.create')
                ->withErrors(['whatsapp' => __('WhatsApp number does not match this registration.')])
                ->withInput();
        }

        return redirect()->route('payment.create', [
            'registration_id' => $registration->id,
            'whatsapp' => $request->input('whatsapp'),
        ])->withInput();
    }

    /**
     * Public: simpan upload bukti transfer (create atau update payment pending).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'registration_id' => ['required', 'integer', 'exists:registrations,id'],
            'whatsapp' => ['required', 'string', 'max:20'],
            'transfer_proof' => ['required', 'file', 'image', 'max:5120'], // 5MB
        ]);

        $registration = Registration::with('rider.user', 'package')->findOrFail($validated['registration_id']);
        $normalized = WhacenterService::normalizeWhatsApp($validated['whatsapp']);
        if ($registration->rider->user->whatsapp !== $normalized) {
            return redirect()->route('payment.create')
                ->withErrors(['whatsapp' => __('WhatsApp number does not match this registration.')])
                ->withInput();
        }

        $amount = $registration->package?->price ?? 0;

        $payment = $registration->payment;

        if ($payment) {
            if (! $payment->isPending()) {
                return redirect()->route('payment.create', ['registration_id' => $registration->id])
                    ->with('error', __('This payment has already been processed.'))
                    ->withInput();
            }
            if ($payment->transfer_proof_path && Storage::disk('public')->exists($payment->transfer_proof_path)) {
                Storage::disk('public')->delete($payment->transfer_proof_path);
            }
        } else {
            $payment = new Payment;
            $payment->registration_id = $registration->id;
            $payment->amount = $amount;
            $payment->status = Payment::STATUS_PENDING;
        }

        $path = $request->file('transfer_proof')->store('payments/proofs', 'public');
        $payment->transfer_proof_path = $path;
        $payment->admin_notes = null;
        $payment->reviewed_at = null;
        $payment->reviewed_by = null;
        $payment->save();

        return redirect()->route('payment.create', [
            'registration_id' => $registration->id,
            'whatsapp' => $request->input('whatsapp'),
        ])->with('status', __('Transfer proof uploaded. We will verify and confirm your payment.'));
    }

    /**
     * Admin: daftar pembayaran (filter status).
     */
    public function index(Request $request)
    {
        abort_unless(auth()->user()->canAs('event.read'), 403);

        $query = Payment::with(['registration.event', 'registration.rider', 'registration.bracket', 'registration.package', 'reviewedByUser']);

        $status = $request->query('status');
        if ($status && in_array($status, Payment::STATUSES, true)) {
            $query->where('status', $status);
        }

        $payments = $query->latest()->paginate(20)->withQueryString();

        return view('payments.index', compact('payments'));
    }

    /**
     * Admin: approve pembayaran (cek uang masuk, lalu approve).
     */
    public function approve(Request $request, Payment $payment)
    {
        abort_unless(auth()->user()->canAs('event.update'), 403);

        if (! $payment->isPending()) {
            return redirect()->route('payments.index')
                ->with('error', __('Payment is not pending.'));
        }

        $validated = $request->validate([
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $payment->update([
            'status' => Payment::STATUS_APPROVED,
            'admin_notes' => $validated['admin_notes'] ?? null,
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
        ]);

        return redirect()->route('payments.index')
            ->with('status', __('Payment approved.'));
    }

    /**
     * Admin: reject pembayaran (jika tidak ada uang masuk / bukti tidak valid).
     */
    public function reject(Request $request, Payment $payment)
    {
        abort_unless(auth()->user()->canAs('event.update'), 403);

        if (! $payment->isPending()) {
            return redirect()->route('payments.index')
                ->with('error', __('Payment is not pending.'));
        }

        $validated = $request->validate([
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $payment->update([
            'status' => Payment::STATUS_REJECTED,
            'admin_notes' => $validated['admin_notes'] ?? null,
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
        ]);

        return redirect()->route('payments.index')
            ->with('status', __('Payment rejected.'));
    }
}

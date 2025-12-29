<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use App\Models\Payment;
use App\Jobs\SendConfirmNotificationJob;
use App\Jobs\SendCancelNotificationJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $payments = Payment::select('id', 'participant_id', 'payment_method', 'amount', 'payment_date', 'status', 'created_at', 'updated_at')
            ->with(['participant' => function ($query) {
                $query->select('id', 'package_id', 'registration_number', 'name', 'email');
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($payments);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'participant_id' => 'required|exists:participants,id',
            'payment_method' => 'required|in:bank_transfer,e_wallet,cash',
            'amount' => 'required|numeric|min:0',
            'transaction_id' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $participant = Participant::findOrFail($validated['participant_id']);

        // Check if payment already exists
        if ($participant->payment) {
            return response()->json(['message' => 'Payment already exists for this participant'], 400);
        }

        $payment = Payment::create($validated);

        return response()->json($payment, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $payment = Payment::select('id', 'participant_id', 'payment_method', 'amount', 'payment_date', 'payment_proof', 'transaction_id', 'notes', 'status', 'created_at', 'updated_at')
            ->with(['participant' => function ($query) {
                $query->select('id', 'package_id', 'registration_number', 'name', 'email', 'phone');
            }])
            ->findOrFail($id);

        return response()->json($payment);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $payment = Payment::findOrFail($id);

        $validated = $request->validate([
            'payment_method' => 'sometimes|required|in:bank_transfer,e_wallet,cash',
            'amount' => 'sometimes|required|numeric|min:0',
            'payment_date' => 'nullable|date',
            'transaction_id' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'status' => 'sometimes|required|in:pending,paid,verified,rejected,refunded',
        ]);

        $payment->update($validated);

        return response()->json($payment);
    }

    /**
     * Upload payment proof
     */
    public function uploadProof(Request $request, string $id)
    {
        $payment = Payment::findOrFail($id);

        $request->validate([
            'payment_proof' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($payment->payment_proof) {
            Storage::delete($payment->payment_proof);
        }

        $path = $request->file('payment_proof')->store('payment-proofs', 'public');
        $payment->update([
            'payment_proof' => $path,
            'status' => 'paid',
            'payment_date' => now(),
        ]);

        return response()->json($payment);
    }

    /**
     * Verify payment
     */
    public function verify(string $payment)
    {
        $paymentModel = Payment::with('participant')->findOrFail($payment);

        if ($paymentModel->status !== 'paid') {
            return response()->json(['message' => 'Payment must be paid before verification'], 400);
        }

        $admin = Auth::guard('admin')->user();
        
        $paymentModel->update([
            'status' => 'verified',
            'payment_date' => now(),
            'verified_by' => $admin ? $admin->id : null,
        ]);

        // Update participant status
        $participant = $paymentModel->participant;
        $participant->update(['status' => 'confirmed']);

        // Dispatch confirmation notification job (email + WhatsApp with QR Code)
        SendConfirmNotificationJob::dispatch($participant->fresh());

        return response()->json($paymentModel);
    }

    /**
     * Reject payment
     */
    public function reject(Request $request, string $payment)
    {
        $paymentModel = Payment::with('participant')->findOrFail($payment);

        $validated = $request->validate([
            'notes' => 'nullable|string',
        ]);

        $paymentModel->update([
            'status' => 'rejected',
            'notes' => $validated['notes'] ?? $paymentModel->notes,
        ]);

        // Update participant status
        $participant = $paymentModel->participant;
        $participant->update(['status' => 'cancelled']);

        // Dispatch cancellation notification job (email + WhatsApp)
        SendCancelNotificationJob::dispatch($participant->fresh());

        return response()->json($paymentModel);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $payment = Payment::findOrFail($id);

        if ($payment->payment_proof) {
            Storage::delete($payment->payment_proof);
        }

        $payment->delete();

        return response()->json(['message' => 'Payment deleted successfully'], 200);
    }
}

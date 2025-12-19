<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\Participant;
use Illuminate\Http\Request;

class ParticipantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $participants = Participant::select('id', 'package_id', 'registration_number', 'name', 'email', 'phone', 'status', 'created_at', 'updated_at')
            ->with(['package' => function ($query) {
                $query->select('id', 'category_id', 'name', 'price');
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($participants);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'package_id' => 'required|exists:packages,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
        ]);

        $package = Package::findOrFail($validated['package_id']);

        if (!$package->isAvailable()) {
            return response()->json(['message' => 'Package is not available'], 400);
        }

        $participant = Participant::create($validated);

        // Tidak perlu update package current_participants karena kuota diatur di kategori

        return response()->json($participant, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $registrationNumber)
    {
        $participant = Participant::select('id', 'package_id', 'registration_number', 'name', 'email', 'phone', 'address', 'date_of_birth', 'gender', 'emergency_contact_name', 'emergency_contact_phone', 'status', 'created_at', 'updated_at')
            ->with(['package' => function ($query) {
                $query->select('id', 'category_id', 'name', 'description', 'price');
            }, 'payment' => function ($query) {
                $query->select('id', 'participant_id', 'payment_method', 'amount', 'status', 'payment_date');
            }])
            ->where('registration_number', $registrationNumber)
            ->firstOrFail();

        return response()->json($participant);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $participant = Participant::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|max:255',
            'phone' => 'sometimes|required|string|max:20',
            'address' => 'nullable|string',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'status' => 'sometimes|required|in:pending,registered,confirmed,cancelled',
        ]);

        $participant->update($validated);

        return response()->json($participant);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $participant = Participant::findOrFail($id);
        $package = $participant->package;

        $participant->delete();

        // Tidak perlu update package current_participants karena kuota diatur di kategori

        return response()->json(['message' => 'Participant deleted successfully'], 200);
    }

    /**
     * Verify participant registration
     */
    public function verify(string $participant)
    {
        $participantModel = Participant::findOrFail($participant);
        $participantModel->update(['status' => 'confirmed']);

        return response()->json($participantModel);
    }
}

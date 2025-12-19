<?php

namespace App\Http\Controllers;

use App\Models\Package;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $packages = Package::select('id', 'event_id', 'name', 'description', 'price', 'max_participants', 'current_participants', 'status', 'created_at', 'updated_at')
            ->where('status', 'active')
            ->with(['event' => function ($query) {
                $query->select('id', 'name', 'status');
            }])
            ->orderBy('price', 'asc')
            ->get();

        return response()->json($packages);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $package = Package::select('id', 'event_id', 'name', 'description', 'price', 'max_participants', 'current_participants', 'status', 'created_at', 'updated_at')
            ->with(['event' => function ($query) {
                $query->select('id', 'name', 'description', 'status');
            }])
            ->findOrFail($id);

        return response()->json($package);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'event_id' => 'required|exists:events,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'max_participants' => 'nullable|integer|min:1',
            'status' => 'required|in:active,inactive,sold_out',
        ]);

        $package = Package::create($validated);

        return response()->json($package, 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $package = Package::findOrFail($id);

        $validated = $request->validate([
            'event_id' => 'sometimes|required|exists:events,id',
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'max_participants' => 'nullable|integer|min:1',
            'status' => 'sometimes|required|in:active,inactive,sold_out',
        ]);

        $package->update($validated);

        return response()->json($package);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $package = Package::findOrFail($id);
        $package->delete();

        return response()->json(['message' => 'Package deleted successfully'], 200);
    }
}

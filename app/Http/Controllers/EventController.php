<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $events = Event::select('id', 'name', 'description', 'start_date', 'end_date', 'location', 'image', 'status', 'created_at', 'updated_at')
            ->where('status', 'published')
            ->orderBy('start_date', 'asc')
            ->get();

        return response()->json($events);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $event = Event::select('id', 'name', 'description', 'start_date', 'end_date', 'registration_start', 'registration_end', 'location', 'image', 'status', 'created_at', 'updated_at')
            ->with(['categories' => function ($query) {
                $query->select('id', 'event_id', 'name', 'description', 'status')
                    ->where('status', 'active');
            }])
            ->findOrFail($id);

        return response()->json($event);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'registration_start' => 'required|date',
            'registration_end' => 'required|date|after:registration_start',
            'location' => 'required|string|max:255',
            'image' => 'nullable|string|max:255',
            'status' => 'required|in:draft,published,closed,cancelled',
        ]);

        $event = Event::create($validated);

        return response()->json($event, 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $event = Event::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after_or_equal:start_date',
            'registration_start' => 'sometimes|required|date',
            'registration_end' => 'sometimes|required|date|after:registration_start',
            'location' => 'sometimes|required|string|max:255',
            'image' => 'nullable|string|max:255',
            'status' => 'sometimes|required|in:draft,published,closed,cancelled',
        ]);

        $event->update($validated);

        return response()->json($event);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $event = Event::findOrFail($id);
        $event->delete();

        return response()->json(['message' => 'Event deleted successfully'], 200);
    }
}

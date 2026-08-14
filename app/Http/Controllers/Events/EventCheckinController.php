<?php

namespace App\Http\Controllers\Events;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventCheckin;
use App\Models\Registration;
use Illuminate\Http\Request;

class EventCheckinController extends Controller
{
    public function index(Event $event)
    {
        abort_unless(auth()->user()->canAs('checkin.read'), 403);

        return redirect()->route('events.show', [$event, 'tab' => 'checkin']);
    }

    public function create(Event $event)
    {
        abort_unless(auth()->user()->canAs('checkin.create'), 403);

        return view('events.checkins.create', compact('event'));
    }

    public function store(Request $request, Event $event)
    {
        abort_unless(auth()->user()->canAs('checkin.create'), 403);

        $validated = $request->validate([
            'registration_id' => ['required', 'integer', 'exists:registrations,id'],
        ]);

        $registration = Registration::with(['rider', 'bracket'])->findOrFail($validated['registration_id']);
        if ($registration->event_id !== $event->id) {
            abort(404);
        }
        if ($registration->checkin()->exists()) {
            return redirect()->route('events.show', ['event' => $event, 'tab' => 'checkin'])
                ->with('error', __('This registration is already checked in.'));
        }

        $event->checkins()->create([
            'registration_id' => $registration->id,
            'checked_in_by' => auth()->id(),
            'notes' => $request->input('notes'),
        ]);

        return redirect()->route('events.show', ['event' => $event, 'tab' => 'checkin'])
            ->with('checkin_success', $registration->checkinSummary());
    }

    public function edit(Event $event, EventCheckin $checkin)
    {
        abort_unless(auth()->user()->canAs('checkin.update'), 403);
        $this->authorize('update', $checkin);
        abort_if($checkin->event_id !== $event->id, 404);

        $checkin->load(['registration.rider', 'registration.bracket']);

        return view('events.checkins.edit', compact('event', 'checkin'));
    }

    public function update(Request $request, Event $event, EventCheckin $checkin)
    {
        abort_unless(auth()->user()->canAs('checkin.update'), 403);
        $this->authorize('update', $checkin);
        abort_if($checkin->event_id !== $event->id, 404);

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $checkin->update($validated);

        return redirect()->route('events.show', ['event' => $event, 'tab' => 'checkin'])
            ->with('status', __('Check-in updated.'));
    }

    public function destroy(Event $event, EventCheckin $checkin)
    {
        abort_unless(auth()->user()->canAs('checkin.delete'), 403);
        $this->authorize('delete', $checkin);
        abort_if($checkin->event_id !== $event->id, 404);

        $checkin->delete();

        return redirect()->route('events.show', ['event' => $event, 'tab' => 'checkin'])
            ->with('status', __('Check-in removed.'));
    }
}

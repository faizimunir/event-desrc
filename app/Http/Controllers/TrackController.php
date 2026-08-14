<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Track;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TrackController extends Controller
{
    public function index(Event $event)
    {
        abort_unless(auth()->user()->canAs('track.read'), 403);

        return redirect()->route('events.show', [$event, 'tab' => 'tracks']);
    }

    public function create(Event $event)
    {
        abort_unless(auth()->user()->canAs('track.create'), 403);

        return view('events.tracks.create', compact('event'));
    }

    public function store(Request $request, Event $event)
    {
        abort_unless(auth()->user()->canAs('track.create'), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'material' => ['nullable', 'string', 'max:255'],
            'long_track' => ['nullable', 'string', 'max:100'],
            'photo_track' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.(config('media.max_upload_size_kb', 2048))],
        ]);

        $path = null;
        if ($request->hasFile('photo_track')) {
            $path = $request->file('photo_track')->store('tracks', 'public');
        }

        $event->tracks()->create([
            'name' => $validated['name'],
            'material' => $validated['material'] ?? null,
            'long_track' => $validated['long_track'] ?? null,
            'photo_track' => $path,
        ]);

        return redirect()->route('events.show', [$event, 'tab' => 'tracks'])->with('status', __('Track created.'));
    }

    public function edit(Event $event, Track $track)
    {
        abort_unless(auth()->user()->canAs('track.update'), 403);
        $this->authorize('update', $track);
        abort_if($track->event_id !== $event->id, 404);

        return view('events.tracks.edit', compact('event', 'track'));
    }

    public function update(Request $request, Event $event, Track $track)
    {
        abort_unless(auth()->user()->canAs('track.update'), 403);
        $this->authorize('update', $track);
        abort_if($track->event_id !== $event->id, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'material' => ['nullable', 'string', 'max:255'],
            'long_track' => ['nullable', 'string', 'max:100'],
            'photo_track' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.(config('media.max_upload_size_kb', 2048))],
        ]);

        $path = $track->photo_track;
        if ($request->hasFile('photo_track')) {
            if ($track->photo_track) {
                Storage::disk('public')->delete($track->photo_track);
            }
            $path = $request->file('photo_track')->store('tracks', 'public');
        }

        $track->update([
            'name' => $validated['name'],
            'material' => $validated['material'] ?? null,
            'long_track' => $validated['long_track'] ?? null,
            'photo_track' => $path,
        ]);

        return redirect()->route('events.show', [$event, 'tab' => 'tracks'])->with('status', __('Track updated.'));
    }

    public function destroy(Event $event, Track $track)
    {
        abort_unless(auth()->user()->canAs('track.delete'), 403);
        $this->authorize('delete', $track);
        abort_if($track->event_id !== $event->id, 404);

        if ($track->photo_track) {
            Storage::disk('public')->delete($track->photo_track);
        }
        $track->delete();

        return redirect()->route('events.show', [$event, 'tab' => 'tracks'])->with('status', __('Track deleted.'));
    }
}

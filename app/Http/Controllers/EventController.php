<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Location;
use App\Models\Order;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->canAs('event.read'), 403);

        return view('events.index');
    }

    public function show(Event $event)
    {
        abort_unless(auth()->user()->canAs('event.read'), 403);
        $this->authorize('view', $event);

        $event->load(['location', 'racingCommittee', 'masterOfCeremony', 'brackets', 'packages.rewards', 'tracks', 'rundowns.brackets']);

        $validTabs = ['overview', 'code-access', 'packages', 'tracks', 'registrations', 'brackets', 'rundown', 'checkin', 'live-result'];
        $requestedTab = request('tab');
        $firstTab = in_array($requestedTab, $validTabs, true)
            ? $requestedTab
            : 'overview';

        return view('events.show', compact('event', 'firstTab'));
    }

    /**
     * Public event show at /{slug} (e.g. desrc.id/slug).
     * Draft events are not visible; returns 404.
     */
    public function showBySlug(Event $event)
    {
        if ($event->isDraft()) {
            abort(404);
        }

        $event->load(['location', 'organizer.user', 'racingCommittee', 'masterOfCeremony', 'brackets', 'packages.rewards', 'tracks', 'rundowns.brackets']);

        Order::enforceExpiredDraftsForEvent($event->id);
        Order::enforceExpiredPaymentWindowsForEvent($event->id);

        $hasEarlyAccess = $this->hasEarlyAccessForEvent($event);

        $participantTabActive = $event->show_participants_publicly && (
            request()->has('participant_page')
            || request()->filled('participant_search')
            || request()->filled('participant_bracket')
        );

        return view('event-show', compact(
            'event',
            'hasEarlyAccess',
            'participantTabActive',
        ));
    }

    /**
     * Verify early access code (from modal on event-show). Stores event id in session on success.
     */
    public function verifyEarlyAccess(Request $request, Event $event)
    {
        if ($event->isDraft()) {
            abort(404);
        }

        $code = $request->input('code');
        if (! is_string($code) || trim($code) === '') {
            return redirect()->route('events.public.show', $event->slug)
                ->with('error', __('Please enter an access code.'));
        }

        $access = $event->codeAccess()
            ->where('code', trim($code))
            ->first();

        if (! $access || ! $access->isValid()) {
            return redirect()->route('events.public.show', $event->slug)
                ->with('error', __('Invalid or expired access code.'));
        }

        $ids = session('event_early_access', []);
        if (! in_array($event->id, $ids, true)) {
            $ids[] = $event->id;
            session(['event_early_access' => $ids]);
            $access->incrementUse();
        }

        return redirect()->route('events.public.show', $event->slug)
            ->with('status', __('Access granted. You can now register.'));
    }

    protected function hasEarlyAccessForEvent(Event $event): bool
    {
        $ids = session('event_early_access', []);

        return in_array($event->id, $ids, true);
    }

    public function create()
    {
        abort_unless(auth()->user()->canAs('event.create'), 403);

        $locations = Location::orderBy('name')->get();

        return view('events.create', compact('locations'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->canAs('event.create'), 403);

        $request->merge(['location_id' => $request->input('location_id') ?: null]);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:'.Event::CATEGORY_UMUR.','.Event::CATEGORY_TAHUN],
            'description' => ['nullable', 'string'],
            'start_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'status' => ['required', 'in:'.implode(',', Event::STATUSES)],
            'registration_opens_at' => ['nullable', 'date'],
            'registration_closes_at' => ['nullable', 'date', 'after_or_equal:registration_opens_at'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
        ]);

        Event::create($validated);

        return redirect()->route('events.index')->with('status', __('Event created.'));
    }

    public function edit(Event $event)
    {
        abort_unless(auth()->user()->canAs('event.update'), 403);
        $this->authorize('update', $event);

        $locations = Location::orderBy('name')->get();

        return view('events.edit', compact('event', 'locations'));
    }

    public function update(Request $request, Event $event)
    {
        abort_unless(auth()->user()->canAs('event.update'), 403);
        $this->authorize('update', $event);

        $request->merge(['location_id' => $request->input('location_id') ?: null]);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:'.Event::CATEGORY_UMUR.','.Event::CATEGORY_TAHUN],
            'description' => ['nullable', 'string'],
            'start_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'status' => ['required', 'in:'.implode(',', Event::STATUSES)],
            'registration_opens_at' => ['nullable', 'date'],
            'registration_closes_at' => ['nullable', 'date', 'after_or_equal:registration_opens_at'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
        ]);

        $event->update($validated);

        return redirect()->route('events.index')->with('status', __('Event updated.'));
    }

    public function destroy(Event $event)
    {
        abort_unless(auth()->user()->canAs('event.delete'), 403);
        $this->authorize('delete', $event);

        $event->delete();

        return redirect()->route('events.index')->with('status', __('Event deleted.'));
    }
}

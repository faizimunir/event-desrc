<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventCodeAccess;
use Illuminate\Http\Request;

class EventCodeAccessController extends Controller
{
    public function index(Event $event)
    {
        abort_unless(auth()->user()->canAs('event.update'), 403);
        $this->authorize('update', $event);

        $event->load('codeAccess');
        $codes = $event->codeAccess()->orderBy('created_at', 'desc')->get();

        return view('event-code-access.index', compact('event', 'codes'));
    }

    public function store(Request $request, Event $event)
    {
        abort_unless(auth()->user()->canAs('event.update'), 403);
        $this->authorize('update', $event);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
        ]);

        $event->codeAccess()->create([
            'code' => trim($validated['code']),
            'name' => $validated['name'] ? trim($validated['name']) : null,
            'valid_from' => $validated['valid_from'] ?? null,
            'valid_until' => $validated['valid_until'] ?? null,
            'usage_limit' => $validated['usage_limit'] ?? null,
        ]);

        return redirect()->route('events.code-access.index', $event)
            ->with('status', __('Access code added.'));
    }

    public function destroy(Event $event, EventCodeAccess $codeAccess)
    {
        abort_unless(auth()->user()->canAs('event.update'), 403);
        $this->authorize('update', $event);

        if ($codeAccess->event_id !== $event->id) {
            abort(404);
        }

        $codeAccess->delete();

        return redirect()->route('events.code-access.index', $event)
            ->with('status', __('Access code removed.'));
    }
}

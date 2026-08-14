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

        return redirect()->route('events.show', [$event, 'tab' => 'code-access']);
    }

    public function create(Event $event)
    {
        abort_unless(auth()->user()->canAs('event.update'), 403);
        $this->authorize('update', $event);

        return view('events.code-access.create', compact('event'));
    }

    public function store(Request $request, Event $event)
    {
        abort_unless(auth()->user()->canAs('event.update'), 403);
        $this->authorize('update', $event);

        $event->codeAccess()->create($this->validatedCode($request));

        return redirect()->route('events.show', [$event, 'tab' => 'code-access'])
            ->with('status', __('Access code added.'));
    }

    public function edit(Event $event, EventCodeAccess $codeAccess)
    {
        abort_unless(auth()->user()->canAs('event.update'), 403);
        $this->authorize('update', $event);
        abort_if($codeAccess->event_id !== $event->id, 404);

        return view('events.code-access.edit', compact('event', 'codeAccess'));
    }

    public function update(Request $request, Event $event, EventCodeAccess $codeAccess)
    {
        abort_unless(auth()->user()->canAs('event.update'), 403);
        $this->authorize('update', $event);
        abort_if($codeAccess->event_id !== $event->id, 404);

        $codeAccess->update($this->validatedCode($request));

        return redirect()->route('events.show', [$event, 'tab' => 'code-access'])
            ->with('status', __('Access code updated.'));
    }

    public function destroy(Event $event, EventCodeAccess $codeAccess)
    {
        abort_unless(auth()->user()->canAs('event.update'), 403);
        $this->authorize('update', $event);
        abort_if($codeAccess->event_id !== $event->id, 404);

        $codeAccess->delete();

        return redirect()->route('events.show', [$event, 'tab' => 'code-access'])
            ->with('status', __('Access code removed.'));
    }

    private function validatedCode(Request $request): array
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
        ]);

        return [
            'code' => trim($validated['code']),
            'name' => filled($validated['name'] ?? null) ? trim($validated['name']) : null,
            'valid_from' => $validated['valid_from'] ?? null,
            'valid_until' => $validated['valid_until'] ?? null,
            'usage_limit' => $validated['usage_limit'] ?? null,
        ];
    }
}

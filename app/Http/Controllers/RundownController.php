<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\LiveResultCategory;
use App\Models\Rundown;

class RundownController extends Controller
{
    public function index(Event $event)
    {
        abort_unless(auth()->user()->canAs('rundown.read'), 403);

        return redirect()->route('events.show', [$event, 'tab' => 'rundown']);
    }

    public function create(Event $event)
    {
        abort_unless(auth()->user()->canAs('rundown.create'), 403);

        return view('events.rundowns.create', compact('event'));
    }

    public function show(Event $event, Rundown $rundown)
    {
        abort_unless(auth()->user()->canAs('rundown.read'), 403);
        abort_if($rundown->event_id !== $event->id, 404);

        if (auth()->user()->canAs('rundown.update') && auth()->user()->can('update', $rundown)) {
            return redirect()->route('events.rundowns.edit', [$event, $rundown]);
        }

        return redirect()->route('events.show', [$event, 'tab' => 'rundown']);
    }

    public function edit(Event $event, Rundown $rundown)
    {
        abort_unless(auth()->user()->canAs('rundown.update'), 403);
        $this->authorize('update', $rundown);
        abort_if($rundown->event_id !== $event->id, 404);

        return view('events.rundowns.edit', compact('event', 'rundown'));
    }

    public function destroy(Event $event, Rundown $rundown)
    {
        abort_unless(auth()->user()->canAs('rundown.delete'), 403);
        $this->authorize('delete', $rundown);
        abort_if($rundown->event_id !== $event->id, 404);

        $rundown->delete();

        LiveResultCategory::syncOrderForEvent($event);

        return redirect()->route('events.show', [$event, 'tab' => 'rundown'])->with('status', __('Rundown deleted.'));
    }
}

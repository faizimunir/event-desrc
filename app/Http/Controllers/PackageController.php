<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Package;

class PackageController extends Controller
{
    public function index(Event $event)
    {
        abort_unless(auth()->user()->canAs('package.read'), 403);

        return redirect()->route('events.show', [$event, 'tab' => 'packages']);
    }

    public function create(Event $event)
    {
        abort_unless(auth()->user()->canAs('package.create'), 403);

        return view('events.packages.create', compact('event'));
    }

    public function show(Event $event, Package $package)
    {
        abort_unless(auth()->user()->canAs('package.read'), 403);
        abort_if($package->event_id !== $event->id, 404);

        if (auth()->user()->canAs('package.update') && auth()->user()->can('update', $package)) {
            return redirect()->route('events.packages.edit', [$event, $package]);
        }

        return redirect()->route('events.show', [$event, 'tab' => 'packages']);
    }

    public function edit(Event $event, Package $package)
    {
        abort_unless(auth()->user()->canAs('package.update'), 403);
        $this->authorize('update', $package);
        abort_if($package->event_id !== $event->id, 404);

        return view('events.packages.edit', compact('event', 'package'));
    }

    public function destroy(Event $event, Package $package)
    {
        abort_unless(auth()->user()->canAs('package.delete'), 403);
        $this->authorize('delete', $package);
        abort_if($package->event_id !== $event->id, 404);

        $package->delete();

        return redirect()->route('events.show', [$event, 'tab' => 'packages'])->with('status', __('Package deleted.'));
    }
}

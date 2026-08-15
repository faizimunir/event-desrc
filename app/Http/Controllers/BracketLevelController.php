<?php

namespace App\Http\Controllers;

use App\Models\Bracket;
use App\Models\BracketLevel;
use App\Models\Event;
use App\Models\Level;
use Illuminate\Http\Request;

class BracketLevelController extends Controller
{
    public function index(Event $event, Bracket $bracket)
    {
        abort_unless(auth()->user()->canAs('bracket_level.read'), 403);
        abort_if($bracket->event_id !== $event->id, 404);

        $bracket->load(['bracketLevels.level']);

        return view('events.brackets.bracket-levels.index', compact('event', 'bracket'));
    }

    public function create(Event $event, Bracket $bracket)
    {
        abort_unless(auth()->user()->canAs('bracket_level.create'), 403);
        abort_if($bracket->event_id !== $event->id, 404);

        $levels = Level::orderBy('order')->orderBy('id')->get();

        return view('events.brackets.bracket-levels.create', compact('event', 'bracket', 'levels'));
    }

    public function store(Request $request, Event $event, Bracket $bracket)
    {
        abort_unless(auth()->user()->canAs('bracket_level.create'), 403);
        abort_if($bracket->event_id !== $event->id, 404);

        $validated = $request->validate([
            'event_level_id' => ['required', 'exists:levels,id'],
            'name_original' => ['required', 'string', 'max:255'],
        ]);

        $bracket->bracketLevels()->create([
            'event_level_id' => $validated['event_level_id'],
            'name_original' => $validated['name_original'],
        ]);

        return redirect()->route('events.brackets.bracket-levels.index', [$event, $bracket])
            ->with('status', __('Bracket level created.'));
    }

    public function show(Event $event, Bracket $bracket, BracketLevel $bracketLevel)
    {
        abort_unless(auth()->user()->canAs('bracket_level.read'), 403);
        abort_if($bracket->event_id !== $event->id, 404);
        abort_if($bracketLevel->event_bracket_id !== $bracket->id, 404);

        if (auth()->user()->canAs('bracket_level.update') && auth()->user()->can('update', $bracketLevel)) {
            return redirect()->route('events.brackets.bracket-levels.edit', [$event, $bracket, $bracketLevel]);
        }

        return redirect()->route('events.brackets.bracket-levels.index', [$event, $bracket]);
    }

    public function edit(Event $event, Bracket $bracket, BracketLevel $bracketLevel)
    {
        abort_unless(auth()->user()->canAs('bracket_level.update'), 403);
        abort_if($bracket->event_id !== $event->id, 404);
        abort_if($bracketLevel->event_bracket_id !== $bracket->id, 404);
        $this->authorize('update', $bracketLevel);

        $levels = Level::orderBy('order')->orderBy('id')->get();

        return view('events.brackets.bracket-levels.edit', compact('event', 'bracket', 'bracketLevel', 'levels'));
    }

    public function update(Request $request, Event $event, Bracket $bracket, BracketLevel $bracketLevel)
    {
        abort_unless(auth()->user()->canAs('bracket_level.update'), 403);
        abort_if($bracket->event_id !== $event->id, 404);
        abort_if($bracketLevel->event_bracket_id !== $bracket->id, 404);
        $this->authorize('update', $bracketLevel);

        $validated = $request->validate([
            'event_level_id' => ['required', 'exists:levels,id'],
            'name_original' => ['required', 'string', 'max:255'],
        ]);

        $bracketLevel->update($validated);

        return redirect()->route('events.brackets.bracket-levels.index', [$event, $bracket])
            ->with('status', __('Bracket level updated.'));
    }

    public function destroy(Event $event, Bracket $bracket, BracketLevel $bracketLevel)
    {
        abort_unless(auth()->user()->canAs('bracket_level.delete'), 403);
        abort_if($bracket->event_id !== $event->id, 404);
        abort_if($bracketLevel->event_bracket_id !== $bracket->id, 404);
        $this->authorize('delete', $bracketLevel);

        $bracketLevel->delete();

        return redirect()->route('events.brackets.bracket-levels.index', [$event, $bracket])
            ->with('status', __('Bracket level deleted.'));
    }
}

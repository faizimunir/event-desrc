<?php

namespace App\Http\Controllers;

use App\Models\Organizer;
use App\Models\Team;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->canAs('team.read'), 403);

        return view('teams.index');
    }

    public function create()
    {
        abort_unless(auth()->user()->canAs('team.create'), 403);

        $organizers = Organizer::orderBy('name')->get();

        return view('teams.create', compact('organizers'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->canAs('team.create'), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'organizer_id' => ['nullable', 'exists:organizers,id'],
            'type' => ['nullable', 'string', 'max:255'],
        ]);
        $validated['organizer_id'] = $validated['organizer_id'] ?: null;

        Team::create($validated);

        return redirect()->route('teams.index')->with('status', __('Team created.'));
    }

    public function edit(Team $team)
    {
        abort_unless(auth()->user()->canAs('team.update'), 403);
        $this->authorize('update', $team);

        $organizers = Organizer::orderBy('name')->get();

        return view('teams.edit', compact('team', 'organizers'));
    }

    public function update(Request $request, Team $team)
    {
        abort_unless(auth()->user()->canAs('team.update'), 403);
        $this->authorize('update', $team);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'organizer_id' => ['nullable', 'exists:organizers,id'],
            'type' => ['nullable', 'string', 'max:255'],
        ]);
        $validated['organizer_id'] = $validated['organizer_id'] ?: null;

        $team->update($validated);

        return redirect()->route('teams.index')->with('status', __('Team updated.'));
    }

    public function destroy(Team $team)
    {
        abort_unless(auth()->user()->canAs('team.delete'), 403);
        $this->authorize('delete', $team);

        $team->delete();

        return redirect()->route('teams.index')->with('status', __('Team deleted.'));
    }
}

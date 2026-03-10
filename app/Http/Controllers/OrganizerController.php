<?php

namespace App\Http\Controllers;

use App\Models\Organizer;
use Illuminate\Http\Request;

class OrganizerController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->canAs('organizer.read'), 403);

        return view('organizers.index');
    }

    public function create()
    {
        abort_unless(auth()->user()->canAs('organizer.create'), 403);

        return view('organizers.create');
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->canAs('organizer.create'), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'link' => ['nullable', 'string', 'max:255', 'url'],
        ]);

        Organizer::create($validated);

        return redirect()->route('organizers.index')->with('status', __('Organizer created.'));
    }

    public function edit(Organizer $organizer)
    {
        abort_unless(auth()->user()->canAs('organizer.update'), 403);
        $this->authorize('update', $organizer);

        return view('organizers.edit', compact('organizer'));
    }

    public function update(Request $request, Organizer $organizer)
    {
        abort_unless(auth()->user()->canAs('organizer.update'), 403);
        $this->authorize('update', $organizer);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'link' => ['nullable', 'string', 'max:255', 'url'],
        ]);

        $organizer->update($validated);

        return redirect()->route('organizers.index')->with('status', __('Organizer updated.'));
    }

    public function destroy(Organizer $organizer)
    {
        abort_unless(auth()->user()->canAs('organizer.delete'), 403);
        $this->authorize('delete', $organizer);

        $organizer->delete();

        return redirect()->route('organizers.index')->with('status', __('Organizer deleted.'));
    }
}

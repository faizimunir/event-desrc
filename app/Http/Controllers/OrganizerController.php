<?php

namespace App\Http\Controllers;

use App\Models\Organizer;

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

        return view('organizers.form', ['organizer' => null]);
    }

    public function edit(Organizer $organizer)
    {
        abort_unless(auth()->user()->canAs('organizer.update'), 403);
        $this->authorize('update', $organizer);

        return view('organizers.form', compact('organizer'));
    }

    public function destroy(Organizer $organizer)
    {
        abort_unless(auth()->user()->canAs('organizer.delete'), 403);
        $this->authorize('delete', $organizer);

        $organizer->delete();

        return redirect()->route('organizers.index')->with('status', __('Organizer deleted.'));
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->canAs('location.read'), 403);

        return view('locations.index');
    }

    public function create()
    {
        abort_unless(auth()->user()->canAs('location.create'), 403);

        return view('locations.create');
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->canAs('location.create'), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'google_map' => ['nullable', 'string'],
        ]);

        Location::create($validated);

        return redirect()->route('locations.index')->with('status', __('Location created.'));
    }

    public function edit(Location $location)
    {
        abort_unless(auth()->user()->canAs('location.update'), 403);
        $this->authorize('update', $location);

        return view('locations.edit', compact('location'));
    }

    public function update(Request $request, Location $location)
    {
        abort_unless(auth()->user()->canAs('location.update'), 403);
        $this->authorize('update', $location);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'google_map' => ['nullable', 'string'],
        ]);

        $location->update($validated);

        return redirect()->route('locations.index')->with('status', __('Location updated.'));
    }

    public function destroy(Location $location)
    {
        abort_unless(auth()->user()->canAs('location.delete'), 403);
        $this->authorize('delete', $location);

        $location->delete();

        return redirect()->route('locations.index')->with('status', __('Location deleted.'));
    }
}

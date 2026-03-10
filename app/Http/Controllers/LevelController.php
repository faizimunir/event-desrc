<?php

namespace App\Http\Controllers;

use App\Models\Level;
use Illuminate\Http\Request;

class LevelController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->canAs('level.read'), 403);

        return view('levels.index');
    }

    public function create()
    {
        abort_unless(auth()->user()->canAs('level.create'), 403);

        return view('levels.create');
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->canAs('level.create'), 403);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:255', 'unique:event_levels,code'],
            'name' => ['required', 'string', 'max:255'],
            'order' => ['required', 'integer', 'min:0'],
        ]);

        Level::create($validated);

        return redirect()->route('levels.index')->with('status', __('Level created.'));
    }

    public function edit(Level $level)
    {
        abort_unless(auth()->user()->canAs('level.update'), 403);
        $this->authorize('update', $level);

        return view('levels.edit', compact('level'));
    }

    public function update(Request $request, Level $level)
    {
        abort_unless(auth()->user()->canAs('level.update'), 403);
        $this->authorize('update', $level);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:255', 'unique:event_levels,code,'.$level->id],
            'name' => ['required', 'string', 'max:255'],
            'order' => ['required', 'integer', 'min:0'],
        ]);

        $level->update($validated);

        return redirect()->route('levels.index')->with('status', __('Level updated.'));
    }

    public function destroy(Level $level)
    {
        abort_unless(auth()->user()->canAs('level.delete'), 403);
        $this->authorize('delete', $level);

        $level->delete();

        return redirect()->route('levels.index')->with('status', __('Level deleted.'));
    }
}

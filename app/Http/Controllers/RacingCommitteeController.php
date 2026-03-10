<?php

namespace App\Http\Controllers;

use App\Models\RacingCommittee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RacingCommitteeController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->canAs('rc.read'), 403);

        return view('racing-committees.index');
    }

    public function create()
    {
        abort_unless(auth()->user()->canAs('rc.create'), 403);

        return view('racing-committees.create');
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->canAs('rc.create'), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'link' => ['nullable', 'string', 'max:255', 'url'],
            'photo_rc' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.(config('media.max_upload_size_kb', 2048))],
        ]);

        $path = null;
        if ($request->hasFile('photo_rc')) {
            $path = $request->file('photo_rc')->store('racing-committees', 'public');
        }

        RacingCommittee::create([
            'name' => $validated['name'],
            'link' => $validated['link'] ?? null,
            'photo_rc' => $path,
        ]);

        return redirect()->route('racing-committees.index')->with('status', __('Racing committee created.'));
    }

    public function edit(RacingCommittee $racingCommittee)
    {
        abort_unless(auth()->user()->canAs('rc.update'), 403);
        $this->authorize('update', $racingCommittee);

        return view('racing-committees.edit', compact('racingCommittee'));
    }

    public function update(Request $request, RacingCommittee $racingCommittee)
    {
        abort_unless(auth()->user()->canAs('rc.update'), 403);
        $this->authorize('update', $racingCommittee);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'link' => ['nullable', 'string', 'max:255', 'url'],
            'photo_rc' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.(config('media.max_upload_size_kb', 2048))],
        ]);

        $path = $racingCommittee->photo_rc;
        if ($request->hasFile('photo_rc')) {
            if ($racingCommittee->photo_rc) {
                Storage::disk('public')->delete($racingCommittee->photo_rc);
            }
            $path = $request->file('photo_rc')->store('racing-committees', 'public');
        }

        $racingCommittee->update([
            'name' => $validated['name'],
            'link' => $validated['link'] ?? null,
            'photo_rc' => $path,
        ]);

        return redirect()->route('racing-committees.index')->with('status', __('Racing committee updated.'));
    }

    public function destroy(RacingCommittee $racingCommittee)
    {
        abort_unless(auth()->user()->canAs('rc.delete'), 403);
        $this->authorize('delete', $racingCommittee);

        if ($racingCommittee->photo_rc) {
            Storage::disk('public')->delete($racingCommittee->photo_rc);
        }
        $racingCommittee->delete();

        return redirect()->route('racing-committees.index')->with('status', __('Racing committee deleted.'));
    }
}

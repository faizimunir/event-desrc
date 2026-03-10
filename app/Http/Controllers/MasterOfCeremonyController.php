<?php

namespace App\Http\Controllers;

use App\Models\MasterOfCeremony;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MasterOfCeremonyController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->canAs('mc.read'), 403);

        return view('master-of-ceremonies.index');
    }

    public function create()
    {
        abort_unless(auth()->user()->canAs('mc.create'), 403);

        return view('master-of-ceremonies.create');
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->canAs('mc.create'), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'link' => ['nullable', 'string', 'max:255', 'url'],
            'avatar_mc' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.(config('media.max_upload_size_kb', 2048))],
        ]);

        $path = null;
        if ($request->hasFile('avatar_mc')) {
            $path = $request->file('avatar_mc')->store('master-of-ceremonies', 'public');
        }

        MasterOfCeremony::create([
            'name' => $validated['name'],
            'link' => $validated['link'] ?? null,
            'avatar_mc' => $path,
        ]);

        return redirect()->route('master-of-ceremonies.index')->with('status', __('Master of Ceremony created.'));
    }

    public function edit(MasterOfCeremony $masterOfCeremony)
    {
        abort_unless(auth()->user()->canAs('mc.update'), 403);
        $this->authorize('update', $masterOfCeremony);

        return view('master-of-ceremonies.edit', compact('masterOfCeremony'));
    }

    public function update(Request $request, MasterOfCeremony $masterOfCeremony)
    {
        abort_unless(auth()->user()->canAs('mc.update'), 403);
        $this->authorize('update', $masterOfCeremony);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'link' => ['nullable', 'string', 'max:255', 'url'],
            'avatar_mc' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.(config('media.max_upload_size_kb', 2048))],
        ]);

        $path = $masterOfCeremony->avatar_mc;
        if ($request->hasFile('avatar_mc')) {
            if ($masterOfCeremony->avatar_mc) {
                Storage::disk('public')->delete($masterOfCeremony->avatar_mc);
            }
            $path = $request->file('avatar_mc')->store('master-of-ceremonies', 'public');
        }

        $masterOfCeremony->update([
            'name' => $validated['name'],
            'link' => $validated['link'] ?? null,
            'avatar_mc' => $path,
        ]);

        return redirect()->route('master-of-ceremonies.index')->with('status', __('Master of Ceremony updated.'));
    }

    public function destroy(MasterOfCeremony $masterOfCeremony)
    {
        abort_unless(auth()->user()->canAs('mc.delete'), 403);
        $this->authorize('delete', $masterOfCeremony);

        if ($masterOfCeremony->avatar_mc) {
            Storage::disk('public')->delete($masterOfCeremony->avatar_mc);
        }
        $masterOfCeremony->delete();

        return redirect()->route('master-of-ceremonies.index')->with('status', __('Master of Ceremony deleted.'));
    }
}

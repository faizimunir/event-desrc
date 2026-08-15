<?php

namespace App\Http\Controllers;

use App\Models\Rider;
use App\Services\MediaService;
use Illuminate\Http\Request;

class RiderController extends Controller
{
    public function __construct(
        protected MediaService $mediaService
    ) {}

    public function index()
    {
        abort_unless(auth()->user()->canAs('rider.read'), 403);

        return view('riders.index');
    }

    public function create()
    {
        abort_unless(auth()->user()->canAs('rider.create'), 403);

        return view('riders.create');
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->canAs('rider.create'), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:255'],
            'pob' => ['nullable', 'string', 'max:255'],
            'dob' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'in:boys,girls,other'],
            'number_plate' => ['nullable', 'string', 'max:50'],
            'photo_rider' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.(config('media.max_upload_size_kb', 2048))],
            'photo_kia' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.(config('media.max_upload_size_kb', 2048))],
        ]);

        $rider = Rider::create([
            'name' => $validated['name'],
            'nickname' => $validated['nickname'] ?? null,
            'pob' => $validated['pob'] ?? null,
            'dob' => $validated['dob'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'number_plate' => $validated['number_plate'] ?? null,
        ]);

        if ($request->hasFile('photo_rider')) {
            $this->mediaService->upload($request->file('photo_rider'), $rider, 'photo_rider');
        }
        if ($request->hasFile('photo_kia')) {
            $this->mediaService->upload($request->file('photo_kia'), $rider, 'photo_kia');
            $rider->update(['photo_kia' => $rider->getFirstMediaUrl('photo_kia')]);
        }

        return redirect()->route('riders.index')->with('status', __('Rider created.'));
    }

    public function show(Rider $rider)
    {
        abort_unless(auth()->user()->canAs('rider.read'), 403);
        $this->authorize('view', $rider);

        $rider->load([
            'user.roles',
            'teams',
            'registrations' => fn ($query) => $query
                ->with(['event', 'bracket', 'order'])
                ->latest(),
        ]);

        return view('riders.show', compact('rider'));
    }

    public function edit(Rider $rider)
    {
        abort_unless(auth()->user()->canAs('rider.update'), 403);
        $this->authorize('update', $rider);

        return view('riders.edit', compact('rider'));
    }

    public function update(Request $request, Rider $rider)
    {
        abort_unless(auth()->user()->canAs('rider.update'), 403);
        $this->authorize('update', $rider);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:255'],
            'pob' => ['nullable', 'string', 'max:255'],
            'dob' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'in:boys,girls,other'],
            'number_plate' => ['nullable', 'string', 'max:50'],
            'photo_rider' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.(config('media.max_upload_size_kb', 2048))],
            'photo_kia' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.(config('media.max_upload_size_kb', 2048))],
        ]);

        $rider->update([
            'name' => $validated['name'],
            'nickname' => $validated['nickname'] ?? null,
            'pob' => $validated['pob'] ?? null,
            'dob' => $validated['dob'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'number_plate' => $validated['number_plate'] ?? null,
        ]);

        if ($request->hasFile('photo_rider')) {
            $rider->deleteMediaCollection('photo_rider');
            $this->mediaService->upload($request->file('photo_rider'), $rider, 'photo_rider');
        }
        if ($request->hasFile('photo_kia')) {
            $rider->deleteMediaCollection('photo_kia');
            $this->mediaService->upload($request->file('photo_kia'), $rider, 'photo_kia');
            $rider->update(['photo_kia' => $rider->getFirstMediaUrl('photo_kia')]);
        }

        return redirect()->route('riders.show', $rider)->with('status', __('Rider updated.'));
    }

    public function destroy(Rider $rider)
    {
        abort_unless(auth()->user()->canAs('rider.delete'), 403);
        $this->authorize('delete', $rider);

        $rider->delete();

        return redirect()->route('riders.index')->with('status', __('Rider deleted.'));
    }

    public function updateAvatar(Request $request, Rider $rider)
    {
        abort_unless(auth()->user()->canAs('rider.update'), 403);
        $this->authorize('update', $rider);

        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.(config('media.max_upload_size_kb', 2048))],
        ]);

        $rider->deleteMediaCollection('avatar');
        $this->mediaService->upload($request->file('avatar'), $rider, 'avatar');

        return back()->with('message', 'Avatar updated.');
    }
}

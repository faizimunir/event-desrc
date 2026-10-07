<?php

namespace App\Http\Controllers;

use App\Models\Rider;
use Illuminate\View\View;

class MyRiderController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        abort_unless($user->canAs('myrider.manage'), 403);

        $riders = $user->riders()
            ->with('teams')
            ->withCount('registrations')
            ->orderBy('name')
            ->get();

        return view('my-rider.index', compact('riders'));
    }

    public function create(): View
    {
        abort_unless(auth()->user()->canAs('myrider.manage'), 403);

        return view('my-rider.create');
    }

    public function edit(Rider $rider): View
    {
        $user = auth()->user();
        abort_unless($user->canAs('myrider.manage'), 403);
        abort_unless($rider->user_id === $user->id, 403);
        $this->authorize('update', $rider);

        return view('my-rider.edit', compact('rider'));
    }
}

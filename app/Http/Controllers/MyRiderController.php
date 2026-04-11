<?php

namespace App\Http\Controllers;

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
}

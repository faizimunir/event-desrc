<?php

namespace App\Http\Controllers;

use App\Models\Reward;
use Illuminate\Http\Request;

class RewardController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->canAs('reward.read'), 403);

        return view('rewards.index');
    }

    public function create()
    {
        abort_unless(auth()->user()->canAs('reward.create'), 403);

        return view('rewards.create');
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->canAs('reward.create'), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:255'],
        ]);

        Reward::create($validated);

        return redirect()->route('rewards.index')->with('status', __('Reward created.'));
    }

    public function edit(Reward $reward)
    {
        abort_unless(auth()->user()->canAs('reward.update'), 403);
        $this->authorize('update', $reward);

        return view('rewards.edit', compact('reward'));
    }

    public function update(Request $request, Reward $reward)
    {
        abort_unless(auth()->user()->canAs('reward.update'), 403);
        $this->authorize('update', $reward);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:255'],
        ]);

        $reward->update($validated);

        return redirect()->route('rewards.index')->with('status', __('Reward updated.'));
    }

    public function destroy(Reward $reward)
    {
        abort_unless(auth()->user()->canAs('reward.delete'), 403);
        $this->authorize('delete', $reward);

        $reward->delete();

        return redirect()->route('rewards.index')->with('status', __('Reward deleted.'));
    }
}

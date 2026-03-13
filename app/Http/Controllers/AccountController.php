<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->canAs('account.read'), 403);

        return view('accounts.index');
    }

    public function create()
    {
        abort_unless(auth()->user()->canAs('account.create'), 403);

        return view('accounts.create');
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->canAs('account.create'), 403);

        $validated = $request->validate([
            'acc_name' => ['required', 'string', 'max:255'],
            'acc_bank' => ['required', 'string', 'max:255'],
            'acc_number' => ['required', 'string', 'max:255'],
        ]);

        Account::create($validated);

        return redirect()->route('accounts.index')->with('status', __('Account created.'));
    }

    public function edit(Account $account)
    {
        abort_unless(auth()->user()->canAs('account.update'), 403);
        $this->authorize('update', $account);

        return view('accounts.edit', compact('account'));
    }

    public function update(Request $request, Account $account)
    {
        abort_unless(auth()->user()->canAs('account.update'), 403);
        $this->authorize('update', $account);

        $validated = $request->validate([
            'acc_name' => ['required', 'string', 'max:255'],
            'acc_bank' => ['required', 'string', 'max:255'],
            'acc_number' => ['required', 'string', 'max:255'],
        ]);

        $account->update($validated);

        return redirect()->route('accounts.index')->with('status', __('Account updated.'));
    }

    public function destroy(Account $account)
    {
        abort_unless(auth()->user()->canAs('account.delete'), 403);
        $this->authorize('delete', $account);

        $account->delete();

        return redirect()->route('accounts.index')->with('status', __('Account deleted.'));
    }
}

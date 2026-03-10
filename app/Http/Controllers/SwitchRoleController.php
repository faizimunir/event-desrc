<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SwitchRoleController extends Controller
{
    /**
     * Switch the active role for the authenticated user
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $request->validate([
            'role' => ['required', 'string', 'in:'.implode(',', Auth::user()->roles->pluck('name')->toArray())],
        ]);

        Auth::user()->setActiveRole($request->input('role'));

        return redirect()->back();
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->canAs('user.read'), 403);

        return view('users.index');
    }

    public function create()
    {
        abort_unless(auth()->user()->canAs('user.create'), 403);

        $roles = Role::where('guard_name', 'web')->orderBy('name')->get();

        return view('users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->canAs('user.create'), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'] ?: null,
            'phone' => $validated['phone'] ?: null,
            'password' => $validated['password'],
        ]);

        if (! empty($validated['roles'])) {
            $user->syncRoles($validated['roles']);
        }

        return redirect()->route('users.index')->with('status', __('User created.'));
    }

    public function edit(User $user)
    {
        abort_unless(auth()->user()->canAs('user.update'), 403);
        $this->authorize('update', $user);

        $user->load('roles');
        $roles = Role::where('guard_name', 'web')->orderBy('name')->get();

        return view('users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        abort_unless(auth()->user()->canAs('user.update'), 403);
        $this->authorize('update', $user);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['nullable', 'string', 'confirmed', Password::defaults()],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ]);

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'] ?: null,
            'phone' => $validated['phone'] ?: null,
        ]);

        if (! empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        $user->save();

        if (array_key_exists('roles', $validated)) {
            $user->syncRoles($validated['roles'] ?? []);
        }

        return redirect()->route('users.index')->with('status', __('User updated.'));
    }

    public function destroy(User $user)
    {
        abort_unless(auth()->user()->canAs('user.delete'), 403);

        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')->with('error', __('You cannot delete your own account.'));
        }

        $this->authorize('delete', $user);
        $user->delete();

        return redirect()->route('users.index')->with('status', __('User deleted.'));
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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

        $roles = $this->assignableRolesFor(auth()->user());

        return view('users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->canAs('user.create'), 403);

        $assignable = auth()->user()->assignableRoleNames();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255', 'unique:users,email'],
            'whatsapp' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', Rule::in($assignable)],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'] ?: null,
            'whatsapp' => $validated['whatsapp'] ?: null,
            'password' => $validated['password'],
        ]);

        if (! empty($validated['roles'])) {
            $user->syncRoles($validated['roles']);
        }

        return redirect()->route('users.index')->with('status', __('User created.'));
    }

    public function show(User $user)
    {
        abort_unless(auth()->user()->canAs('user.read'), 403);
        $this->authorize('view', $user);

        $user->load([
            'roles',
            'riders' => fn ($query) => $query->orderBy('name'),
            'registrations' => fn ($query) => $query
                ->with(['event', 'rider', 'bracket', 'order'])
                ->latest(),
        ]);

        return view('users.show', compact('user'));
    }

    public function edit(User $user)
    {
        abort_unless(auth()->user()->canAs('user.update'), 403);
        $this->authorize('update', $user);

        $user->load('roles');
        $roles = $this->assignableRolesFor(auth()->user());

        return view('users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        abort_unless(auth()->user()->canAs('user.update'), 403);
        $this->authorize('update', $user);

        $actor = auth()->user();
        $assignable = $actor->assignableRoleNames();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'whatsapp' => ['nullable', 'string', 'max:50'],
            'password' => ['nullable', 'string', 'confirmed', Password::defaults()],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', Rule::in($assignable)],
        ]);

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'] ?: null,
            'whatsapp' => $validated['whatsapp'] ?: null,
        ]);

        if (! empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        $user->save();

        if (array_key_exists('roles', $validated)) {
            $requested = array_values(array_intersect($validated['roles'] ?? [], $assignable));

            // Pertahankan elevated roles yang actor tidak boleh sentuh (jika masih ada di target).
            if (! $actor->hasRole('super_admin')) {
                $protected = $user->roles
                    ->pluck('name')
                    ->filter(fn (string $name): bool => in_array($name, User::ELEVATED_ROLES, true))
                    ->all();
                $requested = array_values(array_unique(array_merge($requested, $protected)));
            }

            $user->syncRoles($requested);
        }

        return redirect()->route('users.show', $user)->with('status', __('User updated.'));
    }

    /**
     * Bypass aktivasi (WhatsApp OTP): hanya super_admin.
     */
    public function activate(User $user)
    {
        abort_unless(auth()->user()->hasRole('super_admin'), 403);

        if ($user->isActivated()) {
            return redirect()->back()->with('error', __('This account is already activated.'));
        }

        if (! $user->email || ! $user->password) {
            return redirect()->back()->with('error', __('Set an email and password for this user first (Edit User) before activating.'));
        }

        $user->forceFill([
            'activated_at' => now(),
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        return redirect()->back()->with('status', __('User activated.'));
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

    /**
     * @return \Illuminate\Support\Collection<int, Role>
     */
    private function assignableRolesFor(User $actor)
    {
        $names = $actor->assignableRoleNames();

        return Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $names)
            ->orderBy('name')
            ->get();
    }
}

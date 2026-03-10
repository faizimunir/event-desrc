<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    private const SUPER_ADMIN_ROLE = 'super_admin';

    private function ensureSuperAdmin(): void
    {
        abort_unless(auth()->user()->hasRole(self::SUPER_ADMIN_ROLE), 403);
    }

    public function index()
    {
        $this->ensureSuperAdmin();
        $roles = Role::where('guard_name', 'web')
            ->withCount('permissions')
            ->orderBy('name')
            ->paginate(15);

        return view('roles.index', compact('roles'));
    }

    public function create()
    {
        $this->ensureSuperAdmin();
        $permissions = Permission::where('guard_name', 'web')->orderBy('name')->get();

        return view('roles.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        $this->ensureSuperAdmin();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->where('guard_name', 'web')],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);

        if (! empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return redirect()->route('roles.index')->with('status', __('Role created.'));
    }

    public function edit(Role $role)
    {
        $this->ensureSuperAdmin();
        if ($role->guard_name !== 'web') {
            abort(404);
        }

        $role->load('permissions');
        $permissions = Permission::where('guard_name', 'web')->orderBy('name')->get();

        return view('roles.edit', compact('role', 'permissions'));
    }

    public function update(Request $request, Role $role)
    {
        $this->ensureSuperAdmin();
        if ($role->guard_name !== 'web') {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->where('guard_name', 'web')->ignore($role->id)],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role->update(['name' => $validated['name']]);

        $role->syncPermissions($validated['permissions'] ?? []);

        return redirect()->route('roles.index')->with('status', __('Role updated.'));
    }

    public function destroy(Role $role)
    {
        $this->ensureSuperAdmin();
        if ($role->guard_name !== 'web') {
            abort(404);
        }

        if ($role->name === self::SUPER_ADMIN_ROLE) {
            return redirect()->route('roles.index')->with('error', __('The super_admin role cannot be deleted.'));
        }

        $role->delete();

        return redirect()->route('roles.index')->with('status', __('Role deleted.'));
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    private function ensureSuperAdmin(): void
    {
        abort_unless(auth()->user()->hasRole('super_admin'), 403);
    }

    public function index()
    {
        $this->ensureSuperAdmin();

        return view('permissions.index');
    }

    public function create()
    {
        $this->ensureSuperAdmin();

        return view('permissions.create');
    }

    public function store(Request $request)
    {
        $this->ensureSuperAdmin();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('permissions', 'name')->where('guard_name', 'web')],
        ]);

        Permission::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);

        return redirect()->route('permissions.index')->with('status', __('Permission created.'));
    }

    public function edit(Permission $permission)
    {
        $this->ensureSuperAdmin();
        if ($permission->guard_name !== 'web') {
            abort(404);
        }

        return view('permissions.edit', compact('permission'));
    }

    public function update(Request $request, Permission $permission)
    {
        $this->ensureSuperAdmin();
        if ($permission->guard_name !== 'web') {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('permissions', 'name')->where('guard_name', 'web')->ignore($permission->id)],
        ]);

        $permission->update(['name' => $validated['name']]);

        return redirect()->route('permissions.index')->with('status', __('Permission updated.'));
    }

    public function destroy(Permission $permission)
    {
        $this->ensureSuperAdmin();
        if ($permission->guard_name !== 'web') {
            abort(404);
        }

        $permission->delete();

        return redirect()->route('permissions.index')->with('status', __('Permission deleted.'));
    }
}

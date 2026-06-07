<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:admin.roles.view')->only(['index']);
        $this->middleware('can:admin.roles.create')->only(['create', 'store']);
        $this->middleware('can:admin.roles.edit')->only(['edit', 'update']);
        $this->middleware('can:admin.roles.delete')->only(['destroy']);
    }

    public function index(): View
    {
        $roles = Role::where('guard_name', 'web')->withCount('permissions')->orderBy('name')->get();
        return view('admin.roles.index', compact('roles'));
    }

    public function create(): View
    {
        return view('admin.roles.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
        ]);

        Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);

        return redirect()->route('admin.roles.index')->with('success', 'Role created.');
    }

    public function edit(Role $role): View
    {
        $permissions = Permission::where('guard_name', 'web')->orderBy('name')->get()->groupBy(fn ($p) => explode('.', $p->name)[0] ?? 'other');
        return view('admin.roles.edit', compact('role', 'permissions'));
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,name'],
        ]);
        $role->syncPermissions($validated['permissions'] ?? []);
        return redirect()->route('admin.roles.index')->with('success', 'Role permissions updated.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->name === 'Super Admin') {
            return redirect()->route('admin.roles.index')->with('error', 'Super Admin role cannot be deleted.');
        }

        $role->delete();

        return redirect()->route('admin.roles.index')->with('success', 'Role deleted.');
    }
}

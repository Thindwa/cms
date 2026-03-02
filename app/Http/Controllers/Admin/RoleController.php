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
        $this->middleware('can:admin.roles');
    }

    public function index(): View
    {
        $roles = Role::where('guard_name', 'web')->withCount('permissions')->orderBy('name')->get();
        return view('admin.roles.index', compact('roles'));
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
}

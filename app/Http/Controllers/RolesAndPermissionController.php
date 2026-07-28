<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;


class RolesAndPermissionController extends Controller
{


public function index(Request $request)
{
    $roles = Role::withCount('users')->get();
    $portal = $request->routeIs('admin.*') ? 'admin' : 'client';

    return view('users.rolesandpermission', compact('roles', 'portal') + ['active' => 'roles']);
}



    public function bulkDelete(Request $request)
    {
        $ids = $request->input('role_ids', []);

        if (!empty($ids)) {
            Role::whereIn('id', $ids)->delete();
            return redirect()->route('admin.itsm.roles')->with('success', 'Selected roles deleted successfully.');
        }

        return redirect()->route('admin.itsm.roles')->with('error', 'No roles selected for deletion.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'role_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'department' => 'required|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['permissions'] = array_values($validated['permissions'] ?? []);
        $validated['is_active'] = $request->boolean('is_active');
        Role::create($validated);

        return redirect()->route('admin.itsm.roles')->with('success', 'Role created successfully.');
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'role_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'department' => 'required|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['permissions'] = array_values($validated['permissions'] ?? []);
        $validated['is_active'] = $request->boolean('is_active');
        $role->update($validated);

        return redirect()->route('admin.itsm.roles')->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role)
    {
        $role->delete();

        return redirect()->route('admin.itsm.roles')->with('success', 'Role deleted successfully.');
    }
}

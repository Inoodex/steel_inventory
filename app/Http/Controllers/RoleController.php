<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $roles = Role::withCount(['users', 'permissions'])
            ->with(['permissions', 'users'])
            ->get();

        return view('frontend.pages.role.index', compact('roles'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $permissions = Permission::orderBy('name')->get();
        return view('frontend.pages.role.create', compact('permissions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:100|unique:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role = Role::create([
            'name' => $validatedData['name'],
            'guard_name' => 'web',
        ]);

        if (!empty($request->permissions)) {
            $permissionModels = Permission::whereIn('id', $request->permissions)->get();
            $role->syncPermissions($permissionModels);
        }

        // Reset Spatie cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        session()->flash('sweet_alert', [
            'type' => 'success',
            'title' => 'Success!',
            'text' => 'Role created successfully.',
        ]);

        return redirect()->route('role.index')->with('success', 'Role created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $role = Role::with('permissions')->findOrFail($id);
        $permissions = Permission::orderBy('name')->get();
        $rolePermissionIds = $role->permissions->pluck('id')->toArray();

        return view('frontend.pages.role.edit', compact('role', 'permissions', 'rolePermissionIds'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $role = Role::findOrFail($id);

        $validatedData = $request->validate([
            'name' => 'required|string|max:100|unique:roles,name,' . $role->id,
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role->update([
            'name' => $validatedData['name'],
        ]);

        if (isset($request->permissions)) {
            $permissionModels = Permission::whereIn('id', $request->permissions)->get();
            $role->syncPermissions($permissionModels);
        } else {
            $role->syncPermissions([]);
        }

        // Reset Spatie cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        session()->flash('sweet_alert', [
            'type' => 'success',
            'title' => 'Success!',
            'text' => 'Role updated successfully.',
        ]);

        return redirect()->route('role.index')->with('success', 'Role updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $role = Role::findOrFail($id);

        if (in_array(strtolower($role->name), ['super admin', 'admin'])) {
            session()->flash('sweet_alert', [
                'type' => 'error',
                'title' => 'Protected Role!',
                'text' => 'System administrator roles cannot be deleted.',
            ]);
            return redirect()->route('role.index')->with('error', 'System administrator roles cannot be deleted.');
        }

        $role->delete();

        // Reset Spatie cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        session()->flash('sweet_alert', [
            'type' => 'success',
            'title' => 'Success!',
            'text' => 'Role deleted successfully.',
        ]);

        return redirect()->route('role.index')->with('success', 'Role deleted successfully');
    }
}

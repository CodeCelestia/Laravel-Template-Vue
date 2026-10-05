<?php

namespace App\Http\Controllers;

use App\Http\Requests\Permissions\StorePermissionRequest;
use App\Http\Requests\Permissions\UpdatePermissionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionController extends Controller
{
    /**
     * Store a newly created permission in storage.
     */
    public function store(StorePermissionRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $permission = Permission::create([
            'name' => strtolower(trim($validated['name'])),
            'guard_name' => 'web',
        ]);

        if (! empty($validated['roles'])) {
            $permission->syncRoles($validated['roles']);
        }

        // Ensure System Admin always receives the new permission
        $adminRole = Role::where('name', 'System Admin')->first();
        if ($adminRole && ! $adminRole->hasPermissionTo($permission)) {
            $adminRole->givePermissionTo($permission);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Permission :name has been created successfully.', ['name' => $permission->name]),
        ]);

        return to_route('roles.index', ['tab' => 'permissions']);
    }

    /**
     * Update the specified permission in storage.
     */
    public function update(UpdatePermissionRequest $request, Permission $permission): RedirectResponse
    {
        $validated = $request->validated();

        $permission->name = strtolower(trim($validated['name']));
        $permission->save();

        if (isset($validated['roles'])) {
            $permission->syncRoles($validated['roles']);

            // Always ensure System Admin retains permission
            $adminRole = Role::where('name', 'System Admin')->first();
            if ($adminRole && ! $adminRole->hasPermissionTo($permission)) {
                $adminRole->givePermissionTo($permission);
            }
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Permission :name has been updated successfully.', ['name' => $permission->name]),
        ]);

        return to_route('roles.index', ['tab' => 'permissions']);
    }

    /**
     * Remove the specified permission from storage.
     */
    public function destroy(Request $request, Permission $permission): RedirectResponse
    {
        abort_unless($request->user()->can('delete', $permission), 403);

        $name = $permission->name;
        $permission->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Permission :name has been deleted successfully.', ['name' => $name]),
        ]);

        return to_route('roles.index', ['tab' => 'permissions']);
    }
}

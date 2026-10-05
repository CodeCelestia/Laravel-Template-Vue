<?php

namespace App\Http\Controllers;

use App\Http\Requests\Roles\StoreRoleRequest;
use App\Http\Requests\Roles\UpdateRoleRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Display a listing of roles.
     */
    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('viewAny', Role::class), 403);

        $search = $request->string('search')->trim()->value();
        $sort = $request->string('sort')->value() ?: 'name';
        $direction = strtolower($request->string('direction')->value() ?: 'asc');
        $perPage = min(max($request->integer('per_page', 10), 5), 100);

        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'asc';
        }

        $query = Role::query()
            ->with(['permissions:id,name'])
            ->withCount('users');

        if (! empty($search)) {
            $query->where('name', 'like', "%{$search}%");
        }

        if (in_array($sort, ['name', 'created_at', 'users_count'], true)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->orderBy('name', 'asc');
        }

        $roles = $query->paginate($perPage)->withQueryString();

        $allPermissions = Permission::query()
            ->select(['id', 'name'])
            ->with('roles:id,name')
            ->orderBy('name')
            ->get();

        $allRolesList = Role::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();

        return Inertia::render('roles/Index', [
            'roles' => [
                'data' => $roles->map(fn (Role $role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'created_at' => $role->created_at?->toISOString(),
                    'users_count' => $role->users_count,
                    'permissions' => $role->permissions->map(fn ($p) => [
                        'id' => $p->id,
                        'name' => $p->name,
                    ]),
                    'can' => [
                        'update' => $request->user()->can('update', $role),
                        'delete' => $request->user()->can('delete', $role),
                    ],
                ]),
                'current_page' => $roles->currentPage(),
                'last_page' => $roles->lastPage(),
                'per_page' => $roles->perPage(),
                'total' => $roles->total(),
                'from' => $roles->firstItem(),
                'to' => $roles->lastItem(),
            ],
            'permissions' => $allPermissions->map(fn (Permission $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'roles_count' => $p->roles->count(),
                'roles' => $p->roles->map(fn ($r) => [
                    'id' => $r->id,
                    'name' => $r->name,
                ]),
                'can' => [
                    'update' => $request->user()->can('update', $p),
                    'delete' => $request->user()->can('delete', $p),
                ],
            ]),
            'all_roles' => $allRolesList,
            'filters' => [
                'search' => $search,
                'sort' => $sort,
                'direction' => $direction,
                'per_page' => $perPage,
                'tab' => $request->string('tab')->value() ?: 'roles',
            ],
        ]);
    }

    /**
     * Store a newly created role in storage.
     */
    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);

        if (! empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Role :name has been created successfully.', ['name' => $role->name]),
        ]);

        return to_route('roles.index');
    }

    /**
     * Display the specified role details.
     */
    public function show(Request $request, Role $role): JsonResponse|Response
    {
        abort_unless($request->user()->can('view', $role), 403);

        $role->load(['permissions:id,name', 'users:id,name,email']);

        $data = [
            'id' => $role->id,
            'name' => $role->name,
            'created_at' => $role->created_at?->toISOString(),
            'users_count' => $role->users->count(),
            'users' => $role->users->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
            ]),
            'permissions' => $role->permissions->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
            ]),
            'can' => [
                'update' => $request->user()->can('update', $role),
                'delete' => $request->user()->can('delete', $role),
            ],
        ];

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return Inertia::render('roles/Show', [
            'role' => $data,
        ]);
    }

    /**
     * Update the specified role in storage.
     */
    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $validated = $request->validated();

        if ($role->name === 'System Admin' && $validated['name'] !== 'System Admin') {
            abort(403, 'The System Admin role name cannot be modified.');
        }

        if ($role->name !== 'System Admin') {
            $role->name = $validated['name'];
            $role->save();
        }

        if (isset($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Role :name has been updated successfully.', ['name' => $role->name]),
        ]);

        return to_route('roles.index');
    }

    /**
     * Remove the specified role from storage.
     */
    public function destroy(Request $request, Role $role): RedirectResponse
    {
        abort_if($role->name === 'System Admin', 403, 'The System Admin role cannot be deleted.');
        abort_if($role->users()->exists(), 403, 'Cannot delete a role that currently has assigned users.');
        abort_unless($request->user()->can('delete', $role), 403);

        $name = $role->name;
        $role->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Role :name has been deleted successfully.', ['name' => $name]),
        ]);

        return to_route('roles.index');
    }
}

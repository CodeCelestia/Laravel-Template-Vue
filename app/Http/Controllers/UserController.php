<?php

namespace App\Http\Controllers;

use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('viewAny', User::class), 403);

        $search = $request->string('search')->trim()->value();
        $roleFilter = $request->string('role')->trim()->value();
        $sort = $request->string('sort')->value() ?: 'created_at';
        $direction = strtolower($request->string('direction')->value() ?: 'desc');
        $perPage = min(max($request->integer('per_page', 10), 5), 100);

        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'desc';
        }

        $query = User::query()
            ->with(['roles:id,name'])
            ->select('users.*');

        // Search by Name or Email
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by Role
        if (! empty($roleFilter)) {
            $query->whereHas('roles', function ($q) use ($roleFilter) {
                $q->where('name', $roleFilter);
            });
        }

        // Column Sorting
        if ($sort === 'role') {
            $query->orderBy(
                Role::select('name')
                    ->join('model_has_roles', 'roles.id', '=', 'model_has_roles.role_id')
                    ->whereColumn('model_has_roles.model_id', 'users.id')
                    ->limit(1),
                $direction
            );
        } elseif (in_array($sort, ['name', 'email', 'created_at'], true)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->latest();
        }

        $users = $query->paginate($perPage)->withQueryString();

        $roles = Role::select(['id', 'name'])
            ->orderBy('name')
            ->get();

        return Inertia::render('users/Index', [
            'users' => [
                'data' => $users->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at?->toISOString(),
                    'created_at' => $user->created_at?->toISOString(),
                    'roles' => $user->roles->map(fn ($r) => [
                        'id' => $r->id,
                        'name' => $r->name,
                    ]),
                    'can' => [
                        'update' => $request->user()->can('update', $user),
                        'delete' => $request->user()->can('delete', $user) && $request->user()->id !== $user->id,
                    ],
                ]),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'from' => $users->firstItem(),
                'to' => $users->lastItem(),
            ],
            'roles' => $roles,
            'filters' => [
                'search' => $search,
                'role' => $roleFilter,
                'sort' => $sort,
                'direction' => $direction,
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
        ]);

        $user->syncRoles([$validated['role']]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('User :name has been created successfully.', ['name' => $user->name]),
        ]);

        return to_route('users.index');
    }

    /**
     * Display the specified user details with roles and permissions.
     */
    public function show(Request $request, User $user): JsonResponse|Response
    {
        abort_unless($request->user()->can('view', $user), 403);

        $user->load(['roles.permissions']);

        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at?->toISOString(),
            'created_at' => $user->created_at?->toISOString(),
            'roles' => $user->roles->map(fn ($r) => [
                'id' => $r->id,
                'name' => $r->name,
            ]),
            'permissions' => $user->getAllPermissions()->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
            ]),
        ];

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return Inertia::render('users/Show', [
            'user' => $data,
        ]);
    }

    /**
     * Update the specified user in storage.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        $user->syncRoles([$validated['role']]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('User :name has been updated successfully.', ['name' => $user->name]),
        ]);

        return to_route('users.index');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_if($request->user()->id === $user->id, 403, 'You cannot delete your own account.');
        abort_unless($request->user()->can('delete', $user), 403);

        $name = $user->name;
        $user->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('User :name has been deleted successfully.', ['name' => $name]),
        ]);

        return to_route('users.index');
    }
}

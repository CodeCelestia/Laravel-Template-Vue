<?php

namespace App\Http\Controllers;

use App\Http\Requests\Municipalities\StoreMunicipalityRequest;
use App\Http\Requests\Municipalities\UpdateMunicipalityRequest;
use App\Models\Municipality;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MunicipalityController extends Controller
{
    /**
     * Display a listing of municipalities and cities.
     */
    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('viewAny', Municipality::class), 403);

        $search = $request->string('search')->trim()->value();
        $district = $request->string('district')->trim()->value();
        $status = $request->string('status')->trim()->value();
        $sort = $request->string('sort')->value() ?: 'name';
        $direction = strtolower($request->string('direction')->value() ?: 'asc');
        $perPage = min(max($request->integer('per_page', 10), 5), 100);

        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'asc';
        }

        $allowedSorts = ['name', 'code', 'type', 'district', 'zip_code', 'is_active'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'name';
        }

        $query = Municipality::query()
            ->search($search)
            ->district($district);

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $municipalities = $query
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Municipality $m) => [
                'id' => $m->id,
                'name' => $m->name,
                'code' => $m->code,
                'type' => $m->type,
                'district' => $m->district,
                'zip_code' => $m->zip_code,
                'location_description' => $m->location_description,
                'latitude' => $m->latitude,
                'longitude' => $m->longitude,
                'map_url' => $m->map_url,
                'is_active' => $m->is_active,
                'created_at' => $m->created_at?->format('M d, Y'),
            ]);

        $stats = [
            'total_lgus' => Municipality::count(),
            'total_cities' => Municipality::where('type', 'city')->count(),
            'total_municipalities' => Municipality::where('type', 'municipality')->count(),
            'district_1_count' => Municipality::where('district', 'District 1')->count(),
            'district_2_count' => Municipality::where('district', 'District 2')->count(),
        ];

        return Inertia::render('municipalities/Index', [
            'municipalities' => $municipalities,
            'filters' => [
                'search' => $search,
                'district' => $district,
                'status' => $status,
                'sort' => $sort,
                'direction' => $direction,
                'per_page' => $perPage,
            ],
            'stats' => $stats,
            'can_manage' => $request->user()->can('create', Municipality::class),
        ]);
    }

    /**
     * Store a newly created municipality.
     */
    public function store(StoreMunicipalityRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active', true);

        $municipality = Municipality::create($validated);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __(':name has been added successfully.', ['name' => $municipality->name]),
        ]);

        return back();
    }

    /**
     * Update the specified municipality.
     */
    public function update(UpdateMunicipalityRequest $request, Municipality $municipality): RedirectResponse
    {
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active', true);

        $municipality->update($validated);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __(':name has been updated successfully.', ['name' => $municipality->name]),
        ]);

        return back();
    }

    /**
     * Remove the specified municipality.
     */
    public function destroy(Request $request, Municipality $municipality): RedirectResponse
    {
        abort_unless($request->user()->can('delete', $municipality), 403);

        $municipality->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __(':name has been removed successfully.', ['name' => $municipality->name]),
        ]);

        return back();
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LocationResource;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LocationController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Location::class);

        $locations = Location::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->query('search').'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('code', 'like', $term)
                        ->orWhere('name', 'like', $term);
                });
            })
            ->orderBy('code')
            ->paginate($request->query('per_page', 15));

        return LocationResource::collection($locations);
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Location::class);

        $location = Location::create($request->validate([
            'code' => 'required|string|max:50|unique:locations,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]));

        return (new LocationResource($location))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Location $location)
    {
        Gate::authorize('view', $location);

        return new LocationResource($location);
    }

    public function update(Request $request, Location $location)
    {
        Gate::authorize('update', $location);

        $location->update($request->validate([
            'code' => 'sometimes|string|max:50|unique:locations,code,'.$location->id,
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
        ]));

        return new LocationResource($location);
    }

    public function destroy(Location $location)
    {
        Gate::authorize('delete', $location);

        $location->delete();

        return response()->json(null, 204);
    }
}

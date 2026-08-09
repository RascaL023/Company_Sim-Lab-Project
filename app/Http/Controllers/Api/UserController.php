<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', User::class);

        $users = User::query()
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->query('role')))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->query('search');

                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate($request->query('per_page', 15));

        return UserResource::collection($users);
    }

    public function store(Request $request)
    {
        Gate::authorize('create', User::class);

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'required|email|max:150|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,staf',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'sometimes|boolean',
        ]);

        $user = User::create([
            ...$validated,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return (new UserResource($user))
            ->response()
            ->setStatusCode(201);
    }

    public function show(User $user)
    {
        Gate::authorize('view', $user);

        return new UserResource($user);
    }

    public function update(Request $request, User $user)
    {
        Gate::authorize('update', $user);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:150',
            'email' => ['sometimes', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'sometimes|string|min:8',
            'role' => 'sometimes|in:admin,staf',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'sometimes|boolean',
        ]);

        $user->update($validated);

        return new UserResource($user->fresh());
    }

    public function destroy(User $user)
    {
        Gate::authorize('delete', $user);

        $user->delete();

        return response()->json(null, 204);
    }
}

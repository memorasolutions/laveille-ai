<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Api\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Http\Requests\StoreUserRequest;
use Modules\Auth\Http\Requests\UpdateUserRequest;
use Modules\Auth\Http\Resources\UserResource;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @group Users
 *
 * Admin endpoints for managing user accounts (requires admin permission).
 */
class UserController extends BaseApiController
{
    /**
     * Return a paginated, filterable list of all users.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', User::class);

        $users = QueryBuilder::for(User::class)
            ->allowedFilters([
                AllowedFilter::partial('name'),
                AllowedFilter::exact('email'),
                AllowedFilter::exact('roles.name', null, false),
            ])
            ->allowedSorts(['name', 'email', 'created_at'])
            ->defaultSort('-created_at')
            ->with('roles')
            ->paginate($request->integer('per_page', 15));

        return UserResource::collection($users);
    }

    /**
     * Return a single user with their roles.
     */
    public function show(User $user): UserResource
    {
        $this->authorize('view', $user);

        return new UserResource($user->load('roles'));
    }

    /**
     * Create a new user account and optionally assign roles.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => Hash::make($request->validated('password')),
        ]);

        if ($request->has('roles')) {
            $user->syncRoles($request->validated('roles'));
        }

        return $this->respondCreated(new UserResource($user->load('roles')));
    }

    /**
     * Update an existing user's data and roles.
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);

        $data = $request->validated();

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        if ($request->has('roles')) {
            $user->syncRoles($request->validated('roles'));
        }

        return $this->respondSuccess(new UserResource($user->fresh()->load('roles')), 'Utilisateur mis à jour.');
    }

    /**
     * Permanently delete a user account.
     */
    public function destroy(User $user): JsonResponse
    {
        // Protection absolue (bypass Gate::before pour super_admin)
        if ($user->id === 1 || $user->id === auth()->id()) {
            abort(403);
        }

        $this->authorize('delete', $user);

        $user->delete();

        return $this->respondSuccess(message: 'Utilisateur supprimé.');
    }
}

<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Modules\Api\Http\Requests\ChangePasswordRequest;
use Modules\Api\Http\Requests\UpdateProfileRequest;
use Modules\Auth\Http\Resources\UserResource;

/**
 * @group Profile
 *
 * Endpoints for the authenticated user to read and update their own profile.
 */
class ProfileApiController extends BaseApiController
{
    /**
     * Return the authenticated user's profile with roles.
     */
    public function show(Request $request): JsonResponse
    {
        return $this->respondSuccess(new UserResource($request->user()->load('roles')));
    }

    /**
     * Update the authenticated user's profile information.
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $request->user()->update($validated);

        return $this->respondSuccess(new UserResource($request->user()->fresh()->load('roles')));
    }

    /**
     * Change the authenticated user's password after verifying the current one.
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {

        $request->user()->update(['password' => Hash::make($request->password)]);

        return $this->respondSuccess(null, 'Mot de passe mis à jour');
    }
}

<?php

declare(strict_types=1);

namespace Modules\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Modules\Api\Http\Requests\ChangePasswordRequest;
use Modules\Api\Http\Requests\UpdateProfileRequest;
use Modules\Auth\Http\Resources\UserResource;

class ProfileApiController extends BaseApiController
{
    public function show(Request $request): JsonResponse
    {
        return $this->respondSuccess(new UserResource($request->user()->load('roles')));
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $request->user()->update($validated);

        return $this->respondSuccess(new UserResource($request->user()->fresh()->load('roles')));
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {

        $request->user()->update(['password' => Hash::make($request->password)]);

        return $this->respondSuccess(null, 'Mot de passe mis à jour');
    }
}

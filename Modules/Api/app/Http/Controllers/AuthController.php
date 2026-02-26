<?php

declare(strict_types=1);

namespace Modules\Api\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Modules\Api\Http\Requests\LoginRequest;
use Modules\Api\Http\Requests\RegisterRequest;
use Modules\Auth\Http\Resources\UserResource;

class AuthController extends BaseApiController
{
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (! Auth::attempt($credentials)) {
            return $this->respondUnauthorized('Identifiants invalides.');
        }

        $user = Auth::user();
        $token = $user->createToken('api-token')->plainTextToken;

        return $this->respondSuccess([
            'user' => new UserResource($user->load('roles')),
            'token' => $token,
        ]);
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => Hash::make($request->validated('password')),
        ]);

        $user->assignRole('user');
        $token = $user->createToken('api-token')->plainTextToken;

        return $this->respondCreated([
            'user' => new UserResource($user->load('roles')),
            'token' => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->respondSuccess(message: 'Déconnecté avec succès.');
    }

    public function user(Request $request): JsonResponse
    {
        return $this->respondSuccess(new UserResource($request->user()->load('roles')));
    }
}

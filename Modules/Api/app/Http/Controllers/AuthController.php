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

/**
 * @group Authentication
 *
 * Endpoints for registering, logging in and managing the current session.
 */
class AuthController extends BaseApiController
{
    /**
     * Log in with email and password and receive a Sanctum token.
     *
     * @unauthenticated
     */
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

    /**
     * Create a new user account and receive a Sanctum token.
     *
     * @unauthenticated
     */
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

    /**
     * Revoke the current Sanctum token and log out.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->respondSuccess(message: 'Déconnecté avec succès.');
    }

    /**
     * Return the authenticated user's profile with roles.
     */
    public function user(Request $request): JsonResponse
    {
        return $this->respondSuccess(new UserResource($request->user()->load('roles')));
    }
}

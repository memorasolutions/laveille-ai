<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Api\Http\Controllers\UserController;
use Modules\Auth\Http\Resources\UserResource;

// Public routes
Route::get('/status', fn () => response()->json([
    'app' => config('app.name'),
    'version' => 'v1',
    'environment' => app()->environment(),
    'timestamp' => now()->toIso8601String(),
]));

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn (Request $request) => new UserResource($request->user()->load('roles')));

    Route::apiResource('users', UserController::class);
});

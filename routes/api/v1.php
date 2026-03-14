<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Api\Http\Controllers\ArticleApiController;
use Modules\Api\Http\Controllers\AuthController;
use Modules\Api\Http\Controllers\BlogApiController;
use Modules\Api\Http\Controllers\CommentApiController;
use Modules\Api\Http\Controllers\NewsletterApiController;
use Modules\Api\Http\Controllers\NotificationApiController;
use Modules\Api\Http\Controllers\PlanApiController;
use Modules\Api\Http\Controllers\ProfileApiController;
use Modules\Api\Http\Controllers\PushSubscriptionController;
use Modules\Api\Http\Controllers\UserController;

// Status
Route::get('/status', fn () => response()->json(array_filter([
    'app' => config('app.name'),
    'version' => 'v1',
    'environment' => app()->isProduction() ? null : app()->environment(),
    'timestamp' => now()->toIso8601String(),
])));

// Auth routes (public, rate-limited)
Route::middleware('throttle:login')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
});

// Blog public (sans authentification)
Route::get('/articles', [BlogApiController::class, 'index']);
Route::get('/articles/{slug}', [BlogApiController::class, 'show']);
Route::get('/blog/categories', [BlogApiController::class, 'categories']);
Route::get('/blog/search', [BlogApiController::class, 'search']);

// Plans publics
Route::get('/plans', [PlanApiController::class, 'index']);
Route::get('/plans/{plan:slug}', [PlanApiController::class, 'show']);

// Newsletter (rate limited + honeypot)
Route::middleware(['throttle:newsletter', 'honeypot'])->group(function () {
    Route::post('/newsletter/subscribe', [NewsletterApiController::class, 'subscribe']);
});

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Profile
    Route::get('/profile', [ProfileApiController::class, 'show']);
    Route::put('/profile', [ProfileApiController::class, 'update']);
    Route::put('/profile/password', [ProfileApiController::class, 'changePassword']);

    // Notifications
    Route::get('/notifications', [NotificationApiController::class, 'index']);
    Route::post('/notifications/read-all', [NotificationApiController::class, 'markAllRead']);
    Route::post('/notifications/{id}/read', [NotificationApiController::class, 'markRead']);
    Route::delete('/notifications/{id}', [NotificationApiController::class, 'destroy']);

    // Comments
    Route::post('/articles/{article:id}/comments', [CommentApiController::class, 'store']);

    // Articles CRUD (authentifié - binding par id car slug est translatable)
    Route::post('/articles', [ArticleApiController::class, 'store']);
    Route::put('/articles/{article:id}', [ArticleApiController::class, 'update']);
    Route::delete('/articles/{article:id}', [ArticleApiController::class, 'destroy']);

    // Push subscriptions
    Route::post('/push-subscriptions', [PushSubscriptionController::class, 'store']);
    Route::delete('/push-subscriptions', [PushSubscriptionController::class, 'destroy']);

    Route::apiResource('users', UserController::class);
});

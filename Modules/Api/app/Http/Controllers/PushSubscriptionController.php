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

/**
 * @group Push Subscriptions
 *
 * Endpoints for managing Web Push notification subscriptions for the authenticated user.
 */
class PushSubscriptionController extends BaseApiController
{
    /**
     * Register or update the user's Web Push subscription endpoint and keys.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => 'required|url',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
        ]);

        $request->user()->updatePushSubscription(
            $validated['endpoint'],
            $validated['keys']['p256dh'],
            $validated['keys']['auth']
        );

        return $this->respondSuccess(null, 'Subscription saved');
    }

    /**
     * Remove a specific Web Push subscription endpoint for the authenticated user.
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->validate(['endpoint' => 'required|url']);

        $request->user()->deletePushSubscription($request->endpoint);

        return $this->respondSuccess(null, 'Subscription removed');
    }
}

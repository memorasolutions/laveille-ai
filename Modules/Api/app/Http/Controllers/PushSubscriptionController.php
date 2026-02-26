<?php

declare(strict_types=1);

namespace Modules\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends BaseApiController
{
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

    public function destroy(Request $request): JsonResponse
    {
        $request->validate(['endpoint' => 'required|url']);

        $request->user()->deletePushSubscription($request->endpoint);

        return $this->respondSuccess(null, 'Subscription removed');
    }
}

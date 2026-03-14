<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Http\Controllers\Webhooks;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AI\Models\Channel;
use Modules\AI\Services\ChannelRegistry;

class EmailWebhookController extends Controller
{
    public function store(Request $request, string $secret, ChannelRegistry $channelRegistry): JsonResponse
    {
        $channel = Channel::where('inbound_secret', $secret)
            ->where('type', 'email')
            ->first();

        if (! $channel) {
            abort(404);
        }

        $adapter = $channelRegistry->adapterFor($channel);
        $adapter->receive($request->all(), $channel);

        return response()->json(['ok' => true]);
    }
}

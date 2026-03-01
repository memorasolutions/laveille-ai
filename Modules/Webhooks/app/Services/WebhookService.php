<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Webhooks\Services;

use Modules\Backoffice\Models\WebhookEndpoint;
use Modules\Webhooks\Enums\WebhookEvent;
use Modules\Webhooks\Jobs\DispatchWebhookJob;
use Modules\Webhooks\Models\WebhookCall;

class WebhookService
{
    public function dispatch(WebhookEvent $event, array $payload): int
    {
        $endpoints = WebhookEndpoint::where('is_active', true)->get();
        $dispatched = 0;

        foreach ($endpoints as $endpoint) {
            if (! $this->endpointListensTo($endpoint, $event)) {
                continue;
            }

            $call = WebhookCall::create([
                'webhook_endpoint_id' => $endpoint->id,
                'event' => $event->value,
                'payload' => array_merge($payload, [
                    'event' => $event->value,
                    'timestamp' => now()->toIso8601String(),
                ]),
                'status' => WebhookCall::STATUS_PENDING,
            ]);

            DispatchWebhookJob::dispatch($call);
            $dispatched++;
        }

        return $dispatched;
    }

    public function retry(WebhookCall $call): void
    {
        $call->update([
            'status' => WebhookCall::STATUS_PENDING,
        ]);

        DispatchWebhookJob::dispatch($call);
    }

    public function testEndpoint(WebhookEndpoint $endpoint): WebhookCall
    {
        $call = WebhookCall::create([
            'webhook_endpoint_id' => $endpoint->id,
            'event' => 'test.ping',
            'payload' => [
                'event' => 'test.ping',
                'timestamp' => now()->toIso8601String(),
                'message' => 'Test webhook delivery',
            ],
            'status' => WebhookCall::STATUS_PENDING,
        ]);

        DispatchWebhookJob::dispatch($call);

        return $call;
    }

    public function generateSignature(array $payload, string $secret): string
    {
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return hash_hmac('sha256', (string) $json, $secret);
    }

    private function endpointListensTo(WebhookEndpoint $endpoint, WebhookEvent $event): bool
    {
        $events = $endpoint->events;

        if (empty($events)) {
            return true;
        }

        return in_array($event->value, $events, true);
    }
}

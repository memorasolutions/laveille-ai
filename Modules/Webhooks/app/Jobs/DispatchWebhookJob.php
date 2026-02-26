<?php

declare(strict_types=1);

namespace Modules\Webhooks\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Webhooks\Models\WebhookCall;

class DispatchWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int> */
    public array $backoff = [10, 60, 300];

    public function __construct(
        public WebhookCall $webhookCall,
    ) {}

    public function handle(): void
    {
        $endpoint = $this->webhookCall->webhookEndpoint;
        $payload = $this->webhookCall->payload;
        $jsonPayload = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $signature = hash_hmac('sha256', (string) $jsonPayload, $endpoint->secret ?? '');

        $response = Http::timeout(10)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'X-Webhook-Signature' => $signature,
                'X-Webhook-Event' => $this->webhookCall->event,
            ])
            ->post($endpoint->url, $payload);

        $this->webhookCall->update([
            'response_code' => $response->status(),
            'response_body' => mb_substr($response->body(), 0, 5000),
            'attempts' => $this->webhookCall->attempts + 1,
            'last_attempt_at' => now(),
            'status' => $response->successful()
                ? WebhookCall::STATUS_SUCCESS
                : WebhookCall::STATUS_FAILED,
        ]);

        if (! $response->successful()) {
            Log::warning('Webhook delivery failed', [
                'endpoint' => $endpoint->url,
                'event' => $this->webhookCall->event,
                'status' => $response->status(),
            ]);

            $this->fail();
        }
    }

    public function failed(?\Throwable $exception = null): void
    {
        $this->webhookCall->update([
            'status' => WebhookCall::STATUS_FAILED,
            'attempts' => $this->webhookCall->attempts + 1,
            'last_attempt_at' => now(),
        ]);

        Log::error('Webhook delivery exhausted retries', [
            'webhook_call_id' => $this->webhookCall->id,
            'endpoint' => $this->webhookCall->webhookEndpoint?->url,
            'event' => $this->webhookCall->event,
            'exception' => $exception?->getMessage(),
        ]);
    }
}

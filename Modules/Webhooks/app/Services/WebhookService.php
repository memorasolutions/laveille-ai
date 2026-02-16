<?php

declare(strict_types=1);

namespace Modules\Webhooks\Services;

use Spatie\WebhookServer\WebhookCall;

class WebhookService
{
    public function send(string $url, array $payload, string $secret = ''): void
    {
        $webhook = WebhookCall::create()
            ->url($url)
            ->payload($payload);

        if ($secret !== '') {
            $webhook->useSecret($secret);
        }

        $webhook->dispatch();
    }

    public function sendWithHeaders(string $url, array $payload, array $headers, string $secret = ''): void
    {
        $webhook = WebhookCall::create()
            ->url($url)
            ->payload($payload)
            ->withHeaders($headers);

        if ($secret !== '') {
            $webhook->useSecret($secret);
        }

        $webhook->dispatch();
    }
}

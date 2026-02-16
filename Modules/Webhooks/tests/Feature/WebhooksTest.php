<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Modules\Webhooks\Services\WebhookService;
use Spatie\WebhookServer\CallWebhookJob;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('webhook service is registered as singleton', function () {
    $service1 = app(WebhookService::class);
    $service2 = app(WebhookService::class);

    expect($service1)->toBeInstanceOf(WebhookService::class);
    expect($service1)->toBe($service2);
});

test('webhook service dispatches webhook call', function () {
    Bus::fake();

    $service = app(WebhookService::class);
    $service->send('https://example.com/webhook', ['event' => 'test'], 'secret123');

    Bus::assertDispatched(CallWebhookJob::class);
});

test('webhook service dispatches with custom headers', function () {
    Bus::fake();

    $service = app(WebhookService::class);
    $service->sendWithHeaders(
        'https://example.com/webhook',
        ['event' => 'test'],
        ['X-Custom' => 'header-value'],
        'secret123'
    );

    Bus::assertDispatched(CallWebhookJob::class);
});

test('webhook server package is available', function () {
    expect(class_exists(\Spatie\WebhookServer\WebhookCall::class))->toBeTrue();
});

test('webhook client package is available', function () {
    expect(class_exists(\Spatie\WebhookClient\WebhookConfig::class))->toBeTrue();
});

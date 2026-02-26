<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Modules\Backoffice\Models\WebhookEndpoint;
use Modules\Webhooks\Enums\WebhookEvent;
use Modules\Webhooks\Jobs\DispatchWebhookJob;
use Modules\Webhooks\Services\WebhookService;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('webhook service is registered as singleton', function () {
    $service1 = app(WebhookService::class);
    $service2 = app(WebhookService::class);

    expect($service1)->toBeInstanceOf(WebhookService::class);
    expect($service1)->toBe($service2);
});

test('webhook service dispatches webhook call', function () {
    Queue::fake();
    WebhookEndpoint::factory()->create(['is_active' => true, 'events' => null]);

    $service = app(WebhookService::class);
    $count = $service->dispatch(WebhookEvent::ArticleCreated, ['id' => 1]);

    expect($count)->toBe(1);
    Queue::assertPushed(DispatchWebhookJob::class);
});

test('webhook service generates signature', function () {
    $service = app(WebhookService::class);
    $signature = $service->generateSignature(['test' => true], 'secret');

    expect($signature)->toBeString()->not->toBeEmpty();
});

test('webhook server package is available', function () {
    expect(class_exists(\Spatie\WebhookServer\WebhookCall::class))->toBeTrue();
});

test('webhook client package is available', function () {
    expect(class_exists(\Spatie\WebhookClient\WebhookConfig::class))->toBeTrue();
});

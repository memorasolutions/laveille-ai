<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Modules\Backoffice\Models\WebhookEndpoint;
use Modules\Webhooks\Enums\WebhookEvent;
use Modules\Webhooks\Jobs\DispatchWebhookJob;
use Modules\Webhooks\Models\WebhookCall;
use Modules\Webhooks\Services\WebhookService;

uses(RefreshDatabase::class);

// --- WebhookEvent enum ---

it('webhook event enum has 9 cases', function () {
    expect(WebhookEvent::cases())->toHaveCount(9);
});

it('webhook event values returns array of strings', function () {
    $values = WebhookEvent::values();
    expect($values)->toBeArray()
        ->and($values)->toContain('article.created')
        ->and($values)->toContain('user.created');
});

it('webhook event label returns french string', function () {
    expect(WebhookEvent::ArticleCreated->label())->toBe('Article créé');
    expect(WebhookEvent::UserCreated->label())->toBe('Utilisateur créé');
});

// --- WebhookCall model ---

it('webhook call can be created', function () {
    $endpoint = WebhookEndpoint::factory()->create();
    $call = WebhookCall::create([
        'webhook_endpoint_id' => $endpoint->id,
        'event' => 'article.created',
        'payload' => ['test' => true],
        'status' => WebhookCall::STATUS_PENDING,
    ]);
    expect($call)->toBeInstanceOf(WebhookCall::class)
        ->and($call->id)->toBeGreaterThan(0);
});

it('webhook call has pending scope', function () {
    $endpoint = WebhookEndpoint::factory()->create();
    WebhookCall::create([
        'webhook_endpoint_id' => $endpoint->id,
        'event' => 'test',
        'payload' => [],
        'status' => WebhookCall::STATUS_PENDING,
    ]);
    WebhookCall::create([
        'webhook_endpoint_id' => $endpoint->id,
        'event' => 'test',
        'payload' => [],
        'status' => WebhookCall::STATUS_SUCCESS,
    ]);
    expect(WebhookCall::pending()->count())->toBe(1);
});

it('webhook call has failed scope', function () {
    $endpoint = WebhookEndpoint::factory()->create();
    WebhookCall::create([
        'webhook_endpoint_id' => $endpoint->id,
        'event' => 'test',
        'payload' => [],
        'status' => WebhookCall::STATUS_FAILED,
    ]);
    expect(WebhookCall::failed()->count())->toBe(1);
});

it('webhook call has successful scope', function () {
    $endpoint = WebhookEndpoint::factory()->create();
    WebhookCall::create([
        'webhook_endpoint_id' => $endpoint->id,
        'event' => 'test',
        'payload' => [],
        'status' => WebhookCall::STATUS_SUCCESS,
    ]);
    expect(WebhookCall::successful()->count())->toBe(1);
});

it('webhook call isPending returns true for pending status', function () {
    $endpoint = WebhookEndpoint::factory()->create();
    $call = WebhookCall::create([
        'webhook_endpoint_id' => $endpoint->id,
        'event' => 'test',
        'payload' => [],
        'status' => WebhookCall::STATUS_PENDING,
    ]);
    expect($call->isPending())->toBeTrue()
        ->and($call->isSuccessful())->toBeFalse()
        ->and($call->isFailed())->toBeFalse();
});

it('webhook call belongs to webhook endpoint', function () {
    $endpoint = WebhookEndpoint::factory()->create();
    $call = WebhookCall::create([
        'webhook_endpoint_id' => $endpoint->id,
        'event' => 'test',
        'payload' => [],
        'status' => WebhookCall::STATUS_PENDING,
    ]);
    expect($call->webhookEndpoint->id)->toBe($endpoint->id);
});

// --- WebhookEndpoint model ---

it('webhook endpoint has many calls', function () {
    $endpoint = WebhookEndpoint::factory()->create();
    WebhookCall::create([
        'webhook_endpoint_id' => $endpoint->id,
        'event' => 'test',
        'payload' => [],
        'status' => WebhookCall::STATUS_PENDING,
    ]);
    expect($endpoint->calls()->count())->toBe(1);
});

it('webhook endpoint casts events as array', function () {
    $endpoint = WebhookEndpoint::factory()->create(['events' => ['article.created', 'user.created']]);
    expect($endpoint->events)->toBeArray()
        ->and($endpoint->events)->toContain('article.created');
});

// --- WebhookService ---

it('webhook service dispatches to active endpoints', function () {
    Queue::fake();
    $endpoint = WebhookEndpoint::factory()->create(['is_active' => true, 'events' => null]);

    $service = app(WebhookService::class);
    $count = $service->dispatch(WebhookEvent::ArticleCreated, ['id' => 1]);

    expect($count)->toBe(1);
    Queue::assertPushed(DispatchWebhookJob::class);
});

it('webhook service skips inactive endpoints', function () {
    Queue::fake();
    WebhookEndpoint::factory()->create(['is_active' => false]);

    $service = app(WebhookService::class);
    $count = $service->dispatch(WebhookEvent::ArticleCreated, ['id' => 1]);

    expect($count)->toBe(0);
    Queue::assertNotPushed(DispatchWebhookJob::class);
});

it('webhook service filters by endpoint events', function () {
    Queue::fake();
    WebhookEndpoint::factory()->create([
        'is_active' => true,
        'events' => ['article.created'],
    ]);

    $service = app(WebhookService::class);
    $count = $service->dispatch(WebhookEvent::UserCreated, ['id' => 1]);

    expect($count)->toBe(0);
    Queue::assertNotPushed(DispatchWebhookJob::class);
});

it('webhook service dispatches to all if endpoint events is null', function () {
    Queue::fake();
    WebhookEndpoint::factory()->create(['is_active' => true, 'events' => null]);

    $service = app(WebhookService::class);
    $count = $service->dispatch(WebhookEvent::UserCreated, ['id' => 1]);

    expect($count)->toBe(1);
    Queue::assertPushed(DispatchWebhookJob::class);
});

it('webhook service generates hmac sha256 signature', function () {
    $service = app(WebhookService::class);
    $payload = ['data' => 'test'];
    $secret = 'my-secret';

    $signature = $service->generateSignature($payload, $secret);
    $expected = hash_hmac('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $secret);

    expect($signature)->toBe($expected);
});

it('webhook service test endpoint creates pending call', function () {
    Queue::fake();
    $endpoint = WebhookEndpoint::factory()->create();

    $service = app(WebhookService::class);
    $call = $service->testEndpoint($endpoint);

    expect($call)->toBeInstanceOf(WebhookCall::class)
        ->and($call->status)->toBe(WebhookCall::STATUS_PENDING)
        ->and($call->event)->toBe('test.ping');
    Queue::assertPushed(DispatchWebhookJob::class);
});

it('webhook service retry resets status to pending', function () {
    Queue::fake();
    $endpoint = WebhookEndpoint::factory()->create();
    $call = WebhookCall::create([
        'webhook_endpoint_id' => $endpoint->id,
        'event' => 'test',
        'payload' => [],
        'status' => WebhookCall::STATUS_FAILED,
    ]);

    $service = app(WebhookService::class);
    $service->retry($call);

    expect($call->fresh()->isPending())->toBeTrue();
    Queue::assertPushed(DispatchWebhookJob::class);
});

// --- DispatchWebhookJob ---

it('dispatch webhook job implements ShouldQueue', function () {
    expect(is_subclass_of(DispatchWebhookJob::class, \Illuminate\Contracts\Queue\ShouldQueue::class))
        ->toBeTrue();
});

it('dispatch webhook job has retry configuration', function () {
    $endpoint = WebhookEndpoint::factory()->create();
    $call = WebhookCall::create([
        'webhook_endpoint_id' => $endpoint->id,
        'event' => 'test',
        'payload' => [],
        'status' => WebhookCall::STATUS_PENDING,
    ]);
    $job = new DispatchWebhookJob($call);
    expect($job->tries)->toBe(3)
        ->and($job->backoff)->toBe([10, 60, 300]);
});

it('webhook call creates record in database', function () {
    $endpoint = WebhookEndpoint::factory()->create();

    Queue::fake();
    $service = app(WebhookService::class);
    $service->dispatch(WebhookEvent::ArticleCreated, ['article_id' => 42]);

    $this->assertDatabaseHas('webhook_calls', [
        'webhook_endpoint_id' => $endpoint->id,
        'event' => 'article.created',
        'status' => WebhookCall::STATUS_PENDING,
    ]);
});

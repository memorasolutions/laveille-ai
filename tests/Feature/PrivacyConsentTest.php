<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\RightsRequest;
use App\Models\UserConsent;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ── UserConsent model ──────────────────────────────────────────────

test('user consent can be created via factory', function () {
    $consent = UserConsent::factory()->create();

    expect($consent)->toBeInstanceOf(UserConsent::class);
    $this->assertDatabaseHas('user_consents', ['id' => $consent->id]);
});

test('generate token creates unique 64-char tokens', function () {
    $token1 = UserConsent::generateToken();
    $token2 = UserConsent::generateToken();

    expect($token1)->not->toBe($token2)
        ->and(strlen($token1))->toBe(64)
        ->and(strlen($token2))->toBe(64);
});

test('consent token is auto-generated on creating', function () {
    $consent = UserConsent::factory()->create(['consent_token' => null]);

    expect($consent->consent_token)->not->toBeNull()
        ->and(strlen($consent->consent_token))->toBe(64);
});

test('active scope excludes expired consents', function () {
    $active = UserConsent::factory()->create(['expires_at' => now()->addDays(30)]);
    $expired = UserConsent::factory()->create(['expires_at' => now()->subDay()]);

    $results = UserConsent::active()->pluck('id');

    expect($results)->toContain($active->id)
        ->not->toContain($expired->id);
});

test('by jurisdiction scope filters correctly', function () {
    $gdpr = UserConsent::factory()->create(['jurisdiction' => 'gdpr']);
    $pipeda = UserConsent::factory()->create(['jurisdiction' => 'pipeda']);

    $results = UserConsent::byJurisdiction('gdpr')->pluck('id');

    expect($results)->toContain($gdpr->id)
        ->not->toContain($pipeda->id);
});

test('is expired returns true for past expiry', function () {
    $consent = UserConsent::factory()->create(['expires_at' => now()->subDay()]);
    expect($consent->isExpired())->toBeTrue();
});

test('is expired returns false for future expiry', function () {
    $consent = UserConsent::factory()->create(['expires_at' => now()->addDays(30)]);
    expect($consent->isExpired())->toBeFalse();
});

test('choices is cast to array', function () {
    $consent = UserConsent::factory()->create(['choices' => ['essential' => true, 'analytics' => false]]);
    $consent->refresh();

    expect($consent->choices)->toBeArray()
        ->and($consent->choices['essential'])->toBeTrue()
        ->and($consent->choices['analytics'])->toBeFalse();
});

// ── RightsRequest model ────────────────────────────────────────────

test('rights request can be created via factory', function () {
    $request = RightsRequest::factory()->create();

    expect($request)->toBeInstanceOf(RightsRequest::class);
    $this->assertDatabaseHas('rights_requests', ['id' => $request->id]);
});

test('rights request auto-generates reference DR-YYYY-NNNNNN', function () {
    $request = RightsRequest::factory()->create();

    expect($request->reference)->toMatch('/^DR-\d{4}-\d{6}$/');
});

test('pending scope includes only pending requests', function () {
    $pending = RightsRequest::factory()->create(['status' => 'pending']);
    $completed = RightsRequest::factory()->create(['status' => 'completed']);

    $results = RightsRequest::pending()->pluck('id');

    expect($results)->toContain($pending->id)
        ->not->toContain($completed->id);
});

test('overdue scope includes only overdue non-completed requests', function () {
    $overdue = RightsRequest::factory()->create([
        'status' => 'pending',
        'deadline_at' => now()->subDay(),
    ]);
    $notOverdue = RightsRequest::factory()->create([
        'status' => 'pending',
        'deadline_at' => now()->addDays(5),
    ]);
    $completedPast = RightsRequest::factory()->create([
        'status' => 'completed',
        'deadline_at' => now()->subDay(),
    ]);

    $results = RightsRequest::overdue()->pluck('id');

    expect($results)->toContain($overdue->id)
        ->not->toContain($notOverdue->id)
        ->not->toContain($completedPast->id);
});

test('mark completed updates status and responded_at', function () {
    $request = RightsRequest::factory()->create(['status' => 'pending']);

    $request->markCompleted();

    expect($request->status)->toBe('completed')
        ->and($request->responded_at)->not->toBeNull();
});

test('is overdue returns true for past deadline pending request', function () {
    $request = RightsRequest::factory()->create([
        'status' => 'pending',
        'deadline_at' => now()->subDay(),
    ]);
    expect($request->isOverdue())->toBeTrue();
});

test('is overdue returns false for future deadline', function () {
    $request = RightsRequest::factory()->create([
        'deadline_at' => now()->addDays(5),
    ]);
    expect($request->isOverdue())->toBeFalse();
});

// ── Consent API ────────────────────────────────────────────────────

test('POST consent with valid data returns 201 and token', function () {
    $response = $this->postJson('/api/privacy/consent', [
        'choices' => [
            'essential' => true,
            'analytics' => false,
            'marketing' => true,
        ],
        'jurisdiction' => 'pipeda',
        'policy_version' => '1.0',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['success', 'token', 'expires_at']);

    $this->assertDatabaseHas('user_consents', [
        'jurisdiction' => 'pipeda',
        'policy_version' => '1.0',
    ]);
});

test('POST consent with GPC header disables marketing and third_party for GDPR', function () {
    $response = $this->withHeaders(['Sec-GPC' => '1'])
        ->postJson('/api/privacy/consent', [
            'choices' => [
                'essential' => true,
                'analytics' => true,
                'marketing' => true,
                'third_party' => true,
            ],
            'jurisdiction' => 'gdpr',
            'policy_version' => '1.0',
        ]);

    $response->assertStatus(201);

    $consent = UserConsent::latest('id')->first();

    expect($consent->gpc_enabled)->toBeTrue()
        ->and($consent->choices['essential'])->toBeTrue()
        ->and($consent->choices['analytics'])->toBeTrue()
        ->and($consent->choices['marketing'])->toBeFalse()
        ->and($consent->choices['third_party'])->toBeFalse();
});

test('POST consent with invalid jurisdiction returns 422', function () {
    $response = $this->postJson('/api/privacy/consent', [
        'choices' => ['essential' => true],
        'jurisdiction' => 'invalid',
        'policy_version' => '1.0',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['jurisdiction']);
});

test('GET consent by token returns choices', function () {
    $consent = UserConsent::factory()->create([
        'choices' => ['essential' => true, 'analytics' => false],
        'expires_at' => now()->addDays(30),
    ]);

    $response = $this->getJson("/api/privacy/consent/{$consent->consent_token}");

    $response->assertStatus(200)
        ->assertJson([
            'choices' => ['essential' => true, 'analytics' => false],
        ]);
});

test('GET expired consent returns 404', function () {
    $consent = UserConsent::factory()->create([
        'expires_at' => now()->subDay(),
    ]);

    $response = $this->getJson("/api/privacy/consent/{$consent->consent_token}");

    $response->assertStatus(404);
});

test('GET cookie-list returns categories config', function () {
    $response = $this->getJson('/api/privacy/cookie-list');

    $response->assertStatus(200)
        ->assertJsonStructure(['essential', 'analytics', 'marketing']);
});

// ── Rights Request API ─────────────────────────────────────────────

test('POST valid rights request returns 201 and reference', function () {
    $response = $this->postJson('/api/privacy/rights-request', [
        'name' => 'Jean Dupont',
        'email' => 'jean@example.com',
        'request_type' => 'access',
        'description' => 'Je souhaite accéder à mes données personnelles.',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['success', 'reference', 'deadline_at']);

    $this->assertDatabaseHas('rights_requests', [
        'email' => 'jean@example.com',
        'request_type' => 'access',
    ]);
});

test('POST rights request with missing fields returns 422', function () {
    $response = $this->postJson('/api/privacy/rights-request', [
        'name' => 'Jean Dupont',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'request_type', 'description']);
});

test('POST rights request with invalid type returns 422', function () {
    $response = $this->postJson('/api/privacy/rights-request', [
        'name' => 'Jean Dupont',
        'email' => 'jean@example.com',
        'request_type' => 'invalid_type',
        'description' => 'Test description.',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['request_type']);
});

// ── Legal pages ────────────────────────────────────────────────────

test('GET /privacy-policy returns 200', function () {
    $this->get('/privacy-policy')->assertStatus(200);
});

test('GET /terms-of-use returns 200', function () {
    $this->get('/terms-of-use')->assertStatus(200);
});

test('GET /cookie-policy returns 200', function () {
    $this->get('/cookie-policy')->assertStatus(200);
});

// ── Consent cookie ─────────────────────────────────────────────────

test('POST consent sets cookie with configured name', function () {
    $response = $this->postJson('/api/privacy/consent', [
        'choices' => ['essential' => true],
        'jurisdiction' => 'gdpr',
        'policy_version' => '1.0',
    ]);

    $response->assertStatus(201);
    $response->assertCookie(config('privacy.consent.cookie_name', 'consent_v1'));
});

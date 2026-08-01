<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\Health\Checks\OpcacheCheck;
use Modules\Health\Http\Controllers\OpcacheStatusController;
use Spatie\Health\Enums\Status;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    config()->set('health.opcache', [
        'enabled' => true,
        'path' => '_sante/opcache',
        'token' => 'jeton-secret',
        'timeout' => 5,
        'warn_keys_percent' => 75,
        'fail_keys_percent' => 90,
        'warn_memory_percent' => 75,
        'fail_memory_percent' => 90,
        'warn_interned_percent' => 80,
        'fail_interned_percent' => 95,
        'warn_refusals_delta' => 100,
        'fail_refusals_delta' => 1000,
        'refusals_cache_key' => 'tests:health:opcache:refusals',
    ]);

    Cache::forget('tests:health:opcache:refusals');
});

function opcachePayload(array $overrides = []): array
{
    return array_replace_recursive([
        'sapi' => 'fpm-fcgi',
        'memory_usage' => [
            'used_memory' => 20,
            'free_memory' => 75,
            'wasted_memory' => 5,
            'current_wasted_percentage' => 5.0,
        ],
        'opcache_statistics' => [
            'num_cached_scripts' => 1000,
            'num_cached_keys' => 2000,
            'max_cached_keys' => 10000,
            'hits' => 50000,
            'misses' => 1010,
            'oom_restarts' => 0,
            'hash_restarts' => 0,
            'manual_restarts' => 0,
        ],
        'interned_strings_usage' => [
            'buffer_size' => 100,
            'used_memory' => 20,
            'free_memory' => 80,
            'number_of_strings' => 1000,
        ],
        'cache_full' => false,
    ], $overrides);
}

it('retourne 404 sans jeton', function () {
    $this->get(route('health.opcache.status'))->assertNotFound();
});

it('retourne 404 avec un mauvais jeton', function () {
    $this->get(route('health.opcache.status', ['token' => 'mauvais']))->assertNotFound();
});

it('retourne 404 lorsque la surveillance est désactivée', function () {
    config()->set('health.opcache.enabled', false);

    $this->get(route('health.opcache.status', ['token' => 'jeton-secret']))->assertNotFound();
});

it('retourne le statut OPcache avec le bon jeton', function () {
    $controller = Mockery::mock(OpcacheStatusController::class)->makePartial();
    $controller->shouldReceive('readOpcacheStatus')->once()->andReturn(opcachePayload());
    app()->instance(OpcacheStatusController::class, $controller);

    $this->getJson(route('health.opcache.status', ['token' => 'jeton-secret']))
        ->assertOk()
        ->assertJsonPath('sapi', PHP_SAPI)
        ->assertJsonPath('opcache_statistics.num_cached_scripts', 1000);
});

it('échoue lorsque la requête HTTP échoue', function () {
    Http::fake(['*' => Http::response('Indisponible', 503)]);

    $result = OpcacheCheck::new()->run();

    expect($result->status->equals(Status::failed()))->toBeTrue()
        ->and($result->getNotificationMessage())->toContain('Impossible de mesurer OPcache');
});

it('avertit lorsque les clés dépassent seules leur seuil', function () {
    Http::fake(['*' => Http::response(opcachePayload([
        'opcache_statistics' => ['num_cached_keys' => 8000],
    ]))]);

    expect(OpcacheCheck::new()->run()->status->equals(Status::warning()))->toBeTrue();
});

it('échoue lorsque la mémoire dépasse seule son seuil', function () {
    Http::fake(['*' => Http::response(opcachePayload([
        'memory_usage' => ['used_memory' => 91, 'free_memory' => 8, 'wasted_memory' => 1],
    ]))]);

    expect(OpcacheCheck::new()->run()->status->equals(Status::failed()))->toBeTrue();
});

it('avertit lorsque les chaînes internées dépassent seules leur seuil', function () {
    Http::fake(['*' => Http::response(opcachePayload([
        'interned_strings_usage' => ['used_memory' => 85, 'free_memory' => 15],
    ]))]);

    expect(OpcacheCheck::new()->run()->status->equals(Status::warning()))->toBeTrue();
});

it('échoue immédiatement lorsque le cache est plein', function () {
    Http::fake(['*' => Http::response(opcachePayload(['cache_full' => true]))]);

    expect(OpcacheCheck::new()->run()->status->equals(Status::failed()))->toBeTrue();
});

it('ne déclenche aucun signal sous les seuils', function () {
    Http::fake(['*' => Http::response(opcachePayload())]);

    $result = OpcacheCheck::new()->run();

    expect($result->status->equals(Status::ok()))->toBeTrue()
        ->and($result->shortSummary)->toContain('clés 20.0 %');
});

it('calcule la progression des refus entre deux passages', function () {
    Http::fakeSequence()
        ->push(opcachePayload(), 200)
        ->push(opcachePayload(['opcache_statistics' => ['misses' => 1211]]), 200);

    $first = OpcacheCheck::new()->run();
    $second = OpcacheCheck::new()->run();

    expect($first->meta['refusals_delta'])->toBe(0)
        ->and($second->meta['refusals_delta'])->toBe(201)
        ->and($second->status->equals(Status::warning()))->toBeTrue();
});

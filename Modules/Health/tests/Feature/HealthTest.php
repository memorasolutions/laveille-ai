<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('health check endpoint returns ok', function () {
    $this->get('/health')->assertOk();
});

test('health checks are registered', function () {
    $checks = \Spatie\Health\Facades\Health::registeredChecks();

    expect($checks)->not->toBeEmpty();
});

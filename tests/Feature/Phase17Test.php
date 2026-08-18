<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Support\Facades\Config;

test('sanctum config exists with expiration', function () {
    expect(Config::get('sanctum'))->not->toBeNull();
    expect(Config::get('sanctum.expiration'))->not->toBeNull();
});

test('health checks include cache and schedule', function () {
    $content = file_get_contents(base_path('Modules/Health/app/Providers/HealthCheckServiceProvider.php'));
    expect($content)->toContain('CacheCheck');
    expect($content)->toContain('ScheduleCheck');
});

test('sync permissions command exists', function () {
    expect(class_exists(Modules\RolesPermissions\Console\SyncPermissionsCommand::class))->toBeTrue();
});

test('readme is customized for core template', function () {
    $content = file_get_contents(base_path('README.md'));
    // Croissance légitime, valeur actualisée le 2026-08-18 (triage tests hérités) : README réécrit.
    expect($content)->toContain('Laravel SaaS CORE Template');
});

test('sanctum token prefix is configured', function () {
    expect(Config::get('sanctum.token_prefix'))->toBe('core_');
});

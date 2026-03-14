<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */
test('core:new-project command is registered', function () {
    expect(class_exists(\Modules\Core\Console\NewProjectCommand::class))->toBeTrue();

    $this->artisan('core:new-project --help')
        ->assertSuccessful();
});

test('larastan is configured at level 6', function () {
    $config = file_get_contents(base_path('phpstan.neon'));

    expect($config)->toContain('level: 6');
});

test('.env.example contains all required keys', function () {
    $envExample = file_get_contents(base_path('.env.example'));

    expect($envExample)->toContain('STRIPE_KEY=');
    expect($envExample)->toContain('STRIPE_SECRET=');
    expect($envExample)->toContain('SAAS_CURRENCY=');
    expect($envExample)->toContain('TENANCY_IDENTIFICATION=');
    expect($envExample)->toContain('SENTRY_LARAVEL_DSN=');
    expect($envExample)->toContain('SANCTUM_STATEFUL_DOMAINS=');
});

test('ci workflow exists with coverage', function () {
    $ci = file_get_contents(base_path('.github/workflows/ci.yml'));

    expect($ci)->toContain('coverage: pcov');
    expect($ci)->toContain('--coverage');
    expect($ci)->toContain('--min=80');
});

test('scripts/new-project.sh is executable', function () {
    $path = base_path('scripts/new-project.sh');

    expect(file_exists($path))->toBeTrue();
    expect(is_executable($path))->toBeTrue();
});

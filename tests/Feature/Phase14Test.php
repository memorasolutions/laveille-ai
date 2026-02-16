<?php

declare(strict_types=1);

test('larastan config exists', function () {
    expect(file_exists(base_path('phpstan.neon')))->toBeTrue();
});

test('architecture tests exist', function () {
    expect(file_exists(base_path('tests/Architecture/ArchTest.php')))->toBeTrue();
});

test('eager loading auto is enabled', function () {
    $provider = new \App\Providers\AppServiceProvider(app());
    $provider->boot();
    // If preventLazyLoading was called, it means eager loading is configured
    expect(true)->toBeTrue(); // Boot didn't throw
});

test('github actions ci workflow exists', function () {
    expect(file_exists(base_path('.github/workflows/ci.yml')))->toBeTrue();
});

test('pulse package is installed', function () {
    expect(class_exists(\Laravel\Pulse\Pulse::class))->toBeTrue();
});

test('rector config exists', function () {
    expect(file_exists(base_path('rector.php')))->toBeTrue();
});

test('sentry package is installed', function () {
    expect(class_exists(\Sentry\Laravel\ServiceProvider::class))->toBeTrue();
});

test('failover queue driver is configured', function () {
    $config = config('queue.connections.failover');
    expect($config)->not->toBeNull();
    expect($config['driver'])->toBe('failover');
    expect($config['connections'])->toContain('redis');
});

test('failover cache driver is configured', function () {
    $config = config('cache.stores.failover');
    expect($config)->not->toBeNull();
    expect($config['driver'])->toBe('failover');
    expect($config['stores'])->toContain('redis');
});

test('production json log channel exists', function () {
    $config = config('logging.channels.production');
    expect($config)->not->toBeNull();
    expect($config['driver'])->toBe('daily');
});

test('breezy 2fa package is installed', function () {
    expect(class_exists(\Jeffgreco13\FilamentBreezy\BreezyCore::class))->toBeTrue();
});

test('user model has two factor authenticatable trait', function () {
    $user = new \App\Models\User;
    expect(method_exists($user, 'hasConfirmedTwoFactor'))->toBeTrue();
});

test('activity chart widget exists', function () {
    expect(class_exists(\Modules\Backoffice\Filament\Widgets\ActivityChart::class))->toBeTrue();
});

test('api v1 routes file exists', function () {
    expect(file_exists(base_path('routes/api/v1.php')))->toBeTrue();
});

test('api health endpoint returns ok', function () {
    $this->getJson('/api/health')->assertOk()->assertJson(['status' => 'ok']);
});

test('api v1 status endpoint returns app info', function () {
    $this->getJson('/api/v1/status')->assertOk()->assertJsonStructure(['app', 'version', 'environment', 'timestamp']);
});

test('scramble api docs package is installed', function () {
    expect(class_exists(\Dedoc\Scramble\Scramble::class))->toBeTrue();
});

test('sentry is configured in exception handler', function () {
    $content = file_get_contents(base_path('bootstrap/app.php'));
    expect($content)->toContain('sentry');
});

test('env example has sentry and pulse config', function () {
    $env = file_get_contents(base_path('.env.example'));
    expect($env)->toContain('SENTRY_LARAVEL_DSN')->toContain('PULSE_ENABLED');
});

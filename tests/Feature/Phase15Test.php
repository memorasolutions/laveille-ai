<?php

declare(strict_types=1);

test('error page 503 exists', function () {
    expect(file_exists(resource_path('views/errors/503.blade.php')))->toBeTrue();
});

test('cors config exists', function () {
    expect(config('cors'))->not->toBeNull()
        ->and(config('cors.paths'))->toContain('api/*');
});

test('database seeder has admin user creation', function () {
    $content = file_get_contents(base_path('database/seeders/DatabaseSeeder.php'));
    expect($content)->toContain('app.admin_email')
        ->toContain('moderator@laravel-core.test')
        ->toContain('super_admin')
        ->toContain('RolesAndPermissionsSeeder');
});

test('welcome mail class exists', function () {
    expect(class_exists(\Modules\Notifications\Mail\WelcomeMail::class))->toBeTrue();
});

test('welcome notification class exists', function () {
    expect(class_exists(\Modules\Notifications\Notifications\WelcomeNotification::class))->toBeTrue();
});

test('docker compose file exists', function () {
    expect(file_exists(base_path('docker-compose.yml')))->toBeTrue();
});

test('dockerfile exists', function () {
    expect(file_exists(base_path('docker/php/Dockerfile')))->toBeTrue();
});

test('nginx config exists', function () {
    expect(file_exists(base_path('docker/nginx/default.conf')))->toBeTrue();
});

test('rate limiting is configured', function () {
    expect(\Illuminate\Support\Facades\RateLimiter::limiter('api'))->not->toBeNull()
        ->and(\Illuminate\Support\Facades\RateLimiter::limiter('login'))->not->toBeNull();
});

test('pennant features are defined', function () {
    $provider = new \App\Providers\AppServiceProvider(app());
    $provider->boot();
    expect(\Laravel\Pennant\Feature::defined())->toContain('module-saas');
});

test('welcome email view exists', function () {
    expect(file_exists(resource_path('views/emails/welcome.blade.php')))->toBeTrue();
});

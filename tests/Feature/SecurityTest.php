<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Support\Facades\RateLimiter;
use Modules\Core\Http\Middleware\ContentSecurityPolicy;
use Modules\Core\Http\Middleware\ForceHttps;
use Modules\Core\Http\Middleware\SecurityHeaders;

test('security headers middleware exists', function () {
    expect(class_exists(SecurityHeaders::class))->toBeTrue();
});

test('content security policy middleware exists', function () {
    expect(class_exists(ContentSecurityPolicy::class))->toBeTrue();
});

test('force https middleware exists', function () {
    expect(class_exists(ForceHttps::class))->toBeTrue();
});

test('csp middleware sets header with nonce', function () {
    $middleware = new ContentSecurityPolicy;

    $request = \Illuminate\Http\Request::create('/test');
    $response = $middleware->handle($request, fn ($req) => new \Illuminate\Http\Response('OK'));

    expect($response->headers->get('Content-Security-Policy'))->toContain("default-src 'self'");
    expect($response->headers->get('Content-Security-Policy'))->toContain('nonce-');
});

test('rate limiter api is configured', function () {
    expect(RateLimiter::limiter('api'))->not->toBeNull();
});

test('rate limiter login is configured', function () {
    expect(RateLimiter::limiter('login'))->not->toBeNull();
});

test('rate limiter sensitive is configured', function () {
    expect(RateLimiter::limiter('sensitive'))->not->toBeNull();
});

// Phase 24 - Corrections sécurité

test('password rules include PasswordPolicyRule', function () {
    $rules = (new \Modules\Auth\Http\Requests\StoreUserRequest)->rules();

    $hasPasswordRule = collect($rules['password'])->contains(
        fn ($rule) => $rule instanceof \Modules\Auth\Rules\PasswordPolicyRule
    );

    expect($hasPasswordRule)->toBeTrue();
});

test('models use fillable for mass assignment protection', function () {
    $modelFiles = [
        base_path('app/Models/User.php'),
        base_path('Modules/SEO/app/Models/MetaTag.php'),
        base_path('Modules/Settings/app/Models/Setting.php'),
    ];

    foreach ($modelFiles as $file) {
        $content = file_get_contents($file);
        expect($content)->toContain('fillable');
    }
});

test('api routes require sanctum authentication', function () {
    $content = file_get_contents(base_path('routes/api/v1.php'));
    expect($content)->toContain('auth:sanctum');
});

test('debug mode defaults to false in config', function () {
    $content = file_get_contents(base_path('config/app.php'));
    expect($content)->toContain("env('APP_DEBUG', false)");
});

test('honeypot middleware exists', function () {
    expect(class_exists(\App\Http\Middleware\HoneypotProtection::class))->toBeTrue();
});

test('honeypot middleware rejects filled bot field', function () {
    $middleware = new \App\Http\Middleware\HoneypotProtection;
    $request = \Illuminate\Http\Request::create('/test', 'POST', ['website_url' => 'spam']);

    expect(fn () => $middleware->handle($request, fn ($r) => new \Illuminate\Http\Response('OK')))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
});

test('honeypot middleware allows empty bot field', function () {
    $middleware = new \App\Http\Middleware\HoneypotProtection;
    $request = \Illuminate\Http\Request::create('/test', 'POST', ['website_url' => '']);

    $response = $middleware->handle($request, fn ($r) => new \Illuminate\Http\Response('OK'));
    expect($response->getStatusCode())->toBe(200);
});

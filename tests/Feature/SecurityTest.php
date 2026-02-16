<?php

declare(strict_types=1);

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

<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Core\Http\Middleware\ContentSecurityPolicy;
use Modules\Core\Http\Middleware\ForceHttps;
use Modules\Core\Http\Middleware\ForceJsonResponse;

test('ForceJsonResponse sets accept header to json', function () {
    $middleware = new ForceJsonResponse;
    $request = Request::create('/api/test', 'GET');

    $middleware->handle($request, function ($req) {
        expect($req->headers->get('Accept'))->toBe('application/json');

        return new Response('OK');
    });
});

test('ForceHttps redirects in production', function () {
    app()->detectEnvironment(fn () => 'production');

    $middleware = new ForceHttps;
    $request = Request::create('http://example.com/test', 'GET');

    $response = $middleware->handle($request, function () {
        return new Response('OK');
    });

    expect($response->getStatusCode())->toBe(301);

    app()->detectEnvironment(fn () => 'testing');
});

test('ForceHttps does not redirect in non-production', function () {
    $middleware = new ForceHttps;
    $request = Request::create('http://example.com/test', 'GET');

    $response = $middleware->handle($request, function () {
        return new Response('OK');
    });

    expect($response->getStatusCode())->toBe(200);
});

test('ContentSecurityPolicy adds CSP header with nonce', function () {
    $middleware = new ContentSecurityPolicy;
    $request = Request::create('/test', 'GET');

    $response = $middleware->handle($request, function () {
        return new Response('OK');
    });

    $csp = $response->headers->get('Content-Security-Policy');
    expect($csp)->toContain("default-src 'self'")
        ->toContain('script-src')
        ->toContain('nonce-');
});

test('ContentSecurityPolicy sets nonce on request', function () {
    $middleware = new ContentSecurityPolicy;
    $request = Request::create('/test', 'GET');

    $middleware->handle($request, function ($req) {
        expect($req->attributes->get('csp-nonce'))->not->toBeNull();

        return new Response('OK');
    });
});

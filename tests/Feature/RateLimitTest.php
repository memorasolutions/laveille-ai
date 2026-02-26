<?php

declare(strict_types=1);

use Illuminate\Support\Facades\RateLimiter;

test('api rate limiter is configured', function () {
    expect(RateLimiter::limiter('api'))->not->toBeNull();
});

test('login rate limiter is configured', function () {
    expect(RateLimiter::limiter('login'))->not->toBeNull();
});

test('sensitive rate limiter is configured', function () {
    expect(RateLimiter::limiter('sensitive'))->not->toBeNull();
});

test('login route has throttle middleware', function () {
    $routes = collect(app('router')->getRoutes()->getRoutes());
    $loginRoute = $routes->first(fn ($r) => $r->uri() === 'api/v1/login');

    expect($loginRoute)->not->toBeNull();
    $middleware = $loginRoute->gatherMiddleware();
    expect($middleware)->toContain('throttle:login');
});

test('register route has throttle middleware', function () {
    $routes = collect(app('router')->getRoutes()->getRoutes());
    $registerRoute = $routes->first(fn ($r) => $r->uri() === 'api/v1/register');

    expect($registerRoute)->not->toBeNull();
    $middleware = $registerRoute->gatherMiddleware();
    expect($middleware)->toContain('throttle:login');
});

test('export rate limiter is configured', function () {
    expect(RateLimiter::limiter('export'))->not->toBeNull();
});

test('import rate limiter is configured', function () {
    expect(RateLimiter::limiter('import'))->not->toBeNull();
});

test('search rate limiter is configured', function () {
    expect(RateLimiter::limiter('search'))->not->toBeNull();
});

test('newsletter rate limiter is configured', function () {
    expect(RateLimiter::limiter('newsletter'))->not->toBeNull();
});

test('export routes have throttle middleware', function () {
    $routes = collect(app('router')->getRoutes()->getRoutes());
    $exportRoute = $routes->first(fn ($r) => $r->getName() === 'admin.export.users');

    expect($exportRoute)->not->toBeNull();
    expect($exportRoute->gatherMiddleware())->toContain('throttle:export');
});

test('import routes have throttle middleware', function () {
    $routes = collect(app('router')->getRoutes()->getRoutes());
    $importRoute = $routes->first(fn ($r) => $r->getName() === 'admin.import.users.store');

    expect($importRoute)->not->toBeNull();
    expect($importRoute->gatherMiddleware())->toContain('throttle:import');
});

test('search route has throttle middleware', function () {
    $routes = collect(app('router')->getRoutes()->getRoutes());
    $searchRoute = $routes->first(fn ($r) => $r->getName() === 'admin.search');

    expect($searchRoute)->not->toBeNull();
    expect($searchRoute->gatherMiddleware())->toContain('throttle:search');
});

test('newsletter subscribe route has throttle middleware', function () {
    $routes = collect(app('router')->getRoutes()->getRoutes());
    $route = $routes->first(fn ($r) => $r->uri() === 'api/v1/newsletter/subscribe');

    expect($route)->not->toBeNull();
    expect($route->gatherMiddleware())->toContain('throttle:newsletter');
});

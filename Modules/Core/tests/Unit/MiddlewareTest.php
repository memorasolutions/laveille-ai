<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Core\Http\Middleware\SanitizeInput;
use Modules\Core\Http\Middleware\SecurityHeaders;

uses(Tests\TestCase::class);

test('SecurityHeaders adds security headers', function () {
    $middleware = new SecurityHeaders;
    $request = Request::create('/test', 'GET');

    $response = $middleware->handle($request, function () {
        return new Response('OK');
    });

    expect($response->headers->get('X-Content-Type-Options'))->toBe('nosniff');
    expect($response->headers->get('X-Frame-Options'))->toBe('SAMEORIGIN');
    expect($response->headers->get('X-XSS-Protection'))->toBe('1; mode=block');
    expect($response->headers->get('Referrer-Policy'))->toBe('strict-origin-when-cross-origin');
});

test('SanitizeInput strips tags from input', function () {
    $middleware = new SanitizeInput;
    $request = Request::create('/test', 'POST', [
        'name' => '<script>alert("xss")</script>John',
        'password' => '<b>secret</b>',
    ]);

    $middleware->handle($request, function ($req) {
        expect($req->input('name'))->toBe('alert("xss")John');
        expect($req->input('password'))->toBe('<b>secret</b>');

        return new Response('OK');
    });
});

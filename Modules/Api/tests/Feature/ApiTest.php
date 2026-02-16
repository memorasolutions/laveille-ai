<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Api\Http\Controllers\BaseApiController;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('base api controller exists', function () {
    expect(class_exists(BaseApiController::class))->toBeTrue();
});

test('base api controller success response format', function () {
    $controller = new BaseApiController;
    $method = new ReflectionMethod($controller, 'respondSuccess');

    $response = $method->invoke($controller, ['key' => 'value'], 'OK', 200);

    expect($response->getStatusCode())->toBe(200);

    $data = json_decode($response->getContent(), true);
    expect($data['success'])->toBeTrue();
    expect($data['message'])->toBe('OK');
    expect($data['data'])->toBe(['key' => 'value']);
});

test('base api controller error response format', function () {
    $controller = new BaseApiController;
    $method = new ReflectionMethod($controller, 'respondError');

    $response = $method->invoke($controller, 'Bad request', 400, ['field' => 'required']);

    expect($response->getStatusCode())->toBe(400);

    $data = json_decode($response->getContent(), true);
    expect($data['success'])->toBeFalse();
    expect($data['message'])->toBe('Bad request');
    expect($data['errors'])->toBe(['field' => 'required']);
});

test('base api controller created response', function () {
    $controller = new BaseApiController;
    $method = new ReflectionMethod($controller, 'respondCreated');

    $response = $method->invoke($controller, ['id' => 1]);

    expect($response->getStatusCode())->toBe(201);
});

test('query builder package is available', function () {
    expect(class_exists(\Spatie\QueryBuilder\QueryBuilder::class))->toBeTrue();
});

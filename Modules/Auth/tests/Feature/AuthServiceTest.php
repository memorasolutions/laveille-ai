<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Services\AuthService;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('auth service can register a user', function () {
    $service = app(AuthService::class);

    $user = $service->register([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password123',
    ]);

    expect($user)->toBeInstanceOf(User::class);
    expect($user->name)->toBe('Test User');
    expect($user->email)->toBe('test@example.com');
    expect(Hash::check('Password123', $user->password))->toBeTrue();
});

test('auth service can authenticate with valid credentials', function () {
    $service = app(AuthService::class);

    User::factory()->create([
        'email' => 'auth@test.com',
        'password' => Hash::make('SecurePass1'),
    ]);

    expect($service->authenticate('auth@test.com', 'SecurePass1'))->toBeTrue();
});

test('auth service fails authentication with wrong password', function () {
    $service = app(AuthService::class);

    User::factory()->create([
        'email' => 'wrong@test.com',
        'password' => Hash::make('CorrectPass1'),
    ]);

    expect($service->authenticate('wrong@test.com', 'WrongPass1'))->toBeFalse();
});

test('auth service can send password reset link', function () {
    $service = app(AuthService::class);

    User::factory()->create(['email' => 'reset@test.com']);

    $status = $service->sendPasswordResetLink('reset@test.com');

    expect($status)->toBeString();
});

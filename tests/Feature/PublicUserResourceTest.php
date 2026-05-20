<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Http\Resources\PublicUserResource;

uses(RefreshDatabase::class);

test('public user resource exposes only id and name', function () {
    $user = User::factory()->create();

    $data = (new PublicUserResource($user))->toArray(request());

    expect(array_keys($data))->toBe(['id', 'name'])
        ->and($data['id'])->toBe($user->id)
        ->and($data['name'])->toBe($user->name);
});

test('public user resource never exposes email or roles', function () {
    $user = User::factory()->create();

    $data = (new PublicUserResource($user))->toArray(request());

    expect($data)->not->toHaveKey('email')
        ->and($data)->not->toHaveKey('roles')
        ->and($data)->not->toHaveKey('email_verified_at');
});

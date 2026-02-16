<?php

declare(strict_types=1);

use App\Models\User;
use Modules\Core\Contracts\UserInterface;
use Modules\Core\Events\UserCreated;
use Modules\Core\Events\UserDeleted;
use Modules\Core\Events\UserUpdated;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

test('User model implements UserInterface', function () {
    $user = User::factory()->create();

    expect($user)->toBeInstanceOf(UserInterface::class);
    expect($user->getName())->toBe($user->name);
    expect($user->getEmail())->toBe($user->email);
    expect($user->getKey())->toBe($user->id);
});

test('UserInterface is bound in container', function () {
    $resolved = app(UserInterface::class);

    expect($resolved)->toBe(User::class);
});

test('Core events can be instantiated with UserInterface', function () {
    $user = User::factory()->create();

    $created = new UserCreated($user);
    $updated = new UserUpdated($user);
    $deleted = new UserDeleted($user);

    expect($created->user)->toBeInstanceOf(UserInterface::class);
    expect($updated->user)->toBeInstanceOf(UserInterface::class);
    expect($deleted->user)->toBeInstanceOf(UserInterface::class);
});

test('Core events are dispatchable', function () {
    $user = User::factory()->create();

    Event::fake([UserCreated::class, UserUpdated::class, UserDeleted::class]);

    UserCreated::dispatch($user);
    UserUpdated::dispatch($user);
    UserDeleted::dispatch($user);

    Event::assertDispatched(UserCreated::class);
    Event::assertDispatched(UserUpdated::class);
    Event::assertDispatched(UserDeleted::class);
});

<?php

declare(strict_types=1);

test('user resource exists and returns correct structure', function () {
    expect(class_exists(Modules\Auth\Http\Resources\UserResource::class))->toBeTrue();
    $reflection = new ReflectionClass(Modules\Auth\Http\Resources\UserResource::class);
    expect($reflection->isSubclassOf(Illuminate\Http\Resources\Json\JsonResource::class))->toBeTrue();
    expect($reflection->hasMethod('toArray'))->toBeTrue();
});

test('user policy exists with authorization methods', function () {
    expect(class_exists(Modules\Auth\Policies\UserPolicy::class))->toBeTrue();
    $reflection = new ReflectionClass(Modules\Auth\Policies\UserPolicy::class);
    expect($reflection->hasMethod('viewAny'))->toBeTrue();
    expect($reflection->hasMethod('view'))->toBeTrue();
    expect($reflection->hasMethod('create'))->toBeTrue();
    expect($reflection->hasMethod('update'))->toBeTrue();
    expect($reflection->hasMethod('delete'))->toBeTrue();
});

test('user observer exists with model event methods', function () {
    expect(class_exists(Modules\Auth\Observers\UserObserver::class))->toBeTrue();
    $reflection = new ReflectionClass(Modules\Auth\Observers\UserObserver::class);
    expect($reflection->hasMethod('created'))->toBeTrue();
    expect($reflection->hasMethod('updated'))->toBeTrue();
    expect($reflection->hasMethod('deleted'))->toBeTrue();
    expect($reflection->implementsInterface(Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit::class))->toBeTrue();
});

test('user observer is registered on user model', function () {
    $content = file_get_contents(app_path('Models/User.php'));
    expect($content)->toContain('#[ObservedBy(UserObserver::class)]');
});

test('user created event exists in Core module', function () {
    expect(class_exists(Modules\Core\Events\UserCreated::class))->toBeTrue();
    $reflection = new ReflectionClass(Modules\Core\Events\UserCreated::class);
    $constructor = $reflection->getConstructor();
    expect($constructor)->not->toBeNull();
    $params = $constructor->getParameters();
    expect($params[0]->getName())->toBe('user');
});

test('send welcome notification listener exists in Auth module', function () {
    expect(class_exists(Modules\Auth\Listeners\SendWelcomeNotification::class))->toBeTrue();
    $reflection = new ReflectionClass(Modules\Auth\Listeners\SendWelcomeNotification::class);
    expect($reflection->hasMethod('handle'))->toBeTrue();
    $params = $reflection->getMethod('handle')->getParameters();
    expect($params[0]->getType()->getName())->toBe(Modules\Core\Events\UserCreated::class);
});

test('process user export job exists and is queueable', function () {
    expect(class_exists(Modules\Auth\Jobs\ProcessUserExport::class))->toBeTrue();
    $reflection = new ReflectionClass(Modules\Auth\Jobs\ProcessUserExport::class);
    expect($reflection->implementsInterface(Illuminate\Contracts\Queue\ShouldQueue::class))->toBeTrue();
    expect($reflection->hasProperty('tries'))->toBeTrue();
    expect($reflection->hasProperty('backoff'))->toBeTrue();
});

test('api v1 user route uses auth controller', function () {
    $content = file_get_contents(base_path('routes/api/v1.php'));
    expect($content)->toContain('AuthController');
});

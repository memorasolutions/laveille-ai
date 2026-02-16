<?php

declare(strict_types=1);

test('force json response middleware exists', function () {
    expect(class_exists(Modules\Core\Http\Middleware\ForceJsonResponse::class))->toBeTrue();
});

test('force json middleware is registered as alias', function () {
    $content = file_get_contents(base_path('bootstrap/app.php'));
    expect($content)->toContain("'force-json'")
        ->toContain('ForceJsonResponse');
});

test('api routes use force-json middleware', function () {
    $content = file_get_contents(base_path('routes/api.php'));
    expect($content)->toContain('force-json');
});

test('store user request exists with validation rules', function () {
    expect(class_exists(Modules\Auth\Http\Requests\StoreUserRequest::class))->toBeTrue();
    $reflection = new ReflectionClass(Modules\Auth\Http\Requests\StoreUserRequest::class);
    expect($reflection->hasMethod('rules'))->toBeTrue();
    expect($reflection->hasMethod('messages'))->toBeTrue();
    expect($reflection->isSubclassOf(Modules\Core\Http\Requests\BaseFormRequest::class))->toBeTrue();
});

test('update user request exists with validation rules', function () {
    expect(class_exists(Modules\Auth\Http\Requests\UpdateUserRequest::class))->toBeTrue();
    $reflection = new ReflectionClass(Modules\Auth\Http\Requests\UpdateUserRequest::class);
    expect($reflection->hasMethod('rules'))->toBeTrue();
    expect($reflection->hasMethod('messages'))->toBeTrue();
    expect($reflection->isSubclassOf(Modules\Core\Http\Requests\BaseFormRequest::class))->toBeTrue();
});

test('api exception handler renders json for api routes', function () {
    $content = file_get_contents(base_path('bootstrap/app.php'));
    expect($content)->toContain('ValidationException')
        ->toContain('AuthenticationException')
        ->toContain('NotFoundHttpException')
        ->toContain('AccessDeniedHttpException')
        ->toContain('TooManyRequestsHttpException');
});

test('event listener binding is registered in Auth module', function () {
    $content = file_get_contents(base_path('Modules/Auth/app/Providers/AuthServiceProvider.php'));
    expect($content)->toContain('Event::listen(UserCreated::class, SendWelcomeNotification::class)');
});

test('reset password email template exists', function () {
    expect(file_exists(resource_path('views/emails/reset-password.blade.php')))->toBeTrue();
    $content = file_get_contents(resource_path('views/emails/reset-password.blade.php'));
    expect($content)->toContain('mail::message')
        ->toContain('mail::button');
});

test('verify email template exists', function () {
    expect(file_exists(resource_path('views/emails/verify-email.blade.php')))->toBeTrue();
    $content = file_get_contents(resource_path('views/emails/verify-email.blade.php'));
    expect($content)->toContain('mail::message')
        ->toContain('mail::button');
});

test('api not found returns json', function () {
    $response = $this->getJson('/api/v1/nonexistent-route');
    $response->assertStatus(404)
        ->assertJson(['success' => false]);
});

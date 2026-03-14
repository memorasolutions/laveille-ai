<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Modules\Auth\Http\Requests\StoreUserRequest;
use Modules\Auth\Http\Requests\UpdateUserRequest;
use Modules\Auth\Policies\UserPolicy;

test('StoreUserRequest requires name', function () {
    $request = new StoreUserRequest;
    $rules = $request->rules();
    expect($rules['name'])->toContain('required');
});

test('StoreUserRequest requires email', function () {
    $request = new StoreUserRequest;
    $rules = $request->rules();
    expect($rules['email'])->toContain('required');
});

test('StoreUserRequest requires password with complexity rules', function () {
    $request = new StoreUserRequest;
    $rules = $request->rules();
    expect($rules['password'])->toContain('required')
        ->toContain('string')
        ->toContain('confirmed');

    // Vérifie qu'un objet PasswordPolicyRule est présent (complexité)
    $hasPasswordRule = collect($rules['password'])->contains(
        fn ($rule) => $rule instanceof \Modules\Auth\Rules\PasswordPolicyRule
    );
    expect($hasPasswordRule)->toBeTrue();
});

test('StoreUserRequest has french validation messages', function () {
    $request = new StoreUserRequest;
    $messages = $request->messages();
    expect($messages)->toHaveKey('name.required')
        ->toHaveKey('email.required')
        ->toHaveKey('password.required');
});

test('UpdateUserRequest uses sometimes for all fields', function () {
    $request = new UpdateUserRequest;
    $rules = $request->rules();
    expect($rules['name'])->toContain('sometimes');
    expect($rules['email'][0])->toBe('sometimes');
    expect($rules['password'])->toContain('sometimes');
});

test('UserPolicy viewAny has correct signature', function () {
    $reflection = new ReflectionMethod(UserPolicy::class, 'viewAny');
    expect($reflection->getNumberOfParameters())->toBe(1);
    expect($reflection->getReturnType()->getName())->toBe('bool');
});

test('UserPolicy delete requires different user ids', function () {
    $content = file_get_contents(base_path('Modules/Auth/app/Policies/UserPolicy.php'));
    expect($content)->toContain('$user->id !== $model->id');
});

test('UserPolicy create is restricted to super_admin', function () {
    $content = file_get_contents(base_path('Modules/Auth/app/Policies/UserPolicy.php'));
    expect($content)->toContain("hasRole('super_admin')");
});

test('UserPolicy update prevents admin editing super_admin', function () {
    $content = file_get_contents(base_path('Modules/Auth/app/Policies/UserPolicy.php'));
    expect($content)->toContain("! \$model->hasRole('super_admin')");
});

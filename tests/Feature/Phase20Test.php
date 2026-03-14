<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */
test('HasApiResponse trait exists in Core module', function () {
    expect(trait_exists(Modules\Core\Traits\HasApiResponse::class))->toBeTrue();
});

test('BaseApiController uses HasApiResponse trait', function () {
    $reflection = new ReflectionClass(Modules\Api\Http\Controllers\BaseApiController::class);
    $traits = $reflection->getTraitNames();
    expect($traits)->toContain(Modules\Core\Traits\HasApiResponse::class);
});

test('HasApiResponse has all response methods', function () {
    $reflection = new ReflectionClass(Modules\Core\Traits\HasApiResponse::class);
    expect($reflection->hasMethod('respondSuccess'))->toBeTrue();
    expect($reflection->hasMethod('respondError'))->toBeTrue();
    expect($reflection->hasMethod('respondCreated'))->toBeTrue();
    expect($reflection->hasMethod('respondNotFound'))->toBeTrue();
    expect($reflection->hasMethod('respondUnauthorized'))->toBeTrue();
    expect($reflection->hasMethod('respondForbidden'))->toBeTrue();
    expect($reflection->hasMethod('respondNoContent'))->toBeTrue();
});

test('core:setup command exists', function () {
    expect(class_exists(Modules\Core\Console\CoreSetupCommand::class))->toBeTrue();
    $this->artisan('list')->assertSuccessful();
});

test('app:sync-permissions command exists', function () {
    expect(class_exists(Modules\RolesPermissions\Console\SyncPermissionsCommand::class))->toBeTrue();
});

test('api health endpoint returns json with force-json middleware', function () {
    $response = $this->get('/api/health');
    $response->assertStatus(200)
        ->assertJson(['status' => 'ok']);
});

test('api v1 status returns json structure', function () {
    $response = $this->getJson('/api/v1/status');
    $response->assertStatus(200)
        ->assertJsonStructure(['app', 'version', 'environment', 'timestamp']);
});

<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('table passkeys existe', function () {
    expect(Schema::hasTable('passkeys'))->toBeTrue();
});

test('User model implémente HasPasskeys interface', function () {
    $implements = class_implements(User::class);
    expect($implements)->toHaveKey(\Spatie\LaravelPasskeys\Models\Concerns\HasPasskeys::class);
});

test('User model utilise InteractsWithPasskeys trait', function () {
    $traits = class_uses_recursive(User::class);
    expect($traits)->toContain(\Spatie\LaravelPasskeys\Models\Concerns\InteractsWithPasskeys::class);
});

test('config passkeys.relying_party.name existe', function () {
    expect(config('passkeys.relying_party.name'))->not->toBeEmpty();
});

test('config passkeys.redirect_to_after_login est /admin', function () {
    expect(config('passkeys.redirect_to_after_login'))->toBe('/admin');
});

test('route /user/passkeys retourne 200 pour un user authentifié', function () {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->get('/user/passkeys')
        ->assertStatus(200);
});

test('route /user/passkeys redirige les guests', function () {
    $this->get('/user/passkeys')->assertRedirect();
});

test('vue login contient le composant passkey', function () {
    $content = File::get(resource_path('../Modules/Auth/resources/views/livewire/login.blade.php'));
    expect($content)->toContain('authenticate-passkey');
});

test('package @simplewebauthn/browser est dans package.json', function () {
    $json = json_decode(File::get(base_path('package.json')), true);
    expect($json['dependencies'] ?? [])->toHaveKey('@simplewebauthn/browser');
});

test('config passkeys.php existe', function () {
    expect(File::exists(config_path('passkeys.php')))->toBeTrue();
});

test('bootstrap.js contient les exports WebAuthn', function () {
    $content = File::get(resource_path('js/bootstrap.js'));
    expect($content)->toContain('startAuthentication')
        ->toContain('startRegistration');
});

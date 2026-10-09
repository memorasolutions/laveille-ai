<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Modules\Idp\Models\OAuthUser;

uses(RefreshDatabase::class);
uses(\Modules\Idp\Tests\Concerns\SkipsWhenIdpDisabled::class);
uses(\Modules\Idp\Tests\Concerns\EnablesIdp::class);

beforeEach(function (): void {
    test()->skipIfIdpModuleDisabled();
});

afterEach(function (): void {
    \Modules\Idp\Tests\Concerns\EnablesIdp::disableIdpEnv();
});

function idpLoadKeys(): void
{
    // Clés Passport éphémères (aucune clé du dépôt ni de storage/ n'est utilisée).
    $dir = sys_get_temp_dir().'/idp-keys-'.bin2hex(random_bytes(4));
    mkdir($dir);
    $res = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    openssl_pkey_export($res, $private);
    file_put_contents($dir.'/oauth-private.key', $private);
    file_put_contents($dir.'/oauth-public.key', openssl_pkey_get_details($res)['key']);
    chmod($dir.'/oauth-private.key', 0600);
    chmod($dir.'/oauth-public.key', 0600);
    Passport::loadKeysFrom($dir);
}

it('ON : le provider Passport est chargé', function (): void {
    expect(config('idp.enabled'))->toBeTrue();
    expect(app()->getProviders(\Laravel\Passport\PassportServiceProvider::class))->not->toBeEmpty();
});

it('ON : un OAuthUser authentifié par le guard idp obtient ses claims', function (): void {
    idpLoadKeys();
    $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
    auth('idp')->setUser(OAuthUser::findOrFail($user->id)); // pas shouldUse() : le garde par défaut reste web, comme en prod

    $this->getJson('/api/oauth/userinfo')->assertOk()->assertExactJson([
        'sub' => (string) $user->id,
        'email' => $user->email,
        'email_verified' => true,
        'name' => $user->name,
    ]);
});

it('ON : un compte désactivé est refusé (403)', function (): void {
    idpLoadKeys();
    $user = User::factory()->create(['is_active' => false]);
    auth('idp')->setUser(OAuthUser::findOrFail($user->id)); // pas shouldUse() : le garde par défaut reste web, comme en prod

    $this->getJson('/api/oauth/userinfo')->assertForbidden();
});

it('ON : sans jeton, 401', function (): void {
    idpLoadKeys();
    $this->getJson('/api/oauth/userinfo')->assertUnauthorized();
});

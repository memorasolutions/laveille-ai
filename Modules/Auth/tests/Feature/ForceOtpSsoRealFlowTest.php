<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Reproduction du FLUX RÉEL en deux requêtes, sans withSession : une route protégée lève
 * AuthenticationException depuis une URL contenant /oauth/authorize, le gestionnaire de Laravel
 * pose url.intended via redirect()->guest(), puis GET /login doit renvoyer vers le code (OTP).
 * Le test withSession seul ne prouvait pas ce chaînage (leçon : test unitaire != flux réel).
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'super_admin']);
    Role::firstOrCreate(['name' => 'user']);
    config(['auth.force_otp_in_sso_context' => true]);
});

it('flux réel : /oauth/authorize (invité) pose url.intended, puis /login renvoie vers le code', function () {
    // Route jetable qui imite ce que fait Passport::promptForLogin pour un invité : elle lève
    // AuthenticationException. Le gestionnaire Laravel la transforme en redirect()->guest(login),
    // ce qui pose url.intended = URL complète de cette requête (contient /oauth/authorize).
    Route::middleware('web')->get('/sso/oauth/authorize-probe', function () {
        throw new \Illuminate\Auth\AuthenticationException();
    });

    // 1) L'invité est renvoyé par le SSO vers /oauth/authorize : il atterrit sur /login.
    $this->get('/sso/oauth/authorize-probe?client_id=x&redirect_uri=y&response_type=code')
        ->assertRedirect(route('login'));

    // Preuve que redirect()->guest() a bien posé url.intended sur l'URL SSO.
    expect(session('url.intended'))->toContain('/oauth/authorize');

    // 2) GET /login dans ce contexte doit renvoyer vers le code (OTP), sans consommer url.intended.
    $this->get('/login')
        ->assertRedirect(route('magic-link.request'))
        ->assertSessionHas('url.intended');

    expect(session('url.intended'))->toContain('/oauth/authorize');
});

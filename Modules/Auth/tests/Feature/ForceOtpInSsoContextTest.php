<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Contexte SSO académie : /login renvoie vers le code (OTP) sans consommer url.intended,
 * sauf pour le super-admin, qui garde le mot de passe.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

uses(Tests\TestCase::class, RefreshDatabase::class);

const SSO_INTENDED = 'https://laveille.ai/oauth/authorize?client_id=x&redirect_uri=y';

beforeEach(function () {
    Role::firstOrCreate(['name' => 'super_admin']);
    Role::firstOrCreate(['name' => 'user']);
    config(['auth.force_otp_in_sso_context' => true]);
});

it('renvoie vers le code en contexte SSO sans consommer url.intended', function () {
    $this->withSession(['url.intended' => SSO_INTENDED])
        ->get('/login')
        ->assertRedirect(route('magic-link.request'))
        ->assertSessionHas('url.intended', SSO_INTENDED);
});

it('laisse la connexion normale sans url.intended', function () {
    $this->get('/login')->assertOk();
});

it('laisse la connexion normale si url.intended n\'est pas le SSO', function () {
    $this->withSession(['url.intended' => 'https://laveille.ai/profil'])
        ->get('/login')
        ->assertOk();
});

it('ne redirige pas quand le drapeau est désactivé', function () {
    config(['auth.force_otp_in_sso_context' => false]);

    $this->withSession(['url.intended' => SSO_INTENDED])->get('/login')->assertOk();
});

it('ramène le super-admin au mot de passe sans créer de compte ni envoyer de code', function () {
    config(['app.superadmin_email' => 'founder@test']);
    $founder = User::factory()->create(['email' => 'founder@test']);
    $founder->assignRole('super_admin');
    Notification::fake();
    $count = User::count();

    $this->withSession(['url.intended' => SSO_INTENDED])
        ->post('/magic-link', ['email' => 'founder@test'])
        ->assertRedirect(route('login'))
        ->assertSessionHas('status')
        ->assertSessionHas('force_password_login', true)
        ->assertSessionHas('url.intended', SSO_INTENDED);

    expect(User::count())->toBe($count);
    Notification::assertNothingSent();
});

it('garde le flux code pour un courriel ordinaire', function () {
    config(['app.superadmin_email' => 'founder@test']);
    Notification::fake();

    $this->withSession(['url.intended' => SSO_INTENDED])
        ->post('/magic-link', ['email' => 'apprenant@test.ca'])
        ->assertRedirect()
        ->assertSessionHas('url.intended', SSO_INTENDED);

    $user = User::where('email', 'apprenant@test.ca')->first();
    expect($user)->not->toBeNull();
    Notification::assertSentTo($user, \Modules\Auth\Notifications\MagicLinkNotification::class);
});

it('ne reboucle pas le super-admin : le drapeau force_password_login laisse /login afficher le mot de passe en contexte SSO', function () {
    $this->withSession(['url.intended' => SSO_INTENDED, 'force_password_login' => true])
        ->get('/login')
        ->assertOk()
        ->assertSessionHas('url.intended', SSO_INTENDED);
});

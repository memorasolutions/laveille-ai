<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->user = User::factory()->create(['password' => Hash::make('password')]);
});

it('la page setup 2FA retourne 200 pour un utilisateur authentifié', function () {
    $response = $this->actingAs($this->user)->get('/user/two-factor/setup');
    $response->assertStatus(200);
});

it('les invités sont redirigés vers /login pour setup 2FA', function () {
    $response = $this->get('/user/two-factor/setup');
    $response->assertRedirect('/login');
});

it('la page setup affiche le titre Double authentification', function () {
    $response = $this->actingAs($this->user)->get('/user/two-factor/setup');
    $response->assertSee('Double authentification');
});

it('la page setup affiche un QR code', function () {
    $response = $this->actingAs($this->user)->get('/user/two-factor/setup');
    $response->assertSee('data:image/svg+xml', false);
});

it('la page setup affiche un champ code', function () {
    $response = $this->actingAs($this->user)->get('/user/two-factor/setup');
    $response->assertSee('name="code"', false);
});

it('confirmer un mauvais code TOTP retourne une erreur', function () {
    // D'abord initialiser le secret
    $this->actingAs($this->user)->get('/user/two-factor/setup');
    $this->user->refresh();

    $response = $this->actingAs($this->user)->post('/user/two-factor/confirm', [
        'code' => '000000',
    ]);
    $response->assertSessionHasErrors(['code']);
});

it('désactiver 2FA avec un mauvais mot de passe retourne une erreur', function () {
    $response = $this->actingAs($this->user)->post('/user/two-factor/disable', [
        'password' => 'wrong-password',
    ]);
    $response->assertSessionHasErrors(['password']);
});

it('désactiver 2FA avec le bon mot de passe supprime le secret', function () {
    // Activer 2FA manuellement
    $this->user->update([
        'two_factor_secret' => Crypt::encrypt('TESTSECRET1234567890'),
        'two_factor_recovery_codes' => Crypt::encrypt(json_encode(['AAAA1111-BBBB2222'])),
        'two_factor_confirmed_at' => now(),
    ]);

    $response = $this->actingAs($this->user)->post('/user/two-factor/disable', [
        'password' => 'password',
    ]);

    $response->assertRedirect(route('user.profile'));
    $this->user->refresh();
    expect($this->user->two_factor_secret)->toBeNull();
    expect($this->user->two_factor_confirmed_at)->toBeNull();
});

it('la page recovery-codes redirige si 2FA non activé', function () {
    $response = $this->actingAs($this->user)->get('/user/two-factor/recovery-codes');
    $response->assertRedirect(route('user.profile'));
});

it('la page recovery-codes affiche les codes si 2FA activé', function () {
    $codes = ['AAAA1111-BBBB2222', 'CCCC3333-DDDD4444'];
    $this->user->update([
        'two_factor_secret' => Crypt::encrypt('TESTSECRET1234567890'),
        'two_factor_recovery_codes' => Crypt::encrypt(json_encode($codes)),
        'two_factor_confirmed_at' => now(),
    ]);

    $response = $this->actingAs($this->user)->get('/user/two-factor/recovery-codes');
    $response->assertStatus(200);
    $response->assertSee('AAAA1111-BBBB2222');
});

it('régénérer les codes crée 8 nouveaux codes', function () {
    $this->user->update([
        'two_factor_secret' => Crypt::encrypt('TESTSECRET1234567890'),
        'two_factor_recovery_codes' => Crypt::encrypt(json_encode(['OLD1234-CODE5678'])),
        'two_factor_confirmed_at' => now(),
    ]);

    $response = $this->actingAs($this->user)->post('/user/two-factor/recovery-codes/regenerate');
    $response->assertRedirect(route('user.two-factor.recovery-codes'));

    $this->user->refresh();
    $newCodes = json_decode(Crypt::decrypt($this->user->two_factor_recovery_codes), true);
    expect($newCodes)->toHaveCount(8);
    expect($newCodes[0])->not->toBe('OLD1234-CODE5678');
});

it('le profil affiche le bouton Activer le 2FA si désactivé', function () {
    $response = $this->actingAs($this->user)->get('/user/profile');
    $response->assertSee('Activer le 2FA');
});

it('le profil affiche Désactiver et codes de secours si 2FA activé', function () {
    $this->user->update([
        'two_factor_secret' => Crypt::encrypt('TESTSECRET1234567890'),
        'two_factor_recovery_codes' => Crypt::encrypt(json_encode(['AAAA1111-BBBB2222'])),
        'two_factor_confirmed_at' => now(),
    ]);

    $response = $this->actingAs($this->user)->get('/user/profile');
    $response->assertSee('Désactiver');
    $response->assertSee('Codes de secours');
});

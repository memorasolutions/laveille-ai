<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Services\TwoFactorService;
use PragmaRX\Google2FA\Google2FA;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->google2faMock = \Mockery::mock(Google2FA::class);
    $this->google2faMock->shouldReceive('generateSecretKey')->andReturn('TESTSECRET16CHAR');
    $this->google2faMock->shouldReceive('getQRCodeUrl')->andReturn('otpauth://totp/App:test@test.com?secret=TESTSECRET16CHAR');
    $this->google2faMock->shouldReceive('verifyKey')->andReturn(false)->byDefault();

    $this->app->instance(Google2FA::class, $this->google2faMock);

    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

    $this->user = User::factory()->create([
        'password' => Hash::make('TestP@ssw0rd!'),
    ]);
    $this->user->assignRole('admin');
});

it('can enable two factor authentication', function () {
    $service = app(TwoFactorService::class);
    $data = $service->enable($this->user);

    $this->user->refresh();

    expect($this->user->two_factor_secret)->not->toBeNull();
    expect($this->user->two_factor_recovery_codes)->not->toBeNull();
    expect($this->user->two_factor_confirmed_at)->toBeNull();
    expect($data)->toHaveKeys(['secret', 'qr_url', 'recovery_codes']);
    expect($data['recovery_codes'])->toHaveCount(8);
});

it('can confirm two factor authentication with valid OTP', function () {
    $this->google2faMock->shouldReceive('verifyKey')->once()->andReturn(true);

    $service = app(TwoFactorService::class);
    $service->enable($this->user);
    $confirmed = $service->confirm($this->user, '123456');

    $this->user->refresh();

    expect($confirmed)->toBeTrue();
    expect($this->user->two_factor_confirmed_at)->not->toBeNull();
    expect($this->user->hasEnabledTwoFactor())->toBeTrue();
});

it('cannot confirm two factor with invalid OTP', function () {
    $this->google2faMock->shouldReceive('verifyKey')->once()->andReturn(false);

    $service = app(TwoFactorService::class);
    $service->enable($this->user);
    $confirmed = $service->confirm($this->user, '000000');

    $this->user->refresh();

    expect($confirmed)->toBeFalse();
    expect($this->user->two_factor_confirmed_at)->toBeNull();
});

it('can disable two factor authentication', function () {
    $service = app(TwoFactorService::class);
    $service->enable($this->user);
    $this->user->update(['two_factor_confirmed_at' => now()]);

    $service->disable($this->user);
    $this->user->refresh();

    expect($this->user->two_factor_secret)->toBeNull();
    expect($this->user->two_factor_recovery_codes)->toBeNull();
    expect($this->user->two_factor_confirmed_at)->toBeNull();
    expect($this->user->hasEnabledTwoFactor())->toBeFalse();
});

it('can verify a valid OTP code', function () {
    $this->google2faMock->shouldReceive('verifyKey')->once()->andReturn(true);

    $service = app(TwoFactorService::class);
    $service->enable($this->user);

    expect($service->verify($this->user, '123456'))->toBeTrue();
});

it('cannot verify an invalid OTP code', function () {
    $this->google2faMock->shouldReceive('verifyKey')->once()->andReturn(false);

    $service = app(TwoFactorService::class);
    $service->enable($this->user);

    expect($service->verify($this->user, '000000'))->toBeFalse();
});

it('cannot verify OTP without secret', function () {
    $service = app(TwoFactorService::class);

    expect($service->verify($this->user, '123456'))->toBeFalse();
});

it('can use a valid recovery code', function () {
    $service = app(TwoFactorService::class);
    $data = $service->enable($this->user);
    $this->user->refresh();
    $recoveryCode = $data['recovery_codes'][0];

    expect($service->verifyRecoveryCode($this->user, $recoveryCode))->toBeTrue();
});

it('recovery code is invalidated after single use', function () {
    $service = app(TwoFactorService::class);
    $data = $service->enable($this->user);
    $this->user->refresh();
    $recoveryCode = $data['recovery_codes'][0];

    $firstUse = $service->verifyRecoveryCode($this->user, $recoveryCode);
    $this->user->refresh();
    $secondUse = $service->verifyRecoveryCode($this->user, $recoveryCode);

    expect($firstUse)->toBeTrue();
    expect($secondUse)->toBeFalse();
});

it('rejects unknown recovery code', function () {
    $service = app(TwoFactorService::class);
    $service->enable($this->user);
    $this->user->refresh();

    expect($service->verifyRecoveryCode($this->user, 'INVALID0-CODE0000'))->toBeFalse();
});

it('can access profile page with 2fa section', function () {
    $response = $this->actingAs($this->user)->get(route('admin.profile'));

    $response->assertStatus(200);
    $response->assertSee('Double authentification');
    $response->assertSee('2FA');
});

it('can enable 2fa from profile page', function () {
    $response = $this->actingAs($this->user)
        ->post(route('admin.profile.2fa.enable'));

    $response->assertRedirect();
    $this->user->refresh();
    expect($this->user->two_factor_secret)->not->toBeNull();
});

it('profile shows qr code after enabling', function () {
    $response = $this->actingAs($this->user)
        ->withSession(['2fa.setup' => [
            'qr_url' => 'data:image/svg+xml;base64,dGVzdA==',
            'recovery_codes' => ['CODE1234-ABCD5678'],
            'secret' => 'TESTSECRET',
        ]])
        ->get(route('admin.profile'));

    $response->assertStatus(200);
    $response->assertSee('Codes de récupération');
    $response->assertSee('CODE1234-ABCD5678');
});

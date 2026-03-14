<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
});

it('verified user can access dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('user.dashboard'))->assertStatus(200);
});

it('unverified user can still access dashboard', function () {
    $user = User::factory()->unverified()->create();
    $this->actingAs($user)->get(route('user.dashboard'))->assertStatus(200);
});

it('unverified user sees verification banner on dashboard', function () {
    $user = User::factory()->unverified()->create();
    $this->actingAs($user)->get(route('user.dashboard'))->assertSee('Vérifiez');
});

it('verified user does not see verification banner', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('user.dashboard'))->assertDontSee('Vérifiez');
});

it('email verification notice page loads for unverified user', function () {
    $user = User::factory()->unverified()->create();
    $this->actingAs($user)->get(route('verification.notice'))->assertStatus(200);
});

it('verified user is redirected from notice page', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('verification.notice'))
        ->assertRedirect(route('user.dashboard'));
});

it('resend verification email sends notification', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create();
    $this->actingAs($user)->post(route('verification.send'));
    Notification::assertSentTo($user, VerifyEmail::class);
});

it('unauthenticated user is redirected from verification notice', function () {
    $this->get(route('verification.notice'))->assertRedirect(route('login'));
});

it('user has verified email when email_verified_at is set', function () {
    $user = User::factory()->create();
    expect($user->hasVerifiedEmail())->toBeTrue();
});

it('user does not have verified email when email_verified_at is null', function () {
    $user = User::factory()->unverified()->create();
    expect($user->hasVerifiedEmail())->toBeFalse();
});

it('user model implements MustVerifyEmail', function () {
    expect(new User)->toBeInstanceOf(MustVerifyEmail::class);
});

it('user factory creates verified users by default', function () {
    $user = User::factory()->create();
    expect($user->hasVerifiedEmail())->toBeTrue();
});

<?php

declare(strict_types=1);

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Modules\Auth\Listeners\LogFailedLogin;
use Modules\Auth\Listeners\LogLoginAttempt;
use Modules\Auth\Rules\PasswordPolicyRule;
use Modules\Settings\Models\Setting;

uses(RefreshDatabase::class);

it('lockout columns exist on users table', function () {
    expect(Schema::hasColumn('users', 'failed_login_count'))->toBeTrue();
    expect(Schema::hasColumn('users', 'locked_until'))->toBeTrue();
});

it('user isLocked returns false by default', function () {
    $user = User::factory()->create();
    expect($user->isLocked())->toBeFalse();
});

it('user isLocked returns true when locked_until is future', function () {
    $user = User::factory()->create([
        'locked_until' => Carbon::now()->addMinutes(30),
    ]);
    expect($user->isLocked())->toBeTrue();
});

it('user isLocked returns false when locked_until is past', function () {
    $user = User::factory()->create([
        'locked_until' => Carbon::now()->subMinute(),
    ]);
    expect($user->isLocked())->toBeFalse();
});

it('failed login listener increments failed_login_count', function () {
    $user = User::factory()->create();
    $listener = new LogFailedLogin;
    $event = new Failed('web', $user, ['email' => $user->email]);
    $listener->handle($event);
    expect($user->fresh()->failed_login_count)->toBe(1);
});

it('account locks after max attempts', function () {
    Setting::create(['group' => 'security', 'key' => 'security.max_login_attempts', 'value' => '3', 'type' => 'number']);
    Setting::create(['group' => 'security', 'key' => 'security.lockout_duration', 'value' => '30', 'type' => 'number']);
    $user = User::factory()->create();
    $listener = new LogFailedLogin;
    for ($i = 0; $i < 3; $i++) {
        $event = new Failed('web', $user, ['email' => $user->email]);
        $listener->handle($event);
    }
    expect($user->fresh()->locked_until)->not->toBeNull();
});

it('successful login resets lockout', function () {
    $user = User::factory()->create([
        'failed_login_count' => 3,
        'locked_until' => Carbon::now()->addMinutes(10),
    ]);
    $listener = new LogLoginAttempt;
    $event = new Login('web', $user, false);
    $listener->handle($event);
    $fresh = $user->fresh();
    expect($fresh->failed_login_count)->toBe(0);
    expect($fresh->locked_until)->toBeNull();
});

it('unlock user command works', function () {
    $user = User::factory()->create([
        'failed_login_count' => 5,
        'locked_until' => Carbon::now()->addMinutes(10),
    ]);
    Artisan::call('auth:unlock-user', ['email' => $user->email]);
    $fresh = $user->fresh();
    expect($fresh->failed_login_count)->toBe(0);
    expect($fresh->locked_until)->toBeNull();
});

it('unlock user command fails for unknown email', function () {
    $exitCode = Artisan::call('auth:unlock-user', ['email' => 'unknown@test.com']);
    expect($exitCode)->toBe(1);
});

it('admin can unlock user via route', function () {
    \Spatie\Permission\Models\Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    $user = User::factory()->create([
        'failed_login_count' => 2,
        'locked_until' => Carbon::now()->addMinutes(5),
    ]);
    $this->actingAs($admin)
        ->post(route('admin.users.unlock', $user))
        ->assertRedirect();
    $fresh = $user->fresh();
    expect($fresh->locked_until)->toBeNull();
    expect($fresh->failed_login_count)->toBe(0);
});

it('password policy rule validates min length', function () {
    Setting::create(['group' => 'security', 'key' => 'security.password_min_length', 'value' => '10', 'type' => 'number']);
    $rule = new PasswordPolicyRule;
    $failed = false;
    $rule->validate('password', 'Short1', function () use (&$failed) {
        $failed = true;
    });
    expect($failed)->toBeTrue();
});

it('password policy rule validates uppercase', function () {
    Setting::create(['group' => 'security', 'key' => 'security.password_require_uppercase', 'value' => 'true', 'type' => 'boolean']);
    $rule = new PasswordPolicyRule;
    $failed = false;
    $rule->validate('password', 'password1', function () use (&$failed) {
        $failed = true;
    });
    expect($failed)->toBeTrue();
});

it('password policy rule validates number', function () {
    Setting::create(['group' => 'security', 'key' => 'security.password_require_number', 'value' => 'true', 'type' => 'boolean']);
    $rule = new PasswordPolicyRule;
    $failed = false;
    $rule->validate('password', 'Password', function () use (&$failed) {
        $failed = true;
    });
    expect($failed)->toBeTrue();
});

it('password policy rule validates special chars', function () {
    Setting::create(['group' => 'security', 'key' => 'security.password_require_special', 'value' => 'true', 'type' => 'boolean']);
    $rule = new PasswordPolicyRule;
    $failed = false;
    $rule->validate('password', 'Password1', function () use (&$failed) {
        $failed = true;
    });
    expect($failed)->toBeTrue();
});

it('password policy rule passes valid password', function () {
    Setting::create(['group' => 'security', 'key' => 'security.password_require_uppercase', 'value' => 'true', 'type' => 'boolean']);
    Setting::create(['group' => 'security', 'key' => 'security.password_require_number', 'value' => 'true', 'type' => 'boolean']);
    Setting::create(['group' => 'security', 'key' => 'security.password_require_special', 'value' => 'true', 'type' => 'boolean']);
    $rule = new PasswordPolicyRule;
    $failed = false;
    $rule->validate('password', 'Password1!', function () use (&$failed) {
        $failed = true;
    });
    expect($failed)->toBeFalse();
});

it('settings seeder includes security settings', function () {
    $this->seed();
    expect(Setting::where('key', 'security.max_login_attempts')->exists())->toBeTrue();
    expect(Setting::where('key', 'security.password_min_length')->exists())->toBeTrue();
});

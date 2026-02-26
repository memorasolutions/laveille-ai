<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Modules\Auth\Models\LoginAttempt;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Modules\RolesPermissions\Database\Seeders\RolesPermissionsDatabaseSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole(Role::findByName('super_admin', 'web'));
});

// --- Login Attempts ---

it('login_attempts table exists', function () {
    expect(Schema::hasTable('login_attempts'))->toBeTrue();
});

it('login_attempts table has correct columns', function () {
    expect(Schema::hasColumns('login_attempts', ['user_id', 'email', 'ip_address', 'user_agent', 'status', 'logged_in_at']))
        ->toBeTrue();
});

it('login listener records successful login', function () {
    $listener = new \Modules\Auth\Listeners\LogLoginAttempt;
    $event = new \Illuminate\Auth\Events\Login('web', $this->admin, false);
    $listener->handle($event);

    expect(LoginAttempt::where('status', 'success')->count())->toBe(1)
        ->and(LoginAttempt::first()->email)->toBe($this->admin->email);
});

it('failed login listener records failed attempt', function () {
    $listener = new \Modules\Auth\Listeners\LogFailedLogin;
    $event = new \Illuminate\Auth\Events\Failed('web', null, ['email' => 'bad@test.com', 'password' => 'wrong']);
    $listener->handle($event);

    expect(LoginAttempt::where('status', 'failed')->exists())->toBeTrue()
        ->and(LoginAttempt::first()->email)->toBe('bad@test.com');
});

it('login attempt stores ip address', function () {
    $listener = new \Modules\Auth\Listeners\LogLoginAttempt;
    $event = new \Illuminate\Auth\Events\Login('web', $this->admin, false);
    $listener->handle($event);

    $attempt = LoginAttempt::first();
    expect($attempt->ip_address)->not->toBeNull();
});

it('login attempt belongs to user', function () {
    LoginAttempt::create([
        'user_id' => $this->admin->id,
        'email' => $this->admin->email,
        'ip_address' => '127.0.0.1',
        'status' => 'success',
        'logged_in_at' => now(),
    ]);

    $attempt = LoginAttempt::first();
    expect($attempt->user->id)->toBe($this->admin->id);
});

// --- Login History Admin ---

it('login history admin page requires auth', function () {
    $this->get('/admin/login-history')->assertRedirect();
});

it('login history admin page loads for admin', function () {
    $this->actingAs($this->admin)
        ->get('/admin/login-history')
        ->assertOk();
});

it('login history shows attempts in view', function () {
    LoginAttempt::create([
        'user_id' => $this->admin->id,
        'email' => $this->admin->email,
        'ip_address' => '192.168.1.1',
        'status' => 'success',
        'logged_in_at' => now(),
    ]);

    $this->actingAs($this->admin)
        ->get('/admin/login-history')
        ->assertSee('192.168.1.1');
});

// --- Cache Management ---

it('cache admin page requires auth', function () {
    $this->get('/admin/cache')->assertRedirect();
});

it('cache admin page loads for admin', function () {
    $this->actingAs($this->admin)
        ->get('/admin/cache')
        ->assertOk();
});

it('clear cache action works', function () {
    $this->actingAs($this->admin)
        ->post('/admin/cache/clear-cache')
        ->assertRedirect()
        ->assertSessionHas('success');
});

it('clear all caches action works', function () {
    $this->actingAs($this->admin)
        ->post('/admin/cache/clear-all')
        ->assertRedirect()
        ->assertSessionHas('success');
});

// --- Cleanup Command ---

it('cleanup command runs successfully', function () {
    $this->artisan('app:cleanup')->assertExitCode(0);
});

it('cleanup command is scheduled daily', function () {
    Artisan::call('schedule:list');
    $output = Artisan::output();

    expect($output)->toContain('app:cleanup');
});

it('cleanup command deletes old login attempts', function () {
    LoginAttempt::create([
        'email' => 'old@test.com',
        'ip_address' => '1.2.3.4',
        'status' => 'failed',
        'logged_in_at' => now()->subDays(100),
    ]);

    LoginAttempt::create([
        'email' => 'recent@test.com',
        'ip_address' => '5.6.7.8',
        'status' => 'success',
        'logged_in_at' => now(),
    ]);

    $this->artisan('app:cleanup')->assertExitCode(0);

    expect(LoginAttempt::count())->toBe(1)
        ->and(LoginAttempt::first()->email)->toBe('recent@test.com');
});

<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\Auth\Models\BlockedIp;
use Modules\Auth\Models\LoginAttempt;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Modules\RolesPermissions\Database\Seeders\RolesPermissionsDatabaseSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole(Role::findByName('super_admin', 'web'));
});

// --- BlockedIp Model ---

it('blocked_ips table exists', function () {
    expect(Schema::hasTable('blocked_ips'))->toBeTrue();
});

it('blocked ip model can be created', function () {
    BlockedIp::create(['ip_address' => '192.168.1.1', 'reason' => 'Test']);

    expect(BlockedIp::where('ip_address', '192.168.1.1')->exists())->toBeTrue();
});

it('isBlocked returns true for active block', function () {
    BlockedIp::create([
        'ip_address' => '10.0.0.1',
        'blocked_until' => now()->addHours(1),
    ]);

    expect(BlockedIp::isBlocked('10.0.0.1'))->toBeTrue();
});

it('isBlocked returns true for permanent block', function () {
    BlockedIp::create([
        'ip_address' => '10.0.0.2',
        'blocked_until' => null,
    ]);

    expect(BlockedIp::isBlocked('10.0.0.2'))->toBeTrue();
});

it('isBlocked returns false for expired block', function () {
    BlockedIp::create([
        'ip_address' => '10.0.0.3',
        'blocked_until' => now()->subMinutes(5),
    ]);

    expect(BlockedIp::isBlocked('10.0.0.3'))->toBeFalse();
});

// --- Middleware ---

it('middleware blocks requests from blocked ip', function () {
    BlockedIp::create(['ip_address' => '127.0.0.1']);

    $this->getJson('/api/v1/status')->assertStatus(403);
});

// --- Security Dashboard ---

it('security dashboard requires auth', function () {
    $this->get('/admin/security')->assertRedirect();
});

it('security dashboard loads for admin', function () {
    $this->actingAs($this->admin)
        ->get('/admin/security')
        ->assertOk();
});

it('security dashboard has stats', function () {
    $this->actingAs($this->admin)
        ->get('/admin/security')
        ->assertViewHas('stats');
});

// --- Blocked IPs Admin ---

it('blocked ips page requires auth', function () {
    $this->get('/admin/blocked-ips')->assertRedirect();
});

it('blocked ips page loads for admin', function () {
    $this->actingAs($this->admin)
        ->get('/admin/blocked-ips')
        ->assertOk();
});

it('admin can block ip', function () {
    $this->actingAs($this->admin)
        ->post('/admin/blocked-ips', ['ip_address' => '203.0.113.10'])
        ->assertRedirect();

    expect(BlockedIp::where('ip_address', '203.0.113.10')->exists())->toBeTrue();
});

it('admin can unblock ip', function () {
    $blocked = BlockedIp::create(['ip_address' => '203.0.113.11']);

    $this->actingAs($this->admin)
        ->delete("/admin/blocked-ips/{$blocked->id}")
        ->assertRedirect();

    expect(BlockedIp::where('id', $blocked->id)->exists())->toBeFalse();
});

// --- Auto-block Command ---

it('block suspicious ips command runs', function () {
    $this->artisan('app:block-suspicious-ips')->assertExitCode(0);
});

it('auto-block blocks ip with many failures', function () {
    $ip = '203.0.113.50';

    for ($i = 0; $i < 15; $i++) {
        LoginAttempt::create([
            'email' => 'attacker@test.com',
            'ip_address' => $ip,
            'status' => 'failed',
            'logged_in_at' => now()->subMinutes(5),
        ]);
    }

    $this->artisan('app:block-suspicious-ips', [
        '--threshold' => 10,
        '--minutes' => 30,
    ])->assertExitCode(0);

    expect(BlockedIp::where('ip_address', $ip)->exists())->toBeTrue()
        ->and(BlockedIp::where('ip_address', $ip)->first()->auto_blocked)->toBeTrue();
});

it('auto-block does not block ip below threshold', function () {
    $ip = '203.0.113.51';

    for ($i = 0; $i < 5; $i++) {
        LoginAttempt::create([
            'email' => 'user@test.com',
            'ip_address' => $ip,
            'status' => 'failed',
            'logged_in_at' => now()->subMinutes(5),
        ]);
    }

    $this->artisan('app:block-suspicious-ips', [
        '--threshold' => 10,
        '--minutes' => 30,
    ])->assertExitCode(0);

    expect(BlockedIp::where('ip_address', $ip)->exists())->toBeFalse();
});

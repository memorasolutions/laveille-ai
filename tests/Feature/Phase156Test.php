<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Modules\Settings\Models\Setting;

uses(RefreshDatabase::class);

test('retention settings have default values after seeding', function () {
    Artisan::call('db:seed', ['--class' => 'Modules\\Settings\\Database\\Seeders\\SettingsDatabaseSeeder']);

    expect((int) Setting::get('retention.login_attempts_days', 90))->toBe(90);
    expect((int) Setting::get('retention.sent_emails_days', 90))->toBe(90);
    expect((int) Setting::get('retention.activity_log_days', 180))->toBe(180);
    expect((int) Setting::get('retention.blocked_ips_days', 365))->toBe(365);
});

test('retention settings can be updated', function () {
    Setting::set('retention.login_attempts_days', '30', 'number', 'retention');

    expect((int) Setting::get('retention.login_attempts_days'))->toBe(30);
});

test('cleanup command removes old login attempts', function () {
    DB::table('login_attempts')->insert([
        ['email' => 'old@test.com', 'ip_address' => '1.1.1.1', 'status' => 'failed', 'logged_in_at' => now()->subDays(100), 'created_at' => now()->subDays(100), 'updated_at' => now()->subDays(100)],
        ['email' => 'new@test.com', 'ip_address' => '2.2.2.2', 'status' => 'success', 'logged_in_at' => now()->subDays(10), 'created_at' => now()->subDays(10), 'updated_at' => now()->subDays(10)],
    ]);

    Artisan::call('app:cleanup');

    expect(DB::table('login_attempts')->count())->toBe(1);
    expect(DB::table('login_attempts')->first()->email)->toBe('new@test.com');
});

test('cleanup command removes old sent emails', function () {
    DB::table('sent_emails')->insert([
        ['to' => 'old@test.com', 'subject' => 'Old', 'status' => 'sent', 'sent_at' => now()->subDays(100), 'created_at' => now()->subDays(100), 'updated_at' => now()->subDays(100)],
        ['to' => 'new@test.com', 'subject' => 'New', 'status' => 'sent', 'sent_at' => now()->subDays(10), 'created_at' => now()->subDays(10), 'updated_at' => now()->subDays(10)],
    ]);

    Artisan::call('app:cleanup');

    expect(DB::table('sent_emails')->count())->toBe(1);
    expect(DB::table('sent_emails')->first()->to)->toBe('new@test.com');
});

test('cleanup command removes old activity logs', function () {
    DB::table('activity_log')->insert([
        ['log_name' => 'default', 'description' => 'old action', 'created_at' => now()->subDays(200), 'updated_at' => now()->subDays(200)],
        ['log_name' => 'default', 'description' => 'recent action', 'created_at' => now()->subDays(10), 'updated_at' => now()->subDays(10)],
    ]);

    Artisan::call('app:cleanup');

    expect(DB::table('activity_log')->count())->toBe(1);
    expect(DB::table('activity_log')->first()->description)->toBe('recent action');
});

test('cleanup command removes expired magic tokens', function () {
    DB::table('magic_login_tokens')->insert([
        ['email' => 'expired@test.com', 'token' => '111111', 'expires_at' => now()->subHour(), 'created_at' => now()->subHour(), 'updated_at' => now()->subHour()],
        ['email' => 'valid@test.com', 'token' => '222222', 'expires_at' => now()->addHour(), 'created_at' => now(), 'updated_at' => now()],
    ]);

    Artisan::call('app:cleanup');

    expect(DB::table('magic_login_tokens')->count())->toBe(1);
    expect(DB::table('magic_login_tokens')->first()->email)->toBe('valid@test.com');
});

test('cleanup command removes expired blocked ips older than retention', function () {
    DB::table('blocked_ips')->insert([
        ['ip_address' => '10.0.0.1', 'reason' => 'old', 'blocked_until' => now()->subDay(), 'auto_blocked' => true, 'created_at' => now()->subDays(400), 'updated_at' => now()->subDays(400)],
        ['ip_address' => '10.0.0.2', 'reason' => 'permanent', 'blocked_until' => null, 'auto_blocked' => false, 'created_at' => now()->subDays(400), 'updated_at' => now()->subDays(400)],
    ]);

    Artisan::call('app:cleanup');

    expect(DB::table('blocked_ips')->count())->toBe(1);
    expect(DB::table('blocked_ips')->first()->ip_address)->toBe('10.0.0.2');
});

test('cleanup command respects custom retention settings', function () {
    Setting::set('retention.login_attempts_days', '5', 'number', 'retention');

    DB::table('login_attempts')->insert([
        'email' => 'test@test.com', 'ip_address' => '3.3.3.3', 'status' => 'failed',
        'logged_in_at' => now()->subDays(10), 'created_at' => now()->subDays(10), 'updated_at' => now()->subDays(10),
    ]);

    Artisan::call('app:cleanup');

    expect(DB::table('login_attempts')->count())->toBe(0);
});

test('cleanup command outputs summary', function () {
    Artisan::call('app:cleanup');

    expect(Artisan::output())->toContain('Nettoyage terminé.');
});

test('cleanup is scheduled daily', function () {
    $consoleFile = file_get_contents(base_path('routes/console.php'));

    expect($consoleFile)->toContain('app:cleanup');
    expect($consoleFile)->toContain('dailyAt');
});

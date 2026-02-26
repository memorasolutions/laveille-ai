<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Notifications\Mail\DigestMail;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('notification_frequency column exists', function () {
    expect(Schema::hasColumn('users', 'notification_frequency'))->toBeTrue();
});

test('default frequency is immediate', function () {
    $user = User::factory()->create();
    $user->refresh();
    expect($user->notification_frequency)->toBe('immediate');
});

test('user can update frequency to daily', function () {
    $user = User::factory()->create();
    $response = $this->actingAs($user)->put('/user/notifications/frequency', [
        'notification_frequency' => 'daily',
    ]);
    $response->assertRedirect();
    $user->refresh();
    expect($user->notification_frequency)->toBe('daily');
});

test('user can update frequency to weekly', function () {
    $user = User::factory()->create();
    $response = $this->actingAs($user)->put('/user/notifications/frequency', [
        'notification_frequency' => 'weekly',
    ]);
    $response->assertRedirect();
    $user->refresh();
    expect($user->notification_frequency)->toBe('weekly');
});

test('invalid frequency rejected', function () {
    $user = User::factory()->create();
    $response = $this->actingAs($user)->put('/user/notifications/frequency', [
        'notification_frequency' => 'invalid',
    ]);
    $response->assertSessionHasErrors('notification_frequency');
});

test('guest cannot update frequency', function () {
    $response = $this->put('/user/notifications/frequency', [
        'notification_frequency' => 'daily',
    ]);
    $response->assertRedirect('/login');
});

test('digest command exists', function () {
    $exitCode = Artisan::call('notifications:send-digest');
    expect($exitCode)->toBe(0);
});

test('digest command with weekly option', function () {
    $exitCode = Artisan::call('notifications:send-digest', ['--frequency' => 'weekly']);
    expect($exitCode)->toBe(0);
});

test('digest skips users with immediate frequency', function () {
    Mail::fake();
    $user = User::factory()->create(['notification_frequency' => 'immediate']);
    DB::table('notifications')->insert([
        'id' => Str::uuid()->toString(),
        'type' => 'App\\Notifications\\TestNotification',
        'notifiable_type' => 'App\\Models\\User',
        'notifiable_id' => $user->id,
        'data' => json_encode(['title' => 'Test', 'message' => 'Test message']),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    Artisan::call('notifications:send-digest');
    Mail::assertNothingSent();
});

test('digest sends to daily users', function () {
    Mail::fake();
    $user = User::factory()->create(['notification_frequency' => 'daily']);
    DB::table('notifications')->insert([
        'id' => Str::uuid()->toString(),
        'type' => 'App\\Notifications\\TestNotification',
        'notifiable_type' => 'App\\Models\\User',
        'notifiable_id' => $user->id,
        'data' => json_encode(['title' => 'Test', 'message' => 'Test message']),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    Artisan::call('notifications:send-digest');
    Mail::assertQueued(DigestMail::class);
});

test('digest marks notifications as read after sending', function () {
    Mail::fake();
    $user = User::factory()->create(['notification_frequency' => 'daily']);
    $notifId = Str::uuid()->toString();
    DB::table('notifications')->insert([
        'id' => $notifId,
        'type' => 'App\\Notifications\\TestNotification',
        'notifiable_type' => 'App\\Models\\User',
        'notifiable_id' => $user->id,
        'data' => json_encode(['title' => 'Test', 'message' => 'Test']),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    Artisan::call('notifications:send-digest');
    $notification = DB::table('notifications')->find($notifId);
    expect($notification->read_at)->not->toBeNull();
});

test('notification frequency is included in user fillable', function () {
    $user = new User;
    expect($user->getFillable())->toContain('notification_frequency');
});

test('digest command is registered', function () {
    $commands = Artisan::all();
    expect($commands)->toHaveKey('notifications:send-digest');
});

test('schedule includes digest commands', function () {
    $schedule = file_get_contents(base_path('routes/console.php'));
    expect($schedule)->toContain('notifications:send-digest');
    expect($schedule)->toContain('--frequency=daily');
    expect($schedule)->toContain('--frequency=weekly');
});

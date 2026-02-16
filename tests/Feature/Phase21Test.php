<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Modules\Auth\Jobs\ProcessUserExport;
use Modules\Auth\Listeners\SendWelcomeNotification;
use Modules\Core\Events\UserCreated;

uses(RefreshDatabase::class);

// --- HTTP Integration Tests ---

test('api health endpoint returns json with correct headers', function () {
    $response = $this->get('/api/health');

    $response->assertStatus(200)
        ->assertJson(['status' => 'ok'])
        ->assertHeader('Content-Type', 'application/json');
});

test('api v1 routes apply throttle middleware', function () {
    $content = file_get_contents(base_path('routes/api.php'));
    expect($content)->toContain('throttle:api');
});

test('api v1 unauthenticated user endpoint returns 401', function () {
    $response = $this->getJson('/api/v1/user');

    $response->assertStatus(401)
        ->assertJson(['success' => false, 'message' => 'Non authentifié.']);
});

test('api nonexistent route returns json 404', function () {
    $response = $this->getJson('/api/v1/does-not-exist');

    $response->assertStatus(404)
        ->assertJson(['success' => false]);
});

// --- Rate Limiting Tests ---

test('rate limiter api is defined with 60 per minute', function () {
    $content = file_get_contents(app_path('Providers/AppServiceProvider.php'));
    expect($content)->toContain('perMinute(60)');
});

test('rate limiter login is defined with 5 per minute', function () {
    $content = file_get_contents(app_path('Providers/AppServiceProvider.php'));
    expect($content)->toContain('perMinute(5)');
});

test('rate limiter sensitive is defined with 10 per minute', function () {
    $content = file_get_contents(app_path('Providers/AppServiceProvider.php'));
    expect($content)->toContain('perMinute(10)');
});

// --- Event/Listener Integration Tests ---

test('user created event can be dispatched', function () {
    Event::fake([UserCreated::class]);

    $user = User::factory()->create();
    UserCreated::dispatch($user);

    Event::assertDispatched(UserCreated::class, function ($event) use ($user) {
        return $event->user->id === $user->id;
    });
});

test('user created event has listener registered in Auth module', function () {
    $content = file_get_contents(base_path('Modules/Auth/app/Providers/AuthServiceProvider.php'));
    expect($content)->toContain('Event::listen(UserCreated::class, SendWelcomeNotification::class)');
});

test('send welcome notification listener handles event', function () {
    Notification::fake();

    $user = User::factory()->create();
    $event = new UserCreated($user);
    $listener = new SendWelcomeNotification;
    $listener->handle($event);

    Notification::assertSentTo($user, Modules\Notifications\Notifications\WelcomeNotification::class);
});

// --- Job Integration Tests ---

test('process user export job can be dispatched', function () {
    Queue::fake();

    ProcessUserExport::dispatch('csv');

    Queue::assertPushed(ProcessUserExport::class, function ($job) {
        return $job->format === 'csv';
    });
});

test('process user export job has retry configuration', function () {
    $job = new ProcessUserExport;
    expect($job->tries)->toBe(3);
    expect($job->backoff)->toBe([30, 60, 120]);
});

// --- .env.example Completeness ---

test('env example has sanctum configuration', function () {
    $content = file_get_contents(base_path('.env.example'));
    expect($content)->toContain('SANCTUM_TOKEN_EXPIRATION')
        ->toContain('SANCTUM_TOKEN_PREFIX');
});

test('env example has frontend url', function () {
    $content = file_get_contents(base_path('.env.example'));
    expect($content)->toContain('FRONTEND_URL');
});

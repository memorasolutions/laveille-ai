<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Modules\Blog\Models\Article;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('returns json download on export', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('user.export-data'));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/json');
    expect($response->headers->get('Content-Disposition'))->toContain('attachment');
});

it('export contains profile data with name and email', function () {
    $user = User::factory()->create(['name' => 'Marie Dupont', 'email' => 'marie@example.com']);

    $response = $this->actingAs($user)->get(route('user.export-data'));
    $data = json_decode($response->streamedContent(), true);

    expect($data['profile'])->toHaveKeys(['name', 'email', 'created_at'])
        ->and($data['profile']['name'])->toBe('Marie Dupont')
        ->and($data['profile']['email'])->toBe('marie@example.com');
});

it('export contains user articles', function () {
    $user = User::factory()->create();
    Article::factory()->count(2)->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->get(route('user.export-data'));
    $data = json_decode($response->streamedContent(), true);

    expect($data['articles'])->toBeArray()->toHaveCount(2);
});

it('export contains sessions', function () {
    $user = User::factory()->create();

    DB::table('sessions')->insert([
        'id' => 'gdpr-test-session',
        'user_id' => $user->id,
        'ip_address' => '10.0.0.1',
        'user_agent' => 'Mozilla/5.0 Test',
        'payload' => '',
        'last_activity' => time(),
    ]);

    $response = $this->actingAs($user)->get(route('user.export-data'));
    $data = json_decode($response->streamedContent(), true);

    expect($data['sessions'])->toBeArray()->not->toBeEmpty()
        ->and($data['sessions'][0]['ip_address'])->toBe('10.0.0.1');
});

it('export contains login attempts', function () {
    $user = User::factory()->create();

    DB::table('login_attempts')->insert([
        'user_id' => $user->id,
        'email' => $user->email,
        'ip_address' => '192.168.1.1',
        'status' => 'success',
        'logged_in_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('user.export-data'));
    $data = json_decode($response->streamedContent(), true);

    expect($data['login_attempts'])->toBeArray()->not->toBeEmpty();
});

it('export does not contain other user data', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    Article::factory()->create(['user_id' => $userB->id]);

    $response = $this->actingAs($userA)->get(route('user.export-data'));
    $data = json_decode($response->streamedContent(), true);

    expect($data['articles'])->toBeArray()->toBeEmpty();
});

it('guest cannot access data export', function () {
    $this->get(route('user.export-data'))->assertRedirect();
});

it('export route has throttle middleware', function () {
    $route = Route::getRoutes()->getByName('user.export-data');

    expect($route)->not->toBeNull();

    $middleware = collect($route->gatherMiddleware());

    expect($middleware->contains(fn ($m) => str_contains($m, 'throttle')))->toBeTrue();
});

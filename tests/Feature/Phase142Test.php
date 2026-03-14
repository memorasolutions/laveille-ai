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
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Modules\Api\Http\Controllers\NotificationApiController;
use Modules\Blog\Models\Article;

uses(RefreshDatabase::class);

test('GET notifications returns paginated list when authenticated', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $user->notifications()->create([
        'id' => Str::uuid(),
        'type' => 'test',
        'data' => ['msg' => 'hello'],
    ]);

    $this->getJson('/api/v1/notifications')->assertOk();
});

test('GET notifications without auth returns 401', function () {
    $this->getJson('/api/v1/notifications')->assertUnauthorized();
});

test('POST notifications/{id}/read marks notification as read', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $notification = $user->notifications()->create([
        'id' => Str::uuid(),
        'type' => 'test',
        'data' => ['msg' => 'hello'],
    ]);

    $this->postJson("/api/v1/notifications/{$notification->id}/read")->assertOk();

    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('DELETE notifications/{id} deletes notification', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $notification = $user->notifications()->create([
        'id' => Str::uuid(),
        'type' => 'test',
        'data' => ['msg' => 'hello'],
    ]);

    $this->deleteJson("/api/v1/notifications/{$notification->id}")->assertOk();
    $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
});

test('PUT profile/password with correct current_password updates password', function () {
    $user = User::factory()->create(['password' => bcrypt('oldpassword')]);
    Sanctum::actingAs($user);

    $this->putJson('/api/v1/profile/password', [
        'current_password' => 'oldpassword',
        'password' => 'NewPassword1!',
        'password_confirmation' => 'NewPassword1!',
    ])->assertOk();

    expect(Hash::check('NewPassword1!', $user->fresh()->password))->toBeTrue();
});

test('PUT profile/password with wrong current_password returns 422', function () {
    $user = User::factory()->create(['password' => bcrypt('oldpassword')]);
    Sanctum::actingAs($user);

    $this->putJson('/api/v1/profile/password', [
        'current_password' => 'wrongpassword',
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ])->assertUnprocessable();
});

test('PUT profile/password validates required fields', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->putJson('/api/v1/profile/password', [])->assertUnprocessable();
});

test('POST articles/{id}/comments creates comment with status pending', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $article = Article::factory()->create(['status' => 'published', 'published_at' => now(), 'user_id' => $user->id]);

    $response = $this->postJson("/api/v1/articles/{$article->id}/comments", [
        'content' => 'Un super commentaire',
    ]);

    $response->assertCreated();
    $response->assertJsonPath('data.status', 'pending');
});

test('POST articles/{id}/comments validates content required', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $article = Article::factory()->create(['status' => 'published', 'published_at' => now(), 'user_id' => $user->id]);

    $this->postJson("/api/v1/articles/{$article->id}/comments", [
        'content' => '',
    ])->assertUnprocessable();
});

test('GET blog/search returns filtered articles', function () {
    $article = Article::factory()->create(['title' => 'Laravel Testing Guide', 'status' => 'published', 'published_at' => now()]);

    $this->getJson('/api/v1/blog/search?q=Laravel')->assertOk();
});

test('GET blog/search without q parameter returns 422', function () {
    $this->getJson('/api/v1/blog/search')->assertUnprocessable();
});

test('NotificationApiController class exists', function () {
    expect(class_exists(NotificationApiController::class))->toBeTrue();
});

<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Backoffice\Models\WebhookEndpoint;
use Modules\Backoffice\Services\AnalyticsService;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\Category;
use Modules\Webhooks\Models\WebhookCall;

uses(RefreshDatabase::class);

// --- AnalyticsService ---

it('analytics service can be resolved', function () {
    expect(app(AnalyticsService::class))->toBeInstanceOf(AnalyticsService::class);
});

it('getOverview returns expected keys', function () {
    $data = app(AnalyticsService::class)->getOverview();
    expect($data)->toHaveKeys([
        'total_users', 'active_users', 'new_users',
        'total_articles', 'published_articles',
        'total_comments', 'pending_comments',
        'total_subscribers', 'total_webhook_calls',
        'webhook_success_rate', 'total_activities',
    ]);
});

it('getOverview counts users correctly', function () {
    User::factory()->count(3)->create(['is_active' => true]);
    User::factory()->create(['is_active' => false]);

    $data = app(AnalyticsService::class)->getOverview();
    expect($data['total_users'])->toBe(4)
        ->and($data['active_users'])->toBe(3);
});

it('getOverview counts articles correctly', function () {
    Article::factory()->count(2)->create(['status' => 'published']);
    Article::factory()->create(['status' => 'draft']);

    $data = app(AnalyticsService::class)->getOverview();
    expect($data['total_articles'])->toBe(3)
        ->and($data['published_articles'])->toBe(2);
});

it('getWebhookStats returns expected keys', function () {
    $data = app(AnalyticsService::class)->getWebhookStats();
    expect($data)->toHaveKeys(['total', 'successful', 'failed', 'pending', 'success_rate', 'by_event']);
});

it('getWebhookStats calculates success rate', function () {
    $endpoint = WebhookEndpoint::factory()->create();
    WebhookCall::create(['webhook_endpoint_id' => $endpoint->id, 'event' => 'test', 'payload' => [], 'status' => 'success']);
    WebhookCall::create(['webhook_endpoint_id' => $endpoint->id, 'event' => 'test', 'payload' => [], 'status' => 'success']);
    WebhookCall::create(['webhook_endpoint_id' => $endpoint->id, 'event' => 'test', 'payload' => [], 'status' => 'failed']);

    $data = app(AnalyticsService::class)->getWebhookStats();
    expect($data['success_rate'])->toBe(66.7)
        ->and($data['successful'])->toBe(2)
        ->and($data['failed'])->toBe(1);
});

it('getContentStats returns expected keys', function () {
    $data = app(AnalyticsService::class)->getContentStats();
    expect($data)->toHaveKeys([
        'articles_created', 'articles_published',
        'comments_created', 'comments_approved', 'by_category',
    ]);
});

it('getContentStats counts by category', function () {
    $category = Category::factory()->create(['is_active' => true]);
    Article::factory()->count(3)->create(['category_id' => $category->id]);

    $data = app(AnalyticsService::class)->getContentStats();
    expect($data['by_category'])->toBeArray()
        ->and($data['articles_created'])->toBe(3);
});

it('getActivityTimeline returns array of date and count', function () {
    $data = app(AnalyticsService::class)->getActivityTimeline(7);
    expect($data)->toBeArray()
        ->and(count($data))->toBeGreaterThanOrEqual(7);

    foreach ($data as $entry) {
        expect($entry)->toHaveKeys(['date', 'count']);
    }
});

it('getUserGrowth returns 12 months data', function () {
    $data = app(AnalyticsService::class)->getUserGrowth(12);
    expect($data)->toBeArray()
        ->and($data)->toHaveCount(12);

    foreach ($data as $entry) {
        expect($entry)->toHaveKeys(['label', 'count']);
    }
});

it('getOverview webhook_success_rate is 0 when no calls', function () {
    $data = app(AnalyticsService::class)->getOverview();
    expect($data['webhook_success_rate'])->toBe(0);
});

// --- Routes admin analytics ---

it('analytics overview route requires auth', function () {
    $this->get(route('admin.analytics.overview'))
        ->assertRedirect();
});

it('analytics overview route requires admin role', function () {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->get(route('admin.analytics.overview'))
        ->assertForbidden();
});

it('analytics overview route returns json for admin', function () {
    $user = User::factory()->create();
    \Spatie\Permission\Models\Role::findOrCreate('super_admin');
    $user->assignRole('super_admin');

    $this->actingAs($user)
        ->getJson(route('admin.analytics.overview'))
        ->assertOk()
        ->assertJsonFragment(['success' => true])
        ->assertJsonStructure(['success', 'data']);
});

it('analytics webhooks route returns json for admin', function () {
    $user = User::factory()->create();
    \Spatie\Permission\Models\Role::findOrCreate('super_admin');
    $user->assignRole('super_admin');

    $this->actingAs($user)
        ->getJson(route('admin.analytics.webhooks'))
        ->assertOk()
        ->assertJsonFragment(['success' => true]);
});

it('analytics content route returns json for admin', function () {
    $user = User::factory()->create();
    \Spatie\Permission\Models\Role::findOrCreate('super_admin');
    $user->assignRole('super_admin');

    $this->actingAs($user)
        ->getJson(route('admin.analytics.content'))
        ->assertOk()
        ->assertJsonFragment(['success' => true]);
});

it('analytics activity route returns json for admin', function () {
    $user = User::factory()->create();
    \Spatie\Permission\Models\Role::findOrCreate('super_admin');
    $user->assignRole('super_admin');

    $this->actingAs($user)
        ->getJson(route('admin.analytics.activity'))
        ->assertOk()
        ->assertJsonFragment(['success' => true]);
});

it('analytics routes accept days parameter', function () {
    $user = User::factory()->create();
    \Spatie\Permission\Models\Role::findOrCreate('super_admin');
    $user->assignRole('super_admin');

    $this->actingAs($user)
        ->getJson(route('admin.analytics.overview', ['days' => 7]))
        ->assertOk();
});

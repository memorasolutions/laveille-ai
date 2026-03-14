<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\User;
use Illuminate\Support\Carbon;
use Modules\Pages\Models\StaticPage;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->testUser = User::factory()->create();
});

function createScheduledPage(array $attributes = []): StaticPage
{
    $user = test()->testUser;

    return StaticPage::create(array_merge([
        'user_id' => $user->id,
        'title' => 'Test Page',
        'slug' => 'test-page-'.uniqid(),
        'content' => 'Test content',
        'status' => 'draft',
        'template' => 'default',
    ], $attributes));
}

test('scopePublishedNow returns only currently published content', function () {
    $published = createScheduledPage(['status' => 'published', 'published_at' => now()->subDay()]);
    $scheduled = createScheduledPage(['status' => 'published', 'published_at' => now()->addDay()]);
    $expired = createScheduledPage(['status' => 'published', 'published_at' => now()->subDays(2), 'expired_at' => now()->subDay()]);
    $draft = createScheduledPage(['status' => 'draft', 'published_at' => now()->subDay()]);
    $noDate = createScheduledPage(['status' => 'published', 'published_at' => null]);

    $results = StaticPage::publishedNow()->pluck('id');

    expect($results)->toContain($published->id)
        ->toContain($noDate->id)
        ->not->toContain($scheduled->id)
        ->not->toContain($expired->id)
        ->not->toContain($draft->id);
});

test('scopeScheduled returns only future-dated content', function () {
    createScheduledPage(['published_at' => now()->subDay()]);
    $scheduled = createScheduledPage(['published_at' => now()->addDay()]);
    createScheduledPage(['published_at' => null]);

    $results = StaticPage::scheduled()->pluck('id');

    expect($results)->toHaveCount(1)
        ->toContain($scheduled->id);
});

test('scopeExpired returns only past-expired content', function () {
    createScheduledPage(['expired_at' => now()->addDay()]);
    $expired = createScheduledPage(['expired_at' => now()->subDay()]);
    createScheduledPage(['expired_at' => null]);

    $results = StaticPage::expired()->pluck('id');

    expect($results)->toHaveCount(1)
        ->toContain($expired->id);
});

test('isPublishedNow returns correct boolean', function () {
    $page = createScheduledPage(['status' => 'published', 'published_at' => now()->subDay()]);
    expect($page->isPublishedNow())->toBeTrue();

    $page->status = 'draft';
    expect($page->isPublishedNow())->toBeFalse();

    $page->status = 'published';
    $page->published_at = now()->addDay();
    expect($page->isPublishedNow())->toBeFalse();

    $page->published_at = now()->subDay();
    $page->expired_at = now()->subHour();
    expect($page->isPublishedNow())->toBeFalse();
});

test('isScheduled returns correct boolean', function () {
    $page = createScheduledPage(['published_at' => now()->addDay()]);
    expect($page->isScheduled())->toBeTrue();

    $page->published_at = now()->subDay();
    expect($page->isScheduled())->toBeFalse();

    $page->published_at = null;
    expect($page->isScheduled())->toBeFalse();
});

test('isExpired returns correct boolean', function () {
    $page = createScheduledPage(['expired_at' => now()->subDay()]);
    expect($page->isExpired())->toBeTrue();

    $page->expired_at = now()->addDay();
    expect($page->isExpired())->toBeFalse();

    $page->expired_at = null;
    expect($page->isExpired())->toBeFalse();
});

test('publishNow sets published_at and status', function () {
    $page = createScheduledPage(['status' => 'draft', 'published_at' => null]);

    $page->publishNow();

    expect($page->status)->toBe('published')
        ->and($page->published_at)->not->toBeNull()
        ->and(Carbon::parse($page->published_at)->lte(now()))->toBeTrue();
});

test('schedule sets published_at and optional expired_at', function () {
    $page = createScheduledPage();

    $publishAt = now()->addDays(2)->startOfSecond();
    $expireAt = now()->addDays(10)->startOfSecond();

    $page->schedule($publishAt, $expireAt);

    expect(Carbon::parse($page->published_at)->equalTo($publishAt))->toBeTrue()
        ->and(Carbon::parse($page->expired_at)->equalTo($expireAt))->toBeTrue();
});

test('expired content excluded from publishedNow scope', function () {
    createScheduledPage([
        'status' => 'published',
        'published_at' => now()->subDays(5),
        'expired_at' => now()->subDay(),
    ]);

    $results = StaticPage::publishedNow()->get();

    expect($results)->toHaveCount(0);
});

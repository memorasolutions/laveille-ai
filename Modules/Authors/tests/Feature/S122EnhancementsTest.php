<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Modules\Authors\Livewire\AuthorAnalyticsWidget;
use Modules\Authors\Models\AuthorPost;
use Modules\Authors\Models\AuthorProfile;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makeAuthorS122(): AuthorProfile
{
    $user = User::factory()->create(['email' => 'a-'.Str::random(4).'@example.com']);

    return AuthorProfile::create([
        'user_id' => $user->id,
        'slug' => 's122-'.strtolower(Str::random(6)),
        'display_name' => 'S122 Test',
        'tier' => 'free',
    ]);
}

function makeS122Post(AuthorProfile $author, array $overrides = []): AuthorPost
{
    return AuthorPost::create(array_merge([
        'author_profile_id' => $author->id,
        'slug' => 'post-'.strtolower(Str::random(6)),
        'title' => 'Post '.Str::random(4),
        'body_markdown' => 'Lorem ipsum dolor sit amet consectetur.',
        'body_html' => '<p>Lorem ipsum dolor sit amet consectetur.</p>',
        'status' => 'published',
        'visibility' => 'public',
        'published_at' => now()->subMinute(),
        'tags' => [],
        'reading_time_minutes' => 1,
        'views_count' => 0,
    ], $overrides));
}

it('Tag archive lists posts having the tag', function () {
    $author = makeAuthorS122();
    makeS122Post($author, ['title' => 'Article IA', 'tags' => ['ia', 'llm']]);
    makeS122Post($author, ['title' => 'Article Cuisine', 'tags' => ['cuisine']]);

    $response = $this->get('/@'.$author->slug.'/tag/ia');
    $response->assertStatus(200);
    $response->assertSee('Article IA', false);
    $response->assertDontSee('Article Cuisine');
});

it('Tag archive returns 404 for unknown tag', function () {
    $author = makeAuthorS122();
    makeS122Post($author, ['tags' => ['ia']]);

    $this->get('/@'.$author->slug.'/tag/nonexistent')->assertStatus(404);
});

it('Tag archive renders Schema.org CollectionPage', function () {
    $author = makeAuthorS122();
    makeS122Post($author, ['tags' => ['veille']]);

    $response = $this->get('/@'.$author->slug.'/tag/veille');
    $response->assertStatus(200);
    $response->assertSee('CollectionPage', false);
    $response->assertSee('Fil d\'Ariane', false);
});

it('Post page tag chips link to tag archive', function () {
    $author = makeAuthorS122();
    $post = makeS122Post($author, ['tags' => ['robotique']]);

    $response = $this->get('/@'.$author->slug.'/'.$post->slug);
    $response->assertStatus(200);
    $response->assertSee('/@'.$author->slug.'/tag/robotique', false);
});

it('PublishScheduledPostsCommand publishes due scheduled posts', function () {
    $author = makeAuthorS122();
    $post = makeS122Post($author, [
        'status' => 'scheduled',
        'published_at' => now()->subMinutes(5),
    ]);

    $this->artisan('authors:publish-scheduled')->assertSuccessful();

    expect($post->fresh()->status)->toBe('published');
});

it('PublishScheduledPostsCommand skips future scheduled posts', function () {
    $author = makeAuthorS122();
    $post = makeS122Post($author, [
        'status' => 'scheduled',
        'published_at' => now()->addDays(2),
    ]);

    $this->artisan('authors:publish-scheduled')->assertSuccessful();

    expect($post->fresh()->status)->toBe('scheduled');
});

it('AuthorPost isScheduled detects future scheduled status', function () {
    $author = makeAuthorS122();
    $scheduled = makeS122Post($author, ['status' => 'scheduled', 'published_at' => now()->addDay()]);
    $published = makeS122Post($author, ['status' => 'published', 'published_at' => now()->subDay()]);

    expect($scheduled->isScheduled())->toBeTrue()
        ->and($published->isScheduled())->toBeFalse();
});

it('Scheduled post not visible on public mini-site timeline', function () {
    $author = makeAuthorS122();
    makeS122Post($author, ['title' => 'Hidden Scheduled', 'status' => 'scheduled', 'published_at' => now()->addDay()]);

    $count = AuthorPost::published()->public()->where('author_profile_id', $author->id)->count();
    expect($count)->toBe(0);
});

it('AuthorAnalyticsWidget renders with 30-day series', function () {
    $author = makeAuthorS122();
    makeS122Post($author, ['published_at' => now()->subDays(2)]);

    $component = Livewire::test(AuthorAnalyticsWidget::class, ['authorProfileId' => $author->id]);

    expect($component->viewData('postsSeries'))->toHaveCount(30)
        ->and($component->viewData('commentsSeries'))->toHaveCount(30)
        ->and($component->viewData('subscribersSeries'))->toHaveCount(30);
});

it('AuthorAnalyticsWidget computes totals + sparkline path', function () {
    $author = makeAuthorS122();
    makeS122Post($author, ['published_at' => now()->subDays(1)]);
    makeS122Post($author, ['published_at' => now()->subDays(3)]);

    $component = Livewire::test(AuthorAnalyticsWidget::class, ['authorProfileId' => $author->id]);

    expect($component->viewData('postsTotal'))->toBe(2)
        ->and($component->viewData('postsPath'))->toContain(',');
});

it('Draft preview accessible with valid signature even when draft', function () {
    $author = makeAuthorS122();
    $post = makeS122Post($author, ['title' => 'Brouillon secret', 'status' => 'draft', 'published_at' => null]);

    $url = URL::signedRoute('authors.post.preview', [
        'slug' => $author->slug,
        'postSlug' => $post->slug,
    ], now()->addDays(7));

    $response = $this->get($url);
    $response->assertStatus(200);
    $response->assertSee('Aperçu brouillon', false);
    $response->assertSee('Brouillon secret', false);
});

it('Draft preview rejects invalid signature', function () {
    $author = makeAuthorS122();
    $post = makeS122Post($author, ['status' => 'draft']);

    $this->get('/@'.$author->slug.'/preview/'.$post->slug.'?signature=invalid')
        ->assertStatus(403);
});

it('Draft post not accessible via normal public route', function () {
    $author = makeAuthorS122();
    $post = makeS122Post($author, ['status' => 'draft', 'published_at' => null]);

    $this->get('/@'.$author->slug.'/'.$post->slug)->assertStatus(404);
});

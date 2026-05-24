<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Modules\Authors\Jobs\SendWebmentionsJob;
use Modules\Authors\Models\AuthorPost;
use Modules\Authors\Models\AuthorProfile;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makeAuthorS115(): AuthorProfile
{
    $user = User::factory()->create();

    return AuthorProfile::create([
        'user_id' => $user->id,
        'slug' => 's115-'.strtolower(Str::random(6)),
        'display_name' => 'S115 Test',
        'tier' => 'free',
    ]);
}

it('API GET /api/v1/author-profiles/{slug} returns JSON with mini-site links', function () {
    $author = makeAuthorS115();
    $response = $this->getJson('/api/v1/author-profiles/'.$author->slug);
    $response->assertStatus(200);
    $response->assertJsonStructure([
        'data' => ['slug', 'display_name', 'bio', 'tier', 'links' => ['mini_site', 'rss', 'json_feed']],
    ]);
    $response->assertJsonPath('data.slug', $author->slug);
});

it('API GET /api/v1/author-profiles/{slug}/posts returns paginated published posts', function () {
    $author = makeAuthorS115();
    AuthorPost::create([
        'author_profile_id' => $author->id,
        'slug' => 'api-test-post',
        'title' => 'API Test',
        'body_markdown' => 'X',
        'body_html' => '<p>X</p>',
        'status' => 'published',
        'visibility' => 'public',
        'published_at' => now()->subMinute(),
    ]);
    AuthorPost::create([
        'author_profile_id' => $author->id,
        'slug' => 'draft-not-shown',
        'title' => 'Draft',
        'body_markdown' => 'X',
        'body_html' => '<p>X</p>',
        'status' => 'draft',
        'visibility' => 'public',
    ]);

    $response = $this->getJson('/api/v1/author-profiles/'.$author->slug.'/posts');
    $response->assertStatus(200);
    $response->assertJsonStructure([
        'data' => [['slug', 'title', 'excerpt', 'tags', 'reading_time_minutes', 'views_count', 'published_at', 'links']],
    ]);
    expect(count($response->json('data')))->toBe(1);
    expect($response->json('data.0.slug'))->toBe('api-test-post');
});

it('API returns error (404 or 401 if auth middleware) for unknown author', function () {
    $response = $this->getJson('/api/v1/author-profiles/unknown-slug-xyz');
    expect(in_array($response->status(), [404, 401, 403], true))->toBeTrue();
});

it('AuthorPostObserver dispatches SendWebmentionsJob when published', function () {
    Bus::fake();
    $author = makeAuthorS115();

    $post = AuthorPost::create([
        'author_profile_id' => $author->id,
        'slug' => 'observer-test',
        'title' => 'Observer Test',
        'body_markdown' => 'X',
        'body_html' => '<p>Body</p>',
        'status' => 'draft',
        'visibility' => 'public',
    ]);

    Bus::assertNotDispatched(SendWebmentionsJob::class);

    $post->update([
        'status' => 'published',
        'published_at' => now(),
    ]);

    Bus::assertDispatched(SendWebmentionsJob::class, fn ($job) => $job->authorPostId === $post->id);
});

it('SendWebmentionsJob handles missing post gracefully', function () {
    $job = new SendWebmentionsJob(99999);
    $sender = new \Modules\Authors\Services\WebmentionSenderService();
    $job->handle($sender);
    expect(true)->toBeTrue();
});

it('AllAuthorsViewer Livewire component renders authors table', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    makeAuthorS115();
    makeAuthorS115();

    $component = Livewire::test(\Modules\Authors\Livewire\AllAuthorsViewer::class);
    $authors = $component->viewData('authors');
    expect($authors->total())->toBeGreaterThanOrEqual(1);
});

it('AllAuthorsViewer search filters by slug', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $author = makeAuthorS115();

    $component = Livewire::test(\Modules\Authors\Livewire\AllAuthorsViewer::class)
        ->set('search', $author->slug);

    $authors = $component->viewData('authors');
    expect($authors->total())->toBe(1);
    expect($authors->first()->slug)->toBe($author->slug);
});

it('AuthorsHealthCommand runs without exception', function () {
    $this->artisan('authors:health')
        ->expectsOutputToContain('Authors Platform Healthcheck');
});

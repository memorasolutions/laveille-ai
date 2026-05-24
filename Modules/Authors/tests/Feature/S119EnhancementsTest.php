<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Modules\Authors\Livewire\AuthorRelatedPosts;
use Modules\Authors\Models\AuthorPost;
use Modules\Authors\Models\AuthorProfile;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makeAuthorS119(): AuthorProfile
{
    $user = User::factory()->create(['email' => 'a-'.Str::random(4).'@example.com']);

    return AuthorProfile::create([
        'user_id' => $user->id,
        'slug' => 's119-'.strtolower(Str::random(6)),
        'display_name' => 'S119 Test',
        'tier' => 'free',
    ]);
}

function makeS119Post(AuthorProfile $author, array $overrides = []): AuthorPost
{
    return AuthorPost::create(array_merge([
        'author_profile_id' => $author->id,
        'slug' => 'post-'.strtolower(Str::random(6)),
        'title' => 'Post '.Str::random(4),
        'body_markdown' => 'Lorem ipsum dolor sit amet.',
        'body_html' => '<p>Lorem ipsum dolor sit amet.</p>',
        'status' => 'published',
        'visibility' => 'public',
        'published_at' => now()->subMinute(),
        'tags' => [],
        'reading_time' => 1,
    ], $overrides));
}

it('AuthorRelatedPosts mounts with author + post + tags', function () {
    $author = makeAuthorS119();
    $post = makeS119Post($author, ['tags' => ['ia', 'llm']]);
    $tags = ['ia', 'llm'];

    Livewire::test(AuthorRelatedPosts::class, [
        'authorProfileId' => $author->id,
        'currentPostId' => $post->id,
        'currentTags' => $tags,
    ])
        ->assertSet('authorProfileId', $author->id)
        ->assertSet('currentPostId', $post->id)
        ->assertSet('currentTags', $tags);
});

it('AuthorRelatedPosts filters by same author only', function () {
    $author1 = makeAuthorS119();
    $author2 = makeAuthorS119();

    $post1 = makeS119Post($author1);
    makeS119Post($author1);
    makeS119Post($author1);
    makeS119Post($author2);

    $component = Livewire::test(AuthorRelatedPosts::class, [
        'authorProfileId' => $author1->id,
        'currentPostId' => $post1->id,
        'currentTags' => [],
    ]);

    expect($component->viewData('relatedPosts')->count())->toBe(2);
});

it('AuthorRelatedPosts filters by shared tags', function () {
    $author = makeAuthorS119();

    $currentPost = makeS119Post($author, ['tags' => ['ia', 'llm']]);
    $matchPost = makeS119Post($author, ['tags' => ['ia', 'crypto']]);
    $noMatchPost = makeS119Post($author, ['tags' => ['cuisine']]);

    $component = Livewire::test(AuthorRelatedPosts::class, [
        'authorProfileId' => $author->id,
        'currentPostId' => $currentPost->id,
        'currentTags' => ['ia', 'llm'],
    ]);

    $relatedPosts = $component->viewData('relatedPosts');
    expect($relatedPosts->pluck('id'))->toContain($matchPost->id)
        ->and($relatedPosts->pluck('id'))->not->toContain($noMatchPost->id);
});

it('AuthorRelatedPosts excludes current post id', function () {
    $author = makeAuthorS119();
    $post1 = makeS119Post($author);
    $post2 = makeS119Post($author);

    $component = Livewire::test(AuthorRelatedPosts::class, [
        'authorProfileId' => $author->id,
        'currentPostId' => $post1->id,
        'currentTags' => [],
    ]);

    $relatedPosts = $component->viewData('relatedPosts');
    expect($relatedPosts->pluck('id'))->not->toContain($post1->id)
        ->and($relatedPosts->pluck('id'))->toContain($post2->id);
});

it('AuthorRelatedPosts limits to 3 results', function () {
    $author = makeAuthorS119();
    for ($i = 0; $i < 5; $i++) {
        makeS119Post($author);
    }
    $currentPost = makeS119Post($author);

    $component = Livewire::test(AuthorRelatedPosts::class, [
        'authorProfileId' => $author->id,
        'currentPostId' => $currentPost->id,
        'currentTags' => [],
    ]);

    expect($component->viewData('relatedPosts')->count())->toBeLessThanOrEqual(3);
});

it('AuthorRelatedPosts returns empty when no related', function () {
    $author = makeAuthorS119();
    $post = makeS119Post($author);

    $component = Livewire::test(AuthorRelatedPosts::class, [
        'authorProfileId' => $author->id,
        'currentPostId' => $post->id,
        'currentTags' => [],
    ]);

    expect($component->viewData('relatedPosts')->isEmpty())->toBeTrue();
});

it('Post page renders share buttons section', function () {
    $author = makeAuthorS119();
    $post = makeS119Post($author);

    $response = $this->get('/@'.$author->slug.'/'.$post->slug);
    $response->assertStatus(200);
    $response->assertSee('Partager cet article', false);
    $response->assertSee('twitter.com/intent/tweet', false);
    $response->assertSee('linkedin.com/sharing', false);
    $response->assertSee('mailto:', false);
});

it('Post page renders reading progress bar', function () {
    $author = makeAuthorS119();
    $post = makeS119Post($author);

    $response = $this->get('/@'.$author->slug.'/'.$post->slug);
    $response->assertStatus(200);
    $response->assertSee('lv-reading-progress', false);
    $response->assertSee('Progression de lecture', false);
});

it('Post page renders related posts container', function () {
    $author = makeAuthorS119();
    $post1 = makeS119Post($author);
    makeS119Post($author);

    $response = $this->get('/@'.$author->slug.'/'.$post1->slug);
    $response->assertStatus(200);
    $response->assertSee('lv-related', false);
});

it('Mini-site /@slug renders search toggle + follow button', function () {
    $author = makeAuthorS119();

    $response = $this->get('/@'.$author->slug);
    $response->assertStatus(200);
    $response->assertSee('Rechercher dans les articles', false);
    $response->assertSee('Suivre', false);
    $response->assertSee('lv-followed-authors', false);
});

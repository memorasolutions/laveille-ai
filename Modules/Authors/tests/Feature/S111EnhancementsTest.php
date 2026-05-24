<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Modules\Authors\Models\AuthorPost;
use Modules\Authors\Models\AuthorProfile;
use Modules\Authors\Models\AuthorWebmention;
use Modules\Authors\Services\WebmentionService;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makeAuthorS111(): AuthorProfile
{
    $user = User::factory()->create();

    return AuthorProfile::create([
        'user_id' => $user->id,
        'slug' => 's111-'.strtolower(Str::random(6)),
        'display_name' => 'S111 Test',
        'tier' => 'free',
    ]);
}

it('AuthorEditor publish transforms oEmbed URLs in body markdown', function () {
    $author = makeAuthorS111();
    $this->actingAs($author->user);

    Livewire::test(\Modules\Authors\Livewire\AuthorEditor::class, ['authorProfile' => $author])
        ->set('title', 'Mon article avec embed')
        ->set('body_markdown', "Voici une vidéo :\n\nhttps://www.youtube.com/watch?v=embed123\n\nFin de l'article qui dépasse vingt caractères.")
        ->call('publish');

    $post = AuthorPost::first();
    expect($post->body_html)->toContain('youtube-nocookie.com/embed/embed123');
    expect($post->body_html)->toContain('lv-embed--youtube');
});

it('Service Worker is registered in mini-site', function () {
    $author = makeAuthorS111();
    $response = $this->get('/@'.$author->slug);
    $response->assertStatus(200);
    $body = $response->getContent();
    expect($body)->toContain('manifest.webmanifest');
    expect($body)->toContain('serviceWorker.register');
    expect($body)->toContain('rel="webmention"');
});

it('webmention endpoint accepts valid source + target URLs', function () {
    $author = makeAuthorS111();
    $post = AuthorPost::create([
        'author_profile_id' => $author->id,
        'slug' => 'webmention-target',
        'title' => 'Webmention Target',
        'body_markdown' => 'Body',
        'body_html' => '<p>Body</p>',
        'status' => 'published',
        'visibility' => 'public',
        'published_at' => now()->subMinute(),
    ]);

    $targetUrl = url('/@'.$author->slug.'/'.$post->slug);
    $response = $this->post('/webmention', [
        'source' => 'https://external-blog.example.com/post-mentionant',
        'target' => $targetUrl,
    ]);

    expect(in_array($response->status(), [200, 201, 202], true))->toBeTrue();
});

it('webmention endpoint rejects missing source', function () {
    $response = $this->post('/webmention', ['target' => 'https://laveille.ai/@x/y']);
    expect($response->status())->toBe(400);
});

it('WebmentionService findPostFromTargetUrl matches valid /@slug/post-slug', function () {
    $author = makeAuthorS111();
    $post = AuthorPost::create([
        'author_profile_id' => $author->id,
        'slug' => 'test-find',
        'title' => 'X',
        'body_markdown' => 'X',
        'body_html' => '<p>X</p>',
        'status' => 'published',
        'visibility' => 'public',
        'published_at' => now()->subMinute(),
    ]);

    $service = new WebmentionService();
    $found = $service->findPostFromTargetUrl(url('/@'.$author->slug.'/test-find'));

    expect($found)->not->toBeNull();
    expect($found->id)->toBe($post->id);
});

it('WebmentionService verify returns true when source contains target backlink', function () {
    $author = makeAuthorS111();
    $post = AuthorPost::create([
        'author_profile_id' => $author->id,
        'slug' => 'backlink-test',
        'title' => 'X',
        'body_markdown' => 'X',
        'body_html' => '<p>X</p>',
        'status' => 'published',
        'visibility' => 'public',
        'published_at' => now()->subMinute(),
    ]);

    Http::fake([
        '*' => Http::response('<html><body><a href="https://laveille.ai/@'.$author->slug.'/backlink-test">backlink</a></body></html>', 200),
    ]);

    $webmention = AuthorWebmention::create([
        'author_post_id' => $post->id,
        'target_url' => 'https://laveille.ai/@'.$author->slug.'/backlink-test',
        'source_url' => 'https://external.com/post',
        'received_at' => now(),
    ]);

    $service = new WebmentionService();
    $verified = $service->verify($webmention);

    expect($verified)->toBeTrue();
    $webmention->refresh();
    expect($webmention->verified_at)->not->toBeNull();
});

it('ActivityLog viewer Livewire component mounts with authorProfileId', function () {
    $author = makeAuthorS111();
    $this->actingAs($author->user);

    $component = Livewire::test(\Modules\Authors\Livewire\AuthorActivityLogViewer::class, ['authorProfileId' => $author->id]);
    $component->assertSet('authorProfileId', $author->id);
    $component->assertSet('period', '7d');
    $component->assertSet('logName', 'all');
});

it('ActivityLog viewer setPeriod and setLogName filters work', function () {
    $author = makeAuthorS111();
    $this->actingAs($author->user);

    Livewire::test(\Modules\Authors\Livewire\AuthorActivityLogViewer::class, ['authorProfileId' => $author->id])
        ->call('setPeriod', '30d')
        ->assertSet('period', '30d')
        ->call('setLogName', 'author_post')
        ->assertSet('logName', 'author_post')
        ->call('setLogName', 'invalid-value')
        ->assertSet('logName', 'author_post');
});

it('PWA manifest is accessible and contains app name + theme + locale', function () {
    $response = $this->get('/manifest.webmanifest');
    $response->assertStatus(200);
    $content = $response->getContent();
    expect($content)->toContain('La veille');
    expect($content)->toMatch('/theme_color[\s":]+#/');
    expect($content)->toMatch('/lang["\s:]+["\s]+fr/i');
    expect($content)->toContain('"standalone"');
});

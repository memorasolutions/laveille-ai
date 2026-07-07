<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Authors\Models\AuthorPost;
use Modules\Authors\Models\AuthorProfile;
use Modules\Authors\Services\AuthorsSitemapService;
use Modules\Authors\Services\OembedService;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makeAuthorS110(): AuthorProfile
{
    $user = User::factory()->create();

    return AuthorProfile::create([
        'user_id' => $user->id,
        'slug' => 's110-'.strtolower(Str::random(6)),
        'display_name' => 'S110 Test',
        'tier' => 'free',
    ]);
}

it('AuthorsSitemapService generates valid sitemap XML with authors and posts', function () {
    config(['app.url' => 'https://laveille.ai']);
    app('url')->forceRootUrl('https://laveille.ai');

    $author = makeAuthorS110();
    AuthorPost::create([
        'author_profile_id' => $author->id,
        'slug' => 'mon-post',
        'title' => 'Mon Post',
        'body_markdown' => 'X',
        'body_html' => '<p>X</p>',
        'status' => 'published',
        'visibility' => 'public',
        'published_at' => now()->subMinute(),
    ]);

    $service = new AuthorsSitemapService();
    $xml = $service->generate();

    expect($xml)->toContain('<?xml version="1.0" encoding="UTF-8"?>')
        ->toContain('xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"')
        ->toContain('<loc>https://laveille.ai/@'.$author->slug.'</loc>')
        ->toContain('<loc>https://laveille.ai/@'.$author->slug.'/mon-post</loc>')
        ->toContain('<priority>0.8</priority>')
        ->toContain('<priority>0.6</priority>');
});

it('sitemap route /sitemap-authors.xml returns 200 with xml content-type', function () {
    $response = $this->get('/sitemap-authors.xml');
    $response->assertStatus(200);
    $contentType = $response->headers->get('Content-Type');
    expect($contentType)->toContain('xml');
});

it('OembedService detects YouTube URL with v=ID format', function () {
    $service = new OembedService();
    $match = $service->detect('https://www.youtube.com/watch?v=dQw4w9WgXcQ');

    expect($match)->not->toBeNull()
        ->and($match['provider'])->toBe('youtube')
        ->and($match['id'])->toBe('dQw4w9WgXcQ');
});

it('OembedService detects YouTube short URL youtu.be format', function () {
    $service = new OembedService();
    $match = $service->detect('https://youtu.be/abc12345');

    expect($match['provider'])->toBe('youtube')
        ->and($match['id'])->toBe('abc12345');
});

it('OembedService detects Spotify track URL', function () {
    $service = new OembedService();
    $match = $service->detect('https://open.spotify.com/track/4iV5W9uYEdYUVa79Axb7Rh');

    expect($match['provider'])->toBe('spotify')
        ->and($match['id'])->toBe('4iV5W9uYEdYUVa79Axb7Rh')
        ->and($match['type'])->toBe('track');
});

it('OembedService returns null for non-matching URL', function () {
    $service = new OembedService();
    expect($service->detect('https://example.com/random'))->toBeNull();
});

it('OembedService toEmbedHtml generates iframe for YouTube', function () {
    $service = new OembedService();
    $html = $service->toEmbedHtml('https://www.youtube.com/watch?v=dQw4w9WgXcQ');

    expect($html)->toContain('youtube-nocookie.com/embed/dQw4w9WgXcQ')
        ->toContain('loading="lazy"')
        ->toContain('allowfullscreen');
});

it('OembedService transformMarkdown replaces lone URLs by embeds and preserves inline', function () {
    $service = new OembedService();

    $markdown = "Hello world\n\nhttps://www.youtube.com/watch?v=test1234\n\nMore text.";
    $result = $service->transformMarkdown($markdown);

    expect($result)->toContain('Hello world')
        ->toContain('youtube-nocookie.com/embed/test1234')
        ->toContain('More text.');

    $inline = "Voir https://www.youtube.com/watch?v=inline1 dans la phrase.";
    expect($service->transformMarkdown($inline))->toContain('Voir https://www.youtube.com/watch?v=inline1');
});

it('AuthorPost has LogsActivity trait registered for compliance Loi 25', function () {
    $traits = class_uses(AuthorPost::class);
    expect($traits)->toHaveKey(\Spatie\Activitylog\Traits\LogsActivity::class);
});

it('post route renders mode lecture toggle button', function () {
    $author = makeAuthorS110();
    $post = AuthorPost::create([
        'author_profile_id' => $author->id,
        'slug' => 'reader-test',
        'title' => 'Reader Test',
        'body_markdown' => 'Body',
        'body_html' => '<p>Body</p>',
        'status' => 'published',
        'visibility' => 'public',
        'published_at' => now()->subMinute(),
    ]);

    $response = $this->get('/@'.$author->slug.'/'.$post->slug);
    $response->assertStatus(200);
    $response->assertSee('lv-reader-toggle', false);
    $response->assertSee('Mode lecture', false);
});

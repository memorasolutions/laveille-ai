<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\Authors\Models\AuthorPost;
use Modules\Authors\Models\AuthorProfile;
use Modules\Authors\Services\SpamDetectionService;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makeAuthorS109(): AuthorProfile
{
    $user = User::factory()->create();

    return AuthorProfile::create([
        'user_id' => $user->id,
        'slug' => 's109-'.strtolower(Str::random(6)),
        'display_name' => 'S109 Test',
        'tier' => 'free',
    ]);
}

it('mini-site contains breadcrumb navigation with aria-label', function () {
    $author = makeAuthorS109();
    $response = $this->get('/@'.$author->slug);
    $response->assertStatus(200);
    $response->assertSee("aria-label=\"Fil d'Ariane\"", false);
    $response->assertSee('aria-current="page"', false);
});

it('mini-site theme-icon SVG has role and aria-label', function () {
    $author = makeAuthorS109();
    $response = $this->get('/@'.$author->slug);
    $response->assertSee('id="theme-icon" role="img" aria-label=', false);
});

it('mini-site response includes Cache-Control public + ETag for guests', function () {
    $author = makeAuthorS109();
    $response = $this->get('/@'.$author->slug);
    $cacheControl = $response->headers->get('Cache-Control');
    expect($cacheControl)->toContain('public');
    expect($cacheControl)->toContain('s-maxage=');
    expect($response->headers->get('ETag'))->not->toBeNull();
});

it('mini-site authenticated user receives private cache headers', function () {
    $user = User::factory()->create();
    $author = makeAuthorS109();
    $this->actingAs($user);
    $response = $this->get('/@'.$author->slug);
    $cacheControl = $response->headers->get('Cache-Control');
    expect($cacheControl)->toContain('private');
});

it('jsonld helpers blog_posting + breadcrumb + faq_page exist and return valid arrays', function () {
    expect(function_exists('lv_jsonld_blog_posting'))->toBeTrue();
    expect(function_exists('lv_jsonld_breadcrumb'))->toBeTrue();
    expect(function_exists('lv_jsonld_faq_page'))->toBeTrue();

    $author = makeAuthorS109();
    $post = AuthorPost::create([
        'author_profile_id' => $author->id,
        'slug' => 'test-jsonld',
        'title' => 'Test JSON-LD',
        'body_markdown' => 'Body content',
        'body_html' => '<p>Body content</p>',
        'status' => 'published',
        'visibility' => 'public',
        'published_at' => now(),
    ]);

    $bp = lv_jsonld_blog_posting($post, $author);
    expect($bp['@type'])->toBe('BlogPosting');
    expect($bp['headline'])->toBe('Test JSON-LD');

    $bc = lv_jsonld_breadcrumb([
        ['name' => 'Accueil', 'url' => 'https://laveille.ai/'],
        ['name' => 'Test', 'url' => 'https://laveille.ai/test'],
    ]);
    expect($bc['@type'])->toBe('BreadcrumbList');
    expect($bc['itemListElement'])->toHaveCount(2);
    expect($bc['itemListElement'][0]['position'])->toBe(1);

    $faq = lv_jsonld_faq_page([
        ['question' => 'Q1?', 'answer' => 'A1.'],
    ]);
    expect($faq['@type'])->toBe('FAQPage');
    expect($faq['mainEntity'][0]['@type'])->toBe('Question');
});

it('RSS feed includes content:encoded + atom:author + dc:creator namespaces', function () {
    $author = makeAuthorS109();
    $response = $this->get('/@'.$author->slug.'/feed.xml');
    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'application/rss+xml; charset=utf-8');
    $body = $response->getContent();
    expect($body)->toContain('xmlns:content=');
    expect($body)->toContain('xmlns:atom=');
    expect($body)->toContain('xmlns:dc=');
    expect($body)->toContain('<copyright>');
    expect($body)->toContain('laveille.ai Authors');
});

it('post controller injects BlogPosting + BreadcrumbList JSON-LD on public post', function () {
    $author = makeAuthorS109();
    $post = AuthorPost::create([
        'author_profile_id' => $author->id,
        'slug' => 'public-post-jsonld',
        'title' => 'Public Post JSON-LD',
        'body_markdown' => 'Body',
        'body_html' => '<p>Body content here</p>',
        'status' => 'published',
        'visibility' => 'public',
        'published_at' => now()->subMinute(),
        'excerpt' => 'Short excerpt',
    ]);

    $response = $this->get('/@'.$author->slug.'/'.$post->slug);
    $response->assertStatus(200);
    $body = $response->getContent();
    expect($body)->toContain('"BlogPosting"');
    expect($body)->toContain('"BreadcrumbList"');
    expect($body)->toContain('"position":1');
});

it('SpamDetectionService akismetCheck triggers HTTP call when API key configured', function () {
    config(['services.akismet.key' => 'test-akismet-key']);
    config(['app.url' => 'https://laveille.ai']);

    Http::fake([
        'rest.akismet.com/*' => Http::response('false', 200),
    ]);

    $service = new SpamDetectionService();
    $score = $service->score('hello world', 'user@example.com', '1.2.3.4', 'Mozilla/5.0');

    expect($score)->toBe(10);
    Http::assertSent(fn ($request) => str_contains($request->url(), 'akismet.com'));
});

it('SpamDetectionService akismetCheck flags spam when API returns true', function () {
    config(['services.akismet.key' => 'test-akismet-key']);
    config(['app.url' => 'https://laveille.ai']);

    Http::fake([
        'rest.akismet.com/*' => Http::response('true', 200),
    ]);

    $service = new SpamDetectionService();
    $score = $service->score('buy viagra now click here', 'spam@example.com', '1.2.3.4', 'curl/7.0');

    expect($score)->toBeGreaterThanOrEqual(70);
});

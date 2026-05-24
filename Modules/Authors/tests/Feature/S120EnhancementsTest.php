<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Authors\Models\AuthorPost;
use Modules\Authors\Models\AuthorProfile;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makeAuthorS120(): AuthorProfile
{
    $user = User::factory()->create(['email' => 'a-'.Str::random(4).'@example.com']);

    return AuthorProfile::create([
        'user_id' => $user->id,
        'slug' => 's120-'.strtolower(Str::random(6)),
        'display_name' => 'S120 Test',
        'tier' => 'free',
        'bio' => 'Auteur passionné par la veille IA et le développement web.',
        'qualifications' => ['Expert IA', 'Développeur full-stack', 'Auteur'],
        'social_links' => ['twitter' => 'https://x.com/test', 'linkedin' => 'https://linkedin.com/in/test'],
    ]);
}

function makeS120Post(AuthorProfile $author, array $overrides = []): AuthorPost
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
        'views_count' => 0,
    ], $overrides));
}

it('Mini-site renders search header toggle button without Livewire', function () {
    $author = makeAuthorS120();

    $response = $this->get('/@'.$author->slug);
    $response->assertStatus(200);
    $response->assertSee('Rechercher dans les articles', false);
    $response->assertSee('lv-search-toggle', false);
    $response->assertSee('Ctrl', false);
});

it('Mini-site preserves Cache-Control public for guests after search toggle restored', function () {
    $author = makeAuthorS120();

    $response = $this->get('/@'.$author->slug);
    expect($response->headers->get('Cache-Control'))->toContain('public');
});

it('Mini-site search query filters AuthorPost by title', function () {
    $author = makeAuthorS120();
    $matchPost = makeS120Post($author, ['title' => 'Article sur IA generative et LLM']);
    makeS120Post($author, ['title' => 'Cuisine du Quebec']);

    $response = $this->get('/@'.$author->slug.'?q=generative');
    $response->assertStatus(200);
    $response->assertSee('Résultats pour', false);
    $response->assertSeeText('generative');
});

it('Mini-site search rejects query < 2 chars (no results section)', function () {
    $author = makeAuthorS120();
    makeS120Post($author, ['title' => 'Test article']);

    $response = $this->get('/@'.$author->slug.'?q=a');
    $response->assertStatus(200);
    $response->assertDontSee('Résultats pour', false);
});

it('Mini-site search empty results state', function () {
    $author = makeAuthorS120();
    makeS120Post($author, ['title' => 'Article unique']);

    $response = $this->get('/@'.$author->slug.'?q=nonexistent');
    $response->assertStatus(200);
    $response->assertSee('Résultats pour', false);
    $response->assertSee('Aucun résultat trouvé', false);
});

it('Mini-site renders popular posts ordered by views_count desc', function () {
    $author = makeAuthorS120();
    $low = makeS120Post($author, ['title' => 'Low views', 'views_count' => 5]);
    $high = makeS120Post($author, ['title' => 'High views', 'views_count' => 500]);
    $mid = makeS120Post($author, ['title' => 'Mid views', 'views_count' => 50]);

    $response = $this->get('/@'.$author->slug);
    $response->assertStatus(200);
    $response->assertSee('Articles populaires', false);
    $response->assertSee('lv-popular-posts', false);
    $response->assertSee('High views', false);

    $content = $response->getContent();
    expect(strpos($content, 'High views'))->toBeLessThan(strpos($content, 'Mid views'));
    expect(strpos($content, 'Mid views'))->toBeLessThan(strpos($content, 'Low views'));
});

it('Post page renders author bio card after related posts', function () {
    $author = makeAuthorS120();
    $post = makeS120Post($author);

    $response = $this->get('/@'.$author->slug.'/'.$post->slug);
    $response->assertStatus(200);
    $response->assertSee('lv-author-bio-card', false);
    $response->assertSee('À propos de l\'auteur', false);
    $response->assertSee('Voir tous ses articles', false);
});

it('Post page bio card renders qualifications chips', function () {
    $author = makeAuthorS120();
    $post = makeS120Post($author);

    $response = $this->get('/@'.$author->slug.'/'.$post->slug);
    $response->assertStatus(200);
    $response->assertSee('Expert IA', false);
    $response->assertSee('Développeur full-stack', false);
});

it('Post page renders ARIA breadcrumb navigation', function () {
    $author = makeAuthorS120();
    $post = makeS120Post($author);

    $response = $this->get('/@'.$author->slug.'/'.$post->slug);
    $response->assertStatus(200);
    $response->assertSee('aria-label="Fil d\'Ariane"', false);
    $response->assertSee('lv-post-breadcrumb', false);
    $response->assertSee('aria-current="page"', false);
});

it('Post page Schema.org BreadcrumbList @graph entry present', function () {
    $author = makeAuthorS120();
    $post = makeS120Post($author);

    $response = $this->get('/@'.$author->slug.'/'.$post->slug);
    $response->assertStatus(200);
    $response->assertSee('BreadcrumbList', false);
    $response->assertSee('itemListElement', false);
});

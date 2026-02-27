<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\Models\Article;
use Modules\Pages\Models\StaticPage;
use Modules\SEO\Services\JsonLdService;

uses(RefreshDatabase::class);

// --- Service unit tests ---

it('génère un schéma Organization valide', function () {
    $org = JsonLdService::organization();

    expect($org['@type'])->toBe('Organization');
    expect($org['name'])->toBe(config('app.name'));
    expect($org['url'])->toBe(config('app.url'));
});

it('génère un schéma WebSite avec SearchAction', function () {
    $site = JsonLdService::website();

    expect($site['@type'])->toBe('WebSite');
    expect($site['potentialAction']['@type'])->toBe('SearchAction');
    expect($site['potentialAction']['query-input'])->toContain('search_term_string');
});

it('génère un schéma Article avec les champs requis', function () {
    $article = (object) [
        'title' => 'Mon article test',
        'published_at' => now(),
        'updated_at' => now(),
        'cover_image' => null,
        'user' => (object) ['name' => 'Jean Dupont'],
        'meta_description' => 'Description de test.',
        'excerpt' => null,
    ];

    $schema = JsonLdService::article($article);

    expect($schema['@type'])->toBe('Article');
    expect($schema['headline'])->toBe('Mon article test');
    expect($schema['author']['name'])->toBe('Jean Dupont');
    expect($schema['description'])->toBe('Description de test.');
    expect($schema['publisher']['@type'])->toBe('Organization');
});

it('génère un schéma WebPage', function () {
    $page = (object) [
        'title' => 'Page test',
        'meta_description' => 'Description page.',
        'updated_at' => now(),
    ];

    $schema = JsonLdService::webPage($page);

    expect($schema['@type'])->toBe('WebPage');
    expect($schema['name'])->toBe('Page test');
});

it('génère un BreadcrumbList', function () {
    $breadcrumbs = JsonLdService::breadcrumbs([
        ['name' => 'Accueil', 'url' => 'http://localhost'],
        ['name' => 'Blog', 'url' => 'http://localhost/blog'],
        ['name' => 'Article'],
    ]);

    expect($breadcrumbs['@type'])->toBe('BreadcrumbList');
    expect($breadcrumbs['itemListElement'])->toHaveCount(3);
    expect($breadcrumbs['itemListElement'][0]['position'])->toBe(1);
    expect($breadcrumbs['itemListElement'][2])->not->toHaveKey('item');
});

it('render produit du JSON-LD valide', function () {
    $html = JsonLdService::render(JsonLdService::organization());

    expect($html)->toContain('<script type="application/ld+json">');
    expect($html)->toContain('"@context": "https://schema.org"');
    expect($html)->toContain('"@type": "Organization"');
});

it('render avec multiple schémas produit un tableau', function () {
    $html = JsonLdService::render(
        JsonLdService::organization(),
        JsonLdService::website()
    );

    $json = json_decode(
        str_replace(['<script type="application/ld+json">', '</script>'], '', $html),
        true
    );

    expect($json)->toBeArray();
    expect($json[0]['@type'])->toBe('Organization');
    expect($json[1]['@type'])->toBe('WebSite');
});

// --- Integration tests ---

it('la page d\'accueil contient Organization JSON-LD', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('application/ld+json', false)
        ->assertSee('"@type": "Organization"', false);
});

it('une page statique contient WebPage JSON-LD', function () {
    StaticPage::create([
        'title' => 'Page JSON-LD test',
        'slug' => 'jsonld-test',
        'content' => '<p>Contenu test.</p>',
        'status' => 'published',
        'meta_description' => 'Description test SEO.',
    ]);

    $this->get('/pages/jsonld-test')
        ->assertOk()
        ->assertSee('"@type": "WebPage"', false)
        ->assertSee('"@type": "BreadcrumbList"', false);
});

it('un article de blog contient Article JSON-LD', function () {
    $user = \App\Models\User::factory()->create();

    $article = Article::create([
        'title' => 'Article JSON-LD test',
        'slug' => 'article-jsonld-test',
        'content' => '<p>Contenu article.</p>',
        'excerpt' => 'Extrait article.',
        'status' => 'published',
        'published_at' => now(),
        'user_id' => $user->id,
    ]);

    $this->get('/blog/' . $article->slug)
        ->assertOk()
        ->assertSee('"@type": "Article"', false)
        ->assertSee('"@type": "BreadcrumbList"', false)
        ->assertSee('Article JSON-LD test', false);
});

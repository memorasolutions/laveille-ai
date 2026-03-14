<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\AI\Models\KnowledgeUrl;
use Modules\AI\Services\WebScraperService;
use Spatie\Permission\Models\Role;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('peut créer une URL KB', function () {
    $url = KnowledgeUrl::factory()->create([
        'url' => 'https://example.com/test',
        'label' => 'Test URL',
    ]);

    $this->assertDatabaseHas('ai_knowledge_urls', [
        'url' => 'https://example.com/test',
        'label' => 'Test URL',
    ]);
});

it('le scope active filtre les URLs', function () {
    KnowledgeUrl::factory()->create(['is_active' => true]);
    KnowledgeUrl::factory()->create(['is_active' => false]);

    expect(KnowledgeUrl::active()->count())->toBe(1);
});

it('le scope needsScraping filtre correctement', function () {
    KnowledgeUrl::factory()->create([
        'is_active' => true,
        'robots_allowed' => true,
        'last_scraped_at' => null,
    ]);

    KnowledgeUrl::factory()->create([
        'is_active' => false,
        'robots_allowed' => true,
        'last_scraped_at' => null,
    ]);

    KnowledgeUrl::factory()->create([
        'is_active' => true,
        'robots_allowed' => true,
        'last_scraped_at' => now(),
        'scrape_frequency' => 'weekly',
    ]);

    KnowledgeUrl::factory()->create([
        'is_active' => true,
        'robots_allowed' => false,
        'last_scraped_at' => null,
    ]);

    expect(KnowledgeUrl::needsScraping()->count())->toBe(1);
});

it('la vérification robots.txt autorise quand pas de fichier', function () {
    Http::fake([
        '*/robots.txt' => Http::response('', 404),
    ]);

    $scraperService = app(WebScraperService::class);

    expect($scraperService->checkRobotsTxt('https://example.com/page'))->toBeTrue();
});

it('la vérification robots.txt bloque quand interdit', function () {
    Http::fake([
        '*/robots.txt' => Http::response("User-agent: *\nDisallow: /private/", 200),
    ]);

    $scraperService = app(WebScraperService::class);

    expect($scraperService->checkRobotsTxt('https://example.com/private/page'))->toBeFalse();
});

it('l\'extraction de contenu nettoie le HTML', function () {
    $html = <<<'HTML'
    <html>
        <head><title>Test</title></head>
        <body>
            <script>alert('test');</script>
            <nav>Navigation</nav>
            <main>
                <h1>Contenu principal</h1>
                <p>Ceci est un paragraphe utile.</p>
            </main>
            <footer>Footer content</footer>
        </body>
    </html>
    HTML;

    $scraperService = app(WebScraperService::class);
    $content = $scraperService->extractContent($html);

    expect($content)
        ->toContain('Contenu principal')
        ->toContain('Ceci est un paragraphe utile')
        ->not->toContain('alert')
        ->not->toContain('Navigation')
        ->not->toContain('Footer content');
});

it('l\'extraction de titre prioritise h1', function () {
    $html = <<<'HTML'
    <html>
        <head><title>Titre dans head</title></head>
        <body>
            <h1>Titre H1 principal</h1>
            <p>Contenu</p>
        </body>
    </html>
    HTML;

    $scraperService = app(WebScraperService::class);

    expect($scraperService->extractTitle($html))->toBe('Titre H1 principal');
});

it('le CRUD admin URLs nécessite auth', function () {
    $this->get(route('admin.ai.urls.index'))
        ->assertRedirect();
});

it('l\'admin peut lister les URLs KB', function () {
    Role::findOrCreate('super_admin');
    $admin = \App\Models\User::factory()->create();
    $admin->assignRole('super_admin');

    KnowledgeUrl::factory()->create(['label' => 'URL de test admin']);

    $this->actingAs($admin)
        ->get(route('admin.ai.urls.index'))
        ->assertOk()
        ->assertSee('URL de test admin');
});

it('l\'admin peut créer une URL KB', function () {
    Role::findOrCreate('super_admin');
    $admin = \App\Models\User::factory()->create();
    $admin->assignRole('super_admin');

    $this->actingAs($admin)
        ->post(route('admin.ai.urls.store'), [
            'url' => 'https://example.com/nouvelle-url',
            'label' => 'Nouvelle URL admin',
            'max_pages' => 50,
            'scrape_frequency' => 'weekly',
            'is_active' => true,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('ai_knowledge_urls', [
        'url' => 'https://example.com/nouvelle-url',
        'label' => 'Nouvelle URL admin',
    ]);
});

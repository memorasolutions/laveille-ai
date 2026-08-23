<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Tests `php artisan directory:check-links --fix` — corrige la confusion entre « la fiche a
 * vraiment disparu » (404/410, seule famille qui justifie la quarantaine) et « le site est
 * vivant mais refuse notre robot » (401/403/405/429) ou « ennui serveur probablement
 * transitoire » (5xx, timeouts). Avant ce correctif, tout code >= 400 mettait la fiche en
 * quarantaine (status=draft) sans distinction, retirant de l'annuaire public des outils bien
 * vivants (ProductHunt, tout ce qui est derrière Cloudflare) simplement parce qu'ils bloquent
 * les robots identifiables (User-Agent LaVeilleBot/1.0).
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\Directory\Models\Tool;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

function makeCheckLinksTestTool(string $slug, string $url): Tool
{
    $tool = new Tool();
    $tool->url = $url;
    $tool->pricing = 'free';
    $tool->status = 'published';
    $tool->is_featured = false;
    $tool->setTranslation('name', 'fr_CA', ucfirst($slug));
    $tool->setTranslation('slug', 'fr_CA', $slug);
    $tool->setTranslation('description', 'fr_CA', 'Outil de test.');
    $tool->setTranslation('short_description', 'fr_CA', 'Test.');
    $tool->save();

    return $tool;
}

test('un 404 avec --fix met la fiche en quarantaine (disparu confirme)', function () {
    $tool = makeCheckLinksTestTool('outil-404', 'https://outil-404.exemple-test.invalid/page');

    Http::fake([
        'outil-404.exemple-test.invalid/*' => Http::response('', 404),
    ]);

    $this->artisan('directory:check-links', ['--fix' => true])->assertExitCode(0);

    expect($tool->fresh()->status)->toBe('draft');
});

test('un 410 avec --fix met la fiche en quarantaine (disparu confirme)', function () {
    $tool = makeCheckLinksTestTool('outil-410', 'https://outil-410.exemple-test.invalid/page');

    Http::fake([
        'outil-410.exemple-test.invalid/*' => Http::response('', 410),
    ]);

    $this->artisan('directory:check-links', ['--fix' => true])->assertExitCode(0);

    expect($tool->fresh()->status)->toBe('draft');
});

test('un 403 avec --fix ne met PAS la fiche en quarantaine (site vivant qui refuse le robot)', function () {
    $tool = makeCheckLinksTestTool('outil-403', 'https://outil-403.exemple-test.invalid/page');

    Http::fake([
        'outil-403.exemple-test.invalid/*' => Http::response('', 403),
    ]);

    $this->artisan('directory:check-links', ['--fix' => true])->assertExitCode(0);

    expect($tool->fresh()->status)->toBe('published');
});

test('un 429 avec --fix ne met PAS la fiche en quarantaine (limitation de debit, transitoire)', function () {
    $tool = makeCheckLinksTestTool('outil-429', 'https://outil-429.exemple-test.invalid/page');

    Http::fake([
        'outil-429.exemple-test.invalid/*' => Http::response('', 429),
    ]);

    $this->artisan('directory:check-links', ['--fix' => true])->assertExitCode(0);

    expect($tool->fresh()->status)->toBe('published');
});

test('un 500 avec --fix ne met PAS la fiche en quarantaine (ennui serveur probablement transitoire)', function () {
    $tool = makeCheckLinksTestTool('outil-500', 'https://outil-500.exemple-test.invalid/page');

    Http::fake([
        'outil-500.exemple-test.invalid/*' => Http::response('', 500),
    ]);

    $this->artisan('directory:check-links', ['--fix' => true])->assertExitCode(0);

    expect($tool->fresh()->status)->toBe('published');
});

test('sans --fix, meme un 404 ne modifie rien', function () {
    $tool = makeCheckLinksTestTool('outil-404-sans-fix', 'https://outil-404-sans-fix.exemple-test.invalid/page');

    Http::fake([
        'outil-404-sans-fix.exemple-test.invalid/*' => Http::response('', 404),
    ]);

    $this->artisan('directory:check-links')->assertExitCode(0);

    expect($tool->fresh()->status)->toBe('published');
});

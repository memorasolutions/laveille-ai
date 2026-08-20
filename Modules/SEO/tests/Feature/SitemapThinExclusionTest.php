<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Audit AdSense 2026-08-20 (« contenu de faible valeur ») : cohérence noindex/sitemap - une URL
 * noindex conditionnelle ne doit pas rester au sitemap.xml. Couvre les 2 endroits où le générateur
 * (Modules/SEO/app/Http/Controllers/SitemapController.php) a été ajusté : les fiches annuaire
 * minces (même critère que PublicDirectoryController::show(), constante partagée
 * THIN_SHORT_DESCRIPTION_MAX_LENGTH) et /roadmap tant qu'aucune proposition publique n'existe. PAS
 * de délégation ici, ce test n'est PAS exécuté par ce sous-agent (contrainte projet - le
 * superviseur lance la suite une seule fois, en série).
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Directory\Models\Tool;
use Modules\Roadmap\Models\Board;
use Modules\Roadmap\Models\Idea;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('app.noindex', false);
});

function makeSitemapTestTool(string $slug, array $overrides = []): Tool
{
    config(['app.locale' => 'fr_CA']);

    $tool = new Tool();
    $tool->setTranslation('name', 'fr_CA', 'Outil Sitemap Test '.$slug);
    $tool->setTranslation('slug', 'fr_CA', $slug);
    $tool->setTranslation('description', 'fr_CA', $overrides['description'] ?? 'Description de test.');
    $tool->setTranslation('short_description', 'fr_CA', $overrides['short_description'] ?? '');
    $tool->url = 'https://exemple-'.$slug.'.test';
    $tool->pricing = 'free';
    $tool->status = 'published';
    $tool->save();
    $tool->refresh();

    return $tool;
}

test('le sitemap exclut une fiche annuaire mince', function () {
    $thinTool = makeSitemapTestTool('outil-sitemap-mince', ['short_description' => 'Trop court.']);
    $richTool = makeSitemapTestTool('outil-sitemap-riche', ['short_description' => 'Un résumé suffisamment long et informatif pour ne pas être mince.']);

    $response = $this->get(route('sitemap'));

    $response->assertOk();
    $response->assertDontSee($thinTool->getPublicUrl(), false);
    $response->assertSee($richTool->getPublicUrl(), false);
});

test('le sitemap exclut /roadmap tant qu\'aucune proposition publique n\'existe', function () {
    Board::factory()->create(['is_public' => true]);

    $response = $this->get(route('sitemap'));

    $response->assertOk();
    $response->assertDontSee(route('roadmap.boards.index'), false);
});

test('le sitemap inclut /roadmap dès qu\'une proposition publique existe', function () {
    $board = Board::factory()->create(['is_public' => true]);
    Idea::factory()->create(['board_id' => $board->id]);

    $response = $this->get(route('sitemap'));

    $response->assertOk();
    $response->assertSee(route('roadmap.boards.index'), false);
});

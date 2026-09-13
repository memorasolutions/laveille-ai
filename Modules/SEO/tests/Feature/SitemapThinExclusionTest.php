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
use Modules\Dictionary\Models\Term;
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

// ── Ticket #2523 (docs/specs/2026-09-11-mesure-visibilite-et-fraicheur.md, MESURE B) : le lastmod
//      du glossaire suivait `updated_at`, réécrit par une simple consultation (Modules\Core\
//      Services\ViewCounterService::record(), qui incrémente par le query builder brut) - mesuré
//      jusqu'à quatre mois d'écart sur laveille.ai/glossaire/sora. Couvre le correctif du bloc
//      Dictionary\Term de SitemapController::index() : editorialModifiedAt() remplace updated_at.
//      Même convention de construction que Modules/Dictionary/tests/Feature/
//      TermSchemaDateModifiedTest.php (pas de TermFactory dans ce module).

function makeSitemapTestTerm(string $suffixe): Term
{
    config(['app.locale' => 'fr_CA']);
    $locale = app()->getLocale();
    $slug = 'terme-sitemap-'.$suffixe.'-'.uniqid();

    return Term::create([
        'name' => [$locale => 'Terme sitemap '.$suffixe, 'fr' => 'Terme sitemap '.$suffixe],
        'slug' => [$locale => $slug, 'fr' => $slug],
        'definition' => [$locale => 'Définition de test.', 'fr' => 'Définition de test.'],
        'is_published' => true,
    ]);
}

test('le lastmod du glossaire suit content_updated_at, jamais un updated_at poussé par une consultation', function () {
    $term = makeSitemapTestTerm('derive');

    // Simule la dérive mesurée en production : une consultation (ViewCounterService::record(),
    // qui passe par increment() sur le query builder brut, sans déclencher l'évènement 'saving')
    // pousse updated_at loin devant SANS jamais faire avancer content_updated_at.
    $derive = now()->addMonths(4);
    Term::query()->whereKey($term->getKey())->update(['updated_at' => $derive]);
    $term->refresh();

    expect($term->updated_at->toIso8601String())->not->toBe($term->content_updated_at->toIso8601String());

    $lastmodAttendu = $term->content_updated_at->format(DateTime::ATOM);
    $dateDerivee = $derive->format(DateTime::ATOM);

    $response = $this->get(route('sitemap'));

    $response->assertOk();
    $response->assertSee('<lastmod>'.$lastmodAttendu.'</lastmod>', false);
    $response->assertDontSee($dateDerivee, false);
});

test('le lastmod du glossaire ne disparaît pas pour un terme publié sans révision éditoriale connue', function () {
    $term = makeSitemapTestTerm('sans-revision');

    // Fraîchement créé : content_updated_at = created_at (même convention que
    // Modules/Dictionary/tests/Feature/TermSchemaDateModifiedTest.php), aucune révision connue -
    // le lastmod doit malgré tout être présent (repli sur la date de création), jamais disparaître.
    expect($term->hasKnownEditorialRevision())->toBeFalse();

    $lastmodAttendu = $term->content_updated_at->format(DateTime::ATOM);

    $response = $this->get(route('sitemap'));

    // Mord si le select() du contrôleur oublie created_at/content_updated_at : editorialModifiedAt()
    // renverrait alors null, et Url::setLastModificationDate() (paramètre DateTimeInterface non
    // nullable) ferait échouer la génération du sitemap au lieu de se replier proprement.
    $response->assertOk();
    $response->assertSee('<lastmod>'.$lastmodAttendu.'</lastmod>', false);
});

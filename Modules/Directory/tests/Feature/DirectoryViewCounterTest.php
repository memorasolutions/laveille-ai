<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Couvre le correctif 2026-08-28 (incident 2026-08-13, recoupement GA4 propriété 500300528,
 * janvier 2026 à aujourd'hui - mesure : jusqu'à 652x le trafic humain réel selon la fiche, ex.
 * FLUX 1 957 affichés contre 3 vues réelles) : PublicDirectoryController::show() délègue
 * désormais à Modules\Core\Services\ViewCounterService::record() au lieu d'un
 * $tool->increment('clicks_count') brut, sans tri robots ni déduplication. L'annuaire était le
 * SEUL module resté sur ce mécanisme - Tools, Authors, News et Dictionary étaient déjà passés
 * par le service (voir Modules/Core/tests/Feature/ViewCounterServiceTest.php pour le test
 * générique du service lui-même). Ce fichier-ci prouve l'INTÉGRATION réelle côté annuaire : le
 * contrôleur, ET la colonne clicks_count - nom propre à Directory, PAS views_count comme les 3
 * autres modules - avec son jumeau clicks_count_verified (migration
 * 2026_08_28_100000_add_clicks_count_verified_to_directory_tools.php).
 *
 * Polyfill FIELD() : même contournement centralisé (DRY) que Modules/Directory/tests/Feature/
 * AffiliateLinkTest.php et ThinContentNoindexTest.php (sqlite :memory: de la suite de tests n'a
 * pas la fonction MySQL FIELD() utilisée par show() pour trier les ressources - limitation
 * pré-existante, sans rapport avec ce correctif).
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Modules\Core\Services\ViewCounterService;
use Modules\Directory\Models\Tool;
use Tests\Concerns\RegistersMysqlSqliteCompatFunctions;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);
uses(RegistersMysqlSqliteCompatFunctions::class);

beforeEach(fn () => $this->registerMysqlSqliteCompatFunctions());

function makeViewCounterTestTool(string $slug): Tool
{
    config(['app.locale' => 'fr_CA']);

    $tool = new Tool();
    $tool->setTranslation('name', 'fr_CA', 'Outil compteur '.$slug);
    $tool->setTranslation('slug', 'fr_CA', $slug);
    $tool->setTranslation('description', 'fr_CA', 'Description de test.');
    $tool->setTranslation('short_description', 'fr_CA', 'Résumé de test.');
    $tool->url = 'https://exemple-'.$slug.'.test';
    $tool->pricing = 'free';
    $tool->status = 'published';
    $tool->save();
    $tool->refresh();

    return $tool;
}

// ── 1. Le service, appliqué au modèle et à la colonne propres à l'annuaire ─────────────────

test('un appel direct au service incrémente clicks_count ET son jumeau clicks_count_verified', function () {
    $tool = makeViewCounterTestTool('outil-vc-service-normal');

    $request = Request::create('/annuaire/'.$tool->slug, 'GET', [], [], [], [
        'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) Chrome/128.0',
        'REMOTE_ADDR' => '203.0.113.20',
    ]);
    app()->instance('request', $request);

    ViewCounterService::record($tool, 'clicks_count');

    $tool->refresh();
    expect($tool->clicks_count)->toBe(1)
        ->and($tool->clicks_count_verified)->toBe(1);
});

test('un appel direct au service avec un user-agent de robot déclaré n\'incrémente ni l\'un ni l\'autre', function () {
    $tool = makeViewCounterTestTool('outil-vc-service-robot');

    $request = Request::create('/annuaire/'.$tool->slug, 'GET', [], [], [], [
        'HTTP_USER_AGENT' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        'REMOTE_ADDR' => '203.0.113.21',
    ]);
    app()->instance('request', $request);

    ViewCounterService::record($tool, 'clicks_count');

    $tool->refresh();
    expect($tool->clicks_count)->toBe(0)
        ->and($tool->clicks_count_verified)->toBe(0);
});

// ── 2. Le contrôleur réel (route directory.show) - preuve que show() délègue bien au service ──

test('une visite normale de la fiche /annuaire/{slug} incrémente clicks_count ET clicks_count_verified', function () {
    $tool = makeViewCounterTestTool('outil-vc-http-normal');

    $response = $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/128.0'])
        ->get(route('directory.show', $tool->slug));

    $response->assertOk();

    $tool->refresh();
    expect($tool->clicks_count)->toBe(1)
        ->and($tool->clicks_count_verified)->toBe(1);
});

test('une visite de /annuaire/{slug} par un robot déclaré n\'incrémente ni clicks_count ni clicks_count_verified', function () {
    $tool = makeViewCounterTestTool('outil-vc-http-robot');

    $response = $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'])
        ->get(route('directory.show', $tool->slug));

    $response->assertOk();

    $tool->refresh();
    expect($tool->clicks_count)->toBe(0)
        ->and($tool->clicks_count_verified)->toBe(0);
});

// ── 3. Partie 2 (docs/specs/2026-09-11-mesure-visibilite-et-fraicheur.md) : la date de
//      modification ÉDITORIALE publiée (texte visible « Mis à jour le… », le cas le plus visible
//      mesuré) ne doit pas bouger quand la fiche n'est que CONSULTÉE - ni via increment() sur
//      clicks_count (Partie 1), ni via un contenu qui n'a en réalité pas changé (Partie 2).

test('consulter la fiche /annuaire/{slug} ne fait pas avancer la date « Mis à jour le » publiée', function () {
    $tool = makeViewCounterTestTool('outil-vc-freshness');

    // La fiche doit porter une révision RÉELLEMENT connue, sinon la page n'affiche aucune date
    // du tout (et c'est voulu : présenter la date de création comme une date de révision serait
    // le même mensonge sous un autre nom). C'est donc le cas où la date est publiée qu'on éprouve
    // ici ; l'autre cas est couvert par le test suivant.
    $tool->content_updated_at = $tool->created_at->copy()->addDays(10);
    $tool->saveQuietly();
    $tool->refresh();
    expect($tool->hasKnownEditorialRevision())->toBeTrue();

    $editorialAvant = $tool->editorialModifiedAt()->toIso8601String();

    // Horloge avancée pour que le test morde à coup sûr si la moindre écriture (increment()
    // sur clicks_count OU un save() du modèle) faisait avancer la date publiée.
    Carbon\Carbon::setTestNow(now()->addDays(3));
    $response = $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/128.0'])
        ->get(route('directory.show', $tool->slug));
    Carbon\Carbon::setTestNow();

    $response->assertOk();
    $tool->refresh();

    // Partie 1 : la consultation a bien compté (preuve que le compteur fonctionne toujours).
    expect($tool->clicks_count)->toBe(1)
        // Partie 2 : mais la date éditoriale publiée, elle, n'a pas bougé.
        ->and($tool->editorialModifiedAt()->toIso8601String())->toBe($editorialAvant);

    // Preuve visuelle : le texte réellement affiché sur la page porte la MÊME date qu'avant la
    // consultation (format_date() en 'short' => jour/mois/année, insensible aux secondes).
    $response->assertSee(format_date($tool->editorialModifiedAt()));
});

test('une fiche sans révision connue n\'affiche AUCUNE date - jamais la date de création déguisée', function () {
    // Le repli du trait (content_updated_at = created_at quand aucune trace n'existe) ne doit
    // JAMAIS être publié : 464 des 544 termes mesurés le 2026-09-11 n'ont aucune trace de
    // révision. Ce test mord si quelqu'un réintroduit un affichage inconditionnel de la date.
    $tool = makeViewCounterTestTool('outil-vc-sans-revision');
    $tool->refresh();

    expect($tool->hasKnownEditorialRevision())->toBeFalse();

    $response = $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/128.0'])
        ->get(route('directory.show', $tool->slug));

    $response->assertOk()
        ->assertDontSee('Révisé le')
        ->assertDontSee(format_date($tool->created_at));
});

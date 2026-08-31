<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Preuve HTTP de bout en bout de /glossaire (dictionary.index) - mandat #1939 : cette route était
 * IMPOSSIBLE à atteindre dans la suite de tests avant ce fichier, faute d'un polyfill sqlite
 * (« no such function: JSON_UNQUOTE », mesuré le 2026-08-27, reconfirmé identique le 2026-08-31).
 * Modules/Dictionary/tests/Feature/PublicListCachePurgeOnPublishTest.php documentait la
 * limitation et contournait via l'accueil ; ce fichier-ci teste la VRAIE route directement,
 * middleware + contrôleur (PublicDictionaryController::index()) + vue.
 *
 * Le polyfill JSON_UNQUOTE (voir tests/Concerns/RegistersMysqlSqliteCompatFunctions.php) est un
 * simple passe-plat : le json_extract() natif de sqlite renvoie déjà un scalaire non quoté, donc
 * il reproduit exactement JSON_UNQUOTE(JSON_EXTRACT(...)) sous MySQL pour ce projet - vérifié
 * empiriquement avant écriture (SELECT json_extract('{"a":"x"}','$.a') = x, jamais "x").
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Dictionary\Models\Term;
use Tests\Concerns\RegistersMysqlSqliteCompatFunctions;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);
uses(RegistersMysqlSqliteCompatFunctions::class);

beforeEach(fn () => $this->registerMysqlSqliteCompatFunctions());

/** Construction directe (pas de TermFactory dans ce module - même convention que les autres tests Dictionary/Directory). */
function makeGlossaryIndexTestTerm(string $suffixe, array $overrides = []): Term
{
    config(['app.locale' => 'fr_CA']);
    $slug = 'terme-index-'.$suffixe.'-'.uniqid();

    return Term::create(array_merge([
        'name' => ['fr_CA' => 'Terme '.$suffixe, 'fr' => 'Terme '.$suffixe],
        'slug' => ['fr_CA' => $slug, 'fr' => $slug],
        'definition' => ['fr_CA' => 'Définition de test pour '.$suffixe.'.', 'fr' => 'Définition de test.'],
        'type' => 'ai_term',
        'is_published' => true,
    ], $overrides));
}

// ── Preuve de bout en bout : la route répond et respecte la visibilité published() ─────────

it('rend /glossaire avec 200 et affiche uniquement les termes publiés', function () {
    $marqueurPublie = 'TermePublieMarqueur'.uniqid();
    $marqueurBrouillon = 'TermeBrouillonMarqueur'.uniqid();

    makeGlossaryIndexTestTerm('publie', [
        'name' => ['fr_CA' => $marqueurPublie, 'fr' => $marqueurPublie],
        'is_published' => true,
    ]);
    makeGlossaryIndexTestTerm('brouillon', [
        'name' => ['fr_CA' => $marqueurBrouillon, 'fr' => $marqueurBrouillon],
        'is_published' => false,
    ]);

    $response = $this->get(route('dictionary.index'));

    $response->assertOk();
    $response->assertSee($marqueurPublie, false);
    $response->assertDontSee($marqueurBrouillon, false);
});

// ── Régression S89 #68 : tri alphabétique insensible à la casse ────────────────────────────
//
// Avant ce correctif (S89 #68, PublicDictionaryController::index()), un tri binaire plaçait
// "AGI" (majuscules) avant "affinage" (minuscules) - le LOWER(JSON_UNQUOTE(JSON_EXTRACT(...)))
// corrige ça. Preuve directe sur la vraie route, pas seulement sur la requête Eloquent isolée.

it('trie les termes alphabetiquement sans egard a la casse (regression S89 #68 : AGI apres affinage)', function () {
    $nomMajuscule = 'AGI'.uniqid();
    $nomMinuscule = 'affinage'.uniqid();

    // Créés dans l'ordre INVERSE du tri attendu, pour ne jamais laisser l'ordre d'insertion
    // masquer une régression de tri.
    makeGlossaryIndexTestTerm('agi', ['name' => ['fr_CA' => $nomMajuscule, 'fr' => $nomMajuscule]]);
    makeGlossaryIndexTestTerm('affinage', ['name' => ['fr_CA' => $nomMinuscule, 'fr' => $nomMinuscule]]);

    $response = $this->get(route('dictionary.index'));
    $response->assertOk();

    $html = $response->getContent();
    $positionMinuscule = strpos($html, $nomMinuscule);
    $positionMajuscule = strpos($html, $nomMajuscule);

    expect($positionMinuscule)->not->toBeFalse()
        ->and($positionMajuscule)->not->toBeFalse()
        ->and($positionMinuscule)->toBeLessThan($positionMajuscule);
});

// ── Filtre type ──────────────────────────────────────────────────────────────────────────

it('le filtre ?type= ne retourne que les termes du type demande', function () {
    $marqueurAcronyme = 'MarqueurAcronyme'.uniqid();
    $marqueurExplication = 'MarqueurExplication'.uniqid();

    makeGlossaryIndexTestTerm('acronyme', [
        'name' => ['fr_CA' => $marqueurAcronyme, 'fr' => $marqueurAcronyme],
        'type' => 'acronym',
    ]);
    makeGlossaryIndexTestTerm('explication', [
        'name' => ['fr_CA' => $marqueurExplication, 'fr' => $marqueurExplication],
        'type' => 'explainer',
    ]);

    $response = $this->get(route('dictionary.index', ['type' => 'acronym']));

    $response->assertOk();
    $response->assertSee($marqueurAcronyme, false);
    $response->assertDontSee($marqueurExplication, false);
});

// ── Filtre letter ────────────────────────────────────────────────────────────────────────

it('le filtre ?letter= ne retourne que les termes commencant par cette lettre', function () {
    $marqueurZ = 'Zebre'.uniqid();
    $marqueurA = 'Affinage'.uniqid();

    makeGlossaryIndexTestTerm('lettre-z', ['name' => ['fr_CA' => $marqueurZ, 'fr' => $marqueurZ]]);
    makeGlossaryIndexTestTerm('lettre-a', ['name' => ['fr_CA' => $marqueurA, 'fr' => $marqueurA]]);

    $response = $this->get(route('dictionary.index', ['letter' => 'z']));

    $response->assertOk();
    $response->assertSee($marqueurZ, false);
    $response->assertDontSee($marqueurA, false);
});

// ── Recherche q ──────────────────────────────────────────────────────────────────────────

it('le filtre ?q= cherche dans le nom et dans la definition', function () {
    $marqueurTrouve = 'ChercheMoi'.uniqid();
    $texteDefinitionUnique = 'phraseraredefinitionuniquenoise'.uniqid();
    $marqueurAbsent = 'IntrouvableAilleurs'.uniqid();

    makeGlossaryIndexTestTerm('recherche-def', [
        'name' => ['fr_CA' => $marqueurTrouve, 'fr' => $marqueurTrouve],
        'definition' => ['fr_CA' => $texteDefinitionUnique, 'fr' => $texteDefinitionUnique],
    ]);
    makeGlossaryIndexTestTerm('recherche-absent', ['name' => ['fr_CA' => $marqueurAbsent, 'fr' => $marqueurAbsent]]);

    $response = $this->get(route('dictionary.index', ['q' => $texteDefinitionUnique]));

    $response->assertOk();
    $response->assertSee($marqueurTrouve, false);
    $response->assertDontSee($marqueurAbsent, false);
});

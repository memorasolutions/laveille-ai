<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * Tests de la désambiguïsation des acronymes homonymes (sigle = N sens).
 * ACTION: tests ciblés désambiguïsation acronymes
 * MCP: SELF | RAISON: < 5 lignes de logique, structure de test uniquement
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Core\Services\GlossaryLinkifier;

uses(Tests\TestCase::class, RefreshDatabase::class);

// ── Helpers internes ────────────────────────────────────────────────────────

/**
 * Insère une fiche acronyme en DB directement (JSON translatable).
 *
 * @return int L'id inséré
 */
function insertAcronym(string $acro, string $fullName, string $slug, bool $published = true): int
{
    $frCa = fn (string $v) => json_encode(['fr_CA' => $v, 'fr' => $v]);

    return DB::table('acronyms')->insertGetId([
        'acronym' => $frCa($acro),
        'full_name' => $frCa($fullName),
        'slug' => $frCa($slug),
        'description' => $frCa('Description de test pour ' . $acro . '.'),
        'domain' => 'education',
        'match_strategy' => 'case_sensitive',
        'aliases' => json_encode([]),
        'is_published' => $published,
        'sort_order' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

// ── Test 1 : sigle unique → fiche directe ────────────────────────────────────

test('un sigle avec 1 seule fiche affiche directement cette fiche', function () {
    insertAcronym('UNI', "Université Nationale d'Informatique", 'uni-test');

    $response = $this->get('/acronymes-education/uni-test');

    $response->assertStatus(200);
    $response->assertSee('UNI');
    $response->assertSee("Université Nationale d'Informatique");
});

// ── Test 2 : sigle ambigu → page de désambiguïsation ─────────────────────────

test('un sigle avec N fiches rend la page de désambiguïsation avec les N sens', function () {
    insertAcronym('ATE', 'Alternance Travail-Études', 'ate-disamb-1');
    insertAcronym('ATE', 'Autres Tâches Éducatives', 'ate-disamb-2');

    $response = $this->get('/acronymes-education/disambiguate/ate');

    $response->assertStatus(200);
    $response->assertSee('ATE');
    $response->assertSee('Alternance Travail-Études');
    $response->assertSee('Autres Tâches Éducatives');
});

// ── Test 3 : slug exact → fiche directe même si sigle partagé ────────────────

test('le slug exact renvoie la bonne fiche même si le sigle est partagé', function () {
    insertAcronym('ATE', 'Alternance Travail-Études', 'ate-exact-1');
    insertAcronym('ATE', 'Autres Tâches Éducatives', 'ate-exact-2');

    $response = $this->get('/acronymes-education/ate-exact-1');

    // La fiche ate-exact-1 doit s'afficher (statut 200, pas de redirection)
    // et le titre h1 doit contenir l'acronyme ATE (pas la page de désambiguïsation)
    $response->assertStatus(200);
    $response->assertSee('Alternance Travail-Études');
    // Vérifier qu'on N'est PAS sur la page de désambiguïsation
    $response->assertDontSee('Plusieurs significations');
    $response->assertDontSee('Désambiguïsation');
});

// ── Test 4 : GlossaryLinkifier::resolveAmbiguousAcronymUrl ───────────────────

test('GlossaryLinkifier::resolveAmbiguousAcronymUrl retourne désambiguïsation pour N candidats', function () {
    $url = GlossaryLinkifier::resolveAmbiguousAcronymUrl('ATE', [
        ['url' => '/acronymes-education/ate', 'name' => 'ATE'],
        ['url' => '/acronymes-education/ate-2', 'name' => 'ATE'],
    ]);

    expect($url)->toBe('/acronymes-education/disambiguate/ate');
});

test('GlossaryLinkifier::resolveAmbiguousAcronymUrl retourne URL directe pour 1 candidat', function () {
    $url = GlossaryLinkifier::resolveAmbiguousAcronymUrl('OBVIA', [
        ['url' => '/acronymes-education/obvia', 'name' => 'OBVIA'],
    ]);

    expect($url)->toBe('/acronymes-education/obvia');
});

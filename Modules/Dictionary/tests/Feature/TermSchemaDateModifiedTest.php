<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Tests Pest - le `dateModified` du JSON-LD (Modules\Dictionary\Services\TermSchemaService)
 * du glossaire. Ticket #2451 (plan glossaire, docs/specs/2026-09-11-glossaire-plan-complet.md
 * section 5) : la page ne mentait plus au VISITEUR (show.blade.php utilise déjà
 * hasKnownEditorialRevision()/editorialModifiedAt(), voir ViewCounterDictionaryTest.php dans
 * ce même dossier), mais mentait toujours aux MOTEURS - le JSON-LD annonçait `updated_at`
 * (réécrit par une simple consultation, cf. Modules\Core\Services\ViewCounterService::record())
 * comme une date de révision éditoriale, sur 544 fiches. Ce fichier couvre le correctif :
 * `dateModified` suit désormais exactement la même règle que l'affichage visiteur.
 */

use Modules\Dictionary\Models\Term;
use Modules\Dictionary\Services\TermSchemaService;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

// ── Helper (même convention que ViewCounterDictionaryTest.php : construction directe, pas de
//      TermFactory dans ce module) ──────────────────────────────────────────────────────────

function makeSchemaDictionaryTerm(string $suffixe): Term
{
    config(['app.locale' => 'fr_CA']);
    $locale = app()->getLocale();
    $slug = 'terme-schema-'.$suffixe.'-'.uniqid();

    return Term::create([
        'name' => [$locale => 'Terme schema '.$suffixe, 'fr' => 'Terme schema '.$suffixe],
        'slug' => [$locale => $slug, 'fr' => $slug],
        'definition' => [$locale => 'Définition de test.', 'fr' => 'Définition de test.'],
        'is_published' => true,
    ]);
}

/** Extrait le tableau @graph décodé du JSON-LD produit par buildGraph(). */
function decodeSchemaGraph(Term $term): array
{
    $html = TermSchemaService::buildGraph($term);
    // buildGraph() enveloppe le JSON dans <script type="application/ld+json">…</script>.
    $json = preg_replace('#^<script[^>]*>|</script>$#', '', $html);
    $payload = json_decode((string) $json, true, flags: JSON_THROW_ON_ERROR);

    return $payload['@graph'];
}

function findByType(array $graph, string $type): ?array
{
    foreach ($graph as $node) {
        if (($node['@type'] ?? null) === $type) {
            return $node;
        }
    }

    return null;
}

// ── Cas 1 : révision éditoriale connue - dateModified présente, égale à content_updated_at ──

it('un terme avec une révision éditoriale connue produit un dateModified égal à content_updated_at', function () {
    $term = makeSchemaDictionaryTerm('revise');

    Carbon\Carbon::setTestNow(now()->addDays(5));
    $term->definition = ['fr_CA' => 'Définition réellement modifiée.', 'fr' => 'Définition réellement modifiée.'];
    $term->save();
    Carbon\Carbon::setTestNow();

    $term->refresh();
    expect($term->hasKnownEditorialRevision())->toBeTrue();
    $attendu = $term->content_updated_at->toIso8601String();

    $graph = decodeSchemaGraph($term);
    $definedTerm = findByType($graph, 'DefinedTerm');
    $article = findByType($graph, 'Article');

    expect($definedTerm['dateModified'])->toBe($attendu)
        ->and($article['dateModified'])->toBe($attendu);
});

// ── Cas 2 (témoin négatif) : aucune révision connue - AUCUNE clé dateModified n'est émise ────

it('un terme sans révision éditoriale connue ne produit AUCUNE clé dateModified', function () {
    $term = makeSchemaDictionaryTerm('sans-revision');

    // Fraîchement créé : content_updated_at = created_at (même convention que
    // ViewCounterDictionaryTest.php), aucune révision connue.
    expect($term->hasKnownEditorialRevision())->toBeFalse();

    $graph = decodeSchemaGraph($term);
    $definedTerm = findByType($graph, 'DefinedTerm');
    $article = findByType($graph, 'Article');

    // Mord si la garde disparaît : sans ce test, un correctif qui émettrait toujours une date
    // (par exemple un repli silencieux sur editorialModifiedAt(), qui retombe sur created_at)
    // passerait à tort pour un succès.
    expect($definedTerm)->not->toHaveKey('dateModified')
        ->and($article)->not->toHaveKey('dateModified');
});

// ── Cas 3 : le défaut d'origine - updated_at poussé loin devant content_updated_at (simule
//      la dérive mesurée, jusqu'à ~50 jours) ne doit JAMAIS apparaître dans le JSON-LD ────────

it('un updated_at poussé loin devant par une simple consultation n\'apparaît jamais dans le JSON-LD', function () {
    $term = makeSchemaDictionaryTerm('derive');

    Carbon\Carbon::setTestNow(now()->addDays(2));
    $term->definition = ['fr_CA' => 'Définition révisée avant la dérive.', 'fr' => 'Définition révisée avant la dérive.'];
    $term->save();
    Carbon\Carbon::setTestNow();
    $term->refresh();
    $contentUpdatedAt = $term->content_updated_at->toIso8601String();

    // Simule la dérive mesurée (docs/specs/2026-09-11-mesure-visibilite-et-fraicheur.md) :
    // updated_at avance de 50 jours SANS passer par save() Eloquent (comme un increment() brut
    // de compteur de vues), donc content_updated_at ne bouge pas.
    $derive = now()->addDays(50);
    Term::query()->whereKey($term->getKey())->update(['updated_at' => $derive]);
    $term->refresh();

    expect($term->updated_at->toIso8601String())->not->toBe($contentUpdatedAt);

    $html = TermSchemaService::buildGraph($term);
    $graph = decodeSchemaGraph($term);
    $definedTerm = findByType($graph, 'DefinedTerm');
    $article = findByType($graph, 'Article');

    expect($html)->not->toContain($derive->toIso8601String())
        ->and($definedTerm['dateModified'])->toBe($contentUpdatedAt)
        ->and($article['dateModified'])->toBe($contentUpdatedAt);
});

<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Tests unitaires de CompositionPayloadNormalizer (design doc "extension de l'écran de
 * composition des actualités", 2026-09-03, section 2.2 - Lot 1 « fondations »). Verrouille le
 * comportement des méthodes migrées depuis Modules\News\Console\NewsApplyCommand (mêmes cas
 * déjà couverts avant l'extraction) - preuve que le déplacement n'a rien changé - et de la
 * méthode fusionnée validateProofPair(). Convention du projet : uses(Tests\TestCase::class)
 * sans RefreshDatabase (service purement statique, aucun modèle/BD requis) - même patron que
 * Modules\News\tests\Unit\SummaryQualityGateTest.php / DedupServiceTest.php.
 */

use Modules\News\Services\CompositionPayloadNormalizer;

uses(Tests\TestCase::class);

// ── normalizeComposedSummary() ──────────────────────────────────────────────────────

it('normalise un composed_summary complet et valide', function () {
    $result = CompositionPayloadNormalizer::normalizeComposedSummary([
        'hook' => 'Une accroche de test.',
        'key_points' => ['Premier point.', 'Deuxième point.'],
        'why_important' => 'Parce que.',
        'key_number' => '42 %',
        'quote' => ['text' => 'Une citation.', 'author' => 'Une personne'],
        'angle_qc_ca' => 'Angle québécois.',
        'action_concrete' => 'Fais ceci.',
        'reperes_dates' => [['date' => '2026', 'texte' => 'Un jalon.']],
    ]);

    expect($result['ok'])->toBeTrue()
        ->and($result['error'])->toBeNull()
        ->and($result['value']['hook'])->toBe('Une accroche de test.')
        ->and($result['value']['key_points'])->toBe(['Premier point.', 'Deuxième point.'])
        ->and($result['value']['quote'])->toBe(['text' => 'Une citation.', 'author' => 'Une personne'])
        ->and($result['value']['reperes_dates'])->toBe([['date' => '2026', 'texte' => 'Un jalon.']]);
});

it('refuse une sous-clé inconnue de composed_summary, avec le message exact', function () {
    $result = CompositionPayloadNormalizer::normalizeComposedSummary(['clef_inventee' => 'x']);

    expect($result['ok'])->toBeFalse()
        ->and($result['value'])->toBeNull()
        ->and($result['error'])->toContain('Clé(s) non autorisée(s) dans composed_summary')
        ->and($result['error'])->toContain('clef_inventee');
});

it('refuse une sous-clé simple qui dépasse 600 caractères', function () {
    $result = CompositionPayloadNormalizer::normalizeComposedSummary(['hook' => str_repeat('a', 601)]);

    expect($result['ok'])->toBeFalse()
        ->and($result['error'])->toContain('composed_summary.hook dépasse 600 caractères.');
});

it('un null explicite sur une sous-clé simple devient une valeur null dans le résultat (retrait délibéré)', function () {
    $result = CompositionPayloadNormalizer::normalizeComposedSummary(['hook' => null]);

    expect($result['ok'])->toBeTrue()
        ->and(array_key_exists('hook', $result['value']))->toBeTrue()
        ->and($result['value']['hook'])->toBeNull();
});

it('une sous-clé absente du payload est absente du résultat (jamais déduite à null)', function () {
    $result = CompositionPayloadNormalizer::normalizeComposedSummary(['hook' => 'Texte.']);

    expect($result['ok'])->toBeTrue()
        ->and(array_key_exists('why_important', $result['value']))->toBeFalse();
});

it('retire le tiret cadratin des sous-clés de prose simple (CLAUDE.md règle 10)', function () {
    $result = CompositionPayloadNormalizer::normalizeComposedSummary(['hook' => 'Avant — après.']);

    expect($result['ok'])->toBeTrue()
        ->and($result['value']['hook'])->toBe('Avant - après.');
});

it('key_points : refuse plus de 5 puces', function () {
    $result = CompositionPayloadNormalizer::normalizeComposedSummary(['key_points' => array_fill(0, 6, 'x')]);

    expect($result['ok'])->toBeFalse()
        ->and($result['error'])->toContain('composed_summary.key_points dépasse la limite de 5 puces.');
});

it('key_points : refuse une puce de plus de 300 caractères', function () {
    $result = CompositionPayloadNormalizer::normalizeComposedSummary(['key_points' => [str_repeat('a', 301)]]);

    expect($result['ok'])->toBeFalse()
        ->and($result['error'])->toContain('dépasse 300 caractères.');
});

it('quote : le texte est obligatoire', function () {
    $result = CompositionPayloadNormalizer::normalizeComposedSummary(['quote' => ['author' => 'Quelqu\'un']]);

    expect($result['ok'])->toBeFalse()
        ->and($result['error'])->toContain('composed_summary.quote.text est obligatoire');
});

it('quote : refuse une clé inconnue à l\'intérieur de l\'objet', function () {
    $result = CompositionPayloadNormalizer::normalizeComposedSummary(['quote' => ['text' => 'x', 'source' => 'y']]);

    expect($result['ok'])->toBeFalse()
        ->and($result['error'])->toContain('Clé(s) non autorisée(s) dans composed_summary.quote');
});

it('reperes_dates : refuse plus de 4 repères', function () {
    $repere = ['date' => '2026', 'texte' => 'x'];
    $result = CompositionPayloadNormalizer::normalizeComposedSummary(['reperes_dates' => array_fill(0, 5, $repere)]);

    expect($result['ok'])->toBeFalse()
        ->and($result['error'])->toContain('composed_summary.reperes_dates dépasse la limite de 4 repères.');
});

it('reperes_dates : refuse une url invalide', function () {
    $result = CompositionPayloadNormalizer::normalizeComposedSummary([
        'reperes_dates' => [['date' => '2026', 'texte' => 'x', 'url' => 'pas-une-url']],
    ]);

    expect($result['ok'])->toBeFalse()
        ->and($result['error'])->toContain('url invalide');
});

// ── overlayComposedSummary() ────────────────────────────────────────────────────────

it('overlayComposedSummary fusionne : sous-clé fournie réécrit, sous-clé absente préservée, null retire', function () {
    $existing = ['hook' => 'Ancien hook.', 'why_important' => 'Ancienne raison.', 'key_number' => '10 %'];
    $normalized = ['hook' => 'Nouveau hook.', 'key_number' => null];

    $result = CompositionPayloadNormalizer::overlayComposedSummary($existing, $normalized);

    expect($result['hook'])->toBe('Nouveau hook.')
        ->and($result['why_important'])->toBe('Ancienne raison.')
        ->and(array_key_exists('key_number', $result))->toBeFalse()
        ->and($result['composed'])->toBeTrue();
});

it('overlayComposedSummary sur une fiche sans composition existante équivaut à un remplacement pur', function () {
    $result = CompositionPayloadNormalizer::overlayComposedSummary([], ['hook' => 'Seul hook fourni.']);

    expect($result)->toBe(['hook' => 'Seul hook fourni.', 'composed' => true]);
});

// ── normalizePrimarySources() ───────────────────────────────────────────────────────

it('normalise une liste valide de sources primaires, note absente devient null', function () {
    $result = CompositionPayloadNormalizer::normalizePrimarySources([
        ['label' => 'Le Devoir', 'url' => 'https://exemple.com/article'],
    ]);

    expect($result['ok'])->toBeTrue()
        ->and($result['value'])->toBe([
            ['label' => 'Le Devoir', 'url' => 'https://exemple.com/article', 'note' => null],
        ]);
});

it('refuse plus de 10 sources primaires', function () {
    $source = ['label' => 'X', 'url' => 'https://exemple.com'];
    $result = CompositionPayloadNormalizer::normalizePrimarySources(array_fill(0, 11, $source));

    expect($result['ok'])->toBeFalse()
        ->and($result['error'])->toBe('primary_sources dépasse la limite de 10 sources.');
});

it('refuse une source primaire sans url valide (http/https)', function () {
    $result = CompositionPayloadNormalizer::normalizePrimarySources([
        ['label' => 'X', 'url' => 'ftp://exemple.com/fichier'],
    ]);

    expect($result['ok'])->toBeFalse()
        ->and($result['error'])->toContain('URL de source primaire invalide');
});

it('refuse une source primaire dont le label ou l\'url est absent', function () {
    $result = CompositionPayloadNormalizer::normalizePrimarySources([['label' => 'X']]);

    expect($result['ok'])->toBeFalse()
        ->and($result['error'])->toBe('Chaque source de primary_sources doit contenir label et url (chaînes).');
});

// ── normalizeSlugsList() ────────────────────────────────────────────────────────────

it('normalise une liste de slugs valide et ré-indexe le tableau', function () {
    $result = CompositionPayloadNormalizer::normalizeSlugsList(['a' => 'slug-un', 'b' => 'slug-deux'], 'related_tool_slugs', 10);

    expect($result['ok'])->toBeTrue()
        ->and($result['value'])->toBe(['slug-un', 'slug-deux']);
});

it('refuse une liste de slugs qui dépasse le plafond, avec le nom du champ dans le message', function () {
    $result = CompositionPayloadNormalizer::normalizeSlugsList(['a', 'b', 'c'], 'related_article_slugs', 1);

    expect($result['ok'])->toBeFalse()
        ->and($result['error'])->toBe('related_article_slugs dépasse la limite de 1 slug(s).');
});

it('refuse un slug vide dans la liste', function () {
    $result = CompositionPayloadNormalizer::normalizeSlugsList(['valide', '   '], 'related_tool_slugs', 10);

    expect($result['ok'])->toBeFalse()
        ->and($result['error'])->toContain('doit être une chaîne non vide de 120 caractères maximum');
});

it('refuse un slug de plus de 120 caractères', function () {
    $result = CompositionPayloadNormalizer::normalizeSlugsList([str_repeat('a', 121)], 'related_tool_slugs', 10);

    expect($result['ok'])->toBeFalse()
        ->and($result['error'])->toContain('120 caractères maximum');
});

// ── validateProofPair() ─────────────────────────────────────────────────────────────

it('valide une paire "fact" dont l\'extrait est une sous-chaîne exacte de la source', function () {
    $source = 'Le modèle a été entraîné sur 12 000 heures de vidéo.';
    $result = CompositionPayloadNormalizer::validateProofPair($source, [
        'statement' => 'Le modèle a beaucoup appris.',
        'excerpt' => 'entraîné sur 12 000 heures de vidéo',
        'type' => 'fact',
    ]);

    expect($result['ok'])->toBeTrue()
        ->and($result['reason'])->toBeNull()
        ->and($result['entry']['type'])->toBe('fact')
        ->and($result['entry']['statement'])->toBe('Le modèle a beaucoup appris.')
        ->and(array_key_exists('source_verified', $result['entry']))->toBeFalse()
        ->and($result['entry']['id'])->toBeString()
        ->and($result['entry']['created_at'])->toBeString();
});

it('refuse une paire "fact" dont l\'extrait n\'est pas dans la source', function () {
    $result = CompositionPayloadNormalizer::validateProofPair('Un texte source quelconque.', [
        'statement' => 'Une affirmation.',
        'excerpt' => 'Ceci n\'apparaît nulle part dans la source.',
        'type' => 'fact',
    ]);

    expect($result['ok'])->toBeFalse()
        ->and($result['entry'])->toBeNull()
        ->and($result['reason'])->toContain('absent du texte source');
});

it('accepte une paire "fact" SANS vérification quand la source est déjà purgée (todo #1984), marquée source_verified=false', function () {
    $result = CompositionPayloadNormalizer::validateProofPair('', [
        'statement' => 'Une affirmation.',
        'excerpt' => 'N\'importe quel extrait.',
        'type' => 'fact',
    ]);

    expect($result['ok'])->toBeTrue()
        ->and($result['entry']['source_verified'])->toBeFalse();
});

it('une paire "analysis" n\'est jamais vérifiée contre la source, même un extrait absent est accepté', function () {
    $result = CompositionPayloadNormalizer::validateProofPair('Texte source sans rapport.', [
        'statement' => 'Mon opinion éditoriale.',
        'excerpt' => 'Un extrait qui ne vient pas du tout de la source.',
        'type' => 'analysis',
    ]);

    expect($result['ok'])->toBeTrue()
        ->and(array_key_exists('source_verified', $result['entry']))->toBeFalse();
});

it('une paire "primary_fact" exige une source_url http/https valide', function () {
    $sansUrl = CompositionPayloadNormalizer::validateProofPair('', [
        'statement' => 'Un fait confirmé à la source.',
        'excerpt' => 'Extrait de l\'original.',
        'type' => 'primary_fact',
    ]);

    $avecUrlInvalide = CompositionPayloadNormalizer::validateProofPair('', [
        'statement' => 'Un fait confirmé à la source.',
        'excerpt' => 'Extrait de l\'original.',
        'type' => 'primary_fact',
        'source_url' => 'pas-une-url',
    ]);

    $avecUrlValide = CompositionPayloadNormalizer::validateProofPair('', [
        'statement' => 'Un fait confirmé à la source.',
        'excerpt' => 'Extrait de l\'original.',
        'type' => 'primary_fact',
        'source_url' => 'https://exemple.com/original',
    ]);

    expect($sansUrl['ok'])->toBeFalse()
        ->and($avecUrlInvalide['ok'])->toBeFalse()
        ->and($avecUrlValide['ok'])->toBeTrue()
        ->and($avecUrlValide['entry']['source_url'])->toBe('https://exemple.com/original');
});

it('refuse un type de paire inconnu', function () {
    $result = CompositionPayloadNormalizer::validateProofPair('Source.', [
        'statement' => 'x', 'excerpt' => 'x', 'type' => 'opinion',
    ]);

    expect($result['ok'])->toBeFalse()
        ->and($result['reason'])->toContain('type invalide');
});

it('refuse une paire à qui il manque statement, excerpt ou type', function () {
    $result = CompositionPayloadNormalizer::validateProofPair('Source.', ['statement' => 'x']);

    expect($result['ok'])->toBeFalse()
        ->and($result['reason'])->toBe('doit contenir statement, excerpt et type (chaînes).');
});

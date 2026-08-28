<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Preuve du correctif 2026_08_28_100000_add_codex_alias.php : la fiche « OpenAI Codex »
 * (aliases=[], match_strategy=case_sensitive AVANT ce correctif) ne se liait qu'à la chaîne EXACTE
 * « OpenAI Codex », jamais à « Codex » seul - or le corps du site écrit presque toujours « Codex ».
 *
 * Reproduit ICI la configuration exacte posée par la migration d'alias, sans dépendre de la
 * migration de DONNÉES elle-même : 2026_08_27_110000_add_codex_term.php n'insère la fiche que sous
 * le driver mysql (local/prod), jamais sous SQLite :memory: (driver des tests, voir phpunit.xml) -
 * la fiche réelle n'existe donc pas dans cette suite. Ce test construit un Term qui porte
 * EXACTEMENT ce que la migration d'alias applique (aliases=['Codex'], match_strategy=
 * 'case_sensitive'), puis prouve le comportement réel via l'appel PUBLIC
 * GlossaryLinkifier::linkify() - jamais un helper de réflexion qui court-circuiterait loadTerms()
 * et donc l'escalade de stratégie sur l'alias (escalateStrategyIfStopList()).
 *
 * Le test qui compte vraiment est le deuxième : « codex » minuscule ne doit JAMAIS se lier, c'est
 * lui qui protège du sens commun français (manuscrit ancien, Larousse). Le troisième test le
 * verrouille par contraste : sans match_strategy=case_sensitive (donc en 'loose'), la même phrase
 * minuscule SE LIE - preuve que le test 2 dépend réellement de la casse stricte et passerait au
 * rouge si elle disparaissait un jour de la fiche réelle. Vérifié manuellement pendant le
 * développement (php artisan test avec match_strategy forcé à 'loose' sur le test 2 -> échec
 * confirmé), voir le rapport de la tâche.
 */

use Modules\Core\Services\GlossaryLinkifier;
use Modules\Dictionary\Models\Term;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

function makeCodexTerm(string $matchStrategy = 'case_sensitive'): Term
{
    config(['app.locale' => 'fr_CA']);
    $locale = app()->getLocale();
    $slug = 'openai-codex-test-'.uniqid();

    return Term::create([
        'name' => [$locale => 'OpenAI Codex', 'fr' => 'OpenAI Codex'],
        'slug' => [$locale => $slug, 'fr' => $slug],
        'definition' => [$locale => 'Agent d\'ingénierie logicielle infonuagique d\'OpenAI.', 'fr' => 'Agent d\'ingénierie logicielle infonuagique d\'OpenAI.'],
        'is_published' => true,
        'match_strategy' => $matchStrategy,
        'aliases' => ['Codex'],
    ]);
}

beforeEach(function () {
    GlossaryLinkifier::resetState();
    GlossaryLinkifier::flushCache();
});

it('lie "Codex" capitalise, ecrit seul, vers la fiche OpenAI Codex', function () {
    $terme = makeCodexTerm();

    $html = GlossaryLinkifier::linkify('<p>Rakuten a reduit son MTTR grace a Codex, l\'agent d\'OpenAI.</p>');

    expect($html)->toContain('/glossaire/'.$terme->slug)
        ->and($html)->toContain('glossary-link')
        ->and($html)->toContain('>Codex</a>');
});

it('ne lie PAS "codex" en minuscules - proteg le sens commun francais (manuscrit ancien)', function () {
    $terme = makeCodexTerm();

    // Phrase reelle du site (article de blog laveille.ai sur l'histoire des innovations) : le
    // "codex" minuscule y designe un manuscrit ancien, jamais l'outil d'OpenAI.
    $html = GlossaryLinkifier::linkify('<p>Les livres imprimes ne pourraient jamais egaler les codex manuscrits.</p>');

    expect($html)->not->toContain('glossary-link')
        ->and($html)->not->toContain('/glossaire/'.$terme->slug);
});

it('ROUGE sans case_sensitive : la meme phrase minuscule se lierait en strategie loose', function () {
    // Verrouille la DEPENDANCE du test precedent envers match_strategy=case_sensitive : si la
    // fiche reelle perdait un jour cette strategie, ce test-ci prouve que le sens commun francais
    // recommencerait a etre capte a tort.
    makeCodexTerm('loose');

    $html = GlossaryLinkifier::linkify('<p>Les livres imprimes ne pourraient jamais egaler les codex manuscrits.</p>');

    expect($html)->toContain('glossary-link');
});

it('ne coupe pas de lien a l interieur d un mot compose sans espace (CodexBar hors de portee)', function () {
    makeCodexTerm();

    // Nom reel d'un outil de l'annuaire (id 2272, "CodexBar Lite") : aucune frontiere de mot entre
    // "Codex" et "Bar", donc aucun lien ne doit se poser au milieu du mot compose.
    $html = GlossaryLinkifier::linkify('<p>CodexBar Lite affiche vos statistiques dans la barre de menu macOS.</p>');

    expect($html)->not->toContain('glossary-link');
});

it('enveloppe uniquement le mot "Codex", jamais un fragment voisin', function () {
    $terme = makeCodexTerm();

    // Phrase calquee sur une description reelle de l'annuaire ("Animated companions for your Codex
    // workflow") : le lien doit s'arreter net a "Codex", jamais engloutir "workflow" ni s'arreter
    // avant la fin du mot.
    $html = GlossaryLinkifier::linkify('<p>Cette extension anime des compagnons pour votre atelier Codex workflow.</p>');

    expect($html)->toContain('>Codex</a> workflow')
        ->and($html)->not->toContain('>Codex workflow</a>')
        ->and($html)->not->toContain('/glossaire/'.$terme->slug.'">Cod</a>');
});

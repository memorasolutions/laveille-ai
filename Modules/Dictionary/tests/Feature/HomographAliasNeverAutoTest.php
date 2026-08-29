<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Deux faux liens MESURÉS en production le 2026-08-29, même famille de défaut que Codex (voir
 * CodexAliasAutoLinkTest.php) mais deux causes distinctes dans loadTerms() :
 *
 *  1. Sur une actualité de journalisme, « CNN » (réseau de télévision) était lié quatre fois vers
 *     /glossaire/reseau-convolutif. Cause : extractQualifierAliases('Réseau convolutif (CNN)')
 *     dérive « CNN » comme alias, exactement comme conçu (voir son docblock) - CNN porte, hors de
 *     ce site, un second sens que le glossaire ignore.
 *  2. Sur une actualité de droit, « une requête en rejet » était lié vers /glossaire/prompt. Cause :
 *     la migration 2026_07_23_160000_add_requete_alias_to_prompt_term.php a curé « requête » et
 *     « requêtes » comme alias de « prompt » - vrai en contexte IA, mais « requête » est un nom
 *     commun français omniprésent hors de ce contexte.
 *
 * Correctif : GlossaryLinkifier::ALIAS_NEVER_AUTO (voir son docblock) - liste de blocage vérifiée
 * au moment où un ALIAS (curé, dérivé d'un qualifier, ou variante morphologique) entre dans
 * $terms, jamais le nom PRINCIPAL d'une fiche. Reproduit ICI la configuration exacte des fiches
 * réelles (aucune des deux n'existe dans cette suite : « reseau-convolutif » n'est dans AUCUN
 * seeder/migration versionné - créée directement en base de production - et « prompt » vient du
 * seeder original DictionarySeeder.php sans les alias posés ensuite par migration), même patron
 * que CodexAliasAutoLinkTest.php : appel PUBLIC GlossaryLinkifier::linkify(), jamais la réflexion
 * bas niveau de GlossaryLinkifierTest.php.
 *
 * Les tests GAN et « réseau convolutif » (sans CNN) verrouillent la frontière : élargir une
 * exclusion casse silencieusement les termes voisins (mesuré sur ce projet le 2026-08-27) - CNN
 * doit rester bloqué SEUL, ni son propre terme de base, ni un sigle acronyme voisin (GAN), ni le
 * mot « prompt » lui-même ne doivent perdre leur auto-lien légitime.
 */

use Modules\Core\Services\GlossaryLinkifier;
use Modules\Dictionary\Models\Term;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

// ── Helpers (préfixe han = Homograph Alias Never-auto) ────────────────────────

function hanTerm(string $name, string $slug, array $aliases = [], string $matchStrategy = 'loose'): Term
{
    config(['app.locale' => 'fr_CA']);
    $locale = app()->getLocale();
    $uniqueSlug = $slug.'-'.uniqid();

    return Term::create([
        'name' => [$locale => $name, 'fr' => $name],
        'slug' => [$locale => $uniqueSlug, 'fr' => $uniqueSlug],
        'definition' => [$locale => 'Définition de test pour '.$name.'.', 'fr' => 'Définition de test pour '.$name.'.'],
        'is_published' => true,
        'match_strategy' => $matchStrategy,
        'aliases' => $aliases,
    ]);
}

beforeEach(function () {
    GlossaryLinkifier::resetState();
    GlossaryLinkifier::flushCache();
});

// ── Cas 1 : CNN (réseau de télévision) vs Réseau convolutif (CNN) ─────────────

it('ne lie PAS "CNN" - texte réel qui a produit le faux lien en production (journalisme, pas IA)', function () {
    hanTerm('Réseau convolutif (CNN)', 'reseau-convolutif-test');

    $html = GlossaryLinkifier::linkify('<p>Le réseau CNN rapportait le 22 août 2026 des informations sur des figurants payés.</p>');

    expect($html)->not->toContain('glossary-link')
        ->and($html)->not->toContain('/glossaire/reseau-convolutif');
});

it('lie toujours "réseau convolutif" (la base, sans CNN) dans un vrai texte technique', function () {
    $terme = hanTerm('Réseau convolutif (CNN)', 'reseau-convolutif-test');

    $html = GlossaryLinkifier::linkify('<p>Le réseau convolutif reste une architecture classique en vision par ordinateur.</p>');

    expect($html)->toContain('glossary-link')
        ->and($html)->toContain('/glossaire/'.$terme->slug);
});

it('lie toujours un sigle acronyme VOISIN (GAN) - la frontière ne doit bloquer que CNN', function () {
    $terme = hanTerm('Réseau antagoniste génératif (GAN)', 'reseau-antagoniste-generatif-test');

    $html = GlossaryLinkifier::linkify('<p>Un GAN peut générer des images synthétiques très réalistes.</p>');

    expect($html)->toContain('glossary-link')
        ->and($html)->toContain('/glossaire/'.$terme->slug)
        ->and($html)->toContain('>GAN</a>');
});

// ── Cas 2 : requête (nom commun juridique) vs alias curé de Prompt ────────────

it('ne lie PAS "requête" dans "une requête en rejet" - texte réel qui a produit le faux lien (droit, pas IA)', function () {
    hanTerm('Prompt', 'prompt-test', aliases: ['requête', 'requêtes']);

    $html = GlossaryLinkifier::linkify('<p>Une requête en rejet est une manoeuvre défensive utilisée par la défense.</p>');

    expect($html)->not->toContain('glossary-link')
        ->and($html)->not->toContain('/glossaire/prompt');
});

it('lie toujours "prompt" lui-même - le nom principal de la fiche reste intact', function () {
    $terme = hanTerm('Prompt', 'prompt-test', aliases: ['requête', 'requêtes']);

    $html = GlossaryLinkifier::linkify('<p>Écris un bon prompt pour obtenir une réponse précise du modèle.</p>');

    expect($html)->toContain('glossary-link')
        ->and($html)->toContain('/glossaire/'.$terme->slug)
        ->and($html)->toContain('>prompt</a>');
});

// ── Cas 3 : témoin (personne qui témoigne) vs alias curé de Cookie, repéré par l'audit ─

it('ne lie PAS "témoin" dans un contexte judiciaire - même motif, corrigé par précaution', function () {
    hanTerm('Témoin de connexion (cookie)', 'cookie-test', aliases: ['cookie', 'cookies', 'témoin', 'témoin de connexion']);

    $html = GlossaryLinkifier::linkify('<p>Le témoin a déclaré ne rien savoir des événements survenus ce soir-là.</p>');

    expect($html)->not->toContain('glossary-link')
        ->and($html)->not->toContain('/glossaire/cookie-test');
});

it('lie toujours "cookie" - le mot technique reste un alias valide du même terme', function () {
    $terme = hanTerm('Témoin de connexion (cookie)', 'cookie-test', aliases: ['cookie', 'cookies', 'témoin', 'témoin de connexion']);

    $html = GlossaryLinkifier::linkify('<p>Ce site dépose un cookie pour mémoriser vos préférences de langue.</p>');

    expect($html)->toContain('glossary-link')
        ->and($html)->toContain('/glossaire/'.$terme->slug);
});

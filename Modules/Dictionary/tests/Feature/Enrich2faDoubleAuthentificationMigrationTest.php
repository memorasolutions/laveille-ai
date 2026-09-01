<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Couvre Modules/Dictionary/database/migrations/2026_09_01_220000_enrich_2fa_double_authentification.php
 * - preuve directe sur le FICHIER de migration livré, jamais une réimplémentation de sa logique.
 * Verrouille :
 *  - les 3 alias, les 3 FAQ et les 2 sources s'AJOUTENT sur `2fa` sans toucher au contenu déjà
 *    présent (alias/FAQ/source de test pré-existants conservés tels quels) ;
 *  - `mfa.narrower_slugs` gagne `passkey` et `passkey.broader_slugs` gagne `mfa`, sans écraser les
 *    relations déjà posées (`2fa` côté mfa, `fido2` côté passkey) ;
 *  - idempotence : ré-exécuter up() ne duplique rien ;
 *  - down() reconstruit exactement l'état de départ des cinq champs touchés ;
 *  - portabilité : up()/down() ne lèvent aucune exception si un ou plusieurs des trois termes sont
 *    absents (cas réel de l'environnement local, où `2fa`/`mfa`/`passkey` n'existent QUE côté
 *    production - créés hors dépôt git avant le suivi par migrations) ;
 *  - preuve comportementale : "double authentification", écrit dans un texte, se lie réellement à
 *    la fiche `2fa` après la migration - alors qu'AVANT (aliases=[]), la même phrase ne se liait
 *    pas (l'extraction automatique du qualificatif entre parenthèses du `name` ne pousse pas une
 *    locution française minuscule en alias, seulement un acronyme propre - vérifié en lisant
 *    GlossaryLinkifier::extractQualifierAliases() avant d'écrire ce test).
 */

use Modules\Core\Services\GlossaryLinkifier;
use Modules\Dictionary\Models\Term;

uses(Tests\TestCase::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

function e2faMigration()
{
    return require base_path('Modules/Dictionary/database/migrations/2026_09_01_220000_enrich_2fa_double_authentification.php');
}

function e2faSeedTerms(): array
{
    config(['app.locale' => 'fr_CA']);
    $locale = app()->getLocale();

    $terme2fa = Term::create([
        'name' => [$locale => '2FA (authentification à deux facteurs)', 'fr' => '2FA (authentification à deux facteurs)'],
        'slug' => [$locale => '2fa', 'fr' => '2fa'],
        'definition' => [$locale => 'Définition de test.', 'fr' => 'Définition de test.'],
        'is_published' => true,
        'match_strategy' => 'case_sensitive',
        'aliases' => ['alias-preexistant-2fa'],
        'broader_slugs' => ['mfa'],
        'faq' => [
            ['question' => 'Question préexistante ?', 'answer' => 'Réponse préexistante.'],
        ],
        'sources' => [
            ['label' => 'Source préexistante', 'url' => 'https://exemple.test/preexistante', 'year' => '2024', 'author' => 'Test'],
        ],
    ]);

    $termeMfa = Term::create([
        'name' => [$locale => 'MFA (authentification multifacteur)', 'fr' => 'MFA (authentification multifacteur)'],
        'slug' => [$locale => 'mfa', 'fr' => 'mfa'],
        'definition' => [$locale => 'Définition de test.', 'fr' => 'Définition de test.'],
        'is_published' => true,
        'match_strategy' => 'case_sensitive',
        'aliases' => ['Multi-Factor Authentication'],
        'narrower_slugs' => ['2fa'],
    ]);

    $termePasskey = Term::create([
        'name' => [$locale => "passkey (clé d'accès)", 'fr' => "passkey (clé d'accès)"],
        'slug' => [$locale => 'passkey', 'fr' => 'passkey'],
        'definition' => [$locale => 'Définition de test.', 'fr' => 'Définition de test.'],
        'is_published' => true,
        'match_strategy' => 'loose',
        'aliases' => ["clé d'accès"],
        'broader_slugs' => ['fido2'],
    ]);

    return [$terme2fa, $termeMfa, $termePasskey];
}

it('ajoute les 3 alias, les 3 FAQ et les 2 sources sur `2fa` sans toucher au contenu déjà présent', function () {
    [$terme2fa] = e2faSeedTerms();

    e2faMigration()->up();
    $terme2fa->refresh();

    expect($terme2fa->aliases)->toBe([
        'alias-preexistant-2fa',
        'double authentification',
        'authentification à deux facteurs',
        'Two-Factor Authentication',
    ])
        ->and($terme2fa->faq)->toHaveCount(4)
        ->and($terme2fa->faq[0]['question'])->toBe('Question préexistante ?')
        ->and(array_column($terme2fa->faq, 'question'))->toContain('Double authentification, 2FA, MFA : quelle est la différence?')
        ->and($terme2fa->sources)->toHaveCount(3)
        ->and($terme2fa->sources[0]['label'])->toBe('Source préexistante')
        ->and(array_column($terme2fa->sources, 'url'))->toContain('https://vitrinelinguistique.oqlf.gouv.qc.ca/fiche-gdt/fiche/26557344/authentification-a-deux-facteurs');
});

it('ajoute `passkey` aux narrower_slugs de `mfa` et `mfa` aux broader_slugs de `passkey`, sans écraser l\'existant', function () {
    [, $termeMfa, $termePasskey] = e2faSeedTerms();

    e2faMigration()->up();
    $termeMfa->refresh();
    $termePasskey->refresh();

    expect($termeMfa->narrower_slugs)->toBe(['2fa', 'passkey'])
        ->and($termePasskey->broader_slugs)->toBe(['fido2', 'mfa']);
});

it('est idempotente : ré-exécuter up() ne duplique ni alias, ni FAQ, ni sources, ni relations', function () {
    [$terme2fa, $termeMfa, $termePasskey] = e2faSeedTerms();

    $migration = e2faMigration();
    $migration->up();
    $migration->up();

    $terme2fa->refresh();
    $termeMfa->refresh();
    $termePasskey->refresh();

    expect($terme2fa->aliases)->toHaveCount(4)
        ->and($terme2fa->faq)->toHaveCount(4)
        ->and($terme2fa->sources)->toHaveCount(3)
        ->and($termeMfa->narrower_slugs)->toBe(['2fa', 'passkey'])
        ->and($termePasskey->broader_slugs)->toBe(['fido2', 'mfa']);
});

it('down() reconstruit exactement l\'état de départ des cinq champs touchés', function () {
    [$terme2fa, $termeMfa, $termePasskey] = e2faSeedTerms();
    $aliasesAvant = $terme2fa->aliases;
    $faqAvant = $terme2fa->faq;
    $sourcesAvant = $terme2fa->sources;
    $narrowerMfaAvant = $termeMfa->narrower_slugs;
    $broaderPasskeyAvant = $termePasskey->broader_slugs;

    $migration = e2faMigration();
    $migration->up();
    $migration->down();

    $terme2fa->refresh();
    $termeMfa->refresh();
    $termePasskey->refresh();

    expect($terme2fa->aliases)->toBe($aliasesAvant)
        ->and($terme2fa->faq)->toBe($faqAvant)
        ->and($terme2fa->sources)->toBe($sourcesAvant)
        ->and($termeMfa->narrower_slugs)->toBe($narrowerMfaAvant)
        ->and($termePasskey->broader_slugs)->toBe($broaderPasskeyAvant);
});

it('ne lève aucune exception si aucun des trois termes n\'existe (portabilité environnement local)', function () {
    e2faMigration()->up();
    e2faMigration()->down();

    expect(Term::count())->toBe(0);
});

it('ne lève aucune exception si seul `2fa` existe (mfa et passkey absents)', function () {
    config(['app.locale' => 'fr_CA']);
    $locale = app()->getLocale();
    $terme2fa = Term::create([
        'name' => [$locale => '2FA (authentification à deux facteurs)', 'fr' => '2FA (authentification à deux facteurs)'],
        'slug' => [$locale => '2fa', 'fr' => '2fa'],
        'definition' => [$locale => 'Définition de test.', 'fr' => 'Définition de test.'],
        'is_published' => true,
        'match_strategy' => 'case_sensitive',
    ]);

    e2faMigration()->up();
    $terme2fa->refresh();

    expect($terme2fa->aliases)->toBe([
        'double authentification',
        'authentification à deux facteurs',
        'Two-Factor Authentication',
    ]);
});

it('lie "double authentification" écrit dans un texte à la fiche `2fa`, alors que ça ne se liait pas avant la migration', function () {
    [$terme2fa] = e2faSeedTerms();
    GlossaryLinkifier::resetState();
    GlossaryLinkifier::flushCache();

    $avant = GlossaryLinkifier::linkify('<p>Activer la double authentification protège le compte des enseignants.</p>');
    expect($avant)->not->toContain('glossary-link');

    e2faMigration()->up();
    GlossaryLinkifier::resetState();
    GlossaryLinkifier::flushCache();

    $apres = GlossaryLinkifier::linkify('<p>Activer la double authentification protège le compte des enseignants.</p>');
    expect($apres)->toContain('/glossaire/'.$terme2fa->slug)
        ->and($apres)->toContain('glossary-link');
});

<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Couvre Modules/Dictionary/database/migrations/2026_08_31_093000_relocate_mistral_family_aliases.php
 * (ticket #2076, point 2) - preuve directe sur le FICHIER de migration livré (pas seulement sur le
 * mécanisme général, déjà couvert par Modules/Dictionary/tests/Feature/HomographAliasNeverAutoTest.php
 * Cas 6). Verrouille :
 *  - "Mistral Large" et "Mixtral" quittent les alias du terme produit et rejoignent ceux du terme
 *    éditeur, sans toucher aux autres alias déjà présents de chaque côté ;
 *  - idempotence : ré-exécuter up() ne duplique rien (array_unique) et ne relève pas d'erreur ;
 *  - down() reconstruit exactement l'état de départ des deux tableaux d'alias ;
 *  - portabilité : up()/down() ne lèvent aucune exception si l'un des deux termes est absent (cas
 *    réel de l'environnement local, où "mistral-le-chat" n'existe pas - créé hors dépôt git avant
 *    le suivi par migrations).
 */

use Modules\Dictionary\Models\Term;

uses(Tests\TestCase::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

function rmfaMigration()
{
    return require base_path('Modules/Dictionary/database/migrations/2026_08_31_093000_relocate_mistral_family_aliases.php');
}

function rmfaSeedTerms(): array
{
    config(['app.locale' => 'fr_CA']);
    $locale = app()->getLocale();

    $produit = Term::create([
        'name' => [$locale => 'Mistral (Le Chat)', 'fr' => 'Mistral (Le Chat)'],
        'slug' => [$locale => 'mistral-le-chat', 'fr' => 'mistral-le-chat'],
        'definition' => [$locale => 'Définition de test.', 'fr' => 'Définition de test.'],
        'is_published' => true,
        'match_strategy' => 'loose',
        'aliases' => ['Mistral', 'Mistral Large', 'Le Chat Mistral', 'Mixtral'],
    ]);

    $editeur = Term::create([
        'name' => [$locale => 'Mistral', 'fr' => 'Mistral'],
        'slug' => [$locale => 'mistral', 'fr' => 'mistral'],
        'definition' => [$locale => 'Définition de test.', 'fr' => 'Définition de test.'],
        'is_published' => true,
        'match_strategy' => 'case_sensitive',
        'aliases' => ['Mistral AI'],
    ]);

    return [$produit, $editeur];
}

it('retire "Mistral Large" et "Mixtral" du produit, les ajoute à l\'éditeur, sans toucher au reste', function () {
    [$produit, $editeur] = rmfaSeedTerms();

    rmfaMigration()->up();

    $produit->refresh();
    $editeur->refresh();

    expect($produit->aliases)->toBe(['Mistral', 'Le Chat Mistral'])
        ->and($editeur->aliases)->toBe(['Mistral AI', 'Mistral Large', 'Mixtral']);
});

it('est idempotente : ré-exécuter up() ne duplique rien', function () {
    rmfaSeedTerms();

    $migration = rmfaMigration();
    $migration->up();
    $migration->up();

    $editeur = Term::where('slug->fr_CA', 'mistral')->first();
    expect($editeur->aliases)->toBe(['Mistral AI', 'Mistral Large', 'Mixtral']);
});

it('down() reconstruit exactement l\'état de départ des deux tableaux d\'alias', function () {
    [$produit, $editeur] = rmfaSeedTerms();
    $aliasesProduitAvant = $produit->aliases;
    $aliasesEditeurAvant = $editeur->aliases;

    $migration = rmfaMigration();
    $migration->up();
    $migration->down();

    $produit->refresh();
    $editeur->refresh();

    expect($produit->aliases)->toEqualCanonicalizing($aliasesProduitAvant)
        ->and($editeur->aliases)->toEqualCanonicalizing($aliasesEditeurAvant);
});

it('ne lève aucune exception si le terme produit est absent (portabilité environnement local)', function () {
    config(['app.locale' => 'fr_CA']);
    $locale = app()->getLocale();
    Term::create([
        'name' => [$locale => 'Mistral', 'fr' => 'Mistral'],
        'slug' => [$locale => 'mistral', 'fr' => 'mistral'],
        'definition' => [$locale => 'Définition de test.', 'fr' => 'Définition de test.'],
        'is_published' => true,
        'match_strategy' => 'case_sensitive',
        'aliases' => ['Mistral AI'],
    ]);

    rmfaMigration()->up();

    $editeur = Term::where('slug->fr_CA', 'mistral')->first();
    expect($editeur->aliases)->toBe(['Mistral AI']); // inchangé, rien à relocaliser
});

it('ne lève aucune exception si aucun des deux termes n\'existe', function () {
    rmfaMigration()->up();

    expect(Term::count())->toBe(0);
});

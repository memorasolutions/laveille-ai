<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Couvre Modules/Directory/database/migrations/2026_08_31_090000_merge_mistral_duplicate_tools.php
 * (ticket #2076) - fusion du doublon annuaire "Mistral" / "Mistral Le Chat" (deux fiches pour le
 * même produit, chat.mistral.ai). Verrouille :
 *  - la fiche avec le PLUS de clics devient canonique (règle des 5 fusions précédentes de ce
 *    projet - Jasper, LayerGen AI, MiniAi, Copy.ai, Fathom -, jamais l'ordre de création) ;
 *  - la fiche avec le moins de clics est archivée et redirige - HTTP réel, pas seulement les
 *    colonnes lifecycle_* - vers la fiche canonique en 301 (mécanisme générique de
 *    PublicDirectoryController::show(), jusqu'ici jamais couvert par un test malgré 5 usages en
 *    production) ;
 *  - la catégorie de la fiche archivée est copiée sur la fiche canonique quand celle-ci n'en avait
 *    aucune (écart volontaire par rapport au précédent Jasper - voir docblock de la migration) ;
 *  - idempotence : ré-exécuter up() ne duplique rien et n'écrase pas une fusion déjà faite ;
 *  - down() réactive la fiche archivée SANS toucher à la catégorie copiée (additive, jamais
 *    retirée).
 *
 * N'utilise QUE des fiches synthétiques (jamais les IDs réels 23/875 de production) : la migration
 * elle-même sélectionne par contenu (url LIKE + name LIKE), donc une fiche de test qui remplit ces
 * deux critères suffit à l'exercer fidèlement.
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Directory\Models\Category;
use Modules\Directory\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Polyfill FIELD() : même contournement que Modules/Directory/tests/Feature/DirectoryViewCounterTest.php,
// AffiliateLinkTest.php et ThinContentNoindexTest.php (sqlite :memory: de la suite de tests n'a
// pas la fonction MySQL FIELD() utilisée par show() pour trier les ressources - limitation
// pré-existante, sans rapport avec ce correctif, nécessaire seulement pour le test qui appelle la
// route directory.show en HTTP réel).
beforeEach(function () {
    $pdo = DB::connection()->getPdo();
    if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
        $pdo->sqliteCreateFunction('FIELD', function (...$args) {
            $needle = array_shift($args);
            foreach ($args as $i => $value) {
                if ($needle === $value) {
                    return $i + 1;
                }
            }

            return 0;
        });
    }
});

function mmdMigration()
{
    return require base_path('Modules/Directory/database/migrations/2026_08_31_090000_merge_mistral_duplicate_tools.php');
}

/**
 * @return array{0: Tool, 1: Tool} [$moinsClique, $plusClique]
 */
function mmdSeedDuplicates(int $clicksMoins = 368, int $clicksPlus = 447): array
{
    config(['app.locale' => 'fr_CA']);

    $moinsClique = new Tool();
    $moinsClique->setTranslation('name', 'fr_CA', 'Mistral Le Chat');
    $moinsClique->setTranslation('slug', 'fr_CA', 'mmd-mistral-le-chat');
    $moinsClique->setTranslation('description', 'fr_CA', 'Description de test.');
    $moinsClique->setTranslation('short_description', 'fr_CA', 'Résumé de test.');
    $moinsClique->url = 'https://chat.mistral.ai';
    $moinsClique->pricing = 'freemium';
    $moinsClique->status = 'published';
    $moinsClique->clicks_count = $clicksMoins;
    $moinsClique->save();

    $plusClique = new Tool();
    $plusClique->setTranslation('name', 'fr_CA', 'Mistral');
    $plusClique->setTranslation('slug', 'fr_CA', 'mmd-mistral');
    $plusClique->setTranslation('description', 'fr_CA', 'Description de test.');
    $plusClique->setTranslation('short_description', 'fr_CA', 'Résumé de test.');
    $plusClique->url = 'https://chat.mistral.ai';
    $plusClique->pricing = 'freemium';
    $plusClique->status = 'published';
    $plusClique->clicks_count = $clicksPlus;
    $plusClique->save();

    return [$moinsClique->refresh(), $plusClique->refresh()];
}

it('archive la fiche avec le MOINS de clics et la fait pointer vers celle avec le PLUS de clics', function () {
    [$moinsClique, $plusClique] = mmdSeedDuplicates();

    mmdMigration()->up();

    $moinsClique->refresh();
    $plusClique->refresh();

    expect($moinsClique->lifecycle_status)->toBe('archived')
        ->and($moinsClique->lifecycle_replacement_tool_id)->toBe($plusClique->id)
        ->and($plusClique->lifecycle_status)->toBe('active')
        ->and($plusClique->lifecycle_replacement_tool_id)->toBeNull();
});

it('redirige réellement en 301 la fiche archivée vers la fiche canonique (route HTTP, pas seulement les colonnes)', function () {
    [$moinsClique, $plusClique] = mmdSeedDuplicates();

    mmdMigration()->up();

    $response = $this->get(route('directory.show', $moinsClique->getTranslation('slug', 'fr_CA')));

    $response->assertStatus(301)
        ->assertRedirect($plusClique->getPublicUrl());

    // La fiche canonique, elle, répond normalement.
    $this->get(route('directory.show', $plusClique->getTranslation('slug', 'fr_CA')))->assertOk();
});

it('copie la catégorie de la fiche archivée vers la fiche canonique quand celle-ci n\'en a aucune', function () {
    [$moinsClique, $plusClique] = mmdSeedDuplicates();

    $categorie = Category::create([
        'name' => ['fr_CA' => 'Assistants IA test'],
        'slug' => ['fr_CA' => 'mmd-assistants-ia'],
        'sort_order' => 1,
    ]);
    $moinsClique->categories()->attach($categorie->id);

    expect($plusClique->categories()->count())->toBe(0);

    mmdMigration()->up();

    expect($plusClique->categories()->pluck('directory_categories.id')->all())->toBe([$categorie->id])
        // La fiche archivée garde SA propre association - rien n'est retiré côté source.
        ->and($moinsClique->categories()->pluck('directory_categories.id')->all())->toBe([$categorie->id]);
});

it('ne copie PAS une catégorie déjà présente sur la fiche canonique (pas de doublon dans le pivot)', function () {
    [$moinsClique, $plusClique] = mmdSeedDuplicates();

    $categorie = Category::create([
        'name' => ['fr_CA' => 'Assistants IA test 2'],
        'slug' => ['fr_CA' => 'mmd-assistants-ia-2'],
        'sort_order' => 1,
    ]);
    $moinsClique->categories()->attach($categorie->id);
    $plusClique->categories()->attach($categorie->id); // déjà présente avant fusion

    mmdMigration()->up();

    expect($plusClique->categories()->count())->toBe(1);
});

it('est idempotente : ré-exécuter up() sur une fusion déjà faite ne casse rien et ne duplique rien', function () {
    [$moinsClique, $plusClique] = mmdSeedDuplicates();

    $migration = mmdMigration();
    $migration->up();
    $migration->up(); // 2e appel volontaire

    $moinsClique->refresh();
    expect($moinsClique->lifecycle_status)->toBe('archived')
        ->and($moinsClique->lifecycle_replacement_tool_id)->toBe($plusClique->id)
        ->and(DB::table('directory_category_tool')->where('directory_tool_id', $plusClique->id)->count())
        ->toBeLessThanOrEqual(1);
});

it('down() réactive la fiche archivée SANS retirer la catégorie copiée (additive, jamais reprise au rollback)', function () {
    [$moinsClique, $plusClique] = mmdSeedDuplicates();

    $categorie = Category::create([
        'name' => ['fr_CA' => 'Assistants IA test 3'],
        'slug' => ['fr_CA' => 'mmd-assistants-ia-3'],
        'sort_order' => 1,
    ]);
    $moinsClique->categories()->attach($categorie->id);

    $migration = mmdMigration();
    $migration->up();
    $migration->down();

    $moinsClique->refresh();
    $plusClique->refresh();

    expect($moinsClique->lifecycle_status)->toBe('active')
        ->and($moinsClique->lifecycle_replacement_tool_id)->toBeNull()
        ->and($moinsClique->lifecycle_notes)->toBeNull()
        // Catégorie copiée : intentionnellement toujours là après down() (voir docblock migration).
        ->and($plusClique->categories()->pluck('directory_categories.id')->all())->toBe([$categorie->id]);
});

it('ne fait rien si moins de 2 fiches correspondent (portabilité environnement sans données de prod)', function () {
    mmdSeedDuplicates(); // 2 fiches, mais on n'en garde qu'une pour ce cas
    Tool::where('url', 'https://chat.mistral.ai')->latest('id')->first()->delete();

    // Ne doit lever aucune exception.
    mmdMigration()->up();

    expect(Tool::where('lifecycle_status', 'archived')->count())->toBe(0);
});

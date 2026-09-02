<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Ticket #2172 (volet 2) - preuve du correctif déjà posé dans l'arbre de travail :
 *   - PublicDirectoryController::compare() : $category->tools()->published() est devenu
 *     ->published()->notArchived() ;
 *   - ToolComparisonService::loadTools() : ->notArchived() ajouté après ->where('status','published').
 *
 * Défaut fermé : une fiche d'annuaire peut être status='published' ET lifecycle_status='archived'
 * en même temps (cas de toutes les fiches fusionnées qui restent en base et redirigent en 301 -
 * six fusions à ce jour : Mistral, Jasper, LayerGen, MiniAi, Copy.ai, Fathom, cf.
 * MergeMistralDuplicateToolsMigrationTest.php). Les quatre autres portes publiques (index,
 * featured, recent, popular) filtrent déjà par notArchived() ; le comparateur ne le faisait pas -
 * une fiche archivée SUFFISAMMENT CLIQUÉE pouvait donc y être comparée à des outils actifs.
 * Impact en production : nul à ce jour, mais par hasard (les deux fiches Mistral archivées
 * passaient sous le seuil des 6 outils les plus cliqués de leur catégorie) - ce fichier prouve que
 * ce n'est plus un hasard.
 *
 * Deux portes distinctes, testées séparément (l'une pouvait être corrigée sans l'autre - c'était
 * le cas avant ce correctif) :
 *   - PORTE CONTRÔLEUR : route directory.compare (catégorie -> liste d'IDs, LIMIT 6 par clics).
 *     Isolée via l'interaction avec le LIMIT : une fiche archivée au nombre de clics le PLUS ÉLEVÉ
 *     de sa catégorie, si elle n'était pas écartée AVANT le classement, volerait une place aux
 *     outils actifs dans le TOP 6 - un outil actif légitimement 6e disparaîtrait du résultat final
 *     même si loadTools() filtre correctement l'archivée en aval. Le nombre d'outils retournés
 *     (6 et pas 5) et la présence de ce 6e outil actif sont donc le signal qui isole CETTE porte.
 *   - PORTE SERVICE : route directory.compare-by-ids (IDs fournis directement par l'appelant, ne
 *     passe JAMAIS par la branche catégorie du contrôleur - if (empty($ids) && $categorySlug) est
 *     faux dès que des IDs sont fournis) + appel direct à ToolComparisonService::loadTools(),
 *     preuve la plus pure et la plus isolée du filtre propre au service.
 *
 * Chaque cas vérifie AUSSI qu'un outil actif reste bien présent (jamais seulement l'absence de
 * l'archivée) - un filtre trop large qui viderait le comparateur serait pire que le défaut d'origine.
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Directory\Models\Category;
use Modules\Directory\Models\Tool;
use Modules\Directory\Services\ToolComparisonService;

uses(Tests\TestCase::class, RefreshDatabase::class);

/** Construction directe (pas de ToolFactory dans ce module - même convention que les autres tests Directory). */
function makeCompareArchivedTestTool(string $suffixe, array $overrides = []): Tool
{
    config(['app.locale' => 'fr_CA']);

    $tool = new Tool();
    $tool->url = 'https://compare-archived-test-'.$suffixe.'-'.uniqid().'.example';
    $tool->pricing = $overrides['pricing'] ?? 'free';
    $tool->status = $overrides['status'] ?? 'published';
    $tool->clicks_count = $overrides['clicks_count'] ?? 0;
    $tool->is_featured = false;
    if (! empty($overrides['lifecycle_status'])) {
        $tool->lifecycle_status = $overrides['lifecycle_status'];
    }
    $tool->setTranslation('name', 'fr_CA', $overrides['name'] ?? 'Outil comparateur '.$suffixe);
    $tool->setTranslation('slug', 'fr_CA', 'compare-archived-'.$suffixe.'-'.uniqid());
    $tool->setTranslation('description', 'fr_CA', 'Description de test.');
    $tool->setTranslation('short_description', 'fr_CA', 'Résumé de test suffisamment long pour ne pas être considéré comme mince.');
    $tool->save();

    return $tool;
}

function makeCompareArchivedTestCategory(string $suffixe): Category
{
    $slug = 'cmp-arch-'.$suffixe.'-'.uniqid();

    Category::create([
        'name' => ['fr_CA' => 'Catégorie comparateur '.$suffixe],
        'slug' => ['fr_CA' => $slug],
        'sort_order' => 1,
    ]);

    return Category::where('slug->fr_CA', $slug)->firstOrFail();
}

// ────────────────────────── PORTE CONTRÔLEUR : directory.compare (par catégorie) ──────────────────────────

it('exclut une fiche archivée du classement par catégorie sans faire perdre sa place à un outil actif', function () {
    $categorie = makeCompareArchivedTestCategory('porte-controleur');

    // 7 outils ACTIFS publiés, clics décroissants : si le TOP 6 est correct, le 7e (100 clics)
    // est le seul actif légitimement exclu (MAX_TOOLS = 6).
    $actifs = [];
    foreach ([700, 600, 500, 400, 300, 200, 100] as $i => $clics) {
        $outil = makeCompareArchivedTestTool('ctrl-actif-'.$i, [
            'name' => 'Outil actif ctrl '.$i,
            'clicks_count' => $clics,
        ]);
        $outil->categories()->attach($categorie->id);
        $actifs[] = $outil;
    }

    // Fiche ARCHIVÉE avec le nombre de clics le PLUS ÉLEVÉ de toute la catégorie (999) : si elle
    // n'est pas écartée AVANT le classement par clics, elle occupe la 1re place du TOP 6 et évince
    // le 6e outil actif (200 clics) - même si loadTools() la filtre correctement en aval.
    $archive = makeCompareArchivedTestTool('ctrl-archive', [
        'name' => 'Outil archivé le plus cliqué',
        'clicks_count' => 999,
        'lifecycle_status' => 'archived',
    ]);
    $archive->categories()->attach($categorie->id);

    $slug = $categorie->getTranslation('slug', 'fr_CA');
    $response = $this->get(route('directory.compare', $slug));

    $response->assertOk();

    $tools = $response->viewData('tools');
    $ids = collect($tools->pluck('id')->all())->sort()->values()->all();

    // La fiche archivée, malgré son nombre de clics le plus élevé, n'apparaît JAMAIS dans les
    // identifiants retenus par le contrôleur.
    expect($ids)->not->toContain($archive->id);

    // Les 6 outils ACTIFS les plus cliqués sont tous présents - en particulier le 6e (200 clics),
    // qui serait évincé si l'archivée volait sa place dans le TOP 6 avant le filtre notArchived().
    $idsAttendus = collect($actifs)->take(6)->pluck('id')->sort()->values()->all();
    expect($tools)->toHaveCount(6);
    expect($ids)->toEqual($idsAttendus);
});

// ────────────────────────── PORTE SERVICE : directory.compare-by-ids (IDs explicites) ─────────────────────

it('exclut une fiche archivée du comparateur par identifiants explicites sans vider les outils actifs', function () {
    // Cette route ne passe JAMAIS par la branche catégorie du contrôleur (if (empty($ids) &&
    // $categorySlug) est faux ici) : seul le filtre de ToolComparisonService::loadTools() est exercé.
    $archive = makeCompareArchivedTestTool('svc-archive', [
        'name' => 'Outil archivé demandé explicitement',
        'lifecycle_status' => 'archived',
    ]);
    $actif1 = makeCompareArchivedTestTool('svc-actif-1', ['name' => 'Outil actif un']);
    $actif2 = makeCompareArchivedTestTool('svc-actif-2', ['name' => 'Outil actif deux']);

    $response = $this->get(route('directory.compare-by-ids', [
        'ids' => "{$archive->id},{$actif1->id},{$actif2->id}",
    ]));

    $response->assertOk();

    $tools = $response->viewData('tools');
    $ids = collect($tools->pluck('id')->all())->sort()->values()->all();

    expect($ids)->not->toContain($archive->id);
    expect($tools)->toHaveCount(2);
    expect($ids)->toEqual(collect([$actif1->id, $actif2->id])->sort()->values()->all());
});

it('ToolComparisonService::loadTools exclut directement une fiche archivée sans passer par le contrôleur', function () {
    $archive = makeCompareArchivedTestTool('direct-archive', ['lifecycle_status' => 'archived']);
    $actif = makeCompareArchivedTestTool('direct-actif');

    $service = new ToolComparisonService();
    $tools = $service->loadTools([$archive->id, $actif->id]);

    expect($tools->pluck('id')->all())->toEqual([$actif->id]);
});

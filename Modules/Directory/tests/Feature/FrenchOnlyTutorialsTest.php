<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Décision du fondateur, 2026-09-15 (#2575) : sur 1687 tutoriels approuvés de l'annuaire, 1047
 * étaient en anglais, sur un site québécois francophone. L'anglais devient un REPLI, pas un
 * complément. Le tri existant plaçait déjà le français en tête ; il ne suffisait pas, puisque
 * l'anglais restait affiché juste en dessous.
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Directory\Models\Tool;
use Modules\Directory\Models\ToolResource;

uses(Tests\TestCase::class, RefreshDatabase::class);

function frOutil(): Tool
{
    $tool = new Tool;
    $tool->url = 'https://fr-test-'.uniqid().'.example';
    $tool->pricing = 'free';
    $tool->status = 'published';
    $tool->is_featured = false;
    $tool->setTranslation('name', 'fr_CA', 'Outil Test FR');
    $tool->setTranslation('slug', 'fr_CA', 'fr-test-'.uniqid());
    $tool->setTranslation('description', 'fr_CA', 'Description de test.');
    $tool->setTranslation('short_description', 'fr_CA', 'Résumé de test.');
    $tool->save();

    return $tool;
}

function frRessource(int $toolId, string $titre, string $langue): ToolResource
{
    return ToolResource::create([
        'directory_tool_id' => $toolId,
        'user_id' => null,
        'url' => 'https://www.youtube.com/watch?v='.substr(md5($titre), 0, 11),
        'title' => $titre,
        'type' => 'youtube',
        'language' => $langue,
        'video_id' => substr(md5($titre), 0, 11),
        'is_approved' => true,
    ]);
}

// LE test qui porte la décision : un lecteur québécois ne doit pas se voir proposer de l'anglais
// quand du français existe pour ce même outil.
it("ne garde que le français quand l'outil en a", function () {
    $tool = frOutil();
    $resources = collect([
        frRessource($tool->id, 'Tutoriel français 1', 'fr'),
        frRessource($tool->id, 'Tutoriel français 2', 'fr'),
        frRessource($tool->id, 'Tutoriel anglais 1', 'en'),
        frRessource($tool->id, 'Tutoriel anglais 2', 'en'),
        frRessource($tool->id, 'Tutoriel anglais 3', 'en'),
    ]);

    $result = ToolResource::frenchOnlyOrFallback($resources);

    expect($result)->toBeInstanceOf(Collection::class)->toHaveCount(2);
    expect($result->pluck('language')->all())->toBe(['fr', 'fr']);
});

// Rien ne vaut moins pour le lecteur qu'un tutoriel en anglais : là où le français manque,
// l'anglais reste, plutôt qu'une fiche sans aucune ressource.
it("sert l'anglais en repli quand aucun tutoriel français n'existe", function () {
    $tool = frOutil();
    $resources = collect([
        frRessource($tool->id, 'Tutoriel anglais 1', 'en'),
        frRessource($tool->id, 'Tutoriel anglais 2', 'en'),
        frRessource($tool->id, 'Tutoriel anglais 3', 'en'),
    ]);

    $result = ToolResource::frenchOnlyOrFallback($resources);

    expect($result)->toHaveCount(3)->toBe($resources);
});

// La plupart des 2279 fiches n'ont aucun tutoriel : le cas vide est le cas courant, pas le cas rare.
it('retourne une collection vide sans erreur', function () {
    $resources = collect();

    $result = ToolResource::frenchOnlyOrFallback($resources);

    expect($result)->toBeEmpty()->toBe($resources);
});

// Sans réindexation, la vue Blade recevrait des clés à trous et la boucle d'affichage sauterait.
it('réindexe les clés après filtrage', function () {
    $tool = frOutil();
    $resources = collect([
        frRessource($tool->id, 'Tutoriel anglais 1', 'en'),
        frRessource($tool->id, 'Tutoriel français 1', 'fr'),
        frRessource($tool->id, 'Tutoriel anglais 2', 'en'),
        frRessource($tool->id, 'Tutoriel français 2', 'fr'),
    ]);

    $result = ToolResource::frenchOnlyOrFallback($resources);

    expect($result->keys()->all())->toBe([0, 1]);
});

// La méthode s'exécute sur chaque affichage de fiche : une requête cachée ici coûterait 2279 fois.
it('ne fait aucune requête en base', function () {
    $tool = frOutil();
    $resources = collect([
        frRessource($tool->id, 'Tutoriel anglais 1', 'en'),
        frRessource($tool->id, 'Tutoriel français 1', 'fr'),
    ]);

    DB::enableQueryLog();
    DB::flushQueryLog();

    try {
        ToolResource::frenchOnlyOrFallback($resources);

        expect(DB::getQueryLog())->toBeEmpty();
    } finally {
        DB::disableQueryLog();
    }
});

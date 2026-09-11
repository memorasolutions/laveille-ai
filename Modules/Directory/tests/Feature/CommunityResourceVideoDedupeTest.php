<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Ticket #2436, volet « clés de dédoublonnage » (2026-09-11) - défaut (c) corrigé :
 * CommunityController::storeResource() dédoublonnait sur (directory_tool_id, url) EXACTE.
 * Deux URL différentes désignant la même vidéo YouTube (ex. youtube.com/watch?v=X et
 * youtu.be/X) passaient donc toutes les deux pour le même outil. La clé de dédoublonnage
 * devient le video_id, RE-EXTRAIT côté serveur avec l'extracteur déjà présent
 * (Modules/AI/Services/YouTubeService::getVideoId()) plutôt qu'un nouvel extracteur (DRY).
 *
 * Couvre aussi l'architecture retenue et figée (deux fins séparées derrière la même clé) :
 *   1. VACCIN (portée GLOBALE) : une vidéo désapprouvée (is_approved=false) reste bloquée
 *      pour TOUS les outils.
 *   2. DOUBLON (portée LOCALE) : sinon, refuse seulement si CET outil a déjà cette vidéo -
 *      la même vidéo reste permise pour un AUTRE outil (non-régression, pas de sur-blocage).
 * Et la non-régression du repli URL pour les ressources SANS video_id (article/documentation).
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Directory\Models\Tool;
use Modules\Directory\Models\ToolResource;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    config(['app.locale' => 'fr_CA']);
    $this->seed(\Modules\RolesPermissions\Database\Seeders\RolesAndPermissionsSeeder::class);
});

/** Construction directe (pas de ToolFactory dans ce module - même convention que les tests voisins). */
function makeDedupeTestTool(string $slugSuffix): Tool
{
    $tool = new Tool();
    $tool->url = 'https://dedupe-test-'.$slugSuffix.'.example';
    $tool->pricing = 'free';
    $tool->status = 'published';
    $tool->is_featured = false;
    $tool->setTranslation('name', 'fr_CA', 'Outil Dédoublonnage Test '.$slugSuffix);
    $tool->setTranslation('slug', 'fr_CA', 'dedupe-test-'.$slugSuffix.'-'.uniqid());
    $tool->setTranslation('description', 'fr_CA', 'Description de test.');
    $tool->setTranslation('short_description', 'fr_CA', 'Résumé de test.');
    $tool->save();

    return $tool;
}

test('1. deux URL différentes pour la MÊME vidéo (même outil) : la 2e est refusée (défaut (c) corrigé)', function () {
    $tool = makeDedupeTestTool('c1');
    $user = User::factory()->create();
    $slug = $tool->getTranslation('slug', 'fr_CA');

    $first = $this->actingAs($user)->postJson(route('directory.resources.store', $slug), [
        'url' => 'https://www.youtube.com/watch?v=aaaAAAaaaAA',
        'title' => 'Première adresse',
        'type' => 'video',
        'language' => 'fr',
        'video_id' => 'aaaAAAaaaAA',
    ]);
    $first->assertOk();
    expect(ToolResource::where('directory_tool_id', $tool->id)->count())->toBe(1);

    // Même vidéo, adresse youtu.be différente, et volontairement SANS video_id client (cas
    // réel : un lien collé sans que le JS ait appelé fetchYoutubeMeta) - le serveur doit
    // ré-extraire lui-même le video_id depuis l'URL pour détecter le doublon.
    $second = $this->actingAs($user)->postJson(route('directory.resources.store', $slug), [
        'url' => 'https://youtu.be/aaaAAAaaaAA',
        'title' => 'Deuxième adresse, même vidéo',
        'type' => 'video',
        'language' => 'fr',
    ]);

    $second->assertStatus(422);
    expect(ToolResource::where('directory_tool_id', $tool->id)->count())->toBe(1);
});

test('2. le doublon est LOCAL : la MÊME vidéo reste permise pour un AUTRE outil (pas de sur-blocage)', function () {
    $toolA = makeDedupeTestTool('c2a');
    $toolB = makeDedupeTestTool('c2b');
    $user = User::factory()->create();

    $this->actingAs($user)->postJson(route('directory.resources.store', $toolA->getTranslation('slug', 'fr_CA')), [
        'url' => 'https://www.youtube.com/watch?v=bbbBBBbbbBB',
        'title' => 'Vidéo partagée - outil A',
        'type' => 'video',
        'language' => 'fr',
        'video_id' => 'bbbBBBbbbBB',
    ])->assertOk();

    $second = $this->actingAs($user)->postJson(route('directory.resources.store', $toolB->getTranslation('slug', 'fr_CA')), [
        'url' => 'https://www.youtube.com/watch?v=bbbBBBbbbBB',
        'title' => 'Vidéo partagée - outil B',
        'type' => 'video',
        'language' => 'fr',
        'video_id' => 'bbbBBBbbbBB',
    ]);

    $second->assertOk();
    expect(ToolResource::where('video_id', 'bbbBBBbbbBB')->count())->toBe(2);
});

test('3. VACCIN : une vidéo désapprouvée par la modération reste bloquée pour TOUS les outils', function () {
    $tool = makeDedupeTestTool('c3');
    $otherTool = makeDedupeTestTool('c3b');
    $user = User::factory()->create();

    // Vidéo déjà en base, désapprouvée par la modération (ex. ModerateTutorialsCommand ou
    // ModerationController) pour un AUTRE outil - simule l'état laissé par une désapprobation.
    ToolResource::create([
        'directory_tool_id' => $otherTool->id,
        'user_id' => null,
        'url' => 'https://www.youtube.com/watch?v=cccCCCcccCC',
        'title' => 'Vidéo rejetée par un modérateur',
        'type' => 'youtube',
        'language' => 'fr',
        'video_id' => 'cccCCCcccCC',
        'is_approved' => false,
    ]);

    $response = $this->actingAs($user)->postJson(route('directory.resources.store', $tool->getTranslation('slug', 'fr_CA')), [
        'url' => 'https://youtu.be/cccCCCcccCC',
        'title' => 'Même vidéo, autre outil, autre URL',
        'type' => 'video',
        'language' => 'fr',
    ]);

    $response->assertStatus(422);
    expect(ToolResource::where('directory_tool_id', $tool->id)->count())->toBe(0);
});

test('4. ressources NON vidéo (sans video_id) : le repli sur URL reste inchangé (non-régression)', function () {
    $tool = makeDedupeTestTool('c4');
    $user = User::factory()->create();
    $slug = $tool->getTranslation('slug', 'fr_CA');

    $this->actingAs($user)->postJson(route('directory.resources.store', $slug), [
        'url' => 'https://blog.example/article-1',
        'title' => 'Article un',
        'type' => 'article',
        'language' => 'fr',
    ])->assertOk();

    // URL différente => acceptée (deux articles distincts)
    $this->actingAs($user)->postJson(route('directory.resources.store', $slug), [
        'url' => 'https://blog.example/article-2',
        'title' => 'Article deux',
        'type' => 'article',
        'language' => 'fr',
    ])->assertOk();

    // Même URL exacte que le premier => refusée (comportement historique inchangé)
    $again = $this->actingAs($user)->postJson(route('directory.resources.store', $slug), [
        'url' => 'https://blog.example/article-1',
        'title' => 'Article un (2e envoi)',
        'type' => 'article',
        'language' => 'fr',
    ]);
    $again->assertStatus(422);

    expect(ToolResource::where('directory_tool_id', $tool->id)->count())->toBe(2);
});

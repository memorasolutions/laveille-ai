<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Audit AdSense 2026-08-20 (« contenu de faible valeur ») : noindex conditionnel sur 3 des 5
 * corrections de la spec — profils membres minces (/membre/{id}), fiches annuaire minces
 * (/annuaire/{slug}), variante sans outils du comparateur (/annuaire/comparer). PAS de
 * délégation ici, ces tests ne sont PAS exécutés par ce sous-agent (contrainte projet - le
 * superviseur lance la suite une seule fois, en série).
 *
 * Piège #noindex-en-dur-corrige-2026-08-17 (Modules/Books) : master.blade.php teste
 * View::hasSection('page_noindex'), pas sa valeur. La section ne doit donc être déclarée que
 * derrière un @if - jamais @section('page_noindex', $bool) en direct. Les 3 vues touchées ici
 * respectent ce garde-fou ; ces tests le vérifient sur le rendu réel plutôt que sur l'état des
 * sections (non fiable après une requête de test, cf. commentaire BooksPublicLaunchTest.php).
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Directory\Models\Tool;
use Tests\Concerns\RegistersMysqlSqliteCompatFunctions;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);
uses(RegistersMysqlSqliteCompatFunctions::class);

// Environnement de test = sqlite :memory: (phpunit.xml), qui n'a pas la fonction MySQL FIELD()
// utilisée par PublicDirectoryController::show() pour trier les ressources. Polyfill centralisé
// (DRY), même mécanisme que Modules/Directory/tests/Feature/AffiliateLinkTest.php.
beforeEach(function () {
    config()->set('app.noindex', false);
    $this->registerMysqlSqliteCompatFunctions();
});

function makeNoindexTestTool(string $slug, array $overrides = []): Tool
{
    config(['app.locale' => 'fr_CA']);

    $tool = new Tool();
    $tool->setTranslation('name', 'fr_CA', 'Outil Test Noindex '.$slug);
    $tool->setTranslation('slug', 'fr_CA', $slug);
    $tool->setTranslation('description', 'fr_CA', $overrides['description'] ?? 'Description de test.');
    $tool->setTranslation('short_description', 'fr_CA', $overrides['short_description'] ?? '');
    $tool->url = 'https://exemple-'.$slug.'.test';
    $tool->pricing = 'free';
    $tool->status = 'published';
    $tool->save();
    $tool->refresh();

    return $tool;
}

// ── 1. Profils membres /membre/{id} ─────────────────────────────────────────

test('noindex sur un profil membre sans avis/discussion/ressource', function () {
    $user = User::factory()->create();

    $response = $this->get(route('directory.profile', $user->id));

    $response->assertOk();
    $response->assertSee('noindex, follow', false);
});

test('pas de noindex sur un profil membre avec au moins un avis approuvé', function () {
    $user = User::factory()->create();
    $tool = makeNoindexTestTool('outil-pour-avis-profil', ['short_description' => 'Un résumé bien assez long pour être substantiel.']);

    DB::table('directory_reviews')->insert([
        'directory_tool_id' => $tool->id,
        'user_id' => $user->id,
        'rating' => 5,
        'title' => 'Très bon outil, je recommande.',
        'is_approved' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->get(route('directory.profile', $user->id));

    $response->assertOk();
    $response->assertDontSee('noindex', false);
});

// ── 2. Fiches annuaire /annuaire/{slug} ─────────────────────────────────────

test('noindex sur une fiche annuaire mince (sans catégorie, sans avis/tutoriel/screenshot, description courte)', function () {
    $tool = makeNoindexTestTool('outil-mince', ['short_description' => 'Trop court.']);

    $response = $this->get(route('directory.show', $tool->slug));

    $response->assertOk();
    $response->assertSee('noindex, follow', false);
});

test('pas de noindex sur une fiche annuaire avec une catégorie assignée', function () {
    $tool = makeNoindexTestTool('outil-avec-categorie', ['short_description' => 'Trop court.']);
    $category = \Modules\Directory\Models\Category::create([
        'name' => ['fr_CA' => 'Catégorie test'],
        'slug' => ['fr_CA' => 'categorie-test'],
        'sort_order' => 1,
    ]);
    $tool->categories()->attach($category->id);

    $response = $this->get(route('directory.show', $tool->slug));

    $response->assertOk();
    $response->assertDontSee('noindex', false);
});

test('pas de noindex sur une fiche annuaire avec une description en bref substantielle', function () {
    $tool = makeNoindexTestTool('outil-desc-substantielle', [
        'short_description' => 'Un résumé suffisamment long et informatif pour ne pas être considéré comme mince.',
    ]);

    $response = $this->get(route('directory.show', $tool->slug));

    $response->assertOk();
    $response->assertDontSee('noindex', false);
});

// ── 3. Comparateur /annuaire/comparer ───────────────────────────────────────

test('noindex sur le comparateur sans outils sélectionnés', function () {
    $response = $this->get(route('directory.compare-by-ids'));

    $response->assertOk();
    $response->assertSee('noindex, follow', false);
});

test('pas de noindex sur le comparateur avec au moins 2 outils sélectionnés', function () {
    $tool1 = makeNoindexTestTool('outil-cmp-1');
    $tool2 = makeNoindexTestTool('outil-cmp-2');

    $response = $this->get(route('directory.compare-by-ids', ['ids' => $tool1->id.','.$tool2->id]));

    $response->assertOk();
    $response->assertDontSee('noindex', false);
});

test('pas de noindex sur le comparateur par catégorie même avec moins de 2 outils', function () {
    $category = \Modules\Directory\Models\Category::create([
        'name' => ['fr_CA' => 'Catégorie comparateur'],
        'slug' => ['fr_CA' => 'categorie-comparateur'],
        'sort_order' => 1,
    ]);
    $tool = makeNoindexTestTool('outil-cmp-categorie');
    $tool->categories()->attach($category->id);

    $response = $this->get(route('directory.compare', 'categorie-comparateur'));

    $response->assertOk();
    $response->assertDontSee('noindex', false);
});

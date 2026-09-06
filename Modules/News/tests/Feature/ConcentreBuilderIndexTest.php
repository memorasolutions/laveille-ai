<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Tests Pest — rendu HTTP de /admin/concentre-builder (jamais couvert avant : ConcentrePromptBuilderTest
 * ne teste que le service PHP pur, VideoGoalBuilderTest ne teste que l'autre page). Trouvé lors de la
 * passe adversariale /100 du 2026-07-21 sur le bouton "Envoyer vers Objectif vidéo" (commit 0d458bd6) :
 * un typo dans route('admin.news.video-goal.index') aurait fait planter cette page en 500 sans qu'aucun
 * test ne le détecte.
 */

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

function cbiAdmin(): \App\Models\User
{
    $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $user = \App\Models\User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole($role);

    return $user;
}

it('redirects guest to login', function () {
    $this->get(route('admin.concentre.index'))->assertRedirect(route('login'));
});

it('renders the concentre builder page for an admin without error', function () {
    $admin = cbiAdmin();

    $response = $this->actingAs($admin)->get(route('admin.concentre.index'));

    $response->assertOk();
    $response->assertSee('concentreBuilder(', false);
    $response->assertSee('pushToVideoGoal', false);
    // route('admin.news.video-goal.index') doit résoudre sans exception (sinon 500 avant assertOk()) ;
    // @js() encode en JSON, les slashes sont donc échappés (\/) dans le HTML rendu.
    $response->assertSee('admin\/objectif-video', false);
});

// ── Ordre de chargement du mixin partagé (ticket #2210, 2026-09-05) ────────────
// La PRÉSENCE de news-article-picker.js et de l'appel concentreBuilder(...) dans le HTML ne prouve
// rien : les deux étaient déjà présents AVANT le correctif, et l'écran cascadait quand même en
// ReferenceError (le script chargeait APRÈS que Livewire ait déjà démarré Alpine et évalué
// x-data). La preuve porte sur la POSITION RELATIVE dans le HTML rendu : le script doit arriver
// avant le code qui s'en sert ET avant le script Livewire qui démarre Alpine
// (@livewireScripts, Modules/Backoffice/.../layouts/admin.blade.php:180).
it('news-article-picker.js charge avant l\'appel concentreBuilder(...) et avant le script Livewire qui démarre Alpine', function () {
    $admin = cbiAdmin();

    $html = $this->actingAs($admin)->get(route('admin.concentre.index'))->getContent();

    $scriptPos = strpos($html, 'news-article-picker.js');
    $factoryCallPos = strpos($html, 'concentreBuilder(');
    $livewireBootPos = strpos($html, 'livewire.js');

    expect($scriptPos)->not->toBeFalse('news-article-picker.js absent du HTML rendu');
    expect($factoryCallPos)->not->toBeFalse('appel concentreBuilder(...) absent du HTML rendu');
    expect($livewireBootPos)->not->toBeFalse('script livewire.js absent du HTML rendu');

    expect($scriptPos)->toBeLessThan($factoryCallPos);
    expect($scriptPos)->toBeLessThan($livewireBootPos);
});

// ── Non-régression du piège trouvé pendant l'implémentation (ticket #2210) ─────
// La première version du correctif écrivait le mot "@assets" dans un commentaire JAVASCRIPT
// (// ...) à l'intérieur d'un <script> : Blade ne distingue pas un commentaire JS d'un commentaire
// Blade ({{-- --}}) et compile @assets où qu'il apparaisse hors {{-- --}}, ce qui a posé un
// ob_start() jamais fermé (PHPUnit : "did not close its own output buffers"). Sans ce test, la
// prochaine personne qui écrit "@assets" dans un commentaire JS refait exactement la même erreur
// sans que rien ne l'arrête.
it('ne laisse aucun buffer de sortie ouvert après le rendu du concentré', function () {
    $admin = cbiAdmin();

    $obLevelBefore = ob_get_level();
    $response = $this->actingAs($admin)->get(route('admin.concentre.index'));
    $obLevelAfter = ob_get_level();

    $response->assertOk();
    expect($obLevelAfter)->toBe($obLevelBefore);
});

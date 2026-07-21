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

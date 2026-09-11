<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Preuve du branchement du bouton "Nouvel article long" du tableau de bord auteur sur
 * l'éditeur AuthorEditor (route authors.editor), et disparition des notes de développeur
 * qui étaient affichées au visiteur sur les onglets Articles et Curation.
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Modules\Authors\Livewire\AuthorDashboard;
use Modules\Authors\Livewire\AuthorEditor;
use Modules\Authors\Models\AuthorProfile;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makeAuthorProfileForDashboardLink(): AuthorProfile
{
    $user = User::factory()->create();

    return AuthorProfile::create([
        'user_id' => $user->id,
        'slug' => 'dashlink-'.strtolower(Str::random(6)),
        'display_name' => 'Auteur Tableau de bord',
        'tier' => 'free',
    ]);
}

it('le tableau de bord propose un vrai lien vers l\'éditeur d\'article', function () {
    $author = makeAuthorProfileForDashboardLink();

    $this->actingAs($author->user)
        ->get('/auteur/dashboard')
        ->assertOk()
        ->assertSee(route('authors.editor'), false);
});

it('un auteur connecté atteint l\'éditeur depuis son tableau de bord', function () {
    $author = makeAuthorProfileForDashboardLink();

    $this->actingAs($author->user)
        ->get('/auteur/editeur')
        ->assertOk()
        ->assertSee("Titre de l'article", false);
});

it('un invité est redirigé loin de l\'éditeur', function () {
    $this->get('/auteur/editeur')->assertRedirect();
});

it('un utilisateur connecté sans profil auteur n\'atteint pas l\'éditeur', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/auteur/editeur')
        ->assertForbidden();
});

it('un auteur ne peut pas ouvrir l\'éditeur d\'un autre profil auteur', function () {
    $owner = makeAuthorProfileForDashboardLink();
    $intruder = makeAuthorProfileForDashboardLink();

    // Le composant applique lui-même la vérification de propriété (abort_if dans
    // AuthorEditor::mount) : même en construisant directement le composant avec le
    // profil d'un autre auteur, l'accès doit être refusé pour l'utilisateur connecté.
    $this->actingAs($intruder->user);

    Livewire::test(AuthorEditor::class, ['authorProfile' => $owner])
        ->assertForbidden();
});

it('n\'affiche plus la note de développeur sur l\'onglet Articles', function () {
    $author = makeAuthorProfileForDashboardLink();
    $this->actingAs($author->user);

    Livewire::test(AuthorDashboard::class, ['authorProfileId' => $author->id])
        ->call('switchTab', 'articles')
        ->assertDontSee("Article::where('user_id'")
        ->assertSee('Nouvel article long');
});

it('n\'affiche plus la note de développeur sur l\'onglet Curation', function () {
    $author = makeAuthorProfileForDashboardLink();
    $this->actingAs($author->user);

    Livewire::test(AuthorDashboard::class, ['authorProfileId' => $author->id])
        ->call('switchTab', 'curation')
        ->assertDontSee('CurationInbox')
        ->assertDontSee('Phase 2');
});

it('le bouton statut court reste inerte et honnête tant que rien ne le supporte', function () {
    $author = makeAuthorProfileForDashboardLink();
    $this->actingAs($author->user);

    Livewire::test(AuthorDashboard::class, ['authorProfileId' => $author->id])
        ->assertSeeHtml('disabled')
        ->assertSee('bientôt disponible');
});

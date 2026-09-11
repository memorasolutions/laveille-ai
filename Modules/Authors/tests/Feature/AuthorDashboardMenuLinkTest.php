<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Régression : la route authors.dashboard (/auteur/dashboard) existait et fonctionnait mais
 * aucun lien de l'interface n'y menait - un auteur connecté ne pouvait la trouver qu'en tapant
 * l'adresse. Corrigé en ajoutant une entrée « Mon espace auteur » dans le menu utilisateur
 * partagé (Modules/Auth/resources/views/components/user-menu-links.blade.php), visible
 * uniquement pour un utilisateur qui a un profil d'auteur (User::isAuthor(), délégué au trait
 * Modules\Authors\Traits\HasAuthorProfile). La page /dashboard (route user.dashboard) rend à la
 * fois le dropdown du header (variant=dropdown) et la barre latérale (variant=sidebar), les deux
 * consommant le même composant - vérifier son HTML couvre les deux zones vivantes d'un coup.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Authors\Models\AuthorProfile;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('un utilisateur avec un profil d\'auteur voit le lien Mon espace auteur vers /auteur/dashboard', function () {
    $user = User::factory()->create();
    AuthorProfile::create([
        'user_id' => $user->id,
        'slug' => 'auteur-menu-'.strtolower(Str::random(8)),
        'tier' => 'free',
    ]);

    $response = $this->actingAs($user)->get(route('user.dashboard'));

    $response->assertOk();
    $response->assertSee(route('authors.dashboard'), false);
    $response->assertSee('Mon espace auteur');
});

it('un utilisateur sans profil d\'auteur ne voit jamais le lien Mon espace auteur ni /auteur/dashboard', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('user.dashboard'));

    $response->assertOk();
    $response->assertDontSee(route('authors.dashboard'), false);
    $response->assertDontSee('Mon espace auteur');
});

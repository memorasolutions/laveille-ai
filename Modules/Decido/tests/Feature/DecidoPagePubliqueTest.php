<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - page PUBLIQUE de présentation de Décido (/decido pour un visiteur non connecté).
 *
 * Mesuré le 2026-09-15 : /decido et /decido/creer répondaient TOUS DEUX 302 vers /login. Un
 * visiteur ne découvrait jamais l'outil. Ces tests figent le nouveau comportement ET la frontière
 * qui ne doit pas bouger : la CRÉATION reste protégée.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Decido\Models\Poll;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

    $this->superadmin = User::factory()->create([
        'email' => config('app.superadmin_email'),
    ]);
    $this->superadmin->assignRole('super_admin');

    config()->set('decido.under_construction', true);
});

test('un visiteur non connecté reçoit la page de présentation, pas une redirection vers la connexion', function (): void {
    // Si ce test tombe, l'outil redevient inannonçable : toute campagne sociale enverrait les gens
    // sur un formulaire de connexion qui ne dit pas à quoi sert Décido.
    config()->set('decido.under_construction', false);

    $this->get(route('decido.index'))
        ->assertStatus(200)
        ->assertSee('Sondage de dates', false)
        ->assertSee('Sondage classique', false);
});

test('un utilisateur connecté garde son tableau de bord et ne voit pas la page de présentation', function (): void {
    // Si ce test tombe, les membres perdent l'accès direct à la liste de leurs sondages et se
    // retrouvent devant une page de vitrine, ce qui est une régression fonctionnelle pure.
    config()->set('decido.under_construction', false);

    $user = User::factory()->create();

    $this->actingAs($user)->get(route('decido.index'))
        ->assertStatus(200)
        ->assertDontSee('Pourquoi créer un compte pour lancer un sondage', false);
});

test('le mode en construction prime encore sur la page publique', function (): void {
    // Contrôle négatif : prouve que le premier test ne passe pas trivialement. Sans lui, une page
    // publique servie malgré le mode construction exposerait une section inachevée aux visiteurs.
    $this->get(route('decido.index'))
        ->assertStatus(503);
});

test('la création de sondage reste protégée par la connexion', function (): void {
    // La frontière de sécurité. Si ce test tombe, n'importe qui peut créer un sondage anonyme sur
    // le domaine : plus aucune adresse pour l'avertissement d'expiration à 14 jours, aucun
    // responsable identifiable des renseignements collectés, et un vecteur d'hameçonnage ouvert.
    config()->set('decido.under_construction', false);

    $this->get(route('decido.create'))
        ->assertRedirect();
});

test('la page de présentation est indexable, contrairement aux pages de vote', function (): void {
    // Volontaire : c'est une page d'acquisition, elle doit être trouvable par la recherche. Les
    // pages de VOTE, elles, restent noindex parce qu'elles exposent pseudonymes et choix de vote.
    //
    // PIÈGE, mesuré le 2026-09-15 : une première version de ce test cherchait simplement l'absence
    // du mot « noindex » dans le HTML, et ÉCHOUAIT. Ce n'était pas un défaut de la page - en
    // environnement de test, config('app.noindex') met TOUT le site en noindex, donc le contrôle
    // mesurait la configuration d'environnement au lieu de la page. On neutralise ce réglage global
    // pour observer ce que la page décide RÉELLEMENT, et on vérifie la balise POSITIVE plutôt
    // qu'une absence, qui serait vraie pour de mauvaises raisons.
    config()->set('decido.under_construction', false);
    config()->set('app.noindex', false);

    $contenu = $this->get(route('decido.index'))->getContent();

    expect($contenu)->toContain('content="index, follow');
    expect($contenu)->not->toContain('content="noindex');
});

test('la page de VOTE reste noindex, elle', function (): void {
    // Contrôle de frontière : prouve que le test précédent ne décrit pas un site entièrement
    // indexable. Une page de vote indexée exposerait les pseudonymes et les choix des personnes
    // dans les résultats de recherche.
    config()->set('decido.under_construction', false);
    config()->set('app.noindex', false);

    $poll = Poll::factory()->create(['status' => 'open']);

    $contenu = $this->get(route('decido.vote.show', ['slug' => $poll->public_id]))->getContent();

    expect($contenu)->toContain('content="noindex');
});

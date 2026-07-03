<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests de non-régression IDOR (Insecure Direct Object Reference) — Modules/Api.
 *
 * Contexte : audit sécurité ayant classé Modules/Api « INCERTAIN » car les endpoints
 * acceptent des IDs bruts dans l'URL sous auth:sanctum, sans confirmation formelle que
 * la propriété de la ressource par l'utilisateur authentifié était vérifiée.
 *
 * Conclusion de la revue de code : AUCUN IDOR réel n'a été trouvé.
 *   - NotificationApiController : scope systématique via $request->user()->notifications().
 *   - ArticleApiController      : Policy ArticlePolicy::update/delete vérifie
 *                                 $user->id === $article->user_id (ou permission explicite).
 *   - UserController            : Policy UserPolicy::view/update/delete vérifie
 *                                 $user->id === $model->id (ou permission explicite).
 *
 * Ces tests figent ce comportement : un utilisateur A sans permission ne peut PAS
 * lire/modifier/supprimer une ressource appartenant à l'utilisateur B via l'API.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Modules\Blog\Models\Article;

uses(Tests\TestCase::class, RefreshDatabase::class);

// --- Notifications ---------------------------------------------------------

it('empêche un utilisateur de marquer comme lue la notification d\'un autre utilisateur', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $notification = DatabaseNotification::create([
        'id' => (string) Illuminate\Support\Str::uuid(),
        'type' => 'App\\Notifications\\TestNotification',
        'notifiable_type' => User::class,
        'notifiable_id' => $userB->id,
        'data' => ['message' => 'secret de userB'],
    ]);

    $response = $this->actingAs($userA)
        ->postJson("/api/v1/notifications/{$notification->id}/read");

    // findOrFail() scopé à userA->notifications() ne trouve rien -> 404, jamais 200.
    $response->assertStatus(404);

    expect($notification->fresh()->read_at)->toBeNull();
});

it('empêche un utilisateur de supprimer la notification d\'un autre utilisateur', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $notification = DatabaseNotification::create([
        'id' => (string) Illuminate\Support\Str::uuid(),
        'type' => 'App\\Notifications\\TestNotification',
        'notifiable_type' => User::class,
        'notifiable_id' => $userB->id,
        'data' => ['message' => 'secret de userB'],
    ]);

    $response = $this->actingAs($userA)
        ->deleteJson("/api/v1/notifications/{$notification->id}");

    $response->assertStatus(404);

    expect(DatabaseNotification::find($notification->id))->not->toBeNull();
});

// --- Articles ---------------------------------------------------------------

it('empêche un utilisateur sans permission de modifier l\'article d\'un autre utilisateur', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create(); // rôle "user" par défaut, aucune permission update_articles

    $article = Article::factory()->for($owner)->create(['title' => 'Titre original']);

    $response = $this->actingAs($attacker)
        ->putJson("/api/v1/articles/{$article->id}", ['title' => 'Piraté par attacker']);

    $response->assertStatus(403);

    expect($article->fresh()->title)->toBe('Titre original');
});

it('empêche un utilisateur sans permission de supprimer l\'article d\'un autre utilisateur', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();

    $article = Article::factory()->for($owner)->create();

    $response = $this->actingAs($attacker)
        ->deleteJson("/api/v1/articles/{$article->id}");

    $response->assertStatus(403);

    expect(Article::find($article->id))->not->toBeNull();
});

it('permet au propriétaire de modifier son propre article sans permission explicite', function () {
    $owner = User::factory()->create();

    $article = Article::factory()->for($owner)->create(['title' => 'Titre original']);

    $response = $this->actingAs($owner)
        ->putJson("/api/v1/articles/{$article->id}", ['title' => 'Mis à jour par le proprio']);

    $response->assertStatus(200);

    expect($article->fresh()->title)->toBe('Mis à jour par le proprio');
});

// --- Users --------------------------------------------------------------------

it('empêche un utilisateur sans permission de consulter le profil d\'un autre utilisateur', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $response = $this->actingAs($userA)->getJson("/api/v1/users/{$userB->id}");

    $response->assertStatus(403);
});

it('empêche un utilisateur sans permission de modifier un autre utilisateur', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create(['name' => 'Bob original']);

    $response = $this->actingAs($userA)
        ->putJson("/api/v1/users/{$userB->id}", ['name' => 'Piraté par userA']);

    $response->assertStatus(403);

    expect($userB->fresh()->name)->toBe('Bob original');
});

it('permet à un utilisateur de consulter son propre profil via /api/v1/users/{id}', function () {
    $userA = User::factory()->create();

    $response = $this->actingAs($userA)->getJson("/api/v1/users/{$userA->id}");

    $response->assertStatus(200);
});

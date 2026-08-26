<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Sanctum\Sanctum;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

// Faille trouvee le 2026-08-26 dans `Modules/Api`, module jamais ouvert par les audits precedents.
//
// `ArticleApiController::store()` etait la SEULE action d'ecriture du controleur sans
// `$this->authorize()`, alors que `update()` (ligne 59) et `destroy()` (ligne 77) en ont une :
// l'incoherence dans le meme fichier signait l'oubli. `StoreArticleRequest::authorize()` retourne
// `true`, et la route n'est gardee que par `auth:sanctum`.
//
// Consequence : tout visiteur pouvait s'inscrire (inscription libre), s'emettre un jeton Sanctum
// depuis son propre tableau de bord, puis publier un article de blogue immediatement
// (`published_at` est pose automatiquement quand `status` vaut `published`) - en contournant
// entierement la permission `create_articles` qu'exige le back-office pour la meme action.

it('refuse la publication a un utilisateur sans la permission', function () {
    $ordinaire = User::factory()->create();
    Sanctum::actingAs($ordinaire);

    $reponse = $this->postJson('/api/v1/articles', [
        'title' => 'Article de controle audit',
        'content' => 'Contenu de controle.',
        'status' => 'published',
    ]);

    // 403 attendu : l'utilisateur est authentifie mais n'a pas `create_articles`.
    expect($reponse->status())->toBe(403);

    expect(\Modules\Blog\Models\Article::where('title', 'Article de controle audit')->exists())
        ->toBeFalse('Aucun article ne doit avoir ete cree.');
});

it('refuse aussi la creation d un simple brouillon', function () {
    Sanctum::actingAs(User::factory()->create());

    $reponse = $this->postJson('/api/v1/articles', [
        'title' => 'Brouillon de controle',
        'content' => 'Contenu.',
        'status' => 'draft',
    ]);

    expect($reponse->status())->toBe(403);
});

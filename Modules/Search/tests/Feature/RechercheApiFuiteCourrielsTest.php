<?php

declare(strict_types=1);

use App\Models\User;
use Modules\Search\Services\SearchService;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

// Faille trouvee le 2026-08-26 par la passe adversariale de l'audit, dans un module que deux
// audits successifs n'avaient jamais ouvert.
//
// L'API `GET /api/v1/search` n'est protegee que par `auth:sanctum` : AUCUNE permission. Or
// `config('search.models')` place `App\Models\User` en tete, `User::toSearchableArray()` retourne
// `['name' => ..., 'email' => ...]`, et `User` n'implemente pas `shouldBeSearchable()`. La methode
// `search()` de l'API ne filtre rien, contrairement a `searchFront()` qui applique bien ses scopes.
//
// Chaine complete, sans aucun privilege : inscription libre -> jeton Sanctum emis depuis son
// propre tableau de bord -> `?model=User` -> nom et courriel de tous les comptes. C'est une
// communication de renseignements personnels au sens de la Loi 25.
//
// Le correctif filtre l'ACCES et non l'index : desindexer User aurait casse la recherche
// legitime du back-office (searchAdmin, searchNavbar). Ces tests verrouillent les deux cotes.

it('ne laisse PAS un utilisateur ordinaire atteindre le modele User', function () {
    $service = app(SearchService::class);
    $ordinaire = User::factory()->create();

    $modeles = $service->getSearchableModelsFor($ordinaire);

    expect($modeles)->not->toContain(User::class);
});

it('ne laisse PAS un visiteur non authentifie atteindre le modele User', function () {
    $service = app(SearchService::class);

    expect($service->getSearchableModelsFor(null))->not->toContain(User::class);
});

// Non-regression : le back-office DOIT continuer a chercher des utilisateurs. Si ce test casse,
// le correctif de securite a ete applique au mauvais endroit (l'index au lieu de l'acces).
it('laisse le back-office chercher des utilisateurs', function () {
    $service = app(SearchService::class);

    $admin = Mockery::mock(User::factory()->create())->makePartial();
    $admin->shouldReceive('can')->with('view_admin_panel')->andReturn(true);

    expect($service->getSearchableModelsFor($admin))->toContain(User::class);
});

// Fail-closed : si l'evaluation des droits echoue, on protege la donnee plutot que de l'exposer.
it('protege la donnee quand l evaluation des droits echoue', function () {
    $service = app(SearchService::class);

    $casse = Mockery::mock(User::factory()->create())->makePartial();
    $casse->shouldReceive('can')->andThrow(new RuntimeException('permission indisponible'));

    expect($service->getSearchableModelsFor($casse))->not->toContain(User::class);
});

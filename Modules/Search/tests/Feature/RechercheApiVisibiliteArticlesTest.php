<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Blog\Models\Article;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Faille mesuree le 2026-09-25 : Modules/Search/app/Services/SearchService.php::search() et
// ::searchModel() appelaient $model::search($query) SANS le filtre de visibilite deja applique
// par searchFront() (scopePublished / is_published / status=published). Le moteur Scout
// "database" (config/scout.php) n'exclut PAS un brouillon ni un article planifie a une date
// future : DatabaseEngine::initializeSearchQuery() interroge la table entiere par LIKE, et
// Article::shouldBeSearchable() n'intervient jamais pour ce moteur. Consequence : GET
// /api/v1/search (protege par auth:sanctum seulement, aucune permission) rendait visible a TOUT
// membre authentifie le contenu d'un brouillon ou d'un article planifie, y compris son corps
// complet via ?model=Article (searchModel() serialise le modele entier).

beforeEach(function () {
    $this->seed(\Modules\RolesPermissions\Database\Seeders\RolesAndPermissionsSeeder::class);

    $this->brouillon = Article::factory()->draft()->create([
        'title' => 'RechVisibiliteZZZ Brouillon Confidentiel',
    ]);

    $this->planifie = Article::factory()->create([
        'status' => 'published',
        'published_at' => now()->addDays(3),
        'title' => 'RechVisibiliteZZZ Planifie Confidentiel',
    ]);

    $this->publie = Article::factory()->published()->create([
        'title' => 'RechVisibiliteZZZ Publie Public',
    ]);
});

it('ne montre pas un brouillon ni un article planifie a un membre ordinaire (recherche globale)', function () {
    Sanctum::actingAs(User::factory()->create());

    $reponse = $this->getJson('/api/v1/search?q=RechVisibiliteZZZ');

    $reponse->assertOk();

    $idsArticles = collect($reponse->json('data.Article', []))->pluck('id');

    expect($idsArticles)->not->toContain($this->brouillon->id);
    expect($idsArticles)->not->toContain($this->planifie->id);
    expect($idsArticles)->toContain($this->publie->id);
});

it('ne montre pas un brouillon ni un article planifie a un membre ordinaire (recherche filtree model=Article)', function () {
    Sanctum::actingAs(User::factory()->create());

    $reponse = $this->getJson('/api/v1/search?q=RechVisibiliteZZZ&model=Article');

    $reponse->assertOk();

    $idsArticles = collect($reponse->json('data.data', []))->pluck('id');

    expect($idsArticles)->not->toContain($this->brouillon->id);
    expect($idsArticles)->not->toContain($this->planifie->id);
    expect($idsArticles)->toContain($this->publie->id);
});

it('laisse un administrateur voir le brouillon et l article planifie (recherche globale)', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Sanctum::actingAs($admin);

    $reponse = $this->getJson('/api/v1/search?q=RechVisibiliteZZZ');

    $reponse->assertOk();

    $idsArticles = collect($reponse->json('data.Article', []))->pluck('id');

    expect($idsArticles)->toContain($this->brouillon->id);
    expect($idsArticles)->toContain($this->planifie->id);
    expect($idsArticles)->toContain($this->publie->id);
});

it('laisse un administrateur voir le brouillon et l article planifie (recherche filtree model=Article)', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Sanctum::actingAs($admin);

    $reponse = $this->getJson('/api/v1/search?q=RechVisibiliteZZZ&model=Article');

    $reponse->assertOk();

    $idsArticles = collect($reponse->json('data.data', []))->pluck('id');

    expect($idsArticles)->toContain($this->brouillon->id);
    expect($idsArticles)->toContain($this->planifie->id);
    expect($idsArticles)->toContain($this->publie->id);
});

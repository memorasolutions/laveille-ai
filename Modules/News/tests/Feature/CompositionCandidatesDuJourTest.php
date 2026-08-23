<?php

declare(strict_types=1);

/**
 * L'écran de composition ne montre que les actualités collectées DANS LA JOURNÉE, et traduit en
 * français les titres venus de sources non francophones (demande du fondateur, 2026-08-23).
 *
 * Deux propriétés sont verrouillées ici, et ce sont les deux qui peuvent nuire si elles lâchent :
 *  - le filtre porte sur `created_at` (collecte) et non sur `pub_date` (date annoncée par la
 *    source) : un article daté d'hier soir mais récolté ce matin DOIT rester visible ;
 *  - une traduction mal alignée est pire qu'une absence de traduction, donc tout écart de compte
 *    doit rendre TOUS les originaux, jamais un mélange.
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\Core\Services\TranslationService;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, RefreshDatabase::class);

function cdjAdmin(): \App\Models\User
{
    $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $user = \App\Models\User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole($role);

    return $user;
}

function cdjSource(string $langue): NewsSource
{
    return NewsSource::firstOrCreate(
        ['url' => "https://cdj-{$langue}.exemple.com/rss"],
        ['name' => "Source cdj {$langue}", 'language' => $langue, 'active' => true]
    );
}

function cdjArticle(string $langue = 'fr', array $overrides = []): NewsArticle
{
    static $i = 0;
    $i++;
    $suffix = $i.'-'.uniqid();

    return NewsArticle::create(array_merge([
        'news_source_id' => cdjSource($langue)->id,
        'title' => "Titre cdj {$i}",
        'guid' => "guid-cdj-{$suffix}",
        'url' => "https://exemple.com/cdj-{$suffix}",
        'description' => '',
        'summary' => "Résumé cdj {$i}",
        'slug' => "cdj-{$suffix}",
        'pub_date' => now()->subMinutes($i),
        'is_published' => false,
        'seo_status' => 'index',
    ], $overrides));
}

it('ne renvoie que les actualités collectées aujourd\'hui', function () {
    $dujour = cdjArticle();
    $hier = cdjArticle();
    $hier->forceFill(['created_at' => now()->subDay()])->saveQuietly();

    $admin = cdjAdmin();
    $reponse = $this->actingAs($admin)->getJson(route('admin.news.composition.candidates'));

    $reponse->assertOk();
    $ids = collect($reponse->json('items'))->pluck('id')->all();

    expect($ids)->toContain($dujour->id)
        ->and($ids)->not->toContain($hier->id);
});

it('garde un article daté d\'hier mais collecté aujourd\'hui', function () {
    // Le piège que le filtre sur pub_date aurait créé : une source qui date son article de la
    // veille au soir. Il a été récolté ce matin, il doit rester composable.
    $article = cdjArticle('fr', ['pub_date' => now()->subDay()->setTime(21, 30)]);

    $admin = cdjAdmin();
    $reponse = $this->actingAs($admin)->getJson(route('admin.news.composition.candidates'));

    expect(collect($reponse->json('items'))->pluck('id')->all())->toContain($article->id);
});

it('signale explicitement quand il affiche un jour de repli plutôt que la journée en cours', function () {
    $ancien = cdjArticle();
    $ancien->forceFill(['created_at' => now()->subDays(4)])->saveQuietly();

    $admin = cdjAdmin();
    $reponse = $this->actingAs($admin)->getJson(route('admin.news.composition.candidates'));

    $reponse->assertOk()
        ->assertJsonPath('est_repli', true);
    expect(collect($reponse->json('items'))->pluck('id')->all())->toContain($ancien->id);
});

it('rend TOUS les originaux si le nombre de lignes traduites ne correspond pas', function () {
    config()->set('services.openrouter.api_key', 'cle-de-test');
    // Deux titres envoyés, une seule ligne rendue : correspondance impossible à établir.
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [['message' => ['content' => "1. Un seul titre rendu"]]],
        ], 200),
    ]);

    $resultat = TranslationService::translateBatch(['First headline', 'Second headline']);

    expect($resultat['titres'])->toBe(['First headline', 'Second headline'])
        ->and($resultat['statut'])->toBe('indisponible');
});

it('rend les titres traduits et retire la numérotation quand le compte correspond', function () {
    config()->set('services.openrouter.api_key', 'cle-de-test');
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [['message' => ['content' => "1. Premier titre\n2. Deuxième titre"]]],
        ], 200),
    ]);

    $resultat = TranslationService::translateBatch(['First headline', 'Second headline']);

    expect($resultat['titres'])->toBe(['Premier titre', 'Deuxième titre'])
        ->and($resultat['statut'])->toBe('ok');
});

it('signale l\'indisponibilité quand le fournisseur refuse, sans perdre les titres', function () {
    config()->set('services.openrouter.api_key', 'cle-de-test');
    // Le cas qui a immobilisé l'enrichissement de l'annuaire neuf jours : un refus de fournisseur.
    Http::fake(['openrouter.ai/*' => Http::response(['error' => 'no endpoints'], 404)]);

    $resultat = TranslationService::translateBatch(['First headline']);

    expect($resultat['titres'])->toBe(['First headline'])
        ->and($resultat['statut'])->toBe('indisponible')
        ->and($resultat['motif'])->not->toBeNull();
});

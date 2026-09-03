<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Volet B du design doc « extension de l'écran de composition des actualités » (2026-09-03) :
 * l'écran d'administration classique écrivait ses images par un TROISIÈME pipeline
 * (ScreenshotUploadService), avec un format et une convention de nommage différents des deux
 * autres portes. Il est désormais unifié sur NewsImageService.
 *
 * Ce que ces tests verrouillent, et pourquoi chacun compte :
 *   1. la PAIRE .webp + .jpg est produite. C'est le gain réel et invisible : l'ancien chemin ne
 *      produisait qu'un .jpg, donc la page publique n'avait jamais de WebP à servir, alors que
 *      les deux autres portes en produisaient un. Sans cette assertion, un retour au service
 *      concurrent passerait inaperçu ;
 *   2. le chemin suit la convention unifiée `news/images/{id}` - jamais l'ancienne
 *      `news-screenshots/{slug}` ;
 *   3. `image_url` n'est écrit QUE s'il est vide. L'ancien service l'écrasait à chaque appel ;
 *      la nouvelle porte ne doit ni écraser une valeur existante, ni laisser une fiche créée
 *      hors collecte RSS sans valeur du tout (les deux moitiés sont testées) ;
 *   4. le contrat de réponse `ok`/`message` est préservé : le composant partagé
 *      `x-core::screenshot-capture` exige `ok === true` (screenshot-capture.blade.php:246) -
 *      le casser rendrait l'écran muet sans qu'aucun test PHP ne bronche.
 *
 * Même piège d'échappement que NewsProvenanceTwoSourcesTest : aucune apostrophe dans les
 * chaînes attendues côté HTML. Ici les assertions portent sur du JSON et sur le disque, donc
 * la question ne se pose pas - la note est là pour le prochain qui étendra ce fichier.
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;
use Modules\News\Services\NewsImageService;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Helpers préfixés nai (news admin image) - Pest charge tous les fichiers dans un seul
// processus, un nom nu entrerait en collision avec un autre fichier de test.
function naiSource(): NewsSource
{
    return NewsSource::create([
        'name' => 'Source image admin',
        'url' => 'https://nai-source.exemple.com/rss',
        'language' => 'fr',
        'active' => true,
    ]);
}

function naiArticle(array $overrides = []): NewsArticle
{
    static $i = 0;
    $i++;
    $suffix = $i.'-'.uniqid();

    return NewsArticle::create(array_merge([
        'news_source_id' => naiSource()->id,
        'title' => "Fiche image admin {$i}",
        'guid' => "guid-nai-{$suffix}",
        'url' => "https://exemple.com/nai-{$suffix}",
        'slug' => "fiche-nai-{$suffix}",
        'pub_date' => now()->subDay(),
        'is_published' => false,
    ], $overrides));
}

/**
 * Administrateur capable d'atteindre la route (auth + two.factor + EnsureIsAdmin).
 * Même fabrication qu'Actu2CompositionScreenTest::a2cAdmin() : le rôle passe par Spatie,
 * la table `users` ne porte PAS de colonne `role` - une création directe avec ce champ
 * échoue à l'insertion (mesuré en écrivant ce fichier).
 */
function naiAdmin(): \App\Models\User
{
    $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $user = \App\Models\User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole($role);

    return $user;
}

beforeEach(function () {
    Storage::fake('public');
});

it('produit la PAIRE webp + jpg, sous la convention de nommage unifiee', function () {
    $article = naiArticle();

    $reponse = $this->actingAs(naiAdmin())->post(
        route('admin.news.articles.upload-image', $article),
        ['screenshot' => UploadedFile::fake()->image('capture.jpg', 1400, 800)],
        ['X-Requested-With' => 'XMLHttpRequest']
    );

    $reponse->assertOk()->assertJson(['ok' => true]);

    // Le gain du volet B : les DEUX fichiers, là où l'ancien pipeline n'écrivait que le .jpg.
    Storage::disk('public')->assertExists("news/images/{$article->id}.webp");
    Storage::disk('public')->assertExists("news/images/{$article->id}.jpg");

    // ...et jamais l'ancienne convention de nommage par slug.
    Storage::disk('public')->assertMissing("news-screenshots/{$article->slug}.jpg");
});

it('renseigne image_url quand elle est vide - une fiche creee hors collecte RSS n en a jamais eu', function () {
    $article = naiArticle(['image_url' => null]);

    $this->actingAs(naiAdmin())->post(
        route('admin.news.articles.upload-image', $article),
        ['screenshot' => UploadedFile::fake()->image('capture.jpg', 1400, 800)],
        ['X-Requested-With' => 'XMLHttpRequest']
    )->assertOk();

    expect($article->fresh()->image_url)->toBe("/storage/news/images/{$article->id}.webp");
});

it('n ECRASE PAS une image_url deja renseignee - l ancien service le faisait a chaque appel', function () {
    $article = naiArticle(['image_url' => 'https://cdn.exemple.com/image-de-la-collecte-rss.jpg']);

    $this->actingAs(naiAdmin())->post(
        route('admin.news.articles.upload-image', $article),
        ['screenshot' => UploadedFile::fake()->image('capture.jpg', 1400, 800)],
        ['X-Requested-With' => 'XMLHttpRequest']
    )->assertOk();

    expect($article->fresh()->image_url)->toBe('https://cdn.exemple.com/image-de-la-collecte-rss.jpg');
    // Les fichiers sont bien écrits malgré tout : c'est la COLONNE qu'on ne touche pas,
    // la résolution par convention de chemin prend le relais côté affichage.
    Storage::disk('public')->assertExists("news/images/{$article->id}.webp");
});

it('refuse une image sous le minimum, avec le contrat ok=false que le composant partage attend', function () {
    $article = naiArticle();

    $reponse = $this->actingAs(naiAdmin())->post(
        route('admin.news.articles.upload-image', $article),
        // Sous NewsImageService::MIN_WIDTH (600) x MIN_HEIGHT (315).
        ['screenshot' => UploadedFile::fake()->image('minuscule.jpg', 320, 200)],
        ['X-Requested-With' => 'XMLHttpRequest']
    );

    $reponse->assertStatus(422)->assertJson(['ok' => false]);
    expect($reponse->json('message'))->toContain('320')->toContain('200');

    Storage::disk('public')->assertMissing("news/images/{$article->id}.webp");
});

it('accepte desormais un fichier plus lourd que l ancien plafond de 5120 Ko', function () {
    // Seul effet visible du lot pour un humain : le plafond passe de 5120 à
    // NewsImageService::MAX_UPLOAD_KB (8192). Un fichier de 6 Mo était refusé, il passe.
    expect(NewsImageService::MAX_UPLOAD_KB)->toBe(8192);

    $article = naiArticle();
    $fichier = UploadedFile::fake()->image('grosse-capture.jpg', 1400, 800)->size(6000);

    $this->actingAs(naiAdmin())->post(
        route('admin.news.articles.upload-image', $article),
        ['screenshot' => $fichier],
        ['X-Requested-With' => 'XMLHttpRequest']
    )->assertOk()->assertJson(['ok' => true]);
});

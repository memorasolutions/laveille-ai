<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai — articles planifiés (published_at futur)
 *
 * Couvre deux points d'entrée modifiés le 2026-09-25 :
 *   A. Modules/Backoffice/resources/views/themes/backend/livewire/articles-table.blade.php -
 *      un article « published » dont published_at est futur affiche « Planifié » (au lieu de
 *      « Publié ») dans le sélecteur de statut, et un badge « Prévue » dans la colonne
 *      Publication ; un article publié dans le passé affiche « Publié » et aucun badge.
 *   B. Modules/FrontTheme/resources/views/partials/series-nav.blade.php - dans une série
 *      détectée par le slug « -partie-N », une partie publiée mais planifiée (published_at
 *      futur) est annoncée « À paraître le <date> » avec un lien vers sa propre adresse (page
 *      d'avant-première, construite par un autre travail - non testée ici). Quand cette partie
 *      passe au passé, son bloc bascule vers le texte « Lire cette partie ».
 *
 * Convention : Livewire::test(ArticlesTable::class) + utilisateur super_admin, comme
 * tests/Feature/Phase159Test.php.
 */

use App\Models\User;
use Livewire\Livewire;
use Modules\Backoffice\Livewire\ArticlesTable;
use Modules\Blog\Models\Article;
use Spatie\Permission\Models\Role;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

    // Le cache de réponse (cacheResponse:3600 sur blog.show) est déjà désactivé par
    // phpunit.xml (RESPONSE_CACHE_ENABLED=false) - on le repose explicitement ici pour que ce
    // fichier reste vert même exécuté seul avec une config différente.
    config(['responsecache.enabled' => false]);
});

// ── A. Tableau admin : Planifié / Prévue vs Publié ──────────────────────────────────────

test('un article publié à date future affiche Planifié et le badge Prévue dans le tableau admin', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    Article::factory()->create([
        'status' => 'published',
        'published_at' => now()->addDays(3),
    ]);

    Livewire::actingAs($admin)->test(ArticlesTable::class)
        ->assertOk()
        ->assertSee('Planifié')
        ->assertSee('Prévue');
});

test('un article publié à date passée affiche Publié sans badge Prévue dans le tableau admin', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    Article::factory()->create([
        'status' => 'published',
        'published_at' => now()->subDay(),
    ]);

    Livewire::actingAs($admin)->test(ArticlesTable::class)
        ->assertOk()
        ->assertSee('Publié')
        ->assertDontSee('Prévue');
});

// ── B. Navigation de série : partie planifiée annoncée avec son lien ───────────────────

test('la page publique de la partie 1 annonce la partie 2 planifiée avec son lien, puis bascule quand elle passe au passé', function () {
    $partie1 = Article::factory()->create([
        'status' => 'published',
        'published_at' => now()->subDay(),
        'title' => 'Test série partie 1',
        'slug' => 'test-serie-partie-1',
    ]);
    $partie2 = Article::factory()->create([
        'status' => 'published',
        'published_at' => now()->addDays(3),
        'title' => 'Test série partie 2',
        'slug' => 'test-serie-partie-2',
    ]);
    Article::factory()->create([
        'status' => 'published',
        'published_at' => now()->addDays(5),
        'title' => 'Test série partie 3',
        'slug' => 'test-serie-partie-3',
    ]);

    // Avant : la partie 2 est planifiée - annoncée « À paraître le », avec un lien vers sa
    // propre adresse (page d'avant-première, hors périmètre de ce test), mais pas encore le
    // texte réservé aux parties déjà parues.
    $response = $this->get('/blog/'.$partie1->slug);

    $response->assertOk();
    $response->assertSee('À paraître le');
    $response->assertSee('/blog/test-serie-partie-2', false);
    $response->assertDontSee('Lire cette partie');

    // La partie 2 passe au passé : son bloc bascule vers le lien « Lire cette partie ».
    $partie2->update(['published_at' => now()->subHour()]);

    $response = $this->get('/blog/'.$partie1->slug);

    $response->assertOk();
    $response->assertSee('Lire cette partie');
    $response->assertSee('/blog/test-serie-partie-2', false);
});

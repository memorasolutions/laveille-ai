<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Modules\Blog\Models\Article;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Six tentatives précédentes de rendre le titre de référencement modifiable ont échoué en
// silence : le formulaire d'administration n'exposait aucun champ, et les écritures directes en
// base visaient meta['seo_title'], une clé que personne ne lit jamais (voir le commentaire de
// Article::getSeoTitleAttribute()). Un test qui ne vérifierait que l'écriture en base aurait
// « réussi » exactement de la même façon que ces six tentatives. Ces tests prouvent donc le
// trajet COMPLET : un titre saisi via le contrôleur d'administration doit ressortir dans la
// balise <title> de la page publique - pas seulement dans la colonne meta.

beforeEach(function () {
    App::setLocale('fr_CA');
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super_admin');
});

it('un titre pour Google saisi via le controleur admin ressort dans la balise title de la page publique', function () {
    $admin = $this->admin;

    $article = Article::factory()->published()->create([
        'title' => ['fr_CA' => 'Titre normal de l\'article'],
        'excerpt' => ['fr_CA' => 'Un extrait quelconque.'],
        // Clé pré-existante qui doit survivre à l'écriture (jamais un remplacement complet).
        'meta' => ['description' => 'Une description deja presente a preserver'],
    ]);

    $reponse = $this->actingAs($admin)->put(route('admin.blog.articles.update', $article), [
        'title' => $article->title,
        'excerpt' => $article->excerpt,
        'status' => 'published',
        'seo_title' => 'Titre special pour Google',
    ]);

    $reponse->assertRedirect(route('admin.blog.articles.index'));

    $article->refresh();

    // 1) L'écriture cible la bonne clé et préserve les autres.
    expect($article->seo_title)->toBe('Titre special pour Google');
    expect($article->meta['title'] ?? null)->toBe('Titre special pour Google');
    expect($article->meta)->not->toHaveKey('seo_title');
    expect($article->meta['description'] ?? null)->toBe('Une description deja presente a preserver');

    // 2) Le formulaire d'édition pré-remplit bien la valeur enregistrée (le trajet de lecture,
    // pas seulement celui d'écriture).
    $pageEdition = $this->actingAs($admin)->get(route('admin.blog.articles.edit', $article));
    $pageEdition->assertOk();
    $pageEdition->assertSee('value="Titre special pour Google"', false);

    // 3) Le trajet complet : la page PUBLIQUE affiche ce titre dans la balise <title>, à la
    // place du titre normal de l'article.
    $pagePublique = $this->get('/blog/'.$article->slug);
    $pagePublique->assertOk();

    preg_match('#<title>(.*?)</title>#s', $pagePublique->getContent(), $matches);

    expect($matches[1] ?? null)->toBe('Titre special pour Google - '.config('app.name'));
});

it('un titre pour Google laisse vide utilise le titre normal de l\'article dans la balise title', function () {
    $admin = $this->admin;

    $article = Article::factory()->published()->create([
        'title' => ['fr_CA' => 'Titre normal affiche par defaut'],
        'excerpt' => ['fr_CA' => 'Un extrait quelconque.'],
        'meta' => null,
    ]);

    $reponse = $this->actingAs($admin)->put(route('admin.blog.articles.update', $article), [
        'title' => $article->title,
        'excerpt' => $article->excerpt,
        'status' => 'published',
        'seo_title' => '',
    ]);

    $reponse->assertRedirect(route('admin.blog.articles.index'));

    $article->refresh();

    expect($article->seo_title)->toBeNull();

    $pagePublique = $this->get('/blog/'.$article->slug);
    $pagePublique->assertOk();

    preg_match('#<title>(.*?)</title>#s', $pagePublique->getContent(), $matches);

    expect($matches[1] ?? null)->toBe('Titre normal affiche par defaut - '.config('app.name'));
});

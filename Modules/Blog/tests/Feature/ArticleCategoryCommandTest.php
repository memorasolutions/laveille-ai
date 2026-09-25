<?php

declare(strict_types=1);

/**
 * Tests de la porte d'écriture blog:category (2026-09-25, mesuré en production : au moins 3
 * articles publiés sans category_id, aucune commande n'existait pour la corriger).
 *
 * Couvre les deux formes de Modules\Blog\Console\ArticleCategoryCommand :
 *   - --missing : liste en JSON les articles sans category_id, brouillons compris.
 *   - {article} {categorie} [--dry-run] : pose category_id, par id ou par slug, refuse un
 *     article ou une catégorie introuvable, n'écrit rien en --dry-run.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\Category;

uses(Tests\TestCase::class, RefreshDatabase::class);

// ── --missing ────────────────────────────────────────────────────────────────────────────

it('--missing liste un article sans catégorie et pas un article catégorisé', function () {
    $category = Category::factory()->create(['slug' => 'divers']);

    $sansCategorie = Article::factory()->published()->create(['slug' => 'article-sans-categorie']);
    $avecCategorie = Article::factory()->published()->create(['slug' => 'article-avec-categorie', 'category_id' => $category->id]);

    $exitCode = \Illuminate\Support\Facades\Artisan::call('blog:category', ['--missing' => true]);
    $payload = json_decode(trim(\Illuminate\Support\Facades\Artisan::output()), true);

    expect($exitCode)->toBe(0);

    $ids = collect($payload)->pluck('id')->all();

    expect($ids)->toContain($sansCategorie->id)
        ->and($ids)->not->toContain($avecCategorie->id);
});

it('--missing inclut les brouillons, pas seulement les articles publiés', function () {
    $brouillon = Article::factory()->draft()->create(['slug' => 'brouillon-sans-categorie']);

    \Illuminate\Support\Facades\Artisan::call('blog:category', ['--missing' => true]);
    $payload = json_decode(trim(\Illuminate\Support\Facades\Artisan::output()), true);
    $ligne = collect($payload)->firstWhere('id', $brouillon->id);

    expect($ligne)->not->toBeNull()
        ->and($ligne['statut'])->toBe('draft');
});

it('--missing refuse d\'être combiné avec article/categorie', function () {
    Article::factory()->create();

    $this->artisan('blog:category', ['article' => '1', '--missing' => true])
        ->assertFailed();
});

// ── Pose par id et par slug ──────────────────────────────────────────────────────────────

it('pose la catégorie par id d\'article', function () {
    $category = Category::factory()->create(['slug' => 'intelligence-artificielle']);
    $article = Article::factory()->published()->create(['slug' => 'article-par-id']);

    $this->artisan('blog:category', ['article' => (string) $article->id, 'categorie' => 'intelligence-artificielle'])
        ->assertSuccessful();

    expect($article->fresh()->category_id)->toBe($category->id);
});

it('pose la catégorie par slug d\'article', function () {
    $category = Category::factory()->create(['slug' => 'guides-tutoriels']);
    $article = Article::factory()->published()->create(['slug' => 'article-par-slug']);

    $this->artisan('blog:category', ['article' => 'article-par-slug', 'categorie' => 'guides-tutoriels'])
        ->assertSuccessful();

    expect($article->fresh()->category_id)->toBe($category->id);
});

it('est idempotent : ré-exécuter avec la même catégorie ne change rien et reste un succès', function () {
    $category = Category::factory()->create(['slug' => 'outils-ressources']);
    $article = Article::factory()->published()->create(['slug' => 'article-idempotent', 'category_id' => $category->id]);

    $this->artisan('blog:category', ['article' => 'article-idempotent', 'categorie' => 'outils-ressources'])
        ->assertSuccessful();

    expect($article->fresh()->category_id)->toBe($category->id);
});

it('n\'écrit jamais dans la colonne texte category (legacy)', function () {
    $category = Category::factory()->create(['slug' => 'le-concentre']);
    $article = Article::factory()->published()->create(['slug' => 'article-category-texte', 'category' => 'Ancienne valeur texte']);

    $this->artisan('blog:category', ['article' => 'article-category-texte', 'categorie' => 'le-concentre'])
        ->assertSuccessful();

    $fresh = $article->fresh();
    expect($fresh->category_id)->toBe($category->id)
        ->and($fresh->category)->toBe('Ancienne valeur texte');
});

// ── --dry-run ────────────────────────────────────────────────────────────────────────────

it('--dry-run n\'écrit rien', function () {
    $category = Category::factory()->create(['slug' => 'pedagogie-numerique']);
    $article = Article::factory()->published()->create(['slug' => 'article-dry-run']);

    $this->artisan('blog:category', ['article' => 'article-dry-run', 'categorie' => 'pedagogie-numerique', '--dry-run' => true])
        ->assertSuccessful();

    expect($article->fresh()->category_id)->toBeNull();
});

// ── Refus ────────────────────────────────────────────────────────────────────────────────

it('refuse une catégorie inconnue', function () {
    $article = Article::factory()->create(['slug' => 'article-categorie-inconnue']);

    $this->artisan('blog:category', ['article' => 'article-categorie-inconnue', 'categorie' => 'cette-categorie-nexiste-pas'])
        ->assertFailed();

    expect($article->fresh()->category_id)->toBeNull();
});

it('refuse un article introuvable', function () {
    $category = Category::factory()->create(['slug' => 'frequence-numerique']);

    $this->artisan('blog:category', ['article' => '999999', 'categorie' => 'frequence-numerique'])
        ->assertFailed();
});

it('refuse sans {categorie} et sans --missing', function () {
    $article = Article::factory()->create();

    $this->artisan('blog:category', ['article' => (string) $article->id])
        ->assertFailed();
});

<?php

declare(strict_types=1);

/**
 * Second passage (2026-08-30, tâche #1942) -
 * Modules/News/database/migrations/2026_08_30_100000_reset_stale_meta_description_second_pass.php.
 *
 * Couvre la même intersection liste-figée + non-NULL que le premier passage
 * (ResetStaleMetaDescriptionMigrationTest.php), sur le DELTA de 12 IDs découvert par le filtre
 * élargi (summary OU structured_summary, au lieu de summary seul) - voir le commentaire en tête
 * du fichier de migration pour la preuve concrète (fiche #2327) qui a motivé ce second lot.
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, RefreshDatabase::class);

function rsmd2Migration()
{
    return require base_path('Modules/News/database/migrations/2026_08_30_100000_reset_stale_meta_description_second_pass.php');
}

function rsmd2ArticleWithId(int $id, array $overrides = []): NewsArticle
{
    $source = NewsSource::create([
        'name' => 'Source migration meta_description lot 2',
        'url' => 'https://rsmd2-source.exemple.com/rss-'.$id,
        'language' => 'fr',
        'active' => true,
    ]);

    $article = NewsArticle::create(array_merge([
        'news_source_id' => $source->id,
        'title' => "Article migration meta_description lot 2 - {$id}",
        'guid' => "guid-rsmd2-{$id}",
        'url' => "https://exemple.com/rsmd2-{$id}",
        'description' => '',
        'summary' => "Résumé {$id}",
        'slug' => "article-rsmd2-{$id}",
        'pub_date' => now()->subDay(),
        'is_published' => false,
        'seo_status' => 'index',
    ], $overrides));

    DB::table('news_articles')->where('id', $article->id)->update(['id' => $id]);

    return NewsArticle::find($id);
}

it('nulls meta_description for the real case that motivated this second pass (fiche #2327, title corrected, description left with the old figure)', function () {
    // Reproduit le cas réel mesuré en production : titre corrigé (8 000 employés) alors que
    // meta_description affichait encore l'ancien chiffre (16 000 licenciements).
    $article = rsmd2ArticleWithId(2327, [
        'title' => 'Meta licencie 8 000 employés dès mai, d\'autres coupes pourraient suivre',
        'meta_description' => "Meta annonce jusqu'à 16 000 licenciements dès mai 2026 pour financer ses investissements en IA et en centres de données.",
    ]);

    $migration = rsmd2Migration();
    $migration->up();

    $fresh = $article->fresh();
    expect($fresh->meta_description)->toBeNull()
        ->and($fresh->title)->toContain('8 000');
});

it('nulls meta_description only for an article that is BOTH in IDS_A_VERIFIER_LOT_2 and still non-null', function () {
    // 34670 : seule fiche récente du lot 2 (correction du 2026-08-22 via structured_summary
    // seul, ratée par le premier passage qui exigeait la clé exacte "summary").
    $concernee = rsmd2ArticleWithId(34670, ['meta_description' => 'MARQUEUR-PERIMEE-LOT-2']);

    $migration = rsmd2Migration();
    $migration->up();

    expect($concernee->fresh()->meta_description)->toBeNull();
});

it('never touches an article outside IDS_A_VERIFIER_LOT_2, even with a non-null meta_description', function () {
    $horsListe = rsmd2ArticleWithId(999998, ['meta_description' => 'MARQUEUR-JAMAIS-TOUCHEE-LOT-2']);

    $migration = rsmd2Migration();
    $migration->up();

    expect($horsListe->fresh()->meta_description)->toBe('MARQUEUR-JAMAIS-TOUCHEE-LOT-2');
});

it('running up() twice is harmless (idempotent)', function () {
    $concernee = rsmd2ArticleWithId(1936, ['meta_description' => 'MARQUEUR-DOUBLE-RUN-LOT-2']);

    $migration = rsmd2Migration();
    $migration->up();
    $migration->up();

    expect($concernee->fresh()->meta_description)->toBeNull();
});

it('down() is a deliberate no-op', function () {
    $concernee = rsmd2ArticleWithId(8333, ['meta_description' => 'MARQUEUR-AVANT-UP-LOT-2']);

    $migration = rsmd2Migration();
    $migration->up();
    expect($concernee->fresh()->meta_description)->toBeNull();

    $migration->down();
    expect($concernee->fresh()->meta_description)->toBeNull();
});

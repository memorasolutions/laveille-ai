<?php

declare(strict_types=1);

/**
 * Migration de nettoyage rétroactif (2026-08-30, tâche #1942) -
 * Modules/News/database/migrations/2026_08_30_000000_reset_stale_meta_description_after_correction.php.
 *
 * Trois garanties verrouillées ici, chacune choisie parce qu'un échec silencieux y ferait mal :
 *  - seules les fiches à la fois DANS la liste figée IDS_A_VERIFIER ET dont meta_description est
 *    ENCORE non NULL au moment de l'exécution sont touchées - jamais une fiche hors liste, jamais
 *    une fiche déjà à null (rien à corriger) ;
 *  - la remise à null est la SEULE écriture : titre, résumé, slug et toute autre colonne restent
 *    strictement inchangés ;
 *  - down() est un NO-OP assumé (voir le commentaire en tête du fichier de migration) : il ne
 *    doit RIEN modifier, dans un sens comme dans l'autre.
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, RefreshDatabase::class);

function rsmdMigration()
{
    return require base_path('Modules/News/database/migrations/2026_08_30_000000_reset_stale_meta_description_after_correction.php');
}

/**
 * Crée une fiche puis force sa clé primaire à $id (UPDATE direct : create() ignore 'id',
 * absente de NewsArticle::$fillable - seule façon fiable de matérialiser une fiche de test sous
 * un ID précis de IDS_A_VERIFIER, liste figée sur de vrais IDs de production).
 */
function rsmdArticleWithId(int $id, array $overrides = []): NewsArticle
{
    $source = NewsSource::create([
        'name' => 'Source migration meta_description',
        'url' => 'https://rsmd-source.exemple.com/rss-'.$id,
        'language' => 'fr',
        'active' => true,
    ]);

    $article = NewsArticle::create(array_merge([
        'news_source_id' => $source->id,
        'title' => "Article migration meta_description {$id}",
        'guid' => "guid-rsmd-{$id}",
        'url' => "https://exemple.com/rsmd-{$id}",
        'description' => '',
        'summary' => "Résumé {$id}",
        'slug' => "article-rsmd-{$id}",
        'pub_date' => now()->subDay(),
        'is_published' => false,
        'seo_status' => 'index',
    ], $overrides));

    DB::table('news_articles')->where('id', $article->id)->update(['id' => $id]);

    return NewsArticle::find($id);
}

it('nulls meta_description only for an article that is BOTH in IDS_A_VERIFIER and still non-null', function () {
    // 35168 est un ID réel de IDS_A_VERIFIER (mesuré, correction de summary via news:apply le
    // 2026-08-23) - voir le commentaire en tête du fichier de migration pour la méthode de mesure.
    $concernee = rsmdArticleWithId(35168, ['meta_description' => 'MARQUEUR-PERIMEE-A-EFFACER']);

    $migration = rsmdMigration();
    $migration->up();

    expect($concernee->fresh()->meta_description)->toBeNull();
});

it('never touches an article outside IDS_A_VERIFIER, even with a non-null meta_description', function () {
    // ID choisi HORS de la liste figée (aucune correction de summary mesurée sur cette fiche).
    $horsListe = rsmdArticleWithId(999999, ['meta_description' => 'MARQUEUR-JAMAIS-TOUCHEE']);

    $migration = rsmdMigration();
    $migration->up();

    expect($horsListe->fresh()->meta_description)->toBe('MARQUEUR-JAMAIS-TOUCHEE');
});

it('never touches an article in IDS_A_VERIFIER whose meta_description is already null (nothing to correct)', function () {
    $dejaNull = rsmdArticleWithId(37547, ['meta_description' => null, 'summary' => 'Résumé déjà courant.']);

    $migration = rsmdMigration();
    $migration->up();

    $fresh = $dejaNull->fresh();
    expect($fresh->meta_description)->toBeNull()
        ->and($fresh->summary)->toBe('Résumé déjà courant.');
});

it('touches meta_description only - title, summary and slug stay strictly unchanged', function () {
    $concernee = rsmdArticleWithId(38933, [
        'meta_description' => 'MARQUEUR-PERIMEE-CHAMPS-VOISINS',
        'title' => 'MARQUEUR-TITRE-INTACT',
        'summary' => 'MARQUEUR-RESUME-INTACT',
        'slug' => 'marqueur-slug-intact-38933',
    ]);

    $migration = rsmdMigration();
    $migration->up();

    $fresh = $concernee->fresh();
    expect($fresh->meta_description)->toBeNull()
        ->and($fresh->title)->toBe('MARQUEUR-TITRE-INTACT')
        ->and($fresh->summary)->toBe('MARQUEUR-RESUME-INTACT')
        ->and($fresh->slug)->toBe('marqueur-slug-intact-38933');
});

it('running up() twice is harmless (idempotent - already-null rows are simply skipped again)', function () {
    $concernee = rsmdArticleWithId(39471, ['meta_description' => 'MARQUEUR-PERIMEE-DOUBLE-RUN']);

    $migration = rsmdMigration();
    $migration->up();
    $migration->up();

    expect($concernee->fresh()->meta_description)->toBeNull();
});

it('down() is a deliberate no-op : it neither restores the stale value nor touches anything else', function () {
    $concernee = rsmdArticleWithId(39526, ['meta_description' => 'MARQUEUR-PERIMEE-AVANT-UP']);

    $migration = rsmdMigration();
    $migration->up();
    expect($concernee->fresh()->meta_description)->toBeNull();

    // down() ne doit RIEN restaurer : ni la valeur périmée, ni quoi que ce soit d'autre.
    $migration->down();
    expect($concernee->fresh()->meta_description)->toBeNull();
});

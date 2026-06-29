<?php

declare(strict_types=1);

/**
 * Tests pivot news_article_tool : liaison actualités ↔ outils.
 *
 * Couvre : attach/detach/sync, unicité, cascade delete, source, relations inverses.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Directory\Models\Tool;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, RefreshDatabase::class);

/** Fabrique une source RSS de test. */
function makePivotSource(): NewsSource
{
    return NewsSource::create([
        'name' => 'Source pivot test',
        'url' => 'https://test-pivot.exemple.com/rss',
        'language' => 'fr',
        'active' => true,
    ]);
}

/** Fabrique une actualité publiée. */
function makePivotArticle(int $sourceId, string $suffix = 'A'): NewsArticle
{
    return NewsArticle::create([
        'news_source_id' => $sourceId,
        'title' => "Article pivot {$suffix}",
        'guid' => "guid-pivot-{$suffix}",
        'url' => "https://exemple.com/pivot-{$suffix}",
        'description' => "Description de test pour pivot {$suffix}",
        'slug' => "article-pivot-{$suffix}",
        'pub_date' => now()->subDay(),
        'is_published' => true,
        'seo_status' => 'index',
    ]);
}

/** Fabrique un outil publié avec les champs JSON Spatie (sans déclencher IndexNow). */
function makePivotTool(string $suffix = 'A'): Tool
{
    $name = "Outil pivot {$suffix}";
    $slug = "outil-pivot-" . strtolower($suffix);

    // withoutEvents évite l'appel route('directory.show') du trait NotifiesIndexNow en test.
    return Tool::withoutEvents(fn () => Tool::create([
        'name' => json_encode(['fr_CA' => $name, 'en' => $name]),
        'slug' => json_encode(['fr_CA' => $slug, 'en' => $slug]),
        'status' => 'published',
        'pricing' => 'free',
    ]));
}

// ── Relation NewsArticle::tools ──────────────────────────────────────────────

it('attache un outil à une actualité avec source manual', function () {
    $source = makePivotSource();
    $article = makePivotArticle($source->id);
    $tool = makePivotTool();

    $article->tools()->attach($tool->id, ['source' => 'manual']);

    expect($article->tools()->count())->toBe(1);
    expect($article->tools()->first()->id)->toBe($tool->id);
    expect($article->tools()->first()->pivot->source)->toBe('manual');
});

it('détache un outil d\'une actualité', function () {
    $source = makePivotSource();
    $article = makePivotArticle($source->id);
    $tool = makePivotTool();

    $article->tools()->attach($tool->id, ['source' => 'manual']);
    $article->tools()->detach($tool->id);

    expect($article->tools()->count())->toBe(0);
});

it('synchronise plusieurs outils sur une actualité', function () {
    $source = makePivotSource();
    $article = makePivotArticle($source->id);
    $toolA = makePivotTool('S1');
    $toolB = makePivotTool('S2');
    $toolC = makePivotTool('S3');

    $article->tools()->sync([
        $toolA->id => ['source' => 'manual'],
        $toolB->id => ['source' => 'manual'],
    ]);

    expect($article->tools()->count())->toBe(2);

    // Sync vers un seul outil : les deux autres sont retirés.
    $article->tools()->sync([$toolC->id => ['source' => 'manual']]);

    expect($article->tools()->count())->toBe(1);
    expect($article->tools()->first()->id)->toBe($toolC->id);
});

// ── Contrainte unique ────────────────────────────────────────────────────────

it('lève une exception sur un doublon news_article_id + tool_id', function () {
    $source = makePivotSource();
    $article = makePivotArticle($source->id, 'U');
    $tool = makePivotTool('U');

    $article->tools()->attach($tool->id, ['source' => 'manual']);

    expect(fn () => DB::table('news_article_tool')->insert([
        'news_article_id' => $article->id,
        'tool_id' => $tool->id,
        'source' => 'manual',
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});

// ── Relation inverse Tool::newsArticles ──────────────────────────────────────

it('la relation inverse Tool::newsArticles fonctionne', function () {
    $source = makePivotSource();
    $articleA = makePivotArticle($source->id, 'IA');
    $articleB = makePivotArticle($source->id, 'IB');
    $tool = makePivotTool('I');

    $tool->newsArticles()->attach([$articleA->id => ['source' => 'manual'], $articleB->id => ['source' => 'auto']]);

    expect($tool->newsArticles()->count())->toBe(2);
    expect($tool->newsArticles()->pluck('id'))->toContain($articleA->id)
        ->toContain($articleB->id);
});

// ── Cascade delete ────────────────────────────────────────────────────────────

it('supprime les lignes pivot quand l\'actualité est supprimée', function () {
    $source = makePivotSource();
    $article = makePivotArticle($source->id, 'D');
    $tool = makePivotTool('D');

    $article->tools()->attach($tool->id, ['source' => 'manual']);

    $articleId = $article->id;
    $article->delete();

    expect(DB::table('news_article_tool')->where('news_article_id', $articleId)->count())->toBe(0);
});

it('supprime les lignes pivot quand l\'outil est supprimé', function () {
    $source = makePivotSource();
    $article = makePivotArticle($source->id, 'E');
    $tool = makePivotTool('E');

    $article->tools()->attach($tool->id, ['source' => 'manual']);

    $toolId = $tool->id;
    $tool->delete();

    expect(DB::table('news_article_tool')->where('tool_id', $toolId)->count())->toBe(0);
});

// ── Colonne source ────────────────────────────────────────────────────────────

it('conserve la valeur source dans le pivot', function () {
    $source = makePivotSource();
    $article = makePivotArticle($source->id, 'SRC');
    $toolM = makePivotTool('M');
    $toolAuto = makePivotTool('AUTO');

    $article->tools()->attach($toolM->id, ['source' => 'manual']);
    $article->tools()->attach($toolAuto->id, ['source' => 'auto']);

    $pivotManual = $article->tools()->where('directory_tools.id', $toolM->id)->first();
    $pivotAuto = $article->tools()->where('directory_tools.id', $toolAuto->id)->first();

    expect($pivotManual->pivot->source)->toBe('manual');
    expect($pivotAuto->pivot->source)->toBe('auto');
});

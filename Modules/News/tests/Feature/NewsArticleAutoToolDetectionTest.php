<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Tests Pest - détection automatique d'outils annuaire à la publication d'une actualité.
 *
 * Couvre :
 *  - NewsToolSyncAction::attachAuto() : attache les nouveaux outils en source=auto SANS
 *    jamais écraser une liaison déjà existante (manual ou auto) ;
 *  - AutoDetectNewsToolsJob::handle() : détecte et attache réellement un outil (source=auto) ;
 *  - Observer : is_published false→true dispatch le Job ; republication sans changement
 *    (déjà publié, aucune mutation) ne dispatch RIEN.
 *
 * Helpers préfixés `natd` (NewsArticleAutoToolDetection) pour éviter les redéclarations
 * globales avec les autres suites du module News.
 */

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Modules\Directory\Models\Tool;
use Modules\News\Actions\NewsToolSyncAction;
use Modules\News\Jobs\AutoDetectNewsToolsJob;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

// ── Helpers ───────────────────────────────────────────────────────────────────

function natdSource(): NewsSource
{
    return NewsSource::create([
        'name'     => 'Source NATD',
        'url'      => 'https://natd-source.exemple.com/rss',
        'language' => 'fr',
        'active'   => true,
    ]);
}

function natdTool(string $name, string $slug): Tool
{
    return Tool::withoutEvents(fn () => Tool::create([
        'name'    => ['fr_CA' => $name, 'en' => $name],
        'slug'    => ['fr_CA' => $slug, 'en' => $slug],
        'status'  => 'published',
        'pricing' => 'free',
    ]));
}

function natdArticle(int $sourceId, array $overrides = []): NewsArticle
{
    $suffix = uniqid();

    return NewsArticle::create(array_merge([
        'news_source_id' => $sourceId,
        'title'          => 'Article NATD',
        'guid'           => "guid-natd-{$suffix}",
        'url'            => "https://exemple.com/natd-{$suffix}",
        'description'    => '',
        'summary'        => '',
        'slug'           => "article-natd-{$suffix}",
        'pub_date'       => now()->subDay(),
        'is_published'   => false,
        'seo_status'     => 'index',
    ], $overrides));
}

// ── NewsToolSyncAction::attachAuto() ──────────────────────────────────────────

it('attachAuto attache les nouveaux outils en source=auto sans écraser une liaison manuelle existante', function () {
    $source     = natdSource();
    $article    = natdArticle($source->id, ['is_published' => true]);
    $manualTool = natdTool('Outil Manuel NATD', 'outil-manuel-natd');
    $newTool    = natdTool('Outil Nouveau NATD', 'outil-nouveau-natd');

    $article->tools()->attach($manualTool->id, ['source' => 'manual']);

    $count = app(NewsToolSyncAction::class)->attachAuto(
        $article,
        collect([$manualTool->id, $newTool->id])
    );

    expect($count)->toBe(1);

    $pivotManual = DB::table('news_article_tool')
        ->where('news_article_id', $article->id)
        ->where('tool_id', $manualTool->id)
        ->first();
    $pivotNew = DB::table('news_article_tool')
        ->where('news_article_id', $article->id)
        ->where('tool_id', $newTool->id)
        ->first();

    expect($pivotManual->source)->toBe('manual');
    expect($pivotNew->source)->toBe('auto');
});

it('attachAuto ne fait rien si tous les outils détectés sont déjà liés', function () {
    $source  = natdSource();
    $article = natdArticle($source->id, ['is_published' => true]);
    $tool    = natdTool('Outil Déjà Lié NATD', 'outil-deja-lie-natd');

    $article->tools()->attach($tool->id, ['source' => 'manual']);

    $count = app(NewsToolSyncAction::class)->attachAuto($article, collect([$tool->id]));

    expect($count)->toBe(0);

    $pivot = DB::table('news_article_tool')
        ->where('news_article_id', $article->id)
        ->where('tool_id', $tool->id)
        ->first();
    expect($pivot->source)->toBe('manual');
});

// ── AutoDetectNewsToolsJob::handle() ──────────────────────────────────────────

it('AutoDetectNewsToolsJob attache un outil détecté avec source=auto', function () {
    $source = natdSource();
    $tool   = natdTool('Notion NATD', 'notion-natd');

    $article = natdArticle($source->id, [
        'title'               => 'Une actualité sur la productivité en entreprise',
        'is_published'        => true,
        'structured_summary'  => [
            'hook'          => 'Des équipes migrent leur documentation vers Notion NATD pour centraliser leurs connaissances.',
            'key_points'    => [],
            'why_important' => "L'adoption de Notion NATD illustre une tendance de fond.",
        ],
    ]);

    (new AutoDetectNewsToolsJob($article))->handle();

    $pivot = DB::table('news_article_tool')
        ->where('news_article_id', $article->id)
        ->where('tool_id', $tool->id)
        ->first();

    expect($pivot)->not->toBeNull();
    expect($pivot->source)->toBe('auto');
});

it('AutoDetectNewsToolsJob ne fait rien quand aucun outil n\'est détecté', function () {
    $source  = natdSource();
    $article = natdArticle($source->id, [
        'title'        => 'Une actualité générique sans nom d\'outil',
        'is_published' => true,
    ]);

    (new AutoDetectNewsToolsJob($article))->handle();

    expect($article->fresh()->tools()->count())->toBe(0);
});

// ── Déclenchement à la publication (Observer → Job en queue) ─────────────────

it('publier une actualité (is_published false→true) dispatch AutoDetectNewsToolsJob', function () {
    Queue::fake();

    $source  = natdSource();
    $article = natdArticle($source->id, ['is_published' => false]);

    $article->update(['is_published' => true]);

    Queue::assertPushed(AutoDetectNewsToolsJob::class, function (AutoDetectNewsToolsJob $job) use ($article) {
        return $job->article->id === $article->id;
    });
});

it('republier une actualité déjà publiée (aucun changement) ne dispatch PAS AutoDetectNewsToolsJob', function () {
    Queue::fake();

    $source  = natdSource();
    $article = natdArticle($source->id, ['is_published' => true]);

    $article->update(['is_published' => true]);

    Queue::assertNotPushed(AutoDetectNewsToolsJob::class);
});

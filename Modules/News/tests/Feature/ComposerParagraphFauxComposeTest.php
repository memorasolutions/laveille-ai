<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Défaut mesuré en production le 2026-08-28 (fiche « libreoffice-268-... », thèse entière de la
 * fiche : l'ABSENCE d'IA générative). LibreOffice 26.8 a une fonctionnalité nommée « Paragraph
 * Composer » (moteur de composition typographique, RIEN à voir avec l'IA). Les DEUX mécanismes du
 * linkifier partagé se trompaient sur ce seul mot, pour la MÊME cause racine : une seule matching
 * (GlossaryLinkifier::loadTerms()/linkify()) consommée deux fois -
 *   1. Le corps de texte recevait <a href="/annuaire/composer" class="glossary-link"> sur "Composer".
 *   2. NewsToolSyncAction::suggest() (via getLastMatchedTerms(), qui LIT le résultat du même appel
 *      linkify()) attachait l'outil « Composer » à la fiche en source=auto.
 *
 * Les deux mécanismes sont donc CONVERGENTS ici (contrairement au défaut « Local/Montage/Pulse/
 * Logic » fermé plus tôt le même jour, qui lui venait d'un SECOND chemin, la recapture de
 * NewsToolSyncAction::suggest() sur TOOL_NEVER_AUTO - $neverAutoIds, qui ne s'applique pas à
 * "composer" puisqu'il n'a jamais figuré dans TOOL_NEVER_AUTO).
 *
 * Correctif : GlossaryLinkifier::TOOL_COMPOUND_EXCLUSIONS (voir son docblock) - un lookbehind
 * négatif ciblé sur le préfixe fautif exact, PAS un blocage total via TOOL_NEVER_AUTO. Un blocage
 * total aurait aussi supprimé l'auto-lien légitime pour l'outil Composer employé seul (Composer
 * n'est pas un mot français courant en prose ordinaire comme "avec"/"tome" - rien ne justifie de
 * le priver de tout auto-lien) : c'est précisément ce que le 2e test de chaque mécanisme verrouille.
 *
 * Suit le patron déjà établi par BackfillAutoToolDetectionCommandTest.php (helpers préfixés,
 * commande réelle news:backfill-auto-tools) et CodexAliasAutoLinkTest.php (appel PUBLIC
 * GlossaryLinkifier::linkify(), jamais la réflexion bas niveau de GlossaryLinkifierTest.php - c'est
 * justement loadTerms() qui porte le correctif, la réflexion le court-circuiterait).
 */

use Illuminate\Support\Facades\DB;
use Modules\Core\Services\GlossaryLinkifier;
use Modules\Directory\Models\Tool;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

// ── Helpers (préfixe pcfc = Paragraph Composer Faux Composé) ──────────────────

function pcfcSource(): NewsSource
{
    return NewsSource::create([
        'name' => 'Source PCFC',
        'url' => 'https://pcfc-source.exemple.com/rss',
        'language' => 'fr',
        'active' => true,
    ]);
}

function pcfcComposerTool(): Tool
{
    return Tool::withoutEvents(fn () => Tool::create([
        'name' => ['fr_CA' => 'Composer', 'en' => 'Composer'],
        'slug' => ['fr_CA' => 'composer', 'en' => 'composer'],
        'status' => 'published',
        'pricing' => 'free',
    ]));
}

function pcfcArticle(int $sourceId, string $summary): NewsArticle
{
    $suffix = uniqid();

    return NewsArticle::withoutEvents(fn () => NewsArticle::create([
        'news_source_id' => $sourceId,
        'title' => 'Article PCFC',
        'guid' => "guid-pcfc-{$suffix}",
        'url' => "https://exemple.com/pcfc-{$suffix}",
        'description' => '',
        'summary' => $summary,
        'slug' => "article-pcfc-{$suffix}",
        'pub_date' => now()->subDay(),
        'is_published' => true,
        'seo_status' => 'index',
    ]));
}

function pcfcLiaisons(int $articleId): int
{
    return DB::table('news_article_tool')->where('news_article_id', $articleId)->count();
}

beforeEach(function () {
    GlossaryLinkifier::resetState();
    GlossaryLinkifier::flushCache();
});

// ── Mécanisme 1 : lien dans le corps de texte (GlossaryLinkifier) ─────────────

it('ne lie PAS "Composer" a linterieur de "Paragraph Composer"', function () {
    pcfcComposerTool();

    $html = GlossaryLinkifier::linkify('<p>La nouveaute phare, le Paragraph Composer, ameliore la composition typographique.</p>');

    expect($html)->not->toContain('glossary-link')
        ->and($html)->not->toContain('/annuaire/composer');
});

it('lie "Composer" employe seul, dans un contexte qui parle reellement de loutil', function () {
    pcfcComposerTool();

    $html = GlossaryLinkifier::linkify('<p>Les equipes qui utilisent Composer collaborent plus efficacement sur leurs documents.</p>');

    expect($html)->toContain('glossary-link')
        ->and($html)->toContain('/annuaire/composer')
        ->and($html)->toContain('>Composer</a>');
});

// ── Mécanisme 2 : attachement automatique a la fiche (NewsToolSyncAction, source=auto) ─

it('nattache PAS loutil Composer quand seul "Paragraph Composer" est mentionne dans la fiche', function () {
    $source = pcfcSource();
    pcfcComposerTool();

    $article = pcfcArticle(
        $source->id,
        "La nouveaute phare, le Paragraph Composer, ameliore la composition typographique. Rien a voir avec l'IA generative."
    );

    $this->artisan('news:backfill-auto-tools', ['--limit' => 50])->assertExitCode(0);

    expect(pcfcLiaisons($article->id))->toBe(0);
});

it('attache loutil Composer quand il est mentionne seul, en contexte reel', function () {
    $source = pcfcSource();
    pcfcComposerTool();

    $article = pcfcArticle($source->id, 'Composer facilite grandement la collaboration en temps reel sur les documents.');

    $this->artisan('news:backfill-auto-tools', ['--limit' => 50])->assertExitCode(0);

    expect(pcfcLiaisons($article->id))->toBe(1);

    $pivot = DB::table('news_article_tool')->where('news_article_id', $article->id)->first();
    expect($pivot->source)->toBe('auto');
});

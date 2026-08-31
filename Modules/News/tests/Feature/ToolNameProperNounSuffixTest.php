<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Défaut mesuré en production le 2026-08-31 (mandat #2091, CHANGELOG v1.242.11, lot de 250
 * fiches du rattrapage news:backfill-auto-tools) : l'outil « Clark » détecté et rattaché à
 * l'intérieur du nom propre « Clark Wiethorn » (agent du FBI, aucun rapport avec l'outil) ;
 * l'outil « Ghost » détecté et rattaché à l'intérieur du nom de code « Ghost Murmur ». Dans les
 * deux cas, le nom de l'outil est le PREMIER mot d'un nom propre composé de deux mots, sans
 * rapport avec l'outil - le symétrique exact du défaut « Paragraph Composer » corrigé le
 * 2026-08-28 (ComposerParagraphFauxComposeTest.php), où le parasite précédait le nom au lieu de
 * le suivre.
 *
 * Correction : GlossaryLinkifier::TOOL_SUFFIX_SAFE_MODIFIERS + buildToolSuffixGuard() (voir
 * docblock de la constante) - une RÈGLE générale (mot à majuscule initiale non reconnu comme
 * modificateur de produit = probable nom propre composé sans rapport), pas une liste par outil
 * comme TOOL_COMPOUND_EXCLUSIONS : on ne peut pas énumérer tous les noms propres du monde.
 *
 * Les DEUX mécanismes qui partagent ce risque sont couverts par la MÊME garde (voir docblock de
 * NewsToolSyncAction::suggest()) :
 *   1. Le corps de texte (GlossaryLinkifier::linkify()) ne doit plus poser de lien sur le nom.
 *   2. NewsToolSyncAction::suggest() ne doit plus rattacher l'outil à l'actualité.
 *
 * Suit le patron déjà établi par ComposerParagraphFauxComposeTest.php (helpers préfixés,
 * commande réelle news:backfill-auto-tools, appel PUBLIC GlossaryLinkifier::linkify()).
 */

use Illuminate\Support\Facades\DB;
use Modules\Core\Services\GlossaryLinkifier;
use Modules\Directory\Models\Tool;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

// ── Helpers (préfixe tpns = Tool name Proper Noun Suffix) ──────────────────────

function tpnsSource(): NewsSource
{
    return NewsSource::create([
        'name' => 'Source TPNS',
        'url' => 'https://tpns-source.exemple.com/rss',
        'language' => 'fr',
        'active' => true,
    ]);
}

function tpnsTool(string $name, string $slug): Tool
{
    return Tool::withoutEvents(fn () => Tool::create([
        'name' => ['fr_CA' => $name, 'en' => $name],
        'slug' => ['fr_CA' => $slug, 'en' => $slug],
        'status' => 'published',
        'pricing' => 'free',
    ]));
}

function tpnsArticle(int $sourceId, string $summary): NewsArticle
{
    $suffix = uniqid();

    return NewsArticle::withoutEvents(fn () => NewsArticle::create([
        'news_source_id' => $sourceId,
        'title' => 'Article TPNS',
        'guid' => "guid-tpns-{$suffix}",
        'url' => "https://exemple.com/tpns-{$suffix}",
        'description' => '',
        'summary' => $summary,
        'slug' => "article-tpns-{$suffix}",
        'pub_date' => now()->subDay(),
        'is_published' => true,
        'seo_status' => 'index',
    ]));
}

function tpnsLiaisons(int $articleId): int
{
    return DB::table('news_article_tool')->where('news_article_id', $articleId)->count();
}

beforeEach(function () {
    GlossaryLinkifier::resetState();
    GlossaryLinkifier::flushCache();
});

// ── Mécanisme 1 : lien dans le corps de texte (GlossaryLinkifier) ─────────────

it('ne lie PAS "Clark" a linterieur du nom propre "Clark Wiethorn"', function () {
    tpnsTool('Clark', 'clark');

    $html = GlossaryLinkifier::linkify("<p>L'agent du FBI Clark Wiethorn a confirme l'information au tribunal.</p>");

    expect($html)->not->toContain('glossary-link')
        ->and($html)->not->toContain('/annuaire/clark');
});

it('ne lie PAS "Ghost" a linterieur du nom de code "Ghost Murmur"', function () {
    tpnsTool('Ghost', 'ghost');

    $html = GlossaryLinkifier::linkify('<p>Le programme, nom de code Ghost Murmur, a ete revele hier par le ministere.</p>');

    expect($html)->not->toContain('glossary-link')
        ->and($html)->not->toContain('/annuaire/ghost');
});

it('lie "Clark" employe seul, dans un contexte qui parle reellement de loutil', function () {
    tpnsTool('Clark', 'clark');

    $html = GlossaryLinkifier::linkify('<p>Les equipes qui utilisent Clark automatisent une bonne partie de leur veille.</p>');

    expect($html)->toContain('glossary-link')
        ->and($html)->toContain('/annuaire/clark')
        ->and($html)->toContain('>Clark</a>');
});

// ── Mécanisme 2 : attachement automatique a la fiche (NewsToolSyncAction, source=auto) ─

it('nattache PAS loutil Clark quand seul "Clark Wiethorn" est mentionne dans la fiche', function () {
    $source = tpnsSource();
    tpnsTool('Clark', 'clark');

    $article = tpnsArticle(
        $source->id,
        "L'agent du FBI Clark Wiethorn a confirme l'information. L'enquete se poursuit."
    );

    $this->artisan('news:backfill-auto-tools', ['--limit' => 50])->assertExitCode(0);

    expect(tpnsLiaisons($article->id))->toBe(0);
});

it('nattache PAS loutil Ghost quand seul "Ghost Murmur" est mentionne dans la fiche', function () {
    $source = tpnsSource();
    tpnsTool('Ghost', 'ghost');

    $article = tpnsArticle(
        $source->id,
        'Le programme, nom de code Ghost Murmur, a ete revele hier par le ministere de la Defense.'
    );

    $this->artisan('news:backfill-auto-tools', ['--limit' => 50])->assertExitCode(0);

    expect(tpnsLiaisons($article->id))->toBe(0);
});

it('attache loutil Clark quand il est mentionne seul, en contexte reel', function () {
    $source = tpnsSource();
    tpnsTool('Clark', 'clark');

    $article = tpnsArticle($source->id, 'Clark facilite grandement le suivi automatise des sources.');

    $this->artisan('news:backfill-auto-tools', ['--limit' => 50])->assertExitCode(0);

    expect(tpnsLiaisons($article->id))->toBe(1);

    $pivot = DB::table('news_article_tool')->where('news_article_id', $article->id)->first();
    expect($pivot->source)->toBe('auto');
});

// ── Non-régression : les noms composés produit légitimes cités par le mandat #2091 ─────

it('lie et attache "ChatGPT" dans le nom compose legitime "ChatGPT Plus"', function () {
    $source = tpnsSource();
    tpnsTool('ChatGPT', 'chatgpt');

    $article = tpnsArticle($source->id, 'Les abonnes ChatGPT Plus profitent des derniers modeles disponibles.');

    $html = GlossaryLinkifier::linkify('<p>Les abonnes ChatGPT Plus profitent des derniers modeles.</p>');
    expect($html)->toContain('/annuaire/chatgpt');

    $this->artisan('news:backfill-auto-tools', ['--limit' => 50])->assertExitCode(0);
    expect(tpnsLiaisons($article->id))->toBe(1);
});

it('lie et attache "Notion" dans le nom compose legitime "Notion AI"', function () {
    // NB : « Gemini »/« Mistral » ne conviennent PAS ici - les deux figurent DEJA dans
    // GlossaryLinkifier::TOOL_NEVER_AUTO (mots courants/prenoms) et sont donc exclus de
    // loadTerms() pour une raison totalement independante de cette garde suffixe. « Notion »
    // n'y figure pas : c'est un choix valide pour prouver la garde en conditions reelles (DB).
    $source = tpnsSource();
    tpnsTool('Notion', 'notion');

    $article = tpnsArticle($source->id, 'Notion AI facilite grandement la redaction de documentation.');

    $this->artisan('news:backfill-auto-tools', ['--limit' => 50])->assertExitCode(0);
    expect(tpnsLiaisons($article->id))->toBe(1);
});

<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Tests Pest - NewsToolSyncAction::suggest() : détection automatique d'outils liés.
 *
 * Reproduit le bug rapporté (2026-07-04) : le bouton « Suggérer les outils détectés »
 * renvoyait TOUJOURS 0 résultat pour un article dont le nom de l'outil (ex. « Claude »)
 * n'apparaissait QUE dans structured_summary (hook/key_points/why_important), pas dans
 * title/description/summary - seuls champs scannés avant le correctif.
 *
 * Prouve aussi l'absence de régression : un mot français courant partagé avec un nom
 * d'outil TOOL_NEVER_AUTO (ex. « avec ») ne doit PAS déclencher de faux positif quand il
 * apparaît en minuscule en milieu de phrase (un outil publié « Avec » existe réellement
 * dans l'annuaire - confirmé en production).
 */

use Modules\Directory\Models\Tool;
use Modules\News\Actions\NewsToolSyncAction;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

// ── Helpers ───────────────────────────────────────────────────────────────────

function ntsaSource(): NewsSource
{
    return NewsSource::create([
        'name'     => 'Source NTSA',
        'url'      => 'https://ntsa-source.exemple.com/rss',
        'language' => 'fr',
        'active'   => true,
    ]);
}

function ntsaTool(string $name, string $slug): Tool
{
    return Tool::withoutEvents(fn () => Tool::create([
        'name'   => ['fr_CA' => $name, 'en' => $name],
        'slug'   => ['fr_CA' => $slug, 'en' => $slug],
        'status' => 'published',
        'pricing' => 'free',
    ]));
}

// ── Régression : outil mentionné UNIQUEMENT dans structured_summary ──────────

it('suggest() détecte un outil TOOL_NEVER_AUTO mentionné uniquement dans le résumé structuré IA', function () {
    // « claude » fait partie de GlossaryLinkifier::TOOL_NEVER_AUTO (mot aussi courant : prénom).
    $tool = ntsaTool('Claude', 'claude');

    $article = NewsArticle::create([
        'news_source_id' => ntsaSource()->id,
        'title'          => 'Explosion des vulnérabilités de sécurité grâce à l\'IA',
        'guid'           => 'guid-ntsa-claude',
        'url'            => 'https://exemple.com/ntsa-claude',
        // Aucune mention de l'outil dans title/description/summary - AVANT le correctif,
        // ce champ était le SEUL scanné par suggest() → 0 détection.
        'description'    => '',
        'summary'        => '',
        'structured_summary' => [
            'hook' => "En juin 2026, un record de vulnérabilités a été signalé, principalement grâce à l'utilisation de modèles IA comme Claude Mythos Preview d'Anthropic.",
            'key_points' => [
                '1 500 vulnérabilités critiques signalées en juin 2026',
            ],
            'why_important' => "Les modèles d'IA, comme Claude Mythos Preview d'Anthropic, permettent de détecter automatiquement les vulnérabilités.",
        ],
        'slug'         => 'article-ntsa-claude',
        'pub_date'     => now()->subDay(),
        'is_published' => true,
        'seo_status'   => 'index',
    ]);

    $suggested = app(NewsToolSyncAction::class)->suggest($article);

    expect($suggested->all())->toContain($tool->id);
});

// ── Anti-régression : mot français courant NE doit PAS produire de faux positif ──

it('suggest() ignore un outil TOOL_NEVER_AUTO homonyme d\'un mot français courant en minuscule', function () {
    // « avec » fait partie de TOOL_NEVER_AUTO ET un outil publié « Avec » existe réellement
    // dans l'annuaire (confirmé en prod, 2026-07-04) - risque de faux positif si le mot
    // français « avec » (extrêmement courant) était scanné sans distinction de casse.
    $tool = ntsaTool('Avec', 'avec');

    $article = NewsArticle::create([
        'news_source_id' => ntsaSource()->id,
        'title'          => 'Une actualité qui n\'a rien à voir avec cet outil',
        'guid'           => 'guid-ntsa-avec',
        'url'            => 'https://exemple.com/ntsa-avec',
        'description'    => '',
        'summary'        => '',
        'structured_summary' => [
            'hook' => "Cette technologie fonctionne avec plusieurs modules d'intelligence artificielle.",
            'key_points' => [
                'Un point clé quelconque, sans lien avec un outil précis.',
            ],
            'why_important' => "C'est important, mais pas à cause d'un outil nommé ainsi.",
        ],
        'slug'         => 'article-ntsa-avec',
        'pub_date'     => now()->subDay(),
        'is_published' => true,
        'seo_status'   => 'index',
    ]);

    $suggested = app(NewsToolSyncAction::class)->suggest($article);

    expect($suggested->all())->not->toContain($tool->id);
});

// ── Cas général : outil NON TOOL_NEVER_AUTO mentionné uniquement dans structured_summary ──

it('suggest() détecte un outil ordinaire mentionné uniquement dans le résumé structuré IA', function () {
    $tool = ntsaTool('Notion', 'notion');

    $article = NewsArticle::create([
        'news_source_id' => ntsaSource()->id,
        'title'          => 'Une actualité sur la productivité en entreprise',
        'guid'           => 'guid-ntsa-notion',
        'url'            => 'https://exemple.com/ntsa-notion',
        'description'    => '',
        'summary'        => '',
        'structured_summary' => [
            'hook' => 'Des équipes migrent leur documentation vers Notion pour centraliser leurs connaissances.',
            'key_points' => [],
            'why_important' => "L'adoption de Notion illustre une tendance de fond.",
        ],
        'slug'         => 'article-ntsa-notion',
        'pub_date'     => now()->subDay(),
        'is_published' => true,
        'seo_status'   => 'index',
    ]);

    $suggested = app(NewsToolSyncAction::class)->suggest($article);

    expect($suggested->all())->toContain($tool->id);
});

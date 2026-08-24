<?php

declare(strict_types=1);

/**
 * Tests de la commande news:translate-titles (traduction des titres PRÉCALCULÉE, hors du chemin
 * synchrone de l'écran de composition - design doc "Actus - composition manuelle assistée",
 * révision 2026-08-24, voir Modules\News\Console\TranslateTitlesCommand). Couvre : la sélection
 * (source non francophone, 'seo_title' vide, 'title_fr' encore NULL), l'idempotence (un article
 * déjà traduit n'est jamais retraduit), le mécanisme de reprise (un lot rejeté laisse ses
 * articles à NULL, sans boucle de tentative), --dry-run (aucune écriture) et --limit.
 *
 * NON EXÉCUTÉS par ce sous-agent (consigne docs/CONTRAINTES-SOUS-AGENTS.md, section 2) - à
 * exécuter par le superviseur, une seule suite à la fois.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, RefreshDatabase::class);

function ttcSource(string $langue = 'en'): NewsSource
{
    return NewsSource::firstOrCreate(
        ['url' => "https://ttc-{$langue}.exemple.com/rss"],
        ['name' => "Source ttc {$langue}", 'language' => $langue, 'active' => true]
    );
}

function ttcArticle(string $langue = 'en', array $overrides = []): NewsArticle
{
    static $i = 0;
    $i++;
    $suffix = $i.'-'.uniqid();

    return NewsArticle::create(array_merge([
        'news_source_id' => ttcSource($langue)->id,
        'title' => "Headline ttc {$i}",
        'guid' => "guid-ttc-{$suffix}",
        'url' => "https://exemple.com/ttc-{$suffix}",
        'description' => '',
        'summary' => "Résumé ttc {$i}",
        'slug' => "ttc-{$suffix}",
        'pub_date' => now()->subMinutes($i),
        'is_published' => false,
        'seo_status' => 'index',
    ], $overrides));
}

function ttcFakeTranslation(array $lignes): void
{
    config()->set('services.openrouter.api_key', 'cle-de-test');
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [['message' => ['content' => implode("\n", $lignes)]]],
        ], 200),
    ]);
}

it('traduit un article candidat et écrit title_fr et title_fr_at', function () {
    $article = ttcArticle();
    expect($article->title_fr)->toBeNull();

    ttcFakeTranslation(['1. Titre traduit']);

    Artisan::call('news:translate-titles');

    $article->refresh();
    expect($article->title_fr)->toBe('Titre traduit')
        ->and($article->title_fr_at)->not->toBeNull();
});

it('est idempotente : un second passage ne retraduit jamais un article déjà traduit', function () {
    $article = ttcArticle();

    ttcFakeTranslation(['1. Premiere traduction']);
    Artisan::call('news:translate-titles');
    $article->refresh();
    expect($article->title_fr)->toBe('Premiere traduction');
    $premiereDate = $article->title_fr_at;

    // Deuxième passage : l'article n'a plus aucune raison d'être candidat (title_fr n'est plus
    // NULL). On le prouve en faisant échouer toute nouvelle tentative de traduction, et en
    // vérifiant qu'aucun appel réseau n'a même lieu.
    Http::fake([
        'openrouter.ai/*' => Http::response(['error' => 'ne devrait jamais être appelé'], 500),
    ]);
    Artisan::call('news:translate-titles');

    $article->refresh();
    expect($article->title_fr)->toBe('Premiere traduction')
        ->and($article->title_fr_at->equalTo($premiereDate))->toBeTrue();
    Http::assertNothingSent();
});

it('ne sélectionne jamais un article dont la source est francophone', function () {
    $article = ttcArticle('fr');

    ttcFakeTranslation(['1. Ne devrait jamais servir']);
    Artisan::call('news:translate-titles');

    $article->refresh();
    expect($article->title_fr)->toBeNull();
    Http::assertNothingSent();
});

it('ne sélectionne jamais un article qui a déjà un seo_title', function () {
    $article = ttcArticle('en', ['seo_title' => 'Titre éditorial déjà réécrit']);

    ttcFakeTranslation(['1. Ne devrait jamais servir']);
    Artisan::call('news:translate-titles');

    $article->refresh();
    expect($article->title_fr)->toBeNull();
    Http::assertNothingSent();
});

it('--dry-run ne traduit ni n\'écrit rien', function () {
    $article = ttcArticle();

    ttcFakeTranslation(['1. Ne devrait jamais être écrit']);
    Artisan::call('news:translate-titles', ['--dry-run' => true]);

    $article->refresh();
    expect($article->title_fr)->toBeNull()
        ->and($article->title_fr_at)->toBeNull();
    Http::assertNothingSent();
});

it('laisse un lot rejeté à NULL - mécanisme de reprise, sans boucle de tentative', function () {
    // Deux titres envoyés dans le même lot, une seule ligne rendue : TranslationService rejette
    // le lot ENTIER (compte de lignes incohérent - la cascade interne de modèles de
    // TranslationService peut retenter avec un autre modèle, mais la commande elle-même
    // n'appelle translateBatch() qu'UNE fois par lot, jamais en boucle). Les DEUX articles
    // restent à NULL : c'est le mécanisme de reprise voulu, repris à la prochaine exécution.
    $article = ttcArticle();
    $autre = ttcArticle();

    ttcFakeTranslation(['1. Une seule ligne rendue']);
    Artisan::call('news:translate-titles');

    $article->refresh();
    $autre->refresh();
    expect($article->title_fr)->toBeNull()
        ->and($autre->title_fr)->toBeNull();
});

it('respecte --limit : seuls les N premiers candidats sont traités en une exécution', function () {
    $a1 = ttcArticle();
    $a2 = ttcArticle();
    $a3 = ttcArticle();

    ttcFakeTranslation(['1. Traduit']);
    Artisan::call('news:translate-titles', ['--limit' => 1]);

    $traduits = collect([$a1, $a2, $a3])
        ->map(fn (NewsArticle $a) => $a->refresh()->title_fr)
        ->filter()
        ->count();

    expect($traduits)->toBe(1);
});

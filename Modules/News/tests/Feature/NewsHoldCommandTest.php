<?php

declare(strict_types=1);

/**
 * Tests de la commande news:hold - porte d'écriture bornée de la rétention de composition
 * (colonne composition_hold_until, mécanisme imposé le 2026-09-12) que
 * Modules\News\Console\PruneDraftsCommand respecte. Couvre : la fiche introuvable, la pose d'une
 * rétention à N jours, --release (retrait explicite), la forme du JSON canonique
 * {id, composition_hold_until, released} sorti sur stdout.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, RefreshDatabase::class);

// ── Helpers locaux (préfixés Nhc pour éviter tout conflit inter-fichiers) ──────────────

function nhcSource(): NewsSource
{
    return NewsSource::firstOrCreate(
        ['url' => 'https://nhc-source.exemple.com/rss'],
        ['name' => 'Source news:hold test', 'language' => 'fr', 'active' => true]
    );
}

function nhcArticle(array $overrides = []): NewsArticle
{
    static $i = 0;
    $i++;
    $suffix = $i.'-'.uniqid();
    $source = nhcSource();

    return NewsArticle::create(array_merge([
        'news_source_id' => $source->id,
        'title' => "Article news:hold {$i}",
        'guid' => "guid-nhc-{$suffix}",
        'url' => "https://exemple.com/nhc-{$suffix}",
        'description' => '',
        'summary' => "Résumé initial {$i}",
        'slug' => "article-nhc-{$suffix}",
        'pub_date' => now()->subDay(),
        'is_published' => false,
        'seo_status' => 'index',
    ], $overrides));
}

it('refuses when the article does not exist', function () {
    $this->artisan('news:hold', ['article' => 999999])->assertFailed();
});

it('pose une rétention à --days jours et sort le JSON canonique {id, composition_hold_until, released}', function () {
    $article = nhcArticle();

    $exitCode = Artisan::call('news:hold', ['article' => $article->id, '--days' => 20]);
    $decoded = json_decode(trim(Artisan::output()), true);

    expect($exitCode)->toBe(0)
        ->and($decoded['id'])->toBe($article->id)
        ->and($decoded['released'])->toBeFalse()
        ->and($decoded['composition_hold_until'])->not->toBeNull();

    $article->refresh();
    expect($article->composition_hold_until)->not->toBeNull();
    expect(abs($article->composition_hold_until->diffInDays(now())))
        ->toBeGreaterThanOrEqual(19)
        ->toBeLessThanOrEqual(20);
});

it('utilise 14 jours par défaut quand --days est omis', function () {
    $article = nhcArticle();

    $this->artisan('news:hold', ['article' => $article->id])->assertExitCode(0);

    $article->refresh();
    expect(abs($article->composition_hold_until->diffInDays(now())))
        ->toBeGreaterThanOrEqual(13)
        ->toBeLessThanOrEqual(14);
});

it('--release retire la rétention (composition_hold_until = null)', function () {
    $article = nhcArticle(['composition_hold_until' => now()->addDays(14)]);

    $exitCode = Artisan::call('news:hold', ['article' => $article->id, '--release' => true]);
    $decoded = json_decode(trim(Artisan::output()), true);

    expect($exitCode)->toBe(0)
        ->and($decoded['released'])->toBeTrue()
        ->and($decoded['composition_hold_until'])->toBeNull();

    expect($article->fresh()->composition_hold_until)->toBeNull();
});

// ── Contre-épreuve MORD (2026-09-12) : --days=0 doit échouer ET ne rien écrire ──────────

it('--days=0 échoue et n\'écrit rien en base (contre-épreuve MORD)', function () {
    $article = nhcArticle();
    $avant = $article->composition_hold_until;

    $exitCode = Artisan::call('news:hold', ['article' => $article->id, '--days' => 0]);

    expect($exitCode)->not->toBe(0);
    expect($article->fresh()->composition_hold_until)->toEqual($avant);
});

it('--days=abc échoue et n\'écrit rien en base', function () {
    $article = nhcArticle();
    $avant = $article->composition_hold_until;

    $exitCode = Artisan::call('news:hold', ['article' => $article->id, '--days' => 'abc']);

    expect($exitCode)->not->toBe(0);
    expect($article->fresh()->composition_hold_until)->toEqual($avant);
});

it('--days=3jours échoue et n\'écrit rien en base (le cast (int) laissait passer ce cas)', function () {
    $article = nhcArticle();
    $avant = $article->composition_hold_until;

    $exitCode = Artisan::call('news:hold', ['article' => $article->id, '--days' => '3jours']);

    expect($exitCode)->not->toBe(0);
    expect($article->fresh()->composition_hold_until)->toEqual($avant);
});

it('--release --days=0 réussit et retire bien la rétention (la priorité de --release n\'est pas cassée)', function () {
    $article = nhcArticle(['composition_hold_until' => now()->addDays(14)]);

    $exitCode = Artisan::call('news:hold', ['article' => $article->id, '--release' => true, '--days' => 0]);

    expect($exitCode)->toBe(0);
    expect($article->fresh()->composition_hold_until)->toBeNull();
});

it('holdForComposition(0) lève une InvalidArgumentException (contrat interne)', function () {
    $article = nhcArticle();

    expect(fn () => $article->holdForComposition(0))
        ->toThrow(InvalidArgumentException::class);
});

// ── Borne HAUTE : au-delà de MAX_COMPOSITION_HOLD_DAYS, rien ne doit être écrit ────────
// Les deux tests visent MAX + 1 plutôt qu'un 400 en dur : si la borne change un jour, ils
// suivent la constante au lieu de rougir pour une raison qui n'a rien à voir.

it('--days au-delà du maximum échoue et laisse la rétention inchangée', function () {
    $article = nhcArticle(['composition_hold_until' => null]);

    $exitCode = Artisan::call('news:hold', [
        'article' => $article->id,
        '--days' => NewsArticle::MAX_COMPOSITION_HOLD_DAYS + 1,
    ]);

    expect($exitCode)->not->toBe(0);
    expect($article->fresh()->composition_hold_until)->toBeNull();
});

it('holdForComposition au-delà du maximum lève une InvalidArgumentException (contrat interne)', function () {
    $article = nhcArticle();

    expect(fn () => $article->holdForComposition(NewsArticle::MAX_COMPOSITION_HOLD_DAYS + 1))
        ->toThrow(InvalidArgumentException::class);
});

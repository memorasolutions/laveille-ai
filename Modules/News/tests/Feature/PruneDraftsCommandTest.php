<?php

declare(strict_types=1);

/**
 * Tests de la purge sûre des brouillons bruts (fenêtre glissante des N plus récents, design doc
 * SPEC-PRUNE-DRAFTS, approuvé 2026-08-20). Couvre le garde-fou ABSOLU (une fiche publiée,
 * composée, retirée ou relue au-delà du seuil n'est JAMAIS supprimée), la conservation des
 * brouillons bruts les plus récents, --dry-run (aucune écriture), et le cycle
 * backup-avant-suppression + --restore.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, RefreshDatabase::class);

// ── Helpers locaux (préfixés Npd pour éviter tout conflit inter-fichiers) ───────────────

function npdSource(): NewsSource
{
    // firstOrCreate : le helper est appelé par plusieurs tests, l'URL est unique en base.
    return NewsSource::firstOrCreate(
        ['url' => 'https://npd-source.exemple.com/rss'],
        ['name' => 'Source purge brouillons test', 'language' => 'fr', 'active' => true]
    );
}

function npdArticle(array $overrides = []): NewsArticle
{
    static $i = 0;
    $i++;
    $suffix = $i.'-'.uniqid();
    $source = npdSource();

    return NewsArticle::create(array_merge([
        'news_source_id' => $source->id,
        'title' => "Brouillon purge {$i}",
        'guid' => "guid-npd-{$suffix}",
        'url' => "https://exemple.com/npd-{$suffix}",
        'description' => '',
        'summary' => "Résumé brouillon {$i}",
        'slug' => "brouillon-npd-{$suffix}",
        'pub_date' => now()->subMinutes($i),
        'is_published' => false,
        'seo_status' => 'index',
    ], $overrides));
}

// ── (a) Fiche publiée au-delà du seuil : intouchable ────────────────────────

it('une fiche publiée au-delà du seuil n\'est jamais supprimée', function () {
    $published = npdArticle(['is_published' => true, 'pub_date' => now()->subDays(10)]);
    for ($j = 0; $j < 3; $j++) {
        npdArticle(['pub_date' => now()->subMinutes($j)]);
    }

    $this->artisan('news:prune-drafts', ['--keep' => 2])->assertExitCode(0);

    expect(NewsArticle::find($published->id))->not->toBeNull();
});

// ── (b) Fiche composée au-delà du seuil : intouchable ───────────────────────

it('une fiche composée au-delà du seuil n\'est jamais supprimée', function () {
    $composed = npdArticle([
        'pub_date' => now()->subDays(10),
        'structured_summary' => ['composed' => true, 'lead' => 'Texte de test'],
    ]);
    for ($j = 0; $j < 3; $j++) {
        npdArticle(['pub_date' => now()->subMinutes($j)]);
    }

    $this->artisan('news:prune-drafts', ['--keep' => 2])->assertExitCode(0);

    expect(NewsArticle::find($composed->id))->not->toBeNull();
});

// ── (c) Fiche retirée au-delà du seuil : intouchable ────────────────────────

it('une fiche retirée au-delà du seuil n\'est jamais supprimée', function () {
    $retired = npdArticle(['pub_date' => now()->subDays(10)]);
    $retired->retire();
    for ($j = 0; $j < 3; $j++) {
        npdArticle(['pub_date' => now()->subMinutes($j)]);
    }

    $this->artisan('news:prune-drafts', ['--keep' => 2])->assertExitCode(0);

    expect(NewsArticle::find($retired->id))->not->toBeNull();
});

// ── (d) Fiche relue éditorialement au-delà du seuil : intouchable ───────────

it('une fiche relue au-delà du seuil n\'est jamais supprimée', function () {
    $reviewed = npdArticle(['pub_date' => now()->subDays(10), 'reviewed_at' => now()]);
    for ($j = 0; $j < 3; $j++) {
        npdArticle(['pub_date' => now()->subMinutes($j)]);
    }

    $this->artisan('news:prune-drafts', ['--keep' => 2])->assertExitCode(0);

    expect(NewsArticle::find($reviewed->id))->not->toBeNull();
});

// ── (e) Vieux brouillon brut au-delà du seuil : supprimé ────────────────────

it('un vieux brouillon brut au-delà du seuil est supprimé', function () {
    $old = npdArticle(['pub_date' => now()->subDays(10)]);
    for ($j = 0; $j < 3; $j++) {
        npdArticle(['pub_date' => now()->subMinutes($j)]);
    }

    $this->artisan('news:prune-drafts', ['--keep' => 2])->assertExitCode(0);

    expect(NewsArticle::find($old->id))->toBeNull();
});

// ── (f) Les N brouillons bruts les plus récents sont gardés ─────────────────

it('les brouillons bruts les plus récents sont gardés', function () {
    $recent1 = npdArticle(['pub_date' => now()->subMinutes(1)]);
    $recent2 = npdArticle(['pub_date' => now()->subMinutes(2)]);
    $old = npdArticle(['pub_date' => now()->subDays(5)]);

    $this->artisan('news:prune-drafts', ['--keep' => 2])->assertExitCode(0);

    expect(NewsArticle::find($recent1->id))->not->toBeNull();
    expect(NewsArticle::find($recent2->id))->not->toBeNull();
    expect(NewsArticle::find($old->id))->toBeNull();
});

// ── (g) --dry-run ne supprime rien et n'écrit pas de backup ─────────────────

it('--dry-run ne supprime rien et n\'écrit pas de backup', function () {
    npdArticle(['pub_date' => now()->subDays(10)]);
    npdArticle(['pub_date' => now()->subMinutes(1)]);
    npdArticle(['pub_date' => now()->subMinutes(2)]);

    $before = glob(storage_path('app/news-prune-drafts-backup-*.json')) ?: [];

    $this->artisan('news:prune-drafts', ['--keep' => 2, '--dry-run' => true])->assertExitCode(0);

    expect(NewsArticle::count())->toBe(3);
    $after = glob(storage_path('app/news-prune-drafts-backup-*.json')) ?: [];
    expect(count($after))->toBe(count($before));
});

// ── (h) Backup écrit AVANT suppression, --restore recrée les lignes ─────────

it('le backup est écrit avant suppression et --restore recrée les lignes', function () {
    $old = npdArticle(['pub_date' => now()->subDays(10)]);
    npdArticle(['pub_date' => now()->subMinutes(1)]);
    npdArticle(['pub_date' => now()->subMinutes(2)]);
    $oldId = $old->id;
    $oldSlug = $old->slug;

    $this->artisan('news:prune-drafts', ['--keep' => 2])->assertExitCode(0);

    expect(NewsArticle::find($oldId))->toBeNull();

    $backups = glob(storage_path('app/news-prune-drafts-backup-*.json'));
    expect($backups)->not->toBeEmpty();

    $latest = end($backups);
    $decoded = json_decode((string) file_get_contents($latest), true);
    $backedUpIds = array_column($decoded, 'id');
    expect($backedUpIds)->toContain($oldId);

    $this->artisan('news:prune-drafts', ['--restore' => basename($latest)])->assertExitCode(0);

    $restored = NewsArticle::find($oldId);
    expect($restored)->not->toBeNull();
    expect($restored->slug)->toBe($oldSlug);
});

// ── Fenêtre en JOURS de collecte (--keep-days), demande du fondateur 2026-08-23 ─────────

it('garde les brouillons collectés aujourd\'hui et cible ceux de la veille', function () {
    $aujourdhui = npdArticle();
    $hier = npdArticle();
    // La date de collecte se force après coup : created_at n'est pas dans $fillable.
    $hier->forceFill(['created_at' => now()->subDay()->setTime(14, 0)])->saveQuietly();

    $this->artisan('news:prune-drafts', ['--keep-days' => 1])->assertExitCode(0);

    expect(NewsArticle::find($aujourdhui->id))->not->toBeNull()
        ->and(NewsArticle::find($hier->id))->toBeNull();
});

// La fenêtre en jours ne doit RIEN changer aux quatre garde-fous. Chacun est vérifié
// séparément : un test qui les grouperait masquerait lequel a lâché.
it('la fenêtre en jours ne touche ni une fiche publiée ni une fiche composée, même anciennes', function () {
    $publiee = npdArticle(['is_published' => true]);
    $publiee->forceFill(['created_at' => now()->subDays(30)])->saveQuietly();

    $composee = npdArticle(['structured_summary' => ['composed' => true, 'hook' => 'Quelque chose de composé.']]);
    $composee->forceFill(['created_at' => now()->subDays(30)])->saveQuietly();

    $this->artisan('news:prune-drafts', ['--keep-days' => 1])->assertExitCode(0);

    expect(NewsArticle::find($publiee->id))->not->toBeNull()
        ->and(NewsArticle::find($composee->id))->not->toBeNull();
});

it('une date de collecte absente ne se juge pas : le brouillon est gardé', function () {
    $sansDate = npdArticle();
    $sansDate->forceFill(['created_at' => null])->saveQuietly();

    $this->artisan('news:prune-drafts', ['--keep-days' => 1])->assertExitCode(0);

    expect(NewsArticle::find($sansDate->id))->not->toBeNull();
});

it('le mode --dry-run avec une fenêtre en jours ne supprime rien', function () {
    $hier = npdArticle();
    $hier->forceFill(['created_at' => now()->subDays(3)])->saveQuietly();

    $this->artisan('news:prune-drafts', ['--keep-days' => 1, '--dry-run' => true])->assertExitCode(0);

    expect(NewsArticle::find($hier->id))->not->toBeNull();
});

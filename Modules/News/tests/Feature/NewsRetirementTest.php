<?php

declare(strict_types=1);

/**
 * Tests du retrait SEO-sûr et RÉVERSIBLE des fiches d'actualités (chantier AdSense
 * « faible valeur », 2026-08-18). Couvre : la réponse 410 servie par une fiche retirée sur sa
 * page publique, son absence de NewsArticle::published(), la restauration via unretire() qui
 * la remet dans published() et rend sa page 200 à nouveau, et la commande news:retire
 * (--ids-file, --restore, backup AVANT écriture).
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, RefreshDatabase::class);

// ── Helpers locaux (préfixés Nrt pour éviter tout conflit inter-fichiers) ──────────────

function nrtSource(): NewsSource
{
    // firstOrCreate : le helper est appelé par plusieurs tests, l'URL est unique en base.
    return NewsSource::firstOrCreate(
        ['url' => 'https://nrt-source.exemple.com/rss'],
        ['name' => 'Source retrait test', 'language' => 'fr', 'active' => true]
    );
}

function nrtArticle(array $overrides = []): NewsArticle
{
    static $i = 0;
    $i++;
    $suffix = $i.'-'.uniqid();
    $source = nrtSource();

    return NewsArticle::create(array_merge([
        'news_source_id' => $source->id,
        'title' => "Article retrait {$i}",
        'guid' => "guid-nrt-{$suffix}",
        'url' => "https://exemple.com/nrt-{$suffix}",
        'description' => '',
        // Garde-fou anti-corps-vide (design doc "Actus - zéro copie du texte source",
        // 2026-08-13, section 4.4) : une fiche publiée sans résumé n'est plus servie (404).
        'summary' => "Résumé de test retrait {$i}",
        'slug' => "article-nrt-{$suffix}",
        'pub_date' => now()->subDay(),
        'is_published' => true,
        'seo_status' => 'index',
    ], $overrides));
}

function nrtIdsFile(array $ids): string
{
    $path = sys_get_temp_dir().'/nrt-ids-'.uniqid().'.json';
    file_put_contents($path, json_encode(['ids' => $ids], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    return $path;
}

// ── (a) Fiche retirée → 410 + vue gone ──────────────────────────────────────

it('une fiche retirée répond 410 et rend la vue gone sur sa page publique', function () {
    $article = nrtArticle();
    $article->retire();

    $response = $this->get(route('news.show', $article->slug));

    $response->assertStatus(410);
    $response->assertViewIs('news::public.gone');
    $response->assertSee('retirée', escape: false);
});

// ── (b) Fiche retirée absente de published() ────────────────────────────────

it('une fiche retirée est absente de NewsArticle::published()', function () {
    $visible = nrtArticle();
    $retired = nrtArticle();
    $retired->retire();

    $ids = NewsArticle::published()->pluck('id')->all();

    expect($ids)->toContain($visible->id);
    expect($ids)->not->toContain($retired->id);
});

// ── (c) unretire() restaure la visibilité et le 200 ─────────────────────────

it('unretire() remet la fiche dans published() et sa page redevient 200', function () {
    $article = nrtArticle();
    $article->retire();
    expect($article->isRetired())->toBeTrue();
    expect(NewsArticle::published()->pluck('id')->all())->not->toContain($article->id);

    $article->unretire();

    expect($article->isRetired())->toBeFalse();
    expect(NewsArticle::published()->pluck('id')->all())->toContain($article->id);

    $response = $this->get(route('news.show', $article->slug));
    $response->assertStatus(200);
});

// ── (d) retire() est idempotent (ne recule jamais la date de retrait) ───────

it('retire() est idempotent : un second appel ne modifie pas retired_at déjà posé', function () {
    $article = nrtArticle();
    $article->retire();
    $first = $article->fresh()->retired_at;

    $this->travel(1)->hour();
    $article->retire();
    $second = $article->fresh()->retired_at;

    expect($second->equalTo($first))->toBeTrue();
});

// ── (e) news:retire --ids-file marque les fiches et écrit le backup ────────

it('news:retire --ids-file retire les fiches désignées et écrit un backup avant mutation', function () {
    $a1 = nrtArticle();
    $a2 = nrtArticle();
    $idsFile = nrtIdsFile([$a1->id, $a2->id]);

    $this->artisan('news:retire', ['--ids-file' => $idsFile])
        ->assertExitCode(0);

    expect($a1->fresh()->isRetired())->toBeTrue();
    expect($a2->fresh()->isRetired())->toBeTrue();

    $backups = glob(storage_path('app/news-retire-backup-*.json'));
    expect($backups)->not->toBeEmpty();

    $latest = end($backups);
    $decoded = json_decode((string) file_get_contents($latest), true);
    $backedUpIds = array_column($decoded, 'id');
    expect($backedUpIds)->toContain($a1->id);
    expect($backedUpIds)->toContain($a2->id);
    // L'état sauvegardé est celui d'AVANT le retrait (retired_at encore null).
    foreach ($decoded as $row) {
        if (in_array($row['id'], [$a1->id, $a2->id], true)) {
            expect($row['retired_at'])->toBeNull();
        }
    }
});

// ── (f) news:retire --restore remet les fiches en ligne ─────────────────────

it('news:retire --restore restaure les fiches désignées (retired_at = null)', function () {
    $a1 = nrtArticle();
    $a1->retire();
    $idsFile = nrtIdsFile([$a1->id]);

    $this->artisan('news:retire', ['--ids-file' => $idsFile, '--restore' => true])
        ->assertExitCode(0);

    expect($a1->fresh()->isRetired())->toBeFalse();
});

// ── (g) --dry-run n'écrit rien ───────────────────────────────────────────────

it('news:retire --dry-run ne modifie aucune fiche', function () {
    $a1 = nrtArticle();
    $idsFile = nrtIdsFile([$a1->id]);

    $this->artisan('news:retire', ['--ids-file' => $idsFile, '--dry-run' => true])
        ->assertExitCode(0);

    expect($a1->fresh()->isRetired())->toBeFalse();
});

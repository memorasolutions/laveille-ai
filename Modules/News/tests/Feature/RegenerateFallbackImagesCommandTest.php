<?php

declare(strict_types=1);

/**
 * Tests de la commande news:regenerate-fallback-images (opération de masse distincte annoncée
 * hors périmètre par le correctif v1.237.5 - image de repli bakant le mauvais titre). Couvre :
 * la sélection (publiée, non curatée, --non-french-only), la garde curatée en défense en
 * profondeur MÊME via --ids explicite, l'idempotence par manifest (--force pour la contourner),
 * le dry-run qui n'écrit rien, et - un seul test, le plus lourd - une régénération réelle
 * (Imagick) qui prouve le backup AVANT écriture, la mise à jour du manifest et le cache-bust
 * (updated_at) sur un article réel.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, RefreshDatabase::class);

// ── Helpers locaux (préfixés Rfi pour éviter tout conflit inter-fichiers) ──────────────

function rfiSource(string $language = 'fr'): NewsSource
{
    static $i = 0;
    $i++;

    return NewsSource::create([
        'name' => "Source test regen images {$language} {$i}",
        'url' => "https://rfi-source-{$language}-{$i}.exemple.com/rss",
        'language' => $language,
        'active' => true,
    ]);
}

function rfiArticle(int $sourceId, array $overrides = []): NewsArticle
{
    static $i = 0;
    $i++;
    $suffix = $i.'-'.uniqid();

    return NewsArticle::create(array_merge([
        'news_source_id' => $sourceId,
        'title' => "Raw source title {$i}",
        'guid' => "guid-rfi-{$suffix}",
        'url' => "https://exemple.com/rfi-{$suffix}",
        'description' => '',
        'summary' => "Résumé de test {$i}",
        'slug' => "article-rfi-{$suffix}",
        'pub_date' => now()->subDay(),
        'is_published' => true,
        'seo_status' => 'index',
    ], $overrides));
}

// ── Dossier de travail isolé (jamais le storage/app réel) - nettoyé après chaque test ──

function rfiWorkDir(): string
{
    return storage_path('framework/testing/rfi-workdir-'.uniqid());
}

function rfiRmDir(string $dir): void
{
    if (! is_dir($dir)) {
        return;
    }
    foreach (glob("{$dir}/*") ?: [] as $f) {
        is_dir($f) ? rfiRmDir($f) : @unlink($f);
    }
    @rmdir($dir);
}

// ── Sélection ───────────────────────────────────────────────────────────────────────

it('ignore une fiche avec image curatée (image_credit rempli) dès la sélection', function () {
    $source = rfiSource('en');
    rfiArticle($source->id, ['image_credit' => 'Image : générée (Gemini)']);
    $cible = rfiArticle($source->id);

    $work = rfiWorkDir();
    $this->artisan('news:regenerate-fallback-images', ['--dry-run' => true, '--work-dir' => $work])
        ->expectsOutputToContain('Candidats correspondant aux filtres (avant --limit) : 1')
        ->assertSuccessful();

    rfiRmDir($work);
});

it('ignore une fiche non publiée et une fiche retirée', function () {
    $source = rfiSource('fr');
    rfiArticle($source->id, ['is_published' => false]);
    rfiArticle($source->id, ['is_published' => true, 'retired_at' => now()]);
    rfiArticle($source->id); // publiée, vivante -> seule candidate

    $work = rfiWorkDir();
    $this->artisan('news:regenerate-fallback-images', ['--dry-run' => true, '--work-dir' => $work])
        ->expectsOutputToContain('Candidats correspondant aux filtres (avant --limit) : 1')
        ->assertSuccessful();

    rfiRmDir($work);
});

it('--non-french-only ne cible que les fiches dont la source n a pas la langue fr', function () {
    $fr = rfiSource('fr');
    $en = rfiSource('en');
    rfiArticle($fr->id);
    rfiArticle($en->id);
    rfiArticle($en->id);

    $work = rfiWorkDir();
    $this->artisan('news:regenerate-fallback-images', ['--dry-run' => true, '--non-french-only' => true, '--work-dir' => $work])
        ->expectsOutputToContain('Candidats correspondant aux filtres (avant --limit) : 2')
        ->assertSuccessful();

    rfiRmDir($work);
});

it('--ids respecte quand même la garde image curatée (défense en profondeur)', function () {
    $source = rfiSource('en');
    $curatee = rfiArticle($source->id, ['image_credit' => 'Photo : Untel']);
    $normale = rfiArticle($source->id);

    $work = rfiWorkDir();
    $this->artisan('news:regenerate-fallback-images', [
        '--dry-run' => true,
        '--ids' => "{$curatee->id},{$normale->id}",
        '--work-dir' => $work,
    ])
        ->expectsOutputToContain('Candidats correspondant aux filtres (avant --limit) : 1')
        ->assertSuccessful();

    rfiRmDir($work);
});

// ── Idempotence (manifest) ──────────────────────────────────────────────────────────

it('un id déjà présent dans le manifest avec le même titre cible est ignoré au passage suivant', function () {
    $source = rfiSource('en');
    $article = rfiArticle($source->id, ['title' => 'Titre cible stable']);

    $work = rfiWorkDir();
    @mkdir($work, 0755, true);
    file_put_contents("{$work}/manifest.json", json_encode([
        (string) $article->id => ['baked_title' => 'Titre cible stable', 'at' => now()->toIso8601String()],
    ]));

    $this->artisan('news:regenerate-fallback-images', ['--dry-run' => true, '--ids' => (string) $article->id, '--work-dir' => $work])
        ->expectsOutputToContain('ignorées (déjà bon)')
        ->assertSuccessful();

    // Le résumé doit montrer 1 ignorée-déjà-bon et 0 "serait régénéré" - vérifié via le compte,
    // pas seulement la présence du mot, pour ne pas se satisfaire d'un texte accidentel.
    $this->artisan('news:regenerate-fallback-images', ['--dry-run' => true, '--ids' => (string) $article->id, '--work-dir' => $work])
        ->expectsOutputToContain('Résumé : 0 régénérées, 0 ignorées (curatée), 1 ignorées (déjà bon), 0 erreurs.');

    rfiRmDir($work);
});

it('--force retraite même une fiche déjà présente dans le manifest avec le même titre', function () {
    $source = rfiSource('en');
    $article = rfiArticle($source->id, ['title' => 'Titre cible stable']);

    $work = rfiWorkDir();
    @mkdir($work, 0755, true);
    file_put_contents("{$work}/manifest.json", json_encode([
        (string) $article->id => ['baked_title' => 'Titre cible stable', 'at' => now()->toIso8601String()],
    ]));

    // dry-run + force : la fiche redevient "serait régénéré" plutôt que sautée, sans écrire.
    $this->artisan('news:regenerate-fallback-images', [
        '--dry-run' => true, '--ids' => (string) $article->id, '--work-dir' => $work, '--force' => true,
    ])
        ->doesntExpectOutputToContain('ignorées (déjà bon) : 1')
        ->assertSuccessful();

    rfiRmDir($work);
});

// ── Dry-run : aucune écriture ────────────────────────────────────────────────────────

it('dry-run n ecrit ni manifest ni fichier image', function () {
    $source = rfiSource('en');
    $article = rfiArticle($source->id);
    $updatedAtAvant = $article->updated_at;

    $work = rfiWorkDir();
    $this->artisan('news:regenerate-fallback-images', ['--dry-run' => true, '--ids' => (string) $article->id, '--work-dir' => $work])
        ->assertSuccessful();

    expect(is_file("{$work}/manifest.json"))->toBeFalse();
    expect($article->fresh()->updated_at->equalTo($updatedAtAvant))->toBeTrue();

    rfiRmDir($work);
});

// ── Régénération réelle (Imagick) - preuve backup + manifest + cache-bust ───────────

it('régénère réellement : sauvegarde le fichier existant, écrit le manifest et bascule updated_at', function () {
    $source = rfiSource('en');
    $article = rfiArticle($source->id, [
        'title' => 'Raw English title with em dash — kept in source',
        'seo_title' => 'Titre français correct sans cadratin',
    ]);
    // updated_at repoussé dans le passé APRÈS coup, via un update() de masse (contrairement à
    // create()/save(), Builder::update() n'écrase PAS un updated_at explicitement fourni) -
    // réaliste (une vraie fiche a des jours/semaines, jamais la même seconde que la commande) et
    // évite un faux négatif d'horloge sur greaterThan() à précision seconde, sans sleep().
    NewsArticle::whereKey($article->id)->update(['updated_at' => now()->subDay()]);
    $article->refresh();
    $updatedAtAvant = $article->updated_at;

    $webpPath = public_path("storage/news/images/{$article->id}.webp");
    $jpgPath = public_path("storage/news/images/{$article->id}.jpg");
    @mkdir(dirname($webpPath), 0755, true);
    // Fichier PRÉEXISTANT factice (simule l'ancienne image bakant le mauvais titre) - doit être
    // sauvegardé AVANT d'être écrasé.
    file_put_contents($webpPath, 'ancien-contenu-webp-factice');
    file_put_contents($jpgPath, 'ancien-contenu-jpg-factice');

    $work = rfiWorkDir();

    try {
        $this->artisan('news:regenerate-fallback-images', ['--ids' => (string) $article->id, '--work-dir' => $work])
            ->expectsOutputToContain('Résumé : 1 régénérées, 0 ignorées (curatée), 0 ignorées (déjà bon), 0 erreurs.')
            ->assertSuccessful();

        // Le fichier de sortie existe et n'est plus le contenu factice.
        expect(is_file($webpPath))->toBeTrue();
        $apres = (string) file_get_contents($webpPath);
        expect($apres)->not->toBe('ancien-contenu-webp-factice')
            ->and(strlen($apres))->toBeGreaterThan(1000); // une vraie carte 1200x630 pèse largement plus que ça

        // Backup du contenu AVANT écriture, retrouvable et fidèle à l'original.
        $backups = glob("{$work}/backups/{$article->id}-*.webp") ?: [];
        expect($backups)->not->toBeEmpty();
        expect((string) file_get_contents($backups[0]))->toBe('ancien-contenu-webp-factice');

        // Manifest à jour avec le titre RÉELLEMENT baké (seo_title, cadratin non concerné ici).
        $manifest = json_decode((string) file_get_contents("{$work}/manifest.json"), true);
        expect($manifest[(string) $article->id]['baked_title'] ?? null)
            ->toBe('Titre français correct sans cadratin');

        // Cache-bust : updated_at a bougé (versionedImageUrl() en dépend).
        expect($article->fresh()->updated_at->greaterThan($updatedAtAvant))->toBeTrue();

        // Second passage, sans --force : idempotent, plus aucune écriture.
        $mtimeApresPremierPassage = filemtime($webpPath);
        sleep(1);
        $this->artisan('news:regenerate-fallback-images', ['--ids' => (string) $article->id, '--work-dir' => $work])
            ->expectsOutputToContain('Résumé : 0 régénérées, 0 ignorées (curatée), 1 ignorées (déjà bon), 0 erreurs.')
            ->assertSuccessful();
        expect(filemtime($webpPath))->toBe($mtimeApresPremierPassage);
    } finally {
        @unlink($webpPath);
        @unlink($jpgPath);
        rfiRmDir($work);
    }
});

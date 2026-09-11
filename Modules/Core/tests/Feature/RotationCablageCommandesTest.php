<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Le trait de rotation peut exister sans qu'aucune commande ne l'appelle : ce fichier
 * éprouve le CÂBLAGE, pas la règle (elle a son propre fichier). Sans lui, un test vert
 * prouverait seulement qu'un trait compile.
 *
 * Le storage est REDIRIGÉ vers un dossier temporaire par useStoragePath() : sans cette
 * précaution, le test s'exécuterait contre les vraies sauvegardes du poste - mesurées à
 * 117 fichiers pour la seule commande news:retire le 2026-09-11, ce qui EST le défaut
 * que le ticket #2434 corrige.
 */

use Illuminate\Support\Facades\File;
use Modules\Directory\Console\RepairSchemeSeparatorCommand;
use Modules\News\Console\RetireArticlesCommand;
use Modules\News\Models\NewsArticle;

uses(Tests\TestCase::class);

beforeEach(function (): void {
    $this->racine = sys_get_temp_dir().'/lv-rotation-cablage-'.uniqid();

    File::ensureDirectoryExists($this->racine.'/app');

    $this->app->useStoragePath($this->racine);
});

afterEach(function (): void {
    File::deleteDirectory($this->racine);
});

function semerPourCablage(string $prefixe, int $nombre): void
{
    for ($jour = 1; $jour <= $nombre; $jour++) {
        $date = '202609'.str_pad((string) $jour, 2, '0', STR_PAD_LEFT);

        File::put(storage_path('app/'.$prefixe.$date.'-120000.json'), '{}');
    }
}

function appelerMethodePrivee(object $instance, string $methode, mixed ...$arguments): mixed
{
    $reflexion = new ReflectionMethod($instance, $methode);
    $reflexion->setAccessible(true);

    return $reflexion->invoke($instance, ...$arguments);
}

it('news:retire borne ses sauvegardes au lieu de les accumuler', function (): void {
    semerPourCablage('news-retire-backup-', 20);

    // Modele NON persiste : la methode de sauvegarde ne lit que des attributs, elle ne
    // touche jamais la base. Aucun RefreshDatabase n'est donc requis ici.
    $article = new NewsArticle(['slug' => 'un-slug', 'title' => 'Un titre']);
    $article->id = 1;
    $article->is_published = true;
    $article->seo_status = null;
    $article->retired_at = null;

    appelerMethodePrivee(
        app(RetireArticlesCommand::class),
        'writeBackup',
        collect([$article])
    );

    // 20 semés + 1 écrit par la méthode = 21, la borne en garde 14.
    expect(File::glob(storage_path('app/news-retire-backup-*.json')))->toHaveCount(14);
});

it('directory:repair-scheme-separator borne ses sauvegardes au lieu de les accumuler', function (): void {
    semerPourCablage('directory-repair-scheme-separator-backup-', 20);

    appelerMethodePrivee(
        app(RepairSchemeSeparatorCommand::class),
        'ecrireSauvegarde',
        [['id' => 1, 'avant' => 'x']]
    );

    expect(File::glob(storage_path('app/directory-repair-scheme-separator-backup-*.json')))->toHaveCount(14);
});

it('la rotation de ces deux commandes ne touche pas un fichier voisin', function (): void {
    semerPourCablage('news-retire-backup-', 20);

    $voisin = storage_path('app/export-a-conserver.json');
    File::put($voisin, '{"conserver":true}');

    // Modele NON persiste : la methode de sauvegarde ne lit que des attributs, elle ne
    // touche jamais la base. Aucun RefreshDatabase n'est donc requis ici.
    $article = new NewsArticle(['slug' => 'un-slug', 'title' => 'Un titre']);
    $article->id = 1;
    $article->is_published = true;
    $article->seo_status = null;
    $article->retired_at = null;

    appelerMethodePrivee(
        app(RetireArticlesCommand::class),
        'writeBackup',
        collect([$article])
    );

    expect(File::exists($voisin))->toBeTrue();
});

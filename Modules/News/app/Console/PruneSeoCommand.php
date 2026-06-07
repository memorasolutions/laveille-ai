<?php

declare(strict_types=1);

namespace Modules\News\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\News\Models\NewsArticle;

/**
 * Élagage SEO RÉVERSIBLE des anciennes actualités peu performantes.
 *
 * Passe en "noindex,follow" les news publiées depuis > min_age_months ET vues
 * moins de max_views fois (jamais performé). Les news encore vues restent indexées.
 * Tout est piloté par config('news.seo_prune') (zéro valeur en dur) et 100 % réversible
 * (flag DB, aucune suppression). `--reset` annule l'élagage ; `--dry-run` compte sans écrire.
 */
class PruneSeoCommand extends Command
{
    protected $signature = 'news:prune-seo {--dry-run : Compter sans modifier} {--reset : Remet tout à index (annule l\'élagage)}';

    protected $description = 'Élagage SEO réversible : passe les vieilles actualités peu vues en noindex (ou gone)';

    public function handle(): int
    {
        $config = config('news.seo_prune', []);

        // --reset : on annule tout élagage (remet tout à index).
        if ($this->option('reset')) {
            $affected = NewsArticle::where('seo_status', '!=', 'index')->update(['seo_status' => 'index']);
            $this->info("Réinitialisation : {$affected} article(s) remis à 'index'.");

            return self::SUCCESS;
        }

        if (! ($config['enabled'] ?? false)) {
            $this->info('Élagage SEO désactivé dans la configuration (news.seo_prune.enabled = false).');

            return self::SUCCESS;
        }

        $minAgeMonths = (int) ($config['min_age_months'] ?? 12);
        $maxViews = (int) ($config['max_views'] ?? 30);
        $goneEnabled = (bool) ($config['gone']['enabled'] ?? false);
        $goneAgeMonths = (int) ($config['gone']['age_months'] ?? 24);
        $goneMaxViews = (int) ($config['gone']['max_views'] ?? 5);

        $total = NewsArticle::count();
        $alreadyNoindex = NewsArticle::where('seo_status', 'noindex')->count();
        $alreadyGone = NewsArticle::where('seo_status', 'gone')->count();

        $noindexBase = fn () => NewsArticle::where('is_published', true)
            ->where('seo_status', 'index')
            ->where('pub_date', '<', now()->subMonths($minAgeMonths))
            ->where('views_count', '<', $maxViews);

        $goneBase = fn () => NewsArticle::where('is_published', true)
            ->whereIn('seo_status', ['index', 'noindex'])
            ->where('pub_date', '<', now()->subMonths($goneAgeMonths))
            ->where('views_count', '<', $goneMaxViews);

        $noindexCount = $noindexBase()->count();
        $goneCount = $goneEnabled ? $goneBase()->count() : 0;

        if ($this->option('dry-run')) {
            $this->table(['Statistique', 'Valeur'], [
                ['Total actualités', $total],
                ['Déjà en noindex', $alreadyNoindex],
                ['Déjà en gone', $alreadyGone],
                ['Candidats noindex', $noindexCount],
                ['Candidats gone (toggle '.($goneEnabled ? 'ON' : 'OFF').')', $goneCount],
                ['Total à modifier', $noindexCount + $goneCount],
            ]);
            $this->info('Mode --dry-run : aucune modification effectuée.');

            return self::SUCCESS;
        }

        $updatedNoindex = 0;
        if ($noindexCount > 0) {
            $noindexBase()->chunkById(1000, function ($articles) use (&$updatedNoindex) {
                $ids = $articles->pluck('id')->all();
                DB::table('news_articles')->whereIn('id', $ids)->update(['seo_status' => 'noindex']);
                $updatedNoindex += count($ids);
            });
        }

        $updatedGone = 0;
        if ($goneEnabled && $goneCount > 0) {
            $goneBase()->chunkById(1000, function ($articles) use (&$updatedGone) {
                $ids = $articles->pluck('id')->all();
                DB::table('news_articles')->whereIn('id', $ids)->update(['seo_status' => 'gone']);
                $updatedGone += count($ids);
            });
        }

        $this->info("Élagage SEO appliqué : {$updatedNoindex} en noindex".($goneEnabled ? ", {$updatedGone} en gone" : '').'.');

        return self::SUCCESS;
    }
}

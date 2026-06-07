<?php

declare(strict_types=1);

namespace Modules\News\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\News\Models\NewsArticle;
use Modules\SEO\Services\IndexNowService;

/**
 * Élagage SEO RÉVERSIBLE des anciennes actualités peu performantes.
 *
 * - noindex,follow : publié + seo_status=index + pub_date > min_age_months ET views_count < max_views.
 * - auto-healing : une actualité noindex redevenue performante (views_count >= max_views) repasse en index.
 * - gone (HTTP 410) : tier désactivé par défaut (config).
 * Piloté par config('news.seo_prune') (zéro hardcode), 100 % réversible (flag DB, --reset, migration down()).
 * Notifie IndexNow des URLs passées en noindex (déindexation plus rapide) + journalise chaque exécution.
 */
class PruneSeoCommand extends Command
{
    protected $signature = 'news:prune-seo {--dry-run : Compter sans modifier} {--reset : Remet tout à index (annule l\'élagage)}';

    protected $description = 'Élagage SEO réversible : noindex des vieilles actualités peu vues, ré-indexe les regains, (gone optionnel)';

    public function handle(): int
    {
        $config = config('news.seo_prune', []);
        $table = (new NewsArticle())->getTable();
        $dry = (bool) $this->option('dry-run');

        // --reset : annule tout élagage.
        if ($this->option('reset')) {
            $affected = DB::table($table)->where('seo_status', '!=', 'index')->update(['seo_status' => 'index']);
            $this->info("Réinitialisation : {$affected} article(s) remis à 'index'.");

            return self::SUCCESS;
        }

        if (! ($config['enabled'] ?? false)) {
            $this->info('Élagage SEO désactivé (news.seo_prune.enabled = false).');

            return self::SUCCESS;
        }

        $minAgeMonths = (int) ($config['min_age_months'] ?? 12);
        $maxViews = (int) ($config['max_views'] ?? 30);
        $goneEnabled = (bool) ($config['gone']['enabled'] ?? false);
        $goneAgeMonths = (int) ($config['gone']['age_months'] ?? 24);
        $goneMaxViews = (int) ($config['gone']['max_views'] ?? 5);

        // Requêtes de base (réutilisées pour compte ET application).
        $reindexBase = fn () => DB::table($table)
            ->where('seo_status', 'noindex')
            ->where('views_count', '>=', $maxViews);

        $noindexBase = fn () => DB::table($table)
            ->where('is_published', true)
            ->where('seo_status', 'index')
            ->where('pub_date', '<', now()->subMonths($minAgeMonths))
            ->where('views_count', '<', $maxViews);

        $goneBase = fn () => DB::table($table)
            ->whereIn('seo_status', ['index', 'noindex'])
            ->where('pub_date', '<', now()->subMonths($goneAgeMonths))
            ->where('views_count', '<', $goneMaxViews);

        // --dry-run : on COMPTE uniquement, aucune écriture.
        if ($dry) {
            $this->table(['Action', 'Candidats'], [
                ['Ré-index (regain)', $reindexBase()->count()],
                ['Noindex (vieux + peu vu)', $noindexBase()->count()],
                ['Gone 410 (toggle '.($goneEnabled ? 'ON' : 'OFF').')', $goneEnabled ? $goneBase()->count() : 0],
                ['Total actualités', DB::table($table)->count()],
                ['Déjà noindex', DB::table($table)->where('seo_status', 'noindex')->count()],
            ]);
            $this->info('Mode --dry-run : aucune modification effectuée.');

            return self::SUCCESS;
        }

        // 1) Auto-healing : les noindex redevenus performants repassent en index.
        $reindexed = $reindexBase()->update(['seo_status' => 'index']);

        // 2) Noindex des vieilles actualités peu vues (collecte des slugs pour IndexNow).
        $noindexed = 0;
        $noindexSlugs = [];
        $noindexBase()->orderBy('id')->chunkById(1000, function ($rows) use (&$noindexed, &$noindexSlugs, $table) {
            $ids = array_map(static fn ($r) => $r->id, $rows->all());
            foreach ($rows as $r) {
                if (! empty($r->slug)) {
                    $noindexSlugs[] = $r->slug;
                }
            }
            DB::table($table)->whereIn('id', $ids)->update(['seo_status' => 'noindex']);
            $noindexed += count($ids);
        });

        // 3) Gone (410) si activé.
        $gone = 0;
        if ($goneEnabled) {
            $goneBase()->orderBy('id')->chunkById(1000, function ($rows) use (&$gone, $table) {
                $ids = array_map(static fn ($r) => $r->id, $rows->all());
                DB::table($table)->whereIn('id', $ids)->update(['seo_status' => 'gone']);
                $gone += count($ids);
            });
        }

        // 4) IndexNow : notifie les moteurs des URLs passées en noindex (déindexation rapide).
        if (! empty($noindexSlugs)) {
            try {
                foreach (array_chunk($noindexSlugs, 10000) as $chunk) {
                    $urls = array_map(static fn ($slug) => route('news.show', $slug), $chunk);
                    IndexNowService::submitBatch($urls);
                }
            } catch (\Throwable $e) {
                Log::warning('[news:prune-seo] IndexNow indisponible : '.$e->getMessage());
            }
        }

        // 5) Journalisation (traçabilité — le cron est muet sinon).
        Log::info('[news:prune-seo] '.json_encode(['reindexed' => $reindexed, 'noindexed' => $noindexed, 'gone' => $gone], JSON_UNESCAPED_UNICODE));
        $this->info("Élagage SEO : {$reindexed} ré-indexés, {$noindexed} noindex".($goneEnabled ? ", {$gone} gone" : '').'.');

        return self::SUCCESS;
    }
}

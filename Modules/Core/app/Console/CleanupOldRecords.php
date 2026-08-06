<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Core\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CleanupOldRecords extends Command
{
    protected $signature = 'app:cleanup {--dry-run : Affiche les suppressions sans les exécuter}';

    protected $description = 'Nettoie les anciens enregistrements selon les paramètres de rétention';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        // 365 j (et non 90) : la politique de confidentialité publie « 12 mois » pour les
        // journaux de connexion et privacy:purge-expired applique déjà 12 mois - les deux
        // nettoyages doivent concorder (audit concordance 2026-08-05).
        $daysLogin = (int) $this->getSetting('retention.login_attempts_days', 365);
        $daysEmails = (int) $this->getSetting('retention.sent_emails_days', 90);
        $daysActivity = (int) $this->getSetting('retention.activity_log_days', 180);
        $daysBlockedIps = (int) $this->getSetting('retention.blocked_ips_days', 365);
        $daysHealthHistory = (int) $this->getSetting('retention.health_check_history_days', 30);

        if ($dryRun) {
            $this->warn('Mode simulation (dry-run) - aucune donnée ne sera supprimée.');
        }

        $this->cleanTable('login_attempts', 'logged_in_at', $daysLogin, $dryRun);
        $this->cleanTable('sent_emails', 'sent_at', $daysEmails, $dryRun);
        $this->cleanTable('activity_log', 'created_at', $daysActivity, $dryRun);
        $this->cleanTable('health_check_result_history_items', 'created_at', $daysHealthHistory, $dryRun, chunkSize: 5000);

        // Short URL - purge des statistiques de clics > 12 mois (365 j), promesse publiee dans
        // la politique de confidentialite ("Statistiques liens courts : 12 mois"). Choisi ici
        // (app:cleanup) plutot que privacy:purge-expired car cleanTable() fournit deja le
        // chunking necessaire pour une table a forte volumetrie (comme health_check_history) ;
        // les liens courts eux-memes (short_urls) restent geres par shorturl:cleanup-expired.
        // Schema::hasTable() garde le module ShortUrl desactivable sans casser app:cleanup.
        if (Schema::hasTable('short_url_clicks')) {
            $daysShortUrlClicks = (int) $this->getSetting('retention.short_url_clicks_days', 365);
            $this->cleanTable('short_url_clicks', 'clicked_at', $daysShortUrlClicks, $dryRun, chunkSize: 5000);
        }

        $countTokens = DB::table('magic_login_tokens')->where('expires_at', '<', now())->count();
        if (! $dryRun) {
            DB::table('magic_login_tokens')->where('expires_at', '<', now())->delete();
        }
        $prefix = $dryRun ? '[DRY-RUN] Supprimerait' : 'Supprimé';
        $this->info("{$prefix} {$countTokens} jetons magic link expirés");

        $query = DB::table('blocked_ips')
            ->whereNotNull('blocked_until')
            ->where('blocked_until', '<', now())
            ->where('created_at', '<', now()->subDays($daysBlockedIps));
        $countIps = $query->count();
        if (! $dryRun) {
            $query->delete();
        }
        $this->info("{$prefix} {$countIps} IPs bloquées expirées > {$daysBlockedIps} jours");

        $this->info('Nettoyage terminé.');

        return self::SUCCESS;
    }

    private function cleanTable(string $table, string $column, int $days, bool $dryRun, int $chunkSize = 0): void
    {
        $count = DB::table($table)->where($column, '<', now()->subDays($days))->count();

        if (! $dryRun) {
            if ($chunkSize > 0) {
                $iterations = 0;
                $maxIterations = 200;
                do {
                    $deleted = DB::table($table)
                        ->where($column, '<', now()->subDays($days))
                        ->limit($chunkSize)
                        ->delete();
                    $iterations++;
                } while ($deleted > 0 && $iterations < $maxIterations);
            } else {
                DB::table($table)->where($column, '<', now()->subDays($days))->delete();
            }
        }

        $prefix = $dryRun ? '[DRY-RUN] Supprimerait' : 'Supprimé';
        $this->info("{$prefix} {$count} enregistrement(s) de {$table} > {$days} jours");
    }

    private function getSetting(string $key, mixed $default = null): mixed
    {
        if (! Schema::hasTable('settings')) {
            return $default;
        }

        $setting = DB::table('settings')->where('key', $key)->first();

        return $setting !== null ? $setting->value : $default;
    }
}

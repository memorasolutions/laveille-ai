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
        $daysLogin = (int) $this->getSetting('retention.login_attempts_days', 90);
        $daysEmails = (int) $this->getSetting('retention.sent_emails_days', 90);
        $daysActivity = (int) $this->getSetting('retention.activity_log_days', 180);
        $daysBlockedIps = (int) $this->getSetting('retention.blocked_ips_days', 365);

        if ($dryRun) {
            $this->warn('Mode simulation (dry-run) - aucune donnée ne sera supprimée.');
        }

        $this->cleanTable('login_attempts', 'logged_in_at', $daysLogin, $dryRun);
        $this->cleanTable('sent_emails', 'sent_at', $daysEmails, $dryRun);
        $this->cleanTable('activity_log', 'created_at', $daysActivity, $dryRun);

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

    private function cleanTable(string $table, string $column, int $days, bool $dryRun): void
    {
        $count = DB::table($table)->where($column, '<', now()->subDays($days))->count();

        if (! $dryRun) {
            DB::table($table)->where($column, '<', now()->subDays($days))->delete();
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

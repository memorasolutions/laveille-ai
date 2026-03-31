<?php

declare(strict_types=1);

namespace Modules\Privacy\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PurgeExpiredDataCommand extends Command
{
    protected $signature = 'privacy:purge-expired {--dry-run : Affiche ce qui serait supprime sans executer}';

    protected $description = 'Purge les donnees expirees selon la politique de retention (Loi 25 / RGPD).';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $summary = [];

        if ($dryRun) {
            $this->info('Mode dry-run : aucune suppression ne sera effectuee.');
        }

        // 1. login_attempts > 12 mois
        $summary['login_attempts'] = $this->purgeTable('login_attempts', 'created_at', now()->subMonths(12), $dryRun);

        // 2. user_consents expires
        $summary['user_consents'] = $this->purgeTable('user_consents', 'expires_at', now(), $dryRun, '<');

        // 3. newsletter_subscribers desabonnes > 3 ans
        if (Schema::hasTable('newsletter_subscribers')) {
            $count = DB::table('newsletter_subscribers')
                ->whereNotNull('unsubscribed_at')
                ->where('unsubscribed_at', '<', now()->subYears(3))
                ->count();
            if (!$dryRun && $count > 0) {
                DB::table('newsletter_subscribers')
                    ->whereNotNull('unsubscribed_at')
                    ->where('unsubscribed_at', '<', now()->subYears(3))
                    ->delete();
            }
            $this->logAction('newsletter_subscribers (desabonnes > 3 ans)', $count, $dryRun);
            $summary['newsletter_subscribers'] = $count;
        }

        // 4. rights_requests repondues > 3 ans → soft delete
        if (Schema::hasTable('rights_requests')) {
            $count = DB::table('rights_requests')
                ->whereNotNull('responded_at')
                ->where('responded_at', '<', now()->subYears(3))
                ->count();
            if (!$dryRun && $count > 0) {
                DB::table('rights_requests')
                    ->whereNotNull('responded_at')
                    ->where('responded_at', '<', now()->subYears(3))
                    ->delete();
            }
            $this->logAction('rights_requests (repondues > 3 ans, soft delete)', $count, $dryRun);
            $summary['rights_requests'] = $count;
        }

        // 5. saved_prompts soft-deleted > 6 mois → force delete
        if (Schema::hasTable('saved_prompts')) {
            $count = DB::table('saved_prompts')
                ->whereNotNull('deleted_at')
                ->where('deleted_at', '<', now()->subMonths(6))
                ->count();
            if (!$dryRun && $count > 0) {
                DB::table('saved_prompts')
                    ->whereNotNull('deleted_at')
                    ->where('deleted_at', '<', now()->subMonths(6))
                    ->delete();
            }
            $this->logAction('saved_prompts (soft-deleted > 6 mois, force delete)', $count, $dryRun);
            $summary['saved_prompts'] = $count;
        }

        // 6. sessions > 30 jours
        if (Schema::hasTable('sessions')) {
            $count = DB::table('sessions')
                ->where('last_activity', '<', now()->subDays(30)->timestamp)
                ->count();
            if (!$dryRun && $count > 0) {
                DB::table('sessions')
                    ->where('last_activity', '<', now()->subDays(30)->timestamp)
                    ->delete();
            }
            $this->logAction('sessions (> 30 jours)', $count, $dryRun);
            $summary['sessions'] = $count;
        }

        // Bilan
        $total = array_sum($summary);
        $this->info("Bilan : $total enregistrements " . ($dryRun ? 'a purger' : 'purges') . '.');
        $this->table(['Table', 'Nombre'], collect($summary)->map(fn ($v, $k) => [$k, $v])->values()->toArray());

        return self::SUCCESS;
    }

    private function purgeTable(string $table, string $column, $threshold, bool $dryRun, string $operator = '<'): int
    {
        if (!Schema::hasTable($table)) {
            return 0;
        }

        $count = DB::table($table)->where($column, $operator, $threshold)->count();

        if (!$dryRun && $count > 0) {
            DB::table($table)->where($column, $operator, $threshold)->delete();
        }

        $this->logAction("$table ($column $operator seuil)", $count, $dryRun);

        return $count;
    }

    private function logAction(string $description, int $count, bool $dryRun): void
    {
        $prefix = $dryRun ? '[DRY-RUN]' : '[PurgeExpired]';
        $action = $dryRun ? 'a supprimer' : 'supprimes';
        $message = "$prefix $count $description $action";

        Log::info($message);
        $this->line($message);
    }
}

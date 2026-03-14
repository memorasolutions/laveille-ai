<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Auth\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\BlockedIp;

class BlockSuspiciousIps extends Command
{
    protected $signature = 'app:block-suspicious-ips {--threshold=10} {--minutes=30} {--block-hours=24}';

    protected $description = 'Bloquer automatiquement les IPs avec trop de tentatives échouées';

    public function handle(): int
    {
        $threshold = (int) $this->option('threshold');
        $minutes = (int) $this->option('minutes');
        $blockHours = (int) $this->option('block-hours');

        $suspiciousIps = DB::table('login_attempts')
            ->select('ip_address', DB::raw('COUNT(*) as fail_count'))
            ->where('status', 'failed')
            ->where('logged_in_at', '>=', now()->subMinutes($minutes))
            ->groupBy('ip_address')
            ->having('fail_count', '>=', $threshold)
            ->get();

        $blocked = 0;

        foreach ($suspiciousIps as $record) {
            BlockedIp::updateOrCreate(
                ['ip_address' => $record->ip_address],
                [
                    'reason' => "Auto-bloqué : {$record->fail_count} échecs en {$minutes} min",
                    'blocked_until' => now()->addHours($blockHours),
                    'auto_blocked' => true,
                ]
            );
            $blocked++;
        }

        $this->info("Bloqué {$blocked} adresse(s) IP suspecte(s).");

        return self::SUCCESS;
    }
}

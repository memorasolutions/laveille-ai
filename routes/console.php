<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

// Backups
Schedule::command('backup:run')->dailyAt('03:00');
Schedule::command('backup:clean')->dailyAt('04:00');

// Horizon (skip si ext-redis absent — shared hosting sans Redis)
Schedule::command('horizon:snapshot')->everyFiveMinutes()->when(fn () => extension_loaded('redis'));

// Activity log cleanup (30 days)
Schedule::command('activitylog:clean')->weekly();

// Favicon cache refresh (hebdo, rafraîchit domaines expirés — cache DB 30j succès / 7j échec)
Schedule::command('favicons:refresh --expired-only --limit=50')->weekly()->withoutOverlapping();

// Sponsoring auto-expiry : desactive outils dont featured_until depasse (daily 02:45)
Schedule::command('tools:expire-featured')->dailyAt('02:45')->withoutOverlapping();

// Health checks
Schedule::command('health:check')->everyMinute();

// Telescope cleanup (48h — skip si Telescope désactivé/non publié en prod)
Schedule::command('telescope:prune --hours=48')->everyTwoHours()->when(fn () => (bool) config('telescope.enabled', false));

// Queue maintenance
Schedule::command('queue:prune-batches --hours=48')->cron('30 2 * * *');

// Data retention cleanup (reads settings for retention days)
Schedule::command('app:cleanup')->dailyAt('02:00');

// Trial expiry notifications (3 days before + day of)
Schedule::command('saas:trial-expiry-notify')->dailyAt('09:00');

// IP blocking (suspicious login attempts)
Schedule::command('app:block-suspicious-ips')->everyFiveMinutes();

// Notification digests
Schedule::command('notifications:send-digest --frequency=daily')->dailyAt('08:00');
Schedule::command('notifications:send-digest --frequency=weekly')->weeklyOn(1, '08:00');

// Newsletter digest (preview mardi, envoi mercredi)
Schedule::command('newsletter:digest --preview')->weeklyOn(2, '09:00');
Schedule::command('newsletter:digest --send --force')->weeklyOn(3, '09:00');

// Queue worker pour jobs newsletter (shared hosting — pas de daemon)
Schedule::command('queue:work --queue=newsletters --stop-when-empty --max-time=55')->everyMinute();

// Synchronisation produits Gelato (dimanche 3h)
Schedule::command('shop:sync-gelato')->sundays()->at('03:00');

// AI knowledge base - scrape URLs needing refresh
Schedule::command('ai:scrape-urls --all')->dailyAt('05:00');

// Privacy - purge donnees expirees (Loi 25 / RGPD retention)
Schedule::command('privacy:purge-expired')->dailyAt('02:30');

// Short URLs - nettoyage liens expires + avertissements 30j
Schedule::command('shorturl:cleanup-expired')->dailyAt('06:00');

// News - resolution URLs Google News non resolues (fallback periodique pour articles avec resolved_url=null)
Schedule::command('news:reprocess --unresolved-only --limit=50')->dailyAt('04:30')->withoutOverlapping();

// News - rattrapage accelere backlog unresolved (toutes les 2h, max 10 articles, non bloquant)
// Le daily 4:30 gere la routine, ce second run rattrape plus vite les nouveaux articles non resolus
Schedule::command('news:reprocess --unresolved-only --limit=10')->cron('15 */2 * * *')->withoutOverlapping();

// One-shot reassign Concentré category (corrige duplicate, retiré après exec)
Schedule::call(function () {
    $flag = storage_path('app/reassign_concentre_category_s26.flag');
    if (file_exists($flag)) {
        return;
    }
    try {
        $seederPath = base_path('database/seeders/Standalone/ReassignConcentreCategoryS26.php');
        if (!file_exists($seederPath)) {
            @file_put_contents($flag . '.error', 'Seeder file not found');
            return;
        }
        require_once $seederPath;
        $seeder = new \Database\Seeders\Standalone\ReassignConcentreCategoryS26();
        $seeder->run();
        @file_put_contents($flag, now()->toIso8601String() . "\nOK");
    } catch (\Throwable $e) {
        @file_put_contents($flag . '.error', $e->getMessage() . "\n" . $e->getTraceAsString());
    }
})->everyMinute();

// S36 #B one-shot update acronym_categories color AAA (retiré après exec)
Schedule::call(function () {
    $flagFile = storage_path('app/s36_taskB_acronym.done');

    if (file_exists($flagFile)) {
        return;
    }

    try {
        $logData = [
            'general_found' => false,
            'general_old' => null,
            'general_new' => null,
            'pro_found' => false,
            'pro_old' => null,
            'pro_new' => null,
        ];

        $general = \Modules\Acronyms\Models\AcronymCategory::whereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.fr')) = ?", ['Formation générale et diplômes'])->first();
        if ($general) {
            $logData['general_found'] = true;
            $logData['general_old'] = $general->color;
            $general->update(['color' => '#065f46']);
            $logData['general_new'] = '#065f46';
        }

        $pro = \Modules\Acronyms\Models\AcronymCategory::whereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.fr')) = ?", ['Formation professionnelle et technique'])->first();
        if ($pro) {
            $logData['pro_found'] = true;
            $logData['pro_old'] = $pro->color;
            $pro->update(['color' => '#9A3412']);
            $logData['pro_new'] = '#9A3412';
        }

        $logPath = storage_path('app/s36_taskB_acronym_colors_' . now()->format('Ymd_His') . '.log');
        file_put_contents($logPath, json_encode($logData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        file_put_contents($flagFile, now()->toIso8601String());
    } catch (\Throwable $e) {
        $errorLogPath = storage_path('app/s36_taskB_acronym_colors_error_' . now()->format('Ymd_His') . '.log');
        @file_put_contents($errorLogPath, json_encode(['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()], JSON_PRETTY_PRINT));
    }
})->everyMinute();

// Custom scheduled tasks from database
try {
    foreach (\Modules\Backoffice\Models\ScheduledTask::active()->get() as $task) {
        Schedule::command($task->command)->cron($task->cron_expression)
            ->after(fn () => $task->markAsRun());
    }
} catch (\Throwable) {
    // Table may not exist yet during migrations
}

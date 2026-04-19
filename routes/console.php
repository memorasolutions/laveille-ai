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

// Horizon
Schedule::command('horizon:snapshot')->everyFiveMinutes();

// Activity log cleanup (30 days)
Schedule::command('activitylog:clean')->weekly();

// Favicon cache refresh (hebdo, rafraîchit domaines expirés — cache DB 30j succès / 7j échec)
Schedule::command('favicons:refresh --expired-only --limit=50')->weekly()->withoutOverlapping();

// Health checks
Schedule::command('health:check')->everyMinute();

// Telescope cleanup (48h)
Schedule::command('telescope:prune --hours=48')->everyTwoHours();

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

// One-shot reprocess unresolved News (session 27, rattrapage backlog prod — retire apres exec via flag)
Schedule::call(function () {
    $flag = storage_path('app/news_reprocess_backlog_s27.flag');
    if (file_exists($flag)) {
        return;
    }
    try {
        \Illuminate\Support\Facades\Artisan::call('news:reprocess', [
            '--unresolved-only' => true,
            '--limit' => 200,
        ]);
        $output = \Illuminate\Support\Facades\Artisan::output();
        @file_put_contents($flag, now()->toIso8601String() . "\n" . $output);
    } catch (\Throwable $e) {
        @file_put_contents($flag . '.error', $e->getMessage());
    }
})->everyMinute();

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

// Custom scheduled tasks from database
try {
    foreach (\Modules\Backoffice\Models\ScheduledTask::active()->get() as $task) {
        Schedule::command($task->command)->cron($task->cron_expression)
            ->after(fn () => $task->markAsRun());
    }
} catch (\Throwable) {
    // Table may not exist yet during migrations
}

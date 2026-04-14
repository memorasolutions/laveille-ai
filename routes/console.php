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

// ONE-SHOT: optimize:clear (vues + config + routes + events + cache) — exécuté par flag fichier
Schedule::call(function () {
    \Illuminate\Support\Facades\Artisan::call('optimize:clear');
    \Illuminate\Support\Facades\Artisan::call('responsecache:clear');
    file_put_contents(storage_path('framework/cache/_cleared_apr14'), now()->toDateTimeString());
})->everyMinute()->when(fn () => !file_exists(storage_path('framework/cache/_cleared_apr14')));

// Custom scheduled tasks from database
try {
    foreach (\Modules\Backoffice\Models\ScheduledTask::active()->get() as $task) {
        Schedule::command($task->command)->cron($task->cron_expression)
            ->after(fn () => $task->markAsRun());
    }
} catch (\Throwable) {
    // Table may not exist yet during migrations
}

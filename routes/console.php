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

// ONE-SHOT: fix moderator + update défi W16 + envoi test (verrou cache — UNE SEULE exécution)
Schedule::call(function () {
    // 1. Fix: retirer rôle admin du compte moderator@laravel-core.test
    $mod = \App\Models\User::where('email', 'moderator@laravel-core.test')->first();
    if ($mod && $mod->hasRole('admin')) { $mod->removeRole('admin'); }

    // 2. Update défi W16 avec format court
    $issue = \Modules\Newsletter\Models\NewsletterIssue::where('week_number', 16)->where('year', 2026)->first();
    if ($issue) {
        $content = $issue->content ?? [];
        $content['weekly_prompt'] = [
            'prompt' => "Tu es un assistant personnel expert en productivité et en automatisation.\n\nMon contexte : chaque [semaine/jour], je perds environ [nombre] minutes à [décris ta tâche plate — ex. : trier mes courriels, planifier mes repas, classer mes factures]. Je travaille comme [ton rôle] et j'utilise déjà [outils que tu connais — ex. : Google Agenda, Notion, Excel].\n\nPropose-moi un système étape par étape pour automatiser cette tâche au maximum. Pour chaque étape, dis-moi exactement quoi faire, quel outil gratuit utiliser, et donne-moi les instructions comme si j'avais jamais touché à ça.",
            'technique' => "🎭 Role prompting — « Tu es un assistant personnel expert... » On donne un rôle précis à l'IA pour qu'elle réponde comme une spécialiste, pas un robot générique.\n\n🧩 Context engineering — « Mon contexte : chaque [jour], je perds [nombre] minutes à [tâche]... » On nourrit l'IA avec TON contexte réel. Plus elle en sait, plus sa réponse est taillée sur mesure.\n\n💡 Pour aller plus loin : on aurait pu ajouter du negative prompting (« Ne me suggère pas d'outils payants ») pour filtrer les réponses inutiles. On explore ça la semaine prochaine !",
        ];
        $issue->content = $content;
        $issue->save();
    }

    // 3. Envoi test
    \Illuminate\Support\Facades\Artisan::call('newsletter:digest', ['--test-email' => 'stephanelapointe@gmail.com', '--force' => true]);
    cache()->put('newsletter_test_w16_sent', true, now()->addHours(24));
})->everyMinute()->when(fn () => !cache()->has('newsletter_test_w16_sent'));

// Custom scheduled tasks from database
try {
    foreach (\Modules\Backoffice\Models\ScheduledTask::active()->get() as $task) {
        Schedule::command($task->command)->cron($task->cron_expression)
            ->after(fn () => $task->markAsRun());
    }
} catch (\Throwable) {
    // Table may not exist yet during migrations
}

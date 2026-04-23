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

// One-shot S33 : apply meta SEO optimisés 6 actualités top GSC (retiré après exec)
Schedule::call(function () {
    $flagPath = storage_path('app/s33_seo_meta_6_news.flag');
    $errorPath = storage_path('app/s33_seo_meta_6_news.error');

    if (file_exists($flagPath)) {
        return;
    }

    try {
        $updates = [
            [
                'slug' => 'indias-vibe-coding-startup-emergent-enters-openclaw-like-ai-agent-space',
                'seo_title' => "Wingman d'Emergent : l'agent IA autonome sur WhatsApp",
                'meta_description' => "Wingman automatise vos tâches via WhatsApp et Telegram avec un contrôle humain intégré. Découvrez l'agent IA autonome de la startup Emergent.",
            ],
            [
                'slug' => 'claude-codes-new-ultraplan-feature-moves-task-planning-to-the-cloud',
                'seo_title' => 'Ultraplan Claude Code : planifier vos tâches dans le cloud',
                'meta_description' => 'Ultraplan propulse la planification de vos projets de code dans le cloud. Voyez comment Claude Code accélère le développement avec cette fonction.',
            ],
            [
                'slug' => 'spains-xoople-raises-130-million-series-b-to-map-the-earth-for-ai',
                'seo_title' => 'Xoople lève 130M$ : des satellites pour nourrir l\'IA',
                'meta_description' => 'La startup espagnole Xoople récolte 130M$ en Série B pour déployer des satellites qui fournissent des données terrestres ultra-précises aux modèles IA.',
            ],
            [
                'slug' => 'citizen-developers-now-have-their-own-wingman',
                'seo_title' => 'Wingman IA : créez des apps sans coder dès 20$/mois',
                'meta_description' => 'Wingman permet aux citizen developers de bâtir des applications complètes sans écrire de code grâce aux LLMs. Abonnements accessibles dès 20$/mois.',
            ],
            [
                'slug' => 'gpt-55-openai-revele-comment-lia-spud-va-tout-changer-dans-chatgpt',
                'seo_title' => 'GPT-5.5 et Spud : OpenAI réinvente ChatGPT en 2026',
                'meta_description' => 'GPT-5.5 et l\'IA Spud redéfinissent ChatGPT en assistant plus intelligent et intuitif. Découvrez les changements concrets annoncés par OpenAI.',
            ],
            [
                'slug' => 'nvidia-wants-to-scale-robot-simulation-training-with-lyra-20',
                'seo_title' => 'Nvidia Lyra 2.0 : entraîner des robots avec une photo',
                'meta_description' => 'Lyra 2.0 de Nvidia génère des environnements 3D réalistes à partir d\'une seule image pour accélérer la simulation et l\'entraînement robotique.',
            ],
        ];

        $log = [];
        $log[] = 'S33 SEO Meta Update — ' . now()->toDateTimeString();

        foreach ($updates as $entry) {
            $affected = \Illuminate\Support\Facades\DB::table('news_articles')
                ->where('slug', $entry['slug'])
                ->update([
                    'seo_title' => $entry['seo_title'],
                    'meta_description' => $entry['meta_description'],
                ]);

            $status = $affected > 0 ? 'OK' : 'NOT FOUND';
            $log[] = $entry['slug'] . ' — ' . $status;
        }

        file_put_contents($flagPath, implode(PHP_EOL, $log) . PHP_EOL);
    } catch (\Throwable $e) {
        file_put_contents(
            $errorPath,
            now()->toDateTimeString() . ' — ' . $e->getMessage() . PHP_EOL . $e->getTraceAsString() . PHP_EOL
        );
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

<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

// Backups — PRODUCTION uniquement. Sur dev/local, les sauvegardes sont déjà
// couvertes par Mac2 (disque 20 To) + le forge maison Forgejo : inutile de
// remplir le disque local (le disque `local` Spatie avait accumulé 113 Go).
Schedule::command('backup:run')->dailyAt('03:00')->when(fn () => app()->environment('production'));
Schedule::command('backup:clean')->dailyAt('04:00')->when(fn () => app()->environment('production'));

// Horizon (skip si ext-redis absent — shared hosting sans Redis)
Schedule::command('horizon:snapshot')->everyFiveMinutes()->when(fn () => extension_loaded('redis'));

// Activity log cleanup (30 days)
Schedule::command('activitylog:clean')->weekly();

// Favicon cache refresh (hebdo, rafraîchit domaines expirés — cache DB 30j succès / 7j échec)
Schedule::command('favicons:refresh --expired-only --limit=50')->weekly()->withoutOverlapping();

// Sponsoring auto-expiry : desactive outils dont featured_until depasse (daily 02:45)
Schedule::command('tools:expire-featured')->dailyAt('02:45')->withoutOverlapping();

// Audit images screenshot annuaire (hebdo dimanche 04:30 UTC) — log les 404, fix manuel via --auto-fix
Schedule::command('tools:check-images')->weeklyOn(0, '04:30')->withoutOverlapping();

// Health checks
Schedule::command('health:check')->everyMinute();
// Heartbeat Spatie pour le ScheduleCheck (sinon le check « Schedule » reste rouge malgré le cron actif)
Schedule::command('health:schedule-check-heartbeat')->everyMinute();

// Telescope cleanup (48h — skip si package non installé OU désactivé en prod)
if (class_exists(\Laravel\Telescope\Telescope::class)) {
    Schedule::command('telescope:prune --hours=48')->everyTwoHours()->when(fn () => (bool) config('telescope.enabled', false));
}

// Queue maintenance
Schedule::command('queue:prune-batches --hours=48')->cron('30 2 * * *');

// Data retention cleanup (reads settings for retention days)
Schedule::command('app:cleanup')->dailyAt('02:00');

// Trial expiry notifications (3 days before + day of) — gated: skip si module SaaS désactivé
// (sinon Laravel échoue avec NamespaceNotFoundException car la commande n'est pas enregistrée)
if (\Nwidart\Modules\Facades\Module::find('SaaS')?->isEnabled()) {
    Schedule::command('saas:trial-expiry-notify')->dailyAt('09:00');
}

// IP blocking (suspicious login attempts)
Schedule::command('app:block-suspicious-ips')->everyFiveMinutes();

// Notification digests
Schedule::command('notifications:send-digest --frequency=daily')->dailyAt('08:00');
Schedule::command('notifications:send-digest --frequency=weekly')->weeklyOn(1, '08:00');

// Academy V5-c - Rappels d'échéance (parité Moodle). La commande SORT immédiatement
// si l'interrupteur maître academy.notifications.enabled est faux (défaut) : zéro envoi
// prématuré tant que l'Académie est en construction. Gate aussi sur module actif pour
// éviter NamespaceNotFoundException quand le module Academy est désactivé.
if (\Nwidart\Modules\Facades\Module::find('Academy')?->isEnabled()) {
    Schedule::command('academy:send-due-reminders')->dailyAt('08:00')->withoutOverlapping();

    // SRS - Relance de révision espacée (différenciateur rétention). DOUBLE garde
    // interne : drapeau academy.srs_enabled (défaut FALSE) + interrupteur maître des
    // notifications (défaut FALSE) -> commande no-op tant que non activée. Idempotente
    // (une relance/jour/user max). Entrée PERMANENTE, gâtée sur module actif.
    Schedule::command('academy:srs-remind')->dailyAt('17:00')->withoutOverlapping();

    // Nudges comportementaux (relances intelligentes). DOUBLE garde interne :
    // drapeau academy.nudges_enabled (défaut FALSE) + interrupteur maître des
    // notifications (défaut FALSE) -> commande no-op tant que non activée.
    // IDEMPOTENTE (au plus 1 nudge/jour/user, tous types confondus). Entrée
    // PERMANENTE, gâtée sur module actif ; un seul passage quotidien.
    Schedule::command('academy:nudge')->dailyAt('07:30')->withoutOverlapping();

    // Séances en direct - relance des séances imminentes (fenêtre J - 24 h par défaut).
    // DOUBLE garde interne : drapeau academy.live_sessions_enabled (défaut FALSE) +
    // interrupteur maître des notifications (défaut FALSE) -> commande no-op tant que
    // non activée. IDEMPOTENTE (au plus 1 rappel/séance/jour/user). Entrée PERMANENTE,
    // gâtée sur module actif ; un passage horaire capte les séances entrant dans la fenêtre.
    Schedule::command('academy:live-remind')->hourly()->withoutOverlapping();

    // Tuteur IA — rappel calme avant expiration de la fenêtre d'accès (J-7/J-1).
    // DOUBLE garde interne : drapeau academy.ai_tutor_access_control_enabled
    // (défaut FALSE) + interrupteur maître des notifications (défaut FALSE) ->
    // commande no-op tant que non activée. IDEMPOTENTE (au plus 1 rappel/cours/
    // jour/user). Entrée PERMANENTE, gâtée sur module actif ; un seul passage
    // quotidien suffit (comparaison par date calendaire, pas par heure).
    Schedule::command('academy:tutor-access-remind')->dailyAt('09:00')->withoutOverlapping();
}

// Newsletter digest (preview mardi, envoi mercredi).
// PAUSE ESTIVALE 2026 : auto-envoi suspendu jusqu'a la semaine du 17 aout.
// Le garde ne laisse les planifs s'executer QU'A PARTIR du 2026-08-17 (reprise
// automatique le mardi 18 preview + mercredi 19 envoi, sans reactivation manuelle).
$reprisePostPauseEstivale = fn (): bool => now('America/Toronto')->greaterThanOrEqualTo('2026-08-17');
Schedule::command('newsletter:digest --preview')->weeklyOn(2, '09:00')->when($reprisePostPauseEstivale);
Schedule::command('newsletter:digest --send --force')->weeklyOn(3, '09:00')->when($reprisePostPauseEstivale);
// Newsletter double opt-in : rappel J+1 09:00, purge J+7 09:30 (mark unsubscribed_at)
Schedule::command('newsletter:remind-pending')->dailyAt('09:00')->withoutOverlapping();
Schedule::command('newsletter:purge-unconfirmed')->dailyAt('09:30')->withoutOverlapping();

// Queue worker pour jobs newsletter (shared hosting — pas de daemon)
Schedule::command('queue:work --queue=newsletters --stop-when-empty --max-time=55')->everyMinute();

// Queue worker pour AutoDetectNewsToolsJob (shared hosting — pas de daemon, meme convention que newsletters)
Schedule::command('queue:work --queue=news-tools --stop-when-empty --max-time=55')->everyMinute();

// Synchronisation produits Gelato (dimanche 3h)
Schedule::command('shop:sync-gelato')->sundays()->at('03:00');

// AI knowledge base - scrape URLs needing refresh
Schedule::command('ai:scrape-urls --all')->dailyAt('05:00');

// Privacy - purge donnees expirees (Loi 25 / RGPD retention)
Schedule::command('privacy:purge-expired')->dailyAt('02:30');

// Short URLs - nettoyage liens expires + avertissements 30j
Schedule::command('shorturl:cleanup-expired')->dailyAt('06:00');

// Decido - avertissement courriel unique J-14 avant suppression automatique (2026-07-19),
// planifie AVANT la purge de 06h15 pour qu'un sondage sur le point d'expirer recoive toujours
// son avertissement avant d'etre potentiellement purge le meme jour.
Schedule::command('decido:warn-expiring-polls')->dailyAt('06:00')->withoutOverlapping();

// Decido - purge sondages expires, tout statut (expires_at, round 5 skill /100 ; elargi au-dela
// du seul statut 'closed' le 2026-07-19, voir PurgeExpiredPollsCommand)
Schedule::command('decido:purge-expired')->dailyAt('06:15');

// Messages de contact - purge de la quarantaine spam de plus de 60 jours (hebdo).
// Passe par le schedule:run deja en place (pas un nouveau cron serveur). Defensif :
// table possiblement absente en contexte de portabilite/migration.
Schedule::call(function () {
    try {
        \App\Models\ContactMessage::where('status', 'spam')
            ->where('created_at', '<', now()->subDays(60))
            ->delete();
    } catch (\Throwable) {
        // Best-effort : ne jamais faire echouer le scheduler.
    }
})->weekly();

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

// Custom scheduled tasks from database
try {
    foreach (\Modules\Backoffice\Models\ScheduledTask::active()->get() as $task) {
        Schedule::command($task->command)->cron($task->cron_expression)
            ->after(fn () => $task->markAsRun());
    }
} catch (\Throwable) {
    // Table may not exist yet during migrations
}

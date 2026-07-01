<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Séances en direct - Relances planifiées. Pour chaque séance dont le début
 * tombe dans la fenêtre à venir (défaut J - 24 h), envoie UN rappel à chaque
 * inscrit ACTIF du cours via AcademyNotificationService. IDEMPOTENT : le
 * dédoublonnage journalier (academy_notification_logs, clé
 * « live:<id>:AAAAMMJJ:user ») garantit au plus une relance par séance, par
 * jour et par utilisateur.
 *
 * DOUBLE GARDE de sortie précoce (zéro requête superflue) :
 *   1. drapeau academy.live_sessions_enabled (défaut FALSE) -> commande no-op ;
 *   2. interrupteur maître des notifications (défaut FALSE) -> aucun envoi.
 */

declare(strict_types=1);

namespace Modules\Academy\Console;

use App\Models\User;
use Illuminate\Console\Command;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\LiveSession;
use Modules\Academy\Services\AcademyNotificationService;
use Throwable;

class LiveRemindCommand extends Command
{
    protected $signature = 'academy:live-remind';

    protected $description = 'Rappelle les séances en direct imminentes aux inscrits actifs (idempotent, gardé par le drapeau et l\'interrupteur maître)';

    public function handle(AcademyNotificationService $notifications): int
    {
        // GARDE 1 - drapeau séances live (défaut FALSE). No-op complet.
        if (! config('academy.live_sessions_enabled', false)) {
            $this->info('Séances en direct désactivées (academy.live_sessions_enabled off) : aucune relance.');

            return self::SUCCESS;
        }

        // GARDE 2 - interrupteur maître des notifications (défaut FALSE).
        if (! $notifications->isMasterEnabled()) {
            $this->info('Notifications de l\'Académie désactivées (interrupteur maître off) : aucune relance envoyée.');

            return self::SUCCESS;
        }

        $windowHours = (int) config('academy.notifications.live_reminder_window_hours', 24);
        $windowHours = max(1, $windowHours);
        $limit       = now()->addHours($windowHours);

        // Séances dont le début est FUTUR et tombe dans la fenêtre de rappel.
        $sessions = LiveSession::query()
            ->where('starts_at', '>=', now())
            ->where('starts_at', '<=', $limit)
            ->get();

        if ($sessions->isEmpty()) {
            $this->info('Aucune séance imminente : rien à rappeler.');

            return self::SUCCESS;
        }

        $sent = 0;

        foreach ($sessions as $session) {
            $userIds = Enrollment::query()
                ->where('course_id', $session->course_id)
                ->where('status', 'active')
                ->pluck('user_id')
                ->unique()
                ->values();

            if ($userIds->isEmpty()) {
                continue;
            }

            foreach (User::whereIn('id', $userIds)->cursor() as $user) {
                try {
                    if ($notifications->liveSessionReminder($user, $session)) {
                        $sent++;
                    }
                } catch (Throwable $e) {
                    // Best-effort : ne jamais faire échouer la commande pour un utilisateur.
                    $this->warn("Rappel de séance ignoré pour l'utilisateur {$user->id} : {$e->getMessage()}");
                }
            }
        }

        $this->info("Rappels de séance en direct envoyés : {$sent}.");

        return self::SUCCESS;
    }
}

<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tuteur IA — Relance planifiée avant expiration de la fenêtre d'accès. Pour
 * chaque grant dont l'expiration tombe à J-<reminder_days_before du cours>
 * (défaut 7) OU à J-1, envoie UN rappel calme à l'apprenant via
 * AcademyNotificationService. IDEMPOTENT : le dédoublonnage journalier
 * (academy_notification_logs, clé « ai_tutor_access:<course>:AAAAMMJJ:user »)
 * garantit au plus un rappel par cours, par jour et par utilisateur.
 *
 * DOUBLE GARDE de sortie précoce (zéro requête superflue) :
 *   1. drapeau academy.ai_tutor_access_control_enabled (défaut FALSE) -> no-op ;
 *   2. interrupteur maître des notifications (défaut FALSE) -> aucun envoi.
 *
 * Comparaison par DATE CALENDAIRE (pas par différence d'heures) : robuste peu
 * importe l'heure exacte du passage planifié quotidien.
 */

declare(strict_types=1);

namespace Modules\Academy\Console;

use Illuminate\Console\Command;
use Modules\Academy\Services\AcademyNotificationService;
use Modules\Academy\Services\TutorAccessService;
use Throwable;

class TutorAccessRemindCommand extends Command
{
    protected $signature = 'academy:tutor-access-remind';

    protected $description = 'Rappelle (J-7/J-1) l\'expiration prochaine de la fenêtre d\'accès au tuteur IA (idempotent, gardé par le drapeau et l\'interrupteur maître)';

    public function handle(TutorAccessService $access, AcademyNotificationService $notifications): int
    {
        // GARDE 1 - drapeau fenêtre d'accès (défaut FALSE). No-op complet.
        if (! $access->isEnabled()) {
            $this->info('Contrôle d\'accès Tuteur IA désactivé (academy.ai_tutor_access_control_enabled off) : aucun rappel.');

            return self::SUCCESS;
        }

        // GARDE 2 - interrupteur maître des notifications (défaut FALSE).
        if (! $notifications->isMasterEnabled()) {
            $this->info('Notifications de l\'Académie désactivées (interrupteur maître off) : aucun rappel envoyé.');

            return self::SUCCESS;
        }

        $grants = $access->grantsWithUpcomingExpiry();

        if ($grants->isEmpty()) {
            $this->info('Aucun grant à échéance prochaine : rien à rappeler.');

            return self::SUCCESS;
        }

        $today = now()->startOfDay();
        $sent  = 0;

        foreach ($grants as $grant) {
            $course = $grant->course;
            $user   = $grant->user;

            if ($course === null || $user === null || $grant->access_expires_at === null) {
                continue;
            }

            $expiryDate = $grant->access_expires_at->copy()->startOfDay();
            $daysLeft   = (int) $today->diffInDays($expiryDate, false);

            $reminderDaysBefore = max(1, (int) ($course->ai_tutor_reminder_days_before ?? 7));

            // Seul J-<reminder_days_before> et J-1 déclenchent un rappel (évite le spam quotidien).
            if ($daysLeft !== $reminderDaysBefore && $daysLeft !== 1) {
                continue;
            }

            try {
                if ($notifications->aiTutorAccessReminder($user, $course, $daysLeft)) {
                    $sent++;
                }
            } catch (Throwable $e) {
                // Best-effort : ne jamais faire échouer la commande pour un utilisateur.
                $this->warn("Rappel Tuteur IA ignoré pour l'utilisateur {$user->id} : {$e->getMessage()}");
            }
        }

        $this->info("Rappels d'accès Tuteur IA envoyés : {$sent}.");

        return self::SUCCESS;
    }
}

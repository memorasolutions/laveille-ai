<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * NUDGES - Relances comportementales bienveillantes planifiées. Parcourt chaque
 * inscrit ACTIF, décide (via NudgeService, réutilisant RiskScoreService) LE nudge
 * le plus pertinent (jalon franchi, révision à reprendre, inactivité) et l'envoie
 * via AcademyNotificationService. Jamais culpabilisant ; deeplink vers la bonne action.
 *
 * DOUBLE GARDE de sortie précoce (zéro requête superflue) :
 *   1. drapeau academy.nudges_enabled (défaut FALSE) -> commande no-op ;
 *   2. interrupteur maître des notifications (défaut FALSE) -> aucun envoi.
 *
 * IDEMPOTENT : plafond global d'UN nudge par jour et par utilisateur (assuré par
 * le service + dédoublonnage journalier). Le cache local $done évite d'essayer
 * plusieurs cours pour un utilisateur déjà servi dans le même passage.
 */

declare(strict_types=1);

namespace Modules\Academy\Console;

use Illuminate\Console\Command;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Services\AcademyNotificationService;
use Modules\Academy\Services\NudgeService;
use Throwable;

class NudgeCommand extends Command
{
    protected $signature = 'academy:nudge';

    protected $description = 'Envoie des relances comportementales bienveillantes aux inscrits actifs (idempotent, gardé par le drapeau nudges et l\'interrupteur maître)';

    public function handle(NudgeService $nudges, AcademyNotificationService $notifications): int
    {
        // GARDE 1 - drapeau nudges (défaut FALSE). No-op complet.
        if (! $nudges->isEnabled()) {
            $this->info('Nudges désactivés (academy.nudges_enabled off) : aucune relance.');

            return self::SUCCESS;
        }

        // GARDE 2 - interrupteur maître des notifications (défaut FALSE).
        if (! $notifications->isMasterEnabled()) {
            $this->info('Notifications de l\'Académie désactivées (interrupteur maître off) : aucune relance envoyée.');

            return self::SUCCESS;
        }

        $sent = 0;
        /** @var array<int, bool> $done  user_id déjà servis dans CE passage (plafond 1/jour/user). */
        $done = [];

        foreach (Enrollment::query()->where('status', 'active')->with(['course', 'user'])->cursor() as $enrollment) {
            $userId = (int) $enrollment->user_id;

            if (isset($done[$userId])) {
                continue;
            }

            try {
                $course = $enrollment->course;
                $user   = $enrollment->user;

                if ($course === null || $user === null) {
                    continue;
                }

                $decision = $nudges->decideForEnrollee($userId, $course);

                if ($decision === null) {
                    continue;
                }

                if ($notifications->nudge($user, $course, $decision)) {
                    $sent++;
                    $done[$userId] = true;
                }
            } catch (Throwable $e) {
                // Best-effort : ne jamais faire échouer la commande pour une inscription.
                $this->warn("Nudge ignoré pour l'inscription {$enrollment->id} : {$e->getMessage()}");
            }
        }

        $this->info("Nudges envoyés : {$sent}.");

        return self::SUCCESS;
    }
}

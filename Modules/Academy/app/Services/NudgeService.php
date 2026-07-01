<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * NUDGES COMPORTEMENTAUX (relances intelligentes, LMS 2026). DÉCIDE, pour UN
 * inscrit actif dans UN cours, s'il faut une relance bienveillante et LAQUELLE,
 * en RÉUTILISANT RiskScoreService (aucun recalcul du risque : DRY strict).
 *
 * Ce service ne DÉCIDE que : il ne fait AUCUN envoi, aucun dédoublonnage. L'envoi,
 * le plafond anti-spam et le dédoublonnage vivent dans AcademyNotificationService
 * (point d'envoi unique). Retourne AU PLUS un seul nudge (le plus prioritaire).
 *
 * Priorité (le premier applicable gagne) :
 *   1. milestone (positif : félicitations à 50 % / 100 %) ;
 *   2. mastery_drop (>= 2 échecs quiz consécutifs) ;
 *   3. inactivity (>= 7 jours sans activité).
 *
 * Ton : français québécois BIENVEILLANT, jamais culpabilisant (Loi 25 : accompagnement).
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

use Modules\Academy\Models\Course;

final class NudgeService
{
    /** Jalons de progression qui déclenchent des félicitations. */
    private const MILESTONES = [50, 100];

    public function __construct(private readonly RiskScoreService $riskScore) {}

    /** Le module de nudges est-il activé ? (drapeau maître, défaut FALSE). */
    public function isEnabled(): bool
    {
        return (bool) config('academy.nudges_enabled', false);
    }

    /**
     * Décide LE nudge le plus pertinent pour un inscrit (ou null). Réutilise
     * RiskScoreService — aucune requête de risque n'est refaite ici.
     *
     * @return array{
     *   type: string,
     *   subject: string,
     *   heading: string,
     *   message: string,
     *   cta_route: string,
     *   cta_label: string,
     *   context: array<string, mixed>,
     * }|null
     */
    public function decideForEnrollee(int $userId, Course $course): ?array
    {
        if (! $this->isEnabled()) {
            return null;
        }

        $risk    = $this->riskScore->scoreForEnrollee($userId, $course);
        $details = $risk['details'];
        $percent = (int) $details['percent'];
        $title   = (string) $course->title;

        // 1. MILESTONE (positif, prioritaire). Jalon franchi le plus élevé <= percent.
        $milestone = null;
        foreach (self::MILESTONES as $m) {
            if ($percent >= $m) {
                $milestone = $m;
            }
        }

        if ($milestone !== null) {
            $context = ['milestone' => $milestone, 'percent' => $percent];

            if ($milestone === 100) {
                return [
                    'type'      => AcademyNotificationService::TYPE_NUDGE_MILESTONE,
                    'subject'   => 'Félicitations, cours complété!',
                    'heading'   => 'Vous avez tout terminé',
                    'message'   => sprintf('Quel accomplissement! Vous avez complété « %s ». Bravo pour votre persévérance.', $title),
                    'cta_route' => 'academy.courses.show',
                    'cta_label' => 'Voir le cours',
                    'context'   => $context,
                ];
            }

            return [
                'type'      => AcademyNotificationService::TYPE_NUDGE_MILESTONE,
                'subject'   => 'Bravo, vous êtes à mi-chemin!',
                'heading'   => 'Déjà 50 % de complété',
                'message'   => sprintf('Vous avancez à merveille dans « %s ». Continuez sur cette belle lancée, la suite est tout aussi enrichissante.', $title),
                'cta_route' => 'academy.courses.show',
                'cta_label' => 'Continuer le cours',
                'context'   => $context,
            ];
        }

        // 2. MASTERY DROP : plusieurs échecs consécutifs -> proposer une révision.
        if ((int) $details['consecutive_fails'] >= RiskScoreService::QUIZ_CONSECUTIVE_FAILURES_MED) {
            return [
                'type'      => AcademyNotificationService::TYPE_NUDGE_MASTERY_DROP,
                'subject'   => 'Un petit coup de pouce pour vos révisions',
                'heading'   => 'On révise ensemble?',
                'message'   => 'Ces notions demandent parfois quelques essais, c\'est tout à fait normal. Une courte révision ciblée vous aidera à les maîtriser.',
                'cta_route' => 'academy.srs.review',
                'cta_label' => 'Réviser les notions',
                'context'   => ['consecutive_fails' => (int) $details['consecutive_fails']],
            ];
        }

        // 3. INACTIVITÉ : réengagement doux, sans culpabilité.
        if ((int) $details['days_inactive'] >= RiskScoreService::INACTIVITY_DAYS_MED) {
            return [
                'type'      => AcademyNotificationService::TYPE_NUDGE_INACTIVITY,
                'subject'   => sprintf('On s\'ennuie de vous dans « %s »!', $title),
                'heading'   => 'Prêt à reprendre?',
                'message'   => 'Aucune pression : reprenez à votre rythme, exactement là où vous étiez. Chaque petit pas compte.',
                'cta_route' => 'academy.courses.show',
                'cta_label' => 'Reprendre le cours',
                'context'   => ['days_inactive' => (int) $details['days_inactive']],
            ];
        }

        return null;
    }
}

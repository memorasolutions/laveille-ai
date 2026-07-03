<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Mode kiosque (parité Moodle « Safe Exam Browser ») — consignation des
 * incidents pendant une évaluation surveillée. C'EST DE LA DISSUASION ET DE LA
 * TRAÇABILITÉ, PAS UNE GARANTIE DE SÉCURITÉ : un utilisateur technique peut
 * contourner le plein écran, désactiver JS ou modifier le DOM. La notation du
 * quiz reste et restera TOUJOURS calculée exclusivement côté serveur par
 * QuizService::score() (voir QuizController::submitQuiz) — ce service ne
 * touche JAMAIS au score, il se contente de journaliser des comportements
 * suspects à des fins d'audit/affichage formateur. Aucune invalidation
 * automatique de tentative n'est appliquée.
 *
 * ANTI-IDOR : record() exige que la tentative appartienne réellement à
 * l'utilisateur authentifié qui soumet l'incident (vérification serveur
 * stricte, jamais fait confiance à un attempt_id du client seul).
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Modules\Academy\Models\KioskViolation;
use Modules\Academy\Models\QuizAttempt;

final class KioskViolationService
{
    /** Sortie du plein écran (touche Échap, F11, geste système). */
    public const FULLSCREEN_EXIT = 'fullscreen_exit';

    /** Onglet/fenêtre passé en arrière-plan (perte de focus, changement d'onglet). */
    public const TAB_BLUR = 'tab_blur';

    /** Ouverture des outils de développement SUSPECTÉE (heuristique, non garantie). */
    public const DEVTOOLS_SUSPECTED = 'devtools_suspected';

    /** Sortie volontaire via le bouton « Quitter le mode kiosque » (différenciée). */
    public const VOLUNTARY_EXIT = 'voluntary_exit';

    /** Liste blanche stricte des types acceptés — jamais une valeur libre du client. */
    public const TYPES = [
        self::FULLSCREEN_EXIT,
        self::TAB_BLUR,
        self::DEVTOOLS_SUSPECTED,
        self::VOLUNTARY_EXIT,
    ];

    /** Drapeau maître — voir config('academy.kiosk_mode_enabled'), défaut false. */
    public function isEnabled(): bool
    {
        return (bool) config('academy.kiosk_mode_enabled', false);
    }

    /**
     * Consigne UN incident pour une tentative de quiz DÉJÀ créée, après
     * vérification stricte que cette tentative appartient bien à l'utilisateur
     * courant (anti-IDOR). No-op silencieux si le drapeau global est désactivé,
     * si le type n'est pas dans la liste blanche, ou si l'ownership échoue.
     *
     * Utilisée pour un besoin ponctuel de consignation directe (ex. correction
     * manuelle formateur) ; le flux normal APPRENANT passe par stageInSession()
     * + flushSessionToAttempt() car la tentative n'existe pas encore pendant le
     * round actif (voir KioskController::recordViolation).
     */
    public function record(User $user, QuizAttempt $attempt, string $type, array $meta = []): ?KioskViolation
    {
        if (! $this->isEnabled() || ! in_array($type, self::TYPES, true)) {
            return null;
        }

        // ANTI-IDOR : la tentative DOIT appartenir à l'utilisateur authentifié qui
        // soumet l'incident. Un attempt_id d'un AUTRE apprenant est rejeté ici,
        // jamais fait confiance au payload client seul.
        if ((int) $attempt->user_id !== (int) $user->id) {
            return null;
        }

        return $this->persist($attempt, $user->id, $type, now(), $meta === [] ? null : $meta);
    }

    /**
     * Empile UN incident dans le round EN COURS (session serveur), AVANT que la
     * QuizAttempt n'existe (elle n'est créée qu'à la soumission finale — voir
     * QuizController::submitQuiz ligne ~321). Les incidents empilés sont migrés
     * vers academy_kiosk_violations par flushSessionToAttempt() une fois la
     * tentative réellement créée. No-op si le drapeau global est désactivé ou
     * si le type n'est pas dans la liste blanche (jamais une valeur libre).
     *
     * @param  array<string, mixed>  $quizData  référence au tableau de session
     *                                           « academy.quiz.{itemId} » (modifié
     *                                           en place par l'appelant, qui doit
     *                                           ensuite le réécrire en session).
     * @return array<string, mixed> le tableau de session mis à jour
     */
    public function stageInSession(array $quizData, string $type, array $meta = []): array
    {
        if (! $this->isEnabled() || ! in_array($type, self::TYPES, true)) {
            return $quizData;
        }

        $staged   = is_array($quizData['kiosk_violations'] ?? null) ? $quizData['kiosk_violations'] : [];
        $staged[] = [
            'type'        => $type,
            'occurred_at' => now()->toIso8601String(),
            'meta'        => $meta === [] ? null : $meta,
        ];

        $quizData['kiosk_violations'] = $staged;

        return $quizData;
    }

    /**
     * Migre les incidents empilés en session (stageInSession) vers la table
     * academy_kiosk_violations, une fois la QuizAttempt réellement créée.
     * Appelée depuis QuizController::submitQuiz APRÈS QuizAttempt::create() —
     * ne touche JAMAIS au score ni à la logique de notation. Silencieuse en
     * cas d'échec (ne doit jamais bloquer la soumission du quiz).
     *
     * @param  array<int, array<string, mixed>>  $stagedViolations
     */
    public function flushSessionToAttempt(User $user, QuizAttempt $attempt, array $stagedViolations): void
    {
        if (! $this->isEnabled() || $stagedViolations === []) {
            return;
        }

        foreach ($stagedViolations as $staged) {
            $type = $staged['type'] ?? null;
            if (! is_string($type) || ! in_array($type, self::TYPES, true)) {
                continue;
            }

            $this->persist(
                $attempt,
                $user->id,
                $type,
                $staged['occurred_at'] ?? now(),
                is_array($staged['meta'] ?? null) ? $staged['meta'] : null,
            );
        }
    }

    /**
     * Écriture UNIQUE en base (DRY entre record() et flushSessionToAttempt()).
     * Silencieuse en cas d'échec (ne doit jamais faire échouer l'appelant).
     */
    private function persist(QuizAttempt $attempt, int $userId, string $type, mixed $occurredAt, ?array $meta): ?KioskViolation
    {
        try {
            return KioskViolation::create([
                'quiz_attempt_id' => $attempt->id,
                'user_id'         => $userId,
                'lesson_item_id'  => $attempt->lesson_item_id,
                'type'            => $type,
                'occurred_at'     => $occurredAt,
                'meta'            => $meta,
            ]);
        } catch (\Throwable $e) {
            \Log::warning('[KioskViolationService] Consignation incident échouée', ['exception' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Incidents d'UNE tentative donnée, triés chronologiquement — pour affichage
     * formateur (voir livewire/kiosk-violations.blade.php). Lecture seule.
     *
     * @return Collection<int, KioskViolation>
     */
    public function forAttempt(QuizAttempt $attempt): Collection
    {
        return KioskViolation::query()
            ->where('quiz_attempt_id', $attempt->id)
            ->orderBy('occurred_at')
            ->get();
    }

    /**
     * Incidents de TOUTES les tentatives d'un item de leçon (vue d'ensemble
     * formateur), triés par tentative puis chronologiquement. Lecture seule.
     *
     * @return Collection<int, KioskViolation>
     */
    public function forLessonItem(int $lessonItemId): Collection
    {
        return KioskViolation::query()
            ->where('lesson_item_id', $lessonItemId)
            ->with('user:id,name,email')
            ->orderBy('quiz_attempt_id')
            ->orderBy('occurred_at')
            ->get();
    }
}

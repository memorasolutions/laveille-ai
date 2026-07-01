<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

use App\Models\User;
use Modules\Academy\Models\Completion;
use Modules\Academy\Models\LessonItem;

final class CompletionService
{
    /**
     * Marque un item comme complété (idempotent).
     *
     * Si la completion existe déjà avec status='completed', la retourne sans modification.
     * Sinon, crée ou met à jour avec status='completed' + horodatages.
     * Déclenche ensuite (de façon défensive) :
     *   - l'événement 'academy.item.completed'
     *   - le recalcul de progression via ProgressService
     *   - le log d'activité (Spatie ActivityLog)
     *   - le hook QuestProgress (no-op)
     */
    public static function markComplete(
        User $user,
        LessonItem $item,
        ?int $score = null,
        ?int $qtAttemptId = null
    ): Completion {
        // Charger la relation lesson.chapter.course si absente (besoin de ProgressService)
        try {
            $item->loadMissing(['lesson.chapter.course']);
        } catch (\Throwable) {
            // Si la relation échoue, on continue sans recalcul
        }

        // Vérifier si une completion 'completed' existe déjà (idempotence)
        $existing = Completion::where('user_id', $user->id)
            ->where('lesson_item_id', $item->id)
            ->where('status', 'completed')
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        // Créer ou mettre à jour la completion
        $completion = Completion::updateOrCreate(
            [
                'user_id'        => $user->id,
                'lesson_item_id' => $item->id,
            ],
            [
                'course_id'     => self::resolveCourseId($item),
                'status'        => 'completed',
                'score'         => $score,
                'qt_attempt_id' => $qtAttemptId,
                'started_at'    => now(),
                'completed_at'  => now(),
            ]
        );

        // Événement défensif
        try {
            event('academy.item.completed', [$user, $item]);
        } catch (\Throwable) {
            // Silencieux
        }

        // Recalcul de progression
        $course = null;
        try {
            $course = $item->lesson->chapter->course ?? null;
            if ($course !== null && class_exists(ProgressService::class)) {
                ProgressService::recalculate($user, $course);
            }
        } catch (\Throwable) {
            // Silencieux
        }

        // Gamification - crédit XP pour une leçon COMPLÉTÉE (idempotent, drapeau
        // academy.gamification_enabled OFF par défaut). Branché ICI (pas dans
        // ProgressService) car on dispose directement de la Completion précise
        // (source_id stable pour l'anti-triche) — voir GamificationService.
        if ($course !== null && class_exists(GamificationService::class)) {
            try {
                app(GamificationService::class)->awardLessonCompleted($user, $course, $item, $completion);
            } catch (\Throwable) {
                // Silencieux
            }
        }

        // SRS - Répétition espacée (différenciateur rétention). Gardé par le drapeau
        // academy.srs_enabled : si désactivé, enqueueForLesson() est un no-op complet
        // (aucune carte créée). Idempotent (unicité user+source en base) et défensif :
        // un échec ici ne casse jamais la complétion.
        try {
            $lesson = $item->lesson ?? null;
            if ($lesson !== null && class_exists(SrsService::class)) {
                app(SrsService::class)->enqueueForLesson($user, $lesson);
            }
        } catch (\Throwable) {
            // Silencieux
        }

        // ActivityLog défensif — guard idempotence B02b : écrire UNIQUEMENT si la
        // completion est NOUVELLE (wasRecentlyCreated) OU si le statut vient de passer
        // à 'completed' pour la première fois (wasChanged). Empêche toute écriture
        // double en cas de concurrence ou d'appels multiples (race window, retry HTTP).
        // ACTION: fix B02b — guard activity log write (idempotence)
        // SELF: 3 lignes
        // RAISON: sans guard, un appel concurrent qui contourne l'early-return peut
        //         écrire une 2e entrée même si la complétion existe déjà en DB.
        if (class_exists(\Spatie\Activitylog\Facades\Activity::class)
            && ($completion->wasRecentlyCreated || $completion->wasChanged('status'))) {
            try {
                activity('academy')
                    ->performedOn($item)
                    ->causedBy($user)
                    ->log('academy.item.completed');
            } catch (\Throwable) {
                // Silencieux
            }
        }

        // Hook QuestProgress (no-op défensif)
        if (class_exists(\Modules\Tools\Models\QuestProgress::class)) {
            try {
                // Placeholder : logique future (badge academy, streak, etc.)
            } catch (\Throwable) {
                // Silencieux
            }
        }

        return $completion;
    }

    /**
     * Marque un item comme "démarré" (idempotent, ne modifie pas si déjà completed).
     */
    public static function markStarted(User $user, LessonItem $item): Completion
    {
        return Completion::firstOrCreate(
            [
                'user_id'        => $user->id,
                'lesson_item_id' => $item->id,
            ],
            [
                'course_id'  => self::resolveCourseId($item),
                'status'     => 'started',
                'started_at' => now(),
            ]
        );
    }

    /**
     * Résout l'ID du cours à partir de l'item (via la relation, ou 0 si introuvable).
     */
    private static function resolveCourseId(LessonItem $item): int
    {
        try {
            return (int) ($item->lesson->chapter->course->id ?? 0);
        } catch (\Throwable) {
            return 0;
        }
    }
}

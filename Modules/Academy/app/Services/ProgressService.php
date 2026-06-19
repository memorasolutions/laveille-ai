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
use Modules\Academy\Models\Course;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Models\Progress;
use Modules\Academy\Services\CertificateService;

final class ProgressService
{
    /**
     * Recalcule et persiste la progression d'un utilisateur pour un cours donné.
     *
     * Algorithme :
     *   1. Récupère tous les LessonItems is_required=true du cours (via chapters→lessons→lessonItems).
     *   2. Compte combien sont complétés (status='completed') pour cet utilisateur.
     *   3. Calcule le pourcentage (0 si aucun item requis).
     *   4. Enregistre dans `progresses` (updateOrCreate) avec last_lesson_item_id + last_activity_at.
     *   5. Déclenche l'événement 'academy.progress.updated' (défensif).
     *
     * @return \Modules\Academy\Models\Progress
     */
    public static function recalculate(User $user, Course $course): Progress
    {
        // 1. Tous les IDs d'items requis de ce cours
        $requiredIds = LessonItem::whereHas(
            'lesson.chapter',
            fn ($q) => $q->where('course_id', $course->id)
        )
            ->where('is_required', true)
            ->pluck('id')
            ->all();

        $requiredTotal = count($requiredIds);

        // 2. Items requis complétés par l'utilisateur
        $completedQuery = Completion::where('user_id', $user->id)
            ->whereIn('lesson_item_id', $requiredIds)
            ->where('status', 'completed')
            ->orderByDesc('completed_at');

        $completedItems      = $completedQuery->get();
        $requiredCompleted   = $completedItems->count();
        $lastLessonItemId    = $completedItems->isNotEmpty()
            ? $completedItems->first()->lesson_item_id
            : null;

        // 3. Pourcentage
        $percent = $requiredTotal > 0
            ? (int) round(($requiredCompleted / $requiredTotal) * 100)
            : 0;

        // 4. Persist
        $progress = Progress::updateOrCreate(
            [
                'user_id'   => $user->id,
                'course_id' => $course->id,
            ],
            [
                'required_total'      => $requiredTotal,
                'required_completed'  => $requiredCompleted,
                'percent'             => $percent,
                'last_lesson_item_id' => $lastLessonItemId,
                'last_activity_at'    => now(),
            ]
        );

        // 5. Événement défensif
        try {
            event('academy.progress.updated', [$user, $course, $progress]);
        } catch (\Throwable) {
            // Silencieux
        }

        // 6. M6 — Si 100% atteint, émettre le certificat (défensif, idempotent)
        if ($percent === 100) {
            try {
                if (class_exists(CertificateService::class)) {
                    (new CertificateService())->issueFor($user, $course);
                }
            } catch (\Throwable) {
                // Silencieux : ne jamais bloquer la progression pour un cert raté
            }
        }

        return $progress;
    }

    /**
     * Détermine la leçon où l'utilisateur doit reprendre.
     *
     * Retourne la première Lesson contenant un LessonItem requis non complété,
     * dans l'ordre (chapters.position → lessons.position → lessonItems.position).
     * Retourne null si le cours est 100 % complété ou n'a aucun item requis.
     *
     * @return \Modules\Academy\Models\Lesson|null
     */
    public static function resumeLesson(User $user, Course $course): ?\Modules\Academy\Models\Lesson
    {
        try {
            $completedIds = Completion::where('user_id', $user->id)
                ->where('status', 'completed')
                ->pluck('lesson_item_id')
                ->all();

            $course->loadMissing([
                'chapters'                    => fn ($q) => $q->orderBy('position'),
                'chapters.lessons'            => fn ($q) => $q->orderBy('position'),
                'chapters.lessons.lessonItems' => fn ($q) => $q->orderBy('position'),
            ]);

            foreach ($course->chapters as $chapter) {
                foreach ($chapter->lessons as $lesson) {
                    foreach ($lesson->lessonItems as $lessonItem) {
                        if ($lessonItem->is_required && ! in_array($lessonItem->id, $completedIds, true)) {
                            return $lesson;
                        }
                    }
                }
            }
        } catch (\Throwable) {
            // Silencieux
        }

        return null;
    }
}

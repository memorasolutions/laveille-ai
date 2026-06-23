<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * CONCERN (DRY) - autorisation d'accès à un item de leçon pour les actions étudiant
 * (quiz, choice, etc.). SOURCE UNIQUE : auth + appartenance item -> leçon -> cours
 * (anti-IDOR) + inscription active. Mutualisé entre QuizController et ChoiceController
 * (et tout futur contrôleur d'activité de leçon) afin que la règle de sécurité ne soit
 * définie qu'à UN seul endroit. Comportement IDENTIQUE aux anciennes copies : mêmes
 * abort (403 / 404) dans le même ordre.
 */

declare(strict_types=1);

namespace Modules\Academy\Http\Controllers\Concerns;

use Illuminate\Support\Facades\Auth;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;

trait AuthorizesAcademyAccess
{
    /**
     * Vérifie que l'utilisateur est authentifié, inscrit, et que l'item appartient à la
     * leçon qui appartient au cours. Abort 403 / 404 sinon.
     */
    private function authorizeAccess(Course $course, Lesson $lesson, LessonItem $item): void
    {
        if (! Auth::check()) {
            abort(403);
        }

        // Item appartient à la leçon
        if ((int) $item->lesson_id !== $lesson->id) {
            abort(404);
        }

        // Leçon appartient au cours (via chapter)
        $lesson->loadMissing('chapter');
        if ((int) $lesson->chapter->course_id !== $course->id) {
            abort(404);
        }

        // Inscription active
        $isEnrolled = Enrollment::where('user_id', Auth::id())
            ->where('course_id', $course->id)
            ->where('status', 'active')
            ->exists();

        if (! $isEnrolled) {
            abort(403);
        }
    }
}

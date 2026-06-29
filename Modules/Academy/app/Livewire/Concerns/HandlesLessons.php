<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Trait extrait du God-component CourseEditor — CRUD des leçons d'un cours :
 * création, mise à jour, suppression + confirmations inline à 2 temps.
 *
 * SÉCURITÉ : chaque mutation re-résout le cours via $this->resolveCourse() et
 * ré-autorise 'manageStructure' (anti-IDOR), exactement comme dans le composant
 * d'origine. La suppression vérifie l'appartenance de la leçon à CE cours via
 * resolveLessonFor() avant toute écriture. Aucun comportement modifié.
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire\Concerns;

use Illuminate\Support\Str;
use Modules\Academy\Models\Lesson;

trait HandlesLessons
{
    // ─────────────────────────────────────────────────────────────────────────────
    // LEÇONS
    // ─────────────────────────────────────────────────────────────────────────────

    public function addLesson(int $chapterId): void
    {
        $course  = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        // Anti-IDOR : le chapitre doit appartenir à CE cours.
        $chapter = $this->resolveChapterFor($course, $chapterId);

        $input = $this->newLesson[$chapterId] ?? ['title' => '', 'summary' => null, 'estimated_minutes' => null];

        $data = validator($input, [
            'title'             => 'required|string|max:255',
            'summary'           => 'nullable|string|max:1000',
            'estimated_minutes' => 'nullable|integer|min:1',
        ])->validate();

        $position = (int) Lesson::where('chapter_id', $chapter->id)->max('position') + 1;

        Lesson::create([
            'chapter_id'        => $chapter->id,
            'title'             => $data['title'],
            'slug'              => Str::slug($data['title']),
            'summary'           => $data['summary'] ?? null,
            'estimated_minutes' => $data['estimated_minutes'] ?? null,
            'position'          => $position,
        ]);

        unset($this->newLesson[$chapterId]);
        $this->flashSaved('Leçon ajoutée.');
    }

    public function updateLesson(
        int $lessonId,
        string $title,
        ?string $summary = null,
        $estimatedMinutes = null,
        $dripDays = null
    ): void {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        // Anti-IDOR : la leçon doit appartenir à un chapitre de CE cours.
        $lesson = $this->resolveLessonFor($course, $lessonId);

        // Les champs numériques arrivent du DOM en CHAÎNE (value d'un <input>). Un champ
        // vidé ('') doit valoir null, jamais provoquer un TypeError (strict_types) sur un
        // paramètre typé ?int. On normalise donc ici avant validation : '' / null → null,
        // sinon entier.
        $estimatedMinutes = ($estimatedMinutes === '' || $estimatedMinutes === null) ? null : (int) $estimatedMinutes;
        $dripDays = ($dripDays === '' || $dripDays === null) ? null : (int) $dripDays;

        $data = validator(
            [
                'title'             => $title,
                'summary'           => $summary,
                'estimated_minutes' => $estimatedMinutes,
                'drip_days'         => $dripDays,
            ],
            [
                'title'             => 'required|string|max:255',
                'summary'           => 'nullable|string|max:1000',
                'estimated_minutes' => 'nullable|integer|min:1',
                // C4 : 0 ou vide = disponible immédiatement (null). Max 365 jours.
                'drip_days'         => 'nullable|integer|min:0|max:365',
            ]
        )->validate();

        // 0 (« immédiat ») est normalisé en null pour rester cohérent avec « pas de drip ».
        $drip = $data['drip_days'] ?? null;
        if ($drip !== null && (int) $drip === 0) {
            $drip = null;
        }

        // Slug : ne PAS casser un slug existant (le slug suit le cycle de vie de la leçon).
        $lesson->update([
            'title'             => $data['title'],
            'summary'           => $data['summary'] ?? null,
            'estimated_minutes' => $data['estimated_minutes'] ?? null,
            'drip_days'         => $drip,
        ]);

        $this->flashSaved('Leçon mise à jour.');
    }

    public function deleteLesson(int $lessonId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $lesson = $this->resolveLessonFor($course, $lessonId);
        $lesson->delete();

        $this->confirmingLessonDeletion = null;
        $this->flashSaved('Leçon supprimée.');
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Confirmations inline à 2 temps — LEÇONS (jamais de popup native)
    // ─────────────────────────────────────────────────────────────────────────────

    public function confirmLessonDeletion(int $lessonId): void
    {
        $this->confirmingLessonDeletion = $lessonId;
    }

    public function cancelLessonDeletion(): void
    {
        $this->confirmingLessonDeletion = null;
    }
}

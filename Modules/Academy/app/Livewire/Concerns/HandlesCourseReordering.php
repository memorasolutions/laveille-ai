<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Trait extrait du God-component CourseEditor — réordonnancement des chapitres,
 * leçons et items (glisser-déposer + flèches haut/bas).
 *
 * SÉCURITÉ : chaque mutation re-résout le cours via $this->resolveCourse() et
 * ré-autorise 'manageStructure' (anti-IDOR), exactement comme dans le composant
 * d'origine. Aucun comportement modifié.
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire\Concerns;

use Illuminate\Support\Facades\DB;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;

trait HandlesCourseReordering
{
    // ─────────────────────────────────────────────────────────────────────────────
    // CHAPITRES
    // ─────────────────────────────────────────────────────────────────────────────

    public function moveChapterUp(int $chapterId): void
    {
        $this->swapChapter($chapterId, 'up');
    }

    public function moveChapterDown(int $chapterId): void
    {
        $this->swapChapter($chapterId, 'down');
    }

    private function swapChapter(int $chapterId, string $direction): void
    {
        $course  = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $chapter = $this->resolveChapterFor($course, $chapterId);

        $neighbor = Chapter::where('course_id', $course->id)
            ->when(
                $direction === 'up',
                fn ($q) => $q->where('position', '<', $chapter->position)->orderByDesc('position'),
                fn ($q) => $q->where('position', '>', $chapter->position)->orderBy('position')
            )
            ->first();

        if (! $neighbor) {
            return; // déjà en bout de liste
        }

        $tmp                = $chapter->position;
        $chapter->position  = $neighbor->position;
        $neighbor->position = $tmp;
        $chapter->save();
        $neighbor->save();
    }

    /**
     * Réordonne les CHAPITRES du cours (glisser-déposer + persistance instantanée).
     *
     * On ne fait JAMAIS confiance à l'ordre venu du navigateur : on RE-RÉSOUT le cours,
     * on RÉ-AUTORISE manageStructure, puis on VÉRIFIE que l'ensemble d'ids reçu est
     * EXACTEMENT celui des chapitres de CE cours (anti-IDOR). Le moindre id étranger,
     * manquant ou en double → rejet TOTAL, aucune écriture (transaction).
     *
     * @param  array<int|string>  $orderedIds  liste ordonnée des ids de chapitres
     */
    public function reorderChapters(array $orderedIds): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $ordered  = $this->sanitizeOrderedIds($orderedIds);
        $expected = Chapter::where('course_id', $course->id)->pluck('id')->all();

        if (! $this->orderedIdsMatch($ordered, $expected)) {
            return; // ensemble forgé / incomplet : on n'écrit rien (anti-IDOR).
        }

        DB::transaction(function () use ($course, $ordered): void {
            foreach ($ordered as $index => $chapterId) {
                Chapter::where('id', $chapterId)
                    ->where('course_id', $course->id)
                    ->update(['position' => $index + 1]);
            }
        });

        $this->flashSaved('Ordre des chapitres enregistré.');
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // LEÇONS
    // ─────────────────────────────────────────────────────────────────────────────

    public function moveLessonUp(int $lessonId): void
    {
        $this->swapLesson($lessonId, 'up');
    }

    public function moveLessonDown(int $lessonId): void
    {
        $this->swapLesson($lessonId, 'down');
    }

    private function swapLesson(int $lessonId, string $direction): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $lesson = $this->resolveLessonFor($course, $lessonId);

        $neighbor = Lesson::where('chapter_id', $lesson->chapter_id)
            ->when(
                $direction === 'up',
                fn ($q) => $q->where('position', '<', $lesson->position)->orderByDesc('position'),
                fn ($q) => $q->where('position', '>', $lesson->position)->orderBy('position')
            )
            ->first();

        if (! $neighbor) {
            return;
        }

        $tmp               = $lesson->position;
        $lesson->position  = $neighbor->position;
        $neighbor->position = $tmp;
        $lesson->save();
        $neighbor->save();
    }

    /**
     * Réordonne les LEÇONS d'un chapitre (glisser-déposer + persistance instantanée).
     *
     * Anti-IDOR à deux niveaux : le CHAPITRE doit appartenir à CE cours, et l'ensemble
     * d'ids reçu doit être EXACTEMENT celui des leçons de CE chapitre. Sinon → rejet
     * total, aucune écriture.
     *
     * @param  array<int|string>  $orderedIds
     */
    public function reorderLessons(int $chapterId, array $orderedIds): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        // Le chapitre cible doit appartenir à CE cours (sinon ModelNotFound).
        $chapter = $this->resolveChapterFor($course, $chapterId);

        $ordered  = $this->sanitizeOrderedIds($orderedIds);
        $expected = Lesson::where('chapter_id', $chapter->id)->pluck('id')->all();

        if (! $this->orderedIdsMatch($ordered, $expected)) {
            return;
        }

        DB::transaction(function () use ($chapter, $ordered): void {
            foreach ($ordered as $index => $lessonId) {
                Lesson::where('id', $lessonId)
                    ->where('chapter_id', $chapter->id)
                    ->update(['position' => $index + 1]);
            }
        });

        $this->flashSaved('Ordre des leçons enregistré.');
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // ITEMS DE LEÇON
    // ─────────────────────────────────────────────────────────────────────────────

    public function moveItemUp(int $itemId): void
    {
        $this->swapItem($itemId, 'up');
    }

    public function moveItemDown(int $itemId): void
    {
        $this->swapItem($itemId, 'down');
    }

    private function swapItem(int $itemId, string $direction): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $item = $this->resolveItemFor($course, $itemId);

        $neighbor = LessonItem::where('lesson_id', $item->lesson_id)
            ->when(
                $direction === 'up',
                fn ($q) => $q->where('position', '<', $item->position)->orderByDesc('position'),
                fn ($q) => $q->where('position', '>', $item->position)->orderBy('position')
            )
            ->first();

        if (! $neighbor) {
            return; // déjà en bout de liste
        }

        $tmp                = $item->position;
        $item->position     = $neighbor->position;
        $neighbor->position = $tmp;
        $item->save();
        $neighbor->save();
    }

    /**
     * Réordonne les ITEMS d'une leçon (glisser-déposer + persistance instantanée).
     *
     * Anti-IDOR : la LEÇON doit appartenir à un chapitre de CE cours, et l'ensemble
     * d'ids reçu doit être EXACTEMENT celui des items de CETTE leçon. Sinon → rejet
     * total, aucune écriture.
     *
     * @param  array<int|string>  $orderedIds
     */
    public function reorderItems(int $lessonId, array $orderedIds): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        // La leçon cible doit appartenir à un chapitre de CE cours (sinon ModelNotFound).
        $lesson = $this->resolveLessonFor($course, $lessonId);

        $ordered  = $this->sanitizeOrderedIds($orderedIds);
        $expected = LessonItem::where('lesson_id', $lesson->id)->pluck('id')->all();

        if (! $this->orderedIdsMatch($ordered, $expected)) {
            return;
        }

        DB::transaction(function () use ($lesson, $ordered): void {
            foreach ($ordered as $index => $itemId) {
                LessonItem::where('id', $itemId)
                    ->where('lesson_id', $lesson->id)
                    ->update(['position' => $index + 1]);
            }
        });

        $this->flashSaved('Ordre des éléments enregistré.');
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // HELPERS INTERNES (exclusifs au réordonnancement)
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Normalise une liste d'ids venue du navigateur en entiers positifs (les valeurs
     * non numériques ou <= 0 sont écartées). On ne se fie jamais au type reçu.
     *
     * @param  array<int|string>  $ids
     * @return array<int, int>
     */
    private function sanitizeOrderedIds(array $ids): array
    {
        return array_values(array_filter(
            array_map(static fn ($id) => (int) $id, $ids),
            static fn (int $id) => $id > 0
        ));
    }

    /**
     * Vrai si l'ordre reçu est une PERMUTATION EXACTE de l'ensemble attendu :
     * même cardinalité, mêmes ids, aucun en double, aucun étranger, aucun manquant.
     * C'est la garde anti-IDOR : un seul écart → faux → aucune écriture.
     *
     * @param  array<int, int>  $ordered
     * @param  array<int, int>  $expected
     */
    private function orderedIdsMatch(array $ordered, array $expected): bool
    {
        if (count($ordered) !== count($expected)) {
            return false;
        }

        if (count(array_unique($ordered)) !== count($ordered)) {
            return false; // doublon → rejet.
        }

        sort($ordered);
        sort($expected);

        return $ordered === $expected;
    }
}

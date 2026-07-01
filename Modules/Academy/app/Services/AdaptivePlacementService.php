<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Moteur de test de positionnement adaptatif (CAT) SIMPLE et déterministe (pas
 * d'IRT/calibration complexe). Monte à 'difficile' après 2 bonnes réponses
 * consécutives, descend à 'facile' après 2 mauvaises consécutives, s'arrête
 * après MAX_QUESTIONS ou convergence (déjà au plafond/plancher, confirmé deux
 * fois), et recommande une leçon de départ pour éviter de relire le déjà-su.
 * Réutilise QuestionBankService::mapToRoundItem() et QuizService::score() (DRY,
 * aucune logique de correction dupliquée).
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

use Illuminate\Support\Collection;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\Question;
use Modules\Academy\Models\QuestionCategory;

final class AdaptivePlacementService
{
    public const MAX_QUESTIONS = 7;

    private const DIFFICULTY_ORDER = ['facile', 'moyen', 'difficile'];

    private const DIFFICULTY_TO_LEVEL = ['facile' => 'faible', 'moyen' => 'moyen', 'difficile' => 'fort'];

    public function isEnabled(): bool
    {
        return (bool) config('academy.placement_test_enabled', false);
    }

    /**
     * IDs des catégories de banque (+ descendance) référencées par les items
     * quiz de ce cours (payload['question_bank']['category_id']). Le test de
     * positionnement pioche dans le MÊME pool que les quiz réels du cours.
     *
     * @return array<int, int>
     */
    public function categoryIdsForCourse(Course $course): array
    {
        $course->loadMissing('chapters.lessons.lessonItems');

        $categoryIds = [];

        foreach ($course->chapters as $chapter) {
            foreach ($chapter->lessons as $lesson) {
                foreach ($lesson->lessonItems as $item) {
                    if ($item->type !== 'quiz') {
                        continue;
                    }

                    $categoryId = $item->payload['question_bank']['category_id'] ?? null;
                    if (! $categoryId) {
                        continue;
                    }

                    $category = QuestionCategory::find($categoryId);
                    if ($category !== null) {
                        $categoryIds = array_merge($categoryIds, $category->descendantIds());
                    }
                }
            }
        }

        return array_values(array_unique($categoryIds));
    }

    /**
     * Tire UNE question active de type mcq dans les catégories données, à la
     * difficulté demandée (null = 'moyen'), en excluant les IDs déjà posés.
     * Repli sans filtre de difficulté si aucune question n'y correspond
     * (disponibilité avant tout ; jamais de blocage du test).
     *
     * @param  array<int, int> $categoryIds
     * @param  array<int, int> $excludeQuestionIds
     * @return array<string, mixed>|null
     */
    public function drawQuestion(array $categoryIds, string $difficulty, array $excludeQuestionIds): ?array
    {
        if ($categoryIds === []) {
            return null;
        }

        $query = Question::query()
            ->whereIn('category_id', $categoryIds)
            ->active()
            ->where('type', 'mcq');

        if ($excludeQuestionIds !== []) {
            $query->whereNotIn('id', $excludeQuestionIds);
        }

        $question = (clone $query)
            ->whereRaw("COALESCE(difficulty, 'moyen') = ?", [$difficulty])
            ->inRandomOrder()
            ->first();

        if (! $question) {
            $question = $query->inRandomOrder()->first();
        }

        if (! $question) {
            return null;
        }

        return QuestionBankService::mapToRoundItem($question);
    }

    /** Note serveur (jamais côté client) réutilisant le scoring centralisé. */
    public function scoreAnswer(array $mappedQuestion, mixed $given): bool
    {
        $result = QuizService::score([$mappedQuestion], ['0' => $given]);

        return (bool) ($result['details'][0]['correct'] ?? false);
    }

    /** Prochaine difficulté : PURE, sans effet de bord. */
    public function nextDifficulty(string $current, int $consecutiveCorrect, int $consecutiveWrong): string
    {
        $idx = array_search($current, self::DIFFICULTY_ORDER, true);
        if ($idx === false) {
            $idx = 1; // repli 'moyen'
        }

        if ($consecutiveCorrect >= 2) {
            $idx = min($idx + 1, 2);
        } elseif ($consecutiveWrong >= 2) {
            $idx = max($idx - 1, 0);
        }

        return self::DIFFICULTY_ORDER[$idx];
    }

    /** Condition d'arrêt : PURE, sans effet de bord. */
    public function shouldStop(int $questionsAskedCount, string $currentDifficulty, int $consecutiveCorrect, int $consecutiveWrong): bool
    {
        if ($questionsAskedCount >= self::MAX_QUESTIONS) {
            return true;
        }

        if ($questionsAskedCount >= 3) {
            if ($currentDifficulty === 'difficile' && $consecutiveCorrect >= 2) {
                return true;
            }

            if ($currentDifficulty === 'facile' && $consecutiveWrong >= 2) {
                return true;
            }
        }

        return false;
    }

    public function estimateLevel(string $finalDifficulty): string
    {
        return self::DIFFICULTY_TO_LEVEL[$finalDifficulty] ?? 'moyen';
    }

    /**
     * Leçons du cours à plat, dans l'ordre chapitre→position puis leçon→position.
     * Toujours rechargé (ordre déterministe garanti, quel que soit l'état des
     * relations chez l'appelant).
     *
     * @return Collection<int, Lesson>
     */
    public function orderedLessons(Course $course): Collection
    {
        $course->load([
            'chapters'         => fn ($q) => $q->orderBy('position'),
            'chapters.lessons' => fn ($q) => $q->orderBy('position'),
        ]);

        return $course->chapters->flatMap(fn ($chapter) => $chapter->lessons);
    }

    /**
     * Leçon de départ recommandée selon le niveau estimé : faible = 1ère leçon,
     * moyen = saute ~25% des premières leçons (jugées prérequis), fort = ~50%.
     */
    public function recommendStartingLesson(Course $course, string $level): ?Lesson
    {
        $lessons = $this->orderedLessons($course);
        if ($lessons->isEmpty()) {
            return null;
        }

        $total  = $lessons->count();
        $ratios = ['faible' => 0.0, 'moyen' => 0.25, 'fort' => 0.5];
        $ratio  = $ratios[$level] ?? 0.0;
        $index  = (int) floor($total * $ratio);
        $index  = min($index, $total - 1);

        return $lessons->values()->get($index);
    }
}

<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Répétition espacée (SRS) - moteur SM-2 classique + génération/lecture des
 * cartes. TOUT est gardé par le drapeau academy.srs_enabled (défaut FALSE) :
 * désactivé, enqueueForLesson() ne crée rien et dueFor() renvoie une collection
 * vide. Aucune dépendance UI (déterministe, testable).
 *
 * Algorithme SM-2 (SuperMemo 2) : une qualité de rappel q ∈ [0..5] recalcule
 * ease_factor, repetitions, interval_days puis due_at.
 *   - q < 3  → échec : repetitions=0, interval=1 jour (on réapprend).
 *   - q >= 3 → succès : interval grandit (1, 6, puis interval*EF).
 *   - EF (>= 1.3) : EF' = EF + (0.1 - (5-q)(0.08 + (5-q)0.02)).
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Models\SrsCard;

class SrsService
{
    /** Plancher SM-2 du facteur de facilité : jamais en dessous de 1.3. */
    private const MIN_EASE_FACTOR = 1.3;

    /** Le module SRS est-il activé ? (drapeau maître, défaut FALSE). */
    public function isEnabled(): bool
    {
        return (bool) config('academy.srs_enabled', false);
    }

    /**
     * Crée (idempotent) les cartes de révision d'une leçon complétée pour un
     * utilisateur. No-op si le drapeau est désactivé. Une carte par item de
     * type « révisable » (concept doc ou quiz) ; l'unicité (user, source) en
     * base garantit qu'un second appel ne duplique rien.
     *
     * @return int Nombre de cartes réellement créées.
     */
    public function enqueueForLesson(User $user, Lesson $lesson): int
    {
        if (! $this->isEnabled()) {
            return 0;
        }

        // Le cours est résolu via chapter.course (Lesson n'a pas de course_id direct).
        $courseId = $this->resolveCourseId($lesson);
        if ($courseId === 0) {
            return 0;
        }

        $created = 0;

        foreach ($lesson->lessonItems as $item) {
            if (! $this->isReviewable($item)) {
                continue;
            }

            $card = SrsCard::firstOrCreate(
                [
                    'user_id'     => $user->id,
                    'source_type' => LessonItem::class,
                    'source_id'   => $item->id,
                ],
                [
                    'course_id' => $courseId,
                    'lesson_id' => $lesson->id,
                    'front'     => $this->frontFor($item),
                    'back'      => $this->backFor($item),
                    // Nouvelle carte : due immédiatement (première révision).
                    'due_at'    => now(),
                ],
            );

            if ($card->wasRecentlyCreated) {
                $created++;
            }
        }

        return $created;
    }

    /**
     * Cartes DUES de CET utilisateur (jamais celles d'un autre), triées par
     * échéance. No-op (collection vide) si le drapeau est désactivé.
     *
     * @return Collection<int, SrsCard>
     */
    public function dueFor(User $user, int $limit = 20): Collection
    {
        if (! $this->isEnabled()) {
            return collect();
        }

        return SrsCard::query()
            ->dueFor($user->id)
            ->orderByRaw('due_at IS NULL DESC')
            ->orderBy('due_at')
            ->limit($limit)
            ->get();
    }

    /** Nombre de cartes dues de l'utilisateur (0 si désactivé). */
    public function dueCountFor(User $user): int
    {
        if (! $this->isEnabled()) {
            return 0;
        }

        return SrsCard::query()->dueFor($user->id)->count();
    }

    /**
     * Applique l'algorithme SM-2 à une carte selon la qualité de rappel (0..5)
     * et persiste le nouvel état. Retourne la carte mise à jour.
     */
    public function review(SrsCard $card, int $quality): SrsCard
    {
        $quality = max(0, min(5, $quality));

        // 1. Recalcul du facteur de facilité (borné au plancher).
        $ease = $card->ease_factor
            + (0.1 - (5 - $quality) * (0.08 + (5 - $quality) * 0.02));
        $ease = max(self::MIN_EASE_FACTOR, round($ease, 2));

        // 2. Répétitions + intervalle.
        if ($quality < 3) {
            // Échec : on réapprend depuis le début (intervalle court).
            $repetitions = 0;
            $interval    = 1;
        } else {
            $repetitions = $card->repetitions + 1;

            $interval = match ($repetitions) {
                1       => 1,
                2       => 6,
                default => (int) ceil($card->interval_days * $ease),
            };
        }

        $card->ease_factor      = $ease;
        $card->repetitions      = $repetitions;
        $card->interval_days    = $interval;
        $card->last_reviewed_at = now();
        $card->due_at           = now()->addDays($interval);
        $card->save();

        return $card;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Sélection / rendu de la source (DRY : mêmes types que le DeckPlayer)
    // ─────────────────────────────────────────────────────────────────────────

    /** ID du cours d'une leçon via chapter.course (0 si introuvable, défensif). */
    private function resolveCourseId(Lesson $lesson): int
    {
        try {
            $lesson->loadMissing('chapter.course');

            return (int) ($lesson->chapter->course->id ?? 0);
        } catch (\Throwable) {
            return 0;
        }
    }

    /** Un item est révisable s'il porte un concept (doc) ou une question (quiz). */
    private function isReviewable(LessonItem $item): bool
    {
        return in_array($item->type, ['doc', 'quiz'], true);
    }

    /** Recto de la carte : la question du quiz, sinon le titre du concept. */
    private function frontFor(LessonItem $item): string
    {
        if ($item->type === 'quiz') {
            $prompt = $item->payload['prompt'] ?? $item->payload['question'] ?? null;
            if (is_string($prompt) && $prompt !== '') {
                return $prompt;
            }
        }

        return $item->title;
    }

    /** Verso de la carte : explication/réponse du quiz, sinon extrait du concept. */
    private function backFor(LessonItem $item): ?string
    {
        if ($item->type === 'quiz') {
            $answer = $item->payload['explanation'] ?? $item->payload['answer'] ?? null;

            return is_string($answer) && $answer !== '' ? $answer : $item->title;
        }

        // Concept (doc) : court extrait texte du corps markdown, sans HTML.
        $body = $item->payload['body'] ?? $item->payload['text'] ?? null;
        if (is_string($body) && $body !== '') {
            $plain = trim(strip_tags(LessonItem::renderRichText($body)));

            return $plain !== '' ? mb_substr($plain, 0, 400) : null;
        }

        return null;
    }
}

<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Trait extrait du God-component CourseEditor — éditeur répéteur de questions
 * de rétroaction (Feedback, F18) : gabarit vierge, ajout/retrait à la création
 * et à l'édition, chargement/annulation/enregistrement du tampon.
 *
 * SÉCURITÉ : chaque mutation re-résout le cours via $this->resolveCourse() et
 * ré-autorise 'manageStructure' (anti-IDOR). La résolution anti-IDOR de l'item
 * passe par resolveItemFor() avant toute écriture. Aucun comportement modifié.
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire\Concerns;

trait HandlesFeedbackEditor
{
    // ─────────────────────────────────────────────────────────────────────────────
    // FEEDBACK - répéteur de questions (NOUVEL item + ÉDITION). Un répéteur dynamique
    // ne se prête pas au $event.target inline du formulaire générique : on passe par
    // des actions dédiées qui manipulent un tampon Livewire
    // (newItem.{lesson}.feedback_questions à la création, editFeedback.{item} à
    // l'édition).
    // ─────────────────────────────────────────────────────────────────────────────

    /** Gabarit d'une question vierge (répéteur feedback). */
    private function blankFeedbackQuestion(): array
    {
        return ['type' => 'rating', 'label' => '', 'scale' => \Modules\Academy\Services\FeedbackService::DEFAULT_SCALE, 'options' => '', 'required' => false];
    }

    /** NOUVEL item feedback : ajoute une question vierge. */
    public function addNewFeedbackQuestion(int $lessonId): void
    {
        $this->newItem[$lessonId]['feedback_questions'][] = $this->blankFeedbackQuestion();
    }

    /** NOUVEL item feedback : retire la question d'index donné (réindexée). */
    public function removeNewFeedbackQuestion(int $lessonId, int $index): void
    {
        if (isset($this->newItem[$lessonId]['feedback_questions'][$index])) {
            unset($this->newItem[$lessonId]['feedback_questions'][$index]);
            $this->newItem[$lessonId]['feedback_questions'] = array_values($this->newItem[$lessonId]['feedback_questions']);
        }
    }

    /**
     * ÉDITION : charge le tampon d'édition d'un item feedback depuis son payload.
     * Anti-IDOR : l'item doit appartenir à CE cours. Les options « choice » sont
     * converties en chaîne multiligne pour l'édition.
     */
    public function loadFeedbackEditor(int $itemId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);
        $item = $this->resolveItemFor($course, $itemId);

        if ($item->type !== 'feedback') {
            return;
        }

        $questions = [];
        foreach (\Modules\Academy\Services\FeedbackService::questions($item) as $q) {
            $questions[] = [
                'type'     => $q['type'],
                'label'    => $q['label'],
                'scale'    => $q['scale'] ?? \Modules\Academy\Services\FeedbackService::DEFAULT_SCALE,
                'options'  => isset($q['options']) ? implode("\n", $q['options']) : '',
                'required' => (bool) ($q['required'] ?? false),
            ];
        }

        $this->editFeedback[$itemId] = [
            'title'             => $item->title,
            'intro'             => \Modules\Academy\Services\FeedbackService::intro($item),
            'anonymous'         => \Modules\Academy\Services\FeedbackService::isAnonymous($item),
            'completion'        => \Modules\Academy\Services\ActivityCompletionService::criterionFor($item),
            'estimated_minutes' => $item->estimated_minutes,
            'questions'         => $questions,
        ];
    }

    /** Abandonne l'édition en cours d'un item feedback (vide le tampon). */
    public function cancelFeedbackEditor(int $itemId): void
    {
        unset($this->editFeedback[$itemId]);
    }

    /** ÉDITION : ajoute une question vierge au tampon. */
    public function addFeedbackQuestion(int $itemId): void
    {
        $this->editFeedback[$itemId]['questions'][] = $this->blankFeedbackQuestion();
    }

    /** ÉDITION : retire la question d'index donné du tampon (réindexée). */
    public function removeFeedbackQuestion(int $itemId, int $index): void
    {
        if (isset($this->editFeedback[$itemId]['questions'][$index])) {
            unset($this->editFeedback[$itemId]['questions'][$index]);
            $this->editFeedback[$itemId]['questions'] = array_values($this->editFeedback[$itemId]['questions']);
        }
    }

    /**
     * ÉDITION : enregistre un item feedback depuis son tampon. Mêmes gardes que
     * updateItem (resolveCourse → manageStructure → resolveItemFor anti-IDOR →
     * validateItem → buildItemPayload). Réutilise la construction de payload DRY.
     */
    public function saveFeedback(int $itemId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);
        $item = $this->resolveItemFor($course, $itemId);

        if ($item->type !== 'feedback') {
            abort(404);
        }

        $buffer  = $this->editFeedback[$itemId] ?? [];
        $minutes = $buffer['estimated_minutes'] ?? null;

        $input = [
            'type'               => 'feedback',
            'title'              => (string) ($buffer['title'] ?? $item->title),
            'estimated_minutes'  => ($minutes === '' || $minutes === null) ? null : (int) $minutes,
            'feedback_intro'     => $buffer['intro'] ?? null,
            'feedback_questions' => $buffer['questions'] ?? [],
            'anonymous'          => $buffer['anonymous'] ?? null,
            'completion'         => $buffer['completion'] ?? null,
        ];

        $data    = $this->validateItem($input);
        $payload = $this->buildItemPayload('feedback', $input);

        $item->update([
            'type'              => 'feedback',
            'title'             => $data['title'],
            'payload'           => $payload,
            'estimated_minutes' => $data['estimated_minutes'] ?? null,
        ]);

        unset($this->editFeedback[$itemId]);
        $this->flashSaved('Sondage de rétroaction mis à jour.');
    }
}

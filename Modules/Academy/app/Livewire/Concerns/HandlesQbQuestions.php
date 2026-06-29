<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Trait extrait du God-component QuestionBankManager — CRUD des QUESTIONS de la
 * banque : enregistrement (création / édition), chargement dans le formulaire,
 * suppression, réinitialisation du formulaire et confirmations inline à 2 temps.
 *
 * SÉCURITÉ : chaque mutation re-résout la catégorie cible et la question via
 * $this->resolveCategory() / $this->resolveQuestion() (anti-IDOR owner-scoped).
 * Le type est en liste blanche (Question::TYPES) ; le payload est validé par type
 * via buildAndValidatePayload(). owner_id est TOUJOURS forcé = auth() à la création.
 * Aucun comportement modifié.
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire\Concerns;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Modules\Academy\Models\Question;

trait HandlesQbQuestions
{
    // ─────────────────────────────────────────────────────────────────────────
    // QUESTIONS — CRUD
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Enregistre une question (création OU édition selon $editingQuestionId).
     * La catégorie est re-résolue scopée owner (anti-IDOR) ; le type est en liste
     * blanche ; le payload est validé/normalisé par type.
     */
    public function saveQuestion(): void
    {
        if ($this->selectedCategoryId === null) {
            session()->flash('academy_bank_error', 'Sélectionnez d\'abord une catégorie.');

            return;
        }

        // Anti-IDOR : la catégorie cible doit m'appartenir.
        $category = $this->resolveCategory((int) $this->selectedCategoryId);

        // Validation commune (hors payload, traité par type ensuite).
        $this->validate([
            'qType'        => ['required', Rule::in(Question::TYPES)],
            'qPrompt'      => 'required|string|max:2000',
            'qExplanation' => 'nullable|string|max:2000',
            'qDifficulty'  => ['required', Rule::in(self::DIFFICULTIES)],
            'qPoints'      => 'required|integer|min:1|max:100',
            'qClozeText'   => 'nullable|string|max:'.self::MAX_CLOZE_TEXT,
            'qDdwtosText'  => 'nullable|string|max:'.self::MAX_DDWTOS_TEXT,
            'qNumericalUnit' => 'nullable|string|max:40',
            'qGraderInfo'    => 'nullable|string|max:2000',
        ]);

        // Construit + valide le payload selon le type.
        $payload = $this->buildAndValidatePayload($this->qType);

        $attributes = [
            'category_id' => $category->id,
            'type'        => $this->qType,
            'prompt'      => trim($this->qPrompt),
            'explanation' => $this->qExplanation !== null && trim($this->qExplanation) !== ''
                ? trim($this->qExplanation)
                : null,
            'difficulty'  => $this->qDifficulty,
            'points'      => max(1, min(100, (int) $this->qPoints)),
            'is_active'   => $this->qIsActive,
            'payload'     => $payload,
        ];

        if ($this->editingQuestionId !== null) {
            $question = $this->resolveQuestion((int) $this->editingQuestionId);

            // F17 (VERSIONS) : archive l'état précédent si le contenu change.
            $this->maybeSnapshotVersion($question, $attributes);

            $question->update($attributes);
            $message = 'Question mise à jour.';
        } else {
            $attributes['owner_id'] = Auth::id(); // FORCÉ = auth.
            $question = Question::create($attributes);
            $message = 'Question ajoutée.';
        }

        // F17 (TAGS) : synchronisation owner-scopée.
        $this->syncTags($question);

        $this->resetQuestionForm();
        session()->flash('academy_bank_status', $message);
    }

    /** Charge une question dans le formulaire (édition, anti-IDOR). */
    public function editQuestion(int $questionId): void
    {
        $question = $this->resolveQuestion($questionId);

        // On reste sur la catégorie de la question (sélection cohérente).
        $this->selectedCategoryId = (int) $question->category_id;

        $this->editingQuestionId = $question->id;
        $this->qType             = in_array($question->type, Question::TYPES, true) ? $question->type : 'mcq';
        $this->qPrompt           = (string) $question->prompt;
        $this->qExplanation      = $question->explanation;
        $this->qDifficulty       = in_array($question->difficulty, self::DIFFICULTIES, true) ? (string) $question->difficulty : 'moyen';
        $this->qPoints           = max(1, min(100, (int) ($question->points ?? 1)));
        $this->qIsActive         = (bool) $question->is_active;

        $payload = is_array($question->payload) ? $question->payload : [];
        $this->hydratePayloadForm($this->qType, $payload);

        // F17 (TAGS) : pré-remplit la saisie avec les étiquettes existantes.
        $this->qTags = $question->tags()->orderBy('name')->pluck('name')->implode(', ');

        $this->resetErrorBag();
    }

    public function deleteQuestion(int $questionId): void
    {
        $question = $this->resolveQuestion($questionId);
        $question->delete();

        if ($this->editingQuestionId === $questionId) {
            $this->resetQuestionForm();
        }
        if ($this->historyQuestionId === $questionId) {
            $this->historyQuestionId = null;
        }
        $this->confirmingQuestionDeletion = null;
        session()->flash('academy_bank_status', 'Question supprimée.');
    }

    /** Réinitialise tout le formulaire de question (annule l'édition en cours). */
    public function resetQuestionForm(): void
    {
        $this->editingQuestionId = null;
        $this->qType             = 'mcq';
        $this->qPrompt           = '';
        $this->qExplanation      = null;
        $this->qDifficulty       = 'moyen';
        $this->qPoints           = 1;
        $this->qIsActive         = true;
        $this->qTags             = '';
        $this->resetPayloadFields();
        $this->resetErrorBag();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Confirmations inline à 2 temps — questions
    // ─────────────────────────────────────────────────────────────────────────

    public function confirmQuestionDeletion(int $questionId): void
    {
        $this->confirmingQuestionDeletion = $questionId;
    }

    public function cancelQuestionDeletion(): void
    {
        $this->confirmingQuestionDeletion = null;
    }
}

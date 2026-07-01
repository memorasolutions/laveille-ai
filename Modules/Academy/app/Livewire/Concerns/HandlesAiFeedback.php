<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * FEEDBACK IA sur remises rédigées (correction assistée - LMS 2026). Trait cohésif
 * ajouté à CourseAssignments : le formateur demande une PROPOSITION IA (feedback +
 * note suggérée) sur une remise, l'obtient en BROUILLON ÉDITABLE, puis la
 * verse (ou non) dans le formulaire de correction officiel. L'IA ne note JAMAIS
 * l'apprenant : seule l'action « Enregistrer la note » du formateur écrit la note
 * et le feedback OFFICIELS (voir HandlesSubmissionGrading::gradeSubmission).
 *
 * SÉCURITÉ (calquée sur HandlesSubmissionGrading) : chaque action re-résout le
 * cours via resolveCourse() + authorize('manageEnrollments', $course) (anti-IDOR
 * serveur, jamais confiance au navigateur) ; la remise est re-résolue et scopée à
 * CE cours. GATING : gardé aussi par le drapeau academy.ai_feedback_enabled — si
 * OFF, l'action refuse net (défense en profondeur, en plus du bouton caché en Blade).
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire\Concerns;

use Livewire\Attributes\Computed;
use Modules\Academy\Models\Submission;
use Modules\Academy\Services\AcademyFeedbackAiService;

trait HandlesAiFeedback
{
    /** Remise pour laquelle un brouillon IA est affiché (id), null = aucun. */
    public ?int $aiFeedbackSubmission = null;

    /** Brouillon de feedback IA, ÉDITABLE par le formateur avant application. */
    public string $aiFeedbackDraft = '';

    /** Note suggérée IA (chaîne du DOM), éditable ; jamais écrite comme note officielle. */
    public string $aiSuggestedScoreDraft = '';

    /** Message d'erreur non bloquant si la génération IA échoue. */
    public string $aiFeedbackError = '';

    /** Le feedback IA est-il activé ET disponible ? Sert à cacher le bouton en Blade. */
    #[Computed]
    public function aiFeedbackAvailable(): bool
    {
        return (bool) config('academy.ai_feedback_enabled', false)
            && class_exists(\Modules\AI\Services\AiService::class);
    }

    /**
     * Génère une PROPOSITION IA (feedback + note suggérée) pour une remise de CE cours.
     * Gardé : drapeau ON + manageEnrollments + remise scopée (anti-IDOR). N'écrit AUCUNE
     * note officielle : persiste seulement le brouillon (ai_feedback/ai_suggested_score/
     * ai_generated_at), colonnes DISTINCTES des champs humains (score/feedback).
     */
    public function proposeAiFeedback(int $submissionId): void
    {
        // GATE drapeau : si le feedback IA est désactivé, on ne fait rien (aucun appel).
        if (! $this->aiFeedbackAvailable()) {
            return;
        }

        $course = $this->resolveCourse();
        $this->authorize('manageEnrollments', $course);

        $submission = $this->resolveSubmissionFor($course, $submissionId);
        /** @var \Modules\Academy\Models\Assignment|null $assignment */
        $assignment = $submission->assignment;

        $this->aiFeedbackError = '';

        if ($assignment === null) {
            $this->aiFeedbackError = 'Devoir introuvable pour cette remise.';

            return;
        }

        $result = app(AcademyFeedbackAiService::class)->evaluate($submission, $assignment);

        if ($result['error']) {
            $this->aiFeedbackError = "La génération du feedback IA a échoué. Réessayez dans un moment ou corrigez manuellement.";

            return;
        }

        // Persiste le BROUILLON IA (colonnes distinctes) — jamais la note officielle.
        $submission->update([
            'ai_feedback'        => $result['feedback'] !== '' ? $result['feedback'] : null,
            'ai_suggested_score' => $result['suggested_score'],
            'ai_generated_at'    => now(),
        ]);

        $this->aiFeedbackSubmission  = $submission->id;
        $this->aiFeedbackDraft       = $result['feedback'];
        $this->aiSuggestedScoreDraft = $result['suggested_score'] !== null
            ? rtrim(rtrim(number_format($result['suggested_score'], 2, '.', ''), '0'), '.')
            : '';
    }

    /**
     * Verse le brouillon IA (tel qu'ÉDITÉ par le formateur) dans le formulaire de
     * correction officiel : ouvre startGrading() puis y injecte le feedback et la note
     * suggérée comme valeurs PRÉ-REMPLIES. Le formateur reste seul à valider ensuite
     * via « Enregistrer la note » — rien d'officiel n'est écrit ici.
     */
    public function applyAiFeedbackToGrading(int $submissionId): void
    {
        if (! $this->aiFeedbackAvailable()) {
            return;
        }

        $course = $this->resolveCourse();
        $this->authorize('manageEnrollments', $course);

        // Re-résolution scopée (anti-IDOR) : une remise étrangère → ModelNotFound.
        $submission = $this->resolveSubmissionFor($course, $submissionId);

        // Ouvre le formulaire de correction standard (pré-remplit grille/échelle/score).
        $this->startGrading($submission->id);

        // Puis pré-remplit avec le BROUILLON courant (édité), sans écraser un choix de grille.
        $this->gradeFeedback = $this->aiFeedbackDraft;

        $score = trim($this->aiSuggestedScoreDraft);
        if ($score !== '') {
            // On ne pré-remplit la note libre que si le devoir n'a ni grille ni échelle
            // (sinon la note est calculée par la sélection ; on laisse le formateur choisir).
            /** @var \Modules\Academy\Models\Assignment|null $assignment */
            $assignment = $submission->assignment;
            if ($assignment !== null && ! $assignment->hasRubric() && ! $assignment->hasScale()) {
                $this->gradeScore = (string) (int) round((float) $score);
            }
        }
    }

    /** Ferme le panneau de brouillon IA (n'efface pas ce qui est en base). */
    public function dismissAiFeedback(): void
    {
        $this->reset('aiFeedbackSubmission', 'aiFeedbackDraft', 'aiSuggestedScoreDraft', 'aiFeedbackError');
    }
}

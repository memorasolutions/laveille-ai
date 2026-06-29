<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Trait extrait du God-component CourseAssignments — Correction des remises
 * d'un devoir : ouverture du panneau de revue, chargement/annulation du
 * formulaire de correction, enregistrement de la note + feedback, correction
 * par grille (V2-a), correction par échelle (F14), notification étudiant (V5-c).
 *
 * SÉCURITÉ : chaque mutation re-résout le cours via $this->resolveCourse()
 * et re-autorise via $this->authorize('manageEnrollments', ...). La remise est
 * toujours re-résolue et scopée au cours (anti-IDOR). graded_by = auth()->id().
 * La note est bornée SERVEUR [0..max_points] du devoir re-résolu. Aucun
 * comportement modifié par rapport au composant d'origine.
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire\Concerns;

use Modules\Academy\Models\Assignment;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Submission;
use Modules\Academy\Services\GradebookService;

trait HandlesSubmissionGrading
{
    // ─────────────────────────────────────────────────────────────────────────
    // CORRIGER les remises d'un devoir - gâté manageEnrollments
    // ─────────────────────────────────────────────────────────────────────────

    /** Ouvre la liste des remises d'un devoir de CE cours (anti-IDOR). */
    public function reviewAssignment(int $assignmentId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageEnrollments', $course);

        // Re-résolution scopée : un devoir étranger → ModelNotFound.
        $assignment = $this->resolveAssignmentFor($course, $assignmentId);

        $this->reviewingAssignment = $assignment->id;
        $this->cancelGrading();
    }

    public function closeReview(): void
    {
        $this->reviewingAssignment = null;
        $this->cancelGrading();
    }

    /** Charge une remise de CE cours dans le formulaire de correction (anti-IDOR). */
    public function startGrading(int $submissionId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageEnrollments', $course);

        $submission = $this->resolveSubmissionFor($course, $submissionId);

        $this->gradingSubmission = $submission->id;
        $this->gradeScore        = $submission->score !== null ? (string) $submission->score : '';
        $this->gradeFeedback     = (string) $submission->feedback;

        // F14 : devoir noté par échelle -> pré-calculer les niveaux avec points convertis
        // (affichés dans le sélecteur) et pré-sélectionner le niveau correspondant au score
        // stocké (meilleure correspondance ; sinon laisser à choisir).
        $this->gradeScaleLevel       = '';
        $this->scaleLevelsWithPoints = [];
        $assignmentForScale          = $submission->assignment;
        if ($assignmentForScale !== null && $assignmentForScale->hasScale()) {
            // hasScale() a déjà chargé la relation 'scale' via loadMissing() : 0 requête extra.
            $scale  = $assignmentForScale->scale;
            $maxPts = (int) $assignmentForScale->max_points;
            foreach ($scale->levels() as $i => $lvl) {
                $points = GradebookService::scaleValueToPoints($scale, (float) $lvl['value'], $maxPts);
                $this->scaleLevelsWithPoints[$i] = [
                    'label'  => $lvl['label'],
                    'value'  => $lvl['value'],
                    'points' => $points,
                ];
                // Pré-sélectionner le niveau dont la conversion redonne le score stocké.
                if ($submission->score !== null && $points === (int) $submission->score) {
                    $this->gradeScaleLevel = (string) $i;
                }
            }
        }

        // V2-a : si une grille existe, pré-remplir la sélection {criterion_id: level_id}
        // depuis la correction antérieure (chaînes, pour des radios HTML).
        $this->rubricSelection = [];
        foreach ((array) ($submission->rubric_scores ?? []) as $criterionId => $levelId) {
            $this->rubricSelection[(int) $criterionId] = (string) $levelId;
        }

        $this->resetErrorBag(['gradeScore', 'gradeFeedback', 'rubricSelection']);
    }

    public function cancelGrading(): void
    {
        $this->reset('gradingSubmission', 'gradeScore', 'gradeFeedback', 'rubricSelection', 'gradeScaleLevel', 'scaleLevelsWithPoints');
        $this->resetErrorBag(['gradeScore', 'gradeFeedback', 'rubricSelection', 'gradeScaleLevel']);
    }

    /**
     * Enregistre une note + feedback sur une remise de CE cours (anti-IDOR).
     * La note est bornée SERVEUR à [0..max_points] du devoir re-résolu. Une note
     * hors bornes est REJETÉE (rien écrit). graded_by = utilisateur connecté.
     */
    public function gradeSubmission(): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageEnrollments', $course);

        if ($this->gradingSubmission === null) {
            return;
        }

        $submission = $this->resolveSubmissionFor($course, (int) $this->gradingSubmission);
        $assignment = $submission->assignment;

        // Chemins de correction par PRÉCÉDENCE :
        //  - AVEC grille (V2-a) -> un niveau par critère, note AUTO-calculée (mise à l'échelle).
        //  - AVEC échelle (F14) -> un niveau d'échelle, converti en points sur max_points.
        //  - SANS rien          -> correction manuelle (note libre bornée) : INCHANGÉ.
        // $course est déjà re-résolu + autorisé ci-dessus ; on le passe en paramètre
        // pour éviter une 2e requête redondante dans gradeWithRubric/gradeWithScale.
        if ($assignment->hasRubric()) {
            $this->gradeWithRubric($assignment, $submission, $course);

            return;
        }

        if ($assignment->hasScale()) {
            $this->gradeWithScale($assignment, $submission, $course);

            return;
        }

        $maxPoints = $assignment->max_points;

        // Borne dynamique = max_points du devoir (re-résolu serveur, jamais le client).
        $data = $this->validate([
            'gradeScore'    => 'required|integer|min:0|max:'.$maxPoints,
            'gradeFeedback' => 'nullable|string|max:20000',
        ], [
            'gradeScore.max' => "La note doit être comprise entre 0 et {$maxPoints}.",
            'gradeScore.min' => "La note doit être comprise entre 0 et {$maxPoints}.",
        ]);

        $submission->update([
            'score'     => (int) $data['gradeScore'],
            'feedback'  => $data['gradeFeedback'] !== '' ? $data['gradeFeedback'] : null,
            'graded_at' => now(),
            'graded_by' => auth()->id(),
        ]);

        $this->notifyGraded($course, $submission, $assignment);

        $this->cancelGrading();
        $this->flashSaved('Remise corrigée.');
    }

    /**
     * V5-c - Notifie l'étudiant que sa remise a été corrigée (note finale).
     * Gardée par l'interrupteur maître + préférence dans le service. Défensif :
     * ne casse jamais la correction. Le pourcentage est calculé sur max_points.
     */
    private function notifyGraded(Course $course, Submission $submission, Assignment $assignment): void
    {
        try {
            $student = $submission->user;
            if ($student === null) {
                return;
            }

            $max     = (int) ($assignment->max_points ?? 0);
            $percent = $max > 0 ? (int) round(((int) $submission->score) / $max * 100) : null;

            app(\Modules\Academy\Services\AcademyNotificationService::class)
                ->graded($student, $course, (string) $assignment->title, $percent, 'graded:submission-'.$submission->id);
        } catch (\Throwable) {
            // Best-effort.
        }
    }

    /**
     * V2-a - Correction PAR GRILLE : exige un niveau retenu par critère notable.
     * La note est AUTO-calculée (somme des points -> mise à l'échelle sur max_points,
     * bornée) ; les niveaux sont re-résolus côté modèle (anti-IDOR : un niveau
     * étranger est ignoré). rubric_scores ({criterion_id: level_id}) + score +
     * graded_at/by écrits ensemble. graded_by = utilisateur connecté.
     *
     * @param Course $course  Cours déjà re-résolu et autorisé par gradeSubmission()
     *                        (passé en paramètre pour éviter une requête redondante).
     */
    private function gradeWithRubric(Assignment $assignment, Submission $submission, Course $course): void
    {
        $this->validate(
            ['gradeFeedback' => 'nullable|string|max:20000'],
        );

        $result = $assignment->gradeFromRubricSelection($this->rubricSelection);

        if (! $result['complete']) {
            $this->addError('rubricSelection', 'Choisissez un niveau pour chaque critère de la grille.');

            return;
        }

        $submission->update([
            'score'         => $result['scaled'],
            'feedback'      => trim($this->gradeFeedback) !== '' ? $this->gradeFeedback : null,
            'rubric_scores' => $result['normalized'],
            'graded_at'     => now(),
            'graded_by'     => auth()->id(),
        ]);

        $this->notifyGraded($course, $submission, $assignment);

        $this->cancelGrading();
        $this->flashSaved('Remise corrigée selon la grille.');
    }

    /**
     * F14 - Correction PAR ÉCHELLE : le formateur choisit UN niveau de l'échelle du
     * devoir ; la valeur du niveau est CONVERTIE en points sur max_points par
     * GradebookService::scaleValueToPoints (formule documentée là-bas) puis stockée
     * comme une note numérique ordinaire (le carnet/CSV restent inchangés). Le niveau
     * est re-résolu SERVEUR contre les niveaux de l'échelle (anti-IDOR : un index
     * hors liste est rejeté). graded_by = utilisateur connecté.
     *
     * @param Course $course  Cours déjà re-résolu et autorisé par gradeSubmission()
     *                        (passé en paramètre pour éviter une requête redondante).
     */
    private function gradeWithScale(Assignment $assignment, Submission $submission, Course $course): void
    {
        $this->validate(['gradeFeedback' => 'nullable|string|max:20000']);

        // hasScale() (appelé par gradeSubmission) a déjà chargé 'scale' via loadMissing().
        $scale  = $assignment->scale; // garanti non null + niveaux exploitables
        $levels = $scale?->levels() ?? [];

        $idxRaw = trim($this->gradeScaleLevel);
        if ($idxRaw === '' || ! ctype_digit($idxRaw) || ! array_key_exists((int) $idxRaw, $levels)) {
            $this->addError('gradeScaleLevel', 'Choisissez un niveau de l\'échelle.');

            return;
        }

        $level = $levels[(int) $idxRaw];
        $score = GradebookService::scaleValueToPoints($scale, (float) $level['value'], (int) $assignment->max_points);

        $submission->update([
            'score'     => $score,
            'feedback'  => trim($this->gradeFeedback) !== '' ? $this->gradeFeedback : null,
            'graded_at' => now(),
            'graded_by' => auth()->id(),
        ]);

        $this->notifyGraded($course, $submission, $assignment);

        $this->cancelGrading();
        $this->flashSaved('Remise corrigée selon l\'échelle.');
    }
}

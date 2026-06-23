<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * GÉRANT des DEVOIRS d'un cours - PHASE E (E2). Composant Livewire rendu sous
 * l'éditeur de cours. Couvre : créer/éditer/publier/supprimer un devoir,
 * CORRIGER les remises (note + feedback), et le CARNET DE NOTES (gradebook).
 *
 * MODÈLE DE SÉCURITÉ (OWASP A01, autorisation SERVEUR - NON NÉGOCIABLE) :
 *  - L'identifiant du cours est figé au montage ($courseId, source de vérité).
 *  - À CHAQUE mutation, le cours est RE-RÉSOLU via resolveCourse() puis
 *    RÉ-AUTORISÉ par $this->authorize(...) sur la CoursePolicy :
 *      • CRÉER/ÉDITER/PUBLIER/SUPPRIMER un devoir → 'manageStructure'
 *        (admin OU owner/instructor/editor du cours) ;
 *      • CORRIGER une remise + GRADEBOOK            → 'manageEnrollments'
 *        (admin OU owner/instructor du cours).
 *  - On ne fait JAMAIS confiance à un ID/état du navigateur. Chaque Assignment /
 *    Submission ciblé est RE-RÉSOLU et SCOPÉ à CE cours (anti-IDOR) avant toute
 *    écriture : un objet d'un AUTRE cours → ModelNotFound, rien écrit.
 *  - La note est validée SERVEUR contre [0..max_points] du devoir re-résolu.
 *  - graded_by = auth()->id() (jamais une valeur du client).
 *  - @can(...) en Blade ne sert qu'à CACHER des boutons (jamais l'unique garde).
 *  - Le contenu markdown (consignes/feedback) est rendu SÛR (anti-XSS) via les
 *    accesseurs renderedInstructions()/renderedFeedback() des modèles.
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Modules\Academy\Models\Assignment;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\GradeCategory;
use Modules\Academy\Models\GradeItem;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Models\RubricCriterion;
use Modules\Academy\Models\RubricLevel;
use Modules\Academy\Models\Submission;
use Modules\Academy\Services\GradebookService;

class CourseAssignments extends Component
{
    /** Identifiant du cours géré (figé au montage, source de vérité serveur). */
    public int $courseId;

    // ── Saisie : créer / éditer un devoir ───────────────────────────────────────
    /** Devoir en cours d'édition (id), null = composition d'un nouveau. */
    public ?int $editingAssignment = null;

    public string $title = '';
    public string $instructions = '';
    public int $maxPoints = 100;
    public string $dueAt = '';
    /** Leçon de rattachement (id) ou '' = rattaché au cours entier. */
    public string $lessonId = '';

    // ── Correction : note + feedback d'une remise ───────────────────────────────
    /** Remise en cours de correction (id), null = aucune. */
    public ?int $gradingSubmission = null;
    /** Note saisie (chaîne brute du DOM, normalisée serveur). */
    public string $gradeScore = '';
    public string $gradeFeedback = '';

    /** Devoir dont on affiche les remises à corriger (id), null = aucun. */
    public ?int $reviewingAssignment = null;

    // ── Affichage du carnet de notes ────────────────────────────────────────────
    public bool $showGradebook = false;

    // ── Confirmations inline à 2 temps (jamais de popup native) ─────────────────
    public ?int $confirmingAssignmentRemoval = null;

    // ── V2-a : construction de la grille d'évaluation (rubric) ──────────────────
    /** Devoir dont le panneau « Grille » est ouvert (id), null = aucun. */
    public ?int $rubricAssignment = null;
    /** Saisie d'un NOUVEAU critère. */
    public string $newCriterion = '';
    /** Critère en cours d'édition (id) + son libellé. */
    public ?int $editingCriterion = null;
    public string $criterionDescription = '';
    /** Critère auquel on AJOUTE un niveau (id), null = aucun. */
    public ?int $addingLevelTo = null;
    /** Niveau en cours d'édition (id), null = aucun. */
    public ?int $editingLevel = null;
    /** Saisie d'un niveau (ajout ou édition) : libellé + points (chaîne du DOM). */
    public string $levelDescription = '';
    public string $levelPoints = '';
    /** Confirmations inline de suppression (jamais de popup native). */
    public ?int $confirmingCriterionRemoval = null;
    public ?int $confirmingLevelRemoval = null;

    // ── V2-a : correction PAR GRILLE (niveau retenu par critère) ────────────────
    /** Sélection {criterion_id: level_id} de la correction en cours (chaînes du DOM). */
    public array $rubricSelection = [];

    // ── V2-b : carnet de notes PONDÉRÉ (catégories + poids + lettres) ───────────
    /** Panneau de configuration de la pondération ouvert ? (gâté manageStructure) */
    public bool $showGradeStructure = false;
    /** Saisie d'une NOUVELLE catégorie (chaînes du DOM). */
    public string $newCategoryName = '';
    public string $newCategoryWeight = '';
    /** Catégorie en cours d'édition (id) + ses champs. */
    public ?int $editingCategory = null;
    public string $editCategoryName = '';
    public string $editCategoryWeight = '';
    /** Confirmation inline de suppression d'une catégorie (jamais de popup native). */
    public ?int $confirmingCategoryRemoval = null;
    /** Affectation item↔catégorie : maps indexées « {type}_{id} » (chaînes du DOM). */
    public array $itemCategoryMap = [];
    public array $itemWeightMap = [];
    /** Barème de lettres éditable : liste de {letter, min} (chaînes du DOM). */
    public array $letterBands = [];

    /**
     * Entrée. Autorisation SERVEUR d'affichage : pour voir CET espace il faut au
     * minimum pouvoir gérer la structure du cours (admin OU owner/instructor/editor).
     * La correction et le gradebook sont en plus gâtés par 'manageEnrollments'.
     */
    public function mount(Course $course): void
    {
        $this->authorize('manageStructure', $course);

        $this->courseId = $course->id;
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Résolution + autorisation serveur (cœur anti-escalade / anti-IDOR)
    // ─────────────────────────────────────────────────────────────────────────────

    /** Re-résout TOUJOURS le cours depuis la base (jamais depuis le navigateur). */
    private function resolveCourse(): Course
    {
        return Course::findOrFail($this->courseId);
    }

    /**
     * Re-résout un Assignment ET vérifie qu'il appartient bien à CE cours (anti-IDOR).
     * Un devoir d'un autre cours → ModelNotFound, aucune écriture possible.
     */
    private function resolveAssignmentFor(Course $course, int $assignmentId): Assignment
    {
        return Assignment::where('id', $assignmentId)
            ->where('course_id', $course->id)
            ->firstOrFail();
    }

    /**
     * Re-résout une Submission ET vérifie qu'elle appartient à un devoir de CE
     * cours (anti-IDOR), en remontant submission->assignment->course_id.
     */
    private function resolveSubmissionFor(Course $course, int $submissionId): Submission
    {
        return Submission::where('id', $submissionId)
            ->whereHas('assignment', fn ($q) => $q->where('course_id', $course->id))
            ->firstOrFail();
    }

    /**
     * Re-résout une leçon ET vérifie qu'elle appartient à un chapitre de CE cours
     * (anti-IDOR). Sert à valider le rattachement optionnel d'un devoir à une leçon.
     */
    private function resolveLessonFor(Course $course, int $lessonId): Lesson
    {
        return Lesson::where('lessons.id', $lessonId)
            ->whereHas('chapter', fn ($q) => $q->where('course_id', $course->id))
            ->firstOrFail();
    }

    /**
     * V2-a - Re-résout un critère de grille ET vérifie qu'il appartient à un devoir
     * de CE cours (anti-IDOR), en remontant criterion->assignment->course_id.
     */
    private function resolveCriterionFor(Course $course, int $criterionId): RubricCriterion
    {
        return RubricCriterion::where('id', $criterionId)
            ->whereHas('assignment', fn ($q) => $q->where('course_id', $course->id))
            ->firstOrFail();
    }

    /**
     * V2-a - Re-résout un niveau ET vérifie qu'il appartient à un critère d'un devoir
     * de CE cours (anti-IDOR), en remontant level->criterion->assignment->course_id.
     */
    private function resolveLevelFor(Course $course, int $levelId): RubricLevel
    {
        return RubricLevel::where('id', $levelId)
            ->whereHas('criterion.assignment', fn ($q) => $q->where('course_id', $course->id))
            ->firstOrFail();
    }

    /**
     * V2-b - Re-résout une catégorie de notes ET vérifie qu'elle appartient à CE
     * cours (anti-IDOR). Une catégorie d'un autre cours → ModelNotFound.
     */
    private function resolveCategoryFor(Course $course, int $categoryId): GradeCategory
    {
        return GradeCategory::where('id', $categoryId)
            ->where('course_id', $course->id)
            ->firstOrFail();
    }

    /**
     * V2-b - Un item (quiz|assignment) appartient-il à CE cours ? (anti-IDOR).
     * quiz : item de leçon via lesson→chapter→course ; assignment : course_id direct.
     */
    private function itemBelongsToCourse(Course $course, string $type, int $itemId): bool
    {
        if ($type === GradeItem::TYPE_QUIZ) {
            return LessonItem::where('id', $itemId)
                ->where('type', 'quiz')
                ->whereHas('lesson.chapter', fn ($q) => $q->where('course_id', $course->id))
                ->exists();
        }

        if ($type === GradeItem::TYPE_ASSIGNMENT) {
            return Assignment::where('id', $itemId)
                ->where('course_id', $course->id)
                ->exists();
        }

        return false;
    }

    private function flashSaved(string $msg): void
    {
        session()->flash('academy_assignments_status', $msg);
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // CRÉER / ÉDITER / PUBLIER / SUPPRIMER un devoir - gâté manageStructure
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Enregistre un devoir (création ou édition selon $editingAssignment).
     * $publish=true → is_published=true (visible des inscrits) ; false = brouillon.
     */
    public function saveAssignment(bool $publish = false): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        // SECURITY : paramètres du DOM = chaînes. On normalise '' → null AVANT
        // validation (jamais de cast ?int strict = TypeError/500).
        $lessonIdRaw = trim($this->lessonId) === '' ? null : (int) $this->lessonId;
        $dueAtRaw    = trim($this->dueAt) === '' ? null : $this->dueAt;

        $data = $this->validate([
            'title'        => 'required|string|max:200',
            'instructions' => 'nullable|string|max:20000',
            'maxPoints'    => 'required|integer|min:1|max:100000',
            'dueAt'        => 'nullable|date',
        ]);

        // Si une leçon est choisie, elle DOIT appartenir à CE cours (anti-IDOR).
        $resolvedLessonId = null;
        if ($lessonIdRaw !== null) {
            $resolvedLessonId = $this->resolveLessonFor($course, $lessonIdRaw)->id;
        }

        if ($this->editingAssignment !== null) {
            // ÉDITION : devoir re-résolu et scopé à CE cours (anti-IDOR).
            $assignment = $this->resolveAssignmentFor($course, (int) $this->editingAssignment);

            $assignment->title        = trim($data['title']);
            $assignment->instructions = $data['instructions'] !== '' ? $data['instructions'] : null;
            $assignment->max_points   = (int) $data['maxPoints'];
            $assignment->due_at       = $dueAtRaw;
            $assignment->lesson_id    = $resolvedLessonId;

            if ($publish) {
                $assignment->is_published = true;
            }

            $assignment->save();
            $message = $publish ? 'Devoir publié.' : 'Devoir enregistré.';
        } else {
            // CRÉATION : position = fin de liste pour CE cours.
            $position = (int) Assignment::where('course_id', $course->id)->max('position') + 1;

            Assignment::create([
                'course_id'    => $course->id,
                'lesson_id'    => $resolvedLessonId,
                'title'        => trim($data['title']),
                'instructions' => $data['instructions'] !== '' ? $data['instructions'] : null,
                'max_points'   => (int) $data['maxPoints'],
                'due_at'       => $dueAtRaw,
                'is_published' => $publish,
                'position'     => $position,
            ]);
            $message = $publish ? 'Devoir publié.' : 'Brouillon de devoir enregistré.';
        }

        $this->resetAssignmentForm();
        $this->flashSaved($message);
    }

    /** Charge un devoir de CE cours dans le formulaire (édition, anti-IDOR). */
    public function editAssignment(int $assignmentId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $assignment = $this->resolveAssignmentFor($course, $assignmentId);

        $this->editingAssignment = $assignment->id;
        $this->title             = $assignment->title;
        $this->instructions      = (string) $assignment->instructions;
        $this->maxPoints         = $assignment->max_points;
        $this->dueAt             = $assignment->due_at?->format('Y-m-d\TH:i') ?? '';
        $this->lessonId          = $assignment->lesson_id !== null ? (string) $assignment->lesson_id : '';
        $this->resetErrorBag();
    }

    /** Publie un devoir existant (brouillon → publié) de CE cours (anti-IDOR). */
    public function publishAssignment(int $assignmentId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $assignment = $this->resolveAssignmentFor($course, $assignmentId);
        $assignment->update(['is_published' => true]);

        $this->flashSaved('Devoir publié.');
    }

    /** Repasse un devoir publié en brouillon (retire des inscrits), anti-IDOR. */
    public function unpublishAssignment(int $assignmentId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $assignment = $this->resolveAssignmentFor($course, $assignmentId);
        $assignment->update(['is_published' => false]);

        $this->flashSaved('Devoir repassé en brouillon.');
    }

    /** Supprime un devoir de CE cours (re-résolu, anti-IDOR). Remises en cascade. */
    public function deleteAssignment(int $assignmentId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $assignment = $this->resolveAssignmentFor($course, $assignmentId);
        $assignment->delete();

        if ($this->editingAssignment === $assignmentId) {
            $this->resetAssignmentForm();
        }
        if ($this->reviewingAssignment === $assignmentId) {
            $this->reviewingAssignment = null;
        }
        $this->confirmingAssignmentRemoval = null;
        $this->flashSaved('Devoir supprimé.');
    }

    /** Réinitialise le formulaire de devoir (annule l'édition en cours). */
    public function resetAssignmentForm(): void
    {
        $this->reset('editingAssignment', 'title', 'instructions', 'dueAt', 'lessonId');
        $this->maxPoints = 100;
        $this->resetErrorBag();
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // CORRIGER les remises d'un devoir - gâté manageEnrollments
    // ─────────────────────────────────────────────────────────────────────────────

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
        $this->reset('gradingSubmission', 'gradeScore', 'gradeFeedback', 'rubricSelection');
        $this->resetErrorBag(['gradeScore', 'gradeFeedback', 'rubricSelection']);
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

        // V2-a : DEUX chemins selon que le devoir a une grille ACTIVE ou non.
        //  - SANS grille  → correction manuelle (note libre bornée) : INCHANGÉ.
        //  - AVEC grille  → un niveau par critère, note AUTO-calculée (mise à l'échelle).
        if ($assignment->hasRubric()) {
            $this->gradeWithRubric($assignment, $submission);

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
                ->graded($student, $course, (string) $assignment->title, $percent);
        } catch (\Throwable) {
            // Best-effort.
        }
    }

    /**
     * V2-a - Correction PAR GRILLE : exige un niveau retenu par critère notable.
     * La note est AUTO-calculée (somme des points → mise à l'échelle sur max_points,
     * bornée) ; les niveaux sont re-résolus côté modèle (anti-IDOR : un niveau
     * étranger est ignoré). rubric_scores ({criterion_id: level_id}) + score +
     * graded_at/by écrits ensemble. graded_by = utilisateur connecté.
     */
    private function gradeWithRubric(Assignment $assignment, Submission $submission): void
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

        $this->notifyGraded($this->resolveCourse(), $submission, $assignment);

        $this->cancelGrading();
        $this->flashSaved('Remise corrigée selon la grille.');
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // CARNET DE NOTES (gradebook) - gâté manageEnrollments
    // ─────────────────────────────────────────────────────────────────────────────

    public function toggleGradebook(): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageEnrollments', $course);

        $this->showGradebook = ! $this->showGradebook;
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Confirmations inline à 2 temps (jamais de popup native)
    // ─────────────────────────────────────────────────────────────────────────────

    public function confirmAssignmentRemoval(int $assignmentId): void
    {
        $this->confirmingAssignmentRemoval = $assignmentId;
    }

    public function cancelAssignmentRemoval(): void
    {
        $this->confirmingAssignmentRemoval = null;
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // V2-a : CONSTRUIRE la grille d'évaluation (rubric) - gâté manageStructure
    // ─────────────────────────────────────────────────────────────────────────────

    /** Ouvre le panneau « Grille » d'un devoir de CE cours (anti-IDOR). */
    public function openRubric(int $assignmentId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $assignment = $this->resolveAssignmentFor($course, $assignmentId);

        $this->rubricAssignment = $assignment->id;
        $this->resetRubricForms();
    }

    public function closeRubric(): void
    {
        $this->rubricAssignment = null;
        $this->resetRubricForms();
    }

    /** Réinitialise tous les sous-formulaires de la grille (sans fermer le panneau). */
    private function resetRubricForms(): void
    {
        $this->reset(
            'newCriterion', 'editingCriterion', 'criterionDescription',
            'addingLevelTo', 'editingLevel', 'levelDescription', 'levelPoints',
            'confirmingCriterionRemoval', 'confirmingLevelRemoval',
        );
        $this->resetErrorBag(['newCriterion', 'criterionDescription', 'levelDescription', 'levelPoints']);
    }

    /** Ajoute un critère au devoir dont le panneau est ouvert (anti-IDOR). */
    public function addCriterion(): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        if ($this->rubricAssignment === null) {
            return;
        }
        $assignment = $this->resolveAssignmentFor($course, (int) $this->rubricAssignment);

        $this->validate(
            ['newCriterion' => 'required|string|max:500'],
            [],
            ['newCriterion' => 'critère'],
        );

        $position = (int) RubricCriterion::where('assignment_id', $assignment->id)->max('position') + 1;

        RubricCriterion::create([
            'assignment_id' => $assignment->id,
            'description'   => trim($this->newCriterion),
            'position'      => $position,
        ]);

        $this->newCriterion = '';
        $this->resetErrorBag('newCriterion');
        $this->flashSaved('Critère ajouté.');
    }

    /** Charge un critère de CE cours en édition (anti-IDOR). */
    public function editCriterion(int $criterionId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $criterion = $this->resolveCriterionFor($course, $criterionId);

        $this->editingCriterion     = $criterion->id;
        $this->criterionDescription = $criterion->description;
        $this->resetErrorBag('criterionDescription');
    }

    public function saveCriterion(): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        if ($this->editingCriterion === null) {
            return;
        }
        $criterion = $this->resolveCriterionFor($course, (int) $this->editingCriterion);

        $this->validate(
            ['criterionDescription' => 'required|string|max:500'],
            [],
            ['criterionDescription' => 'critère'],
        );

        $criterion->update(['description' => trim($this->criterionDescription)]);

        $this->reset('editingCriterion', 'criterionDescription');
        $this->flashSaved('Critère modifié.');
    }

    public function cancelCriterionEdit(): void
    {
        $this->reset('editingCriterion', 'criterionDescription');
        $this->resetErrorBag('criterionDescription');
    }

    public function confirmCriterionRemoval(int $criterionId): void
    {
        $this->confirmingCriterionRemoval = $criterionId;
    }

    public function cancelCriterionRemoval(): void
    {
        $this->confirmingCriterionRemoval = null;
    }

    /** Supprime un critère de CE cours (niveaux en cascade), anti-IDOR. */
    public function deleteCriterion(int $criterionId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $criterion = $this->resolveCriterionFor($course, $criterionId);
        $criterion->delete();

        $this->confirmingCriterionRemoval = null;
        if ($this->editingCriterion === $criterionId) {
            $this->reset('editingCriterion', 'criterionDescription');
        }
        if ($this->addingLevelTo === $criterionId) {
            $this->reset('addingLevelTo', 'levelDescription', 'levelPoints');
        }
        $this->flashSaved('Critère supprimé.');
    }

    /** Ouvre le formulaire d'ajout d'un niveau à un critère de CE cours (anti-IDOR). */
    public function startAddLevel(int $criterionId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $criterion = $this->resolveCriterionFor($course, $criterionId);

        $this->addingLevelTo    = $criterion->id;
        $this->editingLevel     = null;
        $this->levelDescription = '';
        $this->levelPoints      = '';
        $this->resetErrorBag(['levelDescription', 'levelPoints']);
    }

    public function cancelAddLevel(): void
    {
        $this->reset('addingLevelTo', 'levelDescription', 'levelPoints');
        $this->resetErrorBag(['levelDescription', 'levelPoints']);
    }

    /** Ajoute un niveau au critère ciblé (re-résolu scopé au cours, anti-IDOR). */
    public function addLevel(): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        if ($this->addingLevelTo === null) {
            return;
        }
        $criterion = $this->resolveCriterionFor($course, (int) $this->addingLevelTo);

        $data = $this->validate([
            'levelDescription' => 'required|string|max:500',
            'levelPoints'      => 'required|integer|min:0|max:100000',
        ], [], [
            'levelDescription' => 'libellé du niveau',
            'levelPoints'      => 'points',
        ]);

        $position = (int) RubricLevel::where('criterion_id', $criterion->id)->max('position') + 1;

        RubricLevel::create([
            'criterion_id' => $criterion->id,
            'description'  => trim($data['levelDescription']),
            'points'       => (int) $data['levelPoints'],
            'position'     => $position,
        ]);

        $this->reset('addingLevelTo', 'levelDescription', 'levelPoints');
        $this->flashSaved('Niveau ajouté.');
    }

    /** Charge un niveau de CE cours en édition (anti-IDOR). */
    public function editLevel(int $levelId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $level = $this->resolveLevelFor($course, $levelId);

        $this->editingLevel     = $level->id;
        $this->addingLevelTo    = null;
        $this->levelDescription = $level->description;
        $this->levelPoints      = (string) $level->points;
        $this->resetErrorBag(['levelDescription', 'levelPoints']);
    }

    public function saveLevel(): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        if ($this->editingLevel === null) {
            return;
        }
        $level = $this->resolveLevelFor($course, (int) $this->editingLevel);

        $data = $this->validate([
            'levelDescription' => 'required|string|max:500',
            'levelPoints'      => 'required|integer|min:0|max:100000',
        ], [], [
            'levelDescription' => 'libellé du niveau',
            'levelPoints'      => 'points',
        ]);

        $level->update([
            'description' => trim($data['levelDescription']),
            'points'      => (int) $data['levelPoints'],
        ]);

        $this->reset('editingLevel', 'levelDescription', 'levelPoints');
        $this->flashSaved('Niveau modifié.');
    }

    public function cancelLevelEdit(): void
    {
        $this->reset('editingLevel', 'levelDescription', 'levelPoints');
        $this->resetErrorBag(['levelDescription', 'levelPoints']);
    }

    public function confirmLevelRemoval(int $levelId): void
    {
        $this->confirmingLevelRemoval = $levelId;
    }

    public function cancelLevelRemoval(): void
    {
        $this->confirmingLevelRemoval = null;
    }

    /** Supprime un niveau de CE cours (re-résolu scopé), anti-IDOR. */
    public function deleteLevel(int $levelId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $level = $this->resolveLevelFor($course, $levelId);
        $level->delete();

        $this->confirmingLevelRemoval = null;
        if ($this->editingLevel === $levelId) {
            $this->reset('editingLevel', 'levelDescription', 'levelPoints');
        }
        $this->flashSaved('Niveau supprimé.');
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // V2-b : CARNET PONDÉRÉ — catégories, affectation des items, lettres
    // (configuration gâtée manageStructure ; export gâté manageEnrollments)
    // ─────────────────────────────────────────────────────────────────────────────

    /** Ouvre/ferme le panneau de pondération et (ré)hydrate les maps depuis la BD. */
    public function toggleGradeStructure(): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $this->showGradeStructure = ! $this->showGradeStructure;

        if ($this->showGradeStructure) {
            $this->loadGradeStructure();
        }
    }

    /** Recharge les affectations item↔catégorie + le barème de lettres depuis la BD. */
    private function loadGradeStructure(): void
    {
        $course = $this->resolveCourse();

        $this->itemCategoryMap = [];
        $this->itemWeightMap   = [];
        foreach (GradebookService::gradableItems($course) as $item) {
            $key = $item['type'].'_'.$item['id'];
            $this->itemCategoryMap[$key] = $item['category_id'] !== null ? (string) $item['category_id'] : '';
            $this->itemWeightMap[$key]   = rtrim(rtrim((string) $item['weight'], '0'), '.') ?: '1';
        }

        $this->letterBands = [];
        foreach (GradebookService::letterSchemeFor($course) as $band) {
            $this->letterBands[] = [
                'letter' => (string) $band['letter'],
                'min'    => rtrim(rtrim((string) $band['min'], '0'), '.') ?: '0',
            ];
        }
    }

    /** Ajoute une catégorie de notes (gâté manageStructure, scopé au cours). */
    public function addCategory(): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $data = $this->validate([
            'newCategoryName'   => 'required|string|max:120',
            'newCategoryWeight' => 'required|numeric|min:0|max:100',
        ], [], [
            'newCategoryName'   => 'nom de la catégorie',
            'newCategoryWeight' => 'poids',
        ]);

        $position = (int) GradeCategory::where('course_id', $course->id)->max('position') + 1;

        GradeCategory::create([
            'course_id' => $course->id,
            'name'      => trim($data['newCategoryName']),
            'weight'    => (float) $data['newCategoryWeight'],
            'position'  => $position,
        ]);

        $this->reset('newCategoryName', 'newCategoryWeight');
        $this->loadGradeStructure();
        $this->flashSaved('Catégorie ajoutée.');
    }

    /** Charge une catégorie de CE cours en édition (anti-IDOR). */
    public function editCategory(int $categoryId): void
    {
        $course   = $this->resolveCourse();
        $this->authorize('manageStructure', $course);
        $category = $this->resolveCategoryFor($course, $categoryId);

        $this->editingCategory    = $category->id;
        $this->editCategoryName   = $category->name;
        $this->editCategoryWeight = rtrim(rtrim((string) $category->weight, '0'), '.') ?: '0';
        $this->resetErrorBag(['editCategoryName', 'editCategoryWeight']);
    }

    public function saveCategory(): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        if ($this->editingCategory === null) {
            return;
        }
        $category = $this->resolveCategoryFor($course, (int) $this->editingCategory);

        $data = $this->validate([
            'editCategoryName'   => 'required|string|max:120',
            'editCategoryWeight' => 'required|numeric|min:0|max:100',
        ], [], [
            'editCategoryName'   => 'nom de la catégorie',
            'editCategoryWeight' => 'poids',
        ]);

        $category->update([
            'name'   => trim($data['editCategoryName']),
            'weight' => (float) $data['editCategoryWeight'],
        ]);

        $this->reset('editingCategory', 'editCategoryName', 'editCategoryWeight');
        $this->flashSaved('Catégorie modifiée.');
    }

    public function cancelCategoryEdit(): void
    {
        $this->reset('editingCategory', 'editCategoryName', 'editCategoryWeight');
        $this->resetErrorBag(['editCategoryName', 'editCategoryWeight']);
    }

    public function confirmCategoryRemoval(int $categoryId): void
    {
        $this->confirmingCategoryRemoval = $categoryId;
    }

    public function cancelCategoryRemoval(): void
    {
        $this->confirmingCategoryRemoval = null;
    }

    /**
     * Supprime une catégorie de CE cours (anti-IDOR). Les items affectés deviennent
     * « non classés » (grade_category_id → null via FK set null), JAMAIS supprimés.
     */
    public function deleteCategory(int $categoryId): void
    {
        $course   = $this->resolveCourse();
        $this->authorize('manageStructure', $course);
        $category = $this->resolveCategoryFor($course, $categoryId);

        $category->delete();

        $this->confirmingCategoryRemoval = null;
        if ($this->editingCategory === $categoryId) {
            $this->reset('editingCategory', 'editCategoryName', 'editCategoryWeight');
        }
        $this->loadGradeStructure();
        $this->flashSaved('Catégorie supprimée (les items sont devenus non classés).');
    }

    /**
     * Enregistre l'affectation de CHAQUE item à une catégorie + son poids. Chaque
     * item et chaque catégorie sont RE-RÉSOLUS scopés à CE cours (anti-IDOR) ; une
     * valeur étrangère est ignorée. Catégorie vide = item retiré de la pondération
     * (ligne supprimée, jamais d'autre donnée touchée).
     */
    public function saveItemAssignments(): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        foreach ($this->itemCategoryMap as $key => $catRaw) {
            [$type, $idStr] = array_pad(explode('_', (string) $key, 2), 2, null);
            if (! in_array($type, GradeItem::TYPES, true) || $idStr === null || ! ctype_digit((string) $idStr)) {
                continue;
            }
            $itemId = (int) $idStr;

            // Anti-IDOR : l'item doit appartenir à CE cours.
            if (! $this->itemBelongsToCourse($course, $type, $itemId)) {
                continue;
            }

            $catId = trim((string) $catRaw) === '' ? null : (int) $catRaw;
            // Anti-IDOR : la catégorie (si fournie) doit appartenir à CE cours.
            if ($catId !== null && ! GradeCategory::where('id', $catId)->where('course_id', $course->id)->exists()) {
                $catId = null;
            }

            $weightRaw = $this->itemWeightMap[$key] ?? '1';
            $weight    = is_numeric($weightRaw) ? max(0.0, (float) $weightRaw) : 1.0;

            if ($catId === null) {
                GradeItem::where('course_id', $course->id)
                    ->where('item_type', $type)
                    ->where('item_id', $itemId)
                    ->delete();

                continue;
            }

            GradeItem::updateOrCreate(
                ['course_id' => $course->id, 'item_type' => $type, 'item_id' => $itemId],
                ['grade_category_id' => $catId, 'weight' => $weight],
            );
        }

        $this->loadGradeStructure();
        $this->flashSaved('Affectations enregistrées.');
    }

    /** Ajoute une bande au barème de lettres en cours d'édition (non persistée). */
    public function addLetterBand(): void
    {
        $this->letterBands[] = ['letter' => '', 'min' => '0'];
    }

    /** Retire une bande du barème en cours d'édition (non persistée). */
    public function removeLetterBand(int $index): void
    {
        if (array_key_exists($index, $this->letterBands)) {
            unset($this->letterBands[$index]);
            $this->letterBands = array_values($this->letterBands);
        }
    }

    /**
     * Enregistre le barème de lettres du cours (JSON courses.grade_letter_scheme).
     * Barème vide/illisible → null = défaut raisonnable (rétrocompat). Gâté
     * manageStructure, cours re-résolu (anti-IDOR).
     */
    public function saveLetterScheme(): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $clean = GradebookService::sanitizeScheme($this->letterBands);

        $course->update(['grade_letter_scheme' => $clean === [] ? null : $clean]);

        $this->loadGradeStructure();
        $this->flashSaved('Barème de lettres enregistré.');
    }

    /**
     * EXPORT CSV du carnet (inscrits × items + note finale + lettre). Génération
     * SERVEUR (StreamedResponse), BOM UTF-8 + séparateur « ; » (Excel FR). Gâté
     * manageEnrollments, cours re-résolu et données scopées au cours (anti-IDOR).
     */
    public function exportGradebookCsv(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $course = $this->resolveCourse();
        $this->authorize('manageEnrollments', $course);

        $csv      = GradebookService::buildCsv($course);
        $filename = 'carnet-notes-'.$course->slug.'-'.now('America/Toronto')->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($csv): void {
            echo $csv;
        }, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Lecture (affichage) - listes fraîches, scopées à CE cours
    // ─────────────────────────────────────────────────────────────────────────────

    /** Le cours frais (sert les directives @can dans la vue). */
    #[Computed]
    public function course(): Course
    {
        return Course::findOrFail($this->courseId);
    }

    /** Peut-il corriger + voir le gradebook ? (manageEnrollments) */
    #[Computed]
    public function canGrade(): bool
    {
        return (bool) auth()->user()?->can('manageEnrollments', $this->course);
    }

    /** Devoirs de CE cours, avec le compte de remises et de remises corrigées. */
    #[Computed]
    public function assignments(): EloquentCollection
    {
        return Assignment::where('course_id', $this->courseId)
            ->withCount([
                'submissions',
                'submissions as graded_count' => fn ($q) => $q->whereNotNull('graded_at'),
            ])
            ->with('lesson:id,title')
            ->orderBy('position')
            ->orderBy('id')
            ->get();
    }

    /** Leçons de CE cours (pour le sélecteur de rattachement), ordonnées. */
    #[Computed]
    public function lessons(): EloquentCollection
    {
        return Lesson::query()
            ->whereHas('chapter', fn ($q) => $q->where('course_id', $this->courseId))
            ->join('chapters', 'lessons.chapter_id', '=', 'chapters.id')
            ->where('chapters.course_id', $this->courseId)
            ->orderBy('chapters.position')
            ->orderBy('lessons.position')
            ->select('lessons.*')
            ->get();
    }

    /**
     * Remises du devoir en revue (re-validé scopé à CE cours), avec l'étudiant et
     * le correcteur. Renvoie une collection vide si aucun devoir n'est en revue ou
     * s'il n'appartient pas à CE cours (anti-IDOR d'affichage).
     */
    #[Computed]
    public function reviewSubmissions(): EloquentCollection
    {
        if ($this->reviewingAssignment === null) {
            return new EloquentCollection();
        }

        $assignment = Assignment::where('id', $this->reviewingAssignment)
            ->where('course_id', $this->courseId)
            ->first();

        if ($assignment === null) {
            return new EloquentCollection();
        }

        return Submission::where('assignment_id', $assignment->id)
            ->with(['user:id,name,email', 'grader:id,name'])
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->get();
    }

    /** Le devoir actuellement en revue (objet frais), ou null. */
    #[Computed]
    public function reviewedAssignment(): ?Assignment
    {
        if ($this->reviewingAssignment === null) {
            return null;
        }

        return Assignment::where('id', $this->reviewingAssignment)
            ->where('course_id', $this->courseId)
            ->first();
    }

    /**
     * V2-a - Critères + niveaux du devoir dont le panneau « Grille » est ouvert,
     * scopés à CE cours (anti-IDOR d'affichage). Collection vide si aucun panneau.
     */
    #[Computed]
    public function rubricCriteria(): EloquentCollection
    {
        if ($this->rubricAssignment === null) {
            return new EloquentCollection();
        }

        $assignment = Assignment::where('id', $this->rubricAssignment)
            ->where('course_id', $this->courseId)
            ->first();

        if ($assignment === null) {
            return new EloquentCollection();
        }

        return RubricCriterion::where('assignment_id', $assignment->id)
            ->with('levels')
            ->orderBy('position')
            ->orderBy('id')
            ->get();
    }

    /**
     * V2-a - Critères + niveaux du devoir de la remise EN COURS de correction,
     * scopés à CE cours (anti-IDOR). Sert à afficher les radios de la grille.
     * Collection vide si aucune correction ouverte ou si le devoir n'a pas de grille.
     */
    #[Computed]
    public function gradingCriteria(): EloquentCollection
    {
        if ($this->gradingSubmission === null) {
            return new EloquentCollection();
        }

        $submission = Submission::where('id', $this->gradingSubmission)
            ->whereHas('assignment', fn ($q) => $q->where('course_id', $this->courseId))
            ->first();

        if ($submission === null) {
            return new EloquentCollection();
        }

        return RubricCriterion::where('assignment_id', $submission->assignment_id)
            ->with('levels')
            ->orderBy('position')
            ->orderBy('id')
            ->get();
    }

    /** V2-b - Catégories de notes de CE cours (config de la pondération). */
    #[Computed]
    public function gradeCategories(): EloquentCollection
    {
        return GradeCategory::forCourse($this->courseId)->get();
    }

    /**
     * V2-b - Items notables de CE cours avec leur affectation actuelle (pour le
     * tableau de configuration). Source unique GradebookService (DRY).
     *
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function gradableItems(): array
    {
        return GradebookService::gradableItems(Course::findOrFail($this->courseId))->all();
    }

    /**
     * Données du carnet de notes (gradebook) : inscrits actifs × devoirs, scopés à
     * CE cours. Pour chaque inscrit : sa note par devoir (ou null = non remis/non
     * corrigé) + sa note de quiz agrégée.
     *
     * V1-c - la colonne quiz reflète désormais la NOTE EFFECTIVE de chaque item quiz
     * via QuizGradeService::effectiveGrade (qui applique la méthode de notation de
     * l'item : highest/average/first/last sur l'historique des tentatives), au lieu
     * d'une simple somme de Completion.score. AGRÉGATION choisie quand un cours a
     * PLUSIEURS items quiz : MOYENNE des percent effectifs des items où l'étudiant a
     * au moins une tentative (cohérent, borné 0..100, indépendant du barème en points
     * et du nombre de quiz). Lecture seule, gâté manageEnrollments (anti-IDOR inchangé).
     *
     * @return array{students: array<int, array<string, mixed>>, assignments: \Illuminate\Support\Collection, quizTotal: int}
     */
    #[Computed]
    public function gradebook(): array
    {
        $assignments = Assignment::where('course_id', $this->courseId)
            ->orderBy('position')
            ->orderBy('id')
            ->get(['id', 'title', 'max_points']);

        $assignmentIds = $assignments->pluck('id');

        $enrollments = Enrollment::where('course_id', $this->courseId)
            ->where('status', 'active')
            ->with('user:id,name,email')
            ->get()
            ->sortBy(fn ($e) => $e->user?->name ?? '')
            ->values();

        // Toutes les remises corrigées de ces devoirs, indexées [user_id][assignment_id] → score.
        $scores = Submission::whereIn('assignment_id', $assignmentIds)
            ->whereNotNull('graded_at')
            ->get(['user_id', 'assignment_id', 'score'])
            ->groupBy('user_id');

        // V1-c : items quiz de CE cours (via item→lesson→chapter), re-résolus serveur.
        $quizItems = LessonItem::query()
            ->where('type', 'quiz')
            ->whereHas('lesson.chapter', fn ($q) => $q->where('course_id', $this->courseId))
            ->get();

        // V2-b : pré-calcul UNIQUE (DRY) pour la note finale pondérée. Si le cours
        // n'a AUCUNE catégorie, $weighted = false → carnet INCHANGÉ (rétrocompat).
        $course        = Course::findOrFail($this->courseId);
        $categories    = GradeCategory::forCourse($this->courseId)->get();
        $weighted      = $categories->isNotEmpty();
        $weightedItems = $weighted ? GradebookService::gradableItems($course)->all() : [];

        $students = $enrollments->map(function ($enrollment) use ($assignments, $scores, $quizItems, $course, $categories, $weighted, $weightedItems): array {
            $userId      = $enrollment->user_id;
            $userScores  = $scores->get($userId, collect())->keyBy('assignment_id');

            $cells = $assignments->map(function ($assignment) use ($userScores): ?int {
                $row = $userScores->get($assignment->id);

                return $row?->score;
            });

            // Note de quiz effective de l'étudiant = moyenne des percent effectifs des
            // items quiz où il a tenté (méthode de notation de chaque item appliquée).
            $effective = [];
            foreach ($quizItems as $quizItem) {
                $grade = \Modules\Academy\Services\QuizGradeService::effectiveGrade($userId, $quizItem);
                // ESSAI : un item dont la (seule) tentative est en attente de correction
                // est « à corriger » → exclu de la moyenne (ni 0 ni 100). Sa note finale
                // entrera dès la correction terminée (effectiveGrade ne renvoie alors plus
                // pending). Un quiz sans essai n'est jamais pending → INCHANGÉ.
                if ($grade['attempts'] > 0 && empty($grade['pending'])) {
                    $effective[] = $grade['percent'];
                }
            }
            $quizScore = $effective === [] ? 0 : (int) round(array_sum($effective) / count($effective));

            $row = [
                'user'      => $enrollment->user,
                'cells'     => $cells,           // alignées sur l'ordre de $assignments
                'quizScore' => $quizScore,       // percent effectif moyen (0..100)
                'final'     => null,
                'letter'    => '',
                'catScores' => [],
            ];

            // V2-b : note finale pondérée + lettre + détail par catégorie (si pondéré).
            if ($weighted && $enrollment->user !== null) {
                $g                = GradebookService::finalGradeFor($enrollment->user, $course, $weightedItems, $categories);
                $row['final']     = $g['final'];
                $row['letter']    = $g['letter'];
                $row['catScores'] = $g['categories'];
            }

            return $row;
        })->all();

        // quizTotal sert d'en-tête de colonne : 100 si au moins un item quiz existe
        // (les notes sont des pourcentages effectifs), 0 sinon.
        $quizTotal = $quizItems->isNotEmpty() ? 100 : 0;

        return [
            'students'    => $students,
            'assignments' => $assignments,
            'quizTotal'   => $quizTotal,
            'weighted'    => $weighted,
            'categories'  => $categories,
        ];
    }

    public function render()
    {
        return view('academy::livewire.course-assignments');
    }
}

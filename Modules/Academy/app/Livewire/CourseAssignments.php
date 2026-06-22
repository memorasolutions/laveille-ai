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
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Models\Submission;

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
        $this->resetErrorBag(['gradeScore', 'gradeFeedback']);
    }

    public function cancelGrading(): void
    {
        $this->reset('gradingSubmission', 'gradeScore', 'gradeFeedback');
        $this->resetErrorBag(['gradeScore', 'gradeFeedback']);
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
        $maxPoints  = $submission->assignment->max_points;

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

        $this->cancelGrading();
        $this->flashSaved('Remise corrigée.');
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

        $students = $enrollments->map(function ($enrollment) use ($assignments, $scores, $quizItems): array {
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
                if ($grade['attempts'] > 0) {
                    $effective[] = $grade['percent'];
                }
            }
            $quizScore = $effective === [] ? 0 : (int) round(array_sum($effective) / count($effective));

            return [
                'user'      => $enrollment->user,
                'cells'     => $cells,           // alignées sur l'ordre de $assignments
                'quizScore' => $quizScore,       // percent effectif moyen (0..100)
            ];
        })->all();

        // quizTotal sert d'en-tête de colonne : 100 si au moins un item quiz existe
        // (les notes sont des pourcentages effectifs), 0 sinon.
        $quizTotal = $quizItems->isNotEmpty() ? 100 : 0;

        return [
            'students'    => $students,
            'assignments' => $assignments,
            'quizTotal'   => $quizTotal,
        ];
    }

    public function render()
    {
        return view('academy::livewire.course-assignments');
    }
}

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
 *
 * Groupes cohésifs extraits en traits (Concerns/) :
 *  - HandlesAssignmentCrud    : CRUD devoirs + confirmations + resolveSelectableScaleId
 *  - HandlesSubmissionGrading : correction remises, grille, échelle, notification
 *  - HandlesRubric            : construction grille (critères + niveaux)
 *  - HandlesGradeStructure    : carnet pondéré, catégories, barème lettres, export CSV
 *  - HandlesScales            : échelles personnalisées (F14) + selectableScales
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Modules\Academy\Livewire\Concerns\HandlesAssignmentCrud;
use Modules\Academy\Livewire\Concerns\HandlesGradeStructure;
use Modules\Academy\Livewire\Concerns\HandlesRubric;
use Modules\Academy\Livewire\Concerns\HandlesScales;
use Modules\Academy\Livewire\Concerns\HandlesSubmissionGrading;
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
    use HandlesAssignmentCrud;
    use HandlesSubmissionGrading;
    use HandlesRubric;
    use HandlesGradeStructure;
    use HandlesScales;

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
    /** F14 - Échelle d'évaluation (id) ou '' = note numérique (rétrocompat). */
    public string $scaleId = '';

    // ── Correction : note + feedback d'une remise ───────────────────────────────
    /** Remise en cours de correction (id), null = aucune. */
    public ?int $gradingSubmission = null;
    /** Note saisie (chaîne brute du DOM, normalisée serveur). */
    public string $gradeScore = '';
    public string $gradeFeedback = '';
    /** F14 - Niveau d'échelle retenu à la correction (index dans les niveaux), '' = aucun. */
    public string $gradeScaleLevel = '';
    /**
     * F14 - Niveaux de l'échelle du devoir en cours de correction, avec leur
     * équivalence en points pré-calculée côté composant (évite la logique lourde
     * dans la vue). Format : [{label: string, value: float, points: int}, ...].
     * Peuplé par startGrading(), vidé par cancelGrading().
     *
     * @var array<int, array{label: string, value: float, points: int}>
     */
    public array $scaleLevelsWithPoints = [];

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
    /** F14 - Méthode d'agrégation des catégories (liste blanche serveur). */
    public string $newCategoryMethod = GradeCategory::AGGREGATION_WEIGHTED_MEAN;
    public string $editCategoryMethod = GradeCategory::AGGREGATION_WEIGHTED_MEAN;
    /** Confirmation inline de suppression d'une catégorie (jamais de popup native). */
    public ?int $confirmingCategoryRemoval = null;
    /** Affectation item↔catégorie : maps indexées « {type}_{id} » (chaînes du DOM). */
    public array $itemCategoryMap = [];
    public array $itemWeightMap = [];
    /** Barème de lettres éditable : liste de {letter, min} (chaînes du DOM). */
    public array $letterBands = [];

    // ── F14 : ÉCHELLES personnalisées (scales) : CRUD owner-scopé ───────────────
    /** Panneau de gestion des échelles ouvert ? */
    public bool $showScales = false;
    /** Saisie d'une NOUVELLE échelle (niveaux = textarea « libellé | valeur » par ligne). */
    public string $newScaleName = '';
    public string $newScaleItems = '';
    /** Échelle en cours d'édition (id) + ses champs. */
    public ?int $editingScale = null;
    public string $editScaleName = '';
    public string $editScaleItems = '';
    /** Confirmation inline de suppression d'une échelle (jamais de popup native). */
    public ?int $confirmingScaleRemoval = null;

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
    // Helpers PARTAGÉS entre tous les traits — restent dans le composant.
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
            $userId     = $enrollment->user_id;
            $userScores = $scores->get($userId, collect())->keyBy('assignment_id');

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

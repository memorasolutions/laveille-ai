<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F23 - RAPPORTS et JOURNAUX (parité Moodle « reports / logs »). SOURCE UNIQUE
 * (DRY) en LECTURE SEULE de deux rapports internes destinés au formateur :
 *
 *   1. PARTICIPATION (par étudiant) : pour chaque inscrit actif, agrège la
 *      progression (%), la dernière activité, les items complétés / total, la
 *      note finale (via GradebookService) et un statut synthétique.
 *
 *   2. JOURNAL d'activité (logs) : liste chronologique des événements du cours
 *      (consultation d'item, complétion, tentative de quiz, remise de devoir),
 *      dérivée des horodatages des tables existantes. Le projet n'alimente PAS
 *      spatie/activitylog pour ces évènements pédagogiques (la dépendance sert
 *      les services Certificate/Enrollment/Completion côté audit technique, pas
 *      le détail item-par-item) ; on dérive donc le journal des timestamps
 *      canoniques déjà persistés (Completion.started_at / completed_at,
 *      QuizAttempt.submitted_at, Submission.submitted_at).
 *
 * SÉCURITÉ : toutes les requêtes sont SCOPÉES au cours passé (where course_id =
 * $course->id, ou jointure via les items / devoirs du cours). Aucune agrégation
 * cross-cours, aucune fuite (anti-IDOR au niveau données). Le contrôle d'accès
 * (authorize manageEnrollments) reste la responsabilité de l'appelant ; ce
 * service ne calcule QUE sur un cours déjà autorisé et ne mute jamais rien.
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

use Illuminate\Support\Collection;
use Modules\Academy\Models\Completion;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\Progress;
use Modules\Academy\Models\QuizAttempt;
use Modules\Academy\Models\Submission;

final class CourseReportService
{
    /** Plafond d'évènements lus PAR SOURCE pour le journal (borne mémoire / perf). */
    public const ACTIVITY_LOG_CAP = 1000;

    /**
     * RAPPORT DE PARTICIPATION : une ligne PAR inscrit ACTIF de CE cours.
     *
     * Agrégations par lot (whereIn) pour éviter le N+1 : une requête par source
     * (progression, complétions, tentatives, remises), puis assemblage en PHP.
     *
     * Statut synthétique (status_key) :
     *  - 'completed'     : progression >= 100 % ;
     *  - 'never_started' : aucune activité enregistrée (ni vue, ni complétion, ni
     *                      tentative, ni remise) ET progression 0 % ;
     *  - 'in_progress'   : tout le reste (a commencé mais pas terminé).
     *
     * « Dernière activité » = max( Progress.last_activity_at, dernière complétion,
     * dernière tentative de quiz, dernière remise de devoir ). null si rien.
     *
     * @return Collection<int, array{
     *   user_id:int, name:string, email:string,
     *   percent:int, items_completed:int, items_total:int,
     *   last_activity:\Illuminate\Support\Carbon|null,
     *   grade:array{hasWeighting:bool, final:float, letter:string},
     *   status:string, status_key:string
     * }>
     */
    public function participation(Course $course): Collection
    {
        $enrollments = Enrollment::query()
            ->where('course_id', $course->id)
            ->where('status', 'active')
            ->with(['user:id,name,email'])
            ->get();

        if ($enrollments->isEmpty()) {
            return collect();
        }

        $userIds = $enrollments->pluck('user_id')->unique()->all();

        // Items REQUIS du cours (dénominateur « items complétés / total »), cohérent
        // avec la définition de progression de ProgressService. Scopé au cours via la
        // structure chapitre → leçon → item.
        $requiredItemIds = $this->requiredItemIds($course);
        $itemsTotal = count($requiredItemIds);

        // Progression persistée scopée au cours et à ses inscrits actifs.
        $progressByUser = Progress::query()
            ->where('course_id', $course->id)
            ->whereIn('user_id', $userIds)
            ->get()
            ->keyBy('user_id');

        // Complétions terminées par utilisateur (compteur + dernière date), bornées
        // aux items requis du cours et aux inscrits actifs.
        $completedByUser = [];
        $lastCompletionByUser = [];
        if ($itemsTotal > 0) {
            Completion::query()
                ->where('course_id', $course->id)
                ->where('status', 'completed')
                ->whereIn('lesson_item_id', $requiredItemIds)
                ->whereIn('user_id', $userIds)
                ->get(['user_id', 'completed_at'])
                ->each(function ($row) use (&$completedByUser, &$lastCompletionByUser): void {
                    $completedByUser[$row->user_id] = ($completedByUser[$row->user_id] ?? 0) + 1;
                    if ($row->completed_at !== null) {
                        $current = $lastCompletionByUser[$row->user_id] ?? null;
                        if ($current === null || $row->completed_at->gt($current)) {
                            $lastCompletionByUser[$row->user_id] = $row->completed_at;
                        }
                    }
                });
        }

        // Toute activité (vue OU complétion) pour distinguer « jamais commencé ».
        $hasAnyCompletionRow = Completion::query()
            ->where('course_id', $course->id)
            ->whereIn('user_id', $userIds)
            ->pluck('user_id')
            ->flip();

        // Dernière tentative de quiz par utilisateur (scopée au cours).
        $lastAttemptByUser = QuizAttempt::query()
            ->where('course_id', $course->id)
            ->whereIn('user_id', $userIds)
            ->selectRaw('user_id, MAX(submitted_at) as last_at')
            ->groupBy('user_id')
            ->pluck('last_at', 'user_id');

        // Dernière remise de devoir par utilisateur (devoirs de CE cours).
        $lastSubmissionByUser = Submission::query()
            ->whereHas('assignment', fn ($q) => $q->where('course_id', $course->id))
            ->whereIn('user_id', $userIds)
            ->whereNotNull('submitted_at')
            ->selectRaw('user_id, MAX(submitted_at) as last_at')
            ->groupBy('user_id')
            ->pluck('last_at', 'user_id');

        // Note finale : on précalcule items + catégories UNE fois (DRY + perf) et on
        // les réinjecte dans GradebookService::finalGradeFor par utilisateur.
        $gradeCategories = \Modules\Academy\Models\GradeCategory::forCourse($course->id)->get();
        $gradeItems = $gradeCategories->isEmpty() ? [] : GradebookService::gradableItems($course)->all();

        $rows = collect();

        foreach ($enrollments as $enrollment) {
            $user = $enrollment->user;
            if ($user === null) {
                continue; // utilisateur supprimé : rien à reporter
            }

            $uid = (int) $enrollment->user_id;
            $progress = $progressByUser->get($uid);
            $percent = (int) ($progress->percent ?? 0);

            // Dernière activité = max de toutes les sources horodatées.
            $candidates = array_filter([
                $progress?->last_activity_at,
                $lastCompletionByUser[$uid] ?? null,
                isset($lastAttemptByUser[$uid]) ? \Illuminate\Support\Carbon::parse($lastAttemptByUser[$uid]) : null,
                isset($lastSubmissionByUser[$uid]) ? \Illuminate\Support\Carbon::parse($lastSubmissionByUser[$uid]) : null,
            ]);

            $lastActivity = null;
            foreach ($candidates as $candidate) {
                if ($lastActivity === null || $candidate->gt($lastActivity)) {
                    $lastActivity = $candidate;
                }
            }

            $hasActivity = $hasAnyCompletionRow->has($uid)
                || isset($lastAttemptByUser[$uid])
                || isset($lastSubmissionByUser[$uid])
                || $progress?->last_activity_at !== null;

            if ($percent >= 100) {
                $statusKey = 'completed';
                $status = 'Complété';
            } elseif (! $hasActivity && $percent === 0) {
                $statusKey = 'never_started';
                $status = 'Jamais commencé';
            } else {
                $statusKey = 'in_progress';
                $status = 'En cours';
            }

            $grade = empty($gradeItems)
                ? ['hasWeighting' => false, 'final' => 0.0, 'letter' => '']
                : GradebookService::finalGradeFor($user, $course, $gradeItems, $gradeCategories);

            $rows->push([
                'user_id'         => $uid,
                'name'            => (string) ($user->name ?? 'Apprenant'),
                'email'           => (string) ($user->email ?? ''),
                'percent'         => $percent,
                'items_completed' => (int) ($completedByUser[$uid] ?? 0),
                'items_total'     => $itemsTotal,
                'last_activity'   => $lastActivity,
                'grade'           => [
                    'hasWeighting' => (bool) ($grade['hasWeighting'] ?? false),
                    'final'        => (float) ($grade['final'] ?? 0.0),
                    'letter'       => (string) ($grade['letter'] ?? ''),
                ],
                'status'          => $status,
                'status_key'      => $statusKey,
            ]);
        }

        // Tri par défaut : nom (lecture humaine). L'UI peut re-trier côté serveur.
        return $rows->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values();
    }

    /**
     * JOURNAL d'activité (logs) : évènements du cours, du plus récent au plus ancien.
     *
     * Dérivé des horodatages existants (aucune table de log dédiée alimentée pour ces
     * évènements pédagogiques). UNION logique en PHP de quatre sources, chacune bornée
     * à ACTIVITY_LOG_CAP lignes pour la mémoire :
     *  - 'item_viewed'    : Completion.started_at  (consultation / démarrage d'item) ;
     *  - 'item_completed' : Completion.completed_at (item complété) ;
     *  - 'quiz_attempt'   : QuizAttempt.submitted_at (tentative de quiz) ;
     *  - 'submission'     : Submission.submitted_at (remise de devoir).
     *
     * Filtres simples (clé optionnelle) :
     *  - 'user_id' : restreint à un étudiant ;
     *  - 'type'    : restreint à un type d'évènement (clés ci-dessus).
     *
     * Anti-IDOR : tout est scopé au cours (course_id direct, ou items / devoirs du
     * cours). Aucune écriture.
     *
     * @param  array{user_id?:int|null, type?:string|null}  $filters
     * @return Collection<int, array{
     *   at:\Illuminate\Support\Carbon, user_id:int, user_name:string,
     *   type:string, type_label:string, item:string
     * }>
     */
    public function activityLog(Course $course, array $filters = []): Collection
    {
        $userId = isset($filters['user_id']) && (int) $filters['user_id'] > 0
            ? (int) $filters['user_id']
            : null;
        $type = $filters['type'] ?? null;
        $type = is_string($type) && $type !== '' ? $type : null;

        $events = collect();

        $wantsCompletionLike = $type === null || in_array($type, ['item_viewed', 'item_completed'], true);

        if ($wantsCompletionLike) {
            $completions = Completion::query()
                ->where('course_id', $course->id)
                ->when($userId !== null, fn ($q) => $q->where('user_id', $userId))
                ->with(['user:id,name', 'lessonItem:id,title'])
                ->orderByDesc('updated_at')
                ->limit(self::ACTIVITY_LOG_CAP)
                ->get();

            foreach ($completions as $completion) {
                $itemTitle = $completion->lessonItem?->title ?? 'Item supprimé';
                $userName = $completion->user?->name ?? 'Apprenant';

                // Consultation / démarrage.
                if (($type === null || $type === 'item_viewed') && $completion->started_at !== null) {
                    $events->push([
                        'at'         => $completion->started_at,
                        'user_id'    => (int) $completion->user_id,
                        'user_name'  => (string) $userName,
                        'type'       => 'item_viewed',
                        'type_label' => 'Consultation',
                        'item'       => (string) $itemTitle,
                    ]);
                }

                // Complétion.
                if (($type === null || $type === 'item_completed')
                    && $completion->status === 'completed'
                    && $completion->completed_at !== null) {
                    $events->push([
                        'at'         => $completion->completed_at,
                        'user_id'    => (int) $completion->user_id,
                        'user_name'  => (string) $userName,
                        'type'       => 'item_completed',
                        'type_label' => 'Complétion',
                        'item'       => (string) $itemTitle,
                    ]);
                }
            }
        }

        if ($type === null || $type === 'quiz_attempt') {
            $attempts = QuizAttempt::query()
                ->where('course_id', $course->id)
                ->when($userId !== null, fn ($q) => $q->where('user_id', $userId))
                ->whereNotNull('submitted_at')
                ->with(['user:id,name', 'lessonItem:id,title'])
                ->orderByDesc('submitted_at')
                ->limit(self::ACTIVITY_LOG_CAP)
                ->get();

            foreach ($attempts as $attempt) {
                $events->push([
                    'at'         => $attempt->submitted_at,
                    'user_id'    => (int) $attempt->user_id,
                    'user_name'  => (string) ($attempt->user?->name ?? 'Apprenant'),
                    'type'       => 'quiz_attempt',
                    'type_label' => 'Tentative de quiz',
                    'item'       => (string) ($attempt->lessonItem?->title ?? 'Quiz supprimé')
                        . ' (' . (int) $attempt->percent . ' %)',
                ]);
            }
        }

        if ($type === null || $type === 'submission') {
            $submissions = Submission::query()
                ->whereHas('assignment', fn ($q) => $q->where('course_id', $course->id))
                ->when($userId !== null, fn ($q) => $q->where('user_id', $userId))
                ->whereNotNull('submitted_at')
                ->with(['user:id,name', 'assignment:id,title'])
                ->orderByDesc('submitted_at')
                ->limit(self::ACTIVITY_LOG_CAP)
                ->get();

            foreach ($submissions as $submission) {
                $events->push([
                    'at'         => $submission->submitted_at,
                    'user_id'    => (int) $submission->user_id,
                    'user_name'  => (string) ($submission->user?->name ?? 'Apprenant'),
                    'type'       => 'submission',
                    'type_label' => 'Remise de devoir',
                    'item'       => (string) ($submission->assignment?->title ?? 'Devoir supprimé'),
                ]);
            }
        }

        // Tri chronologique décroissant (plus récent en tête).
        return $events
            ->sortByDesc(fn (array $e) => $e['at']->getTimestamp())
            ->values();
    }

    /**
     * Liste des étudiants inscrits actifs (id => nom) pour alimenter le filtre
     * « par étudiant » du journal. Scopé au cours.
     *
     * @return Collection<int, string>
     */
    public function enrolledUsers(Course $course): Collection
    {
        return Enrollment::query()
            ->where('course_id', $course->id)
            ->where('status', 'active')
            ->with(['user:id,name'])
            ->get()
            ->mapWithKeys(fn ($e) => [(int) $e->user_id => (string) ($e->user?->name ?? 'Apprenant')])
            ->sort(SORT_NATURAL | SORT_FLAG_CASE);
    }

    /**
     * Construit le CSV du rapport de participation (BOM UTF-8, séparateur « ; »,
     * robuste Excel FR). Réutilise participation() (DRY). Scopé au cours (anti-IDOR).
     */
    public function participationCsv(Course $course): string
    {
        $rows = $this->participation($course);

        $lines = [];
        $lines[] = $this->csvRow([
            'Étudiant', 'Courriel', 'Statut', 'Progression (%)',
            'Items complétés', 'Items requis', 'Note finale', 'Dernière activité',
        ]);

        foreach ($rows as $row) {
            $grade = $row['grade']['hasWeighting']
                ? $this->numberFr($row['grade']['final']) . ' %'
                : 'n/d';

            $last = $row['last_activity'] !== null
                ? $row['last_activity']->copy()->timezone('America/Toronto')->format('Y-m-d H:i')
                : 'Aucune';

            $lines[] = $this->csvRow([
                $row['name'],
                $row['email'],
                $row['status'],
                (string) $row['percent'],
                (string) $row['items_completed'],
                (string) $row['items_total'],
                $grade,
                $last,
            ]);
        }

        return "\xEF\xBB\xBF" . implode("\r\n", $lines) . "\r\n";
    }

    /**
     * IDs des items REQUIS du cours (dénominateur de progression). Scopé au cours
     * via la structure chargée (même logique que AnalyticsService, DRY).
     *
     * @return array<int, int>
     */
    private function requiredItemIds(Course $course): array
    {
        $course->loadMissing([
            'chapters.lessons.lessonItems' => fn ($q) => $q->where('is_required', true),
        ]);

        $ids = [];
        foreach ($course->chapters as $chapter) {
            foreach ($chapter->lessons as $lesson) {
                foreach ($lesson->lessonItems as $item) {
                    $ids[] = (int) $item->id;
                }
            }
        }

        return $ids;
    }

    /** Une ligne CSV échappée (séparateur « ; »). */
    private function csvRow(array $cells): string
    {
        // SELF: <5 lignes — injection formule neutralisée avant l'échappement guillemets.
        $escaped = array_map(function (string $cell): string {
            $cell = $this->sanitizeCsvCell($cell);
            $cell = str_replace('"', '""', $cell);

            return '"' . $cell . '"';
        }, array_map(static fn ($c) => (string) $c, $cells));

        return implode(';', $escaped);
    }

    /**
     * Neutralise l'injection de formule CSV (Excel, LibreOffice, Google Sheets).
     *
     * Toute cellule issue de données utilisateur débutant par un caractère
     * d'activation (`= + - @ | \t \r`) est préfixée d'une apostrophe afin
     * d'être interprétée comme du texte brut par les tableurs.
     *
     * À appeler AVANT l'échappement des guillemets (DRY, ordre critique).
     */
    private function sanitizeCsvCell(string $cell): string
    {
        if ($cell !== '' && preg_match('/^[=+\-@|\t\r]/', $cell)) {
            return "'" . $cell;
        }

        return $cell;
    }

    /** Format numérique FR (virgule décimale) sans dépendance. */
    private function numberFr(float $value): string
    {
        return number_format($value, 1, ',', ' ');
    }
}

<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F22 - SUIVI D'ACQUISITION des compétences (« competency outcomes », parité Moodle).
 * SOURCE UNIQUE (DRY) qui DÉRIVE l'état d'acquisition d'une compétence pour un étudiant
 * à partir des données déjà persistées (achèvement V2-c + notes du carnet). Aucun état
 * d'acquisition n'est stocké : il reste toujours cohérent avec la réalité de l'apprenant.
 *
 * ════════════════ RÈGLE D'ACQUISITION (documentée) ════════════════
 * Items pertinents d'une compétence = items de leçon DIRECTEMENT liés + items REQUIS
 * des cours liés (un lien « cours entier » couvre les items requis du cours).
 *
 * Pour CHAQUE item pertinent, l'item est « acquis » par l'étudiant si :
 *   • la compétence n'a PAS de seuil (pass_threshold = null)  → l'item est ACHEVÉ
 *     (Completion status='completed', V2-c) ;  ← défaut, rétrocompatible.
 *   • la compétence a un seuil ET l'item est NOTÉ (quiz)       → la note effective de
 *     l'étudiant sur cet item ≥ seuil (QuizGradeService, finalisée) ;
 *   • la compétence a un seuil ET l'item N'est PAS noté        → l'item est ACHEVÉ.
 *
 * ÉTAT GLOBAL : « acquise » si TOUS les items pertinents sont acquis (et ≥ 1 item) ;
 * « en cours » s'il y a au moins un item acquis OU démarré ; sinon « non commencée ».
 * Une compétence SANS item pertinent reste « non commencée » (rien à acquérir) =
 * rétrocompat stricte (aucune compétence/lien => comportement actuel inchangé).
 *
 * NIVEAU affiché : réutilise l'ÉCHELLE F14 (scale_id) si fournie (niveaux ordonnés du
 * plus faible au plus fort, choisis selon la progression) ; sinon barème binaire
 * « Non atteint » / « Atteint ».
 *
 * SÉCURITÉ : calcul 100 % SERVEUR, lecture seule, défensif (toute erreur → état neutre).
 * PERF : agrégation PAR LOT (acquisitionForUsers / courseMatrix préchargent complétions
 * et tentatives de quiz en quelques requêtes, jamais une requête par couple étudiant×item).
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Academy\Models\Competency;
use Modules\Academy\Models\Completion;
use Modules\Academy\Models\CompetencyLink;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Models\QuizAttempt;

final class CompetencyService
{
    /** Types d'items de leçon considérés comme NOTÉS (note effective comparable au seuil). */
    public const GRADABLE_TYPES = ['quiz'];

    public const STATE_ACQUIRED    = 'acquired';
    public const STATE_IN_PROGRESS = 'in_progress';
    public const STATE_NOT_STARTED = 'not_started';

    /**
     * Items de leçon PERTINENTS d'une compétence (ids), dédupliqués : items directement
     * liés + items REQUIS des cours liés. Lecture seule, sans N+1 (deux requêtes au plus).
     *
     * @return array<int, int>
     */
    public static function relevantItemIds(Competency $competency): array
    {
        $links = $competency->links()->get(['course_id', 'lesson_item_id']);

        $directItemIds = $links->pluck('lesson_item_id')->filter()->map(fn ($id): int => (int) $id);
        $courseIds     = $links->pluck('course_id')->filter()->map(fn ($id): int => (int) $id)->unique()->values();

        $courseItemIds = collect();
        if ($courseIds->isNotEmpty()) {
            $courseItemIds = LessonItem::query()
                ->where('is_required', true)
                ->whereHas('lesson.chapter', fn ($q) => $q->whereIn('course_id', $courseIds->all()))
                ->pluck('id')
                ->map(fn ($id): int => (int) $id);
        }

        return $directItemIds->merge($courseItemIds)->unique()->values()->all();
    }

    /**
     * État d'acquisition d'UNE compétence pour UN étudiant. Lecture seule, défensif.
     *
     * @return array{state: string, total: int, achieved: int, percent: float, level: string}
     */
    public static function acquisitionState(User $user, Competency $competency): array
    {
        $itemIds = self::relevantItemIds($competency);

        if ($itemIds === []) {
            return self::neutralState($competency);
        }

        $items     = self::loadItems($itemIds);
        $completed = self::completionMap([$user->getKey()], $itemIds)[$user->getKey()] ?? [];
        $started   = self::startedMap([$user->getKey()], $itemIds)[$user->getKey()] ?? [];
        $attempts  = self::attemptsMap([$user->getKey()], $items, $competency)[$user->getKey()] ?? [];

        return self::computeState($competency, $items, $completed, $started, $attempts);
    }

    /**
     * État d'acquisition d'une compétence pour PLUSIEURS étudiants, EN UN LOT (anti-N+1).
     * Préchargement : complétions + tentatives de quiz en quelques requêtes globales.
     *
     * @param  array<int, int>  $userIds
     * @return array<int, array{state: string, total: int, achieved: int, percent: float, level: string}>  indexé par userId
     */
    public static function acquisitionForUsers(Competency $competency, array $userIds): array
    {
        $userIds = array_values(array_unique(array_map('intval', $userIds)));
        $itemIds = self::relevantItemIds($competency);

        if ($userIds === []) {
            return [];
        }

        if ($itemIds === []) {
            $neutral = self::neutralState($competency);

            return array_fill_keys($userIds, $neutral);
        }

        $items        = self::loadItems($itemIds);
        $completedMap  = self::completionMap($userIds, $itemIds);
        $startedMap    = self::startedMap($userIds, $itemIds);
        $attemptsMap   = self::attemptsMap($userIds, $items, $competency);

        $out = [];
        foreach ($userIds as $uid) {
            $out[$uid] = self::computeState(
                $competency,
                $items,
                $completedMap[$uid] ?? [],
                $startedMap[$uid] ?? [],
                $attemptsMap[$uid] ?? []
            );
        }

        return $out;
    }

    /**
     * Compétences PERTINENTES pour un cours : liées au cours OU à un de ses items.
     * Sert au rapport formateur (gâté manageEnrollments côté composant).
     *
     * @return Collection<int, Competency>
     */
    public static function competenciesForCourse(Course $course): Collection
    {
        $itemIds = LessonItem::query()
            ->whereHas('lesson.chapter', fn ($q) => $q->where('course_id', $course->id))
            ->pluck('id')->all();

        return Competency::query()
            ->whereHas('links', function ($q) use ($course, $itemIds): void {
                $q->where('course_id', $course->id);
                if ($itemIds !== []) {
                    $q->orWhereIn('lesson_item_id', $itemIds);
                }
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * MATRICE de suivi d'un cours : pour chaque compétence pertinente et chaque étudiant
     * inscrit ACTIF, l'état d'acquisition. Agrégation par lot (anti-N+1). Lecture seule.
     *
     * @return array{
     *   competencies: Collection<int, Competency>,
     *   students: Collection<int, User>,
     *   states: array<int, array<int, array{state: string, total: int, achieved: int, percent: float, level: string}>>
     * }  states[competencyId][userId]
     */
    public static function courseMatrix(Course $course): array
    {
        $competencies = self::competenciesForCourse($course);

        $studentIds = Enrollment::where('course_id', $course->id)
            ->where('status', 'active')
            ->pluck('user_id')->map(fn ($id): int => (int) $id)->unique()->values();

        $students = $studentIds->isEmpty()
            ? collect()
            : User::whereIn('id', $studentIds->all())->orderBy('name')->get();

        $states = [];
        foreach ($competencies as $competency) {
            $states[$competency->id] = self::acquisitionForUsers($competency, $studentIds->all());
        }

        return [
            'competencies' => $competencies,
            'students'     => $students,
            'states'       => $states,
        ];
    }

    /**
     * « Mes compétences » d'un étudiant : compétences liées aux cours qu'il SUIT (inscription
     * active) ou à leurs items, avec leur état. Strictement scopé à l'utilisateur (anti-IDOR).
     * Délègue le calcul à acquisitionForCompetencies (anti-N+1, lot unique).
     *
     * @return Collection<int, array{competency: Competency, state: array{state: string, total: int, achieved: int, percent: float, level: string}}>
     */
    public static function studentCompetencies(User $user): Collection
    {
        $courseIds = Enrollment::where('user_id', $user->getKey())
            ->where('status', 'active')
            ->pluck('course_id')->map(fn ($id): int => (int) $id)->unique()->values();

        if ($courseIds->isEmpty()) {
            return collect();
        }

        $itemIds = LessonItem::query()
            ->whereHas('lesson.chapter', fn ($q) => $q->whereIn('course_id', $courseIds->all()))
            ->pluck('id')->all();

        $competencies = Competency::query()
            ->where('is_active', true)
            ->whereHas('links', function ($q) use ($courseIds, $itemIds): void {
                $q->whereIn('course_id', $courseIds->all());
                if ($itemIds !== []) {
                    $q->orWhereIn('lesson_item_id', $itemIds);
                }
            })
            ->orderBy('name')
            ->get();

        // Calcul en LOT (anti-N+1) : une seule passe pour toutes les compétences.
        $states = self::acquisitionForCompetencies($user, $competencies);

        return $competencies->map(fn (Competency $c): array => [
            'competency' => $c,
            'state'      => $states[(int) $c->id] ?? self::neutralState($c),
        ])->values();
    }

    /**
     * État d'acquisition de PLUSIEURS compétences pour UN étudiant, EN UN LOT (anti-N+1).
     * Symétrique de acquisitionForUsers : regroupe les item_ids de toutes les compétences,
     * précharge complétions + tentatives en quelques requêtes, puis dispatche par compétence.
     *
     * @param  Collection<int, Competency>  $competencies
     * @return array<int, array{state: string, total: int, achieved: int, percent: float, level: string}>  indexé par competencyId
     */
    public static function acquisitionForCompetencies(User $user, Collection $competencies): array
    {
        if ($competencies->isEmpty()) {
            return [];
        }

        $userId        = (int) $user->getKey();
        $competencyIds = $competencies->pluck('id')->map(fn ($id): int => (int) $id)->all();

        // ── Étape 1 : tous les liens en UNE requête ──────────────────────────────
        $allLinks = CompetencyLink::query()
            ->whereIn('competency_id', $competencyIds)
            ->get(['competency_id', 'course_id', 'lesson_item_id']);

        $linksByCompetency = $allLinks->groupBy(fn ($l): int => (int) $l->competency_id);

        // ── Étape 2 : items requis par cours, UNE seule requête jointe ───────────
        $allCourseIds = $allLinks->pluck('course_id')->filter()
            ->map(fn ($id): int => (int) $id)->unique()->values()->all();

        // Map course_id => int[] (item_ids requis du cours)
        $courseItemsMap = [];
        if ($allCourseIds !== []) {
            DB::table('lesson_items')
                ->join('lessons', 'lessons.id', '=', 'lesson_items.lesson_id')
                ->join('chapters', 'chapters.id', '=', 'lessons.chapter_id')
                ->where('lesson_items.is_required', true)
                ->whereIn('chapters.course_id', $allCourseIds)
                ->select('lesson_items.id as item_id', 'chapters.course_id as course_id')
                ->get()
                ->each(function ($row) use (&$courseItemsMap): void {
                    $courseItemsMap[(int) $row->course_id][] = (int) $row->item_id;
                });
        }

        // ── Étape 3 : map competency_id => item_ids pertinents ───────────────────
        $itemsPerCompetency = [];
        foreach ($competencies as $competency) {
            $cId   = (int) $competency->id;
            $links = $linksByCompetency->get($cId, collect());

            $directIds = $links->pluck('lesson_item_id')->filter()
                ->map(fn ($id): int => (int) $id)->all();

            $courseItemIds = [];
            foreach ($links->pluck('course_id')->filter()->map(fn ($id): int => (int) $id)->unique() as $courseId) {
                $courseItemIds = array_merge($courseItemIds, $courseItemsMap[$courseId] ?? []);
            }

            $itemsPerCompetency[$cId] = array_values(array_unique(
                array_merge($directIds, $courseItemIds)
            ));
        }

        // ── Étape 4 : union de tous les item_ids nécessaires ─────────────────────
        $merged = [];
        foreach ($itemsPerCompetency as $ids) {
            foreach ($ids as $id) {
                $merged[] = $id;
            }
        }
        $allItemIds = array_values(array_unique($merged));

        if ($allItemIds === []) {
            // Toutes les compétences sont sans item : état neutre pour chacune.
            $out = [];
            foreach ($competencies as $competency) {
                $out[(int) $competency->id] = self::neutralState($competency);
            }

            return $out;
        }

        // ── Étape 5 : préchargement complétions + démarrages EN LOT ─────────────
        $items        = self::loadItems($allItemIds);
        $completedAll = self::completionMap([$userId], $allItemIds)[$userId] ?? [];
        $startedAll   = self::startedMap([$userId], $allItemIds)[$userId] ?? [];

        // ── Étape 6 : tentatives quiz EN LOT (une requête si seuil présent) ──────
        $allAttemptsRaw  = collect();
        $hasAnyThreshold = $competencies->contains(fn (Competency $c): bool => $c->pass_threshold !== null);

        if ($hasAnyThreshold) {
            $quizItemIds = $items
                ->filter(fn (LessonItem $i): bool => in_array($i->type, self::GRADABLE_TYPES, true))
                ->keys()->all();

            if ($quizItemIds !== []) {
                $allAttemptsRaw = QuizAttempt::query()
                    ->where('user_id', $userId)
                    ->whereIn('lesson_item_id', $quizItemIds)
                    ->orderBy('submitted_at')
                    ->orderBy('id')
                    ->get(['user_id', 'lesson_item_id', 'percent', 'needs_grading']);
            }
        }

        // ── Étape 7 : dispatcher computeState par compétence ─────────────────────
        $out = [];
        foreach ($competencies as $competency) {
            $cId     = (int) $competency->id;
            $itemIds = $itemsPerCompetency[$cId] ?? [];

            if ($itemIds === []) {
                $out[$cId] = self::neutralState($competency);
                continue;
            }

            // Sous-ensemble d'items pertinents pour cette compétence.
            $compItems = $items->only($itemIds);

            // Tentatives pertinentes pour cette compétence (filtrées depuis le lot global).
            $compAttempts = [];
            if ($competency->pass_threshold !== null && $allAttemptsRaw->isNotEmpty()) {
                $quizItems = $compItems->filter(fn (LessonItem $i): bool => in_array($i->type, self::GRADABLE_TYPES, true));

                if ($quizItems->isNotEmpty()) {
                    $methods = [];
                    foreach ($quizItems as $item) {
                        $methods[(int) $item->id] = QuizGradeService::methodFor($item);
                    }

                    foreach ($allAttemptsRaw->groupBy('lesson_item_id') as $itemId => $itemRows) {
                        if ($quizItems->has((int) $itemId)) {
                            $compAttempts[(int) $itemId] = self::effectivePercent(
                                $itemRows,
                                $methods[(int) $itemId] ?? 'highest'
                            );
                        }
                    }
                }
            }

            $out[$cId] = self::computeState($competency, $compItems, $completedAll, $startedAll, $compAttempts);
        }

        return $out;
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Internes (calcul + préchargement par lot)
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * @param  array<int, int>  $itemIds
     * @return Collection<int, LessonItem>  indexée par id
     */
    private static function loadItems(array $itemIds): Collection
    {
        return LessonItem::whereIn('id', $itemIds)->get(['id', 'type', 'payload'])->keyBy('id');
    }

    /**
     * Map userId => [itemId => true] des items ACHEVÉS (Completion status='completed').
     *
     * @param  array<int, int>  $userIds
     * @param  array<int, int>  $itemIds
     * @return array<int, array<int, bool>>
     */
    private static function completionMap(array $userIds, array $itemIds): array
    {
        $map = [];
        Completion::query()
            ->whereIn('user_id', $userIds)
            ->whereIn('lesson_item_id', $itemIds)
            ->where('status', 'completed')
            ->get(['user_id', 'lesson_item_id'])
            ->each(function (Completion $c) use (&$map): void {
                $map[(int) $c->user_id][(int) $c->lesson_item_id] = true;
            });

        return $map;
    }

    /**
     * Map userId => [itemId => true] des items DÉMARRÉS (toute Completion, started/completed).
     *
     * @param  array<int, int>  $userIds
     * @param  array<int, int>  $itemIds
     * @return array<int, array<int, bool>>
     */
    private static function startedMap(array $userIds, array $itemIds): array
    {
        $map = [];
        Completion::query()
            ->whereIn('user_id', $userIds)
            ->whereIn('lesson_item_id', $itemIds)
            ->get(['user_id', 'lesson_item_id'])
            ->each(function (Completion $c) use (&$map): void {
                $map[(int) $c->user_id][(int) $c->lesson_item_id] = true;
            });

        return $map;
    }

    /**
     * Map userId => [itemId => percent|null] des notes effectives de quiz, EN UN LOT.
     * Renvoie un tableau VIDE si la compétence n'a pas de seuil (notes inutiles → on
     * s'appuie alors uniquement sur l'achèvement).
     *
     * @param  array<int, int>             $userIds
     * @param  Collection<int, LessonItem>  $items
     * @return array<int, array<int, ?float>>
     */
    private static function attemptsMap(array $userIds, Collection $items, Competency $competency): array
    {
        if ($competency->pass_threshold === null) {
            return [];
        }

        $quizItems = $items->filter(fn (LessonItem $i): bool => in_array($i->type, self::GRADABLE_TYPES, true));
        if ($quizItems->isEmpty()) {
            return [];
        }

        // Préchargement EN UNE REQUÊTE de toutes les tentatives (étudiants × items quiz).
        $rows = QuizAttempt::query()
            ->whereIn('user_id', $userIds)
            ->whereIn('lesson_item_id', $quizItems->keys()->all())
            ->orderBy('submitted_at')
            ->orderBy('id')
            ->get(['user_id', 'lesson_item_id', 'percent', 'needs_grading']);

        // Méthode de notation par item (highest/average/first/last), calculée une fois.
        $methods = [];
        foreach ($quizItems as $item) {
            $methods[(int) $item->id] = QuizGradeService::methodFor($item);
        }

        $map = [];
        foreach ($rows->groupBy('user_id') as $uid => $userRows) {
            foreach ($userRows->groupBy('lesson_item_id') as $itemId => $itemRows) {
                $map[(int) $uid][(int) $itemId] = self::effectivePercent($itemRows, $methods[(int) $itemId] ?? 'highest');
            }
        }

        return $map;
    }

    /**
     * Note effective [0..100] à partir des tentatives préchargées d'un item, ou NULL si
     * aucune tentative finalisée. Réplique la logique de QuizGradeService::effectiveGrade
     * (filtre needs_grading + agrégation par méthode), sans requête (DRY/perf en lot).
     *
     * @param  \Illuminate\Support\Collection<int, QuizAttempt>  $attempts
     */
    private static function effectivePercent(Collection $attempts, string $method): ?float
    {
        $finalized = $attempts->filter(static fn ($a): bool => ! (bool) $a->needs_grading);
        if ($finalized->isEmpty()) {
            return null;
        }

        $percents = $finalized->map(static fn ($a): int => (int) $a->percent);

        return match ($method) {
            'average' => (float) round($percents->avg()),
            'first'   => (float) $percents->first(),
            'last'    => (float) $percents->last(),
            default   => (float) $percents->max(), // highest + repli défensif
        };
    }

    /**
     * Décide l'état global à partir des maps préchargées d'UN étudiant.
     *
     * @param  Collection<int, LessonItem>  $items
     * @param  array<int, bool>             $completed
     * @param  array<int, bool>             $started
     * @param  array<int, ?float>           $attempts
     * @return array{state: string, total: int, achieved: int, percent: float, level: string}
     */
    private static function computeState(
        Competency $competency,
        Collection $items,
        array $completed,
        array $started,
        array $attempts
    ): array {
        $threshold = $competency->pass_threshold;
        $total     = $items->count();

        // Garde défensive : items liés supprimés en race condition (total=0).
        // Sans cette garde, 0 >= 0 retournerait ACQUIRED à tort.
        if ($total === 0) {
            return self::neutralState($competency);
        }

        $achieved  = 0;
        $anyStart  = false;

        foreach ($items as $item) {
            $id        = (int) $item->id;
            $isGraded  = in_array($item->type, self::GRADABLE_TYPES, true);
            $isAchieved = false;

            if ($threshold !== null && $isGraded) {
                $percent    = $attempts[$id] ?? null;
                $isAchieved = $percent !== null && $percent >= (float) $threshold;
            } else {
                $isAchieved = ! empty($completed[$id]);
            }

            if ($isAchieved) {
                $achieved++;
            }
            if ($isAchieved || ! empty($started[$id]) || ($attempts[$id] ?? null) !== null) {
                $anyStart = true;
            }
        }

        if ($achieved >= $total) {
            $state = self::STATE_ACQUIRED;
        } elseif ($achieved > 0 || $anyStart) {
            $state = self::STATE_IN_PROGRESS;
        } else {
            $state = self::STATE_NOT_STARTED;
        }

        $percent = $total > 0 ? round($achieved / $total * 100, 1) : 0.0;

        return [
            'state'    => $state,
            'total'    => $total,
            'achieved' => $achieved,
            'percent'  => $percent,
            'level'    => self::levelLabel($competency, $state, $total > 0 ? $achieved / $total : 0.0),
        ];
    }

    /** État neutre d'une compétence sans item pertinent (rien à acquérir). */
    private static function neutralState(Competency $competency): array
    {
        return [
            'state'    => self::STATE_NOT_STARTED,
            'total'    => 0,
            'achieved' => 0,
            'percent'  => 0.0,
            'level'    => self::levelLabel($competency, self::STATE_NOT_STARTED, 0.0),
        ];
    }

    /**
     * Libellé du NIVEAU d'acquisition : échelle F14 si présente (niveaux ordonnés du plus
     * faible au plus fort, choisis selon la progression), sinon barème binaire.
     */
    private static function levelLabel(Competency $competency, string $state, float $ratio): string
    {
        $scale  = $competency->scale_id !== null ? $competency->scale : null;
        $levels = $scale !== null ? $scale->levels() : [];

        if ($levels === []) {
            // Barème binaire par défaut (« Non atteint » / « Atteint »).
            return $state === self::STATE_ACQUIRED ? 'Atteint' : 'Non atteint';
        }

        $count = count($levels);
        if ($state === self::STATE_ACQUIRED) {
            return (string) $levels[$count - 1]['label']; // niveau le plus fort
        }

        if ($count === 1) {
            return (string) $levels[0]['label'];
        }

        // Progression mappée sur les niveaux NON terminaux (le dernier reste réservé à
        // l'acquisition complète) : index dans [0, count-2].
        $index = (int) floor($ratio * ($count - 1));
        $index = max(0, min($count - 2, $index));

        return (string) $levels[$index]['label'];
    }
}

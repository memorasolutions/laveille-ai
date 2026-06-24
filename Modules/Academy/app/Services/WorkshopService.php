<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F21 - ATELIER (Workshop) : SOURCE UNIQUE (DRY) de l'activité « workshop » (item de leçon
 * « workshop », type Moodle « Workshop » : évaluation par les pairs). Lue côté SERVEUR par le
 * contrôleur d'actions, le lecteur (lesson.blade) et l'éditeur (CourseEditor). La
 * configuration, la grille de critères, les travaux, l'ALLOCATION déterministe, le CALCUL de
 * note pondéré et le rendu sûr ne vivent qu'ICI.
 *
 * ── LES 4 PHASES (pilotées par le gérant) ─────────────────────────────────────────────
 *   1. setup       : le gérant prépare la grille (aucune remise / évaluation possible).
 *   2. submission  : chaque inscrit REMET son travail (1 par étudiant, éditable tant que
 *                    la phase reste ouverte).
 *   3. assessment  : chaque inscrit ayant remis ÉVALUE reviews_per_student travaux de PAIRS
 *                    (anonymisés si anonymous) selon la grille. L'attribution est faite par
 *                    allocate() (déterministe, équitable, jamais sa propre copie).
 *   4. closed      : chaque étudiant voit SA note finale (moyenne des évaluations reçues) +
 *                    les retours reçus.
 *
 * Le payload de l'item porte (aucune nouvelle colonne, comme forum/wiki/database) :
 *   - intro               : texte d'introduction facultatif ;
 *   - phase               : setup / submission / assessment / closed (défaut submission) ;
 *   - reviews_per_student : nombre de travaux qu'un pair doit évaluer (défaut 2) ;
 *   - anonymous           : masquer l'auteur des travaux à l'évaluateur (défaut true).
 *
 * La GRILLE (les critères) vit dans la table academy_workshop_criteria (pas dans le payload),
 * car les notes y font référence (FK). syncCriteria() applique la grille définie dans
 * l'éditeur (création / mise à jour / soft-suppression des critères retirés).
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Models\WorkshopAssessment;
use Modules\Academy\Models\WorkshopAssessmentScore;
use Modules\Academy\Models\WorkshopCriterion;
use Modules\Academy\Models\WorkshopSubmission;

final class WorkshopService
{
    /** Phases connues (liste blanche stricte), dans l'ordre de progression. */
    public const PHASES = ['setup', 'submission', 'assessment', 'closed'];

    /** Phase par défaut quand une phase forgée / inconnue est reçue. */
    public const DEFAULT_PHASE = 'submission';

    /** Nombre maximal de critères dans une grille (anti-explosion). */
    public const MAX_CRITERIA = 20;

    /** Borne du nombre de travaux à évaluer par pair. */
    public const REVIEWS_DEFAULT = 2;

    public const REVIEWS_MAX = 10;

    /** Borne du score maximal d'un critère. */
    public const MAX_SCORE_CAP = 100;

    /** Longueur maximale d'un libellé / d'une description de critère. */
    public const LABEL_MAX = 200;

    public const DESCRIPTION_MAX = 1000;

    /** Bornes de longueur des champs d'un travail (anti-abus). */
    public const TITLE_MAX = 255;

    public const BODY_MAX = 20000;

    public const FEEDBACK_MAX = 5000;

    /** Nom du champ honeypot anti-spam (caché, doit rester vide). */
    public const HONEYPOT = 'hp_url';

    // ─────────────────────────────────────────────────────────────────────────────
    // LECTURE DE LA CONFIGURATION (payload)
    // ─────────────────────────────────────────────────────────────────────────────

    public static function intro(LessonItem $item): string
    {
        $intro = is_array($item->payload ?? null) ? ($item->payload['intro'] ?? '') : '';

        return is_string($intro) ? $intro : '';
    }

    /** Phase EFFECTIVE de l'atelier (liste blanche ; valeur forgée => défaut « submission »). */
    public static function phase(LessonItem $item): string
    {
        $raw = is_array($item->payload ?? null) ? ($item->payload['phase'] ?? null) : null;

        return in_array($raw, self::PHASES, true) ? (string) $raw : self::DEFAULT_PHASE;
    }

    /** Nombre de travaux à évaluer par pair (borné 1..REVIEWS_MAX ; défaut 2). */
    public static function reviewsPerStudent(LessonItem $item): int
    {
        $raw = is_array($item->payload ?? null) ? ($item->payload['reviews_per_student'] ?? null) : null;
        $n   = (int) ($raw ?? self::REVIEWS_DEFAULT);

        return max(1, min(self::REVIEWS_MAX, $n));
    }

    /** L'auteur des travaux est-il masqué à l'évaluateur ? DÉFAUT true (clé absente = masqué). */
    public static function isAnonymous(LessonItem $item): bool
    {
        $raw = is_array($item->payload ?? null) ? ($item->payload['anonymous'] ?? null) : null;

        return $raw === null ? true : (bool) $raw;
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // GRILLE (critères)
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Critères NON supprimés de l'item, dans l'ordre de la grille (position puis id).
     *
     * @return Collection<int, WorkshopCriterion>
     */
    public static function criteria(LessonItem $item): Collection
    {
        return WorkshopCriterion::forItem($item->id)->limit(self::MAX_CRITERIA)->get();
    }

    /**
     * Normalise une liste BRUTE de définitions de critères (venue de l'éditeur) en gabarits
     * propres prêts à persister. Ignore les critères sans libellé. Borne le libellé, la
     * description, max_score (1..MAX_SCORE_CAP) et le poids (>= 0). Conserve l'id existant
     * (édition d'un critère déjà persisté).
     *
     * @param  array<int|string, array<string, mixed>>  $raw
     * @return array<int, array{id: int|null, label: string, description: string|null, max_score: int, weight: float}>
     */
    public static function normalizeCriteria(array $raw): array
    {
        $clean = [];

        foreach ($raw as $row) {
            if (! is_array($row)) {
                continue;
            }

            $label = trim((string) ($row['label'] ?? ''));
            if ($label === '') {
                continue; // un critère sans libellé est ignoré (parité « ligne vide »).
            }
            $label = Str::limit($label, self::LABEL_MAX, '');

            $description = trim((string) ($row['description'] ?? ''));
            $description = $description === '' ? null : Str::limit($description, self::DESCRIPTION_MAX, '');

            $maxScore = (int) ($row['max_score'] ?? 10);
            $maxScore = max(1, min(self::MAX_SCORE_CAP, $maxScore));

            $weight = (float) ($row['weight'] ?? 1);
            $weight = max(0.0, $weight);

            $id = isset($row['id']) && (int) $row['id'] > 0 ? (int) $row['id'] : null;

            $clean[] = [
                'id'          => $id,
                'label'       => $label,
                'description' => $description,
                'max_score'   => $maxScore,
                'weight'      => $weight,
            ];

            if (count($clean) >= self::MAX_CRITERIA) {
                break;
            }
        }

        return $clean;
    }

    /**
     * Synchronise la GRILLE d'un atelier avec la liste brute de l'éditeur. Crée les nouveaux
     * critères, met à jour les critères conservés (par id, re-scopés à l'item anti-IDOR), et
     * SOFT-SUPPRIME les critères retirés (leurs notes déjà saisies restent rattachées). La
     * position suit l'ordre de la liste fournie.
     *
     * @param  array<int|string, array<string, mixed>>  $rawCriteria
     */
    public static function syncCriteria(LessonItem $item, array $rawCriteria): void
    {
        $normalized = self::normalizeCriteria($rawCriteria);

        // Critères actuels de l'item (re-scopés : anti-IDOR sur l'id fourni par l'éditeur).
        $existing = WorkshopCriterion::forItem($item->id)->get()->keyBy('id');
        $keptIds  = [];

        foreach ($normalized as $position => $def) {
            $attrs = [
                'lesson_item_id' => $item->id,
                'label'          => $def['label'],
                'description'    => $def['description'],
                'max_score'      => $def['max_score'],
                'weight'         => $def['weight'],
                'position'       => $position,
            ];

            $current = $def['id'] !== null ? $existing->get($def['id']) : null;

            if ($current !== null) {
                $current->update($attrs);
                $keptIds[] = $current->id;
            } else {
                $created   = WorkshopCriterion::create($attrs);
                $keptIds[] = $created->id;
            }
        }

        // Critères retirés de la grille : soft-suppression (les notes restent en base).
        foreach ($existing as $id => $criterion) {
            if (! in_array((int) $id, $keptIds, true)) {
                $criterion->delete();
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // TRAVAUX (submissions)
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Travaux remis pour l'item (récents d'abord). Précharge l'auteur (anti N+1). Réservé à
     * un appelant gérant (tableau de bord) : l'auteur y est exposé volontairement.
     *
     * @return Collection<int, WorkshopSubmission>
     */
    public static function submissions(LessonItem $item): Collection
    {
        return WorkshopSubmission::forItem($item->id)
            ->with('author:id,name')
            ->orderByDesc('id')
            ->get();
    }

    /** Le travail d'un étudiant pour l'item (1 par étudiant), ou null. */
    public static function submissionFor(LessonItem $item, ?int $userId): ?WorkshopSubmission
    {
        if ($userId === null) {
            return null;
        }

        return WorkshopSubmission::forItem($item->id)->where('user_id', $userId)->first();
    }

    /**
     * Remet (ou met à jour) le travail d'un étudiant : 1 par étudiant (firstOrNew), éditable
     * tant que la phase « submission » reste ouverte. body stocké BRUT (rendu strippé).
     */
    public static function upsertSubmission(LessonItem $item, int $userId, string $title, ?string $body): WorkshopSubmission
    {
        $submission = WorkshopSubmission::forItem($item->id)->where('user_id', $userId)->first()
            ?? new WorkshopSubmission(['lesson_item_id' => $item->id, 'user_id' => $userId]);

        $submission->title  = Str::limit(trim($title), self::TITLE_MAX, '');
        $submission->body   = ($body === null || trim($body) === '') ? null : trim($body);
        $submission->status = 'submitted';
        $submission->save();

        return $submission;
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // ALLOCATION (déterministe, équitable, jamais sa propre copie)
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * ATTRIBUE les évaluations à l'entrée en phase « assessment ». Les ÉVALUATEURS sont les
     * inscrits ayant remis un travail (ils ont « du jeu » dans l'atelier). Chacun se voit
     * attribuer reviews_per_student travaux de PAIRS, JAMAIS le sien, répartis ÉQUITABLEMENT.
     *
     * ALGORITHME DÉTERMINISTE (anneau) : travaux triés par id, indexés 0..n-1. Pour chaque
     * travail i (son auteur = évaluateur), on attribue les travaux (i+1), (i+2), … (i+r) en
     * cercle (modulo n). Comme l'offset va de 1 à r et r <= n-1, la cible n'est JAMAIS i :
     * impossible de s'auto-attribuer. Chaque travail reçoit ainsi EXACTEMENT r évaluateurs et
     * chaque évaluateur fait EXACTEMENT r évaluations (équité parfaite). firstOrCreate =
     * idempotent (rejouer l'allocation ne crée pas de doublon ; unique submission_id+assessor).
     *
     * @return int Nombre d'évaluations attribuées (créées ou déjà présentes).
     */
    public static function allocate(LessonItem $item): int
    {
        $submissions = WorkshopSubmission::forItem($item->id)
            ->orderBy('id')
            ->get(['id', 'user_id']);

        $n = $submissions->count();
        if ($n < 2) {
            return 0; // pas assez de travaux pour une évaluation croisée
        }

        $reviews = min(self::reviewsPerStudent($item), $n - 1);
        $count   = 0;

        DB::transaction(function () use ($submissions, $n, $reviews, &$count): void {
            foreach ($submissions as $i => $submission) {
                $assessorId = $submission->user_id;
                if ($assessorId === null) {
                    continue; // auteur supprimé : ne peut pas évaluer
                }

                for ($offset = 1; $offset <= $reviews; $offset++) {
                    $target = $submissions[($i + $offset) % $n];

                    // Garde-fou défensif : jamais sa propre copie (déjà garanti par l'anneau).
                    if ((int) $target->id === (int) $submission->id) {
                        continue;
                    }

                    // Idempotence sûre avec SoftDeletes + contrainte unique (submission, assessor) :
                    // firstOrCreate ignore les lignes soft-supprimées et tenterait un INSERT qui
                    // violerait la contrainte. On inspecte donc withTrashed et on restaure le cas
                    // échéant, plutôt que de ré-insérer.
                    $existing = WorkshopAssessment::withTrashed()
                        ->where('submission_id', $target->id)
                        ->where('assessor_id', $assessorId)
                        ->first();

                    if ($existing === null) {
                        WorkshopAssessment::create([
                            'submission_id' => $target->id,
                            'assessor_id'   => $assessorId,
                        ]);
                    } elseif ($existing->trashed()) {
                        $existing->restore();
                    }

                    $count++;
                }
            }
        });

        return $count;
    }

    /**
     * Travaux ATTRIBUÉS à un pair évaluateur (en phase assessment), avec leur évaluation
     * existante. ANONYMAT SERVEUR : si anonymous, on ne charge JAMAIS l'auteur (ni user_id
     * exposé à la vue) ; on ne sélectionne que id / titre / corps du travail. Anti N+1.
     *
     * @return Collection<int, WorkshopAssessment>
     */
    public static function assignmentsFor(LessonItem $item, int $assessorId): Collection
    {
        $anonymous = self::isAnonymous($item);

        // Colonnes du travail exposées à l'évaluateur : JAMAIS user_id si anonyme.
        $submissionCols = $anonymous
            ? ['id', 'title', 'body']
            : ['id', 'user_id', 'title', 'body'];

        return WorkshopAssessment::query()
            ->forAssessor($assessorId)
            ->whereHas('submission', fn ($q) => $q->where('lesson_item_id', $item->id))
            ->with([
                'submission' => function ($q) use ($submissionCols, $anonymous): void {
                    $q->select($submissionCols);
                    if (! $anonymous) {
                        $q->with('author:id,name');
                    }
                },
                'scores:id,assessment_id,criterion_id,score',
            ])
            ->orderBy('id')
            ->get();
    }

    /** Notes d'une évaluation indexées par criterion_id (lecture par le gabarit / le calcul). */
    public static function scoresByCriterion(WorkshopAssessment $assessment): array
    {
        $map = [];
        foreach ($assessment->scores as $score) {
            $map[(int) $score->criterion_id] = (int) $score->score;
        }

        return $map;
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // CALCUL DE NOTE (pondéré + agrégation)
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Note DÉRIVÉE d'une évaluation à partir des notes par critère, en POURCENTAGE 0..100 :
     *
     *   computed = Σ ( (score_i / max_score_i) × poids_i ) / Σ poids_i × 100
     *
     * Chaque critère contribue par sa FRACTION (score/max) pondérée. Le score est BORNÉ
     * 0..max_score (défensif). Σ poids <= 0 (grille dégénérée) => 0 (jamais de division par 0).
     *
     * @param  Collection<int, WorkshopCriterion>  $criteria
     * @param  array<int|string, mixed>            $rawScores  indexées par criterion_id
     */
    public static function computeScore(Collection $criteria, array $rawScores): float
    {
        $sumWeighted = 0.0;
        $sumWeight   = 0.0;

        foreach ($criteria as $criterion) {
            $max = max(1, (int) $criterion->max_score);
            $raw = (int) ($rawScores[$criterion->id] ?? 0);
            $raw = max(0, min($max, $raw)); // borne 0..max (anti-triche)

            $weight = max(0.0, (float) $criterion->weight);
            $sumWeighted += ($raw / $max) * $weight;
            $sumWeight   += $weight;
        }

        if ($sumWeight <= 0.0) {
            return 0.0;
        }

        return round(max(0.0, min(100.0, $sumWeighted / $sumWeight * 100)), 2);
    }

    /**
     * ENREGISTRE une évaluation rendue par un pair : remplace ses notes par critère (bornées),
     * son feedback, calcule computed_score et horodate la remise. TRANSACTION (multi-tables :
     * scores + l'évaluation). Le feedback est stocké BRUT (rendu strippé à l'affichage).
     *
     * @param  Collection<int, WorkshopCriterion>  $criteria
     * @param  array<int|string, mixed>            $rawScores  indexées par criterion_id
     */
    public static function recordAssessment(WorkshopAssessment $assessment, Collection $criteria, array $rawScores, ?string $feedback): void
    {
        DB::transaction(function () use ($assessment, $criteria, $rawScores, $feedback): void {
            foreach ($criteria as $criterion) {
                $max   = max(1, (int) $criterion->max_score);
                $raw   = (int) ($rawScores[$criterion->id] ?? 0);
                $score = max(0, min($max, $raw));

                WorkshopAssessmentScore::updateOrCreate(
                    ['assessment_id' => $assessment->id, 'criterion_id' => $criterion->id],
                    ['score' => $score],
                );
            }

            $assessment->feedback       = ($feedback === null || trim($feedback) === '') ? null : trim($feedback);
            $assessment->computed_score = self::computeScore($criteria, $rawScores);
            $assessment->submitted_at   = Carbon::now();
            $assessment->save();
        });
    }

    /**
     * Note FINALE d'un travail (0..100) = MOYENNE des computed_score des évaluations RENDUES
     * reçues, ou null si aucune évaluation rendue (parité Moodle « ignorer les notes vides »).
     */
    public static function submissionFinalScore(WorkshopSubmission $submission): ?float
    {
        $scores = WorkshopAssessment::where('submission_id', $submission->id)
            ->whereNotNull('submitted_at')
            ->whereNotNull('computed_score')
            ->pluck('computed_score');

        if ($scores->isEmpty()) {
            return null;
        }

        return round((float) $scores->avg(), 2);
    }

    /**
     * Notes finales de PLUSIEURS travaux en UNE requête (anti N+1 du tableau de bord gérant).
     * Retourne une map [submission_id => note 0..100 arrondie], absente si aucune évaluation
     * rendue (cohérent avec submissionFinalScore qui renvoie null dans ce cas).
     *
     * @param  Collection<int, WorkshopSubmission>  $submissions
     * @return array<int, float>
     */
    public static function batchFinalScores(Collection $submissions): array
    {
        $ids = $submissions->pluck('id')->all();
        if ($ids === []) {
            return [];
        }

        return WorkshopAssessment::query()
            ->whereIn('submission_id', $ids)
            ->whereNotNull('submitted_at')
            ->whereNotNull('computed_score')
            ->groupBy('submission_id')
            ->selectRaw('submission_id, AVG(computed_score) as avg_score')
            ->pluck('avg_score', 'submission_id')
            ->map(fn ($v) => round((float) $v, 2))
            ->all();
    }

    /**
     * Évaluations RENDUES reçues par un travail (pour « les retours reçus » de l'étudiant).
     * Extrait de la vue (DRY) ; scopé au travail fourni (lui-même déjà isolé à son auteur).
     *
     * @return Collection<int, WorkshopAssessment>
     */
    public static function receivedFeedbacks(WorkshopSubmission $submission): Collection
    {
        return WorkshopAssessment::where('submission_id', $submission->id)
            ->whereNotNull('submitted_at')
            ->with('scores')
            ->orderBy('id')
            ->get();
    }

    /**
     * BRANCHEMENT CARNET (gradebook) : note finale d'atelier d'un étudiant pour un item, en
     * POURCENTAGE 0..100, ou null si pas de travail / pas d'évaluation rendue. Même CONTRAT
     * que GradebookService::itemPercentFor (null = item vide, exclu du calcul pondéré). Le
     * carnet pondéré (GradebookService) peut donc l'agréger SANS code spécifique le jour où
     * un GradeItem TYPE_WORKSHOP est ajouté à gradableItems() : il suffira de pointer ici
     * (l'agrégation, les lettres et le CSV sont déjà génériques). On NE modifie pas
     * GradebookService ici (rétrocompat stricte des cours/quiz/devoirs existants).
     */
    public static function finalGradeForStudent(LessonItem $item, ?int $userId): ?float
    {
        $submission = self::submissionFor($item, $userId);

        return $submission === null ? null : self::submissionFinalScore($submission);
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // RENDU SÛR (anti-XSS)
    // ─────────────────────────────────────────────────────────────────────────────

    /** Rend un texte (corps de travail / feedback) en HTML SÛR (markdown strippé). */
    public static function renderText(?string $value): string
    {
        return LessonItem::renderRichText($value);
    }

    /**
     * Progression des évaluations pour le tableau de bord gérant : nombre d'évaluations
     * attribuées et rendues (anti N+1 : 2 requêtes agrégées).
     *
     * @return array{allocated: int, submitted: int}
     */
    public static function assessmentProgress(LessonItem $item): array
    {
        $base = WorkshopAssessment::query()
            ->whereHas('submission', fn ($q) => $q->where('lesson_item_id', $item->id));

        return [
            'allocated' => (clone $base)->count(),
            'submitted' => (clone $base)->whereNotNull('submitted_at')->count(),
        ];
    }
}

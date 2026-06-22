<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * V2-b - CARNET DE NOTES PONDÉRÉ (gradebook Moodle). SOURCE UNIQUE (DRY) du calcul
 * de la note finale pondérée, des lettres et de l'export CSV. Lu côté SERVEUR
 * uniquement (le carnet gérant est gâté manageEnrollments ; la vue étudiant est
 * scopée à auth). Aucune dépendance nouvelle.
 *
 * ── ITEMS DE NOTE ─────────────────────────────────────────────────────────────
 *   (a) chaque item de leçon type=quiz → NOTE EFFECTIVE (QuizGradeService) en % ;
 *   (b) chaque devoir (Assignment) → score/max_points × 100 d'une remise CORRIGÉE.
 *
 * ── NOTE FINALE PONDÉRÉE ──────────────────────────────────────────────────────
 *   note finale = Σ_catégories ( poids_catégorie × score_catégorie ) / Σ poids,
 *   où score_catégorie = MOYENNE PONDÉRÉE des items de la catégorie (chacun en %).
 *
 *   • NORMALISATION DES POIDS : on divise par la somme des poids des catégories
 *     RETENUES (cf. items manquants). Donc si Σ des poids ≠ 100 (ex. 30+30), les
 *     poids sont normalisés PROPORTIONNELLEMENT (ici 50/50). Idem au niveau des
 *     items dans une catégorie (division par la somme des poids des items notés).
 *   • ITEMS SANS TENTATIVE/REMISE : EXCLUS du calcul (parité Moodle « ignorer les
 *     notes vides ») plutôt que comptés 0, pour ne pas pénaliser une évaluation
 *     non encore faite. Une catégorie dont AUCUN item n'a de note est elle-même
 *     EXCLUE (son poids est retiré de la normalisation finale).
 *   • Un cours SANS catégorie → hasWeighting=false : l'appelant retombe sur
 *     l'agrégation simple actuelle (rétrocompat stricte).
 *
 * ── LETTRES ───────────────────────────────────────────────────────────────────
 *   Barème par cours (JSON courses.grade_letter_scheme) ou défaut raisonnable.
 *   letterFor(%) renvoie la 1re lettre dont le seuil min est atteint.
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Modules\Academy\Models\Assignment;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\GradeCategory;
use Modules\Academy\Models\GradeItem;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Models\Submission;

final class GradebookService
{
    /**
     * Barème de lettres par défaut (raisonnable, style québécois A..E).
     * Trié du seuil le plus haut au plus bas.
     *
     * @return array<int, array{letter: string, min: float}>
     */
    public static function defaultLetterScheme(): array
    {
        return [
            ['letter' => 'A', 'min' => 90.0],
            ['letter' => 'B', 'min' => 80.0],
            ['letter' => 'C', 'min' => 70.0],
            ['letter' => 'D', 'min' => 60.0],
            ['letter' => 'E', 'min' => 0.0],
        ];
    }

    /**
     * Barème de lettres EFFECTIF d'un cours : celui stocké (validé) ou le défaut.
     * Toujours trié décroissant par seuil (pour un letterFor déterministe).
     *
     * @return array<int, array{letter: string, min: float}>
     */
    public static function letterSchemeFor(Course $course): array
    {
        $raw = $course->grade_letter_scheme;

        $scheme = self::sanitizeScheme(is_array($raw) ? $raw : null);

        return $scheme === [] ? self::defaultLetterScheme() : $scheme;
    }

    /**
     * Normalise un barème venu du stockage/du formulaire : ne garde que les bandes
     * {letter, min} valides (lettre non vide, min 0..100), triées décroissant.
     * Renvoie [] si rien d'exploitable (l'appelant retombe sur le défaut).
     *
     * @param  array<int, array<string, mixed>>|null  $raw
     * @return array<int, array{letter: string, min: float}>
     */
    public static function sanitizeScheme(?array $raw): array
    {
        if ($raw === null) {
            return [];
        }

        $bands = [];
        foreach ($raw as $band) {
            if (! is_array($band)) {
                continue;
            }
            $letter = trim((string) ($band['letter'] ?? ''));
            if ($letter === '') {
                continue;
            }
            $min = (float) ($band['min'] ?? 0);
            $min = max(0.0, min(100.0, $min));
            $bands[] = ['letter' => $letter, 'min' => $min];
        }

        usort($bands, static fn (array $a, array $b): int => $b['min'] <=> $a['min']);

        return $bands;
    }

    /**
     * Lettre correspondant à un pourcentage selon un barème (défaut si null).
     * Renvoie la 1re bande (triée décroissant) dont le seuil min est atteint.
     *
     * @param  array<int, array{letter: string, min: float}>|null  $scheme
     */
    public static function letterFor(float $percent, ?array $scheme = null): string
    {
        $scheme = ($scheme === null || $scheme === []) ? self::defaultLetterScheme() : self::sanitizeScheme($scheme);
        if ($scheme === []) {
            $scheme = self::defaultLetterScheme();
        }

        foreach ($scheme as $band) {
            if ($percent >= $band['min']) {
                return $band['letter'];
            }
        }

        // Repli défensif : la dernière bande (seuil le plus bas).
        return end($scheme)['letter'] ?? '-';
    }

    /** Vrai si le cours a au moins une catégorie de notes (→ carnet pondéré). */
    public static function hasWeighting(Course $course): bool
    {
        return GradeCategory::where('course_id', $course->id)->exists();
    }

    /**
     * Liste UNIFIÉE des items notables du cours, avec leur affectation actuelle.
     * Source unique réutilisée par le calcul, l'UI d'affectation, le carnet et le CSV.
     *
     * @return Collection<int, array{type: string, id: int, title: string, max: int, category_id: ?int, weight: float, model: mixed}>
     */
    public static function gradableItems(Course $course): Collection
    {
        // Affectations existantes, indexées par « type:id ».
        $assigned = GradeItem::where('course_id', $course->id)
            ->get()
            ->keyBy(fn (GradeItem $gi): string => $gi->item_type.':'.$gi->item_id);

        $items = collect();

        // (a) Items de leçon type=quiz (via item→lesson→chapter→course).
        $quizItems = LessonItem::query()
            ->where('type', 'quiz')
            ->whereHas('lesson.chapter', fn ($q) => $q->where('course_id', $course->id))
            ->get();

        foreach ($quizItems as $quiz) {
            $gi = $assigned->get(GradeItem::TYPE_QUIZ.':'.$quiz->id);
            $items->push([
                'type'        => GradeItem::TYPE_QUIZ,
                'id'          => (int) $quiz->id,
                'title'       => (string) ($quiz->title ?? 'Quiz'),
                'max'         => 100,
                'category_id' => $gi?->grade_category_id,
                'weight'      => $gi !== null ? (float) $gi->weight : 1.0,
                'model'       => $quiz,
            ]);
        }

        // (b) Devoirs (Assignment) du cours.
        $assignments = Assignment::where('course_id', $course->id)
            ->orderBy('position')
            ->orderBy('id')
            ->get(['id', 'title', 'max_points']);

        foreach ($assignments as $assignment) {
            $gi = $assigned->get(GradeItem::TYPE_ASSIGNMENT.':'.$assignment->id);
            $items->push([
                'type'        => GradeItem::TYPE_ASSIGNMENT,
                'id'          => (int) $assignment->id,
                'title'       => (string) $assignment->title,
                'max'         => (int) $assignment->max_points,
                'category_id' => $gi?->grade_category_id,
                'weight'      => $gi !== null ? (float) $gi->weight : 1.0,
                'model'       => $assignment,
            ]);
        }

        return $items->values();
    }

    /**
     * Pourcentage [0..100] d'un item pour un utilisateur, ou NULL si aucune
     * tentative / aucune remise corrigée (→ exclu du calcul pondéré).
     *
     * @param  array{type: string, id: int, max: int, model: mixed}  $item
     */
    public static function itemPercentFor(int $userId, array $item): ?float
    {
        if ($item['type'] === GradeItem::TYPE_QUIZ) {
            $model = $item['model'] instanceof LessonItem
                ? $item['model']
                : LessonItem::find($item['id']);

            if ($model === null) {
                return null;
            }

            $grade = QuizGradeService::effectiveGrade($userId, $model);

            return $grade['attempts'] > 0 ? (float) $grade['percent'] : null;
        }

        // Devoir : dernière remise CORRIGÉE de l'utilisateur pour ce devoir.
        $submission = Submission::where('assignment_id', $item['id'])
            ->where('user_id', $userId)
            ->whereNotNull('graded_at')
            ->whereNotNull('score')
            ->orderByDesc('graded_at')
            ->first(['score']);

        if ($submission === null) {
            return null;
        }

        $max = max(1, (int) $item['max']);

        return max(0.0, min(100.0, (float) $submission->score / $max * 100));
    }

    /**
     * Note finale pondérée d'un utilisateur pour un cours + détail par catégorie.
     *
     * @param  array<int, array<string, mixed>>|null  $items  pré-calcul (DRY/perf) : gradableItems().
     * @param  Collection<int, GradeCategory>|null     $categories  pré-calcul.
     * @return array{
     *   hasWeighting: bool,
     *   final: float,
     *   letter: string,
     *   categories: array<int, array{id:int, name:string, weight:float, score:?float, normalizedWeight:float, hasData:bool}>
     * }
     */
    public static function finalGradeFor(User $user, Course $course, ?array $items = null, ?Collection $categories = null): array
    {
        $categories ??= GradeCategory::forCourse($course->id)->get();

        if ($categories->isEmpty()) {
            return [
                'hasWeighting' => false,
                'final'        => 0.0,
                'letter'       => '',
                'categories'   => [],
            ];
        }

        $items ??= self::gradableItems($course)->all();
        $itemsByCategory = collect($items)->groupBy('category_id');

        $rows        = [];
        $weightedSum = 0.0;  // Σ (score_cat × poids_cat) sur les catégories retenues
        $activeWeight = 0.0; // Σ poids_cat des catégories retenues (normalisation)

        foreach ($categories as $category) {
            $catItems = $itemsByCategory->get($category->id, collect());

            // Moyenne PONDÉRÉE des items de la catégorie (items vides exclus).
            $sumPctWeight = 0.0;
            $sumWeight    = 0.0;
            foreach ($catItems as $item) {
                $pct = self::itemPercentFor($user->id, $item);
                if ($pct === null) {
                    continue; // item sans note → exclu
                }
                $w = max(0.0, (float) $item['weight']);
                if ($w <= 0.0) {
                    $w = 1.0; // poids non renseigné → poids neutre
                }
                $sumPctWeight += $pct * $w;
                $sumWeight    += $w;
            }

            $hasData = $sumWeight > 0.0;
            $score   = $hasData ? $sumPctWeight / $sumWeight : null;

            $catWeight = max(0.0, (float) $category->weight);

            if ($hasData && $catWeight > 0.0) {
                $weightedSum  += $score * $catWeight;
                $activeWeight += $catWeight;
            }

            $rows[] = [
                'id'               => (int) $category->id,
                'name'             => (string) $category->name,
                'weight'           => $catWeight,
                'score'            => $score,
                'hasData'          => $hasData,
                'normalizedWeight' => 0.0, // rempli ci-dessous une fois activeWeight connu
            ];
        }

        $final = $activeWeight > 0.0 ? $weightedSum / $activeWeight : 0.0;
        $final = round(max(0.0, min(100.0, $final)), 1);

        // Poids normalisés (en % du total retenu) pour l'affichage transparent.
        foreach ($rows as &$row) {
            $row['normalizedWeight'] = ($activeWeight > 0.0 && $row['hasData'] && $row['weight'] > 0.0)
                ? round($row['weight'] / $activeWeight * 100, 1)
                : 0.0;
        }
        unset($row);

        return [
            'hasWeighting' => true,
            'final'        => $final,
            'letter'       => self::letterFor($final, self::letterSchemeFor($course)),
            'categories'   => $rows,
        ];
    }

    /**
     * Construit le contenu CSV du carnet (inscrits × items + note finale + lettre).
     * BOM UTF-8 + séparateur « ; » (robuste Excel FR). Source unique du contenu :
     * réutilise gradableItems() + finalGradeFor() (DRY). Scopé au cours (anti-IDOR).
     */
    public static function buildCsv(Course $course): string
    {
        $items      = self::gradableItems($course)->all();
        $categories = GradeCategory::forCourse($course->id)->get();
        $weighted   = $categories->isNotEmpty();

        $enrollments = \Modules\Academy\Models\Enrollment::where('course_id', $course->id)
            ->where('status', 'active')
            ->with('user:id,name,email')
            ->get()
            ->sortBy(fn ($e) => $e->user?->name ?? '')
            ->values();

        // En-têtes.
        $header = ['Personne inscrite', 'Courriel'];
        foreach ($items as $item) {
            $suffix = $item['type'] === GradeItem::TYPE_QUIZ ? ' (quiz, %)' : ' (/'.$item['max'].')';
            $header[] = $item['title'].$suffix;
        }
        if ($weighted) {
            $header[] = 'Note finale (%)';
            $header[] = 'Lettre';
        }

        $lines   = [];
        $lines[] = self::csvRow($header);

        foreach ($enrollments as $enrollment) {
            $user = $enrollment->user;
            if ($user === null) {
                continue;
            }

            $row = [$user->name ?? 'Inconnu', $user->email ?? ''];

            foreach ($items as $item) {
                $pct = self::itemPercentFor($user->id, $item);
                if ($pct === null) {
                    $row[] = '';
                } elseif ($item['type'] === GradeItem::TYPE_QUIZ) {
                    $row[] = (string) (int) round($pct);
                } else {
                    // Devoir : on exporte le score brut sur max (cohérent avec l'UI).
                    $raw = (int) round($pct / 100 * max(1, (int) $item['max']));
                    $row[] = (string) $raw;
                }
            }

            if ($weighted) {
                $g = self::finalGradeFor($user, $course, $items, $categories);
                $row[] = self::numberFr($g['final']);
                $row[] = $g['letter'];
            }

            $lines[] = self::csvRow($row);
        }

        // BOM UTF-8 pour qu'Excel (FR) reconnaisse l'encodage + accents.
        return "\xEF\xBB\xBF".implode("\r\n", $lines)."\r\n";
    }

    /**
     * Encode une ligne CSV : séparateur « ; », guillemets si nécessaire (Excel FR).
     *
     * @param  array<int, string>  $cells
     */
    private static function csvRow(array $cells): string
    {
        $escaped = array_map(static function (string $cell): string {
            // Neutralise les retours de ligne + échappe les guillemets internes.
            $cell = str_replace(["\r\n", "\r", "\n"], ' ', $cell);
            if (str_contains($cell, '"') || str_contains($cell, ';') || str_contains($cell, ',')) {
                return '"'.str_replace('"', '""', $cell).'"';
            }

            return $cell;
        }, $cells);

        return implode(';', $escaped);
    }

    /** Nombre au format FR (virgule décimale) pour Excel FR. */
    private static function numberFr(float $value): string
    {
        return str_replace('.', ',', (string) $value);
    }
}

<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * ACHÈVEMENT DE COURS CONFIGURABLE (« course completion » façon Moodle). SOURCE
 * UNIQUE (DRY) qui décide si un cours est COMPLÉTÉ pour un utilisateur, selon le
 * critère configuré au niveau du cours (colonne JSON courses.completion_criteria).
 *
 * Critères supportés (UN SEUL actif à la fois, simple et lisible) :
 *   • all_required        (DÉFAUT) : tous les items requis complétés (= comportement
 *                                    historique, prouvé par test : Progress.percent ≥ 100) ;
 *   • percent (value=X)            : ≥ X % des items requis complétés (Progress.percent) ;
 *   • min_grade (value=X)          : note finale du carnet ≥ X % (GradebookService /
 *                                    QuizGradeService) ;
 *   • selected_activities (items)  : tous les items DÉSIGNÉS sont complétés.
 *
 * SÉCURITÉ : calcul 100 % SERVEUR à partir des données persistées (Progress,
 * Completion, notes). Aucune donnée venue du navigateur n'intervient. Le service ne
 * modifie rien (lecture seule) : appelé par le certificat et les badges pour décider
 * du déblocage, et par l'affichage (progressToward) pour informer l'apprenant.
 *
 * RÉTROCOMPAT : un cours sans config (NULL) ou avec une forme inconnue retombe sur
 * « all_required » → strictement identique au comportement actuel.
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

use App\Models\User;
use Modules\Academy\Models\Completion;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Models\Progress;

final class CourseCompletionService
{
    public const TYPE_ALL_REQUIRED = 'all_required';
    public const TYPE_PERCENT      = 'percent';
    public const TYPE_MIN_GRADE    = 'min_grade';
    public const TYPE_SELECTED     = 'selected_activities';

    /** Types valides (liste blanche pour la validation et la normalisation). */
    public const TYPES = [
        self::TYPE_ALL_REQUIRED,
        self::TYPE_PERCENT,
        self::TYPE_MIN_GRADE,
        self::TYPE_SELECTED,
    ];

    /**
     * Critère NORMALISÉ d'un cours (source unique de la forme). Toute valeur absente,
     * NULL ou inconnue → défaut « all_required ». value bornée 1..100 pour percent /
     * min_grade ; items = liste d'entiers positifs uniques pour selected_activities.
     *
     * @return array{type: string, value: int|null, items: array<int, int>}
     */
    public static function criteriaFor(Course $course): array
    {
        $raw  = $course->completion_criteria;
        $raw  = is_array($raw) ? $raw : [];
        $type = (string) ($raw['type'] ?? self::TYPE_ALL_REQUIRED);

        if (! in_array($type, self::TYPES, true)) {
            $type = self::TYPE_ALL_REQUIRED;
        }

        $value = null;
        if (in_array($type, [self::TYPE_PERCENT, self::TYPE_MIN_GRADE], true)) {
            $value = (int) ($raw['value'] ?? 0);
            $value = max(1, min(100, $value));
        }

        $items = [];
        if ($type === self::TYPE_SELECTED) {
            $items = array_values(array_unique(array_filter(
                array_map(static fn ($id): int => (int) $id, (array) ($raw['items'] ?? [])),
                static fn (int $id): bool => $id > 0
            )));
        }

        return ['type' => $type, 'value' => $value, 'items' => $items];
    }

    /**
     * Le cours est-il COMPLÉTÉ pour cet utilisateur selon son critère configuré ?
     * Lecture seule, calcul serveur, défensif (toute erreur → false, jamais bloquant).
     */
    public function isComplete(User $user, Course $course): bool
    {
        try {
            $criteria = self::criteriaFor($course);

            return match ($criteria['type']) {
                self::TYPE_PERCENT   => $this->coursePercent($user, $course) >= (int) $criteria['value'],
                self::TYPE_MIN_GRADE => ($g = $this->finalGradePercent($user, $course)) !== null
                                        && $g >= (float) $criteria['value'],
                self::TYPE_SELECTED  => $this->selectedActivitiesComplete($user, $course, $criteria['items']),
                default              => $this->coursePercent($user, $course) >= 100, // all_required
            };
        } catch (\Throwable $e) {
            \Log::warning('[CourseCompletionService] isComplete erreur défensive', ['exception' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Détail de la progression vers la complétion (pour l'affichage, LECTURE SEULE).
     *
     * @return array{
     *   type: string, complete: bool, label: string,
     *   current: float, target: float, unit: string, percentOfGoal: int
     * }
     */
    public function progressToward(User $user, Course $course): array
    {
        $criteria = self::criteriaFor($course);
        $type     = $criteria['type'];

        if ($type === self::TYPE_PERCENT) {
            $target  = (float) $criteria['value'];
            $current = (float) $this->coursePercent($user, $course);

            return $this->row($type,
                'Compléter au moins '.(int) $target.' % des leçons requises',
                $current, $target, '%');
        }

        if ($type === self::TYPE_MIN_GRADE) {
            $target  = (float) $criteria['value'];
            $current = (float) ($this->finalGradePercent($user, $course) ?? 0.0);

            return $this->row($type,
                'Obtenir une note finale d\'au moins '.(int) $target.' %',
                $current, $target, '%');
        }

        if ($type === self::TYPE_SELECTED) {
            $items   = $criteria['items'];
            $target  = (float) count($items);
            $current = (float) $this->selectedActivitiesCompletedCount($user, $items);

            return $this->row($type,
                'Compléter les '.(int) $target.' activité'.($target > 1 ? 's' : '').' désignée'.($target > 1 ? 's' : ''),
                $current, $target, 'activités');
        }

        // all_required (défaut) : on s'appuie sur la barre de progression existante.
        $current = (float) $this->coursePercent($user, $course);

        return $this->row(self::TYPE_ALL_REQUIRED,
            'Compléter toutes les leçons requises',
            $current, 100.0, '%');
    }

    /**
     * Pourcentage de progression du cours (items requis complétés) tel que persisté.
     * 0 si aucune ligne de progression. C'est la MÊME mesure que la barre publique.
     */
    private function coursePercent(User $user, Course $course): int
    {
        return (int) (Progress::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->value('percent') ?? 0);
    }

    /**
     * Note finale du carnet (%) de l'utilisateur pour le cours, ou NULL si AUCUNE note.
     * DRY : réutilise GradebookService (carnet pondéré) si le cours a des catégories,
     * sinon moyenne simple des items notables ayant une note (parité « ignorer les
     * notes vides » de GradebookService::itemPercentFor). NULL distinct de 0 : sans
     * aucune note, le critère min_grade n'est pas rempli (jamais complété par défaut).
     */
    /**
     * Exposé public pour CertificateService (final_score en min_grade). Retourne
     * la note finale du carnet (%) pour l'utilisateur, ou null si aucune note.
     */
    public function finalGradePercent(User $user, Course $course): ?float
    {
        if (! class_exists(GradebookService::class)) {
            return null;
        }

        if (GradebookService::hasWeighting($course)) {
            $grade = GradebookService::finalGradeFor($user, $course);

            return $grade['hasWeighting'] ? (float) $grade['final'] : null;
        }

        // Pas de pondération : moyenne simple des items notables ayant une note.
        $items = GradebookService::gradableItems($course)->all();
        $sum   = 0.0;
        $count = 0;

        foreach ($items as $item) {
            $pct = GradebookService::itemPercentFor($user->id, $item);
            if ($pct === null) {
                continue;
            }
            $sum += $pct;
            $count++;
        }

        return $count > 0 ? round($sum / $count, 1) : null;
    }

    /**
     * Tous les items DÉSIGNÉS sont-ils complétés ? Les ids sont d'abord ramenés aux
     * items appartenant RÉELLEMENT à ce cours (anti-IDOR/anti-bidouille) puis comparés
     * aux complétions « completed » de l'utilisateur. Liste vide → non rempli (false).
     *
     * @param  array<int, int>  $itemIds
     */
    private function selectedActivitiesComplete(User $user, Course $course, array $itemIds): bool
    {
        $valid = $this->validItemIdsForCourse($course, $itemIds);

        if ($valid === []) {
            return false;
        }

        $completed = Completion::query()
            ->where('user_id', $user->id)
            ->whereIn('lesson_item_id', $valid)
            ->where('status', 'completed')
            ->distinct()
            ->count('lesson_item_id');

        return $completed >= count($valid);
    }

    /**
     * Nombre d'activités désignées complétées (pour l'affichage de la progression).
     *
     * @param  array<int, int>  $itemIds
     */
    private function selectedActivitiesCompletedCount(User $user, array $itemIds): int
    {
        if ($itemIds === []) {
            return 0;
        }

        return Completion::query()
            ->where('user_id', $user->id)
            ->whereIn('lesson_item_id', $itemIds)
            ->where('status', 'completed')
            ->distinct()
            ->count('lesson_item_id');
    }

    /**
     * Filtre une liste d'ids d'items pour ne garder QUE ceux d'une leçon d'un chapitre
     * de CE cours (source de vérité serveur). Réutilisé par l'éditeur (validation) et
     * par le calcul de complétion.
     *
     * @param  array<int, int>  $itemIds
     * @return array<int, int>
     */
    public function validItemIdsForCourse(Course $course, array $itemIds): array
    {
        $itemIds = array_values(array_unique(array_filter(
            array_map(static fn ($id): int => (int) $id, $itemIds),
            static fn (int $id): bool => $id > 0
        )));

        if ($itemIds === []) {
            return [];
        }

        return LessonItem::query()
            ->whereIn('id', $itemIds)
            ->whereHas('lesson.chapter', fn ($q) => $q->where('course_id', $course->id))
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * Construit une ligne de progression normalisée (avec un % d'avancement borné pour
     * une barre a11y).
     *
     * @return array{type: string, complete: bool, label: string, current: float, target: float, unit: string, percentOfGoal: int}
     */
    private function row(string $type, string $label, float $current, float $target, string $unit): array
    {
        $percentOfGoal = $target > 0.0
            ? (int) round(max(0.0, min(100.0, $current / $target * 100)))
            : 0;

        return [
            'type'          => $type,
            'complete'      => $current >= $target && $target > 0.0,
            'label'         => $label,
            'current'       => round($current, 1),
            'target'        => round($target, 1),
            'unit'          => $unit,
            'percentOfGoal' => $percentOfGoal,
        ];
    }
}

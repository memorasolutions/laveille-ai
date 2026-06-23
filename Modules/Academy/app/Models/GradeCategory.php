<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * V2-b - CATÉGORIE de notes pondérée d'un cours (gradebook Moodle). Appartient à
 * UN cours (course_id) et porte un POIDS en %. Le contrôle d'appartenance et
 * d'autorisation est fait côté composant Livewire (autorisation SERVEUR ; toute
 * catégorie ciblée est re-résolue scopée au cours = anti-IDOR).
 */

declare(strict_types=1);

namespace Modules\Academy\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int    $id
 * @property int    $course_id
 * @property string $name
 * @property float  $weight
 * @property string $aggregation_method
 * @property int    $position
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class GradeCategory extends Model
{
    // F14 - MÉTHODES D'AGRÉGATION (parité Moodle). weighted_mean = défaut =
    // comportement V2-b existant (moyenne pondérée des items par leur poids).
    public const AGGREGATION_WEIGHTED_MEAN = 'weighted_mean';

    public const AGGREGATION_SIMPLE_MEAN = 'simple_mean';

    public const AGGREGATION_SUM = 'sum';

    public const AGGREGATION_HIGHEST = 'highest';

    public const AGGREGATION_LOWEST = 'lowest';

    public const AGGREGATION_MEDIAN = 'median';

    /** Liste blanche des méthodes valides (validée SERVEUR à l'écriture). */
    public const AGGREGATION_METHODS = [
        self::AGGREGATION_WEIGHTED_MEAN,
        self::AGGREGATION_SIMPLE_MEAN,
        self::AGGREGATION_SUM,
        self::AGGREGATION_HIGHEST,
        self::AGGREGATION_LOWEST,
        self::AGGREGATION_MEDIAN,
    ];

    protected $table = 'academy_grade_categories';

    protected $fillable = [
        'course_id',
        'name',
        'weight',
        'aggregation_method',
        'position',
    ];

    protected $casts = [
        'course_id' => 'integer',
        'weight'    => 'float',
        'position'  => 'integer',
    ];

    protected $attributes = [
        'aggregation_method' => self::AGGREGATION_WEIGHTED_MEAN,
    ];

    /**
     * Libellés FR des méthodes d'agrégation (pour les sélecteurs d'UI). DRY :
     * source unique réutilisée par le composant et la vue.
     *
     * @return array<string, string>
     */
    public static function aggregationLabels(): array
    {
        return [
            self::AGGREGATION_WEIGHTED_MEAN => 'Moyenne pondérée (poids des évaluations)',
            self::AGGREGATION_SIMPLE_MEAN   => 'Moyenne simple (poids ignorés)',
            self::AGGREGATION_SUM           => 'Somme des pourcentages (plafonnée à 100)',
            self::AGGREGATION_HIGHEST       => 'Note la plus haute',
            self::AGGREGATION_LOWEST        => 'Note la plus basse',
            self::AGGREGATION_MEDIAN        => 'Médiane',
        ];
    }

    /** Méthode d'agrégation EFFECTIVE : la valeur stockée si valide, sinon le défaut. */
    public function effectiveAggregationMethod(): string
    {
        $method = (string) ($this->aggregation_method ?? self::AGGREGATION_WEIGHTED_MEAN);

        return in_array($method, self::AGGREGATION_METHODS, true)
            ? $method
            : self::AGGREGATION_WEIGHTED_MEAN;
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** Items de note classés dans cette catégorie. */
    public function gradeItems(): HasMany
    {
        return $this->hasMany(GradeItem::class);
    }

    /** Catégories d'un cours donné, ordonnées (anti-IDOR : toujours scopé course). */
    public function scopeForCourse(Builder $query, int $courseId): Builder
    {
        return $query->where('course_id', $courseId)
            ->orderBy('position')
            ->orderBy('id');
    }
}

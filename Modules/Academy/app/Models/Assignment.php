<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Devoir (assignment) d'un cours (Phase E / E2 - évaluation). Un devoir
 * appartient à UN cours (ownership clair) et peut être rattaché à une leçon
 * (lesson_id, optionnel) ou au cours entier. is_published = false → brouillon
 * (jamais visible d'un étudiant). Le contrôle d'appartenance et d'autorisation
 * est fait côté composant Livewire (autorisation SERVEUR, anti-IDOR).
 */

declare(strict_types=1);

namespace Modules\Academy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int         $id
 * @property int         $course_id
 * @property int|null    $lesson_id
 * @property string      $title
 * @property string|null $instructions
 * @property int         $max_points
 * @property \Illuminate\Support\Carbon|null $due_at
 * @property bool        $is_published
 * @property int         $position
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Assignment extends Model
{
    protected $table = 'academy_assignments';

    protected $fillable = [
        'course_id',
        'lesson_id',
        'title',
        'instructions',
        'max_points',
        'due_at',
        'is_published',
        'position',
    ];

    protected $casts = [
        'max_points'   => 'integer',
        'position'     => 'integer',
        'is_published' => 'boolean',
        'due_at'       => 'datetime',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    /**
     * V2-a - Critères de la grille d'évaluation (rubric) de CE devoir, ordonnés.
     * Aucun critère = devoir sans grille (note manuelle libre, inchangé).
     */
    public function rubricCriteria(): HasMany
    {
        return $this->hasMany(RubricCriterion::class)
            ->orderBy('position')
            ->orderBy('id');
    }

    /**
     * V2-a - Le devoir a-t-il une grille ACTIVE ? Vrai dès qu'au moins un critère
     * possède au moins un niveau (un critère sans niveau ne peut pas être noté).
     */
    public function hasRubric(): bool
    {
        return RubricCriterion::where('assignment_id', $this->id)
            ->whereHas('levels')
            ->exists();
    }

    /**
     * V2-a - Calcule la note à partir d'un choix de niveaux {criterion_id: level_id}.
     *
     * SÉCURITÉ : chaque niveau retenu est RE-RÉSOLU et doit appartenir au critère
     * (lui-même rattaché à CE devoir) ; un niveau étranger est ignoré (anti-IDOR).
     * La note brute = somme des points des niveaux retenus ; elle est MISE À
     * L'ÉCHELLE sur max_points du devoir (raw / max barème × max_points), ce qui la
     * borne naturellement à [0..max_points]. Les critères sans niveau sont ignorés.
     *
     * @param  array<int|string, int|string|null>  $selection  {criterion_id: level_id}
     * @return array{raw:int, max:int, scaled:int, normalized:array<int,int>, complete:bool}
     */
    public function gradeFromRubricSelection(array $selection): array
    {
        $criteria = $this->rubricCriteria()->with('levels')->get();

        $raw        = 0;
        $max        = 0;
        $normalized = [];
        $gradable   = 0;   // nb de critères notables (avec >=1 niveau)
        $chosen     = 0;   // nb de critères pour lesquels un niveau valide est retenu

        foreach ($criteria as $criterion) {
            $levels = $criterion->levels;
            if ($levels->isEmpty()) {
                continue; // critère non notable : ignoré du barème
            }
            $gradable++;
            $max += (int) $levels->max('points');

            $chosenId = $selection[$criterion->id] ?? null;
            if ($chosenId === null || $chosenId === '') {
                continue;
            }

            $level = $levels->firstWhere('id', (int) $chosenId);
            if ($level === null) {
                continue; // niveau d'un autre critère/devoir → ignoré (anti-IDOR)
            }

            $normalized[$criterion->id] = $level->id;
            $raw += (int) $level->points;
            $chosen++;
        }

        $scaled = $max > 0 ? (int) round($raw / $max * $this->max_points) : 0;
        $scaled = max(0, min($scaled, $this->max_points));

        return [
            'raw'        => $raw,
            'max'        => $max,
            'scaled'     => $scaled,
            'normalized' => $normalized,
            'complete'   => $gradable > 0 && $chosen === $gradable,
        ];
    }

    /** Devoirs PUBLIÉS uniquement (is_published = true). */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Consignes rendues en HTML SÛR (anti-XSS stockée) : on délègue au rendu
     * markdown unique de LessonItem::renderRichText (html_input=strip +
     * allow_unsafe_links=false). Rendu via {!! … !!} en toute sûreté.
     */
    public function renderedInstructions(): string
    {
        return LessonItem::renderRichText($this->instructions);
    }
}

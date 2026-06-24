<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F21 - ATELIER (Workshop) : un CRITÈRE de la grille d'évaluation par les pairs (type Moodle
 * « Workshop »). La grille est définie par le gérant dans l'éditeur de cours. Chaque
 * évaluation porte une note par critère (hasMany WorkshopAssessmentScore). SoftDeletes :
 * un critère retiré est conservé (les notes déjà saisies restent), exclu par défaut.
 */

declare(strict_types=1);

namespace Modules\Academy\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int         $id
 * @property int         $lesson_item_id
 * @property string      $label
 * @property string|null $description
 * @property int         $max_score
 * @property float       $weight
 * @property int         $position
 */
class WorkshopCriterion extends Model
{
    use SoftDeletes;

    protected $table = 'academy_workshop_criteria';

    protected $fillable = [
        'lesson_item_id',
        'label',
        'description',
        'max_score',
        'weight',
        'position',
    ];

    protected $casts = [
        'lesson_item_id' => 'integer',
        'max_score'      => 'integer',
        'weight'         => 'float',
        'position'       => 'integer',
    ];

    public function lessonItem(): BelongsTo
    {
        return $this->belongsTo(LessonItem::class, 'lesson_item_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(WorkshopAssessmentScore::class, 'criterion_id');
    }

    /** Scope : critères d'un item « workshop » donné, dans l'ordre de la grille. */
    public function scopeForItem(Builder $query, int $itemId): Builder
    {
        return $query->where('lesson_item_id', $itemId)->orderBy('position')->orderBy('id');
    }
}

<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F21 - ATELIER (Workshop) : la NOTE d'un critère pour une évaluation (type Moodle
 * « Workshop »). score est un entier borné 0..max_score du critère (la borne est appliquée
 * au service avant écriture). Aucune logique métier ici : simple porteur de donnée.
 */

declare(strict_types=1);

namespace Modules\Academy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $assessment_id
 * @property int $criterion_id
 * @property int $score
 */
class WorkshopAssessmentScore extends Model
{
    protected $table = 'academy_workshop_assessment_scores';

    protected $fillable = [
        'assessment_id',
        'criterion_id',
        'score',
    ];

    protected $casts = [
        'assessment_id' => 'integer',
        'criterion_id'  => 'integer',
        'score'         => 'integer',
    ];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(WorkshopAssessment::class, 'assessment_id');
    }

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(WorkshopCriterion::class, 'criterion_id');
    }
}

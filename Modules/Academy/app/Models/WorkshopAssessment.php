<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F21 - ATELIER (Workshop) : une ÉVALUATION d'un travail par un pair (type Moodle
 * « Workshop »). assessor_id = le pair qui évalue (null si compte supprimé). Porte une note
 * par critère (hasMany WorkshopAssessmentScore) + un feedback facultatif. computed_score est
 * la note dérivée du travail (0..100), calculée au service. submitted_at != null = rendue.
 * SoftDeletes : audit. scopeForAssessor : les évaluations attribuées à un pair.
 */

declare(strict_types=1);

namespace Modules\Academy\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int         $id
 * @property int         $submission_id
 * @property int|null    $assessor_id
 * @property string|null $feedback
 * @property float|null  $computed_score
 * @property \Illuminate\Support\Carbon|null $submitted_at
 */
class WorkshopAssessment extends Model
{
    use SoftDeletes;

    protected $table = 'academy_workshop_assessments';

    protected $fillable = [
        'submission_id',
        'assessor_id',
        'feedback',
        'computed_score',
        'submitted_at',
    ];

    protected $casts = [
        'submission_id'  => 'integer',
        'assessor_id'    => 'integer',
        'computed_score' => 'float',
        'submitted_at'   => 'datetime',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(WorkshopSubmission::class, 'submission_id');
    }

    /** Le pair évaluateur (null si compte supprimé). */
    public function assessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessor_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(WorkshopAssessmentScore::class, 'assessment_id');
    }

    /** Scope : évaluations attribuées à un pair évaluateur donné. */
    public function scopeForAssessor(Builder $query, int $assessorId): Builder
    {
        return $query->where('assessor_id', $assessorId);
    }

    /** Scope : évaluations effectivement rendues (note calculée). */
    public function scopeSubmitted(Builder $query): Builder
    {
        return $query->whereNotNull('submitted_at');
    }
}

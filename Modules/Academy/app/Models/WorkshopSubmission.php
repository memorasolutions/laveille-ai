<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F21 - ATELIER (Workshop) : un TRAVAIL remis par un inscrit (type Moodle « Workshop »).
 * user_id = auteur (null si compte supprimé => « (inconnu) »). Reçoit plusieurs évaluations
 * de pairs (hasMany WorkshopAssessment). body est du texte BRUT ; le rendu strippé est fait
 * à l'affichage (anti-XSS). SoftDeletes : un travail supprimé est conservé (audit).
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
 * @property int         $lesson_item_id
 * @property int|null    $user_id
 * @property string      $title
 * @property string|null $body
 * @property string      $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class WorkshopSubmission extends Model
{
    use SoftDeletes;

    protected $table = 'academy_workshop_submissions';

    protected $fillable = [
        'lesson_item_id',
        'user_id',
        'title',
        'body',
        'status',
    ];

    protected $casts = [
        'lesson_item_id' => 'integer',
        'user_id'        => 'integer',
    ];

    public function lessonItem(): BelongsTo
    {
        return $this->belongsTo(LessonItem::class, 'lesson_item_id');
    }

    /** Auteur du travail (null si compte supprimé). */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(WorkshopAssessment::class, 'submission_id');
    }

    /** Scope : travaux d'un item « workshop » donné. */
    public function scopeForItem(Builder $query, int $itemId): Builder
    {
        return $query->where('lesson_item_id', $itemId);
    }
}

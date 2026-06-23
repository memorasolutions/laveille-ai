<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * V1-b — Une tentative de quiz = une soumission. 1 ligne par soumission
 * (l'historique réel), contrairement à Completion (upsertée/idempotente).
 */

declare(strict_types=1);

namespace Modules\Academy\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int                              $id
 * @property int                              $user_id
 * @property int                              $lesson_item_id
 * @property int                              $course_id
 * @property int                              $score
 * @property int                              $max_score
 * @property int                              $percent
 * @property bool                             $passed
 * @property bool                             $timed_out
 * @property bool                             $needs_grading
 * @property array                            $answers
 * @property array|null                       $manual_scores
 * @property array|null                       $manual_feedback
 * @property array|null                       $questions_snapshot
 * @property \Illuminate\Support\Carbon|null  $started_at
 * @property \Illuminate\Support\Carbon       $submitted_at
 * @property \Illuminate\Support\Carbon|null  $graded_at
 * @property int|null                         $graded_by
 * @property \Illuminate\Support\Carbon|null  $created_at
 * @property \Illuminate\Support\Carbon|null  $updated_at
 */
class QuizAttempt extends Model
{
    protected $table = 'academy_quiz_attempts';

    protected $fillable = [
        'user_id',
        'lesson_item_id',
        'course_id',
        'score',
        'max_score',
        'percent',
        'passed',
        // V1-d : true si la soumission est arrivée hors-temps (garde serveur).
        'timed_out',
        // ESSAI : true tant qu'au moins un essai de la tentative reste à corriger.
        'needs_grading',
        'answers',
        // ESSAI : {index_essai: points} et {index_essai: feedback} (saisie formateur).
        'manual_scores',
        'manual_feedback',
        'questions_snapshot',
        'started_at',
        'submitted_at',
        // ESSAI : horodatage + auteur de la correction (jamais une valeur du client).
        'graded_at',
        'graded_by',
    ];

    protected $casts = [
        'score'              => 'integer',
        'max_score'          => 'integer',
        'percent'            => 'integer',
        'passed'             => 'boolean',
        'timed_out'          => 'boolean',
        'needs_grading'      => 'boolean',
        'answers'            => 'array',
        'manual_scores'      => 'array',
        'manual_feedback'    => 'array',
        'questions_snapshot' => 'array',
        'started_at'         => 'datetime',
        'submitted_at'       => 'datetime',
        'graded_at'          => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** ESSAI : correcteur (formateur) ayant attribué les points manuels. */
    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    public function lessonItem(): BelongsTo
    {
        return $this->belongsTo(LessonItem::class, 'lesson_item_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    /**
     * Tentatives d'un item donné.
     */
    public function scopeForItem(Builder $query, int $itemId): Builder
    {
        return $query->where('lesson_item_id', $itemId);
    }

    /**
     * Tentatives d'un utilisateur donné.
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Nombre de tentatives d'un utilisateur pour un item (= application de attempts_allowed).
     */
    public static function attemptCount(int $userId, int $itemId): int
    {
        return static::query()
            ->where('user_id', $userId)
            ->where('lesson_item_id', $itemId)
            ->count();
    }

    /** ESSAI : tentatives EN ATTENTE de correction (au moins un essai non corrigé). */
    public function scopeNeedsGrading(Builder $query): Builder
    {
        return $query->where('needs_grading', true);
    }

    /** ESSAI : tentatives FINALISÉES (auto-notées OU correction terminée). */
    public function scopeFinalized(Builder $query): Builder
    {
        return $query->where('needs_grading', false);
    }
}

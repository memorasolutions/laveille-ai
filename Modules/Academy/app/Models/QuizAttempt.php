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
 * @property array                            $answers
 * @property array|null                       $questions_snapshot
 * @property \Illuminate\Support\Carbon|null  $started_at
 * @property \Illuminate\Support\Carbon       $submitted_at
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
        'answers',
        'questions_snapshot',
        'started_at',
        'submitted_at',
    ];

    protected $casts = [
        'score'              => 'integer',
        'max_score'          => 'integer',
        'percent'            => 'integer',
        'passed'             => 'boolean',
        'answers'            => 'array',
        'questions_snapshot' => 'array',
        'started_at'         => 'datetime',
        'submitted_at'       => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
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
}

<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Academy\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int         $id
 * @property int         $user_id
 * @property int         $course_id
 * @property int         $lesson_item_id
 * @property string      $status
 * @property int|null    $score
 * @property int|null    $qt_attempt_id
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Completion extends Model
{
    protected $fillable = [
        'user_id',
        'course_id',
        'lesson_item_id',
        'status',
        'score',
        'qt_attempt_id',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'score'         => 'integer',
        'qt_attempt_id' => 'integer',
        'started_at'    => 'datetime',
        'completed_at'  => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lessonItem(): BelongsTo
    {
        return $this->belongsTo(LessonItem::class, 'lesson_item_id');
    }
}

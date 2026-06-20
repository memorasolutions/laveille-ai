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
 * @property int      $id
 * @property int      $user_id
 * @property int      $course_id
 * @property int      $percent
 * @property int|null $last_lesson_item_id
 * @property \Illuminate\Support\Carbon|null $last_activity_at
 * @property int      $required_total
 * @property int      $required_completed
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Progress extends Model
{
    // Le pluriel Laravel de « Progress » est « progress » (mot indénombrable),
    // alors que la migration crée « progresses ». On force le vrai nom de table
    // pour aligner le modèle sur sa migration et la relation Course::progresses().
    protected $table = 'progresses';

    protected $fillable = [
        'user_id',
        'course_id',
        'percent',
        'last_lesson_item_id',
        'last_activity_at',
        'required_total',
        'required_completed',
    ];

    protected $casts = [
        'percent'             => 'integer',
        'last_lesson_item_id' => 'integer',
        'last_activity_at'    => 'datetime',
        'required_total'      => 'integer',
        'required_completed'  => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lastLessonItem(): BelongsTo
    {
        return $this->belongsTo(LessonItem::class, 'last_lesson_item_id');
    }
}

<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * FEEDBACK - réponse d'un étudiant à un item de leçon « feedback » (questionnaire
 * multi-questions, non noté). `answers` = { index_question => valeur }. `user_id`
 * est NULL pour une réponse anonyme (aucun lien vers l'identité).
 */

declare(strict_types=1);

namespace Modules\Academy\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int      $id
 * @property int      $lesson_item_id
 * @property int|null $user_id
 * @property array    $answers
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class FeedbackResponse extends Model
{
    protected $table = 'academy_feedback_responses';

    protected $fillable = [
        'lesson_item_id',
        'user_id',
        'answers',
    ];

    protected $casts = [
        'answers'        => 'array',
        'lesson_item_id' => 'integer',
        'user_id'        => 'integer',
    ];

    public function lessonItem(): BelongsTo
    {
        return $this->belongsTo(LessonItem::class, 'lesson_item_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

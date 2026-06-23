<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * CHOICE - vote d'un étudiant à un item de leçon « choice » (sondage non noté).
 * Une seule ligne par (lesson_item_id, user_id) ; `choices` = indices choisis.
 */

declare(strict_types=1);

namespace Modules\Academy\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int   $id
 * @property int   $lesson_item_id
 * @property int   $user_id
 * @property array $choices
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class ChoiceResponse extends Model
{
    protected $table = 'academy_choice_responses';

    protected $fillable = [
        'lesson_item_id',
        'user_id',
        'choices',
    ];

    protected $casts = [
        'choices'        => 'array',
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

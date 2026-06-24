<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F18 - NOTE / RATING (étoiles 1 à 5) sur un item de leçon (parité Moodle
 * « ratings »). UNE note par (item, utilisateur) : contrainte UNIQUE en base ;
 * re-noter MET À JOUR la même ligne (updateOrCreate côté contrôleur).
 */

declare(strict_types=1);

namespace Modules\Academy\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $lesson_item_id
 * @property int $user_id
 * @property int $value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class ItemRating extends Model
{
    protected $table = 'academy_item_ratings';

    protected $fillable = [
        'lesson_item_id',
        'user_id',
        'value',
    ];

    protected $casts = [
        'lesson_item_id' => 'integer',
        'user_id'        => 'integer',
        'value'          => 'integer',
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

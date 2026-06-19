<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Academy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int         $id
 * @property int         $lesson_id
 * @property string      $type
 * @property string      $title
 * @property int         $position
 * @property array|null  $payload
 * @property int|null    $estimated_minutes
 * @property bool        $is_required
 * @property string|null $external_ref
 * @property int|null    $poster_media_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class LessonItem extends Model
{
    protected $fillable = [
        'lesson_id',
        'type',
        'title',
        'position',
        'payload',
        'estimated_minutes',
        'is_required',
        'external_ref',
        'poster_media_id',
    ];

    protected $casts = [
        'payload'           => 'array',
        'is_required'       => 'boolean',
        'position'          => 'integer',
        'estimated_minutes' => 'integer',
    ];

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function completions(): HasMany
    {
        return $this->hasMany(Completion::class, 'lesson_item_id');
    }
}

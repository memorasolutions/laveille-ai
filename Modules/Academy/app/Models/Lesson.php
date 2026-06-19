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
 * @property int         $chapter_id
 * @property string      $title
 * @property string      $slug
 * @property int         $position
 * @property string|null $summary
 * @property int|null    $estimated_minutes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Lesson extends Model
{
    protected $fillable = [
        'chapter_id',
        'title',
        'slug',
        'position',
        'summary',
        'estimated_minutes',
    ];

    protected $casts = [
        'position'          => 'integer',
        'estimated_minutes' => 'integer',
    ];

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    public function lessonItems(): HasMany
    {
        return $this->hasMany(LessonItem::class);
    }
}

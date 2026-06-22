<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * V2-b - AFFECTATION d'un ITEM de note (quiz ou devoir) à une CATÉGORIE + POIDS.
 * item_type = 'quiz' (item_id = LessonItem.id) | 'assignment' (item_id =
 * Assignment.id). grade_category_id nullable (set null à la suppression de la
 * catégorie → item « non classé », jamais perdu). Le contrôle d'appartenance et
 * d'autorisation est fait côté composant Livewire (anti-IDOR : tout item ciblé
 * est re-résolu scopé au cours).
 */

declare(strict_types=1);

namespace Modules\Academy\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int      $id
 * @property int      $course_id
 * @property string   $item_type
 * @property int      $item_id
 * @property int|null $grade_category_id
 * @property float    $weight
 * @property int      $position
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class GradeItem extends Model
{
    public const TYPE_QUIZ = 'quiz';

    public const TYPE_ASSIGNMENT = 'assignment';

    public const TYPES = [self::TYPE_QUIZ, self::TYPE_ASSIGNMENT];

    protected $table = 'academy_grade_items';

    protected $fillable = [
        'course_id',
        'item_type',
        'item_id',
        'grade_category_id',
        'weight',
        'position',
    ];

    protected $casts = [
        'course_id'         => 'integer',
        'item_id'           => 'integer',
        'grade_category_id' => 'integer',
        'weight'            => 'float',
        'position'          => 'integer',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(GradeCategory::class, 'grade_category_id');
    }

    /** Items de note d'un cours donné (anti-IDOR : toujours scopé course). */
    public function scopeForCourse(Builder $query, int $courseId): Builder
    {
        return $query->where('course_id', $courseId);
    }
}

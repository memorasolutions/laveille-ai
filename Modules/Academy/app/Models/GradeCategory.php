<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * V2-b - CATÉGORIE de notes pondérée d'un cours (gradebook Moodle). Appartient à
 * UN cours (course_id) et porte un POIDS en %. Le contrôle d'appartenance et
 * d'autorisation est fait côté composant Livewire (autorisation SERVEUR ; toute
 * catégorie ciblée est re-résolue scopée au cours = anti-IDOR).
 */

declare(strict_types=1);

namespace Modules\Academy\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int    $id
 * @property int    $course_id
 * @property string $name
 * @property float  $weight
 * @property int    $position
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class GradeCategory extends Model
{
    protected $table = 'academy_grade_categories';

    protected $fillable = [
        'course_id',
        'name',
        'weight',
        'position',
    ];

    protected $casts = [
        'course_id' => 'integer',
        'weight'    => 'float',
        'position'  => 'integer',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** Items de note classés dans cette catégorie. */
    public function gradeItems(): HasMany
    {
        return $this->hasMany(GradeItem::class);
    }

    /** Catégories d'un cours donné, ordonnées (anti-IDOR : toujours scopé course). */
    public function scopeForCourse(Builder $query, int $courseId): Builder
    {
        return $query->where('course_id', $courseId)
            ->orderBy('position')
            ->orderBy('id');
    }
}

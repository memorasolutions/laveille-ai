<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Catégorie de cours (taxonomie simple, parité Moodle). Un cours a 0 ou 1
 * catégorie (voir Course::category()). Gestion CRUD réservée à academy.manage
 * (Livewire\CourseCategoryManager) ; les formateurs choisissent une catégorie
 * existante pour leur cours, ils n'en créent/renomment/suppriment aucune.
 */

declare(strict_types=1);

namespace Modules\Academy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int         $id
 * @property string      $name
 * @property string      $slug
 * @property string|null $color
 * @property string|null $icon
 * @property int         $position
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class CourseCategory extends Model
{
    protected $table = 'academy_course_categories';

    protected $fillable = [
        'name',
        'slug',
        'color',
        'icon',
        'position',
    ];

    protected $casts = [
        'position' => 'integer',
    ];

    /**
     * Cours classés dans cette catégorie.
     */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'category_id');
    }
}

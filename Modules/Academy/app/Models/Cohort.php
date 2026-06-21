<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Cohorte / groupe d'apprenants - Phase C (gestion à l'échelle).
 * Une cohorte appartient à UN cours (ownership clair) ; ses membres sont des
 * utilisateurs inscrits à ce cours (le contrôle d'appartenance est fait côté
 * composant Livewire, autorisation serveur).
 */

declare(strict_types=1);

namespace Modules\Academy\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int         $id
 * @property int         $course_id
 * @property string      $name
 * @property string|null $slug
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Cohort extends Model
{
    protected $table = 'academy_cohorts';

    protected $fillable = [
        'course_id',
        'name',
        'slug',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'academy_cohort_user', 'cohort_id', 'user_id')
            ->withTimestamps();
    }
}

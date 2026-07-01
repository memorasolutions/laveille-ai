<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Trace d'un passage du test de positionnement adaptatif (CAT). Une ligne par
 * tentative : jamais de mise à jour croisée entre apprenants/cours (anti-IDOR
 * par construction, chaque tentative est scopée à son user_id/course_id).
 */

declare(strict_types=1);

namespace Modules\Academy\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int         $id
 * @property int         $user_id
 * @property int         $course_id
 * @property array       $questions_asked
 * @property string|null $estimated_level
 * @property int|null    $recommended_lesson_id
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class PlacementAttempt extends Model
{
    protected $table = 'academy_placement_attempts';

    protected $fillable = [
        'user_id',
        'course_id',
        'questions_asked',
        'estimated_level',
        'recommended_lesson_id',
        'completed_at',
    ];

    protected $casts = [
        'user_id'               => 'integer',
        'course_id'             => 'integer',
        'questions_asked'       => 'array',
        'recommended_lesson_id' => 'integer',
        'completed_at'          => 'datetime',
    ];

    /** Apprenant ayant passé le test. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Cours pour lequel le positionnement a été fait. */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** Leçon de départ recommandée à l'issue du test (null si non complété). */
    public function recommendedLesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class, 'recommended_lesson_id');
    }

    /** La tentative est-elle terminée (niveau estimé + recommandation calculés) ? */
    public function isComplete(): bool
    {
        return $this->completed_at !== null;
    }
}

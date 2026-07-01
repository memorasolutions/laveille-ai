<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Un événement d'XP crédité (gamification moderne, LMS 2026). Table append-only :
 * on ne modifie ni ne retire jamais une ligne, le total est TOUJOURS dérivé par
 * SUM(points) (voir GamificationService::totalPointsFor). L'idempotence est
 * garantie par la contrainte unique (user_id, source_type, source_id, reason) —
 * voir la migration pour le détail anti-triche.
 */

declare(strict_types=1);

namespace Modules\Academy\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int         $id
 * @property int         $user_id
 * @property int|null    $course_id
 * @property string      $source_type
 * @property int         $source_id
 * @property string      $reason
 * @property int         $points
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class XpEvent extends Model
{
    protected $table = 'academy_xp_events';

    protected $fillable = [
        'user_id',
        'course_id',
        'source_type',
        'source_id',
        'reason',
        'points',
    ];

    protected $casts = [
        'user_id'    => 'integer',
        'course_id'  => 'integer',
        'source_id'  => 'integer',
        'points'     => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** Scope anti-IDOR : ne renvoie que les événements d'un utilisateur donné. */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /** Scope : ne renvoie que les événements rattachés à un cours donné. */
    public function scopeForCourse(Builder $query, int $courseId): Builder
    {
        return $query->where('course_id', $courseId);
    }
}

<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Academy\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Badge GAGNÉ par un utilisateur (pivot enrichi). Idempotence garantie par la
 * contrainte unique (badge_id, user_id, course_id) + firstOrCreate dans BadgeService.
 *
 * @property int         $id
 * @property int         $badge_id
 * @property int         $user_id
 * @property int|null    $course_id
 * @property \Illuminate\Support\Carbon|null $awarded_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class UserBadge extends Model
{
    protected $table = 'academy_user_badges';

    protected $fillable = [
        'badge_id',
        'user_id',
        'course_id',
        'awarded_at',
    ];

    protected $casts = [
        'awarded_at' => 'datetime',
    ];

    public function badge(): BelongsTo
    {
        return $this->belongsTo(Badge::class, 'badge_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    /** Scope anti-IDOR : badges d'un utilisateur donné uniquement. */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}

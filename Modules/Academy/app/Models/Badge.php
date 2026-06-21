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
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int         $id
 * @property string      $key
 * @property string      $name
 * @property string|null $description
 * @property string|null $icon
 * @property string      $criteria_type
 * @property int|null    $criteria_value
 * @property bool        $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Badge extends Model
{
    protected $table = 'academy_badges';

    protected $fillable = [
        'key',
        'name',
        'description',
        'icon',
        'criteria_type',
        'criteria_value',
        'is_active',
    ];

    protected $casts = [
        'criteria_value' => 'integer',
        'is_active'      => 'boolean',
    ];

    /** Lignes pivot (badges décernés) de ce badge. */
    public function userBadges(): HasMany
    {
        return $this->hasMany(UserBadge::class, 'badge_id');
    }

    /** Utilisateurs ayant gagné ce badge. */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'academy_user_badges', 'badge_id', 'user_id')
            ->withPivot(['course_id', 'awarded_at'])
            ->withTimestamps();
    }

    /** Scope : badges actifs uniquement. */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

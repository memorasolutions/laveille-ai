<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Grant d'accès au Tuteur IA, PAR APPRENANT ET PAR COURS. Voir
 * Modules\Academy\Services\TutorAccessService pour le calcul (figé, non
 * rétroactif) et la vérification (fenêtre + quota).
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
 * @property \Illuminate\Support\Carbon $access_starts_at
 * @property \Illuminate\Support\Carbon|null $access_expires_at
 * @property int         $questions_used_current_period
 * @property \Illuminate\Support\Carbon|null $period_reset_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class TutorAccessGrant extends Model
{
    protected $table = 'academy_ai_tutor_grants';

    protected $fillable = [
        'user_id',
        'course_id',
        'access_starts_at',
        'access_expires_at',
        'questions_used_current_period',
        'period_reset_at',
    ];

    protected $casts = [
        'access_starts_at'              => 'datetime',
        'access_expires_at'             => 'datetime',
        'questions_used_current_period' => 'integer',
        'period_reset_at'               => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}

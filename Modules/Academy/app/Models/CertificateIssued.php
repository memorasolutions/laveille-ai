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
 * @property int         $id
 * @property int         $user_id
 * @property int         $course_id
 * @property string      $serial
 * @property string      $verification_hash
 * @property \Illuminate\Support\Carbon|null $issued_at
 * @property int|null    $hours_earned
 * @property int|null    $final_score
 * @property string      $public_url_slug
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class CertificateIssued extends Model
{
    protected $fillable = [
        'user_id',
        'course_id',
        'serial',
        'verification_hash',
        'issued_at',
        'hours_earned',
        'final_score',
        'public_url_slug',
    ];

    protected $casts = [
        'issued_at'   => 'datetime',
        'hours_earned' => 'integer',
        'final_score' => 'integer',
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

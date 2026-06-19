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
 * @property string      $status
 * @property string      $source
 * @property string|null $cashier_id
 * @property int|null    $amount_cents
 * @property string|null $currency
 * @property \Illuminate\Support\Carbon|null $enrolled_at
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $cancelled_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Enrollment extends Model
{
    protected $fillable = [
        'user_id',
        'course_id',
        'status',
        'source',
        'cashier_id',
        'amount_cents',
        'currency',
        'enrolled_at',
        'expires_at',
        'cancelled_at',
    ];

    protected $casts = [
        'amount_cents' => 'integer',
        'enrolled_at'  => 'datetime',
        'expires_at'   => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

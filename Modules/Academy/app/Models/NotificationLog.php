<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * V5-c - Journal anti-doublon des notifications envoyées (idempotence des rappels).
 */

declare(strict_types=1);

namespace Modules\Academy\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int    $id
 * @property int    $user_id
 * @property string $type
 * @property string $dedup_key
 * @property \Illuminate\Support\Carbon|null $sent_at
 */
class NotificationLog extends Model
{
    protected $table = 'academy_notification_logs';

    protected $fillable = [
        'user_id',
        'type',
        'dedup_key',
        'sent_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'sent_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

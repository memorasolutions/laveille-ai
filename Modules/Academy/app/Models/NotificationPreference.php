<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * V5-c - Préférence de notification courriel d'un utilisateur pour un type donné.
 * Absence de ligne = défaut de config (academy.notifications.defaults.{type}).
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
 * @property bool   $enabled
 */
class NotificationPreference extends Model
{
    protected $table = 'academy_notification_preferences';

    protected $fillable = [
        'user_id',
        'type',
        'enabled',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'enabled' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

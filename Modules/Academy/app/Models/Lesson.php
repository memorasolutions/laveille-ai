<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Academy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property int         $chapter_id
 * @property string      $title
 * @property string      $slug
 * @property int         $position
 * @property string|null $summary
 * @property int|null    $estimated_minutes
 * @property int|null    $drip_days
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Lesson extends Model
{
    protected $fillable = [
        'chapter_id',
        'title',
        'slug',
        'position',
        'summary',
        'estimated_minutes',
        'drip_days',
    ];

    protected $casts = [
        'position'          => 'integer',
        'estimated_minutes' => 'integer',
        'drip_days'         => 'integer',
    ];

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    public function lessonItems(): HasMany
    {
        return $this->hasMany(LessonItem::class);
    }

    // -------------------------------------------------------------------------
    // Drip (libération progressive) - C4
    // -------------------------------------------------------------------------

    /**
     * Date à laquelle la leçon devient disponible pour une inscription donnée
     * (enrolled_at + drip_days), ou null si la leçon est disponible immédiatement
     * (drip_days vide/0) ou si l'on ne dispose d'aucune date d'inscription.
     *
     * @param  \Illuminate\Support\Carbon|null  $enrolledAt  date d'inscription de l'utilisateur
     */
    public function dripAvailableAt(?Carbon $enrolledAt): ?Carbon
    {
        $days = (int) ($this->drip_days ?? 0);

        if ($days <= 0 || $enrolledAt === null) {
            return null;
        }

        return $enrolledAt->copy()->addDays($days);
    }

    /**
     * Vrai si la leçon est ENCORE verrouillée par le drip pour cette date
     * d'inscription (la date de disponibilité est dans le futur). Calcul SERVEUR :
     * jamais de confiance au client. Une leçon sans drip (ou sans inscription
     * connue) n'est jamais verrouillée par ce mécanisme.
     */
    public function isDripLockedFor(?Carbon $enrolledAt): bool
    {
        $availableAt = $this->dripAvailableAt($enrolledAt);

        return $availableAt !== null && $availableAt->isFuture();
    }
}

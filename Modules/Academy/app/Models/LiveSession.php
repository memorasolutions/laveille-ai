<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Séance en direct / visioconférence rattachée à un cours (et, en option, à
 * une cohorte). MVP « par lien » : join_url est collé par le formateur ;
 * provider par défaut = Google Meet. Les heures (starts_at/ends_at) sont des
 * Carbon UTC ; l'affichage Québec d'abord est fait dans les vues.
 */

declare(strict_types=1);

namespace Modules\Academy\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int         $id
 * @property int         $course_id
 * @property int|null    $cohort_id
 * @property string      $title
 * @property string|null $description
 * @property string      $provider
 * @property string      $join_url
 * @property \Illuminate\Support\Carbon      $starts_at
 * @property \Illuminate\Support\Carbon|null $ends_at
 * @property int|null    $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class LiveSession extends Model
{
    protected $table = 'academy_live_sessions';

    /** Fournisseurs de visioconférence supportés (Google Meet en tête). */
    public const PROVIDERS = ['meet', 'zoom', 'teams', 'other'];

    /** Libellés lisibles des fournisseurs (affichage + UI formateur). */
    public const PROVIDER_LABELS = [
        'meet'  => 'Google Meet',
        'zoom'  => 'Zoom',
        'teams' => 'Microsoft Teams',
        'other' => 'Autre',
    ];

    protected $fillable = [
        'course_id',
        'cohort_id',
        'title',
        'description',
        'provider',
        'join_url',
        'starts_at',
        'ends_at',
        'created_by',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at'   => 'datetime',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function cohort(): BelongsTo
    {
        return $this->belongsTo(Cohort::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(LiveSessionAttendance::class);
    }

    /** Libellé lisible du fournisseur (défaut « Autre » si inconnu). */
    public function providerLabel(): string
    {
        return self::PROVIDER_LABELS[$this->provider] ?? self::PROVIDER_LABELS['other'];
    }

    /** Vrai si la séance est à venir (n'a pas encore commencé). */
    public function isUpcoming(): bool
    {
        return $this->starts_at->isFuture();
    }
}

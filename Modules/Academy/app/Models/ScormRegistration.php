<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Import SCORM - état d'exécution CMI par (utilisateur, item de leçon).
 * Voir migration create_academy_scorm_registrations_table pour le détail des
 * colonnes et les limites documentées (single-SCO, pas de séquencement 2004).
 */

declare(strict_types=1);

namespace Modules\Academy\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int         $id
 * @property int         $user_id
 * @property int         $lesson_item_id
 * @property string|null $lesson_status
 * @property int|null    $score_raw
 * @property string|null $lesson_location
 * @property string|null $suspend_data
 * @property array|null  $cmi_data
 * @property \Illuminate\Support\Carbon|null $last_committed_at
 */
class ScormRegistration extends Model
{
    protected $table = 'academy_scorm_registrations';

    protected $fillable = [
        'user_id',
        'lesson_item_id',
        'lesson_status',
        'score_raw',
        'lesson_location',
        'suspend_data',
        'cmi_data',
        'last_committed_at',
    ];

    protected $casts = [
        'cmi_data'          => 'array',
        'score_raw'         => 'integer',
        'last_committed_at' => 'datetime',
    ];

    /**
     * Statuts SCORM 1.2/2004 considérés comme une COMPLÉTION effective de
     * l'item (bridgés vers CompletionService::markComplete). « failed » et
     * « incomplete » ne complètent PAS l'item (l'apprenant peut reprendre).
     */
    public const COMPLETING_STATUSES = ['completed', 'passed'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lessonItem(): BelongsTo
    {
        return $this->belongsTo(LessonItem::class);
    }
}

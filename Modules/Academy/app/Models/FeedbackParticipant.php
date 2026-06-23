<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * FEEDBACK - PARTICIPATION : trace QUI (étudiant authentifié) a répondu à un item de
 * leçon « feedback », SANS lien vers le contenu de ses réponses. C'est le filet anti
 * re-spam ANONYME robuste : la réponse anonyme reste user_id NULL dans
 * {@see FeedbackResponse}, alors qu'ICI on enregistre uniquement le FAIT d'avoir
 * participé (anonymat des RÉPONSES intégralement préservé). UNIQUE(item, user) borne
 * le re-spam même après reconnexion (la session seule est contournable).
 */

declare(strict_types=1);

namespace Modules\Academy\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $lesson_item_id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class FeedbackParticipant extends Model
{
    protected $table = 'academy_feedback_participants';

    protected $fillable = [
        'lesson_item_id',
        'user_id',
    ];

    protected $casts = [
        'lesson_item_id' => 'integer',
        'user_id'        => 'integer',
    ];

    public function lessonItem(): BelongsTo
    {
        return $this->belongsTo(LessonItem::class, 'lesson_item_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

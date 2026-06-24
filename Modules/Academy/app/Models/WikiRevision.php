<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F19 - WIKI : instantané (révision) d'une page de wiki. Une ligne = l'état PRÉCÉDENT
 * d'une page juste avant une édition (même principe que QuestionVersion F17). Lecture
 * seule côté métier : on ne réécrit jamais une révision (on en crée une nouvelle).
 */

declare(strict_types=1);

namespace Modules\Academy\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int         $id
 * @property int         $wiki_page_id
 * @property int|null    $user_id
 * @property string      $title
 * @property string|null $body
 * @property int         $revision
 * @property \Illuminate\Support\Carbon|null $snapshot_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class WikiRevision extends Model
{
    protected $table = 'academy_wiki_revisions';

    protected $fillable = [
        'wiki_page_id',
        'user_id',
        'title',
        'body',
        'revision',
        'snapshot_at',
    ];

    protected $casts = [
        'wiki_page_id' => 'integer',
        'user_id'      => 'integer',
        'revision'     => 'integer',
        'snapshot_at'  => 'datetime',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(WikiPage::class, 'wiki_page_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Corps de la révision rendu en HTML sûr (anti-XSS), pour l'aperçu en historique. */
    public function renderedBody(): string
    {
        return LessonItem::renderRichText($this->body);
    }
}

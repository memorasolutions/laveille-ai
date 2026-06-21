<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Annonce de cours (Phase D / D3 - pilotage). Une annonce appartient à UN cours
 * (ownership clair). published_at non null = publiée (visible des inscrits actifs) ;
 * null = brouillon (jamais visible d'un étudiant). Le contrôle d'appartenance et
 * d'autorisation est fait côté composant Livewire (autorisation SERVEUR).
 */

declare(strict_types=1);

namespace Modules\Academy\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int         $id
 * @property int         $course_id
 * @property int|null    $author_id
 * @property string      $title
 * @property string      $body
 * @property \Illuminate\Support\Carbon|null $published_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Announcement extends Model
{
    protected $table = 'academy_announcements';

    protected $fillable = [
        'course_id',
        'author_id',
        'title',
        'body',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** Annonces PUBLIÉES uniquement (published_at non null et dans le passé/présent). */
    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /** Vrai si l'annonce est publiée (brouillon = published_at null). */
    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->published_at->lessThanOrEqualTo(now());
    }

    /**
     * Corps de l'annonce rendu en HTML SÛR (anti-XSS stockée) : on délègue au
     * rendu markdown unique de LessonItem::renderRichText (html_input=strip +
     * allow_unsafe_links=false). Le résultat peut être rendu via {!! … !!} en
     * toute sûreté : aucun HTML brut de l'utilisateur n'y survit.
     */
    public function renderedBody(): string
    {
        return LessonItem::renderRichText($this->body);
    }
}

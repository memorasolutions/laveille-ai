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
use Modules\Media\Traits\HasMediaAttachments;
use Spatie\MediaLibrary\HasMedia;

/**
 * @property int         $id
 * @property int         $lesson_id
 * @property string      $type
 * @property string      $title
 * @property int         $position
 * @property array|null  $payload
 * @property int|null    $estimated_minutes
 * @property bool        $is_required
 * @property string|null $external_ref
 * @property int|null    $poster_media_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class LessonItem extends Model implements HasMedia
{
    use HasMediaAttachments;  // upload/stockage Spatie (collections « poster » + « attachments »)

    protected $fillable = [
        'lesson_id',
        'type',
        'title',
        'position',
        'payload',
        'estimated_minutes',
        'is_required',
        'external_ref',
        'poster_media_id',
    ];

    protected $casts = [
        'payload'           => 'array',
        'is_required'       => 'boolean',
        'position'          => 'integer',
        'estimated_minutes' => 'integer',
    ];

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function completions(): HasMany
    {
        return $this->hasMany(Completion::class, 'lesson_item_id');
    }

    // -------------------------------------------------------------------------
    // Média : affiche vidéo (collection « poster ») + pièces jointes document
    // (collection « attachments »). Stockage Spatie, disque public.
    // -------------------------------------------------------------------------

    /**
     * « poster »     : une seule affiche/vignette par item vidéo (formats web sûrs).
     * « attachments » : plusieurs documents (pdf / word / images) pour un item document.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('poster')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);

        $this->addMediaCollection('attachments')
            ->acceptsMimeTypes([
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'image/jpeg',
                'image/png',
                'image/webp',
            ]);
    }

    /**
     * URL de l'affiche de la vidéo (collection « poster »), ou null si absente.
     * Source de vérité = collection Spatie ; on retombe sur payload['poster']
     * (URL externe saisie à la main) pour la rétrocompatibilité des anciens items.
     */
    public function posterUrl(string $conversion = ''): ?string
    {
        $media = $this->getFirstMedia('poster');
        if ($media) {
            return $media->getUrl($conversion);
        }

        return $this->payload['poster'] ?? null;
    }
}

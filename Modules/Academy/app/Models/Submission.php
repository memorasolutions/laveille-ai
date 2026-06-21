<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Remise (submission) d'un devoir (Phase E / E2 - évaluation). Une remise
 * appartient à UN devoir et UN utilisateur (unicité). score null = non corrigé.
 * Pièce jointe optionnelle stockée via Spatie MediaLibrary (collection
 * « attachment », mime+taille validés SERVEUR, nom non devinable). Le contrôle
 * d'appartenance et d'autorisation est fait côté composant Livewire.
 */

declare(strict_types=1);

namespace Modules\Academy\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Media\Traits\HasMediaAttachments;
use Spatie\MediaLibrary\HasMedia;

/**
 * @property int         $id
 * @property int         $assignment_id
 * @property int         $user_id
 * @property string|null $body
 * @property \Illuminate\Support\Carbon|null $submitted_at
 * @property int|null    $score
 * @property string|null $feedback
 * @property \Illuminate\Support\Carbon|null $graded_at
 * @property int|null    $graded_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Submission extends Model implements HasMedia
{
    use HasMediaAttachments;  // upload/stockage Spatie (collection « attachment », disque public)

    protected $table = 'academy_submissions';

    protected $fillable = [
        'assignment_id',
        'user_id',
        'body',
        'submitted_at',
        'score',
        'feedback',
        'graded_at',
        'graded_by',
    ];

    protected $casts = [
        'score'        => 'integer',
        'submitted_at' => 'datetime',
        'graded_at'    => 'datetime',
    ];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    /** Vrai si la remise a été corrigée (score posé, graded_at non null). */
    public function isGraded(): bool
    {
        return $this->graded_at !== null && $this->score !== null;
    }

    /**
     * Collection unique « attachment » : une seule pièce jointe par remise,
     * restreinte aux formats sûrs (pdf / word / images). `singleFile()` remplace
     * automatiquement l'ancienne lors d'un nouveau téléversement.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachment')
            ->singleFile()
            ->acceptsMimeTypes([
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'image/jpeg',
                'image/png',
                'image/webp',
            ]);
    }

    /** URL de la pièce jointe (collection « attachment »), ou null si absente. */
    public function attachmentUrl(): ?string
    {
        $media = $this->getFirstMedia('attachment');

        return $media ? $media->getUrl() : null;
    }

    /**
     * Corps de la remise rendu en HTML SÛR (anti-XSS stockée), via le rendu
     * markdown unique de LessonItem::renderRichText.
     */
    public function renderedBody(): string
    {
        return LessonItem::renderRichText($this->body);
    }

    /**
     * Feedback de correction rendu en HTML SÛR (anti-XSS stockée), via le rendu
     * markdown unique de LessonItem::renderRichText.
     */
    public function renderedFeedback(): string
    {
        return LessonItem::renderRichText($this->feedback);
    }
}

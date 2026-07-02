<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Academy\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Media\Traits\HasMediaAttachments;
use Spatie\MediaLibrary\HasMedia;

/**
 * Bibliothèque d'images d'arrière-plan réutilisables pour un DiplomaTemplate
 * (Phase 3 — système de diplomation moderne). Chaque arrière-plan est owner-scopé
 * via le champ `created_by` et stocké dans la collection « background » gérée par
 * Spatie MediaLibrary, suivant EXACTEMENT le même pattern que la couverture des
 * cours (`Course::cover`) — aucun second pipeline de stockage, un seul système
 * de gestion média pour tout le module.
 *
 * @property int                             $id
 * @property string                          $name
 * @property int|null                        $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class DiplomaBackground extends Model implements HasMedia
{
    use HasMediaAttachments;

    protected $table = 'academy_diploma_backgrounds';

    protected $fillable = ['name', 'created_by'];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Collection unique « background » : un seul fichier par arrière-plan,
     * restreint aux formats web sûrs. `singleFile()` remplace automatiquement
     * l'ancien fichier lors d'un nouveau téléversement (pas d'accumulation).
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('background')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    /**
     * URL de l'image d'arrière-plan (conversion optionnelle), ou null si aucune
     * image n'a été téléversée. Source de vérité = collection Spatie « background ».
     */
    public function imageUrl(string $conversion = ''): ?string
    {
        $media = $this->getFirstMedia('background');

        return $media ? $media->getUrl($conversion) : null;
    }

    /**
     * Filtre les arrière-plans appartenant à un utilisateur donné. Utilisé par
     * l'éditeur de gabarits pour lister owner-scopé ; un admin `academy.manage`
     * ignore ce scope (voit toute la bibliothèque, même modèle que DiplomaTemplate).
     */
    public function scopeOwnedBy(Builder $query, ?int $userId): Builder
    {
        return $query->where('created_by', $userId);
    }
}

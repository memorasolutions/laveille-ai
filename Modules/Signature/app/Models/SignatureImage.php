<?php

declare(strict_types=1);

namespace Modules\Signature\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Une image (logo, portrait ou bannière) d'une Signature. Le fichier ORIGINAL téléversé n'est
 * jamais persisté (M3.3) : seule la dérivée finale, produite et vérifiée par
 * Modules\Signature\Services\SignatureImagePipeline dans la MÊME requête, est conservée.
 */
class SignatureImage extends Model
{
    public const ROLE_LOGO = 'logo';

    public const ROLE_PORTRAIT = 'portrait';

    public const ROLE_BANNIERE = 'banniere';

    public const ROLES = [self::ROLE_LOGO, self::ROLE_PORTRAIT, self::ROLE_BANNIERE];

    protected $fillable = [
        'signature_id',
        'role',
        'public_id',
        'path',
        'width',
        'height',
        'display_width',
        'display_height',
        'purged_at',
    ];

    protected $casts = [
        'width' => 'integer',
        'height' => 'integer',
        'display_width' => 'integer',
        'display_height' => 'integer',
        'purged_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // B3 : public_id est généré UNE FOIS à la création, jamais laissé au hasard d'un appelant
        // qui oublierait de le poser - CSPRNG (Str::random() s'appuie sur random_bytes()), 32
        // caractères hex, non séquentiel par construction.
        static::creating(function (self $image): void {
            if (empty($image->public_id)) {
                $image->public_id = bin2hex(random_bytes(16));
            }
        });
    }

    public function signature(): BelongsTo
    {
        return $this->belongsTo(Signature::class);
    }

    public function isPurged(): bool
    {
        return $this->purged_at !== null;
    }

    /**
     * Représentation JSON-friendly réutilisée par tous les points d'écriture/lecture qui doivent
     * hydrater l'éditeur (assistant, gestion par jeton, « Mes signatures », export) - DRY, une
     * seule construction de l'URL publique plutôt qu'une par contrôleur. L'URL porte le public_id
     * (correctif B3), jamais l'id auto-incrémenté interne.
     *
     * @return array{role: string, url: string, width: int, height: int, display_width: int, display_height: int}|null
     */
    public function toAssetPayload(): ?array
    {
        if ($this->path === null) {
            return null;
        }

        return [
            'role' => $this->role,
            'url' => route('signature.asset', ['public_id' => $this->public_id, 'ext' => pathinfo($this->path, PATHINFO_EXTENSION)]),
            'width' => (int) $this->width,
            'height' => (int) $this->height,
            'display_width' => (int) ($this->display_width ?: $this->width),
            'display_height' => (int) ($this->display_height ?: $this->height),
        ];
    }
}

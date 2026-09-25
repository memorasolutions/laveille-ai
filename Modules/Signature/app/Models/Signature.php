<?php

declare(strict_types=1);

namespace Modules\Signature\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Signature\Database\Factories\SignatureFactory;

/**
 * Une signature HTML de courriel (visiteur anonyme via lien secret, ou membre via user_id).
 *
 * Patron du jeton d'administration haché calqué EXACTEMENT sur Modules\Decido\Models\Poll
 * (admin_token_hash, setAdminToken(), hash('sha256', ...) + hash_equals()) - la vérification du
 * jeton en clair se fait par comparaison de hash dans
 * Modules\Signature\Http\Controllers\Concerns\ResolvesSignatureByToken, jamais ici.
 *
 * @see \Modules\Decido\Models\Poll patron exact réutilisé (jeton haché, purge planifiée)
 */
class Signature extends Model
{
    use HasFactory;

    protected $table = 'signatures';

    public const TEMPLATES = ['minimal', 'professionnel', 'portrait', 'compact'];

    public const STATUS_ACTIVE = 'active';

    public const STATUS_PURGED = 'purged';

    protected $fillable = [
        'user_id',
        'admin_token_hash',
        'template',
        'content',
        'schema_version',
        'last_owner_activity_at',
        'expiry_warned_at',
        'purged_at',
        'status',
        'last_image_loaded_on',
        'reminder_email',
        'reminder_sent_at',
    ];

    protected $casts = [
        'content' => 'array',
        'schema_version' => 'integer',
        'last_owner_activity_at' => 'datetime',
        'expiry_warned_at' => 'datetime',
        'purged_at' => 'datetime',
        'last_image_loaded_on' => 'date',
        'reminder_sent_at' => 'datetime',
    ];

    protected $hidden = [
        'admin_token_hash',
    ];

    protected static function newFactory(): SignatureFactory
    {
        return SignatureFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function images(): HasMany
    {
        return $this->hasMany(SignatureImage::class);
    }

    public function image(string $role): ?SignatureImage
    {
        // Round DRY : $this->images est chargée UNE fois par requête (eager-load ->with('images'))
        // dans les contrôleurs qui en ont besoin plus d'une fois - éviter une requête répétée par
        // rôle demandé.
        if ($this->relationLoaded('images')) {
            return $this->images->firstWhere('role', $role);
        }

        return $this->images()->where('role', $role)->first();
    }

    public function setAdminToken(string $plainToken): void
    {
        $this->admin_token_hash = hash('sha256', $plainToken);
        $this->save();
    }

    /**
     * Une action du PROPRIÉTAIRE dans l'outil (ouverture de l'éditeur en écriture, modification,
     * copie, export, clic sur « Prolonger ») - JAMAIS un simple accès en lecture au lien secret, et
     * JAMAIS le chargement d'une image par un destinataire (section 6.1 du plan). Remet aussi
     * expiry_warned_at à NULL pour permettre un futur avertissement (patron Poll::extend()).
     */
    public function markOwnerActivity(): void
    {
        $this->forceFill([
            'last_owner_activity_at' => now(),
            'expiry_warned_at' => null,
        ])->save();
    }

    /**
     * Ancre temporelle de la purge (correctif B1) : l'activité du propriétaire si elle est connue,
     * sinon la date de CRÉATION de la ligne - jamais NULL en pratique (created_at porte un DEFAULT
     * CURRENT_TIMESTAMP, voir la migration), mais reste nullable en PHP pour ne jamais présumer
     * qu'une écriture future respectera cette garantie.
     */
    public function purgeAnchor(): ?\Illuminate\Support\Carbon
    {
        return $this->last_owner_activity_at ?? $this->created_at;
    }

    /**
     * Inactivité publique depuis $months mois (section 6.7-f) : AUCUNE activité du propriétaire
     * (ou, à défaut, aucune ancienneté suffisante depuis la création) ET AUCUN chargement d'image
     * depuis $months mois. Sans ancre connue, une colonne NULLE doit PROTÉGER, jamais condamner
     * (correctif B1 - l'inverse était vrai avant : deux colonnes NULLES rendaient la ligne éligible
     * à la purge immédiatement, y compris pour une signature créée la minute même). Utilisée à la
     * fois par l'avertissement (warning_months) et par la purge (retention_months) - la protection
     * MEMBRE (M1.4) n'est PAS ici, elle vit dans isEligibleForPurge() seulement : un membre reste
     * averti de son inactivité même si sa signature ne sera jamais purgée automatiquement.
     */
    public function isInactivePubliclyFor(int $months): bool
    {
        $anchor = $this->purgeAnchor();
        if ($anchor === null) {
            return false;
        }

        $threshold = now()->subMonths($months);

        $anchorRecent = $anchor->greaterThan($threshold);

        $imageRecentlyLoaded = $this->last_image_loaded_on !== null
            && $this->last_image_loaded_on->greaterThan($threshold->startOfDay());

        return ! $anchorRecent && ! $imageRecentlyLoaded;
    }

    /**
     * Condition de purge complète. Deux garde-fous AVANT même de regarder l'inactivité :
     *   - M1.4 : une signature de MEMBRE (user_id non nul) n'est JAMAIS purgée automatiquement tant
     *     que le compte existe - la contrainte nullOnDelete() de la migration GARANTIT qu'un
     *     user_id non nul pointe vers un compte qui existe encore, donc cette seule vérification
     *     suffit. Seul le membre peut supprimer sa propre signature (UserSignatureController::destroy()).
     *   - B1 : sans ancre temporelle connue (purgeAnchor() nul), jamais de purge - voir
     *     isInactivePubliclyFor().
     */
    public function isEligibleForPurge(int $months): bool
    {
        if ($this->user_id !== null) {
            return false;
        }

        return $this->isInactivePubliclyFor($months);
    }

    public function isPurged(): bool
    {
        return $this->status === self::STATUS_PURGED || $this->purged_at !== null;
    }

    /**
     * @return array<string, array{role: string, url: string, width: int, height: int, display_width: int, display_height: int}>
     */
    public function imagesPayload(): array
    {
        $images = $this->relationLoaded('images') ? $this->images : $this->images()->get();

        $payload = [];
        foreach ($images as $image) {
            $asset = $image->toAssetPayload();
            if ($asset !== null) {
                $payload[$image->role] = $asset;
            }
        }

        return $payload;
    }
}

<?php

declare(strict_types=1);

namespace Modules\Authors\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AuthorProfile extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'author_profiles';

    protected $fillable = [
        'user_id',
        'slug',
        'cover_image',
        'profile_image',
        'bio',
        'manifesto',
        'accent_color',
        'font_family',
        'tier',
        'tier_expires_at',
        'education_approved_at',
        'education_approved_by',
        'social_links',
        'qualifications',
        'modules_visible',
        'last_published_at',
        'archived_at',
    ];

    protected $casts = [
        'social_links' => 'array',
        'qualifications' => 'array',
        'modules_visible' => 'array',
        'tier_expires_at' => 'datetime',
        'education_approved_at' => 'datetime',
        'last_published_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    public const TOGGLABLE_MODULES = [
        'now' => '🔥 En ce moment (statut récent + activité)',
        'newsletter' => '📬 Bloc newsletter',
        'stats' => '📊 Statistiques (publications, qualifications)',
        'about' => '👤 Carte À propos (bio + manifeste)',
        'featured' => '⭐ Article featured (À lire d\'abord)',
        'recent_articles' => '📝 Section publications récentes',
        'social_links' => '🔗 Liens sociaux dans le profil',
        'qualifications_list' => '🎓 Liste des qualifications',
    ];

    public const MODULES_DEFAULTS = [
        'now' => true,
        'newsletter' => true,
        'stats' => true,
        'about' => true,
        'featured' => true,
        'recent_articles' => true,
        'social_links' => true,
        'qualifications_list' => true,
    ];

    public function isModuleVisible(string $key): bool
    {
        $config = $this->modules_visible ?? [];
        return (bool) ($config[$key] ?? (self::MODULES_DEFAULTS[$key] ?? true));
    }

    /**
     * Nom d'affichage de l'auteur. `display_name` n'est pas une colonne :
     * on dérive du nom utilisateur, sinon du slug. Accessor pour cohérence
     * avec tous les composants mini-site (S119-S122).
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->user?->name ?? $this->slug;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function curationItems(): HasMany
    {
        return $this->hasMany(CurationItem::class);
    }

    public function imageVariants(): HasMany
    {
        return $this->hasMany(ImageVariant::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(AuthorActivityLog::class);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('archived_at');
    }

    public function scopeFree($query)
    {
        return $query->where('tier', 'free');
    }

    public function scopePremium($query)
    {
        return $query->whereIn('tier', ['premium', 'premium_manual']);
    }

    public function isPremium(): bool
    {
        return in_array($this->tier, ['premium', 'premium_manual'], true);
    }

    public function isEducation(): bool
    {
        return $this->tier === 'education' && $this->education_approved_at !== null;
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public function daysSinceLastPublish(): int
    {
        if ($this->last_published_at === null) {
            return 0;
        }

        return (int) $this->last_published_at->diffInDays(now());
    }
}

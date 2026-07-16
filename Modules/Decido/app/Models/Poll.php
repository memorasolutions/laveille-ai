<?php

declare(strict_types=1);

namespace Modules\Decido\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;
use Modules\Decido\Database\Factories\PollFactory;
use Modules\Decido\Enums\PollStatus;
use Modules\Decido\Enums\PollType;
use Modules\Decido\Enums\VoteMode;

class Poll extends Model
{
    use HasFactory;

    protected $table = 'decido_polls';

    protected $fillable = [
        'creator_id',
        'title',
        'description',
        'type',
        'vote_mode',
        'timezone',
        'status',
        'duration_minutes',
        'range_start_time',
        'range_end_time',
        'step_minutes',
        'final_option_id',
        'expires_at',
        'admin_token_hash',
        'custom_slug',
        'short_url_id',
    ];

    protected $casts = [
        'type' => PollType::class,
        'vote_mode' => VoteMode::class,
        'status' => PollStatus::class,
        'expires_at' => 'datetime',
    ];

    protected $hidden = [
        'admin_token_hash',
    ];

    public const RESERVED_SLUGS = [
        'index', 'admin', 'api', 'nouveau', 'creer', 'create', 'decido',
        'vote', 'resultats', 'admin-token', 'login', 'logout',
    ];

    public function options(): HasMany
    {
        return $this->hasMany(PollOption::class, 'poll_id')->orderBy('sort_order');
    }

    public function votes(): HasManyThrough
    {
        return $this->hasManyThrough(PollVote::class, PollOption::class, 'poll_id', 'option_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function finalOption(): BelongsTo
    {
        return $this->belongsTo(PollOption::class, 'final_option_id');
    }

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->public_id)) {
                do {
                    $id = Str::random(12);
                } while (static::where('public_id', $id)->exists());

                $model->public_id = $id;
            }
        });
    }

    protected function customSlug(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value
                ? trim(preg_replace('/-{2,}/', '-', preg_replace('/[^a-z0-9-]/', '', strtolower(Str::ascii(str_replace(' ', '-', $value))))), '-') ?: null
                : null,
        );
    }

    public function getShareSlugAttribute(): string
    {
        return $this->custom_slug ?: $this->public_id;
    }

    public function getShareUrlAttribute(): string
    {
        return url('/decido/'.$this->share_slug);
    }

    public static function findByShareIdentifier(string $identifier): ?self
    {
        return static::where('custom_slug', $identifier)
            ->orWhere('public_id', $identifier)
            ->first();
    }

    public function shortUrl(): ?BelongsTo
    {
        // Round 8 (skill /100) : class_exists() seul ne protège PAS contre un module désactivé
        // via modules_statuses.json - nwidart-laravel-modules garde les classes dans le mapping
        // PSR-4 Composer même module désactivé (seul le boot du ServiceProvider est coupé), donc
        // class_exists() reste vrai et un lien court/QR "fantôme" (routes ShortUrl non
        // enregistrées → 404 réel) pouvait être créé sans erreur ni avertissement. ModuleChecker
        // vérifie en plus Module::has()/isEnabled(), le vrai état d'activation de la plateforme.
        if (! \Modules\Core\Services\ModuleChecker::isAvailable('ShortUrl')) {
            return null;
        }

        return $this->belongsTo(\Modules\ShortUrl\Models\ShortUrl::class, 'short_url_id');
    }

    public function getShortUrlString(): ?string
    {
        if (! $this->short_url_id || ! \Modules\Core\Services\ModuleChecker::isAvailable('ShortUrl')) {
            return null;
        }

        // Round 7 (skill /100) : ::find() brut ré-interrogeait la DB à chaque appel - la vue
        // results.blade.php appelle cette méthode 3 fois par chargement de page (6 requêtes
        // redondantes observées via query log réel). $this->shortUrl passe par la relation
        // Eloquent, mise en cache après le premier accès.
        return $this->shortUrl?->getShortUrl();
    }

    public function setAdminToken(string $plainToken): void
    {
        $this->admin_token_hash = hash('sha256', $plainToken);
        $this->save();
    }

    public function verifyAdminToken(string $plainToken): bool
    {
        return hash_equals($this->admin_token_hash, hash('sha256', $plainToken));
    }

    protected static function newFactory(): PollFactory
    {
        return PollFactory::new();
    }
}

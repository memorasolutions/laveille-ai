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
use Illuminate\Support\Facades\DB;
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
        'response_deadline_at',
        'expiry_warned_at',
        'extension_count',
        'expected_participants',
        'admin_token_hash',
        'custom_slug',
        'short_url_id',
    ];

    protected $casts = [
        'type' => PollType::class,
        'vote_mode' => VoteMode::class,
        'status' => PollStatus::class,
        'expires_at' => 'datetime',
        'response_deadline_at' => 'datetime',
        'expiry_warned_at' => 'datetime',
        'extension_count' => 'integer',
        'expected_participants' => 'integer',
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

    /**
     * Nombre de votants DISTINCTS (par voter_token) pour ce sondage - pas le nombre de lignes de
     * vote (un même votant a une ligne par option en mode yes_no_maybe). Sert au suivi "X sur Y
     * réponses reçues" (LOT 3, suivi des non-répondants sans carnet d'adresses).
     */
    public function responseCount(): int
    {
        if ($this->relationLoaded('options') && $this->options->every(fn (PollOption $option) => $option->relationLoaded('votes'))) {
            return $this->options->flatMap(fn (PollOption $option) => $option->votes)
                ->unique('voter_token')
                ->count();
        }

        return PollVote::whereIn('option_id', $this->options()->pluck('id'))
            ->distinct('voter_token')
            ->count('voter_token');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function finalOption(): BelongsTo
    {
        return $this->belongsTo(PollOption::class, 'final_option_id');
    }

    /**
     * LOT 1 : votants ayant déclaré qu'aucune option ne leur convenait - voir PollDecline.
     */
    public function declines(): HasMany
    {
        return $this->hasMany(PollDecline::class, 'poll_id');
    }

    /**
     * LOT 2 (docs/specs/2026-08-16-decido-reste-a-faire.md, point 5) : commentaires libres
     * facultatifs, un par participant - voir PollComment.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(PollComment::class, 'poll_id');
    }

    /**
     * starts_at/expires_at/response_deadline_at sont stockés en UTC brut, mais
     * config('app.timezone') = America/Toronto fait que le cast Eloquent datetime réinterprète à
     * tort la valeur comme déjà en heure de Québec sans conversion (même piège que
     * PollExportService::exportIcs() et vote.blade.php) - reparser explicitement comme UTC avant
     * de convertir vers le fuseau du sondage.
     */
    public function responseDeadlineInPollTimezone(): ?\Carbon\CarbonInterface
    {
        if (! $this->response_deadline_at) {
            return null;
        }

        return \Carbon\Carbon::parse($this->response_deadline_at->format('Y-m-d H:i:s'), 'UTC')
            ->timezone($this->timezone);
    }

    public function isResponseDeadlinePassed(): bool
    {
        if (! $this->response_deadline_at) {
            return false;
        }

        return \Carbon\Carbon::parse($this->response_deadline_at->format('Y-m-d H:i:s'), 'UTC')->isPast();
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

    /**
     * Crée (si nécessaire) et associe un lien court au sondage, à l'abri d'une double création
     * sous requêtes concurrentes.
     *
     * Round 15 (skill /100) : PollManageController::createShortLink() vérifiait
     * `$pollModel->short_url_id` sur l'instance déjà chargée en début de méthode, PUIS créait le
     * ShortUrl, PUIS écrivait - sans transaction ni verrou, contrairement au pattern déjà en place
     * pour le vote/la clôture (PublicPollController::vote(), lockForUpdate). Deux requêtes quasi
     * simultanées (ex. double-clic sur "Créer un lien court") pouvaient toutes deux lire
     * short_url_id=NULL avant que l'une ou l'autre n'ait écrit, créant CHACUNE un ShortUrl distinct
     * - la seconde écriture écrasait silencieusement la première, orphelinant un ShortUrl jamais
     * référencé ni nettoyé (aucune contrainte unique sur decido_polls.short_url_id). Le correctif
     * relit l'état à l'intérieur d'une transaction verrouillée (lockForUpdate) au lieu de faire
     * confiance à l'instance $this, potentiellement périmée : sous MySQL en production, la seconde
     * requête concurrente attend que la première commette avant de relire, et retrouve alors
     * short_url_id déjà posé - elle n'appelle donc jamais ShortUrlService::createShortUrl() une
     * seconde fois.
     */
    public function claimShortUrl(int $userId, \Modules\ShortUrl\Services\ShortUrlService $service, ?string $customSlug = null): ?\Modules\ShortUrl\Models\ShortUrl
    {
        return DB::transaction(function () use ($userId, $service, $customSlug) {
            $locked = static::whereKey($this->id)->lockForUpdate()->first();

            if (! $locked) {
                return null;
            }

            if ($locked->short_url_id) {
                // Une requête concurrente a déjà créé le lien court entre-temps : on renvoie le
                // sien plutôt que d'en créer un second.
                return $locked->shortUrl;
            }

            $data = [
                'original_url' => $locked->share_url,
                'title' => 'Sondage Décido : '.$locked->title,
                'redirect_type' => 301,
                'is_active' => true,
            ];
            if ($customSlug !== null && $customSlug !== '') {
                $data['slug'] = $customSlug;
            }

            $shortUrl = $service->createShortUrl($data, $userId);

            $locked->update(['short_url_id' => $shortUrl->id]);
            $this->short_url_id = $shortUrl->id;

            return $shortUrl;
        });
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

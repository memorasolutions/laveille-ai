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
        if (! class_exists(\Modules\ShortUrl\Models\ShortUrl::class)) {
            return null;
        }

        return $this->belongsTo(\Modules\ShortUrl\Models\ShortUrl::class, 'short_url_id');
    }

    public function getShortUrlString(): ?string
    {
        if (! $this->short_url_id || ! class_exists(\Modules\ShortUrl\Models\ShortUrl::class)) {
            return null;
        }

        $shortUrl = \Modules\ShortUrl\Models\ShortUrl::find($this->short_url_id);

        return $shortUrl?->getShortUrl();
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

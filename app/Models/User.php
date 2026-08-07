<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Scout\Searchable;
use Modules\Academy\Traits\HasSubscriptionTier;
use Modules\Auth\Models\LoginAttempt;
use Modules\Auth\Observers\UserObserver;
use Modules\Core\Contracts\UserInterface;
use Modules\Team\Traits\HasTeams;
use NotificationChannels\WebPush\HasPushSubscriptions;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\LaravelPasskeys\Models\Concerns\HasPasskeys;
use Spatie\LaravelPasskeys\Models\Concerns\InteractsWithPasskeys;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;

#[ObservedBy(UserObserver::class)]
class User extends Authenticatable implements HasMedia, HasPasskeys, MustVerifyEmail, UserInterface
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use Billable, HasApiTokens, HasFactory, HasPushSubscriptions, HasRoles, HasSubscriptionTier, HasTeams, InteractsWithMedia, InteractsWithPasskeys, LogsActivity, Notifiable, Searchable;

    // Community module trait — guard class_exists for portability
    use \Modules\Community\Traits\HasCategorySubscriptions;

    /**
     * @return array<string>
     */
    public function toSearchableArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
        ];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email'])
            ->logOnlyDirty();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'social_provider',
        'social_id',
        'avatar',
        'bio',
        'is_active',
        'phone',
        'phone_verified_at',
        'shipping_address',
        'must_change_password',
        'failed_login_count',
        'locked_until',
        'onboarding_step',
        'onboarding_completed_at',
        'notification_frequency',
        'password_changed_at',
        'social_links',
        'tool_preferences',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'is_active' => 'boolean',
            'phone_verified_at' => 'datetime',
            'shipping_address' => 'array',
            'tool_preferences' => 'array',
            'must_change_password' => 'boolean',
            'failed_login_count' => 'integer',
            'locked_until' => 'datetime',
            'onboarding_step' => 'integer',
            'onboarding_completed_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'social_links' => 'array',
        ];
    }

    protected function avatarUrl(): Attribute
    {
        return Attribute::get(fn () => $this->avatar
            ? asset('storage/'.$this->avatar)
            : $this->defaultAvatarDataUri()
        );
    }

    private function defaultAvatarDataUri(): string
    {
        $initials = '?';
        $name = trim((string) $this->name);

        if ($name !== '') {
            $words = preg_split('/\s+/', $name);
            $initials = mb_strtoupper(mb_substr($words[0] ?? '', 0, 1).mb_substr($words[1] ?? '', 0, 1));
        }

        $initials = htmlspecialchars($initials, ENT_QUOTES);
        $palette = ['#064E5A', '#0B5D46', '#5A2A64', '#7A3B10', '#1F3A5F', '#5C1F33'];
        $color = $palette[abs(crc32((string) $this->email)) % count($palette)];

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="150" height="150" viewBox="0 0 150 150">'
            .'<circle cx="75" cy="75" r="75" fill="'.$color.'"/>'
            .'<text x="75" y="78" text-anchor="middle" dominant-baseline="central" font-family="sans-serif" font-size="60" font-weight="600" fill="#FFFFFF">'.$initials.'</text>'
            .'</svg>';

        return 'data:image/svg+xml;utf8,'.rawurlencode($svg);
    }

    public function hasEnabledTwoFactor(): bool
    {
        return ! is_null($this->two_factor_confirmed_at);
    }

    public function hasTwoFactorSecret(): bool
    {
        return ! is_null($this->two_factor_secret);
    }

    public function isLocked(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }

    public function roleRequiresPassword(): bool
    {
        return $this->roles->every(fn ($role) => (bool) $role->requires_password);
    }

    public function needsOnboarding(): bool
    {
        return $this->onboarding_completed_at === null;
    }

    public function hasCompletedOnboarding(): bool
    {
        return $this->onboarding_completed_at !== null;
    }

    public function isSuperAdmin(): bool
    {
        return $this->email === config('app.superadmin_email') && $this->hasRole('super_admin');
    }

    /**
     * Route d'accueil post-connexion selon le rôle.
     * Évite d'envoyer un non-admin vers /admin (403). Source unique pour toutes les redirections de login.
     */
    public function homeRoute(): string
    {
        return $this->hasRole(['admin', 'super_admin'])
            ? route('admin.dashboard')
            : route('user.dashboard');
    }

    /**
     * Historique des connexions de l'utilisateur.
     */
    public function loginAttempts(): HasMany
    {
        return $this->hasMany(LoginAttempt::class);
    }

    protected static function booted(): void
    {
        static::deleting(function (User $user): bool {
            if ($user->email === config('app.superadmin_email')) {
                return false;
            }

            return true;
        });
    }

    /**
     * Get user's reputation level info (emoji + name).
     */
    public function getLevelBadge(): string
    {
        if (class_exists(\Modules\Directory\Services\ReputationService::class)) {
            $info = \Modules\Directory\Services\ReputationService::getLevelInfo($this->trust_level ?? 0);

            return $info['emoji'].' '.$info['name'];
        }

        return '';
    }
}

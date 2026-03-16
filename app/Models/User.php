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
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Scout\Searchable;
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
    use Billable, HasApiTokens, HasFactory, HasPushSubscriptions, HasRoles, HasTeams, InteractsWithMedia, InteractsWithPasskeys, LogsActivity, Notifiable, Searchable;

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
        'must_change_password',
        'failed_login_count',
        'locked_until',
        'onboarding_step',
        'onboarding_completed_at',
        'notification_frequency',
        'password_changed_at',
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
            'must_change_password' => 'boolean',
            'failed_login_count' => 'integer',
            'locked_until' => 'datetime',
            'onboarding_step' => 'integer',
            'onboarding_completed_at' => 'datetime',
            'password_changed_at' => 'datetime',
        ];
    }

    protected function avatarUrl(): Attribute
    {
        return Attribute::get(fn () => $this->avatar
            ? asset('storage/'.$this->avatar)
            : 'https://www.gravatar.com/avatar/'.md5(strtolower(trim($this->email))).'?d=mp&s=150'
        );
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

    protected static function booted(): void
    {
        static::deleting(function (User $user): bool {
            if ($user->email === config('app.superadmin_email')) {
                return false;
            }

            return true;
        });
    }
}

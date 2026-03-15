<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\Privacy\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Modules\Privacy\Database\Factories\UserConsentFactory;

class UserConsent extends Model
{
    use HasFactory;

    protected $table = 'user_consents';

    protected $fillable = [
        'consent_token',
        'ip_hash',
        'user_agent',
        'choices',
        'jurisdiction',
        'policy_version',
        'region_detected',
        'gpc_enabled',
        'expires_at',
    ];

    protected $casts = [
        'choices' => 'array',
        'gpc_enabled' => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now());
    }

    public function scopeByJurisdiction($query, $jurisdiction)
    {
        return $query->where('jurisdiction', $jurisdiction);
    }

    public static function generateToken(): string
    {
        return Str::random(64);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->consent_token)) {
                $model->consent_token = self::generateToken();
            }
        });
    }

    protected static function newFactory(): UserConsentFactory
    {
        return UserConsentFactory::new();
    }
}

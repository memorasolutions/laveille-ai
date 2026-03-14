<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Model;

class BlockedIp extends Model
{
    protected $fillable = [
        'ip_address',
        'reason',
        'blocked_until',
        'auto_blocked',
    ];

    protected function casts(): array
    {
        return [
            'blocked_until' => 'datetime',
            'auto_blocked' => 'boolean',
        ];
    }

    public function isActive(): bool
    {
        if ($this->blocked_until === null) {
            return true;
        }

        return $this->blocked_until->isFuture();
    }

    public static function isBlocked(string $ip): bool
    {
        return self::where('ip_address', $ip)
            ->where(function ($query) {
                $query->whereNull('blocked_until')
                    ->orWhere('blocked_until', '>', now());
            })
            ->exists();
    }
}

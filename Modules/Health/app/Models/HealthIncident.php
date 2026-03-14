<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Health\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class HealthIncident extends Model
{
    public const STATUS_INVESTIGATING = 'investigating';

    public const STATUS_IDENTIFIED = 'identified';

    public const STATUS_MONITORING = 'monitoring';

    public const STATUS_RESOLVED = 'resolved';

    public const SEVERITY_CRITICAL = 'critical';

    public const SEVERITY_MAJOR = 'major';

    public const SEVERITY_MINOR = 'minor';

    protected $table = 'health_incidents';

    protected $fillable = [
        'title',
        'description',
        'status',
        'severity',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->where('created_at', '>=', now()->subDays(90));
    }

    public function scopeUnresolved(Builder $query): Builder
    {
        return $query->where('status', '!=', self::STATUS_RESOLVED);
    }

    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }
}

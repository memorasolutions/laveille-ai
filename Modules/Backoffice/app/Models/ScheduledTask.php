<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ScheduledTask extends Model
{
    protected $table = 'scheduled_tasks';

    /** @var list<string> */
    protected $fillable = [
        'command',
        'cron_expression',
        'description',
        'is_system',
        'is_active',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_system' => 'boolean',
        'is_active' => 'boolean',
        'last_run_at' => 'datetime',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeCustom(Builder $query): Builder
    {
        return $query->where('is_system', false);
    }

    public function isEditable(): bool
    {
        return ! $this->is_system;
    }

    public function markAsRun(): bool
    {
        return $this->update(['last_run_at' => now()]);
    }
}

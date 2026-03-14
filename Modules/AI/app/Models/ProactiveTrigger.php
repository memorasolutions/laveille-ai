<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\AI\Database\Factories\ProactiveTriggerFactory;

class ProactiveTrigger extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'event_type',
        'conditions',
        'message',
        'is_active',
        'delay_seconds',
    ];

    protected $casts = [
        'conditions' => 'array',
        'is_active' => 'boolean',
        'delay_seconds' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForEvent($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    public function matchesContext(array $context): bool
    {
        if (empty($this->conditions)) {
            return true;
        }

        foreach ($this->conditions as $key => $value) {
            if (! isset($context[$key]) || $context[$key] !== $value) {
                return false;
            }
        }

        return true;
    }

    protected static function newFactory(): ProactiveTriggerFactory
    {
        return ProactiveTriggerFactory::new();
    }
}

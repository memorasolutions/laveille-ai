<?php

declare(strict_types=1);

namespace Modules\Core\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasLifecycleStatus
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_BETA = 'beta';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_ACQUIRED = 'acquired';
    public const STATUS_RENAMED = 'renamed';
    public const STATUS_PIVOTED = 'pivoted';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_SCAM = 'scam';

    public static function lifecycleStatuses(): array
    {
        return [
            self::STATUS_ACTIVE => [
                'label' => 'Actif',
                'color' => '#10b981',
                'icon' => 'fa-circle-check',
                'severity' => 1,
            ],
            self::STATUS_BETA => [
                'label' => 'Bêta',
                'color' => '#6366f1',
                'icon' => 'fa-flask',
                'severity' => 2,
            ],
            self::STATUS_PAUSED => [
                'label' => 'En pause',
                'color' => '#f59e0b',
                'icon' => 'fa-pause-circle',
                'severity' => 3,
            ],
            self::STATUS_RENAMED => [
                'label' => 'Renommé',
                'color' => '#0ea5e9',
                'icon' => 'fa-tag',
                'severity' => 2,
            ],
            self::STATUS_PIVOTED => [
                'label' => 'Pivoté',
                'color' => '#8b5cf6',
                'icon' => 'fa-shuffle',
                'severity' => 3,
            ],
            self::STATUS_ACQUIRED => [
                'label' => 'Acquis',
                'color' => '#3b82f6',
                'icon' => 'fa-handshake',
                'severity' => 2,
            ],
            self::STATUS_CLOSED => [
                'label' => 'Plus en ligne',
                'color' => '#52586a',
                'icon' => 'fa-circle-xmark',
                'severity' => 4,
            ],
            self::STATUS_SCAM => [
                'label' => 'Arnaque',
                'color' => '#dc2626',
                'icon' => 'fa-triangle-exclamation',
                'severity' => 5,
            ],
        ];
    }

    public function getLifecycleLabelAttribute(): string
    {
        return static::lifecycleStatuses()[$this->lifecycle_status]['label'] ?? $this->lifecycle_status;
    }

    public function getLifecycleColorAttribute(): string
    {
        return static::lifecycleStatuses()[$this->lifecycle_status]['color'] ?? '#6b7280';
    }

    public function getLifecycleIconAttribute(): string
    {
        return static::lifecycleStatuses()[$this->lifecycle_status]['icon'] ?? 'fa-circle-question';
    }

    public function getLifecycleSeverityAttribute(): int
    {
        return static::lifecycleStatuses()[$this->lifecycle_status]['severity'] ?? 1;
    }

    public function getIsLifecycleActiveAttribute(): bool
    {
        return in_array($this->lifecycle_status, [
            self::STATUS_ACTIVE,
            self::STATUS_BETA,
        ], true);
    }

    public function getIsLifecycleDownAttribute(): bool
    {
        return in_array($this->lifecycle_status, [
            self::STATUS_CLOSED,
            self::STATUS_SCAM,
        ], true);
    }

    public function getLifecycleBannerMessageAttribute(): string
    {
        return match ($this->lifecycle_status) {
            self::STATUS_CLOSED => 'Cette plateforme a fermé ses portes',
            self::STATUS_SCAM => 'Site signalé comme arnaque — évitez-le',
            default => 'Statut : ' . $this->lifecycle_label,
        };
    }

    public function scopeLifecycle(Builder $query, string $status): Builder
    {
        return $query->where('lifecycle_status', $status);
    }

    public function scopeNotActiveLifecycle(Builder $query): Builder
    {
        return $query->whereNotIn('lifecycle_status', [
            self::STATUS_ACTIVE,
            self::STATUS_BETA,
        ]);
    }

    public function hasReplacement(): bool
    {
        return ! empty($this->lifecycle_replacement_url) || ! empty($this->lifecycle_replacement_tool_id);
    }
}

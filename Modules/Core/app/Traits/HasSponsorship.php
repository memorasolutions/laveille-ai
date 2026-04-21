<?php

declare(strict_types=1);

namespace Modules\Core\Traits;

trait HasSponsorship
{
    public static function initializeHasSponsorship(): void
    {
        static::$casts = array_merge([
            'is_featured' => 'boolean',
            'featured_until' => 'datetime',
            'featured_order' => 'integer',
        ], static::$casts ?? []);
    }

    /** Vérifie si l'entité est actuellement sponsorisée. */
    public function isSponsored(): bool
    {
        return $this->is_featured && ($this->featured_until === null || $this->featured_until->isFuture());
    }

    /** Scope pour les entités activement sponsorisées. */
    public function scopeActivelyFeatured($query)
    {
        return $query->where('is_featured', true)
            ->where(function ($q) {
                $q->whereNull('featured_until')
                  ->orWhere('featured_until', '>', now());
            });
    }

    /** Active le sponsoring pour un nombre donné de jours. */
    public function activateSponsorship(int $days = 30): void
    {
        $this->is_featured = true;
        $this->featured_until = now()->addDays($days);
        $this->featured_order = (static::max('featured_order') ?? 0) + 1;
        $this->saveQuietly();
    }

    /** Désactive le sponsoring. */
    public function deactivateSponsorship(): void
    {
        $this->is_featured = false;
        $this->featured_until = null;
        $this->featured_order = 0;
        $this->saveQuietly();
    }

    /** Scope pour les sponsorings expirés. */
    public function scopeExpiredSponsorship($query)
    {
        return $query->where('is_featured', true)
            ->whereNotNull('featured_until')
            ->where('featured_until', '<=', now());
    }
}

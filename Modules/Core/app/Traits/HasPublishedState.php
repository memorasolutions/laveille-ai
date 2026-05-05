<?php

declare(strict_types=1);

namespace Modules\Core\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Mutualisation #143 (audit Phase 2) : pattern `is_published` boolean partagé par 8+ models.
 *
 * Avant : 8 implémentations distinctes de scopePublished sur is_published.
 * Après : `use HasPublishedState;` dans le model + scope auto.
 *
 * Couvre uniquement le pattern boolean simple. Pour les patterns `status='published'`,
 * state machines ou schedules complexes, utiliser HasScheduledPublishing ou implémenter localement.
 */
trait HasPublishedState
{
    /**
     * Scope les rows publiées (is_published = true).
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope inverse : rows brouillons / non publiées.
     */
    public function scopeUnpublished(Builder $query): Builder
    {
        return $query->where('is_published', false)->orWhereNull('is_published');
    }

    /**
     * Helper accesseur : la row est-elle publiée ?
     */
    public function isPublished(): bool
    {
        return (bool) ($this->is_published ?? false);
    }
}

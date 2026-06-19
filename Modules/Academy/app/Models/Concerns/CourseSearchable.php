<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Academy\Models\Concerns;

use Carbon\Carbon;

/**
 * Trait CourseSearchable — fournit toSearchableArray(), shouldBeSearchable() et searchableAs()
 * pour le modèle Course. Le trait Laravel\Scout\Searchable est ajouté directement sur le modèle
 * Course de façon défensive (via class_exists). Ce trait ne fait qu'implémenter les 3 méthodes
 * de données, sans dépendre de Scout.
 */
trait CourseSearchable
{
    /**
     * Tableau de données indexables pour Scout/Meilisearch.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id'               => $this->id,
            'title'            => $this->title ?? '',
            'summary'          => $this->summary ?? '',
            'level'            => $this->level ?? '',
            'slug'             => $this->slug ?? '',
            'language'         => $this->language ?? '',
            'access_type'      => $this->access_type ?? '',
            'duration_minutes' => $this->duration_minutes ?? 0,
            'tags'             => $this->faq_dictionary_ids ?? [],
            'indexed_at'       => Carbon::now()->toDateTimeString(),
        ];
    }

    /**
     * Ne doit être indexé que si le cours est publié ET public.
     */
    public function shouldBeSearchable(): bool
    {
        return $this->status === 'published' && $this->visibility === 'public';
    }

    /**
     * Nom de l'index Scout.
     */
    public function searchableAs(): string
    {
        return 'academy_courses';
    }
}

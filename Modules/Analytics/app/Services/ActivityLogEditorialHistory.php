<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Analytics\Services;

use Carbon\CarbonInterface;
use Modules\Analytics\Contracts\EditorialHistoryReader;
use Spatie\Activitylog\Models\Activity;

/**
 * Registre éditorial lu dans le journal d'activité (Spatie), en lecture seule.
 *
 * LACUNE CONNUE, volontairement non corrigée (hors périmètre, elle toucherait le module Blog) :
 * Modules\Blog\Models\Article ne journalise PAS is_published (il utilise un état de publication),
 * donc le blogue est absent de ce registre. News (NewsArticle) et Dictionary (Term) sont couverts.
 *
 * Règle de lecture : une activité compte seulement si ses propriétés portent is_published dans
 * "attributes". Une création à is_published faux n'est pas une dépublication : elle est ignorée.
 */
class ActivityLogEditorialHistory implements EditorialHistoryReader
{
    public function transitions(?CarbonInterface $from = null, ?CarbonInterface $to = null): array
    {
        // ACTION: lire les activités dont les propriétés touchent is_published
        // SELF: requête Eloquent de lecture seule, moins de 5 lignes utiles
        // RAISON: aucune écriture, aucun effet de bord sur le journal
        $query = Activity::query()
            ->whereNotNull('properties->attributes->is_published')
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('created_at', '<=', $to))
            ->orderBy('created_at')
            ->orderBy('id');

        $transitions = [];

        foreach ($query->lazyById() as $activity) {
            $transition = $this->toTransition($activity);
            if ($transition !== null) {
                $transitions[] = $transition;
            }
        }

        return $transitions;
    }

    /** @return array<string, mixed>|null */
    private function toTransition(Activity $activity): ?array
    {
        $attributes = $activity->properties['attributes'] ?? null;
        if (! is_array($attributes) || ! array_key_exists('is_published', $attributes)) {
            return null;
        }

        $published = filter_var($attributes['is_published'], FILTER_VALIDATE_BOOLEAN);

        if ($activity->event === 'created' && ! $published) {
            return null;
        }

        $subject = $this->subjectOf($activity);

        return [
            'occurred_at' => $activity->created_at,
            'event' => $published ? 'published' : 'unpublished',
            'subject_type' => (string) $activity->subject_type,
            'subject_id' => $activity->subject_id,
            'url' => $this->urlOf($subject),
            'title' => $this->titleOf($subject),
        ];
    }

    private function subjectOf(Activity $activity): ?object
    {
        try {
            return $activity->subject;
        } catch (\Throwable) {
            // Classe renommée ou retirée depuis l'écriture de l'activité : la transition reste lue.
            return null;
        }
    }

    private function urlOf(?object $subject): ?string
    {
        if ($subject === null || ! method_exists($subject, 'getPublicUrl')) {
            return null;
        }

        try {
            return (string) $subject->getPublicUrl();
        } catch (\Throwable) {
            // Un sujet sans slug exploitable ne doit jamais faire tomber la lecture de l'historique.
            return null;
        }
    }

    private function titleOf(?object $subject): ?string
    {
        if ($subject === null) {
            return null;
        }

        foreach (['title', 'name'] as $field) {
            $value = $subject->{$field} ?? null;
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }
}

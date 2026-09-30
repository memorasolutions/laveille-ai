<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Analytics\Contracts;

use Carbon\CarbonInterface;

/**
 * Lecture seule de l'historique éditorial (publications et dépublications de contenus).
 *
 * Derrière une interface : la source actuelle est le journal d'activité, mais le jalon
 * d'analyse ne doit dépendre que de ce contrat si la source change un jour.
 */
interface EditorialHistoryReader
{
    /**
     * Transitions de publication, de la plus ancienne à la plus récente.
     *
     * @return list<array{occurred_at: CarbonInterface, event: 'published'|'unpublished', subject_type: string, subject_id: int|string, url: ?string, title: ?string}>
     */
    public function transitions(?CarbonInterface $from = null, ?CarbonInterface $to = null): array;
}

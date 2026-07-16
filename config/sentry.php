<?php

declare(strict_types=1);

use Modules\Core\Services\SentryUrlScrubber;
use Sentry\Event;
use Sentry\EventHint;

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Config minimale : fusionnée par le package (mergeConfigFrom) avec les valeurs par défaut de
 * vendor/sentry/sentry-laravel/config/sentry.php - seule la clé définie ici surcharge le défaut
 * (dsn, breadcrumbs, traces_sample_rate, etc. restent pilotés par .env comme avant, AUCUN autre
 * comportement Sentry n'est modifié).
 *
 * Round 13 (skill /100 - module Decido) : voir Modules\Core\Services\SentryUrlScrubber pour le
 * détail de la fuite de jeton corrigée (URL complète, jetons dans le chemin, capturée par
 * Sentry même avec send_default_pii=false).
 */
return [
    'before_send' => static function (Event $event, ?EventHint $hint): ?Event {
        return SentryUrlScrubber::scrubEvent($event);
    },
];

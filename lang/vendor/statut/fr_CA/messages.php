<?php

declare(strict_types=1);

// MEMORA solutions — traductions FR du package Statut.

return [
    'page_title'             => 'État des services',
    'page_subtitle'          => 'Disponibilité en temps réel de la plateforme',

    'all_operational'        => 'Tous les systèmes sont opérationnels',
    'incident_in_progress'   => 'Un incident est en cours',
    'maintenance'            => 'Maintenance en cours',

    'services'               => 'Services surveillés',
    'active_incidents'       => 'Incidents en cours',
    'no_active_incidents'    => 'Aucun incident en cours. Tout va bien.',
    'no_monitors'            => 'Aucun moniteur configuré.',

    'operational'            => 'En ligne',
    'down'                   => 'Hors ligne',
    'paused'                 => 'En pause',
    'unknown'                => 'Inconnu',

    'na'                     => 'n/d',
    'never_checked'          => 'Jamais vérifié',
    'uptime_periods'         => 'Disponibilité par période',
    'started_at_label'       => 'Commencé',
    'cause_label'            => 'Cause :',
    'refreshed_every_60s'    => 'Actualisé toutes les 60 secondes',
    'provider_unavailable'   => 'La plateforme de surveillance est temporairement indisponible. Réessayez dans quelques minutes.',

    'counts' => [
        'total'     => '{0} aucun moniteur|{1} :count moniteur au total|[2,*] :count moniteurs au total',
        'down'      => '{1} :count en panne|[2,*] :count en panne',
        'paused'    => '{1} :count en pause|[2,*] :count en pause',
        'incidents' => '{1} :count incident actif|[2,*] :count incidents actifs',
    ],
];

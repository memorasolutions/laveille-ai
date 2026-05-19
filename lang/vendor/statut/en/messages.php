<?php

declare(strict_types=1);

// MEMORA solutions — EN translations for the Statut package.

return [
    'page_title'             => 'Service status',
    'page_subtitle'          => 'Real-time availability of the platform',

    'all_operational'        => 'All systems are operational',
    'incident_in_progress'   => 'An incident is in progress',
    'maintenance'            => 'Maintenance in progress',

    'services'               => 'Monitored services',
    'active_incidents'       => 'Active incidents',
    'no_active_incidents'    => 'No active incidents. All is well.',
    'no_monitors'            => 'No monitor configured.',

    'operational'            => 'Online',
    'down'                   => 'Offline',
    'paused'                 => 'Paused',
    'unknown'                => 'Unknown',

    'na'                     => 'n/a',
    'never_checked'          => 'Never checked',
    'uptime_periods'         => 'Uptime per period',
    'started_at_label'       => 'Started',
    'cause_label'            => 'Cause:',
    'refreshed_every_60s'    => 'Refreshed every 60 seconds',
    'provider_unavailable'   => 'The monitoring platform is temporarily unavailable. Please try again in a few minutes.',

    'counts' => [
        'total'     => '{0} no monitor|{1} :count monitor total|[2,*] :count monitors total',
        'down'      => '{1} :count down|[2,*] :count down',
        'paused'    => '{1} :count paused|[2,*] :count paused',
        'incidents' => '{1} :count active incident|[2,*] :count active incidents',
    ],
];

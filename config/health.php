<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

return [
    /*
     * A result store is responsible for saving the results of the checks. The
     * `EloquentHealthResultStore` will save results in the database. You
     * can use multiple stores at the same time.
     */
    'result_stores' => [
        Spatie\Health\ResultStores\EloquentHealthResultStore::class => [
            'connection' => env('HEALTH_DB_CONNECTION', env('DB_CONNECTION')),
            'model' => Spatie\Health\Models\HealthCheckResultHistoryItem::class,
            'keep_history_for_days' => 5,
        ],

        /*
        Spatie\Health\ResultStores\CacheHealthResultStore::class => [
            'store' => 'file',
        ],

        Spatie\Health\ResultStores\JsonFileHealthResultStore::class => [
            'disk' => 's3',
            'path' => 'health.json',
        ],

        Spatie\Health\ResultStores\InMemoryHealthResultStore::class,
        */
    ],

    /*
     * You can get notified when specific events occur. Out of the box you can use 'mail' and 'slack'.
     * For Slack you need to install laravel/slack-notification-channel.
     */
    'notifications' => [
        /*
         * Notifications will only get sent if this option is set to `true`.
         *
         * Pilote par variable d'environnement pour rester desactivable sans toucher au code.
         * Etait fige a false, ce qui rendait TOUTE notification de sante impossible : le
         * controle pouvait tourner et echouer, aucun courriel ne partait jamais. Constate le
         * 2026-08-01 en verifiant la boite de reception plutot qu'en supposant l'envoi.
         */
        'enabled' => (bool) env('HEALTH_NOTIFICATIONS_ENABLED', false),

        'notifications' => [
            Modules\Health\Notifications\CheckFailedNotification::class => ['mail'],
        ],

        /*
         * Here you can specify the notifiable to which the notifications should be sent. The default
         * notifiable will use the variables specified in this config file.
         */
        'notifiable' => Spatie\Health\Notifications\Notifiable::class,

        /*
         * When checks start failing, you could potentially end up getting
         * a notification every minute.
         *
         * With this setting, notifications are throttled. By default, you'll
         * only get one notification per hour.
         */
        'throttle_notifications_for_minutes' => 60,
        'throttle_notifications_key' => 'health:latestNotificationSentAt:',

        /*
         * When set to true, notifications will only be sent when at least one
         * check has a 'failed' status. Warnings will be ignored.
         */
        'only_on_failure' => false,

        'mail' => [
            'to' => env('HEALTH_NOTIFY_EMAIL', env('SUPER_ADMIN_EMAIL', env('MAIL_FROM_ADDRESS', 'noreply@laveille.ai'))),

            'from' => [
                'address' => env('MAIL_FROM_ADDRESS', 'noreply@laveille.ai'),
                'name' => env('MAIL_FROM_NAME', config('app.name', 'La veille')),
            ],
        ],

        'slack' => [
            'webhook_url' => env('HEALTH_SLACK_WEBHOOK_URL', ''),

            /*
             * If this is set to null the default channel of the webhook will be used.
             */
            'channel' => null,

            'username' => null,

            'icon' => null,
        ],
    ],

    /*
     * You can let Oh Dear monitor the results of all health checks. This way, you'll
     * get notified of any problems even if your application goes totally down. Via
     * Oh Dear, you can also have access to more advanced notification options.
     */
    'oh_dear_endpoint' => [
        'enabled' => false,

        /*
         * When this option is enabled, the checks will run before sending a response.
         * Otherwise, we'll send the results from the last time the checks have run.
         */
        'always_send_fresh_results' => true,

        /*
         * The secret that is displayed at the Application Health settings at Oh Dear.
         */
        'secret' => env('OH_DEAR_HEALTH_CHECK_SECRET'),

        /*
         * The URL that should be configured in the Application health settings at Oh Dear.
         */
        'url' => '/oh-dear-health-check-results',
    ],

    /*
     * You can specify a heartbeat URL for the Horizon check.
     * This URL will be pinged if the Horizon check is successful.
     * This way you can get notified if Horizon goes down.
     */
    'horizon' => [
        'heartbeat_url' => env('HORIZON_HEARTBEAT_URL'),
    ],

    /*
     * You can specify a heartbeat URL for the Schedule check.
     * This URL will be pinged if the Schedule check is successful.
     * This way you can get notified if the schedule fails to run.
     */
    'schedule' => [
        'heartbeat_url' => env('SCHEDULE_HEARTBEAT_URL'),
    ],

    /*
     * You can set a theme for the local results page
     *
     * - light: light mode
     * - dark: dark mode
     */
    'theme' => 'light',

    /*
     * When enabled, completed `HealthQueueJob`s will be displayed
     * in Horizon's silenced jobs screen.
     */
    'silence_health_queue_job' => true,

    /*
     * The response code to use for HealthCheckJsonResultsController when a health
     * check has failed
     */
    'json_results_failure_status' => 200,

    /*
     * You can specify a secret token that needs to be sent in the X-Secret-Token for secured access.
     */
    'secret_token' => env('HEALTH_SECRET_TOKEN'),

    'opcache' => [
        'enabled' => env('HEALTH_OPCACHE_ENABLED', false),
        'path' => env('HEALTH_OPCACHE_PATH', '_sante/opcache'),
        'token' => env('HEALTH_OPCACHE_TOKEN', ''),
        'timeout' => env('HEALTH_OPCACHE_TIMEOUT', 10),

        // Tolerance aux contentions transitoires de PHP-FPM (2026-08-13). La mesure est une
        // requete HTTP que le serveur s'adresse a LUI-MEME : sur un pool mutualise, elle peut
        // expirer quelques secondes alors que le site sert normalement les visiteurs. Sans
        // reprise ni seuil, 7 alertes « intervention rapide » ont ete envoyees sans qu'aucune
        // ne corresponde a un incident reel.
        'retry_times' => env('HEALTH_OPCACHE_RETRY_TIMES', 2),
        'retry_sleep_ms' => env('HEALTH_OPCACHE_RETRY_SLEEP_MS', 500),
        'fail_after_consecutive_failures' => env('HEALTH_OPCACHE_FAIL_AFTER_CONSECUTIVE_FAILURES', 2),
        'connection_failures_cache_key' => env('HEALTH_OPCACHE_CONNECTION_FAILURES_CACHE_KEY', 'health:opcache:connection_failures'),
        'warn_keys_percent' => env('HEALTH_OPCACHE_WARN_KEYS_PERCENT', 75),
        'fail_keys_percent' => env('HEALTH_OPCACHE_FAIL_KEYS_PERCENT', 90),
        'warn_memory_percent' => env('HEALTH_OPCACHE_WARN_MEMORY_PERCENT', 75),
        'fail_memory_percent' => env('HEALTH_OPCACHE_FAIL_MEMORY_PERCENT', 90),
        'warn_interned_percent' => env('HEALTH_OPCACHE_WARN_INTERNED_PERCENT', 80),
        'fail_interned_percent' => env('HEALTH_OPCACHE_FAIL_INTERNED_PERCENT', 95),
        'warn_refusals_delta' => env('HEALTH_OPCACHE_WARN_REFUSALS_DELTA', 100),
        'fail_refusals_delta' => env('HEALTH_OPCACHE_FAIL_REFUSALS_DELTA', 1000),
        'refusals_cache_key' => env('HEALTH_OPCACHE_REFUSALS_CACHE_KEY', 'health:opcache:refusals'),
    ],

    /*
     * Solde de credit OpenRouter (finance l'enrichissement de l'annuaire).
     *
     * ACTIF PAR DEFAUT, volontairement, contrairement au bloc opcache ci-dessus. Leçon du
     * 2026-08-23 : six drapeaux Pennant jamais definis avaient laisse trois taches planifiees
     * mortes en silence pendant des semaines. Un garde-fou qui exige une variable
     * d'environnement pour exister n'existe pas. Il se DESACTIVE explicitement, il ne s'active
     * pas par decouverte.
     */
    'openrouter' => [
        'enabled' => env('HEALTH_OPENROUTER_ENABLED', true),
        'timeout' => env('HEALTH_OPENROUTER_TIMEOUT', 10),
        // Intervalle entre deux interrogations reelles de l'API. Sert AUSSI d'intervalle
        // d'echantillonnage pour l'estimation d'autonomie : trop court, le bruit domine.
        'poll_seconds' => env('HEALTH_OPENROUTER_POLL_SECONDS', 1800),
        'warn_after_consecutive_failures' => env('HEALTH_OPENROUTER_WARN_AFTER_CONSECUTIVE_FAILURES', 3),
        // AVERTISSEMENTS DESACTIVES le 2026-08-26, sur demande du fondateur : le compte
        // OpenRouter se recharge AUTOMATIQUEMENT par carte, donc un « credit bas » n'appelle
        // aucune action et le rappel ne fait que deranger.
        //
        // A zero, la comparaison `$restant <= 0` n'est jamais vraie : plus aucun avertissement.
        // Les seuils d'ECHEC restent actifs, et c'est delibere : si la carte expire ou est
        // refusee, l'auto-recharge echoue en silence et le credit tombe pour de bon. C'est le
        // seul cas ou une alerte apprend encore quelque chose, et il reste couvert.
        'warn_remaining_usd' => env('HEALTH_OPENROUTER_WARN_REMAINING_USD', 0),
        'fail_remaining_usd' => env('HEALTH_OPENROUTER_FAIL_REMAINING_USD', 15),
        'warn_remaining_days' => env('HEALTH_OPENROUTER_WARN_REMAINING_DAYS', 0),
        'fail_remaining_days' => env('HEALTH_OPENROUTER_FAIL_REMAINING_DAYS', 3),
        'connection_failures_cache_key' => env('HEALTH_OPENROUTER_CONNECTION_FAILURES_CACHE_KEY', 'health:openrouter:echecs_consecutifs'),
        'measurement_cache_key' => env('HEALTH_OPENROUTER_MEASUREMENT_CACHE_KEY', 'health:openrouter:derniere_mesure'),
    ],

/**
 * By default, conditionally skipped health checks are treated as failures.
 * You can override this behavior by uncommenting the configuration below.
 *
 * @link https://spatie.be/docs/laravel-health/v1/basic-usage/conditionally-running-or-modifying-checks
 */
    // 'treat_skipped_as_failure' => false
];

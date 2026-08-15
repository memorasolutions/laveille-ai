<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Processor\PsrLogMessageProcessor;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that is utilized to write
    | messages to your logs. The value provided here should match one of
    | the channels present in the list of "channels" configured below.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Deprecations Log Channel
    |--------------------------------------------------------------------------
    |
    | This option controls the log channel that should be used to log warnings
    | regarding deprecated PHP and library features. This allows you to get
    | your application ready for upcoming major versions of dependencies.
    |
    */

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace' => env('LOG_DEPRECATIONS_TRACE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Laravel
    | utilizes the Monolog PHP logging library, which includes a variety
    | of powerful log handlers and formatters that you're free to use.
    |
    | Available drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog", "custom", "stack"
    |
    */

    'channels' => [

        'stack' => [
            'driver' => 'stack',
            'channels' => explode(',', (string) env('LOG_STACK', 'single')),
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 14),
            'replace_placeholders' => true,
        ],

        // S80 #58 — canal dédié crossword pour isoler debug PDF/grid (à conserver tant que l'outil est en construction)
        'crossword' => [
            'driver' => 'daily',
            'path' => storage_path('logs/crossword.log'),
            'level' => 'debug',
            'days' => 14,
            'replace_placeholders' => true,
        ],

        // Actus 2.0 (2026-08-11) - canal dédié à la fusion/clustering d'actualités
        // (FetchNewsCommand + ArticleClusteringService). 'level' fixé en dur à 'info',
        // volontairement INDÉPENDANT de LOG_LEVEL (env('LOG_LEVEL')='error' en prod filtre sinon
        // ces lignes avant écriture - c'est la cause racine du regroupement invisible en prod).
        // Rétention alignée sur le canal 'daily' existant du projet (même variable LOG_DAILY_DAYS).
        'fusion' => [
            'driver' => 'daily',
            'path' => storage_path('logs/fusion.log'),
            'level' => 'info',
            'days' => env('LOG_DAILY_DAYS', 14),
            'replace_placeholders' => true,
        ],

        // Porte de qualite des resumes IA News (2026-08-13) - canal dedie aux rejets de
        // SummaryQualityGate (Modules\News\Services\AiSummaryService::callModelCascade).
        // 'level' fixe en dur a 'info', volontairement INDEPENDANT de LOG_LEVEL, meme parade
        // que le canal 'fusion' ci-dessus (LOG_LEVEL=error en prod avalerait sinon ces motifs
        // de rejet avant ecriture, rendant impossible tout ajustement de seuils sur donnees
        // reelles). Retention alignee sur les autres canaux 'daily' du projet.
        'quality_gate' => [
            'driver' => 'daily',
            'path' => storage_path('logs/quality_gate.log'),
            'level' => 'info',
            'days' => env('LOG_DAILY_DAYS', 14),
            'replace_placeholders' => true,
        ],

        // Annuaire (2026-08-14, correctif #1840, 3e occurrence du meme piege apres 'fusion' et
        // 'quality_gate' ci-dessus) - canal dedie a l'evenement "maitre de vignette conserve mais
        // perime" (DirectoryAdminController::deriveMasterFromUpload() : une recapture admin, une
        // fois mise a l'echelle, n'atteint pas la hauteur minimale requise - l'ancien maitre et son
        // point focal sont volontairement CONSERVES plutot que detruits, et l'ecart est marque
        // Tool::screenshot_master_stale=true). 'level' fixe en dur a 'info', volontairement
        // INDEPENDANT de LOG_LEVEL - meme parade que 'fusion'/'quality_gate' : LOG_LEVEL=error en
        // prod avalerait sinon ce motif avant ecriture, rendant l'ecart invisible aux admins tant
        // qu'ils ne consultent pas la fiche outil elle-meme. Retention alignee sur les autres
        // canaux 'daily' du projet.
        'directory_screenshots' => [
            'driver' => 'daily',
            'path' => storage_path('logs/directory_screenshots.log'),
            'level' => 'info',
            'days' => env('LOG_DAILY_DAYS', 14),
            'replace_placeholders' => true,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => env('LOG_SLACK_USERNAME', 'Laravel Log'),
            'emoji' => env('LOG_SLACK_EMOJI', ':boom:'),
            'level' => env('LOG_LEVEL', 'critical'),
            'replace_placeholders' => true,
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => env('LOG_PAPERTRAIL_HANDLER', SyslogUdpHandler::class),
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
                'connectionString' => 'tls://'.env('PAPERTRAIL_URL').':'.env('PAPERTRAIL_PORT'),
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'handler_with' => [
                'stream' => 'php://stderr',
            ],
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
            'facility' => env('LOG_SYSLOG_FACILITY', LOG_USER),
            'replace_placeholders' => true,
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'production' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'info'),
            'days' => 14,
            'replace_placeholders' => true,
            'formatter' => \Monolog\Formatter\JsonFormatter::class,
        ],

        'emergency' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => 'emergency',
            'replace_placeholders' => true,
        ],

    ],

];

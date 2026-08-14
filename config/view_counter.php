<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Compteur de vues/consultations - configuration partagée
|--------------------------------------------------------------------------
| Consommé par Modules\Core\Services\ViewCounterService, appelé par tout
| module qui incrémente un compteur de vues public (outils, actualités,
| glossaire, mini-sites auteurs...). Incident 2026-08-13 : un compteur
| incrémenté sans aucun filtre (robots comptés, aucune déduplication) a
| produit un rapport vues/clics réels de 8 à 487x selon la fiche.
*/
return [

    // Fenêtre de déduplication (minutes) : une même identité de visite ne
    // fait progresser le compteur qu'une seule fois par fenêtre, quel que
    // soit le nombre de requêtes rapprochées.
    'dedup_window_minutes' => (int) env('VIEW_COUNTER_DEDUP_MINUTES', 30),

    // Suffixe de la colonne "compteur propre" ajoutée à côté de la colonne
    // historique (jamais réinitialisée, jamais supprimée - voir décision
    // du 2026-08-13). Ex. : views_count -> views_count_verified.
    'verified_suffix' => '_verified',

    // Motifs (sous-chaînes, comparaison insensible à la casse) recherchés
    // dans le User-Agent pour exclure les robots connus. Liste volontairement
    // en configuration (jamais en dur dans le code) pour rester maintenable
    // sans toucher au service. "bot" seul couvre déjà Googlebot, Bingbot,
    // YandexBot, DuckDuckBot, Applebot, Amazonbot, GPTBot, ClaudeBot, etc.
    'bot_patterns' => [
        'bot',
        'spider',
        'crawl',
        'slurp',
        'archiver',
        'ia_archiver',
        'archive.org',
        'curl',
        'wget',
        'python-requests',
        'python-urllib',
        'go-http-client',
        'okhttp',
        'httpclient',
        'libwww-perl',
        'java/',
        'facebookexternalhit',
        'whatsapp',
        'telegram',
        'discordbot',
        'slackbot',
        'headlesschrome',
        'phantomjs',
        'puppeteer',
        'playwright',
        'selenium',
        'scrapy',
        'lighthouse',
        'pagespeed',
        'uptimerobot',
        'pingdom',
        'gtmetrix',
        'feedfetcher',
        'validator',
        'ccbot',
        'anthropic-ai',
        'perplexity',
        'semrush',
        'ahrefs',
        'mj12',
        'dotbot',
        'seznambot',
    ],

];

<?php

declare(strict_types=1);

return [
    'name' => 'News',

    /*
    |--------------------------------------------------------------------------
    | Commentaires sur les actualités
    |--------------------------------------------------------------------------
    | 2026-05-27 #312 — désactivés par défaut (décision user). Les actualités
    | sont des news auto-syndiquées (450+ depuis 23 sources RSS), pas du
    | contenu éditorial original — les commentaires créent du bruit et de la
    | modération sans bénéfice clair. Conservé sur articles blog éditoriaux.
    | Pour réactiver : .env NEWS_COMMENTS_ENABLED=true OU passer default true.
    */
    'comments_enabled' => env('NEWS_COMMENTS_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Déduplication cross-source
    |--------------------------------------------------------------------------
    | Évite le résumé IA sur des articles en doublon cross-source.
    | Désactiver temporairement si la déduplication est trop agressive.
    */
    'dedup_skip_enabled' => (bool) env('NEWS_DEDUP_SKIP_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Fenêtre de traitement des articles récupérés
    |--------------------------------------------------------------------------
    | news:fetch ne (re)traite que les articles créés dans cette fenêtre. Sans
    | cette borne, les articles sautés par quota s'accumulent indéfiniment dans
    | la file (12 436 mesurés le 2026-08-09, ~43 Mo de texte) et le cron horaire
    | meurt en épuisement mémoire (128 Mo CLI) à chaque exécution.
    */
    'fetch_backlog_hours' => (int) env('NEWS_FETCH_BACKLOG_HOURS', 48),
];

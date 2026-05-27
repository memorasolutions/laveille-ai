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
];

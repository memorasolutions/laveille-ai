<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

return [
    'name' => 'Decido',

    /*
    |--------------------------------------------------------------------------
    | Gate « EN CONSTRUCTION » — sondages collectifs (/decido)
    |--------------------------------------------------------------------------
    | Tant que true, seul le superadmin voit les pages publiques du module ;
    | tout le reste (anonyme et connecté) reçoit une page 503 sobre. Pattern
    | identique à Academy/Books (voir config('academy.under_construction')).
    | Désactiver via DECIDO_UNDER_CONSTRUCTION=false dans le .env pour ouvrir
    | Décido au public.
    */
    'under_construction' => env('DECIDO_UNDER_CONSTRUCTION', true),

    /*
    |--------------------------------------------------------------------------
    | Durée de vie des sondages
    |--------------------------------------------------------------------------
    | Expiration automatique après clôture, alignée sur la politique Framadate
    | 2026 (6 mois post-clôture).
    */
    'expiration_months_after_close' => 6,
];

<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

return [
    'name' => 'Books',

    /*
    |--------------------------------------------------------------------------
    | Gate « EN CONSTRUCTION » — bibliothèque de livres (/livres)
    |--------------------------------------------------------------------------
    | Tant que true, seul le superadmin voit les pages publiques du module ;
    | tout le reste (anonyme et connecté) reçoit une page 503 sobre. Pattern
    | identique à Academy (voir config('academy.under_construction')).
    | Désactiver via BOOKS_UNDER_CONSTRUCTION=false dans le .env pour ouvrir
    | la bibliothèque au public.
    */
    'under_construction' => env('BOOKS_UNDER_CONSTRUCTION', true),
];

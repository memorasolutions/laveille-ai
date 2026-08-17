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
    |
    | 2026-08-17 : défaut basculé à false (mise en ligne publique mandatée par le
    | propriétaire). Le .env de production ne définit pas BOOKS_UNDER_CONSTRUCTION,
    | donc ce changement ouvre la bibliothèque au déploiement suivant.
    */
    'under_construction' => env('BOOKS_UNDER_CONSTRUCTION', false),

    /*
    |--------------------------------------------------------------------------
    | Rotation des encarts publicitaires internes
    |--------------------------------------------------------------------------
    | Slugs des livres mis en avant dans <x-fronttheme::book-promo>, dans l'ordre
    | du cycle. Lu par Modules\Books\Services\BookPromoRotator. La rotation avance
    | d'un cran par jour (America/Toronto) et deux emplacements d'une même page ne
    | montrent jamais le même livre.
    |
    | Ajouter ou retirer un titre ici suffit : aucune modification de code, aucune
    | intervention en base. Le livre doit exister dans la table books avec
    | is_published = 1, et ses quatre images doivent être présentes dans
    | public/images/livres/ ({slug}-cover-300.webp, -cover-600.webp, -cover-600.jpg,
    | -og-1200x630.jpg). À défaut, l'encart retombe sur les valeurs par défaut du
    | composant plutôt que d'afficher une image cassée.
    */
    'promo_pool' => [
        'ia-sans-se-faire-poursuivre',
        'ia-pour-les-parents',
        'nexus-neural-tome-1',
    ],
];

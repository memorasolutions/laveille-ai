<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

return [
    'theme' => env('BACKOFFICE_THEME', 'backend'),

    /*
    |--------------------------------------------------------------------------
    | Modules supportant le theme switcher
    |--------------------------------------------------------------------------
    |
    | Liste des modules dont les vues sont résolues dynamiquement selon le
    | thème actif. Chaque entrée mappe le namespace de vue au nom du module.
    | Pour ajouter un nouveau module : ajouter une entrée ici.
    |
    */
    'theme_modules' => [
        'backoffice' => 'Backoffice',
        'auth' => 'Auth',
        'blog' => 'Blog',
        'pages' => 'Pages',
        'newsletter' => 'Newsletter',
    ],
];

<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Configuration du module Newsletter.
 * Chargée sous le namespace « newsletter » (config('newsletter.*')).
 */

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Adresse courriel de test par défaut
    |--------------------------------------------------------------------------
    |
    | Pré-remplit le champ « Adresse courriel de test » du générateur de prompt.
    | Défini dans .env via NEWSLETTER_TEST_EMAIL.
    |
    */
    'test_email' => env('NEWSLETTER_TEST_EMAIL', ''),

    /*
    |--------------------------------------------------------------------------
    | Compagnies IA — facettes de recherche pour les actualités
    |--------------------------------------------------------------------------
    |
    | Liste des compagnies utilisées comme filtres rapides dans le combobox
    | « Actualité vedette » et « Top actualités » du générateur de prompt.
    | Aucun hardcode dans le contrôleur ou la vue — toujours via config('newsletter.companies').
    |
    */
    'companies' => [
        'OpenAI',
        'Anthropic',
        'Google',
        'Meta',
        'Mistral',
        'Microsoft',
        'Apple',
        'xAI',
        'DeepSeek',
    ],

];

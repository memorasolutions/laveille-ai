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

    /*
    |--------------------------------------------------------------------------
    | Domaines de courriels JETABLES (anti-bot, rejet silencieux)
    |--------------------------------------------------------------------------
    |
    | Liste MINIMALE et extensible de domaines d'adresses jetables. Une inscription
    | avec l'un de ces domaines est rejetée SILENCIEUSEMENT (même message de succès,
    | aucun subscriber). Garder court pour ne JAMAIS bloquer de vrais utilisateurs.
    |
    */
    'disposable_domains' => [
        'mailinator.com',
        'guerrillamail.com',
        'guerrillamail.info',
        'sharklasers.com',
        'yopmail.com',
        'trashmail.com',
        'tempmail.com',
        '10minutemail.com',
        'getnada.com',
        'maildrop.cc',
        'dispostable.com',
        'fakeinbox.com',
    ],

];

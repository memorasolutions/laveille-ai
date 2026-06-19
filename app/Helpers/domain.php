<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Helper domain — env-aware, centralise le domaine de l'application.
 *
 * Remplace les littéraux `https://laveille.ai` dans les générateurs
 * de JSON-LD, sitemaps et canonicals afin que le même code fonctionne
 * en local (laveilledestef.test), en staging et en production.
 *
 * Usage : app_domain('/glossaire') → 'https://laveille.ai/glossaire' (prod)
 *                                  → 'https://laveilledestef.test/glossaire' (local)
 */

if (! function_exists('app_domain')) {
    /**
     * Retourne l'URL de base de l'application (sans slash final)
     * suivie du chemin optionnel.
     *
     * @param  string  $path  Chemin à concaténer (ex. '/glossaire').
     */
    function app_domain(string $path = ''): string
    {
        return rtrim(config('app.url', 'https://laveille.ai'), '/').$path;
    }
}

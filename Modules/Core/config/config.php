<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

return [
    'name' => 'Core',

    /**
     * 2026-08-31 (incident #2107, urgence production) : coupe-circuit temporaire du rendu
     * GlossaryLinkifier::linkify() - voir docblock au point d'usage dans
     * Modules/Core/app/Services/GlossaryLinkifier.php::linkify(). Vrai par défaut (aucun
     * changement de comportement). La bascule à false via GLOSSARY_LINKIFIER_ENABLED dans
     * .env sur le serveur de production ne nécessite AUCUN redéploiement (config:cache est
     * interdit sur ce projet - env() relu à chaque requête).
     */
    'glossary_linkifier_enabled' => (bool) env('GLOSSARY_LINKIFIER_ENABLED', true),
];

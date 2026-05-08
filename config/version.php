<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Versioning SemVer 2.0.0 — https://semver.org
 *
 * Règles de bump (Conventional Commits) :
 *   feat:        -> MINOR (nouvelle fonctionnalité, rétro-compatible)
 *   fix:         -> PATCH (correction de bug, rétro-compatible)
 *   feat!: ou
 *   BREAKING:    -> MAJOR (casse rétro-compatibilité)
 *   chore/test/refactor/docs/style/ci -> pas de bump
 *
 * Historique :
 *   1.0.0 · 2026-05-08 · Initial production release (comparateur multi-outils livré)
 */

return [
    'major' => 1,
    'minor' => 0,
    'patch' => 0,

    /**
     * Codename optionnel (nom de la release courante).
     * Vide ou null si pas de codename.
     */
    'codename' => 'comparator',

    /**
     * Format du SemVer assemblé.
     * Lu via lv_version() dans app/Helpers/version.php.
     */
    'semver' => '1.0.0',
];

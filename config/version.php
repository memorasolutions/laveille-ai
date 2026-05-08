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
 *   1.2.0 · 2026-05-08 · Card overlay selection state + circle checkbox 32x32 + floating bar adaptative count>=1
 *   1.1.2 · 2026-05-08 · Fix duplication section Tendance vs Populaires (DRY) + badge vues intégré
 *   1.1.1 · 2026-05-08 · Fix bounce your@example.com (config/health.php hardcoded)
 *   1.1.0 · 2026-05-08 · Comparateur refonte sticky thead + slider arrows + mismatch detection + 6 outils max
 *   1.0.0 · 2026-05-08 · Initial production release (comparateur multi-outils livré)
 */

return [
    'major' => 1,
    'minor' => 2,
    'patch' => 0,

    /**
     * Codename optionnel (nom de la release courante).
     * Vide ou null si pas de codename.
     */
    'codename' => 'card-selection',

    /**
     * Format du SemVer assemblé.
     * Lu via lv_version() dans app/Helpers/version.php.
     */
    'semver' => '1.2.0',
];

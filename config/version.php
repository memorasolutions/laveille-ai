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
 *   1.8.0 · 2026-05-12 · #193 Octopus Prompt Builder Open-in AI — 6 boutons ChatGPT/Claude/Gemini/Perplexity/Mistral/Copilot (URL ?q= pré-rempli, fallback copy+redirect Gemini) + #182 atelier newsletter JIT (steps→prompt→ressources) + chain prompting CoT trigger + justification. Zéro coût opérationnel, zéro données serveur, zéro Loi 25 risk.
 *   1.7.0 · 2026-05-12 · #181 Menu navigation refonte action-oriented — Catégories→Apprendre + Blog/Pages retirés (footer) + Jouer mega menu NEW (Quête narrative, Constructeur, Comparateur, Sudoku, Mots croisés, Raccourcisseur, Calculatrice taxes QC) + WCAG AAA aria-haspopup/expanded/role=menu + mobile sidebar refactorée
 *   1.6.0 · 2026-05-08 · News SEO/AEO/GEO 2026 R1-R7 — JsonLdService enrichi (NewsMediaOrganization + Person/Org author + isBasedOn + speakable + articleBody/wordCount/keywords/inLanguage/articleSection) + TL;DR aside Speakable + H2 PAA + AiSummary tldr/quote/key_stat/expert + news-sitemap.xml dédié 72h
 *   1.5.0 · 2026-05-08 · Pricing audit S89 — BrowserFetch UA Chrome+retry (#58) + PpSearch direct OpenRouter (#60) + screenshot config flag (#59)
 *   1.4.1 · 2026-05-08 · Fix #57 image cassée glossaire (helper dictionary_hero_image_url + fallback .webp/.png)
 *   1.4.0 · 2026-05-08 · Audit pricing multi-source consensus + Playwright screenshot + SLA tiered + page admin review
 *   1.3.0 · 2026-05-08 · UX ultra-intuitive : bouton textuel "+ Ajouter au comparateur" + onboarding tooltip + popover 2e sélection
 *   1.2.1 · 2026-05-08 · Fix CSS compare-toggle invisible (relocation @once vers compare-bar inclus partout)
 *   1.2.0 · 2026-05-08 · Card overlay selection state + circle checkbox 32x32 + floating bar adaptative count>=1
 *   1.1.2 · 2026-05-08 · Fix duplication section Tendance vs Populaires (DRY) + badge vues intégré
 *   1.1.1 · 2026-05-08 · Fix bounce your@example.com (config/health.php hardcoded)
 *   1.1.0 · 2026-05-08 · Comparateur refonte sticky thead + slider arrows + mismatch detection + 6 outils max
 *   1.0.0 · 2026-05-08 · Initial production release (comparateur multi-outils livré)
 */

return [
    'major' => 1,
    'minor' => 8,
    'patch' => 0,

    /**
     * Codename optionnel (nom de la release courante).
     * Vide ou null si pas de codename.
     */
    'codename' => 'open-in-ai',

    /**
     * Format du SemVer assemblé.
     * Lu via lv_version() dans app/Helpers/version.php.
     */
    'semver' => '1.8.0',
];

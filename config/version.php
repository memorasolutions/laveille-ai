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
 *   1.11.0 · 2026-05-13 · #201 Cmd+K command palette globale — modale Alpine WCAG (role=dialog + aria-modal + focus auto + return-focus) déclenchée par Cmd+K (macOS) / Ctrl+K (Windows/Linux) ou bouton loupe header, recherche live cross-module via endpoint JSON `/recherche/palette` (web throttle 60/min) servie par SearchService::searchFront() limité 6/section depuis SearchRegistry (Blog + Actualités + Glossaire + Annuaire + Acronymes), debounce 250 ms + AbortController, keyboard nav ↑↓/Enter/Esc, sections groupées avec icône+compteur, footer « Voir tous les résultats » → /recherche?q=, mobile fullscreen, modules désactivés ignorés (zéro régression). + #200 refonte menus navigation Option E hybride — 3 mega menus Outils (4 groupes Productivité/Création/Détente/Pratique) + Annuaire (5 fiches stars data-driven GA4) + Apprendre (Contenu éditorial + Référence) + mobile sidebar refondue.
 *   1.10.0 · 2026-05-12 · #198 Brain Dump Partie 2 — refonte Mad Libs C+E (90/100 best practice 2026) — 5 chips inline cliquables (catégories multi-select / nbPatterns / nbActions / horizon / tone) avec popovers Alpine, prompt copié = rendu actuel substituant variables, WCAG AAA 32-44px target sizes, mobile popover full-width. + #196 uniformité bloc tool charte Memora (card blanc + ct-btn teal) + #195 Octopus intro.svg lisible (corps #0B7285 + tentacules #52B8C7 + pupilles #1a1d23).
 *   1.9.0 · 2026-05-12 · #194 Brain Dump 2026 — page dédiée /outils/brain-dump SEO+AEO+GEO+EEAT (Schema SoftwareApplication+HowTo+FAQPage+BreadcrumbList+Person, byline auteur, 3 citations scientifiques avec DOI, TL;DR speakable, charte Memora tokens, outil actif timer 10min + 2 prompts copy/Open-in AI 6 IAs) + alias FR /outils/vide-cerveau → 301
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
    'minor' => 11,
    'patch' => 0,

    /**
     * Codename optionnel (nom de la release courante).
     * Vide ou null si pas de codename.
     */
    'codename' => 'command-palette',

    /**
     * Format du SemVer assemblé.
     * Lu via lv_version() dans app/Helpers/version.php.
     */
    'semver' => '1.11.0',
];

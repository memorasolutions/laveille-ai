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
 *   1.13.0 · 2026-05-13 · #204 composant `<x-tools::octopus>` étendu 6 → 12 variants (compositions + émotions). `<x-core::empty-state>` enrichi prop `octopus` (remplace icône emoji). Search palette Cmd+K empty state initial = Octopus loved (« cherche pour vous »), no-results = Octopus confused (« n'a rien trouvé »). Consolidation 12 SVG dans Modules/Tools/resources/assets/octopus/ (dossier preview/ supprimé). #203 harmonisation 11 SVG aux tokens charte officielle (corps #0B7285 / tentacules #52B8C7 / pupilles #1a1d23 — intro.svg comme référence).
 *   1.12.0 · 2026-05-13 · #202 Octopus mascotte sur 7 pages erreur (403/404/405/419/429/500/503) — réutilisation stratégique post-désactivation quête. Partial paramétrisé errors.octopus._render avec 6 émotions SVG (4 nouvelles : confused/thinking/sleeping/surprised générées via qwen3-max openrouter-free 1.4s 0$ + 2 réservées happy/loved). Layout commun aux tokens charte officielle (#0B7285/#C2410C/#F8FAFB). Mapping par code : 404 confused, 403/503 thinking, 419/429 sleeping, 405 confused, 500 surprised. Microcopy ton FR-CA "Cette page s'est perdue dans les courants". + Désactivation /quete via QUEST_ENABLED=false (.env prod) + retrait liens menu (4 occurrences gated config('tools.quest.enabled')). Module préservé pour réactivation 1-flag.
 *   1.11.2 · 2026-05-13 · #189 fix charte /quete — chapter.blade.php + index.blade.php redéfinissaient des tokens locaux (--c-primary #064E5A / --c-accent #9A2A06 / --c-bg #F0F4F8) qui shadowaient les tokens charte officiels (#0B7285 / #C2410C / #F8FAFB). Refactor: héritage direct :root + --c-primary-hover + --c-primary-light + --c-primary-badge + --c-accent-light + --c-text-muted, fonts --f-heading (Plus Jakarta Sans) sur titres/chips/boutons + --f-body (DM Sans) sur corps, radius --r-base/--r-btn, focus-visible orange WCAG AAA SC 2.4.7. + redirect 301 /quete/ch1-eveil-loop → /quete/ch1-eveil-octopus + migration DB current_chapter.
 *   1.11.1 · 2026-05-13 · #189 réécriture ch1 narrative Loop → Octopus (cohérence mascotte) — pieuvre numérique 8 tentacules + clairière sous-marine + bulles-mots + tentacule lumineux. Concepts pédagogiques LLM/prédiction/hallucinations conservés. Délégation rédaction qwen3-max via openrouter-free.
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
    'minor' => 13,
    'patch' => 0,

    /**
     * Codename optionnel (nom de la release courante).
     * Vide ou null si pas de codename.
     */
    'codename' => 'octopus-empty-states',

    /**
     * Format du SemVer assemblé.
     * Lu via lv_version() dans app/Helpers/version.php.
     */
    'semver' => '1.13.0',
];

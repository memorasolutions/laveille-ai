# Audit exhaustif — Anonymiseur & Constructeur de prompts
**Date :** 2026-06-06 (America/Toronto) · **Outils :** https://laveille.ai/outils/anonymiseur · https://laveille.ai/outils/constructeur-prompts
**Périmètres :** (1) anonymiseur seul · (2) constructeur seul · (3) chaîne anonymiseur→constructeur
**Méthode :** preuves vérifiées via MCP (Playwright réseau/perf sur prod live, security headers, wcag-mcp, superagent gemini code review, test Node de robustesse sur 12 cas PII québécois). Cadre best practices juin 2026 (pp_search : scoring pondéré privacy/sécu > a11y > UX/SEO).

## Score global : **86/100**

| Dimension | Poids | Note /100 | Verdict |
|---|---|---|---|
| Vie privée / confidentialité | 20 % | **88** | 0 fuite réseau, 0 PII en URL, handoff consume-once — MAIS PII brutes persistées en localStorage + AdSense sur la page |
| Robustesse anonymisation | 20 % | **78** | Réversibilité 100 % — MAIS auto-détection 80 % (NAS non détecté, montants FR, noms abrégés) |
| Sécurité | 15 % | **90** | Headers A (7/8), sanitize collage robuste (deny-by-default) |
| Accessibilité WCAG 2.2 | 10 % | **90** | AA solide, panneau AA, SVG corrigé ; reste = faux positifs connus |
| Performance | 10 % | **82** | Assets outil légers (22 Ko JS) ; poids dominé par thème + ads (~897 Ko) |
| UX/UI | 10 % | **88** | Très abouti, charte cohérente, responsive ; warning double Alpine |
| SEO / GEO / AEO | 5 % | **84** | Indexable, schema SoftwareApplication ; méta-desc courtes, pas de FAQPage |
| Qualité de code | 5 % | **89** | (superagent gemini) réversibilité, regex accents, TreeWalker DOM |
| Intégration (chaîne + autonomie) | 5 % | **95** | 2 outils 100 % autonomes, chaîne propre, 0 PII traversante |

---

## 1. Vie privée / confidentialité — 88/100
**Preuves (Playwright prod, cycle PII complet) :**
- ✅ **Aucune** requête réseau (80 requêtes, 8 POST) ne contient de PII brute dans l'URL ou le corps. Le texte ne quitte jamais le navigateur.
- ✅ Handoff anonymiseur→constructeur : 0 PII en URL, texte transféré = version **anonymisée**, clé sessionStorage `lv_handoff_prompt_text` **effacée après lecture** (consume-once).
- ✅ GA4 : `anonymize_ip` + consent mode (`npa=1` par défaut), n'encode que l'URL de page + métadonnées comportementales.
- ⚠️ **P0** — `lv_anon_rules_v3` en **localStorage** stocke les **PII brutes** (original↔pseudonyme) de façon **persistante** (survit à la session). Intentionnel (nécessaire à la restauration) mais : lisible par extension/XSS, exposé sur poste partagé, conservation illimitée → tension avec *privacy-by-default* (Loi 25).
- ⚠️ **P1** — **Google AdSense** actif sur la page (54 Ko + DoubleClick) s'exécute dans le même contexte que l'éditeur de PII. Aucune exfiltration détectée, mais posture de confiance discutable pour un outil Loi 25.

## 2. Robustesse de l'anonymisation — 78/100
**Preuves (test Node, 12 cas PII québécois, 40 entités) :**
- ✅ **Réversibilité round-trip : 12/12 (100 %)** — restauration exacte.
- ⚠️ **Détection automatique : 32/40 (80 %)**. Faux négatifs = fuites résiduelles si l'utilisateur se fie à « Détecter » :
  - **P0 — NAS (numéro d'assurance sociale) : 0/4 détecté.** Aucun motif NAS dans le moteur. PII très sensible.
  - **P1 — Montants format FR : 0/2** (« 1 250,00 $ », « 2 750$ » : le `$` est APRÈS le nombre ; la regex exige `$` avant).
  - **P1 — Noms abrégés : 0/2** (« L. Gagnon », « A. Roy » : initiale + nom non captés).
- Mitigation existante : la **sélection manuelle** couvre tout ce que l'auto-détection rate (l'outil est assisté, pas automatique). À documenter clairement.

## 3. Sécurité — 90/100
- ✅ En-têtes : **grade A (7/8)** — CSP, HSTS, X-Frame SAMEORIGIN, nosniff, Referrer-Policy, Permissions-Policy, XSS-Protection. (`access-control-allow-origin` absent = correct pour same-origin.)
- ✅ `sanitizePastedHtml` : **deny-by-default** (DOMParser + liste blanche stricte, retrait de tous les attributs sauf `href` validé) → neutralise `<img onerror>`, `javascript:`, etc.
- ✅ `buildRestoreReportHtml` échappe chaque valeur via `escHtml`.
- ⚠️ **P1** — `innerHTML` réinjecte `sourceHtml` (sanitizé au collage) sans re-validation ; `execCommand('insertHTML')` déprécié. Défense en profondeur : re-sanitizer avant `innerHTML`.

## 4. Accessibilité — 90/100
- ✅ AA largement conforme (focus visible, labels, structure h1-h2, panneau d'anonymisation AA 6.77:1/7.34:1, spans surlignés non-interactifs corrects, SVG plein écran corrigés `aria-hidden`).
- Faux positifs connus de l'audit headless (blanc/blanc = header foncé + modale cachée, « Tab » sur panneaux masqués). Site-wide : skip-link 1×1 (apparaît au focus).

## 5. Performance — 82/100
- ✅ Assets **de l'outil** légers : 22 Ko JS (core 7 + ui 10 + rich 5) + 5 Ko CSS. DCL ~1 s.
- ⚠️ Poids total ~**897 Ko** dominé par thème Bloggar + **AdSense** (adsbygoogle 54 Ko + show_ads 169 Ko). Load 1,78 s (ads après contenu).
- Constructeur : 824 Ko (cache chaud), load 891 ms.

## 6. UX/UI — 88/100
- ✅ Très abouti : éditeur riche, surlignage 2 colonnes, tooltips valeur anonyme, modes réaliste/jetons, aide contextuelle, responsive 375 px corrigé.
- ⚠️ Warning « multiple instances of Alpine » (Livewire) — bénin mais à surveiller.

## 7. SEO / GEO / AEO — 84/100
- ✅ Indexable (`index, follow`), canonical, schema `SoftwareApplication`+`BreadcrumbList`+`Organization`, og:image, présent au sitemap.
- ⚠️ **P2** — méta-descriptions courtes (anonymiseur 92, constructeur **53** car. ; viser 120-160). Pas de `FAQPage` (occasion GEO/AEO).

## 8. Qualité de code — 89/100 (superagent gemini)
- Forts : réversibilité (uniqueFake/overrides), regex insensible accents (getAccentClass), TreeWalker + fragments (pas de corruption DOM), privacy-by-design.
- P1 : confiance à `sourceHtml` sans re-validation, `execCommand` déprécié, faux positifs RAMQ possibles. P2 : surlignage O(N×M) sur très longs docs, state management couplé au DOM.

## 9. Intégration (chaîne + autonomie) — 95/100
- ✅ Chaque outil 100 % autonome (sans l'autre). ✅ Panneau in-page (module partagé). ✅ Handoff sessionStorage volatile, consume-once, 0 PII traversante. ✅ Lien « Anonymiseur complet ».

---

## Recommandations priorisées

### P0 (vie privée / fuite — à traiter en priorité)
1. **Ajouter la détection NAS** (`\d{3} \d{3} \d{3}` / `\d{9}` avec validation Luhn pour limiter les faux positifs). PII sensible actuellement non masquée par « Détecter ».
2. **Cycle de vie des PII en localStorage** : options — (a) bouton « 🗑️ Oublier mes données » (efface la table de correspondance) + note explicite ; (b) basculer les règles en sessionStorage (volatile) au prix de la restauration inter-session ; (c) expiration auto. Recommandé : (a) additif sans tradeoff + note.

### P1
3. Détecter les **montants format FR** (« 1 250,00 $ », « 2 750$ »).
4. Détecter les **noms abrégés** (« L. Gagnon », « A. Roy »).
5. **AdSense sur les pages d'outils PII** : retirer ou encadrer (décision business + politique de confidentialité).
6. **Défense en profondeur** : re-sanitizer avant `innerHTML` (updateOutput/renderAnnotated).

### P2
7. Méta-descriptions 120-160 car. + `FAQPage` schema (GEO/AEO).
8. Remplacer `execCommand` par Selection/Range API.
9. Optimiser le surlignage (regex unique / Trie) pour très longs documents.
10. Résoudre le double Alpine (Livewire).

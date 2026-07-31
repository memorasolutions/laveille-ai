# Matrice de couverture - Audit /outils/constructeur-prompts (2026-07-26)

**Cible** : https://laveille.ai/outils/constructeur-prompts (module `Modules/Tools`, outil "Constructeur de prompts")
**Objectif utilisateur** : inventaire exhaustif des fonctionnalités actuelles + rendre l'outil ultra performant ET ultra simple pour des utilisateurs non-experts en IA, tout en gardant une UX/UI parfaite. Recherche des meilleures pratiques juillet 2026, options notées /100, validation croisée Perplexity + Codex + claude.ai + Gemini. **Livrable = proposition, aucune implémentation avant le go explicite de l'utilisateur.**

| Dimension | Statut | Preuve / justification |
|---|---|---|
| securite-applicative | complété | OWASP LLM Top10 non applicable (aucun appel serveur à un LLM, 100% composition côté client) ; aucun XSS (pas d'innerHTML), CSRF géré, IDOR impossible sur SavedPromptController (double scope user_id+public_id). 2 bloquants trouvés : accents manquants dans PromptBuilderSettingsSeeder.php (dormant, jamais seedé), zéro test dédié. |
| securite-infra | complété | Aucun appel serveur LLM ni infra dédiée à auditer au-delà du site déjà couvert (audit 2026-07-24). CDN Alpine intersect sans SRI (mineur). |
| qualite-code-DRY | complété | Fichier 941 lignes (vue+CSS+JS inline, ~430 lignes JS jamais mis en cache navigateur). 5 contrôleurs Saved*PresetController quasi identiques non factorisés (DRY violé, non bloquant). |
| performance | complété | 271 Ko HTML, 54 requêtes non bundlées, assets Vite fingerprintés SANS cache-control long terme, TTFB 309ms correct mais 109 Ko JS/CSS inline pénalisant le parsing. |
| accessibilite | complété | wcag_audit_aaa : 2 critiques confirmés (radios 13×13px, sous AA 24×24px), 1 haute confirmée (contraste lien 2,22:1), ~15 moyennes (dont le message de confiance "100% local" à 3,02:1 - ironique). |
| UX-UI | complété | Score 72/100 (design-critique). Bug CONFIRMÉ : bandeau cookies réapparaît par-dessus le formulaire, bouton "Tout accepter" hors viewport sur mobile 390px (bloquant réel pour débutant). Jargon non filtré (persona, tokens, few-shot, coquille "Chaîne of thought"). |
| SEO-GEO-AEO | complété | Title/meta uniques, Schema.org SoftwareApplication+WebApplication complet, GEO/AEO answer-box présent. GSC : 5 clics/43 impressions/position 6.2 sur 90 jours - marge de progression réelle. |
| conformite-Loi25-RGPD | complété | 100% côté client, aucune donnée serveur sauf sauvegarde opt-in authentifiée. Garde-fou PII intégré (anonymiseur avant collage IA externe). Lien politique de confidentialité accessible (footer global). |
| tests-couverture | complété | **Zéro test dédié** pour l'outil le plus visité du site (ni rendu de vue, ni SavedPromptController - IDOR/validation/auth/soft-delete non couverts par test automatisé, seulement vérifiés par lecture de code). |
| dependances-CVE-licences | complété | Aucune lib CDN propre à l'outil. jQuery 3.7.1 (site-wide, SRI présent, 0 CVE pertinente). Plugin Alpine intersect via jsdelivr sans SRI (mineur, site-wide). |
| hygiene-serveur | complété | Aucun cron/résidu lié à cet outil. |

## Inventaire fonctionnel actuel

_À remplir (Phase 1)._

## Veille best practices juillet 2026

_À remplir (Phase 2)._

## Options d'amélioration notées /100

_À remplir après audit + veille._

## Validation croisée (Perplexity / Codex / claude.ai / Gemini)

_À remplir._

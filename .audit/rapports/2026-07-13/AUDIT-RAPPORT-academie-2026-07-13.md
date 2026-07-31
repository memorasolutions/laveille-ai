# Audit Académie (laveille.ai) — UX-UI + Accessibilité + comparatif Moodle

**Date :** 13 juillet 2026 (America/Toronto) · **Périmètre demandé :** `/audit ux/ui, /wcag` sur le module Académie + comparatif fonctionnel Moodle 2026 + liste des manques.

## Matrice de couverture (Phase 0, complétée)

| Dimension | Statut | Preuve / justification |
|---|---|---|
| securite-applicative | non applicable - hors périmètre demandé | — |
| securite-infra | non applicable - hors périmètre demandé | — |
| qualite-code-DRY | non applicable - hors périmètre demandé | — |
| performance | non applicable - hors périmètre demandé | — |
| **accessibilite** | **complété** | 4 audits `wcag-mcp` frais (2026-07-13) sur les 4 pages clés, voir section 3 |
| **UX-UI** | **complété** | Observation directe de 7 captures Playwright authentifiées (superadmin), desktop + mobile, voir section 4 |
| SEO-GEO-AEO | non applicable - hors périmètre demandé | — |
| conformite-Loi25-RGPD | non applicable - hors périmètre demandé | — |
| tests-couverture | non applicable - hors périmètre demandé | — |
| dependances-CVE-licences | non applicable - hors périmètre demandé | — |
| hygiene-serveur | non applicable - hors périmètre demandé | — |

**Livrable additionnel** (demandé hors 11 dimensions standard) : inventaire exhaustif des fonctionnalités Académie + comparatif Moodle 2026 + liste des manques → **complété**, voir section 5 et `.audit/comparatif-moodle-academie.md`.

## 1. Résumé exécutif

**Score technique (accessibilité + UX-UI) : 78/100.** Calcul transparent, montré ci-dessous — il n'inclut délibérément PAS le statut de mise en ligne (voir verdict pratique séparé), pour ne pas mélanger une qualité de code (mesurable, dimension par dimension) avec un statut de déploiement/décision d'affaires (binaire, hors rubrique).

| Dimension | Base | Pénalités appliquées | Score |
|---|---|---|---|
| Accessibilité | 100 | -25 (1 bug critique confirmé : texte noir sur fond navy, illisible, `/academie/espace`) · -5 (manques mineurs, AAA seulement : cibles tactiles, skip-link) | **70/100** |
| UX-UI | 100 | -10 (densité de boutons par carte, dashboard « Mes cours ») · -5 (incertitude non résolue : débordement mobile suspecté puis non confirmé) | **85/100** |
| **Moyenne pondérée (50/50)** | | | **≈ 78/100** |

**⚠️ Verdict pratique séparé, qui prime sur le score technique** : `academy.under_construction=true` en prod rend l'Académie invisible à 100 % des visiteurs réels, indépendamment de sa qualité. Ce n'est ni un défaut d'accessibilité ni un défaut UX-UI mesurable par une rubrique — c'est un statut de déploiement. Le tenir séparé du score (plutôt que de le diluer dedans en pénalité opaque) permet de voir clairement les deux vérités distinctes : le code est de bonne qualité (78/100), ET personne n'y accède actuellement. Un bug d'accessibilité critique confirmé (texte noir sur fond navy, illisible) a par ailleurs été trouvé sur le formulaire de connexion `/academie/espace` — inclus dans le calcul ci-dessus.

## 2. Méthodologie et limitation documentée (transparence obligatoire)

- **UX-UI** : le jury multimodal automatisé prévu par le skill `/design-critique` (Gemini + GPT-5 via `multi-ai-mcp`) n'a **pas pu être utilisé** — panne confirmée et root-causée du CLI Gemini (`~/.gemini/settings.json` : le champ `model` est une chaîne alors que la CLI v0.37.1 attend un objet, `mcp__multi-ai-mcp__chat` n'a par ailleurs aucun paramètre d'image). Cette configuration globale est **hors périmètre** de cet audit (ne pas modifier sans accord explicite). **Palliatif appliqué** : observation directe des 7 captures (desktop + mobile) par Opus, explicitement labellisée comme telle ci-dessous — ce n'est pas un jury automatisé noté /100 par critère, mais une revue structurelle qualitative.
- **Accessibilité** : le navigateur headless de `wcag-mcp` tourne dans une session **non authentifiée**, distincte de la session Playwright principale (authentifiée superadmin). Résultat observé : les audits `/academie`, `/academie/courses/demo-decouvrir-ia` et `/academie/courses/demo-decouvrir-ia/lessons/1` retournent des résultats **byte-identiques** (mêmes 86 critères, mêmes violations exactes) — signe que les 3 URLs ont toutes atteint la **même page-gate** (503 « en construction ») plutôt que le contenu réel du catalogue/cours/leçon. L'audit de `/academie/espace` a, lui, atteint un vrai formulaire de connexion (page différente, résultats différents) — cohérent avec le comportement `auth` middleware attendu. **Conséquence** : la couche WCAG automatisée de ce rapport audite fidèlement ce que voit un visiteur anonyme aujourd'hui (le gate + le login), pas l'intérieur de l'application authentifiée. La qualité structurelle de l'intérieur (dashboard, leçon, catalogue réel) a été vérifiée par observation visuelle directe (section 4), pas par un scan WCAG automatisé — limitation assumée et documentée plutôt que masquée.

## 3. Accessibilité — résultats WCAG 2.2 AAA (frais, 2026-07-13)

### 3.1 `/academie`, `/academie/courses/demo-decouvrir-ia`, `/academie/courses/demo-decouvrir-ia/lessons/1` (page-gate, résultats identiques)

| Statut | Nombre |
|---|---|
| Conforme | 19 |
| Non conforme | 10 |
| Partiel | 1 |
| Revue manuelle | 30 |
| Non applicable | 26 |

**Finding le plus visible mais FAUX POSITIF confirmé (à ne pas corriger comme un bug de contraste)** : l'outil rapporte `rgb(255,255,255) sur rgb(255,255,255)` (1:1, invisible) pour le H2 « Académie », le fil d'Ariane « Accueil », et le libellé actif « Académie ». **Cause racine vérifiée dans le code** : `public/css/charte.css:478-480` applique `.wpo-breadcumb-area { background: linear-gradient(135deg, var(--c-dark) 0%, #2D3039 50%, #3F4451 100%) !important; }` — un dégradé navy/anthracite. Le texte est en `color: #fff` explicite. L'outil WCAG lit `getComputedStyle().backgroundColor`, qui reste `rgba(0,0,0,0)` transparent quand le fond est posé via `background-image`/`linear-gradient()` (pas un `background-color` scalaire), et retombe par défaut sur blanc en remontant le DOM. **Confirmé visuellement** sur les 7 captures Playwright réelles (authentifiées) : le titre et le fil d'Ariane sont blancs, gras, parfaitement lisibles sur fond navy foncé — conforme AAA en réalité. **Ne pas corriger comme un bug** ; à signaler à l'éditeur de l'outil `wcag-mcp` comme limitation connue (fonds en dégradé/image non résolus par le contrôle de contraste).

**Findings réels sur cette page-gate** (candidate elle-même à un correctif d'accessibilité, puisque c'est ce que 100 % des visiteurs voient) :
- 🟠 Cible tactile 32×32px pour le bouton « Se connecter » (< 44×44 AAA, borderline 24×24 AA).
- 🟠 Skip-link de taille 1×1px (`<a href="#main-content" class="skip-link">`) — cible bien en dessous des 24×24px minimum AA.
- 🟡 Pas de skip-link comme premier élément tabulable détecté, pas de landmark `breadcrumb`.
- 🟡 Contraste `rgb(107,114,128)` sur blanc = 4.83:1 (conforme AA, sous le seuil AAA 7:1) sur les libellés « Astuce », « ou », « pour rouvrir » du bandeau newsletter.
- Le grand nombre d'éléments « non atteignables au clavier » signalés (~90) concerne des éléments du header/newsletter modal partagés site-wide, pas spécifiques à l'Académie — à traiter dans un audit WCAG site-wide séparé, pas comme un défaut du module Académie.

### 3.2 `/academie/espace` (formulaire de connexion réel)

| Statut | Nombre |
|---|---|
| Conforme | 21 |
| Non conforme | 9 |
| Partiel | 1 |
| Revue manuelle | 28 |
| Non applicable | 27 |

**🔴 CRITIQUE CONFIRMÉ (vrai bug, pas un faux positif)** : `rgb(0,0,0)` (texte noir) sur `rgb(12,20,39)` (fond navy quasi noir) = ratio **1.14:1** — 4 occurrences, sur des `<li class="flex items-center space-x-3"><i class="ti ti-circle-check">...` (liste de fonctionnalités/avantages affichée à côté du formulaire de connexion, ex. « Accès aux formations », etc.). Texte **réellement illisible**. Cause probable : classe de texte pensée pour un fond clair (`text-gray-900`/noir) appliquée sur un panneau à fond sombre sans variante de couleur adaptée. **Fichier/composant à localiser** : vue de connexion Académie (`Modules/Academy` ou `Modules/Sso`, formulaire `wire:model="remember"` visible dans le HTML) — correction recommandée : forcer une couleur claire (`text-white`/`text-gray-100`) sur cette liste dans le contexte du panneau sombre.

**Autres findings réels** :
- 🟠 Skip-link et bouton « Se connecter » : contraste 5.93:1 (blanc sur `#0369a1`), conforme AA, sous le seuil AAA 7:1.
- 🟡 Landmarks `nav`/`footer` manquants (page de connexion minimaliste, cohérent avec son rôle).
- 🟡 Pas d'animation avec `prefers-reduced-motion` déclaré.
- 🟡 4 éléments interactifs non atteignables au clavier (à vérifier manuellement — formulaire d'auth, zone à fort enjeu).

### 3.3 Verdict accessibilité

Un seul vrai bug **critique** trouvé (contraste noir-sur-navy, `/espace`), facilement corrigeable (changement de classe CSS, effort faible). Le reste = faux positif documenté (dégradé mal lu) + améliorations mineures AAA (cibles tactiles, skip-link) qui n'empêchent pas l'usage. **Le module Académie n'a pas de dette d'accessibilité lourde une fois ce bug corrigé.**

## 4. UX-UI — observation directe (jury multimodal indisponible, cf. section 2)

Revue de 7 captures Playwright pleine page, session authentifiée superadmin, desktop (1280px) + mobile (390px) : catalogue, fiche de cours, dashboard « Mon espace », page de leçon.

**Forces observées :**
- Hiérarchie visuelle cohérente sur toutes les pages : bannière hero navy uniforme (titre H2 blanc gras + fil d'Ariane), sous-navigation à onglets claire avec état actif souligné (teal), footer identique site-wide — cohérence de marque forte.
- Page de leçon bien structurée : sommaire latéral des leçons (« Les bases » / « Aller plus loin »), barre de progression, carte de contenu lisible, boutons prev/next explicites, bouton « Écouter cette leçon » (TTS) bien intégré.
- Fiche de cours claire : badges niveau/gratuité/durée bien groupés, bouton CTA primaire net (« Continuer le cours »), boutons de partage social visibles, séances live et test de positionnement mis en avant sans surcharger.
- Dashboard « Mon espace » : sections repliables (Ma progression, Certificats) pour ne pas surcharger, formations en cours avec barre de progression visible.

**Points d'attention (findings UX-UI) :**
- 🟡 **Densité de boutons sur les cartes « Mes cours » du dashboard** : chaque carte affiche 4 boutons d'action (Gérer / Statistiques / Rapports / Dupliquer) en ligne — dense sur desktop, potentiellement à regrouper dans un menu contextuel (`admin-action-menu`, déjà standard ailleurs sur le site selon la mémoire du projet) pour cohérence et pour alléger visuellement.
- 🟡 **Débordement H1 mobile suspecté puis infirmé** : sur la capture mobile fiche-cours, le titre semblait déborder le viewport. Vérification du code (`_page-title.scss:30-41`) : media query `max-width:767px` réduit bien la police à 30px, aucune règle `nowrap`/`overflow` trouvée. **Conclusion : probable artefact de capture (timing de rendu), pas un bug confirmé dans le code** — à revérifier visuellement en conditions normales avant d'ouvrir un ticket.
- 🟢 Aucune régression visuelle majeure trouvée dans les 7 captures (pas de superposition, pas de texte tronqué confirmé, pas de rupture de grille).

**Limite explicite** : cette section est une observation qualitative structurée, pas un score /100 par critère pondéré (hiérarchie/lisibilité/contraste/etc.) comme le produirait normalement le jury multimodal du skill `/design-critique` — la panne d'outil documentée en section 2 empêche cette granularité pour cette session.

## 5. Comparatif fonctionnel vs Moodle 2026

Détail complet (tableaux, sources) : `.audit/comparatif-moodle-academie.md`. Synthèse :

> ⚠️ **Rappel du blocage critique** : peu importe le résultat de ce comparatif, `under_construction=true` en prod signifie qu'aucun visiteur réel n'accède actuellement à l'Académie. C'est le seul fait qui prime sur toute discussion de parité fonctionnelle.

- **Supérieur à Moodle** : Certification/Badges (Open Badges 3.0 natif), IA pédagogique (tuteur RAG ancré au cours, Moodle n'a que des connecteurs génériques), Gamification (XP/niveaux natifs vs plugins tiers requis sur Moodle).
- **Équivalent** : Gestion de cours, Évaluation/Quiz, Social/Collaboratif.
- **Inférieur** : Interopérabilité (SCORM/LTI/xAPI codés mais désactivés en prod, et même activés : LTI consumer seulement, jamais provider ; SCORM sans séquencement IMS SS complet), Admin/Gestion (pas de MFA, pas de LDAP natif).
- **Absent** : application mobile native avec accès hors-ligne (le PWA du site est hors-périmètre Academy).

**Ce qui manque réellement** (8 items, détail avec priorité/effort dans le fichier dédié) : app mobile native (effort élevé), banque de questions à l'échelle de l'organisation (effort moyen), MFA (effort moyen), LTI provider + Deep Linking/AGS/NRPS (effort élevé), SCORM 2004 complet (effort élevé), webconférence embarquée type BigBlueButton (effort élevé), intégration LDAP (effort moyen), actions par lot (effort faible).

**Ce qui est codé mais juste désactivé** (15 flags, ce n'est PAS un manque) : SCORM, import Moodle .mbz, xAPI, graphe de compétences, éditeur de diplômes, discussion vidéo, messagerie directe, paliers d'abonnement, LTI consumer, mode kiosque, catégories de cours, calendrier global, notifications, auto-Meet, SSO/SCIM.

## 6. Plan de match priorisé

| # | Action | Impact | Effort | Priorité |
|---|---|---|---|---|
| 1 | **Lever ou planifier la levée du gate `academy.under_construction`** en prod (décision utilisateur — hors scope technique de cet audit) | Critique | Aucun (config) | **P0** |
| 2 | Corriger le contraste noir-sur-navy sur `/academie/espace` (liste de fonctionnalités à côté du formulaire de connexion) | Élevé (accessibilité réelle, page vue par tout visiteur non connecté) | Faible (1 classe CSS) | **P0** |
| 3 | Signaler à l'outil `wcag-mcp` (ou contourner en interne) la limitation de détection de contraste sur fonds en `linear-gradient()`/`background-image` — source de faux positifs récurrents dans les futurs audits | Moyen (fiabilité des futurs audits) | Faible | P1 |
| 4 | Regrouper les 4 boutons d'action par carte de cours (dashboard « Mes cours ») dans un menu contextuel `admin-action-menu` existant, pour cohérence site-wide et allègement visuel | Moyen | Faible | P1 |
| 5 | Augmenter la taille de cible du skip-link et du bouton « Se connecter » à 44×44px pour la conformité AAA stricte (actuellement conformes AA seulement) | Faible | Faible | P2 |
| 6 | Réparer le CLI Gemini global (`~/.gemini/settings.json`, champ `model` string→objet) pour réactiver le jury multimodal du skill `/design-critique` — **hors scope, nécessite accord explicite de l'utilisateur avant modification** | Moyen (outillage futur) | Faible | P2 (sur demande) |

## 7. Preuve de nettoyage (Phase 7, obligatoire)

- **7 captures temporaires de cet audit supprimées** (`academie-catalogue-desktop/mobile.png`, `academie-fiche-cours-desktop/mobile.png`, `academie-lecon-desktop.png`, `academie-dashboard-desktop/mobile.png`) — confirmé par `ls` (absence). Les 17 autres fichiers `academie-*.png` à la racine du projet, préexistants de sessions de travail antérieures et sans lien avec cet audit, ont été délibérément préservés (non touchés).
- **`browser_close` confirmé** : `mcp__playwright__browser_close` exécuté, « No open tabs » retourné.
- **Aucun cron temporaire créé** pendant cet audit (aucune opération cPanel n'a eu lieu — audit 100 % lecture, DB locale + WCAG + Playwright).
- **Aucune écriture en production** effectuée par cet audit (lecture seule stricte, conforme à la règle prod).

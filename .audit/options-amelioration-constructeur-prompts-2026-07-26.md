# Options d'amélioration — Constructeur de prompts (laveille.ai)

Contexte : audit complet terminé (11 dimensions, voir AUDIT-MATRICE-constructeur-prompts-2026-07-26.md).
Demande utilisateur : rendre l'outil ultra performant ET ultra simple pour des utilisateurs qui ne
connaissent rien à l'IA, sans sacrifier la puissance. Veille pp_search juillet 2026 sur les
meilleures pratiques de prompt builders grand public + performance web.

## Constats clés de l'audit (rappel)

- Bug **P0 confirmé et reproductible** : le bandeau de cookies réapparaît par-dessus le formulaire
  à chaque étape ; sur mobile 390px, son bouton "Tout accepter" est hors du viewport (bloquant réel
  pour un premier contact).
- Jargon IA exposé sans filtre : "persona", "~98 tokens", "Few-shot", coquille bilingue "Chaîne of
  thought" — contradictoire avec l'objectif "ultra simple" pour un public non-technique.
- Radios de choix "Prédéfinie/Personnalisée" à 13×13px (sous le minimum WCAG AA 24×24px).
- Contraste insuffisant sur le lien de connexion (2,22:1) et, ironiquement, sur le message de
  confiance "100% local, aucune donnée ne quitte..." (3,02:1) — le message censé rassurer un
  utilisateur non-technique est lui-même illisible pour certains.
- Performance : 941 lignes de vue+CSS+JS inline (~109 Ko jamais mis en cache navigateur), 54
  requêtes non bundlées, assets Vite fingerprintés SANS `cache-control` long terme.
- Zéro test automatisé pour l'outil le plus visité du site.
- UX déjà solide par ailleurs : stepper 4 étapes clair, aperçu en temps réel, ouverture directe
  dans ChatGPT/Claude/Perplexity/Gemini, anonymisation intégrée au flux, 100% local (aucune donnée
  envoyée sauf sauvegarde opt-in connectée) — score UX-UI actuel 72/100.

## Veille juillet 2026 (pp_search, sources citées)

- Pattern gagnant pour un public débutant : **progressive disclosure** — poser une question à la
  fois (objectif → contexte → style → révision), limiter la profondeur de divulgation à ~2 niveaux,
  cacher les réglages rares derrière un déclencheur clairement libellé ("Voir 3 options avancées"),
  jamais un "mega-textarea" à remplir d'un coup. Éviter le jargon ("Fournir un contexte") au profit
  du langage courant ("Dites à l'IA ce qu'elle doit savoir"). Aide en ligne courte > liens vers de
  la documentation. (aiuxdesign.guide/patterns/progressive-disclosure, 2026)
- Tendance produit 2026 : **le bon compromis n'est pas moins de puissance, mais moins de charge
  mentale** — cacher la complexité à l'écran tout en gérant en coulisse le contexte, les gabarits et
  la qualité de sortie. Les meilleurs outils grand public transforment une intention simple en
  prompt structuré plutôt que d'exiger que l'utilisateur maîtrise le prompt engineering.
- Performance : servir les assets Vite fingerprintés avec `Cache-Control: public, max-age=31536000,
  immutable` (le hash change au contenu, donc sûr) ; ne garder que le CSS/JS strictement critique
  inline, différer le reste ; éviter les chaînes de requêtes CSS `@import`. (solidappmaker.com,
  developer.mozilla.org - Cache-Control, 2026)

## Options notées /100

### Option 1 — Correctifs ciblés seulement (quick wins)
Corriger uniquement les bugs et manques confirmés : bandeau cookies qui bloque le formulaire (P0),
cibles tactiles 13px→44px, contrastes non conformes, extraction du JS inline vers un fichier externe
mis en cache + `cache-control` immutable sur les assets Vite, correction des accents dans
`PromptBuilderSettingsSeeder.php`, ajout de tests Feature pour `SavedPromptController` (IDOR,
validation, auth, soft-delete).
- **Impact 55/100** : corrige de vrais bugs et de la dette technique, mais ne répond pas à la
  demande explicite "ultra simple pour non-experts" (le jargon reste visible).
- **Effort : faible** (quelques jours, risque de régression minimal, aucune refonte structurelle).
- **Score global : 65/100.**

### Option 2 — Simplification du vocabulaire (sans refonte structurelle)
Option 1 + reformulation de tout le jargon en langage courant ("Zero-shot"→"Réponse directe",
"Few-shot"→"Avec des exemples", correction de la coquille "Chaîne of thought"→"Réflexion étape par
étape"), le bloc "Contraintes à respecter" replié par défaut (accordéon) au lieu d'un mur de texte
toujours visible, micro-aide contextuelle courte plutôt que jargon technique dans les libellés.
Garde exactement la même architecture 4 étapes.
- **Impact 75/100** : applique directement le pattern de recherche validé ("langage courant >
  jargon", "cacher les réglages rares") sans rien casser de l'existant.
- **Effort : moyen.**
- **Score global : 82/100.**

### Option 3 — Refonte complète en assistant "objectif d'abord" (gabarit juillet 2026)
Réécriture de la structure : étape 1 = cartes de tâches concrètes ("Que voulez-vous créer ?" - un
courriel, un résumé, une liste d'idées...) au lieu du concept abstrait "persona" en premier ;
profondeur de divulgation plafonnée à 2 niveaux ; déclencheur "Voir les options avancées" toujours
visible et libellé clairement ; aperçu compilé en langage courant avant la vue technique. Combinée à
la refonte performance complète (bundle, cache, JS externe).
- **Impact 92/100** : répond le mieux aux deux exigences (ultra simple ET ultra performant),
  fondée sur les meilleures pratiques 2026 validées par la recherche.
- **Effort : élevé** (réécriture complète des 941 lignes, nouvelle architecture JS, tests de
  régression approfondis - c'est la page la plus visitée du site).
- **Score global : 88/100** - meilleure valeur long terme, mais coût et risque court terme les
  plus élevés des 4 options.

### Option 4 — Bascule "Mode simple / Mode avancé" (recommandation)
Ajoute un choix explicite en haut de l'outil : **Mode simple (recommandé)** — ne montre que
"Que voulez-vous que l'IA fasse ?", une description en langage courant, un choix de ton simplifié
(3-4 options visuelles), un gros bouton "Générer", aperçu toujours en langage courant. **Mode
avancé** — révèle le formulaire actuel au complet (personas, few-shot, chaîne de pensée,
délimiteurs...) pour les utilisateurs qui en ont besoin. Combinée à toutes les corrections de
l'Option 1 (bugs, performance, tests).
- **Impact 88/100** : c'est l'application la plus directe de la conclusion de recherche « le bon
  compromis n'est pas moins de puissance, mais moins de charge mentale » — préserve 100% de la
  puissance actuelle (rien n'est retiré, juste caché par défaut) tout en offrant une first
  impression radicalement plus simple.
- **Effort : moyen-élevé** (nouveau mode d'affichage + logique de bascule, mais réutilise
  l'infrastructure Alpine.js existante sans réécrire le moteur de génération du prompt - risque de
  régression nettement plus faible que l'Option 3).
- **Score global : 90/100** - meilleur équilibre entre les deux exigences explicites de
  l'utilisateur (simplicité ET performance/puissance) pour un risque maîtrisé.

### Option 5 — Assistant IA de pré-remplissage en langage naturel (hors scope recommandé)
L'utilisateur décrit son besoin en une phrase ("Je veux une lettre pour demander un congé à mon
patron") et un système pré-remplit automatiquement persona/tâche/audience/style, laissant
l'utilisateur ajuster ensuite via le formulaire existant. C'est le levier UX le plus puissant
identifié par la recherche ("transformer une intention simple en prompt structuré"), mais nécessite
soit un appel LLM serveur (rupture de la promesse actuelle "100% local, zéro donnée transmise" -
un changement de positionnement majeur qui dépasse la demande initiale), soit une heuristique
locale simpliste (mots-clés) au risque de pré-remplissages médiocres et frustrants.
- **Impact 95/100 si bien exécutée**, mais **effort très élevé** et **changement de modèle de
  confidentialité** non demandé par l'utilisateur.
- **Score global : 70/100** - pénalisé pour le risque/coût et le changement de portée non sollicité
  ; à garder en note pour une itération future, pas pour ce cycle.

## Recommandation préliminaire (avant validation croisée)

**Option 4 (bascule Mode simple/avancé) + corrections techniques de l'Option 1**, pour les raisons
suivantes : (1) répond aux deux exigences explicites sans compromis - rien n'est retiré pour les
utilisateurs avancés, tout est simplifié pour les débutants ; (2) directement fondée sur la
recherche juillet 2026 citée plus haut ; (3) risque de régression maîtrisé (n'exige pas de
réécriture complète de la logique de génération du prompt, contrairement à l'Option 3) ; (4) inclut
la correction du bug P0 (bandeau cookies) qui bloque actuellement TOUS les utilisateurs, peu
importe l'option retenue pour la suite.

## Validation croisée (Codex, Gemini, claude.ai) — TERMINÉE

**Codex** : d'accord avec l'Option 4, MAIS avec une réserve importante — le mode simple doit reprendre
l'entrée « objectif d'abord » de l'Option 3 (cartes de tâches concrètes), pas une simple bascule qui
masque arbitrairement les champs actuels. Renommer la bascule en « Simple / Personnaliser davantage »
plutôt que « Simple/Avancé » (moins intimidant).

**Gemini** (Playwright, agy épuisé) : **en désaccord avec l'Option 4**. Pointe un « anti-pattern de
bascule de mode » : forcer un choix explicite Simple/Avancé crée une friction cognitive avant même de
commencer. Recommande l'Option 3 ajustée : orientation par l'intention + divulgation progressive
CONTEXTUELLE (blocs dépliables du type « Ajouter des règles », « Fournir des exemples ») plutôt qu'un
interrupteur global qui cache/révèle tout d'un coup.

**claude.ai** (session connectée stephane@memora.ca) : **en désaccord avec l'Option 4**. Argument
central : l'Option 4 traite le symptôme (densité du formulaire) et non la cause (l'ordre des étapes) —
un débutant ne peut pas répondre à « Persona » en 1re étape parce qu'il ne sait pas encore ce qu'il
veut obtenir. 3 objections concrètes à la bascule : (1) impose une décision méta avant toute action
utile ; (2) double la surface de test d'un outil qui n'en a aujourd'hui AUCUNE ; (3) le mode avancé
devient un dépotoir où chaque cas limite est repoussé au lieu d'être simplifié. Recommande l'Option 3
comme base, en y intégrant la seule bonne idée de l'Option 4 (un panneau « Afficher tous les réglages »
au niveau 2 de la divulgation progressive - même préservation de puissance, mais UN SEUL parcours et
UN SEUL jeu de tests). Point clé : l'écart de score 88 vs 90 entre Options 3 et 4 disparaît si le
niveau 2 de l'Option 3 est complet - le score seul ne justifie donc pas l'Option 4.

## Recommandation FINALE (après validation croisée)

**Verdict majoritaire clair : 2 experts externes sur 3 (Gemini, claude.ai) rejettent explicitement
la bascule Simple/Avancé de l'Option 4**, et le 3e (Codex) qui l'acceptait exigeait déjà de la
modifier pour intégrer l'entrée « objectif d'abord » de l'Option 3 - donc même Codex converge en
partie vers l'Option 3.

**Recommandation retenue : Option 3 (refonte "objectif d'abord") + le panneau "Afficher tous les
réglages" au niveau 2, plutôt qu'un interrupteur global.** Un seul parcours, une seule surface de
test, la même puissance préservée à un clic de distance (jamais retirée, juste repliée par défaut).

**Séquencement proposé (validé par les 3 experts sur ce point) :**
- **Phase 0 (1-3 jours, non négociable, indépendante du choix d'architecture)** : corriger le bandeau
  de cookies qui bloque le formulaire (en vérifiant d'abord s'il est même nécessaire pour un outil
  100% local/aucune collecte), les cibles tactiles 13px→24px+, le contraste du message de confiance
  et du lien de connexion, extraire le JS inline vers un fichier mis en cache, corriger les accents du
  seeder, ajouter les tests manquants pour `SavedPromptController`.
- **Phase 1** : nouvelle étape 1 "Que voulez-vous créer ?" avec cartes de tâches concrètes (rédiger,
  résumer, analyser, trouver des idées, apprendre...) au lieu du concept abstrait "Persona" en premier ;
  vocabulaire courant partout (zero-shot→"Réponse directe", etc., correction de la coquille "Chaîne of
  thought").
- **Phase 2** : divulgation progressive à 2 niveaux max, panneau "Afficher tous les réglages" toujours
  visible et clairement libellé (pas caché derrière une bascule de mode) ; aperçu compilé toujours en
  langage courant en premier.
- **Phase 3** : refonte performance complète (bundle, cache-control immutable sur les assets Vite,
  regroupement des 54 requêtes) + tests de non-régression sur les 4 destinations (ChatGPT/Claude/
  Perplexity/Gemini) avant mise en production.

**Option 5 (IA de pré-remplissage) : écartée par les 3 experts** (rupture de la promesse "100% local"
non demandée, ou heuristique locale risquée qui nuirait à la crédibilité).

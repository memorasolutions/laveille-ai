# Design - Stratégie de routage / cascade de modèles IA (laveille.ai)

Date : 2026-08-21 (America/Toronto). Club des sages 5 oracles (Perplexity, Codex, Gemini via agy, DeepSeek, claude.ai), round 1, convergence quasi unanime. Statut : DESIGN à approuver (HARD-GATE : aucune implémentation avant feu vert de Stéphane). Cartographie du réel : voir le rapport de session (état fragmenté : ~4 cascades séparées + ~12 services à modèle fixe, tout par défaut sur `openrouter/free` bridé, suivi des coûts inopérant).

## Problème
Le routage IA est fragmenté (4 implémentations de cascade sans code commun + ~12 appels à modèle fixe), pointe par défaut sur `openrouter/free` (gratuit, rate-limité, renvoie vide par intermittence), sans télémétrie coût/qualité. Objectif : une cascade UNIFIÉE, low-cost FIABLE (jamais gratuit) → escalade au besoin, par type de tâche, sans dégrader la perception ni le budget.

## Convergence du club (notes /100)
- **(a) UN moteur unifié** (`CascadeExecutor`/`AiRouter`) que TOUS les services appellent, piloté par des `TaskProfile` déclaratifs (stockés en base, éditables en admin) ; retourne un `Attempt` normalisé (contenu, tokens, coût, latence, palier, verdict du validateur). La cascade News est LE patron à généraliser (pas réécrire). Migration en **strangler** : les 12 services fixes d'abord (cas trivial, les instrumente gratuitement), modération ensuite, News en dernier (elle marche). [Codex 96, DeepSeek 95, claude.ai 92, Gemini 90]
- **(b) Escalade DÉTERMINISTE sur le contrat de sortie, JAMAIS sur l'auto-confiance du modèle** (mal calibré, « confiant dans l'erreur » - Codex 38, claude.ai rejet). 3 signaux par ordre de fiabilité : (1) échecs durs (vide/timeout/429/5xx/refus/JSON invalide) = gratuit, zéro faux positif, règle 100 % du problème `free` ; (2) violations de contrat par tâche (schéma, français QC, longueur plancher, URL de sources résolvables, niveau de preuve) ; (3) la difficulté a priori choisit le PALIER DE DÉPART, pas l'escalade. [Codex 98, claude.ai 88, DeepSeek 85]
- **(c) Réparer le suivi des coûts = sous-produit du moteur, PAS un chantier FinOps.** Une table `ai_calls` ; stocker le coût RAPPORTÉ par OpenRouter (usage accounting), jamais une table de prix maison (périmée en 3 semaines). Disjoncteur = plafond quotidien par tâche avec dégradation graduée (couper l'escalade avant le service, JAMAIS la modération). Réalité claude.ai : le budget est en dizaines de $/mois → la télémétrie sert à détecter l'ANOMALIE (boucle d'escalade, prompt qui double), pas à économiser. Une seule vue : taux d'escalade + coût par tâche. **Bannir `openrouter/free` en prod, aujourd'hui.** [DeepSeek 100, Codex 99, claude.ai 95, Gemini 95]
- **(d) « Meilleure IA à chaque fois » = illusion coûteuse sur la majorité des tâches.** Classification/extraction/résumé court/traduction : un bon petit modèle payant est indiscernable du gros pour le lecteur. Gain perceptible concentré sur la RÉDACTION LONGUE publiée + l'arbitrage de sources contradictoires + le frontal interactif (chatbot, tuteur). Ce que l'utilisateur perçoit vraiment : latence, absence de réponse vide, français québécois - jamais le nom du modèle. L'escalade DOUBLE la latence → à éviter sur le synchrone. [Codex 95, DeepSeek 90, Gemini 80, claude.ai 80]

## Idées neuves (angle /innovation)
1. **BANC D'ESSAI FIGÉ, rejouable en CLI, par tâche (claude.ai - le reframe).** 30-50 cas réels gelés avec leurs assertions (= les mêmes validateurs que la porte de qualité en ligne), rejoués contre N modèles → tableau qualité/coût/latence. Change le problème : on choisit les modèles EMPIRIQUEMENT au lieu de débattre d'architecture d'escalade. Si le palier bas passe le banc à ~97 %, l'escalade devient marginale et on n'a même plus besoin de télémétrie en ligne pour décider. Détecte aussi les régressions silencieuses quand OpenRouter change un routage sous le même nom de modèle. Déjà su faire (mesure du 27,7 % de déformation factuelle).
2. **Repli natif OpenRouter via `models: [A, B]` (Gemini).** L'API bascule seule si le 1er modèle échoue/est surchargé → repli de disponibilité à ZÉRO code PHP. (À vérifier : deny+zdr appliqués aux modèles de repli + remontée du coût du modèle réellement servi.)
3. **Réparer plutôt que régénérer (Codex).** Si un validateur échoue, le palier supérieur reçoit la 1re réponse et RÉPARE seulement les faiblesses détectées (moins cher/rapide qu'une régénération). Repartir de zéro seulement sur erreur factuelle grave.
4. **Retry du MÊME modèle avec prompt durci nommant la contrainte violée AVANT d'escalader de modèle (claude.ai).** Une bonne part des échecs vient du prompt, pas de la taille du modèle - ce palier intermédiaire coûte le dixième.

## Ordre d'exécution recommandé (fusion, arbitré)
Divergence consignée : Codex/DeepSeek/Gemini voulaient l'instrumentation d'abord ; claude.ai le banc d'essai d'abord. **Arbitrage : banc d'essai d'abord** (il de-risque TOUTES les décisions suivantes et peut rendre la cascade marginale), MAIS **retirer `openrouter/free` est fait en parallèle immédiatement** (correctif de sûreté indépendant).
1. **Retrait de `openrouter/free`** : basculer les 6 réglages `ai.*_model` vers un modèle économique payant fiable (gpt-4o-mini par défaut, la cascade News éprouvée). Correctif immédiat, débloque toutes les features IA. (Action de config, réversible.)
2. **Banc d'essai figé par tâche** (CLI) : 30-50 cas réels + validateurs, rejoués contre 2-3 modèles candidats par tâche → tableau qualité/coût/latence. Choisit empiriquement le modèle par tâche.
3. **Moteur unifié `CascadeExecutor` + `TaskProfile`** (généralise la cascade News), migration strangler (12 fixes → modération → News). Instrumentation coût/tokens = sous-produit (table `ai_calls`, coût rapporté par OpenRouter).
4. **Repli de disponibilité** via `models: [A, B]` natif OpenRouter (après vérif deny+zdr).
5. **Escalade qualité EN DERNIER**, seulement là où le banc montre que le palier bas ne suffit pas : validateurs déterministes → retry prompt durci → réparation ciblée. Réservée au frontal + rédaction longue + arbitrage de sources.

## Explicitement écarté (motivé)
- Auto-confiance du modèle comme décideur d'escalade (mal calibré).
- Juge LLM systématique sur chaque réponse (double coût/latence, mêmes biais) - réservé à l'échantillonnage hors ligne.
- Table de prix maison (périmée) - utiliser le coût rapporté par OpenRouter.
- Escalade sur les tâches de fond asynchrones (delta imperceptible, latence pire).

## Critères d'acceptation (par phase, à valider)
Ph1 : plus aucune feature IA ne renvoie vide par intermittence (les 6 réglages ≠ free). Ph2 : un tableau qualité/coût/latence par tâche existe et est rejouable. Ph3 : un seul moteur, tous les services le passent, `ai_calls` enregistre coût réel. Ph4/5 : repli et escalade actifs seulement où le banc le justifie, taux d'escalade sous surveillance.

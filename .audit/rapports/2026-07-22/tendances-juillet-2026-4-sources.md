# Tendances juillet 2026 - suggestions validées par 4 sources

Sources : `pp_search` (Perplexity, recherche brute) → `mcp__superagent__codex` (validation indépendante,
recherche web propre) → `agy` Gemini 3.1 Pro High (2e avis indépendant, sceptique) → arbitrage final
Claude (synthèse, "claude.ai" = jugement de synthèse plutôt qu'une session distincte pour se parler à
soi-même, sans valeur ajoutée réelle).

## Tableau final arbitré

| # | Piste | Perplexity (thème) | Codex | Gemini | **Final (Claude)** | Verdict |
|---|---|---|---|---|---|---|
| 1 | Restructuration AEO/GEO du contenu éditorial (réponses directes, FAQ conversationnelles, tableaux) | Confirmé tendance forte | 86 | 90 | **88** | **Go, priorité haute** |
| 2 | Personnalisation IA de la newsletter (segmentation, timing prédictif, contenu interactif) | Confirmé tendance | 58 | 40 | **45** | **Non, disproportionné** |
| 3 | Radar d'innovation / stack builder pour l'annuaire d'outils IA | Confirmé tendance | 82 | 88 | **85** | **Go, priorité moyenne-haute** |
| 4 | Tuteur pédagogique IA enrichi (parcours adaptatifs) | Confirmé tendance | 64 | 50 | **55** | **Non maintenant, expérimenter petit** |
| 5 | Intégrations calendrier (ICS/Google/Outlook) pour Décido | Confirmé tendance | 78 | 85 | **82** | **Go, priorité moyenne** |
| 6 | Gouvernance automatisée de fraîcheur/confiance du contenu (dates de vérification, badges révisé humainement, alertes liens morts) | — (trouvé par Codex) | 92 | 95 | **93** | **Go, priorité #1** |
| 7 | Générateur de charte IA conforme Loi 25 (outil gratuit, PDF, lead magnet) | — (trouvé par Gemini) | — | (implicite, proposée) | **80** | **Go, priorité haute (différenciateur unique)** |

## Justification de l'arbitrage final

**#6 - Gouvernance de fraîcheur (93/100, priorité #1).** Les deux IA convergent fortement (92 vs 95) et
c'est la seule piste qui renforce TOUTES les autres surfaces du site à la fois (crédibilité éditoriale,
AEO/GEO, annuaire, académie) pour un coût technique faible (tâche planifiée Laravel existante + champs
de métadonnées). Différenciateur concret face au contenu généré en masse par l'IA en 2026 : un badge
« vérifié humainement » a une vraie valeur de confiance.

**#1 - AEO/GEO (88/100).** Convergence forte (86/90). Déjà partiellement en place sur laveille.ai
(sections AEO, JSON-LD, answer-box) - il s'agit d'étendre systématiquement le pattern aux pages à fort
trafic plutôt que de tout reconstruire. Risque signalé par Codex (sur-remplissage FAQ, Google a retiré
les rich results FAQ) à respecter : qualité avant quantité, mesurer les citations IA réelles.

**#7 - Générateur de charte IA Loi 25 (80/100, nouveau).** Idée de Gemini non identifiée par Perplexity
ni Codex, mais particulièrement pertinente : exploite directement le positionnement légal/conformité
déjà fort du site (Privacy module RGPD/Loi 25 déjà mature), coût de développement faible (formulaire +
génération PDF), effet de capture de leads B2B réel. Noté un cran sous le duo #1/#6 car c'est une
NOUVELLE surface produit (pas une amélioration d'existant), donc effort de conception plus élevé.

**#3 - Radar d'innovation annuaire (85/100).** Convergence forte (82/88). Le vrai risque (signalé par
les deux IA) est la charge de maintenance éditoriale du score de fraîcheur - à coupler avec #6
(gouvernance) pour l'automatiser plutôt que de la faire manuellement.

**#5 - Calendrier Décido (82/100).** Convergence forte (78/85), les deux qualifient ça de
quasi-prérequis en 2026 pour un outil de sondage collectif. Commencer par de simples fichiers `.ics`
(faible risque, gain immédiat) avant d'envisager OAuth Google/Outlook (complexité et enjeux de vie
privée nettement plus élevés).

**#2 - Personnalisation newsletter IA (45/100, refusé).** Désaccord notable entre Codex (58) et Gemini
(40, -18 pts) - les deux s'accordent cependant sur le fond : disproportionné pour une équipe d'une
personne et un volume d'abonnés limité (51 abonnés selon la mémoire projet), et frictions Loi 25 sur le
profilage comportemental. Gemini est plus catégorique ("usine à gaz"), ce qui correspond à la réalité
opérationnelle de ce projet - retenu comme le verdict final.

**#4 - Tuteur IA enrichi (55/100, différé).** Désaccord notable (64 vs 50, -14 pts). Les deux
s'accordent : ne PAS refondre le tuteur existant maintenant, mais possiblement expérimenter une
fonctionnalité étroite (quiz auto-générés sur UN cours pilote) avant d'investir davantage. Risque
d'hallucination/garde-fous jugé disproportionné par rapport au bénéfice pour une équipe d'une personne.

## Ordre de priorité recommandé (si l'utilisateur souhaite exécuter)

1. Gouvernance de fraîcheur/confiance du contenu (#6) - fondation qui sert tout le reste
2. Extension systématique AEO/GEO aux pages à fort trafic (#1)
3. Générateur de charte IA Loi 25 (#7) - nouveau produit différenciant
4. Radar d'innovation annuaire (#3) - dépend de #6 pour l'automatisation
5. Intégrations calendrier ICS pour Décido (#5)
6. Newsletter IA (#2) et tuteur enrichi (#4) - non retenus pour l'instant, à revisiter si le volume
   d'abonnés/apprenants croît significativement

**Aucune de ces pistes n'a été implémentée** - ce sont des suggestions notées et validées, en attente
d'une décision de l'utilisateur sur lesquelles (et dans quel ordre) développer.

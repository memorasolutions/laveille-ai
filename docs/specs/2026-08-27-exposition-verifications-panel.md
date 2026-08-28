# Exposition de /verifications : décision tranchée par panel

Document produit le 27 août 2026, 10h47 Québec (14:47 UTC).
Auteur : MEMORA solutions (info@memora.ca, https://memora.solutions).

## Composition du panel et lacune assumée

Cinq oracles étaient prévus. Trois seulement étaient joignables dans cette session :

- Codex, interrogé via l'outil superagent codex.
- DeepSeek (deepseek-r1), interrogé via Hermes en tâche de raisonnement.
- Gemini 3.1 Pro (High), interrogé via la commande `agy` en ligne de commande.

Deux oracles étaient hors de portée et ne l'ont pas caché : Perplexity (serveur MCP absent de cette session) et claude.ai (nécessite un navigateur, lui-même absent de cette session). Le panel est donc à trois voix sur cinq. Ceci n'est pas une unanimité à cinq déguisée : c'est une consultation à trois, dont les conclusions doivent être lues avec cette limite en tête, en particulier sur les questions où Perplexity aurait pu apporter une vérification de terrain (fréquentation de pages de fact-checking comparables ailleurs) et où claude.ai aurait pu apporter un point de vue supplémentaire sur la formulation éditoriale.

## Contexte factuel, tel que mesuré et fourni au panel

Le site laveille.ai est une veille québécoise en intelligence artificielle tenue par une seule personne, sans équipe de modération. Un module de vérification fonctionne déjà : cinq verdicts possibles (contexte manquant, citation inexacte, présentation trompeuse, attribution erronée, contenu synthétique), un balisage machine ClaimReview, et une page publique à l'adresse /verifications.

Chiffres mesurés aujourd'hui :

- 4 573 fiches publiées et vivantes.
- 175 fiches portent une rédaction riche.
- 9 fiches seulement portent un verdict de vérification.
- La page /verifications répond correctement, mais liste dix fiches, un écart d'une unité avec le compte de neuf verdicts que Codex a relevé de lui-même au round 1 sans qu'on le lui demande. Cet écart n'a pas été investigué techniquement dans le cadre de cet exercice (ce document est une consultation de panel, pas un audit de code); il est signalé ici comme point à vérifier avant toute mise en avant de la page.
- La page n'est liée depuis nulle part : zéro lien depuis l'accueil, zéro depuis /actualites.

Sur la fréquentation, l'indice fourni au panel est resté volontairement présenté comme faible : sur trois jours, les trois fiches d'actualité en tête sont des mises au point, avec des durées de lecture de deux à sept minutes, mais pour un total de 24 vues seulement, et ces mêmes fiches sont aussi les plus récentes et les plus partagées sur les réseaux, ce qui suffit à expliquer leur position sans rien devoir à leur angle éditorial. Le référencement ne rapporte rien : aucune de ces fiches n'est encore indexée, zéro clic depuis les moteurs.

L'intuition soumise à l'épreuve du panel : "on doit viser les démentis, les gens aiment que quelqu'un fasse le ménage des nouvelles pour eux". Le risque soumis en parallèle : chercher du démenti à tout prix produit des verdicts forcés, contre la règle éditoriale en vigueur ("dans le doute, pas de verdict", "le mot juste, jamais le mot fort") et contre le principe d'anti-amplification (ne jamais mettre le faux en vedette).

## Méthode : deux rounds, mandat de démolition

**Round 1, en aveugle.** Les trois oracles ont reçu exactement le même texte de contexte et les deux mêmes questions, chacun sans voir les réponses des autres.

**Round 2, réfutation croisée.** Chaque oracle a reçu les trois propositions du round 1, identifiées par lettre et par nom, avec la consigne explicite suivante, transmise mot pour mot : "tes propres idées sont marquées, ne les épargne pas". Chaque oracle devait attaquer sa propre proposition du round 1 aussi durement que celles des deux autres, réviser sa note, et proposer une idée neuve absente des trois listes.

Aucun chiffre d'étude externe n'a été avancé par aucun des trois oracles dans les deux rounds. Là où un oracle a formulé un seuil ou un pourcentage de son cru (par exemple "25 vérifications et six mois", ou "75 % de taux de conversion"), il l'a lui-même étiqueté "non sourcé" dans sa réponse; ce document conserve cette étiquette plutôt que de la faire disparaître. Aucun résultat d'étude fabriqué n'a été détecté dans cette consultation, contrairement à un incident déjà survenu sur ce projet avec un autre oracle.

## Round 1 : les trois propositions en aveugle

**Proposition A, Codex.** Emplacement : filtre "Vérifications (10)" en tête de /actualites, verdict visible et cliquable sur les fiches concernées, aucune entrée de navigation principale avant 25 vérifications et six mois de régularité (seuil non sourcé). Rythme : deux sujets candidats examinés par semaine, un seul verdict publié par semaine au maximum, aucun minimum imposé. Garde-fou : une grille à sept conditions cochées avant tout verdict, plus un second verrou statistique (pause automatique si le taux de conversion candidats vers verdicts dépasse 75 % sur huit candidats consécutifs). Note que Codex s'est donnée à lui-même : 84 sur 100.

**Proposition B, DeepSeek.** Emplacement : filtre "Vérifications (9)" en tête de /actualites, sans navigation principale ni encart d'accueil. Rythme : une à deux vérifications par mois au maximum. Garde-fou : une grille à trois cases, plus un quota strict limitant les vérifications à 10 % des fiches en rédaction riche publiées dans le mois (soit environ 0,5 vérification par mois au rythme actuel de rédaction riche). Note que DeepSeek s'est donnée à elle-même : 75 sur 100.

**Proposition C, Gemini.** Emplacement : un encart contextuel dynamique "Vérifications récentes" limité à trois éléments, plus un simple filtre sur /actualites, sans entrée de navigation principale. Rythme : une vérification toutes les deux semaines, au grand maximum une par semaine. Garde-fou : deux cases obligatoires avant tout verdict (la fiche garde une valeur informative même sans verdict formel; la rumeur visée a déjà franchi un seuil d'audience prouvé par un lien source). Note que Gemini s'est donnée à elle-même : 85 sur 100.

Un accord unanime existe déjà à ce stade, sans qu'aucun round de démolition n'ait été nécessaire pour l'obtenir : aucun des trois oracles n'a recommandé une entrée de navigation principale dans l'état actuel du site.

## Round 2 : ce que la démolition a fait ressortir

### Notes révisées après réfutation croisée

| Proposition | Note round 1 (auto-évaluation) | Note Codex (round 2) | Note DeepSeek (round 2) | Note Gemini (round 2) | Moyenne round 2 |
|---|---|---|---|---|---|
| A, Codex (grille à 7 conditions) | 84 | 63 (propre idée) | 70 | 60 | 64,3 |
| B, DeepSeek (quota lié au volume) | 75 | 39 | 60 (propre idée) | 45 | 48,0 |
| C, Gemini (encart dynamique) | 85 | 55 | 75 | 50 (propre idée) | 60,0 |

Chaque oracle a fait baisser sa propre note plus qu'il n'a fait baisser celle des autres, à l'exception notable de DeepSeek envers la proposition C : ce point est repris plus bas comme divergence.

### La faille la plus sérieuse retenue pour chaque proposition

**Proposition A.** Les trois oracles s'accordent : la grille à sept conditions plus le verrou statistique est une charge bureaucratique disproportionnée pour un rédacteur seul. Gemini nomme le mécanisme précisément : "le syndrome de la case à cocher", où un rédacteur fatigué coche par habitude plutôt que par jugement. Le verrou des 75 % peut être neutralisé en ajoutant délibérément des candidats faibles pour faire baisser artificiellement le ratio, ce que Codex reconnaît lui-même contre sa propre proposition, et que Gemini confirme indépendamment.

**Proposition B.** Les trois oracles convergent sur le même diagnostic, formulé indépendamment par Codex ("aucune relation éditoriale logique") et par Gemini ("une erreur de raisonnement") : indexer le nombre de vérifications autorisées sur le volume de fiches en rédaction riche n'a pas de justification éditoriale. Rien ne dit que ces deux quantités varient pour la même raison. Codex illustre l'absurdité arithmétique : au rythme donné, atteindre vingt vérifications prendrait environ vingt-deux mois. Le critère "trois sources tierces influentes" est jugé gameable par les trois oracles, y compris par DeepSeek envers sa propre proposition, faute de définition de ce qu'est une source influente.

**Proposition C.** Ici le panel diverge fortement, voir section suivante. Codex et Gemini (envers sa propre proposition) jugent l'encart risqué : avec seulement neuf fiches, un encart limité à trois éléments tournera en boucle sur les mêmes contenus pendant des semaines, ce qui crée une impression de stagnation plutôt que de dynamisme, et le garde-fou des deux cases est jugé "une formalité, pas une barrière" par Gemini elle-même, un lien vers un fil de discussion peu actif suffisant techniquement à cocher la case d'audience. DeepSeek, à l'inverse, remonte la note de cette proposition à 75, la jugeant "meilleure idée d'emplacement dynamique" du lot.

## Divergences conservées, non moyennées

**Divergence 1 : l'encart dynamique est-il un moteur de découverte ou un gadget qui tourne à vide?**
Position Codex et Gemini : avec neuf fiches seulement, un encart de trois éléments recycle le même contenu pendant des semaines, ce qui nuit à la crédibilité plutôt que d'y contribuer; Gemini qualifie sa propre idée de "proposition gadget" en round 2.
Position DeepSeek : l'encart reste la meilleure idée de placement du lot (75 sur 100 en round 2, la note la plus haute attribuée à une proposition d'un autre oracle dans tout l'exercice), car il pousse l'information vers le visiteur sans exiger de recherche active.
Arbitrage retenu dans ce document : la position Codex et Gemini est privilégiée, non pas parce qu'elle est majoritaire deux contre un, mais parce que l'auteure même de la proposition (Gemini) l'a démontée avec un argument concret et vérifiable (rotation sur trois éléments avec un inventaire de neuf, donc répétition quasi certaine à courte échéance), argument auquel DeepSeek n'a apporté aucune réfutation directe dans sa réponse. Un encart dynamique redevient défendable une fois l'inventaire de vérifications suffisamment large pour ne pas se répéter, ce qui est un seuil quantitatif observable plus tard, pas maintenant.

**Divergence 2 : une contradiction interne, à signaler telle quelle plutôt qu'à corriger silencieusement.**
Dans sa réponse de round 2, DeepSeek classe son propre classement final comme suit : 1) proposition C, 2) proposition A, 3) proposition B, 4) sa propre idée neuve (le badge "Vérifié"). Mais dans la même réponse, sa justification écrite affirme que "l'idée neuve > B et A", ce qui contredit directement le rang 4 attribué deux lignes plus haut à cette même idée neuve, classée derrière B et A. Ce document ne corrige pas DeepSeek à sa place : il consigne l'incohérence et retient, pour la suite de l'analyse, le contenu de l'argumentation plutôt que le classement numéroté manifestement fautif, puisque l'idée du badge est reprise et jugée solide indépendamment de ce classement.

**Divergence 3 : quel rythme de production, une fois le principe du quota-volume de la proposition B écarté.**
Codex maintient une cadence structurée (deux candidats examinés par semaine, un verdict publié au plus par semaine). Gemini défend un rythme plus lent (une vérification toutes les deux semaines) comme "le plus réaliste pour une personne seule", position qu'elle maintient dans son propre classement final malgré la baisse de note de sa proposition. Aucun arbitrage numérique tranché n'est possible ici sans donnée réelle de charge de travail du rédacteur, que le panel n'a pas mesurée. Ce document retient la position la plus prudente (Gemini) comme point de départ, avec révision possible à la hausse après un trimestre d'observation réelle, plutôt que d'imposer d'emblée le rythme le plus soutenu.

## Les trois idées neuves nées de la démolition

**Codex** propose : un lien discret et permanent vers /verifications dans le pied de page du site, plus des liens contextuels automatiques depuis les fiches qui partagent la même entité ou la même affirmation qu'une fiche vérifiée, sans encart automatique et sans cadence minimale imposée. Garde-fou associé : un registre interne de tous les candidats examinés, y compris les décisions d'abstention ("aucun verdict"), audité par échantillon aléatoire.

**DeepSeek** propose : un badge "Vérifié" cliquable, affiché directement sur les fiches concernées, redirigeant vers /verifications, et n'affichant que le verdict lui-même (par exemple "présentation trompeuse") sans répéter l'affirmation fausse, ce qui respecte la règle d'anti-amplification par construction plutôt que par discipline.

**Gemini** propose l'idée la plus structurellement forte du round 2 : un statut public "en observation" ou "dossier ouvert", qui permet d'exposer publiquement qu'une rumeur ou une affirmation est sous examen, sans jamais obliger à trancher vers l'un des cinq verdicts. Cette proposition ne se contente pas d'ajouter une couche de contrôle autour de la même décision binaire verdict ou silence : elle retire la pression elle-même en créant une troisième issue légitime et publiquement visible. Elle répond directement à la question posée en amont ("quel garde-fou concret empêche cette dérive, autrement qu'en s'en remettant à la bonne volonté du rédacteur") d'une façon qu'aucune grille de conditions ne peut égaler, puisqu'une grille reste contournable par lassitude ou par définition trop souple d'un critère, alors qu'un statut "en observation" change la finalité même du travail d'examen : montrer la rigueur du processus ne dépend plus de la production d'un verdict.

## Options finales, notées et classées

**Rang 1, recommandation combinée (synthèse de ce document) : 82 sur 100.**
Emplacement : filtre "Vérifications" en tête de /actualites (convergence des trois oracles au round 1) plus un badge cliquable sur les fiches individuellement concernées (idée de DeepSeek, round 2) plus un lien discret et permanent dans le pied de page du site (idée de Codex, round 2). Aucune entrée de navigation principale, aucun encart dynamique d'accueil pour l'instant, conformément à la divergence 1 tranchée plus haut. Rythme : aucun objectif chiffré de production; le volume suit les sujets qui le méritent réellement, avec un plafond prudent d'une vérification toutes les deux à quatre semaines le temps d'observer une charge réelle sur un trimestre (divergence 3). Garde-fou principal : le statut public "en observation" ou "dossier ouvert" de Gemini, qui retire la pression de conclure en créant une issue publique légitime autre que le silence complet ou le verdict tranché. Garde-fou secondaire : le registre interne audité de Codex, qui rend visibles et traçables les décisions de ne pas trancher, empêchant qu'elles disparaissent sans laisser de trace.
Justification de la note : cette option combine les trois éléments que le panel n'a pas réussi à démolir dans sa propre démolition (le filtre, le badge, le statut d'observation), et écarte explicitement les deux éléments que le panel a démolis avec les arguments les plus concrets et les moins contestés (le quota lié au volume de rédaction riche de la proposition B, unanimement jugé illogique; l'encart dynamique de la proposition C, démonté par sa propre auteure). Les 18 points retirés reflètent l'absence de tout test réel : cette combinaison n'a été ni mesurée, ni éprouvée sur le terrain, et reste une synthèse de panel, pas une observation.

**Rang 2, proposition A, Codex (grille à sept conditions) : 64 sur 100 (moyenne des trois notes de round 2).**
Solidité analytique reconnue par les trois oracles (seule proposition à exiger une source primaire et une hypothèse concurrente), mais jugée trop lourde pour un rédacteur seul, avec un verrou statistique contournable. Utilisable comme version renforcée si Stéphane préfère un cadre plus formel malgré le risque de lassitude documenté par Gemini.

**Rang 3, proposition C, Gemini (encart dynamique) : 60 sur 100 (moyenne des trois notes de round 2, avec la divergence de DeepSeek à 75 conservée et non lissée).**
Idée d'emplacement jugée séduisante par DeepSeek, mais démontée par sa propre autrice pour un motif concret (rotation sur trois éléments avec neuf fiches en tout). À revisiter une fois l'inventaire de vérifications plus large, pas maintenant.

**Rang 4, proposition B, DeepSeek (quota lié au volume de rédaction riche) : 48 sur 100 (moyenne des trois notes de round 2).**
La proposition la plus fragile de tout l'exercice. Deux oracles indépendants (Codex et Gemini) sont arrivés au même diagnostic par des chemins différents : lier le nombre de vérifications autorisées à un indicateur de production sans rapport éditorial est une erreur de raisonnement, pas seulement un choix prudent. Non recommandée telle quelle.

## Ce que ce document ne peut pas garantir

Ce texte est une synthèse de panel à trois voix sur cinq, pas une mesure de terrain. N'ont pas pu être vérifiés dans le cadre de cet exercice : l'écart entre neuf verdicts et dix fiches listées sur /verifications; la charge de travail réelle d'un rythme d'une vérification toutes les deux à quatre semaines pour un rédacteur seul; l'effet réel d'un badge ou d'un lien de pied de page sur la fréquentation, faute de trafic significatif actuel sur la page; l'avis de Perplexity et de claude.ai, absents de cette session. La recommandation de rang 1 devrait être revue après un trimestre d'observation réelle, pas appliquée comme un verdict définitif du panel lui-même.

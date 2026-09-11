# Mesure réelle du taux de faux - onglet vidéos du glossaire

**Date** : 2026-09-11
**Mandat** : remplacer le débat des cinq oracles (25 % à plus de 95 %, tous retirés au second tour) par une mesure. Aucune écriture en base, aucun commit, aucune modification du projet - ce document est le seul livrable.
**Suite de** : `2026-09-11-glossaire-brief-factuel.md`, `2026-09-11-glossaire-panel-round1.md`, `2026-09-11-glossaire-panel-round2.md`.

---

## 1. Méthode

### 1.1 Source des termes

161 fiches publiées, table `dictionary_terms` (`Modules/Dictionary`), extraites par requête SQL directe sur la base locale `la_veille_de_stef_v2` (nom, `match_strategy`, `type`, alias, définition). Pas de recours à la production - base locale, en clair comme demandé si jamais elle avait dû servir de repli.

### 1.2 Lecture du pipeline réel avant simulation

Fichiers lus intégralement avant toute recherche :
- `Modules/Directory/app/Services/YouTubeService.php`
- `Modules/Directory/app/Console/EnrichTutorialsCommand.php`

Ce que fait réellement le mécanisme (pour un OUTIL de l'annuaire, pas encore pour un terme - c'est justement ce qu'on simule) :
1. **Requête** : `"{nom} tutoriel"` (passe FR) puis `"{nom} tutorial"` (passe EN de repli si la passe FR ne fournit pas assez de résultats). Paramètres API réels : `order=viewCount`, `publishedAfter` = 36 mois, `relevanceLanguage=fr`, `regionCode=CA` (passe FR).
2. **Filtre `scoreAndFilter`** : vues ≥ 1000 (FR) / ≥ 5000 (EN), durée entre 180 et 7200 secondes, exclusion de mots-clés « poubelle » (gameplay, no commentary, full game, speedrun, lyrics, short movie, music video, official trailer), garde de langue (rejette les scripts non latins et une liste de marqueurs ES/PT/DE/IT/ID), et surtout : **`str_contains(titre_en_minuscules, nom_en_minuscules)`** - le titre doit contenir le nom littéral de l'outil.
3. **Score et tri** : 40 % vues normalisées + 30 % ratio de mentions « j'aime » + 30 % fraîcheur, puis les résultats en français sont priorisés, puis on retient les 5 meilleurs.

### 1.3 Simulation

Recherche via `mcp__youtube__youtube_search(query, limit=15)` pour chacun des 30 termes, avec la requête construite EXACTEMENT comme le ferait le pipeline (`"{nom exact en base} tutoriel"`). Puis application manuelle des filtres ci-dessus sur les champs que l'outil retourne (titre, chaîne, durée, vues) :
- Substring du nom : appliqué tel que codé, sur le nom canonique exact tiré de la base.
- Durée 180-7200 s, vues ≥ 1000 : appliqués.
- Mots-clés poubelle et garde de langue (scripts non latins + liste de marqueurs) : appliqués sur le titre.
- Tri des survivants par vues décroissantes (proxy de `order=viewCount`), 5 premiers retenus.

**Écart documenté et nécessaire** : 8 des 30 termes ont un nom canonique à suffixe parenthétique bilingue (ex. `Perplexité (perplexity)`, `Latence (latency)`). Le filtre `str_contains` codé pour un OUTIL (dont le nom n'a jamais de parenthèse) exigerait alors la chaîne littérale `"perplexité (perplexity)"` dans un titre - ce qui n'arrive jamais. Pour pouvoir juger le contenu quand il existe, j'ai assoupli la règle sur CES 8 termes seulement : le titre doit contenir soit la partie avant la parenthèse, soit la partie entre parenthèses. Cette adaptation est signalée à chaque tableau et son effet est mesuré séparément en section 4.

### 1.4 Limites honnêtes

- **Jugement sur titre + chaîne seulement.** L'outil `youtube_search` ne renvoie ni description, ni date de publication, ni nombre de « j'aime », ni langue audio détectée. Le jugement PERTINENT/FAUX/DOUTEUSE repose donc sur moins d'information que ce que prévoyait l'énoncé (« titre, chaîne ET description ») - c'est une limite réelle, pas un choix.
- **Pas de reproduction exacte de l'API réelle.** `order=viewCount`, `publishedAfter` (36 mois), `regionCode=CA`, `relevanceLanguage=fr` ne sont pas des paramètres du outil MCP utilisé. J'ai compensé le tri par vues décroissantes ; je n'ai pas pu appliquer la fenêtre de fraîcheur de 36 mois ni le biais Québec/FR - les résultats peuvent inclure des vidéos plus anciennes ou plus orientées vers un public non québécois que ne le ferait l'API réelle.
- **Une seule passe interrogée (celle en `tutoriel`).** La passe EN de repli (`tutorial`, seuil 5000 vues) n'a pas été lancée séparément par économie ; l'outil simplifié renvoie de toute façon un mélange FR/EN dès la première requête (contrairement à l'API réelle contrainte par `relevanceLanguage=fr`), ce qui capture une partie de ce que la passe EN aurait apporté.
- **Cible de 150 jugements non atteinte : 104 jugements utilisables.** Pour 3 termes, le filtre mécanique ne laisse survivre AUCUN résultat (0/5) ; pour beaucoup d'autres, il en laisse moins de 5. Ce n'est pas un manque de rigueur - **c'est en soi le résultat le plus important de la mesure** (section 4).
- **Deux jugements de contenu, assumés** : les vidéos audio/jeu vidéo/LLM sur la « latence » ont été comptées PERTINENTES (applications légitimes du concept général de délai, pas un autre sens du mot) ; deux vidéos en italien correctement appariées sur le sujet (Google Antigravity, Mistral) ont été comptées PERTINENTES au sens thématique même si la garde de langue du code réel aurait dû les rejeter et n'y est pas arrivée (voir section 4.5).

---

## 2. Les 30 termes, par strate

### Strate A - termes ambigus (10)
Hub, Socket, Windows, Perplexité (perplexity), Époque (epoch), Algorithme, Batch (lot d'entraînement), Cheval de Troie, Docker, Latence (latency).

### Strate B - termes techniques composés (10)
Descente de gradient, Fonction de perte, Recherche sémantique, Similarité cosinus, Matrice de confusion, Effondrement de modèle (model collapse), Informatique en périphérie (edge computing), Confiance zéro (zero trust), Interface PAM (Pluggable Authentication Modules), LLM-as-a-judge.

### Strate C - noms propres (10)
Anthropic, WorkOS, Palisade Research, Z.ai, Greg Brockman, OpenAI Codex, Google Antigravity, Mistral, RedLine Stealer, Jan.ai.

---

## 3. Le chiffre

### 3.1 Tableau global par strate

| Strate | Jugements (N) | Pertinents | Faux | Douteux | **Taux de faux** |
|---|---|---|---|---|---|
| A - ambigus | 47 | 23 | 23 | 1 | **48,9 %** |
| B - techniques composés | 24 | 24 | 0 | 0 | **0 %** |
| C - noms propres | 33 | 33 | 0 | 0 | **0 %** |
| **Total** | **104** | **80** | **23** | **1** | **22,1 %** |

Cible initiale : 150 jugements (30 × 5). Réalisé : 104. L'écart n'est pas un échantillon incomplet par négligence - c'est la conséquence directe et mesurée du filtre mécanique du pipeline réel (section 4).

### 3.2 Détail terme par terme

**Strate A**

| Terme | N/5 | Pertinent | Faux | Douteux | Taux de faux |
|---|---|---|---|---|---|
| Hub | 5 | 0 | 5 | 0 | 100 % |
| Socket | 5 | 1 | 4 | 0 | 80 % |
| Windows | 5 | 4 | 1 | 0 | 20 % |
| Perplexité (perplexity) | 5 | 0 | 5 | 0 | 100 % |
| Époque (epoch) | 5 | 0 | 5 | 0 | 100 % |
| Algorithme | 5 | 4 | 0 | 1 | 0 % |
| Batch (lot d'entraînement) | 2 | 0 | 2 | 0 | 100 % (N court) |
| Cheval de Troie | 5 | 4 | 1 | 0 | 20 % |
| Docker | 5 | 5 | 0 | 0 | 0 % |
| Latence (latency) | 5 | 5 | 0 | 0 | 0 % |

**Strate B**

| Terme | N/5 | Pertinent | Faux | Taux de faux |
|---|---|---|---|---|
| Descente de gradient | **0** | - | - | non mesurable |
| Fonction de perte | 2 | 2 | 0 | 0 % (N court) |
| Recherche sémantique | 2 | 2 | 0 | 0 % (N court) |
| Similarité cosinus | 1 | 1 | 0 | 0 % (N très court) |
| Matrice de confusion | 2 | 2 | 0 | 0 % (N court) |
| Effondrement de modèle (model collapse) | **0** | - | - | non mesurable |
| Informatique en périphérie (edge computing) | 4 | 4 | 0 | 0 % |
| Confiance zéro (zero trust) | 5 | 5 | 0 | 0 % |
| Interface PAM (...) | 3 | 3 | 0 | 0 % (N court) |
| LLM-as-a-judge | 5 | 5 | 0 | 0 % |

**Strate C**

| Terme | N/5 | Pertinent | Faux | Taux de faux |
|---|---|---|---|---|
| Anthropic | 1 | 1 | 0 | 0 % (N très court) |
| WorkOS | 4 | 4 | 0 | 0 % |
| Palisade Research | **0** | - | - | non mesurable |
| Z.ai | 3 | 3 | 0 | 0 % |
| Greg Brockman | 3 | 3 | 0 | 0 % |
| OpenAI Codex | 5 | 5 | 0 | 0 % |
| Google Antigravity | 5 | 5 | 0 | 0 % |
| Mistral | 5 | 5 | 0 | 0 % |
| RedLine Stealer | 4 | 4 | 0 | 0 % |
| Jan.ai | 3 | 3 | 0 | 0 % |

### 3.3 Ce que dit ce chiffre, contre l'intuition des oracles - et la mienne

Le taux de faux global mesuré (22 %) se situe **au bas de la fourchette des cinq oracles** (25 % à plus de 95 %), pas au milieu et certainement pas au sommet. Et il n'est PAS uniforme : il est concentré à 100 % dans la strate A, où il grimpe à 49 %.

Plus surprenant : à l'intérieur même de la strate A, la variance terme-par-terme est extrême (0 % à 100 %) et **ne suit pas l'intuition d'ambiguïté qui a servi à construire l'échantillon**. J'avais moi-même choisi « Docker », « Cheval de Troie », « Mistral » et « Latence » en anticipant un risque élevé (docker = métier portuaire, cheval de Troie = mythologie, Mistral = vent, latence = incubation d'une maladie). Mesuré : ZÉRO faux pour ces quatre termes. Le facteur qui prédit vraiment le taux de faux n'est pas « le mot est commun », c'est **« un homonyme plus gros (marque, jeu vidéo, produit, événement historique) domine-t-il YouTube pour cette chaîne de caractères »** - et cela se vérifie cas par cas, pas par intuition sur le mot :
- **Hub** (100 % faux) : écrasé par Minecraft (Nether Hub), Azure Event Hub, HubSpot.
- **Perplexité (perplexity)** (100 % faux) : écrasé par le produit Perplexity AI (moteur de recherche), qui n'a strictement rien à voir avec la métrique statistique définie par la fiche.
- **Époque (epoch)** (100 % faux) : écrasé par le jeu vidéo « Last Epoch », par Marvel Contest of Champions, par la Belle Époque parisienne, par une carabine de tir et une pédale de guitare de marque « Epoch ».
- **Docker, Latence, Cheval de Troie, Mistral, Algorithme, Windows** (0-20 % faux) : aucun homonyme dominant n'a émergé dans le top des résultats.

---

## 4. Le constat structurel - plus important que le taux de faux lui-même

La question posée était « quel taux de faux verrait-on », mais la mesure révèle un phénomène plus déterminant pour la décision : **le filtre mécanique du pipeline réel, appliqué à des noms de fiches de glossaire, ne renvoie souvent RIEN - pas un mauvais résultat, aucun résultat.** Un onglet vide n'est pas comptabilisé dans un « taux de faux », mais c'est un défaut de produit tout aussi réel.

### 4.1 Le nom à suffixe parenthétique : 8 sur 8

Les 8 termes de l'échantillon dont le nom canonique porte un suffixe entre parenthèses (Perplexité (perplexity), Époque (epoch), Batch (lot d'entraînement), Latence (latency), Effondrement de modèle (model collapse), Informatique en périphérie (edge computing), Confiance zéro (zero trust), Interface PAM (...)) ont un point commun : **sous le filtre `str_contains` strictement tel qu'écrit dans le code actuel (sans l'assouplissement documenté en 1.3), AUCUN de ces 8 termes n'aurait jamais retourné une seule vidéo** - aucun titre YouTube ne contient jamais littéralement la chaîne `"nom français (nom anglais)"`. Même avec l'assouplissement (accepter la partie avant OU dans la parenthèse), un terme sur les 8 (« Effondrement de modèle (model collapse) ») reste à zéro : les vidéos réellement pertinentes existent (« Dérive et effondrement des modèles », « L'effondrement des modèles détruit les progrès de l'IA ») mais utilisent le pluriel « modèles » là où le filtre exige le singulier exact « modèle ».

Ce motif de nommage (« Terme français (équivalent anglais) ») est répandu dans le glossaire - on le retrouve par dizaines dans le catalogue complet de 161 fiches (SLM, A2A, Garde-fous, Computer use, Deep research, Instruction tuning, Sycophancy, Reward hacking, CUDA, F1-score, DOM, VPN, Hameçonnage, Ver informatique, etc.). Réutiliser le module `YouTubeService` sans adaptation laisserait donc, par construction, l'onglet vide sur une fraction substantielle du glossaire - pas seulement les termes ambigus de la strate A.

### 4.2 Le désordre FR/EN sans même de parenthèse : Descente de gradient

« Descente de gradient » n'a pourtant aucune parenthèse. Sur 15 résultats retournés, TOUS sont d'excellents contenus pédagogiques (3Blue1Brown, StatQuest, IBM Technology) - et TOUS sont exclus par le filtre, parce qu'ils sont titrés « Gradient Descent » (anglais, ordre des mots inversé), jamais « Descente de gradient ». Résultat : 0 vidéo, alors que le sujet est l'un des mieux couverts de tout YouTube.

### 4.3 L'accord singulier/pluriel

« Fonction de perte » exclut mécaniquement « Fonctions de perte » (DigitalSreeni, CodeEmporium - deux excellents contenus). « Matrice de confusion » exclut « Matrices de confusion » (Udacity). Le filtre perd du contenu pertinent pour un « s ».

### 4.4 La ponctuation et l'espacement de marque

« Jan.ai » (avec point) exclut « Jan AI » (espace) et « JanAI » (soudé) - trois graphies pour le même produit, une seule reconnue. « Z.ai » exclut « Z AI » et « Z-AI ». « LLM-as-a-judge » (avec tirets) exclut la moitié de ses propres résultats titrés sans tirets (« LLM as a Judge », IBM Technology entre autres) - la moitié pile de son propre corpus est invisible pour une question de trait d'union.

### 4.5 La contiguïté stricte du nom

« Anthropic » n'a trouvé qu'UNE seule vidéo sur 15, parce que la quasi-totalité du contenu sur les produits d'Anthropic (Claude, Claude Code) ne nomme jamais l'entreprise dans le titre - y compris sur la chaîne YouTube officielle d'Anthropic elle-même. « OpenAI Codex » exclut « Codex OpenAI » (ordre inversé) et tout titre qui dit seulement « Codex ». Le produit est plus connu que l'entreprise, et le filtre cherche l'entreprise.

### 4.6 Bilan du taux de remplissage (indépendant du taux de faux)

| Strate | Résultats obtenus / possibles (10 termes × 5) | Taux de remplissage |
|---|---|---|
| A | 47 / 50 | 94 % |
| B | 24 / 50 | 48 % |
| C | 33 / 50 | 66 % |

La strate B (les termes techniques les plus représentatifs du cœur du glossaire IA) est celle qui remplirait le MOINS l'onglet - alors que c'est justement celle qui, lorsqu'elle retourne un résultat, ne se trompe JAMAIS (24 pertinents sur 24). Le risque pour les termes techniques composés n'est donc pas la vidéo fausse, c'est l'onglet vide.

---

## 5. Cinq exemples marquants de faux

1. **Hub** → *« THE NETHER HUB! | The Minecraft Guide - Tutorial Lets Play (Ep. 30) »* (674 492 vues, chaîne wattles). Propose un guide de construction dans Minecraft à la place du Hugging Face Hub (dépôt de modèles IA) que définit la fiche.
2. **Perplexité (perplexity)** → *« Learn 80% of Perplexity in under 10 minutes! »* (1 861 965 vues, Jeff Su). Propose un guide du produit Perplexity AI (moteur de recherche conversationnel) à la place de la métrique statistique qui mesure la « surprise » d'un modèle de langage - deux objets totalement distincts qui ne partagent qu'un nom.
3. **Époque (epoch)** → *« Paris 1900 : Les mystères du Paris de la Belle Époque révélés »* (365 847 vues, Notre Histoire). Propose un documentaire historique sur le Paris de 1900 à la place du passage complet d'un entraînement de modèle sur son jeu de données.
4. **Windows** → *« How To Tint Windows - Window Tinting For Beginners »* (2 054 969 vues). Propose un tutoriel de teinture de vitres d'auto/maison à la place du système d'exploitation Microsoft - preuve qu'même un nom de marque très reconnu n'est pas à l'abri du sens commun du mot.
5. **Socket** → *« WebSockets in 100 Seconds & Beyond with Socket.io »* (1 287 565 vues, Fireship) et *« Learn Socket.io In 30 Minutes »* (608 549 vues) occupent 2 des 5 premières places. Proposent la bibliothèque Socket.IO (communication temps réel en JavaScript) à la place du concept général de socket réseau (adresse IP + port) que définit la fiche - une technologie adjacente qui porte le même nom et écrase le sens générique dans les résultats les plus vus.

---

## 6. Conclusion chiffrée

- **Taux de faux global mesuré : 22,1 % (23 faux sur 104 jugements exploitables)** - au bas de la fourchette des cinq oracles (25-95+ %), pas au milieu.
- **Le faux est concentré à 100 % dans la strate A (ambigus) : 48,9 %.** Les strates B (techniques composés) et C (noms propres) affichent 0 % de faux sur tout ce qui a pu être jugé.
- **La variance à l'intérieur même de la strate A est le fait le plus utile** : le risque ne vient pas du mot commun en soi (Docker, Latence, Cheval de Troie, Mistral : 0-20 % de faux, contrairement à l'intuition qui les avait fait choisir) mais de la présence d'un homonyme plus gros et plus vu sur YouTube (Hub, Perplexité, Époque : 100 % de faux). Ce risque se vérifie terme par terme, il ne se déduit pas d'une liste de mots « à risque ».
- **Constat qui dépasse le mandat initial mais qui pèse autant sur la décision : réutiliser le module `YouTubeService` sans adaptation laisserait l'onglet vide, pas faux, sur une bonne part du glossaire.** Les 8 termes à suffixe parenthétique de l'échantillon (motif très répandu dans le catalogue de 161 fiches) auraient TOUS retourné zéro vidéo sous le filtre strict actuel ; un cas y reste à zéro même après assouplissement. La strate B, celle qui ne se trompe jamais, ne remplit que 48 % de sa cible.
- **Limite assumée** : jugement sur titre + chaîne seulement (pas de description, pas de date de publication réelle), un seul passage de requête (FR) sur les deux prévus par le pipeline réel, et un assouplissement documenté du filtre de sous-chaîne pour rendre les 8 termes parenthétiques jugeables.

# Panel round 3 (attaque des survivantes) - Onglet vidéos/tutoriels sur les fiches de glossaire (ticket #2431)

Document de recherche, lecture seule. Aucun fichier de code modifié. Ce round soumet aux cinq
oracles les six idées ayant survécu au round 2 (`docs/specs/2026-09-11-glossaire-panel-round2.md`),
marquées par oracle, avec mandat de démolir ce qui a tenu - une note haute au round 2 signifiant
surtout que l'idée a été moins attaquée, pas qu'elle est validée. Trois questions décidaient du
plan final (divergence non résolue sur « Vérifie-le toi-même », le protocole de mesure comme vrai
livrable, la question de fond du fondateur), plus la question récurrente sur l'idée neuve.

## Incident de méthode à signaler avant tout : une mesure réelle authoritative est arrivée EN COURS de round, en remplacement d'une mesure plus petite

Le round a démarré avec une mesure réelle mais réduite, exécutée par le rédacteur de ce document
en parallèle du lancement des oracles : recherche effective (`site:youtube.com <terme> tutoriel`
via Perplexity) sur 11 termes du glossaire dont l'existence a été vérifiée par grep dans le code
(5 ambigus prouvés, 2 médians, 4 quasi uniques), en requête naïve et avec le nom canonique réel
stocké en base. Résultat : la désambiguïsation par parenthèse (« Autonomie (IA) », « Entropie
(information) ») résout le problème pour 5 des 6 cas testés, sauf « Pathway (entreprise d'IA) »,
qui reste faux même parfaitement désambiguée parce que le nom désigne plusieurs entités IA
concurrentes actuelles distinctes - une collision de second ordre, à l'intérieur même du domaine
IA. Perplexity et DeepSeek ont reçu cette version réduite dans leur première consultation.

**Avant que ChatGPT et claude.ai ne soient consultés**, une mesure BEAUCOUP plus rigoureuse est
arrivée du coordinateur : 30 termes réels du glossaire, 104 jugements exploitables sur 150 visés,
requête construite EXACTEMENT comme le pipeline existant. Cette mesure remplace la précédente
comme référence du round. Chiffres : **taux de faux global 22,1 %** (le bas de la fourchette
anticipée au round 1, 25 % à plus de 95 % - tout le monde surestimait) ; **48,9 % de faux sur la
seule strate des termes ambigus** ; **0 % de faux sur les termes techniques composés ET sur les
noms propres**. Facteur causal mesuré : non pas la polysémie du mot, mais l'existence d'un
homonyme plus gros et plus vu sur YouTube (un jeu vidéo écrase un terme technique, un produit
commercial écrase la métrique qui porte son nom) - imprévisible en regardant le mot seul, il faut
regarder ce que la plateforme contient déjà. Constat qui dépasse le mandat, probablement plus
important que le taux : **l'onglet serait souvent VIDE, pas faux** - les termes au nom
désambiguïsé par parenthèse (motif fréquent dans ce glossaire) retournent souvent zéro vidéo car
le contenu pertinent est titré en anglais et le filtre linguistique du pipeline l'exclut ; la
strate qui ne se trompe jamais (0 % de faux) ne remplit que 48 % de sa cible. Limites explicites,
transmises à chaque oracle : jugement sur titre/chaîne seulement, une seule passe FR, échantillon
incomplet (104/150) - un ordre de grandeur solide, pas un audit exhaustif.

**Conséquence sur le déroulement** : Perplexity et DeepSeek ont reçu une relance ciblée avec les
chiffres authoritatifs (Perplexity a échoué à répondre de façon exploitable à cette relance - voir
plus bas ; DeepSeek a révisé sa position sur les quatre sous-questions B1-B4). ChatGPT et claude.ai
ont reçu directement la version authoritative dans leur unique consultation. Gemini a reçu la
version réduite dans sa première réponse puis une relance ciblée sur B1-B4 avec les chiffres
authoritatifs, à laquelle il a répondu en confirmant sa position initiale sur B3.

**Limite d'outil supplémentaire à signaler nommément** : ChatGPT en effort de réflexion « Élevé »
a produit DEUX réponses vides consécutives (accusé de réception de la requête, génération
terminée, aucun texte rendu) sur ce même fil de discussion - un mode d'échec silencieux déjà
documenté pour les réflexions très longues. Contourné en ouvrant un nouveau fil et en abaissant
l'effort à « Moyen » (3/5 → 2/5 sur le curseur de puissance), ce qui a produit une réponse complète
et substantielle. Perplexity, de son côté, a de nouveau montré la limite déjà notée au round 2 :
aucune continuité conversationnelle, un format libre plutôt que la structure demandée à la
première tentative, et un échec total (« je n'ai pas accès au contenu du ticket ») à la deuxième
tentative de relance ciblée - une régression par rapport au round 2, où la deuxième tentative avait
fini par réussir. Perplexity reste donc l'oracle le moins exploitable de ce round, pour une raison
d'outil documentée, pas de raisonnement.

## Oracles consultés

Les cinq oracles prévus ont tous répondu, avec des degrés d'exploitabilité très inégaux cette fois.

1. **Perplexity** (`pp_search`, trois tentatives : synthèse libre non conforme au format, puis
   relance ciblée en 10 lignes qui a partiellement répondu sans respecter le format non plus, puis
   deuxième relance ciblée qui a échoué totalement - « je n'ai pas accès au contenu »). Exploitable
   seulement en synthèse partielle, signalé nommément à chaque section concernée.
2. **ChatGPT** (chatgpt.com au navigateur, compte Stéphane Lapointe Pro) - deux réponses vides en
   effort « Élevé » sur le premier fil, réponse complète en effort « Moyen » sur un second fil.
3. **claude.ai** (Opus 5, effort élevé, compte Stéphane Max) - environ 7 minutes de délibération,
   la plus longue et la plus rigoureuse réponse du round (citation d'une source statistique
   vérifiable, rétractation explicite d'un chiffre qu'il avait lui-même avancé).
4. **Gemini** (`agy`, Gemini 3.1 Pro High) - consulté deux fois (réponse complète sur les six
   survivantes + question A + ancienne version de B + question C + idée neuve, puis relance ciblée
   sur B1-B4 avec les chiffres authoritatifs).
5. **DeepSeek** (`mcp__hermes__model_invoke`, task_type=reasoning, routé vers `deepseek/deepseek-r1`)
   - consulté deux fois (réponse complète, puis relance ciblée sur B1-B4). A de nouveau fabriqué
   des références non vérifiables dans sa première réponse malgré l'avertissement explicite en
   system prompt - voir plus bas - mais a répondu sans fabrication dans sa relance ciblée.

---

## Les six survivantes, verdict par verdict

### 1. Carte de sens vérifiée (ChatGPT, round 2 : 810/1000, la mieux notée de toutes)

La note la plus haute du round 2 s'effondre. C'est l'idée la plus démolie de ce round, exactement
comme le prévenait le mandat (une note haute signifie surtout qu'elle a été moins attaquée).

- **ChatGPT (soi-même)** : la règle de seuil « aucune association visible sous 95 % de précision »
  n'a « aucun fondement démontré et viole la règle du round ». Remplacée par une règle observable :
  « aucune automatisation tant que le sens recherché n'est pas distinguable du sens dominant
  rencontré dans les résultats réels ». Avertit qu'une ontologie de sens pour tout le glossaire
  serait une deuxième taxonomie coûteuse - à déclencher seulement sur collision réelle observée.
  Score non chiffré (règle du round) : VRAI très fort x PERÇU fort x DÉFENDABLE très fort.
- **claude.ai** : démolition la plus sévère du round, avec source statistique réelle et citable
  (**Hanley et Lippman-Hand, *JAMA*, 1983** - la règle de trois). Démontrer une précision d'au
  moins 95 % avec 95 % de confiance exige environ **59 jugements consécutifs sans erreur, par
  sens** ; le ticket ne fournit que 104 jugements pour 30 termes, soit ~3,5 par terme ; « dos » n'a
  que 3 occurrences en tout, donc « sa porte ne s'ouvrira jamais » - « la règle devient "jamais
  automatique" presque partout : c'est une curation manuelle déguisée en seuil ». Deuxième faille,
  décisive : un identifiant de sens interne au site ne change RIEN à ce que YouTube renvoie - le
  facteur causal mesuré est l'homonyme EXTERNE, pas l'ambiguïté du texte interne ; pour agir sur la
  vidéo, la carte doit modifier la requête, et elle redevient alors l'idée 4. Troisième faille : qui
  attribue le sens de chacune des 137 occurrences d'« autonomie » ? Humain = coût qui explose ; IA =
  le risque de 27,7 % de déformation revient par la porte arrière. **Verdict : survit seulement
  comme modèle de données de l'auto-lien TEXTUEL, hors du sujet vidéo.** Score : VRAI 7 x PERÇU 4
  (un identifiant interne est invisible pour le lecteur) x DÉFENDABLE 6 = **168** (contre 810 au
  round 2).
- **Gemini** (première passe, avant le chiffre authoritatif) : TUÉE. « Exiger 95 % de précision
  avant affichage nécessite un jeu de données de contrôle validé manuellement... cela déplace le
  goulot d'étranglement de la liaison manuelle vers l'étiquetage manuel, rendant l'automatisation
  illusoire. »
- **DeepSeek** (première passe) : TUÉE. « Le seuil de 95 % est infondé techniquement (aucun modèle
  open-source actuel n'atteint cette fiabilité en désambiguïsation contextuelle sans entraînement
  massif spécifique). » Cite des « benchmarks SOTA en NLP (ex. ACL 2023) » sans article ni URL
  précis - un sourçage encore vague malgré l'avertissement explicite du round, à traiter comme
  [non sourcé] plutôt que comme une preuve.
- **Perplexity** : n'a pas produit d'attaque distincte et vérifiable pour cette idée précise dans
  ses réponses exploitables.

**Verdict consolidé : TUÉE dans sa forme (le seuil de 95 %) par 3 des 4 oracles qui l'ont attaquée
(Gemini, DeepSeek, et ChatGPT sur ce point précis) ; réduite à une survivante hors-sujet (168/1000,
en baisse de 79 % par rapport à 810) par claude.ai, avec la démonstration la plus rigoureuse du
round.** Le mécanisme lui-même (distinguer les sens) n'est pas mauvais ; c'est sa promesse d'agir
sur la VIDÉO qui ne tient pas, puisque le facteur causal réel est externe à toute donnée interne du
site.

### 2. Radar de maturité dynamique (Gemini, round 2 : 720/1000)

Kill quasi unanime, y compris par l'auteur.

- **Gemini (soi-même)** : TUÉE. « Un concept mathématique pur... ne génère pas d'actualité chaude
  et n'est pas un "produit" d'annuaire, la jauge le classerait donc faussement comme "obsolète" ou
  "immature" face au moindre gadget logiciel bénéficiant d'une hype médiatique. »
- **ChatGPT** : TUÉE. « Fréquence dans les actualités et présence dans les fiches-outils ne mesurent
  pas directement la maturité technologique. Elles mesurent surtout la visibilité éditoriale dans
  laveille.ai. » Un terme peut être cité abondamment parce qu'il est controversé ou à la mode ; une
  technologie largement déployée peut générer peu d'actualités - transformer ces proxys en axe
  « hype/R&D → production » « introduit une conclusion que les données ne démontrent pas ».
- **claude.ai** : TUÉE. « Le comptage de fréquence repose sur la même correspondance de chaîne que
  l'auto-lien, mesurée à 59 % de faux [sur "autonomie"]. La jauge hériterait donc de cette
  contamination sur les termes où elle serait le plus lue. » Corpus circulaire (reflète l'agenda
  éditorial du fondateur, pas le marché) ; « hype contre production » est « un verdict sans source
  citable, publié par un média de vérification » ; et rien de tout cela n'aide à comprendre le
  terme.
- **DeepSeek** : seul oracle à la garder, avec un amendement lourd, mais avec une notation
  incohérente (scores hors de l'échelle 0-10 attendue - VRAI 480 x PERÇU 720 x DÉFENDABLE 550 - un
  défaut de format, pas une note comparable aux autres).

**Verdict consolidé : TUÉE à l'unanimité des trois oracles qui l'ont examinée sérieusement
(Gemini soi-même, ChatGPT, claude.ai), pour une raison de fond convergente et indépendante :
les données disponibles (fréquence éditoriale interne) ne mesurent pas la propriété que l'idée
prétend afficher (maturité réelle d'une technologie sur le marché).**

### 3. Écran de validation sémantique (Perplexity, round 2 : 648/1000)

Survit partout, mais amputée de son composant le plus faible (le score de confiance) et resserrée
à un usage bien plus étroit qu'au round 2.

- **Perplexity (soi-même)** : maintenue sous la forme d'une « liste blanche éditoriale » sans
  publication par défaut, avec justification écrite, date de revue et expiration par ressource.
- **ChatGPT** : survit comme contrôle éditorial, PAS comme solution de découverte. Le « score de
  confiance » « peut donner une apparence sophistiquée à une décision qui reste mauvaise. Un score
  produit par le même système qui sélectionne les vidéos n'est pas une preuve indépendante. » Défaut
  économique majeur reconnu : si chaque vidéo doit être validée manuellement, l'essentiel de
  l'avantage de l'automatisation disparaît. Score non chiffré : VRAI fort x PERÇU moyen x DÉFENDABLE
  très fort.
- **claude.ai** : « Le "score de confiance" est le maillon mort. Produit par un modèle, c'est un
  nombre non calibré ; utilisé comme seuil, c'est précisément un chiffre sans source. » Le motif et
  la source tiennent, mais sans le score « l'écran n'est plus une idée : c'est une case dans un flux
  de relecture », dont le vrai coût (temps humain par ressource) n'est pas évalué. Survit AMPUTÉE
  du score, comme règle de procédure. Score : VRAI 8 x PERÇU 3 x DÉFENDABLE 8 = **192** (contre 648).
- **Gemini** (première passe) : AMENDÉE - survivante seulement si le sas d'approbation humaine
  n'est déclenché que par une liste d'exclusion (termes déjà prouvés ambigus), jamais pour toute
  publication. Score : VRAI 8 x PERÇU 7 x DÉFENDABLE 9 = 504 (contre 648).
- **DeepSeek** (première passe) : TUÉE - « charge humaine incompatible avec le flux de production
  actuel (1 fiche/jour) », en citant une « rétro-ingénierie du workflow éditorial actuel de
  laveille.ai (documentée dans Ticket #2401) » - **ce ticket n'existe dans aucun document consulté
  de ce dossier et doit être traité comme fabriqué**, exactement le mode d'échec déjà mesuré au
  round 2.

**Verdict consolidé : SURVIT chez 4 oracles sur 5, mais à chaque fois amputée de son composant
automatisé (le score de confiance chiffré), et resserrée à un déclenchement seulement sur les cas
déjà curés comme ambigus - jamais un flux de validation généralisé à tout le catalogue.** C'est la
seule des six où la convergence sur l'amputation est presque totale.

### 4. Injection sémantique de contexte (DeepSeek, round 2 : 504/1000)

Tuée par la mesure elle-même, avec un contre-exemple concret et déjà observé.

- **DeepSeek (soi-même)** : TUÉE - « n'agit pas sur le vrai problème mesuré (homonymes dominants,
  ex. un jeu vidéo écrasant un terme technique). Risque d'amplifier les biais si le contexte extrait
  est lui-même ambigu. »
- **ChatGPT** : survit seulement comme « technique auxiliaire », tuée comme garde de sécurité. Utile
  pour augmenter la pertinence des candidats, insuffisant pour autoriser leur publication ; note que
  le constat des fiches avec parenthèses montre qu'une désambiguïsation plus agressive « peut
  simplement transformer le problème faux → vide ».
- **claude.ai** : TUÉE PAR LA MESURE, avec le contre-exemple le plus concret du round. « Sa seule
  strate utile est la strate ambiguë (48,9 % de faux), celle qu'on retire de toute façon de
  l'automatique. Sur les deux autres, il n'y a rien à corriger (0 % de faux). » Et surtout : « si la
  requête reprend le nom complet, "Autonomie (IA)" est d'ailleurs déjà une injection de contexte, et
  le résultat mesuré est le VIDE. Ce vide vient du filtre linguistique qui exclut le contenu
  anglais : le problème est l'offre, pas la précision de la requête. »
- **Gemini** (première passe, avant même de voir le constat « onglet vide ») : TUÉE, en anticipant
  presque exactement ce que la mesure authoritative allait confirmer : « l'injection systématique
  crée des requêtes trop restrictives... fait chuter la probabilité de trouver une vidéo
  correspondante à zéro, aggravant massivement le problème de l'onglet vide. »

**Verdict consolidé : TUÉE par 3 des 4 oracles qui l'ont examinée (dont son propre auteur), avec une
convergence remarquable : Gemini avait PRÉDIT le problème « vide » avant de voir la mesure qui le
confirme, et claude.ai le démontre ensuite avec le cas réel « Autonomie (IA) ». ChatGPT la garde
seulement comme réglage mineur, jamais comme la solution.**

### 5. Capsule signée (claude.ai, round 2 : 504/1000)

L'auteur démolit sa propre idée en sept points, dont le dernier est décisif : elle fait doublon
avec un actif déjà en production.

- **claude.ai (soi-même)**, attaque en sept points :
  1. **Chiffre sans source, retiré** : « mon "45-60 secondes" n'a aucune source vérifiable ; je le
     retire » - autocorrection explicite, contrastant avec le comportement de DeepSeek au round 2.
  2. **Prémisse affaiblie** : l'idée répondait à « la découverte automatique se trompe » ; or elle se
     trompe à 0 % sur deux strates sur trois.
  3. **Couverture** : au plus 40 fiches sur 534, soit ~7,5 %. « Un onglet absent de plus de neuf
     fiches sur dix est une exception d'interface, pas un onglet. »
  4. **Capacité** : chaque capsule dépend d'un fondateur seul, qui mène aussi une agence de 148
     sites, de l'enseignement et plusieurs produits, sans relève possible.
  5. **Correction** : une erreur dans un texte se corrige en une minute ; une vidéo se retourne
     difficilement - « pour un média de vérification, c'est le pire format sur ce critère ».
  6. **Péremption** : sur un sujet IA, afficher la date de tournage revient à afficher la date de
     péremption.
  7. **Doublon, le point décisif** : « les 9 planches BD maison sont déjà l'actif "humain, signé,
     pédagogique" que la capsule promettait, dans un format moins coûteux et corrigeable. »
  **Verdict : tuée comme onglet. « Son résidu défendable n'est pas une idée vidéo : c'est une
  planche de plus [BD]. »**
- **ChatGPT** : survit sur le PRINCIPE (« élimine le plus radicalement le risque étudié »), pas sous
  sa spécification actuelle - « durée imposée, nombre déterminé de fiches populaires et nombre
  déterminé d'ambiguïtés [...] sont des choix de production, pas des conclusions issues des mesures
  fournies ». Risque signalé : produire une vidéo simplement parce qu'un onglet existe inverserait
  la logique produit. Score non chiffré : VRAI très fort x PERÇU très fort x DÉFENDABLE fort.
- **Gemini** (première passe) : SURVIVANTE, meilleur score attribué du round parmi les six -
  « n'est absolument plus une solution technologique de glossaire, mais une stratégie de création
  de contenu vidéo sur mesure, impossible à passer à l'échelle des 534 fiches. Elle survit
  néanmoins car c'est la seule qui annule mathématiquement le risque éditorial (0 % de faux
  positif). » Score : VRAI 10 x PERÇU 8 x DÉFENDABLE 9 = **720**.
- **DeepSeek** (première passe) : TUÉE - « production vidéo maison économiquement insoutenable
  (coût moyen estimé à 500 €/vidéo par benchmarks créateurs FR) » citant un « rapport CNC 2023 » -
  **chiffre et source non vérifiables, à traiter comme fabriqués**, malgré l'avertissement explicite
  du round.

**Verdict consolidé : la démolition la plus convaincante du round est celle de l'AUTEUR sur sa
propre idée - le point du doublon avec les BD existantes est un fait vérifiable (9 planches déjà en
production, cf. brief section D) qu'aucun autre oracle n'a soulevé. Gemini la garde au score le
plus haut du round sans avoir vu cette attaque ; ChatGPT et DeepSeek se partagent entre survie de
principe et mort économique. TUÉE comme onglet, sur le fait précis du doublon.**

### 6. Comprendre par contraste, amputée (ChatGPT round 1, amputée par claude.ai round 2)

La divergence la plus intéressante du round : deux oracles la gardent, deux la tuent, et les deux
qui la gardent proposent indépendamment le MÊME correctif - changer la source du champ, pas sa
taille.

- **ChatGPT** : « l'amputation la sauve, mais elle change complètement sa nature. » La version
  amputée « transforme les erreurs RÉELLEMENT détectées par le système en information
  pédagogique » - ce n'est plus une mini-leçon complète, « c'est une vaccination contre une
  confusion prouvée ». Score non chiffré : VRAI très fort x PERÇU fort x DÉFENDABLE très fort.
- **claude.ai** : l'amputation la sauve du volume, MAIS la source choisie au round 2 (les erreurs de
  l'auto-lien textuel, ex. « dos ») « la vide » - « "dos" à 100 % de faux signifie qu'une règle
  confond DOS et le dos ; aucun lecteur de la fiche DOS ne pense à l'anatomie. Un champ "à ne pas
  confondre avec le dos" serait perçu comme du remplissage. » En revanche, « la mesure vidéo fournit
  la BONNE source : l'homonyme plus gros et plus vu est une confusion que le lecteur rencontrera
  réellement dès qu'il cherchera le terme ailleurs. » **Verdict : survit SI l'on change la source
  (des confusions de l'auto-lien vers les homonymes réellement mesurés côté vidéo), rédaction
  humaine, uniquement là où une confusion est documentée.** Score : VRAI 8 x PERÇU 7 x DÉFENDABLE 8
  = **448**, la survivante la mieux placée du round selon claude.ai (à égalité avec sa propre idée
  neuve, voir plus bas).
- **Gemini** (première passe) : TUÉE - « réduire une idée pédagogique globale à un unique champ...
  n'est plus de la pédagogie... transforme une section d'apprentissage légitime en simple affichage
  public des erreurs de l'algorithme. »
- **DeepSeek** (première passe) : TUÉE - même diagnostic (« réduit l'outil à un gadget non
  pédagogique »).

**Verdict consolidé : DIVERGENCE RÉELLE, 2 contre 2 (Perplexity ne s'est pas prononcée). Mais
ChatGPT et claude.ai, sans se concerter, atterrissent sur la MÊME conclusion opérationnelle :
l'amputation était le bon geste (retirer le volume généré par IA), mais la SOURCE retenue au round
2 était la mauvaise (les erreurs internes de l'auto-lien, jamais rencontrées par un vrai lecteur).
La bonne source, révélée par la mesure de ce round, est l'homonyme externe réellement mesuré côté
vidéo. Ceci fusionne de fait l'idée 6 avec le constat causal de la question B.**

---

## A. La divergence non résolue sur « Vérifie-le toi-même » : TRANCHÉE, pas ouverte

Trois oracles (claude.ai elle-même, ChatGPT, DeepSeek) avaient tué cette idée du round 1 pour
fragilité technique ; Gemini l'avait notée meilleure idée de tout le round 1 (729/1000) sans avoir
vu l'objection. Consultés cette fois avec le mandat explicite de trancher :

- **ChatGPT** : « Les trois tueurs ont raison. » Gemini jugeait la valeur pédagogique (excellente
  pour un média de vérification), mais « la question n'est pas seulement pédagogique. Elle est
  expérimentale. » Si une réponse obtenue par API est publiée puis que le lecteur est invité à
  « vérifier lui-même » dans une application grand public au routage, à la version, aux outils, au
  contexte système ou à l'exécution potentiellement différents, « le lecteur ne reproduit pas
  nécessairement l'expérience publiée » - un échec grave car le dispositif prétend précisément
  démontrer la vérifiabilité. Test proposé pour clore un désaccord futur analogue : exécuter les
  mêmes requêtes, au même moment, dans le pipeline de laveille.ai ET dans l'interface réellement
  proposée au lecteur, puis vérifier la stabilité des conclusions importantes.
- **claude.ai** : « Les trois tueurs ont raison. La note de Gemini n'est pas une opinion
  minoritaire : elle a été rendue sans une donnée décisive, donc elle ne compte pas. » Appuie sur
  des faits vérifiables et datés : Anthropic publie les instructions système de claude.ai
  (impliquant qu'elles diffèrent de l'API) ; OpenAI a introduit un routeur automatique dans ChatGPT
  à la sortie de GPT-5, remplaçant le modèle par défaut sans transition. **L'argument qui clôt le
  débat n'est pas le non-déterminisme (qui se mesure), c'est la DÉRIVE SILENCIEUSE** : le modèle
  derrière l'application gratuite change sans préavis - une démonstration validée aujourd'hui peut
  devenir irreproductible demain sans que le site le sache, et le lecteur qui obtient l'inverse de
  ce qu'on lui a promis réfute alors le site lui-même. **Fait précis qui rouvrirait le débat** : un
  taux de reproduction mesuré dans l'application gratuite, avec un vrai compte gratuit et des
  répétitions par invite, stable sur deux mesures encadrant au moins un changement de modèle par
  défaut. Sans ce fait, la position par défaut reste la mort de l'idée.
- **DeepSeek** (première passe) : « Gemini a tort. L'écart technique... est vérifié (ex. OpenAI API
  vs ChatGPT free : routing différent documenté dans leur blog technique ; non-déterminisme mesuré
  dans arXiv:2305.08196). » La référence arXiv n'a pas pu être vérifiée dans le cadre de ce round -
  à traiter avec la même prudence que les autres chiffres de DeepSeek, même si la conclusion
  converge avec les deux autres oracles.

**Verdict du round : TRANCHÉ, pas ouvert. Les trois oracles qui ont examiné l'écart API/application
en détail (ChatGPT, claude.ai, DeepSeek) confirment qu'il est réel et documentable ; Gemini n'a
jamais examiné cet écart, donc sa note de 729 ne pèse pas comme une position concurrente mais comme
une évaluation incomplète.** Le fait le plus utile produit par ce round est celui de claude.ai : ce
n'est pas l'aléa du modèle qui tue l'idée (l'aléa se mesure et se documente), c'est le changement
de modèle par défaut SANS PRÉAVIS côté fournisseur, qui rend une preuve d'aujourd'hui potentiellement
fausse demain sans que quiconque le sache.

---

## B. Le protocole de mesure était-il le vrai livrable ? Réponse : le taux n'était PAS l'argument décisif, mais les mesures elles-mêmes ont changé l'argument

À 22,1 % de faux global et 0 % sur deux strates sur trois (bien en dessous de tout ce que les cinq
oracles avaient anticipé au round 1), la question posée était : le refus tient-il quand même, et
si oui, pour quelles raisons AUTRES que le taux de faux ?

### B1 - La conclusion « pas d'onglet vidéo automatique » tient-elle encore ?

**Oui, chez les quatre oracles qui ont répondu, mais l'argument change de nature chez chacun** :

- **ChatGPT** : le taux global n'est plus l'argument. Le nouvel argument est la **contrôlabilité** -
  « le système ne sait pas reconnaître à l'avance les cas dans lesquels son mécanisme de recherche
  change de régime » (un homonyme externe dominant, imprévisible au seul examen du mot) ; et quand
  on désambiguïse agressivement pour corriger ce risque, on tombe dans l'autre panne, l'absence de
  contenu. « Ce n'est plus "les résultats sont trop mauvais". C'est "le système ne possède pas
  encore une fonction de décision fiable sur ses propres limites". »
- **claude.ai** : trois raisons nouvelles, aucune n'étant le taux. (1) La mesure a jugé la
  pertinence, pas l'exactitude - « 0 % de faux » signifie « 0 % hors sujet », pas « 0 % fausse » ;
  une vidéo dans le sujet peut être périmée ou fausse et le site la cautionnerait par sa seule
  présence. (2) La mesure est une photo, le pipeline tourne en continu - un homonyme géant peut
  apparaître le jour où sort un jeu ou un produit, et un terme à 0 % aujourd'hui peut basculer sans
  que personne ne regarde. (3) L'onglet serait structurellement inconsistant - même la strate sans
  erreur ne remplit que 48 % de sa cible. Reformulation retenue : « aucune publication automatique,
  mais la découverte automatique peut alimenter une file de suggestions validées par un humain »
  (l'idée 3 réduite).
- **Gemini** (relance ciblée) : oui, mais l'argument principal « bascule du taux de faux vers
  l'expérience utilisateur brisée par le taux de vide » - un onglet vide 52 % du temps, même sur les
  strates les plus fiables, « donne l'impression d'une fonctionnalité défectueuse ou d'un site en
  chantier ».
- **DeepSeek** (relance ciblée) : oui - « utilité compromise » (un onglet vide à 52 % pour les
  strates fiables est inopérant), « expérience utilisateur dégradée » (la désambiguïsation manuelle
  transfère la complexité à l'utilisateur), « cohérence éditoriale » (un média de vérification ne
  peut s'appuyer sur un outil qui échoue sur 48,9 % d'une strate ou laisse vide la moitié des
  entrées fiables).

**Convergence réelle, non forcée : les quatre oracles maintiennent la conclusion, mais AUCUN ne la
maintient plus sur le taux de faux - c'est le résultat le plus important de ce point, et il répond
exactement à ce que le round devait tester.** Le refus était FONDÉ, pas une intuition habillée de
chiffres : quand le chiffre s'est effondré, l'argument a migré vers un terrain différent
(contrôlabilité, snapshot vs continu, taux de vide) au lieu de s'effondrer avec lui.

### B2 - Un onglet réservé aux strates à bonne couverture : élégant ou complexité inutile ?

**Convergence unanime des quatre : complexité inutile.**

- **ChatGPT** préfère une règle fondée sur la RESSOURCE (afficher seulement quand une ressource
  validée existe) plutôt que sur la CATÉGORIE lexicale du terme - « la fiche ne devrait pas connaître
  la théorie statistique qui a permis de sélectionner son contenu ».
- **claude.ai**, l'objection la plus tranchante : « la strate "à bonne couverture" n'existe pas : ces
  termes ont une bonne PRÉCISION, pas une bonne COUVERTURE (48 %) » - et le facteur causal (homonyme
  externe) n'est pas prédit par la catégorie lexicale, un nom propre pouvant lui aussi en avoir un.
  « Classer 534 termes en strates, c'est payer une taxonomie qui ne prédit pas le phénomène. »
- **Gemini** : « complexité inutile pour le gain » - effort de développement disproportionné pour
  une fonctionnalité qui ne remplira sa promesse que dans 48 % des cas autorisés.
- **DeepSeek** : complexité inutile, trois raisons (couverture insuffisante même pour les strates
  « sûres », incohérence pour l'utilisateur, maintenance accrue).

### B3 - Onglet FAUX ou onglet VIDE, lequel abîme le plus ? Divergence réelle et préservée

- **ChatGPT** (moyen) : le FAUX. « Nous avons vérifié suffisamment cette ressource pour vous la
  recommander » touche directement la promesse de marque, contre un vide qui dit seulement
  « nous n'avons rien à vous montrer ici ».
- **claude.ai** : le FAUX, pour trois raisons (il contamine le vrai puisque le lecteur ne distingue
  pas une vidéo trouvée par robot des sources externes vérifiées sur la même page ; il se partage -
  une capture « le site de vérification confond une métrique et un jeu vidéo » circule, un onglet
  vide non ; il vise la promesse centrale du site). Nuance importante : « le vide n'est toutefois
  inoffensif que s'il est INVISIBLE. Un onglet affiché puis vide est une promesse rompue », et la
  mesure montre que ce serait fréquent sur les fiches « (IA) », le cœur du site. Ordre de gravité
  retenu : **faux > vide affiché > vide absent**.
- **Gemini** (avant ET après la relance avec les chiffres authoritatifs, position stable) : le FAUX -
  « pour un média positionné sur la veille et la vérification, afficher une vidéo hors-sujet... mine
  activement la crédibilité éditoriale », alors que le vide « frustre l'utilisateur... mais ne remet
  pas en cause votre autorité ou votre sérieux ».
- **DeepSeek** (avant ET après la relance, position stable, l'OUTLIER du round) : le VIDE - « implique
  une absence de service (l'utilisateur croit le sujet non traité)... masque des ressources
  existantes... sape la crédibilité : un média de vérification doit fournir des réponses, pas des
  silences. »

**Verdict : 3 des 4 oracles (ChatGPT, claude.ai, Gemini) convergent sur le FAUX comme dommage
principal, avec la même logique (contamination de la promesse de vérification) ; DeepSeek maintient
à deux reprises, sans faiblir, que le VIDE abîme davantage. Ce n'est pas moyenné. La nuance de
claude.ai (un vide AFFICHÉ, pas simplement absent, est lui-même une promesse rompue) explique en
partie pourquoi DeepSeek n'a pas tort dans l'absolu - le désaccord porte sur un cas particulier (le
vide visible et récurrent) que 3 des 4 oracles minimisent et que DeepSeek place au premier plan.**

### B4 - Le facteur « homonyme plus gros » : garde automatique ou liste curée à la main ?

- **ChatGPT** : « données d'abord → règle ensuite » - une collision observée devient une exclusion
  documentée ; plusieurs collisions à structure identique peuvent ensuite justifier une règle testée
  rétrospectivement. Rejoint la doctrine déjà écrite dans le code (`ALIAS_NEVER_AUTO`).
- **claude.ai** : LES DEUX, avec des rôles séparés et non concurrents - « la liste DÉCIDE » (auditable,
  motif par entrée) ; « la mesure ALERTE, sans jamais bloquer ni publier » (une vérification
  périodique du rapport de volumes qui prévient un humain, jamais un filtre automatique qui publie
  ou bloque seul). Rappelle le précédent maison (0 % de précision, 46 liens légitimes perdus pour 0
  vrai blocage) mais note qu'une liste figée ne voit pas un homonyme apparu après sa rédaction -
  d'où l'alerte non bloquante comme complément, pas comme remplacement.
- **Gemini** (relance) : liste curée à la main - une mesure programmatique de l'écrasement par
  homonyme « exigerait d'analyser de larges volumes de requêtes, d'interpréter sémantiquement les
  intentions de recherche et de gérer les quotas d'API. C'est une usine à gaz technique très
  fragile. »
- **DeepSeek** (relance) : liste curée à la main, sans fabrication cette fois - cite correctement le
  précédent maison (0 %, 46 liens) comme preuve qu'une garde généralisée a déjà échoué sur ce
  projet.

**Verdict : convergence forte sur la liste curée comme mécanisme de DÉCISION, avec un raffinement
non contredit par les trois autres - claude.ai propose d'ajouter une couche de mesure qui ALERTE
sans jamais publier ni bloquer seule, ce qui respecte la doctrine « jamais de garde générale »
tout en corrigeant l'angle mort d'une liste qui ne voit rien après sa rédaction.**

---

## C. La question de fond du fondateur, en une phrase

Deux réponses complètes ont été obtenues (ChatGPT, claude.ai), plus une réponse compatible obtenue
en amont au round 1 (DeepSeek).

- **ChatGPT** : « Reliez chaque terme aux actualités internes qui l'emploient réellement, puis
  utilisez ces cas réels avec les relations, la FAQ, les sources vérifiées et les BD existantes pour
  expliquer concrètement ce qu'il signifie et ce avec quoi il ne faut pas le confondre. »
- **claude.ai** : « Rendez chaque terme abstrait concret avec ce que vous contrôlez déjà, en
  commençant par les fiches les plus consultées : une situation illustrée dans vos planches maison
  et, grâce aux relations existantes, le terme voisin avec lequel le lecteur le confond réellement,
  sans ajouter de flux externe dont vous ne pouvez garantir l'exactitude dans le temps. »

**Ces deux réponses ne convergent qu'à moitié, et le désaccord est réel, pas un artefact de
formulation.** ChatGPT inclut explicitement un pont vers les actualités internes (une résurrection
qualifiée de l'idée « actualités liées » tuée 3 contre 2 au round 2, mais cette fois nourrie
d'extraits vérifiés plutôt que d'un appariement lexical automatique). claude.ai exclut
explicitement tout « flux externe » et reste sur les deux actifs déjà éprouvés du site (BD +
relations entre termes), au motif de la garantie d'exactitude dans le temps. Les deux s'accordent
sur le socle (BD + relations + partir des fiches les plus consultées) et sur l'exclusion de la
vidéo tierce comme mécanisme ; ils divergent sur la place à donner aux actualités internes comme
troisième pilier. **Ce désaccord reste ouvert** - aucun des deux n'a eu l'occasion d'attaquer la
proposition de l'autre dans ce round.

---

## Question récurrente : l'idée neuve la plus rentable

Trois idées neuves authentiques ont émergé (absentes des listes des deux rounds précédents),
produites indépendamment par trois oracles différents.

### 1. Gemini - « Chemin de prérequis internes » (score le plus haut du round, auto-attribué : 900/1000)

Au-dessus de chaque définition complexe, afficher le chemin cliquable de 2 ou 3 termes fondamentaux
du glossaire à maîtriser AVANT de lire cette fiche (ex. « Prérequis : Algorithme > Modèle
d'apprentissage > Réseau de neurones »), construit à partir des relations « plus large/plus étroit »
déjà existantes en base. Risque de faux positif nul (circuit fermé interne déjà vérifié) ; attaque
directement le besoin fondamental de compréhension en balisant un apprentissage séquentiel.

### 2. claude.ai - « La requête testée » (score : 448/1000, à égalité avec l'idée 6 amendée)

Sur les seules fiches où la mesure a trouvé un homonyme dominant ou un vide, publier - à la place
d'un onglet vidéo - la formulation de recherche que le site a testée, l'homonyme à éviter,
l'équivalent anglais du terme, et la date du test. Le site ne cautionne aucune vidéo, seulement une
MÉTHODE vérifiée. Ne demande aucune génération par IA ; la matière première existe déjà pour les 30
termes mesurés. Lien explicite avec l'idée 4 : « même geste, lieu opposé - DeepSeek cachait la
requête et cautionnait ses résultats ; celle-ci montre la requête et ne cautionne rien. » Attaque
que l'auteur applique à sa propre idée : elle se périme comme l'idée A, mais la date du test rend
l'usure visible au lieu de la cacher ; renvoyer un lecteur francophone vers l'anglais contredirait
le filtre linguistique - à limiter aux termes sans ressource française pertinente.

### 3. ChatGPT - « Preuve par usage » (score non chiffré, présentée comme la piste à tester avant l'onglet vidéo)

Pour chaque terme, ajouter automatiquement un ou plusieurs passages RÉELS et VERBATIM provenant des
propres articles de laveille.ai où le terme est employé dans son sens technique, avec quelques
lignes de contexte et un lien vers l'article source - jamais un résumé généré par IA, seulement le
passage source et son contexte vérifiable. Alimente directement le champ « à ne pas confondre
avec » de l'idée 6 lorsque deux usages entrent en collision. Exploite un corpus déjà contrôlé,
sans dépendance à YouTube, crée le pont glossaire-actualités aujourd'hui absent (section D du
brief) sans reproduire l'erreur mesurée de l'auto-lien (puisqu'il s'agit d'extraits vérifiés, pas
d'un appariement par mot).

**Les trois passent le filtre VRAI x PERÇU x DÉFENDABLE et dépassent, seules ou ensemble, la moitié
des dix idées produites dans les deux rounds précédents** (elles battent au moins : radar de
maturité, injection sémantique, capsule signée, vérifie-le toi-même, simulateurs, et actualités
liées sous sa forme naïve - six idées sur dix). Note de vigilance : DeepSeek a proposé en première
passe un « simulateur minimal interactif », qui est une reformulation de son PROPRE simulateur déjà
tué à l'unanimité au round 2 - ce n'est pas une idée neuve, c'est un recyclage non signalé d'une
idée déjà morte, à ne pas compter dans le bilan.

**Lecture transversale** : les trois idées neuves de ce round partagent un principe commun, non
concerté - **utiliser ce que le site a déjà mesuré ou déjà produit (relations, BD, articles,
échantillon de la mesure elle-même) plutôt que d'aller chercher une ressource externe non
contrôlée.** C'est la même direction que les cinq idées neuves du round 2, poussée plus loin :
là où le round 2 proposait de désambiguïser AVANT la découverte ou de remplacer la découverte par
une production humaine, le round 3 propose de remplacer la découverte externe par une
RÉUTILISATION de ce que le site possède déjà (relations structurées, corpus d'articles, résultat de
sa propre mesure).

---

## Fabrications et limites d'outil de ce round, consolidées

- **DeepSeek** a de nouveau cité des références non vérifiables dans sa PREMIÈRE réponse, malgré un
  system prompt d'avertissement explicite rappelant l'incident du round 2 : « Ticket #2401 »
  (n'existe dans aucun document consulté de ce dossier), « rapport CNC 2023 » et « 500 €/vidéo »
  (aucune source citable), « benchmarks SOTA en NLP (ex. ACL 2023) » (venue vague, aucun article
  précis), « arXiv:2305.08196 » (non vérifié dans le cadre de ce round). **Amélioration mesurée** :
  sa relance ciblée sur les questions B1-B4, portant sur des chiffres FOURNIS par le round plutôt
  que sur une estimation à produire, n'a contenu aucune fabrication nouvelle. Le mode d'échec de
  DeepSeek semble donc concentré sur les moments où on lui demande d'ESTIMER quelque chose
  d'inconnu, pas sur les moments où on lui demande de RAISONNER sur des données déjà données.
- **claude.ai**, à l'inverse, a spontanément retiré un chiffre qu'elle avait elle-même avancé
  (« 45-60 secondes » pour la capsule signée) au moment de l'attaquer, sans qu'on le lui demande -
  comportement à noter positivement comme contre-exemple direct au mode d'échec de DeepSeek.
- **Perplexity** confirme, en pire, la limite déjà notée au round 2 : absence de continuité
  conversationnelle, non-respect du format demandé même après reformulation stricte, et échec total
  à la deuxième relance (« je n'ai pas accès au contenu du ticket »). C'est une limite d'outil,
  documentée sur deux rounds consécutifs maintenant, pas un problème de raisonnement - le contenu
  produit dans ses réponses exploitables était cohérent avec celui des autres oracles.
- **ChatGPT** a produit deux réponses vides consécutives en effort de réflexion « Élevé » sur un
  même fil de discussion (génération terminée, aucun texte rendu) - un mode d'échec silencieux de
  l'outil, contourné en ouvrant un nouveau fil à effort « Moyen », qui a produit une réponse complète
  et substantielle. À surveiller si le motif se répète sur de futurs rounds à haute charge de
  raisonnement.

---

## Critère d'arrêt : NON atteint

Le round 2 avait produit cinq idées neuves passant le filtre. **Ce round en produit trois de plus
qui passent également le filtre** (chemin de prérequis, requête testée, preuve par usage), toutes
issues d'oracles différents et sans concertation. Le critère fixé avant le round 1 (deux rounds
CONSÉCUTIFS sans idée neuve passant le filtre VRAI x PERÇU x DÉFENDABLE) n'est donc PAS satisfait :
ce serait le cas seulement si ce round n'avait rien produit, ce qui n'est pas arrivé. **Un round 4
serait mécaniquement indiqué par la règle d'arrêt elle-même, mais ce round ne l'enchaîne pas de son
propre chef, conformément au mandat reçu.** La décision de poursuivre ou de clore ici appartient au
fondateur.

## Ce qui reste tranché pour la suite, si le dossier s'arrête ici

- Le taux de faux n'est plus un argument utilisable en soi (22,1 % global, 0 % sur deux strates sur
  trois) : toute décision de refuser un onglet vidéo automatique doit désormais s'appuyer sur la
  contrôlabilité, le caractère de photo (vs pipeline continu) de toute mesure, et le taux de vide -
  jamais sur « c'est trop souvent faux ».
- Le taux de VIDE (52 % même sur la strate la plus fiable, causé par le filtre linguistique face à
  des ressources anglophones) est un défaut au moins aussi grave que le taux de faux, avec un
  désaccord réel et non résolu sur lequel des deux abîme le plus (3 oracles pour le faux, 1 pour le
  vide, avec une nuance de claude.ai qui explique une partie de l'écart).
- Aucune des six survivantes du round 2 ne sort intacte : la mieux notée (carte de sens, 810)
  s'effondre le plus (168 chez claude.ai, tuée chez deux autres) ; deux meurent franchement (radar
  de maturité, injection sémantique) ; deux survivent amputées à un usage bien plus étroit (écran de
  validation, comprendre par contraste - cette dernière avec un changement de SOURCE, pas seulement
  de taille) ; une meurt par la main de son propre auteur, qui découvre qu'elle fait doublon avec un
  actif déjà en production (capsule signée vs les 9 planches BD existantes).
- La divergence « Vérifie-le toi-même » est tranchée, pas ouverte : l'argument décisif est la dérive
  silencieuse du modèle par défaut d'une application grand public, pas le non-déterminisme.
- Trois idées neuves convergent sur un principe commun (réutiliser ce que le site possède déjà
  plutôt que d'aller chercher à l'extérieur), qui prolonge directement la direction du round 2.

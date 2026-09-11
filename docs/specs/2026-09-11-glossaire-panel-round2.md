# Panel round 2 (réfutation croisée) - Onglet vidéos/tutoriels sur les fiches de glossaire (ticket #2431)

Document de recherche, lecture seule. Aucun fichier de code modifié. Ce round soumet aux cinq
oracles TOUTES les réponses du round 1 (`docs/specs/2026-09-11-glossaire-panel-round1.md`),
marquées par oracle, avec mandat explicite de TUER - minimum deux éliminations nommées par
oracle, dont au moins une de ses propres contributions quand il en a une.

## Incident de méthode à signaler avant tout : le Point 3 a été corrigé EN COURS DE ROUND

Le Point 3 soumis aux cinq oracles portait sur une HYPOTHÈSE de claude.ai (round 1) : le vaccin
anti-doublon vidéo de l'annuaire, indexé sur l'identifiant vidéo seul, contaminerait le glossaire
si le pipeline était réutilisé tel quel. Une contre-vérification dans le code réel de l'annuaire,
menée en parallèle par le superviseur pendant que ce round tournait, a produit un verdict mesuré
**avant** que Perplexity, Gemini et DeepSeek n'aient terminé leur première passe, mais **après**
qu'ils l'aient déjà jugée sur la base de l'hypothèse non vérifiée. Conséquence :

- **Perplexity, Gemini, DeepSeek** ont d'abord répondu sur l'hypothèse brute, puis ont reçu une
  relance ciblée ne portant QUE sur le Point 3 corrigé (les autres points de leur première
  réponse restent valides et ne sont pas rejoués).
- **ChatGPT et claude.ai** ont reçu directement la version corrigée, jamais l'hypothèse brute.

Les deux versions (hypothèse originale jugée par les trois premiers, puis version corrigée jugée
par tous) sont conservées ci-dessous, car l'écart entre elles est lui-même un résultat : deux
oracles (Gemini, DeepSeek) avaient déclaré l'hypothèse non vérifiée « réelle » avec une confiance
que le code ne soutenait qu'en partie.

**Limite d'outil à signaler nommément** : la relance ciblée de Perplexity sur le Point 3 corrigé
a ÉCHOUÉ à la première tentative - l'outil `pp_search` n'a pas de continuité conversationnelle
native et a demandé le contexte qui venait pourtant d'être fourni dans le même appel. Contournée
par un second essai autonome (tout le contexte requis dans une seule requête, format imposé en
8 lignes numérotées), qui a réussi. Perplexity reste donc le seul oracle dont la conformité au
format demandé a exigé un aller-retour supplémentaire.

## Oracles consultés

Les cinq oracles prévus ont tous répondu. Aucun indisponible à signaler.

1. Perplexity (`pp_search`, deux tentatives : essai narratif non conforme puis essai structuré
   conforme en 8 lignes)
2. ChatGPT (chatgpt.com au navigateur, compte Stéphane Lapointe Pro, effort « Élevé »)
3. claude.ai (Opus 5, effort élevé, compte Stéphane Lapointe Max - environ 6 minutes de
   délibération avant la réponse, la plus longue du round)
4. Gemini (`agy`, Gemini 3.1 Pro High) - consulté deux fois (réponse complète + relance ciblée
   Point 3)
5. DeepSeek (`mcp__hermes__model_invoke`, task_type=reasoning, routé vers `deepseek/deepseek-r1`)
   - consulté deux fois (réponse complète + relance ciblée Point 3)

---

## POINT 1 - Les taux de faux : un chiffre sorti d'une intuition ne vaut pas une comparaison à une mesure existante, MAIS l'inégalité stricte de claude.ai ne tient pas non plus

**Verdict qui émerge du round, par convergence réelle (pas une moyenne) :** les trois fourchettes
absolues du round 1 (ChatGPT 25-40 %, Gemini plancher 60 %, DeepSeek 40-70 %) sont **tuées comme
chiffres décisionnels par ChatGPT lui-même, par claude.ai et implicitement par DeepSeek** (qui
retire son propre chiffre). Mais l'inégalité de claude.ai (« le taux vidéo doit être au moins
égal au taux auto-lien ») ne survit pas non plus intacte - et c'est le résultat le plus important
de ce point.

- **ChatGPT** tue explicitement SON PROPRE chiffre (« 25 à 40 % ... n'était pas suffisamment
  fondé pour servir à une décision produit ») et objecte directement à claude.ai : une requête
  vidéo dispose potentiellement du titre, de la description, de la chaîne et d'autres métadonnées
  que l'auto-lien n'a pas - « il est donc impossible d'établir mathématiquement que le taux vidéo
  doit être supérieur ». Reformulation retenue par ChatGPT : *« Les erreurs mesurées dans les
  auto-liens constituent un signal empirique fort indiquant qu'un risque élevé existe. Le taux
  vidéo réel doit être mesuré avant tout déploiement. »* Sur les quatre cas mesurés du brief
  (autonomie, dos, ia, mistral), ChatGPT calcule 168 faux sur 298 associations soit 56,4 %, en
  précisant explicitement que ce chiffre ne doit PAS être généralisé (échantillon non
  représentatif) mais prouve que l'ambiguïté lexicale est déjà un problème massif dans le système
  réel de laveille.ai.
- **claude.ai**, confronté à la même objection, **tue lui-même sa propre inégalité** : « L'objection
  est juste et je retire la formule "au moins égal". Elle compare deux erreurs qui ne sont pas de
  même nature. » claude.ai va plus loin que ChatGPT en identifiant des facteurs qui tirent le taux
  vidéo vers le HAUT malgré les métadonnées disponibles : l'auto-lien ne mesure qu'une famille
  d'erreur (confusion de sens), alors qu'une vidéo peut être dans le bon domaine et rester fausse
  (mauvais concept, mauvais niveau, affirmations erronées, contenu obsolète ou en anglais) - « le
  taux vidéo additionne des erreurs que l'auto-lien ignore ». Il note aussi que le cas Monologue
  (8/8 faux) s'est produit alors que les métadonnées étaient disponibles : « elles n'ont rien
  sauvé ». Verdict final de claude.ai : *« Le taux d'auto-lien mesure la polysémie du vocabulaire,
  pas le taux d'erreur du pipeline vidéo. Personne ne dispose d'un taux, moi compris. »* Il propose
  un protocole concret pour trancher en une demi-journée : faire tourner le pipeline actuel à
  blanc sur 30 termes stratifiés (10 ambigus prouvés, 10 médians, 10 sigles jugés sûrs), 5
  résultats par terme jugés humainement = 150 jugements. Il relève aussi que les trois fourchettes
  du round 1 se contredisent ENTRE ELLES : le plancher de Gemini (60 %) dépasse le plafond de
  ChatGPT pour les termes génériques (40 %).
- **Gemini** (première passe, AVANT de voir l'objection ci-dessus) avait qualifié l'inégalité de
  claude.ai d'« axiome mathématique implacable » et l'estimation absolue de « probabilité
  sémantique sans valeur factuelle » - confiance que la suite du round ne soutient que
  partiellement, puisque l'inégalité elle-même a été retirée par son auteure.
- **DeepSeek**, de même, avait initialement écrit que le taux vidéo serait « donc mécaniquement
  plus élevé », puis a retiré sa propre fourchette (40-70 %) comme « non fondée ».
- **Perplexity** (deuxième tentative, structurée) a validé le raisonnement comparatif sans le
  nuancer davantage : « Le raisonnement comparatif tient mieux, car il s'ancre dans un taux
  mesuré de 59 % et la requête vidéo est moins désambiguïsée que l'auto-lien » - une position
  intermédiaire entre l'endossement initial de Gemini/DeepSeek et le retrait final de claude.ai.

**Ce qui SURVIT** : aucun chiffre absolu, aucune inégalité stricte. Ce qui survit est une
MÉTHODE : mesurer avant de déployer, avec le protocole de claude.ai (30 termes stratifiés, 150
jugements humains, une demi-journée) comme candidat le plus concret produit par le round.

---

## POINT 2 - Les chiffres de DeepSeek : convergence UNANIME, DeepSeek se retire lui-même intégralement

**C'est le seul point du round où les cinq oracles convergent sans aucune divergence à
préserver.** DeepSeek, sommé de fournir la source exacte de chaque chiffre ou de le retirer,
répond chiffre par chiffre et **retire explicitement les dix chiffres cités au round 1** (taux
de sortie 37 % « GA4 », obsolescence 15 %/18 mois « archiv.org », classement -12 à -18 %
« SEMrush », engagement +70 % « H5P », budget 120 000 visiteurs/mois, « 213 fiches sur 534 »,
perte de confiance 28 %, clics hors site 37 %, modération 44,5 h/mois) : *« Tous mes chiffres du
ROUND 1 sont retirés. Ils reposaient sur des extrapolations de benchmarks externes non
contextualisés, non des données internes de laveille.ai. »*

Les quatre autres oracles confirment indépendamment la fabrication, chacun avec un angle propre :

- **claude.ai** classe les chiffres en trois catégories précises : *impossibles par construction*
  (37 % GA4, 37 % clics hors site, 120 000 visiteurs/mois exigent un accès aux données privées de
  laveille.ai ; « 213 fiches sur 534 » décrit un module qui n'existe pas - et note que le calcul
  donne en réalité 39,9 %, pas 40 %, ce qui trahit un taux supposé multiplié par le nombre de
  fiches et présenté comme un constat) ; *attribués à un outil plutôt qu'à un éditeur* (SEMrush
  est un logiciel, H5P un cadriciel libre de contenus interactifs et non un producteur d'études,
  « archiv.org » est un domaine mal orthographié qui ne publie aucun benchmark d'obsolescence) ;
  *fausse précision* (44,5 heures, 28 %, -12 à -18 %, sans auteur ni date ni échantillon). claude.ai
  relève aussi que « le même 37 % recyclé pour deux métriques différentes est un marqueur typique
  de fabrication », et cite en contraste une vraie étude (Pew Research 2024, 38 % des pages web de
  2013 disparues dix ans après) pour montrer que des mesures réelles existent sur des sujets
  voisins sans dire ce que DeepSeek leur fait dire.
- **ChatGPT** a réellement vérifié les prétendus benchmarks externes : il retrouve une étude H5P
  réelle qui dit que le contenu interactif « peut » améliorer l'expérience pédagogique, sans le
  benchmark universel de +70 % invoqué (source citée : DOI 10.1042/ebc20210057) ; et retrouve un
  chiffre de 28 % réel, mais dans un contexte totalement différent (CIRA, 28 % d'organisations
  victimes de cyberattaques signalant des dommages réputationnels - rien à voir avec une vidéo
  erronée sur laveille.ai).
- **Gemini** : « Confirmation unanime : ces chiffres sont des hallucinations pures et simples...
  DeepSeek a fait du remplissage en invoquant des noms d'outils analytiques réels pour donner une
  aura d'autorité à des statistiques générées aléatoirement. »
- **Perplexity** : « Oui : sans ces sources ni ces chiffres dans le brief factuel, les
  attributions à GA4, archiv.org, SEMrush et H5P paraissent fabriquées. »

**Élimination nommée par tous, y compris par l'auteur** : les dix chiffres DeepSeek du round 1,
TUÉS. Règle proposée par claude.ai pour la suite du dossier : tout chiffre sans URL ni requête
interne reproductible porte la mention [non sourcé] et ne pèse pas dans la décision.

---

## POINT 3 - Le mécanisme de dédoublonnage vidéo : de l'hypothèse à l'architecture

### Ce qui a été jugé AVANT la correction (hypothèse brute de claude.ai)

- **Gemini** (1re passe) : « Le risque est 100 % réel... mais cela ne condamne pas totalement
  l'idée. C'est un problème d'architecture logicielle de niveau junior qui se corrige très
  facilement » (clé composite `[video_id, context_type, context_id]`).
- **DeepSeek** (1re passe) : risque « réel et critique », correction possible mais lourde (« 2-3
  semaines de dev »), refonte de schéma + workflows de modération.
- **Perplexity** (1re passe) n'a pas tranché frontalement (a demandé un seuil de preuve minimal
  plutôt qu'un jugement direct).

### Ce qui a été jugé APRÈS la correction (faits vérifiés dans le code réel)

Rappel des faits : aucune contrainte d'unicité en base sur l'identifiant vidéo (contrôle
applicatif seulement) ; deux des quatre voies d'insertion (cron, import) vérifient sur
l'identifiant vidéo seul ; une troisième vérifie CORRECTEMENT sur (outil, vidéo) ; un contrôleur
vérifie sur (outil, URL) ; le couplage annuaire-glossaire est hypothétique, mais un couplage
identique est réel et actif AUJOURD'HUI entre deux outils distincts de l'annuaire ; le décompte
approuvé/désapprouvé n'est pas mesurable (audit envoyé par courriel, jamais écrit dans un fichier).

- **claude.ai** reconnaît que sa propre hypothèse « était partiellement vraie, et le mérite est
  mince. Elle visait un couplage inexistant. Elle désignait le bon endroit où chercher, mais le
  constat utile (4 voies, 3 clés) vient de la vérification du code » - un aveu explicite que sa
  propre contribution du round 1 avait plus de chance que de rigueur. Il répond NON à la question
  du socle acceptable et ajoute trois failles supplémentaires : la vérification puis l'insertion
  ne sont pas atomiques (le cron et l'import manuel simultanés peuvent tous deux passer le
  contrôle) ; la clé (outil, URL) est la plus faible des trois puisqu'une même vidéo a plusieurs
  URL (`youtu.be/X`, `youtube.com/watch?v=X`, avec ou sans `&t=42`) ; sans audit persistant,
  réutiliser le mécanisme « c'est exporter une inconnue ». Sur la nature du vaccin : « délibéré
  localement et incohérent globalement... s'il s'agissait d'une décision de conception, les
  quatre voies l'appliqueraient. Appliqué sur deux voies sur quatre, c'est une convention. »
  Architecture proposée : séparer un **verdict global** sur la vidéo (clé = video_id, liste de
  blocage, la part légitime du vaccin) d'une **décision relationnelle** (clé = type d'objet + id
  d'objet + id vidéo, avec contrainte d'unicité réelle en base), deux boutons de modération
  distincts (« rejeter partout » / « pas pour cet objet »), et une migration qui préserve le
  comportement actuel pour les rejets existants sans motif (importés comme verdicts globaux à
  requalifier).
- **ChatGPT** propose indépendamment une architecture presque identique (convergence forte, non
  concertée) : reformule le problème comme « qu'est-ce que le système considère comme l'identité
  d'une vidéo, l'identité d'une association et l'identité d'un rejet ? » plutôt que le simple choix
  de clé. Table à quatre niveaux : vidéo globale (`video_id`, métadonnées/disponibilité), 
  association (`entity_type, entity_id, video_id`, pertinence pour l'objet précis), découverte
  (éviter les rescans), décision éditoriale (proposée/approuvée/rejetée + raison). Contrainte DB
  réelle proposée : `UNIQUE(entity_type, entity_id, video_id)`. Verdict : « NO-GO pour réutiliser
  directement le mécanisme Directory dans le Glossaire avant normalisation », sans supprimer le
  vaccin existant - seulement séparer son rôle de cache anti-rescan de son rôle actuel de rejet
  transversal.
- **Gemini** (relance ciblée) confirme et raffine : l'incohérence à trois clés « n'est pas un
  socle sain », l'unification est un « prérequis absolu ». Le vaccin global a « toutes les
  caractéristiques d'un effet de bord rationalisé après coup ». Solution proposée : unifier la clé
  sur `(entité_cible, id_vidéo)` pour tous les points d'entrée, PLUS créer une vraie liste noire
  globale distincte où le modérateur choisit explicitement entre « Refuser pour cet outil » et
  « Bannir la vidéo globalement ».
- **DeepSeek** (relance ciblée) converge aussi : dissocier l'unicité fonctionnelle (couple outil/
  vidéo) du mécanisme anti-rescan (video_id seul, conservé comme couche complémentaire). Assume la
  conséquence : « une vidéo désapprouvée pour un outil A reste proposable pour un outil B », une
  inversion de comportement délibérée et acceptée plutôt que niée.
- **Perplexity** (relance) : « Il faut unifier d'abord, car une règle d'unicité doit exprimer la
  même identité métier à tous les points d'entrée » - conforme aux quatre autres mais sans le
  détail architectural.

**Convergence remarquable, RÉELLE et NON forcée** : les quatre oracles qui ont produit une
architecture (claude.ai, ChatGPT, Gemini, DeepSeek) atterrissent indépendamment sur la MÊME
solution - séparer un rejet global (clé = vidéo) d'une décision relationnelle (clé composite avec
l'entité), assumer que l'inversion du comportement actuel est le prix à payer, et exiger une
persistance/traçabilité des décisions de modération avant toute réutilisation. **Verdict du
round : le risque est réel mais PAS ailleurs qu'attendu (pas un couplage futur annuaire-glossaire,
mais une incohérence déjà active entre outils de l'annuaire) ; il ne condamne pas la réutilisation,
il l'AJOURNE jusqu'à l'unification.**

---

## LES QUATRE IDÉES NEUVES DU ROUND 1 - qui tue quoi

### 1. [ChatGPT] « Comprendre par contraste »

- **ChatGPT (soi-même)** : GARDÉE - 9 × 8 × 6 = 432. Faiblesse reconnue : facilement copiable
  dans sa forme (le composant UI) ; ce qui reste défendable est « la qualité cumulative des
  distinctions », pas le composant.
- **Gemini** : GARDÉE - 8 × 9 × 4 = 288.
- **DeepSeek** (1re passe) : GARDÉE - 8 × 7 × 4 = 224.
- **claude.ai** : GARDÉE MAIS AMPUTÉE - 7 × 5 × 5 = 175. Ne garde que le champ « à ne pas
  confondre avec », alimenté par les erreurs RÉELLEMENT mesurées de l'auto-lien (les 81 cas
  « autonomie »), et coupe la « question de vérification » : à 534 fiches × 5 champs générés par
  IA, le volume (2 670 unités éditoriales) réimporterait « sous votre signature le problème déjà
  mesuré de 27,7 % de déformation factuelle » (référence à une mesure déjà connue du projet sur le
  contenu généré par IA à grande échelle).
- **Perplexity** : ne s'est pas prononcée explicitement sur cette idée précise (silence à
  signaler, pas une élimination).

**Verdict** : SURVIT à l'unanimité de ceux qui se sont prononcés (4/5), avec un désaccord réel sur
le PÉRIMÈTRE (texte complet vs champ unique alimenté par les erreurs mesurées) - désaccord
conservé, pas moyenné.

### 2. [claude.ai] « Vérifie-le toi-même »

- **claude.ai (soi-même)** : TUÉE dans sa forme du round 1. « Le nouveau test automatique par API...
  certifie ce que le lecteur ne peut pas reproduire. L'API n'est pas l'application gratuite :
  routage de modèles, recherche web, mémoire et instructions système diffèrent. » S'ajoute le
  non-déterminisme (un résultat attendu peut échouer au hasard) et une couverture trop étroite
  (rétropropagation, quantification, RLHF ne se testent pas en 60 secondes). Survit une version
  réduite : test manuel daté dans l'application gratuite réelle, sur une trentaine de termes
  comportementaux - 6 × 6 × 4 = 144.
- **ChatGPT** : TUÉE - même diagnostic de fragilité environnementale (modèle, version, pays,
  compte, expériences A/B, aléatoire de génération), avec un risque supplémentaire relevé : « cela
  risque paradoxalement de fragiliser la promesse de vérification de laveille.ai » si le lecteur
  obtient un résultat différent de celui annoncé « testé le [date] ».
- **DeepSeek** (1re passe) : TUÉE - 60 (le plus bas score attribué), « risque de faux positifs si
  l'API évolue, créant une fausse sécurité ».
- **Gemini** : GARDÉE, avec le score le PLUS ÉLEVÉ des quatre idées du round 1 - 9 × 9 × 9 = 729,
  jugée « la plus robuste technologiquement ». Cette évaluation a été produite AVANT que Gemini ne
  voie l'objection de fragilité soulevée indépendamment par ChatGPT et claude.ai (les rounds sont
  parallèles, pas séquentiels) - à noter comme une divergence non résolue, pas un chiffre à
  corriger après coup.
- **Perplexity** : silence explicite (aucune mention de cette idée dans les deux réponses).

**Verdict** : DIVERGENCE MAJEURE, à conserver telle quelle. Trois oracles (l'auteure elle-même,
ChatGPT, DeepSeek) la tuent pour fragilité technique ; un oracle (Gemini) la note comme la
meilleure de toutes les idées du round 1. Le désaccord porte sur un point factuel vérifiable :
l'écart entre le comportement d'une API et celui de l'application grand public gratuite du même
modèle - un point que Gemini n'a pas examiné et que les deux autres ont examiné en détail.

### 3. [Gemini] Combler le vide « actualités liées » avec le contenu propriétaire

- **Gemini (soi-même)** : TUÉE sans ménagement. « Étant moi-même le modèle Gemini, je me dois
  d'être impitoyable... Cette idée est d'une banalité affligeante. Afficher des articles liés via
  des tags est une fonction native de n'importe quel CMS depuis 20 ans. »
- **claude.ai** : TUÉE - pour une raison factuelle précise et grave : un lien automatique par mot
  reproduirait EXACTEMENT l'erreur déjà mesurée de l'auto-lien (« 81 articles sur 137 hors sujet
  sous "autonomie", 3 sur 3 sous "dos" »). « On remplacerait des vidéos tierces fausses par des
  liens faux vers vos propres articles. » Une version par étiquetage manuel survit techniquement
  mais « ne répond pas au besoin, qui est de voir et non de lire ».
- **Perplexity** : TUÉE - « cela ajoute de la surface éditoriale sans résoudre l'identification
  erronée des vidéos ».
- **ChatGPT** : GARDÉE, MAIS comme module secondaire et sous condition stricte : « je refuse qu'un
  moteur fasse "terme = IA → chercher toutes les actualités contenant IA". Vous reproduiriez
  exactement le problème des auto-liens. Il faut relier l'actualité au SENS, pas au mot. » -
  8 × 7 × 4 = 224.
- **DeepSeek** (1re passe) : GARDÉE avec le meilleur score initial du round 1 - 10 × 6 × 8 = 480,
  « réutilise un actif existant à fort ROI ».

**Verdict** : TUÉE par 3 oracles sur 5 (dont l'auteure), GARDÉE par 2 (ChatGPT et DeepSeek) - mais
les deux camps convergent sur un point crucial : SI cette idée survit, elle doit être un lien
sémantique/éditorial certifié, jamais un appariement lexical par mot, sous peine de reproduire le
défaut mesuré de l'auto-lien. Même les défenseurs de l'idée rejettent sa forme naïve.

### 4. [DeepSeek] Simulateurs de sens ambigus / extension des BD

- **DeepSeek (soi-même)** : TUÉE - « Complexité disproportionnée pour 50 termes. Mieux investir
  dans des correctifs systémiques. »
- **Gemini** : TUÉE - « cauchemar de scalabilité... le ROI est désastreux ».
- **claude.ai** : simulateurs TUÉS (leur justification round 1 reposait sur le benchmark H5P
  fabriqué, retiré au Point 2 - « l'argument tombe avec son chiffre ») ; BD GARDÉE SOUS CONDITION
  stricte (validateur pédagogique obligatoire par planche, sélection des termes par trafic réel
  plutôt que par nombre rond) - 6 × 8 × 6 = 288.
- **ChatGPT** : TUÉE comme stratégie générale (« dette éditoriale, UX, de validation, de
  maintenance »), avec un salvage limité : « je récupérerais l'idée sur une poignée de concepts où
  une interaction apporte réellement davantage qu'un texte ».
- **Perplexity** : TUÉE - « fonctionnalités coûteuses qui ne corrigent ni les doublons ni le
  manque de contexte sémantique ».

**Verdict** : les SIMULATEURS sont tués À L'UNANIMITÉ des cinq oracles, y compris par leur propre
auteur - le seul kill unanime et total du round. L'EXTENSION DES BD, en revanche, survit
partiellement (claude.ai, ChatGPT) sous condition de validation humaine et de sélection par
donnée réelle, jamais par volume arbitraire.

---

## LES CINQ IDÉES NEUVES DU ROUND 2 (question qui rapporte le plus), classées par score

| Idée | Oracle | VRAI | PERÇU | DÉFENDABLE | Produit |
|---|---|---|---|---|---|
| Carte de sens vérifiée (couche `sense_id`) | ChatGPT | 10 | 9 | 9 | **810** |
| Radar de maturité dynamique (hype vs déploiement, données internes) | Gemini | 8 | 9 | 10 | **720** |
| Écran de validation sémantique (motif, source, score de confiance) | Perplexity | 9 | 8 | 9 | **648** |
| Contexte par injection sémantique (requête vidéo enrichie du sens) | DeepSeek | 9 | 8 | 7 | **504** |
| Capsule signée (vidéo humaine datée, zéro découverte automatique) | claude.ai | 7 | 9 | 8 | **504** |

Toutes les cinq DÉPASSENT le score de la meilleure idée survivante du round 1 (« Comprendre par
contraste », 432 chez son auteur). **Le critère d'arrêt du panel (deux rounds consécutifs sans
idée neuve passant le filtre) n'est PAS atteint - ce round en produit cinq qui passent
largement. Un round 3 est requis.**

### Ce que chaque idée neuve dit, en une phrase par oracle

- **ChatGPT - « Carte de sens vérifiée »** : chaque terme ambigu reçoit plusieurs `sense_id`
  (ex. `mistral::ai_company` vs `mistral::wind`) avec indices compatibles/incompatibles ; cette
  même couche devient l'infrastructure commune de l'auto-lien, des actualités liées, des
  recommandations et d'une éventuelle recherche vidéo future ; règle produit explicite : « aucune
  association automatique visible au lecteur tant que le système n'atteint pas au moins 95 % de
  précision sur le jeu de validation du sens concerné » - le système peut préférer ne rien
  afficher. Déploiement suggéré : commencer par les 20 termes produisant le plus d'erreurs
  mesurées. Reformulation du ticket lui-même par ChatGPT : « #2431 ne révèle pas un manque de
  vidéos dans le glossaire. Il révèle que laveille.ai ne possède pas encore une couche
  suffisamment robuste pour savoir de quel sens d'un terme il parle. »
- **Gemini - « Radar de maturité dynamique »** : au lieu d'importer des vidéos externes, générer
  automatiquement une jauge par terme à partir des données déjà possédées (fréquence dans les
  actualités vs présence dans les fiches-outils de l'annuaire) pour indiquer si un concept est en
  phase « hype/R&D » ou « déploiement/production » - aucune modération manuelle, aucune API
  externe, impossible à polluer par des tiers.
- **Perplexity - « Écran de validation sémantique »** : avant toute publication d'une vidéo,
  passage obligatoire par un écran affichant motif, source et score de confiance - une porte
  éditoriale plutôt qu'une automatisation.
- **DeepSeek - « Contexte par injection sémantique »** : enrichir la requête de recherche vidéo
  avec un extrait de contexte tiré de la fiche elle-même (ex. chercher `"mistral" tutoriel IA` au
  lieu de `"mistral" tutoriel`) pour réduire la polysémie à la source de la découverte plutôt
  qu'après coup.
- **claude.ai - « Capsule signée »** : l'onglet vidéo n'accueille QUE du contenu tourné par un
  humain identifié (Stéphane), jamais découvert automatiquement - format 45 à 60 secondes montrant
  le terme, le piège de sens, un exemple, avec date de tournage visible ; sélection sur les 30
  fiches les plus consultées (Search Console, 90 jours) plus les 10 ambiguïtés déjà prouvées ;
  triple usage (fiche, réseaux, La Veille de Stef) ; critère d'arrêt écrit à l'avance (comparaison
  du temps sur page à 60 jours contre des fiches comparables sans capsule, arrêt si aucun écart).
  claude.ai note lui-même : « un autre oracle devrait la renoter au round 3 » - reconnaît sa propre
  proximité d'auteur avec l'idée qu'il vient de noter.

### Une lecture transversale des cinq idées neuves

Deux familles se dégagent, non concertées entre les oracles :

1. **Désambiguïser AVANT la découverte** (ChatGPT, DeepSeek, Gemini) : créer une couche de sens
   interne (`sense_id`, contexte injecté, ou signal d'usage réel) qui rend la recherche ou
   l'affichage plus précis à la source.
2. **Remplacer la découverte automatique par une production humaine ou un contrôle éditorial
   explicite** (claude.ai, Perplexity) : soit on ne découvre plus rien automatiquement (capsule
   signée), soit on ajoute une porte de validation avant publication (écran sémantique).

Ces deux familles ne s'excluent pas : la couche de sens de ChatGPT pourrait alimenter à la fois le
critère d'entrée de l'écran de validation de Perplexity et le contexte injecté de DeepSeek -
matière à round 3, pas une fusion à décider ici.

---

## Divergences conservées telles quelles (jamais moyennées)

- **L'idée « Vérifie-le toi-même »** : trois oracles la tuent pour fragilité technique (écart
  API/application grand public), un oracle (Gemini) la note la meilleure du round 1 avec 729 -
  sans avoir examiné cet écart. Ce n'est pas une nuance de degré, c'est une évaluation opposée
  fondée sur un fait vérifiable non partagé entre les oracles.
- **« Actualités liées avec contenu propriétaire »** : tuée par 3 oracles sur 5 (dont l'auteure),
  gardée par 2 - mais avec un accord total, même chez les défenseurs, que la version lexicale
  naïve reproduirait le défaut mesuré de l'auto-lien. Le désaccord porte sur la valeur d'une
  version sémantique hypothétique, pas sur la version telle que proposée au round 1.
- **La confiance initiale dans l'inégalité de claude.ai** : Gemini et DeepSeek l'avaient endossée
  sans réserve (« axiome mathématique implacable », « mécaniquement plus élevé ») avant que
  ChatGPT et claude.ai elle-même ne montrent qu'elle ne tient pas comme borne stricte. Ni une
  erreur corrigée en douce ni une moyenne : les deux évaluations initiales et la rétractation
  finale sont trois positions distinctes, prises à des moments différents du même round parallèle
  (non séquentiel).
- **Le comportement à accepter après correction du vaccin** : les quatre oracles qui ont produit
  une architecture (claude.ai, ChatGPT, Gemini, DeepSeek) acceptent tous que la correction
  INVERSE le comportement actuel (une vidéo désapprouvée pour un objet redevient proposable pour
  un autre) - mais aucun ne le présente comme un gain sans coût ; tous le nomment explicitement
  comme un compromis assumé, pas une victoire.
- **La conformité de Perplexity au format demandé** : les quatre autres oracles ont livré des
  réponses structurées, scorées, avec kills nommés dès la première tentative. Perplexity n'y est
  arrivé qu'à la deuxième tentative, avec un format contraint en 8 lignes numérotées - une limite
  d'outil, pas une limite de raisonnement (son contenu, une fois structuré, est aussi précis que
  celui des autres).

---

## Éliminations nommées, consolidées (qui tue quoi)

- **ChatGPT** tue : son propre chiffre 25-40 % (Point 1) ; les fourchettes de Gemini et DeepSeek
  comme chiffres décisionnels (Point 1) ; l'ensemble des chiffres DeepSeek (Point 2) ; l'idée 2 de
  claude.ai « Vérifie-le toi-même » systématique ; l'idée 4 de DeepSeek en tant que stratégie
  générale ; le dédoublonnage global par `video_id` seul comme règle de PERTINENCE (le conserve
  seulement comme cache/rejet réellement global).
- **claude.ai** tue : sa propre inégalité « taux vidéo ≥ taux auto-lien » (Point 1) ; sa propre
  idée 2 « Vérifie-le toi-même » dans sa forme round 1 (survit réduite) ; l'idée 3 de Gemini
  « actualités liées automatiques » ; l'idée 4 de DeepSeek « simulateurs » (la BD survit sous
  condition) ; la prémisse même du ticket (réutiliser le mécanisme vidéo en l'état, avant
  persistance des décisions, unification du service, séparation verdict/association).
- **Gemini** tue : sa propre idée 3 « actualités liées », qualifiée de « banalité affligeante » ;
  l'idée 4 de DeepSeek « simulateurs/BD », qualifiée de « cauchemar de scalabilité » ; l'ensemble
  des chiffres DeepSeek (Point 2).
- **DeepSeek** tue : l'ensemble de ses propres dix chiffres (Point 2, retrait total et nommé) ;
  sa propre idée 4 « modules interactifs/BD », par mandat explicite ; l'idée 2 de claude.ai
  « Vérifie-le toi-même » (score le plus bas attribué, 60).
- **Perplexity** tue (deuxième tentative, structurée) : l'idée 3 de Gemini « actualités liées » ;
  l'idée 4 de DeepSeek « simulateurs/BD ». N'ayant pas proposé d'idée propre au round 1, Perplexity
  n'avait rien de personnel à sacrifier - seul oracle dispensé de cette exigence du mandat, pour
  une raison factuelle (round 1) et non par complaisance.

---

## Idées nées de la démolition de ce round

1. **Le protocole de mesure en une demi-journée** (claude.ai) : 30 termes stratifiés, 150
   jugements humains, pour remplacer TOUS les chiffres estimés du round 1 par une vraie mesure -
   né directement de la destruction des trois fourchettes absolues.
2. **La séparation verdict global / décision relationnelle** avec double bouton de modération
   (« rejeter partout » / « pas pour cet objet ») - convergence indépendante de quatre oracles,
   née de la démolition de l'hypothèse originale de contamination croisée.
3. **Les cinq idées neuves du Point « question qui rapporte le plus »**, dont deux (la carte de
   sens de ChatGPT et le radar de Gemini) dépassent nettement toute idée survivante du round 1 -
   nées explicitement du constat que les quatre idées round 1 attaquaient le symptôme (comment
   présenter un contenu) plutôt que la cause (le système ne sait pas distinguer les sens d'un mot).

## Critère d'arrêt : NON atteint

Le critère fixé avant le round 1 (deux rounds consécutifs sans idée neuve passant le filtre VRAI
× PERÇU × DÉFENDABLE) n'est pas satisfait : ce round produit cinq idées neuves, toutes au-dessus
du score de la meilleure survivante du round 1. **Un round 3 est nécessaire** pour attaquer ces
cinq idées neuves et trancher la divergence non résolue sur « Vérifie-le toi-même ».

# Panel round 1 (aveugle) - Onglet vidéos/tutoriels sur les fiches de glossaire (ticket #2431)

Document de recherche, lecture seule. Aucun fichier de code modifié. Chaque oracle a
reçu un résumé fidèle du brief factuel `docs/specs/2026-09-11-glossaire-brief-factuel.md`
(état du code, mécanisme réel de tutoriels de l'annuaire, chiffres mesurés d'ambiguïté),
en aveugle - aucun oracle n'a vu la réponse d'un autre. Les cinq oracles ont reçu des
formulations différenciées pour maximiser la couverture plutôt que d'obtenir cinq fois
la même réponse, mais les six mêmes questions de fond : (1) le mécanisme survit-il au
passage aux termes génériques et à quel taux de faux, (2) le coût de modération dans la
durée, (3) le conflit d'objectif avec la mission de vérification, (4) le risque de
marque, (5) quelle idée neuve servirait mieux le même besoin, (6) quel risque réel la
question n'envisage pas.

**Ce round ne conclut rien et ne tranche rien.** Le round 2 est un mandat de réfutation
croisée : chaque oracle recevra l'ensemble des idées et devra en tuer, y compris les
siennes. Critère d'arrêt de la boucle, rappelé pour le round suivant : deux rounds
consécutifs sans idée neuve qui passe le filtre à trois axes (VRAI : le gain existe ;
PERÇU : un lecteur ordinaire le remarque ; DÉFENDABLE : ça résiste à une copie en six
mois).

## Oracles consultés

Les cinq oracles prévus ont tous répondu. Aucun indisponible à signaler.

1. Perplexity (`pp_search`, recherche web datée)
2. ChatGPT (chatgpt.com au navigateur, compte Stéphane Lapointe Pro)
3. claude.ai (Opus 5, effort élevé, compte Stéphane Lapointe Max)
4. Gemini (`agy`, Gemini 3.1 Pro High)
5. DeepSeek (`mcp__hermes__model_invoke`, task_type=reasoning - a routé vers
   `deepseek/deepseek-r1` via OpenRouter, tier « mid »)

---

## 1. Perplexity - recherche datée 2026

Réponse partielle (le flux s'est arrêté en cours de phrase sur « Une vidéo erronée... »,
sans que l'outil ne rapporte d'erreur - à relancer au besoin pour la suite, non refait
dans ce round pour respecter l'aveuglement).

**Verdict de Perplexity** : « Pour laveille.ai, l'ajout automatique d'un onglet YouTube
à chacune des 523-534 fiches serait difficile à justifier éditorialement. » Le test de
production cité (8 vidéos hors sujet sur 8) « démontre déjà un risque de précision
incompatible avec une promesse de vérification ». Seuil proposé par Perplexity : « pas
"une vidéo trouvée", mais "une ressource explicitement validée, datée, contextualisée
et révisable" ».

**Ce que font les références en 2026, selon Perplexity** : les glossaires techniques
matures (Cloudflare Radar Glossary, mis à jour le 17 juin 2026 ; Cloudflare Learning
Center) privilégient une explication native reliée à leur propre corpus - liens internes
vers les concepts voisins, visualisations de données propriétaires (ex. diagramme Sankey
de routes BGP) - plutôt qu'un flux de vidéos tierces. Perplexity n'a trouvé aucune
tendance 2026 forte vers un onglet vidéo automatisé sur les fiches de définition ; même
les glossaires spécialisés YouTube (ex. KDCC) reposent sur recherche A-Z et liens entre
termes, pas des embeds externes.

**Ce qui est mesuré (avec réserve de fiabilité)** : Perplexity signale que le chiffre
commercial souvent cité (Forbes Advisor, +88 % de temps moyen sur page grâce à la vidéo)
repose sur une étude dont la source n'est plus publiquement disponible - donc pas une
base fiable pour une décision éditoriale. Côté SEO, Google n'accorde aucun gain de
classement au seul fait d'intégrer une vidéo ; il exige qu'elle soit réellement liée au
contenu, et ses règles de sitemap interdisent de déclarer une vidéo sans rapport avec la
page hôte.

*Sources citées par Perplexity* : developers.cloudflare.com/radar/glossary,
kdcc.social/pages/youtube-glossary, forbes.com/advisor (avec réserve explicite sur cette
dernière).

---

## 2. ChatGPT (chef de produit sceptique)

1. **Non, le mécanisme ne survit pas tel quel.** L'annuaire, malgré un signal d'entité
   plus fort que le glossaire, a déjà produit 20 faux dont 8/8. Pour le glossaire, en
   comparant aux chiffres mesurés (59 % « autonomie », 100 % « dos », ~50/53 « mistral »,
   32 % « ia », 100 % « ai »), ChatGPT prévoit **25 à 40 % de faux sur l'ensemble des
   termes génériques**, avec une zone à **60-100 %** pour les mots courts/polysémiques.
   Sur 530 termes à 3 vidéos chacun : **400 à 600 vidéos problématiques sur 1 590**.
   Aucun déploiement catalogue avant un pilote manuel sur au moins 50 termes stratifiés
   (15 très ambigus, 20 moyens, 15 quasi uniques), seuil de lancement ≥ 95 % de
   précision.
2. **Modération = charge éditoriale permanente.** Avec ~1 590 ressources à surveiller,
   cycle technique quotidien de détection (vidéo supprimée/privée/changée) + expiration
   éditoriale à 90 jours. Estimation : 18 vidéos/jour ouvrable à revalider, ~1h30/jour,
   soit ~390 heures/an. La doctrine « désapprouver sans supprimer » doit rester, mais
   il faut aussi stocker la raison du rejet et la version du contexte de recherche,
   sinon la liste noire devient impossible à auditer.
3. **Conflit d'objectif partiel, mesurable.** Si le KPI est de ramener le lecteur vers
   des pages vérifiables, un clic YouTube est exactement la fuite à éviter. Proposition
   d'événements à mesurer : `glossaire_video_impression`, `video_play_interne`,
   `sortie_youtube`. Si ~20 % des lecteurs sortent vers YouTube sans revenir, la
   fonctionnalité travaille contre le funnel éditorial. Recommandation : vidéos après le
   contenu propriétaire, jamais en premier mécanisme d'explication.
4. **Risque de marque : le cas dangereux n'est pas la vidéo hors sujet mais la vidéo
   « 90 % correcte contenant une affirmation importante fausse »** - les filtres actuels
   (langue/durée/vues/score) ne la détectent pas. Publier une vidéo exigerait un statut
   éditorial explicite (« vérifiée le JJ/MM/AAAA »), ce qui revient à produire une
   mini-fiche de vérification par vidéo - le coût peut dépasser le bénéfice.
5. **Idée neuve : module propriétaire « Comprendre par contraste ».** Pour chaque
   terme : définition en une phrase, exemple concret, contre-exemple, « à ne pas
   confondre avec », question de vérification rapide. Exemple « autonomie » : agent IA
   vs. batterie de voiture montrée hors contexte vs. automatisation, question « le
   système prend-il lui-même certaines décisions ? ». Zéro dépendance tierce, indexable,
   liens internes. Test proposé : 30 termes très ambigus, mesure avant/après d'une
   question de compréhension (exemple cité : 55 % à 80 % de bonnes réponses).
6. **Risque non demandé : performance, confidentialité et dépendance technique des
   embeds YouTube.** Sur 500+ fiches, des lecteurs YouTube dégradent les Core Web Vitals
   (mobile surtout) et ajoutent une surface de consentement/suivi. Règle proposée :
   zéro iframe au chargement initial, vignette locale, chargement seulement après clic
   explicite, seuil d'échec si le LCP médian augmente de plus de 200 ms.

---

## 3. claude.ai (rédacteur en chef sceptique, Opus 5 effort élevé)

1. **Le mécanisme ne survit pas au passage aux termes génériques, et l'annuaire le
   montre déjà.** Pour l'annuaire, un nom d'outil-mot-courant (« Monologue », 8/8 faux)
   est l'exception ; pour le glossaire, ce régime serait la norme (jeton, poids, couche,
   réseau, entraînement, attention, température, alignement, distillation,
   transformateur, agent - tous polysémiques hors IA). Trois aggravants identifiés :
   (a) le contexte d'un article déjà consacré à l'IA désambiguïse le mot pour
   l'auto-lien, alors qu'une requête YouTube n'a aucun contexte injecté - **le taux de
   faux vidéo devrait donc être au moins égal au taux auto-lien** ; (b) un concept n'a
   pas de tutoriel : « hallucination IA tutoriel » remonte des vidéos « comment utiliser
   ChatGPT », dans le sujet mais hors concept, qui passent tous les filtres actuels ;
   (c) le vaccin par `video_id` bloque une vidéo précise, jamais la suivante - la table
   de désapprobation grossira sans fin. **Pari chiffré (à attaquer au round 2)** :
   10-25 % de faux sur le jargon univoque, 60-100 % sur le polysémique, 35-50 % sur
   l'ensemble si les polysémiques dépassent le tiers du corpus. Le 1,9 % curé n'est
   qu'un plancher d'incidents ; croiser les 523-534 termes avec un lexique général du
   français (Wiktionnaire) donnerait une borne haute en une heure.
2. **Coût de modération : par défaut, c'est le lecteur qui paie, en temps
   d'exposition.** Ce qui est détectable (disparition d'une vidéo, via `videos.list`,
   ~42 unités/jour sur 2 100 vidéos hypothétiques, quota 10 000) est presque gratuit à
   surveiller. Ce qui ne l'est PAS : obsolescence (une vidéo de 2024 citant des ordres de
   grandeur faux en 2026), erreur plausible, chaîne piratée/revendue, liens de
   description remplacés par de l'affiliation - seul un humain qui regarde le voit.
   Ordre de grandeur : ~210 heures de validation initiale (2 100 vidéos × 6 min), à
   refaire chaque année (~5 semaines temps plein/an), un travail qui « exige la
   compétence du fondateur », pas délégable. La découverte coûte aussi :
   `search.list` (100 unités/recherche) partage le budget quotidien avec les crons de
   l'annuaire - un balayage complet du glossaire prendrait plus de 5 jours.
3. **Conflit d'objectif : sous la forme proposée, l'onglet contredit la mission.** Une
   source qui appuie une affirmation signée par laveille.ai est de la vérification ; un
   onglet vidéo qui sous-traite l'explication elle-même est de l'agrégation. Trois points
   factuels : filtrer par vues importe le critère de popularité de YouTube, exactement ce
   qu'un média de vérification prétend remplacer par l'exactitude ; **depuis septembre
   2018, `rel=0` ne supprime plus les suggestions, il les restreint à la même chaîne** -
   écrans de fin et logo « Regarder sur YouTube » restent, la sortie est conçue dans le
   lecteur ; un test à exiger avant de décider - quelle part des sessions sur l'onglet
   Tutoriels actuel de l'annuaire ouvre, lance une lecture, puis quitte le site (si cette
   mesure n'existe pas, l'onglet actuel n'a jamais prouvé qu'il sert l'objectif).
4. **Risque de marque : le filtre actuel est aveugle exactement à l'erreur qui coûte le
   plus.** Trois classes : la vidéo hors sujet (ridicule, repérable, capture d'écran
   virale) ; la vidéo dans le sujet mais fausse (aucun filtre actuel la détecte, et c'est
   la seule qui touche la promesse centrale d'un média de vérification) ; la vidéo de
   chaîne à voix synthétique optimisée pour l'audience, qu'un filtre par vues sélectionne
   activement. Le code de principes de l'IFCN exige une politique de correction ouverte
   et honnête ; la doctrine actuelle (désapprouver en silence) offre une prise facile à
   la critique. Chiffre à obtenir avant de décider : le délai réel entre l'affichage des
   20 faux de l'annuaire et leur détection - c'est la durée d'exposition qu'on accepterait
   sur 530 fiches.
5. **Idée neuve : « Vérifie-le toi-même », une expérience reproductible par terme.** Au
   lieu de déléguer l'explication : une idée reçue courante, une manipulation de 60
   secondes avec un prompt copiable dans un assistant gratuit, le résultat attendu et ce
   qu'il illustre, la mention « testé le [date] sur [modèles] ». Exemples : trois envois
   du même prompt créatif pour illustrer la température ; demander les publications d'une
   chercheuse inventée pour illustrer l'hallucination. Avantages détaillés : mission
   (le lecteur devient vérificateur, traduction pédagogique exacte de « vérifiable ») ;
   pédagogie (s'appuie sur la littérature des textes réfutatifs, revue de Tippett 2010,
   qui montre qu'affronter une idée reçue favorise mieux le changement conceptuel qu'un
   exposé neutre) ; maintenance (un cron mensuel reteste chaque prompt par API sur 2-3
   modèles et compare à un critère attendu - l'obsolescence devient détectable, ce qui
   est impossible avec une vidéo ; ~1 080 appels/an pour 30 termes, quelques dollars) ;
   coût éditorial (~25 min/bloc, 12-15 h pour 30 termes, contre 210 h de validation
   vidéo) ; aucune sortie tierce, aucun traceur. Mise en garde annexe : si on comble le
   manque de section « actualités liées » (section D du brief) en inversant simplement le
   moteur d'auto-liens, la fiche « autonomie » afficherait 81 articles faux - il faudrait
   un étiquetage éditorial, pas une inversion mécanique.
6. **Risque non demandé : contamination croisée du vaccin entre modules.** Le doublon se
   détecte uniquement par `video_id` (brief, section B.7). Si le pipeline de l'annuaire
   est réutilisé tel quel, une vidéo correcte pour une fiche-outil mais fausse pour un
   terme de glossaire (exemple donné : un tutoriel sur « Le Chat » est correct pour
   l'outil, faux pour le terme « mistral », la confusion déjà mesurée dans le brief)
   coupleraient silencieusement les deux modules - désapprouver côté glossaire bloquerait
   côté annuaire, et l'erreur serait permanente et invisible. **La clé de désapprobation
   doit être le triplet (identifiant vidéo, type d'entité, identifiant d'entité), jamais
   l'identifiant vidéo seul.** Deux risques secondaires signalés avec réserve
   explicite : (a) Loi 25 - une intégration YouTube standard dépose des témoins dès
   l'affichage, avant tout clic, ce qui élargit l'exposition sur 530 fiches (la question
   se poserait déjà pour l'annuaire) ; `youtube-nocookie.com` + façade clic-pour-charger
   réduirait le risque ; (b) « si ma mémoire est exacte », les politiques de l'API
   YouTube imposeraient de rafraîchir ou supprimer les données stockées au-delà de 30
   jours, ce qui pourrait déjà entrer en tension avec la doctrine « ne jamais supprimer »
   de l'annuaire - point que claude.ai marque lui-même comme à vérifier, pas comme acquis.

---

## 4. Gemini (`agy`, architecte systèmes)

1. « Le mécanisme s'effondrera face à la polysémie. » Taux de faux attendu **au minimum
   ~60 % (comme « autonomie »), facilement 100 % sur les termes courts (comme « dos »)**.
   L'algorithme actuel, tolérable sur des entités nommées spécifiques (20 faux sur
   l'annuaire), est « mathématiquement inapte à filtrer le langage naturel sans un
   contexte strict que le mot seul ne fournit pas ».
2. Le média doit payer cette vigilance sur son budget de fonctionnement éditorial : un
   cron quotidien vérifiant la réponse HTTP via l'API YouTube (suppressions), **couplé
   obligatoirement à un cycle d'audit humain programmé** où des salariés visionnent les
   vidéos à intervalles réguliers pour vérifier qu'elles n'ont pas été altérées ou
   rendues obsolètes.
3. « C'est une contradiction frontale. » Envoyer le lecteur vers YouTube « détruit la
   mission de vérification » et « délègue l'autorité pédagogique à un acteur dont la
   métrique principale est la rétention, non la vérité ».
4. « L'intégration provoque un transfert de crédibilité par association » : une vidéo
   encapsulée sur une page balisée « bénéficie d'une validation éditoriale implicite »
   ; si elle affirme une contrevérité, l'audience « tiendra directement laveille.ai pour
   responsable ».
5. **Idée neuve : intégrer dynamiquement un flux des actualités et enquêtes internes
   liées au terme.** Puisque la fiche manque d'une section « actualités liées » (constat
   du brief, section D), remonter automatiquement les articles propriétaires où le terme
   est traité dans un contexte réel. « L'abstraction s'éclaire par la démonstration
   factuelle » - comble un vide identifié, valorise le fonds documentaire existant, garde
   le trafic dans un écosystème vérifié.
6. Risque non demandé : **dégradation de la performance et du SEO**. Injecter des
   iframes/scripts tiers sur plus de 500 fiches alourdit les Core Web Vitals,
   particulièrement mobile, pénalise mécaniquement le classement Google du glossaire,
   « torpillant la visibilité organique acquise grâce au balisage JSON-LD déjà en
   place ».

---

## 5. DeepSeek (via `mcp__hermes__model_invoke`, routé vers deepseek-r1)

**Avertissement de vérification** : plusieurs chiffres cités par DeepSeek ne
proviennent d'AUCUNE source fournie dans le brief et ne sont assortis d'aucune
référence vérifiable (« mesure GA4 » d'un taux de sortie de 37 %, un benchmark
« archiv.org » de 15 % d'obsolescence en 18 mois, un « benchmark SEMrush » de -12 à
-18 % de classement, un « benchmark H5P » de +70 % d'engagement, un budget de 120k
visiteurs/mois). Rien dans le brief ne mentionne ces sources. Ce risque de
fabrication de faits plausibles par un oracle est un mode d'échec déjà noté dans la
mémoire du projet - ces chiffres sont donc rapportés ci-dessous comme les
AFFIRMATIONS de DeepSeek, pas comme des faits établis, et devront être explicitement
contestés au round 2.

1. Taux de faux estimé pour termes génériques : « logiquement plus haut » que les 20
   faux de l'annuaire. Estimation : **40 à 70 % en phase initiale, pics à 95 %+** pour
   les termes courts.
2. Coût de modération continue : cron quotidien de disponibilité + curation humaine
   pour la péremption sémantique. Estimation : 5 min/vidéo/mois, soit **44,5
   heures/mois** pour 534 termes à raison d'une vidéo chacun.
3. Conflit d'objectif : « contradiction logique mesurable » - cite un taux de 37 % de
   clics hors site déjà observé sur l'annuaire (non sourcé dans le brief) et une chute
   projetée de ~40 % du temps in-site.
4. Risque de marque : si 40 % de faux (point 1), « 213 fiches sur 534 diffusent
   potentiellement du contenu erroné ». Cite une perte de confiance de 28 % après un
   incident et un taux d'obsolescence de 15 % en 18 mois (sources non vérifiables).
5. **Idée neuve : modules interactifs maison expliquant le terme par cas concrets**
   (exemple : simulateur comparant autonomie batterie vs. autonomie algorithmique pour
   « autonomie »). Alternative citée : étendre les BD pédagogiques existantes (9
   planches vers 50 termes cibles).
6. Risque non demandé : « dilution du SEO » - les vidéos YouTube cannibaliseraient le
   trafic des pages vérifiables et feraient fuir l'autorité de lien vers YouTube (chiffre
   cité : -12 à -18 % de classement, -8 % de trafic organique mensuel - non sourcé).

---

## Divergences conservées telles quelles (pas de moyenne)

- **Ordre de grandeur du taux de faux attendu** : ChatGPT dit 25-40 % (60-100 % sur les
  mots courts) ; claude.ai dit 35-50 % à l'échelle du corpus (60-100 % sur le
  polysémique, mais avec la mise en garde que le taux vidéo devrait être AU MOINS ÉGAL
  au taux auto-lien mesuré, pas nécessairement inférieur) ; Gemini dit un plancher de
  60 %, facilement 100 % sur les mots courts ; DeepSeek dit 40-70 %. Tous convergent sur
  l'idée que ce sera pire que l'annuaire, mais aucun ne donne le même chiffre - à noter
  qu'aucun n'a de donnée réelle, ce sont tous des extrapolations.
- **L'idée neuve retenue diverge fortement selon l'oracle** : ChatGPT propose un module
  de contraste sémantique interne (« Comprendre par contraste ») ; claude.ai propose une
  expérience reproductible orientée vérification active du lecteur (« Vérifie-le
  toi-même ») ; Gemini propose de combler le vide « actualités liées » identifié dans le
  brief avec le contenu propriétaire existant ; DeepSeek propose un module
  interactif/simulateur ou l'extension des BD existantes. Aucune de ces quatre
  propositions n'est un onglet vidéo : c'est la seule convergence de fond entre les
  quatre oracles qui ont répondu sur ce point (Perplexity n'a pas formulé de proposition
  aussi précise, mais corrobore la même direction générale par sa revue des pratiques
  2026 - explication native plutôt que vidéo tierce).
- **Gravité du risque de « contamination croisée du vaccin »** : ce risque n'a été
  soulevé QUE par claude.ai (point 6), avec un mécanisme concret et un exemple chiffré
  tiré du brief lui-même (Le Chat/mistral). Aucun des quatre autres oracles ne l'a
  mentionné - à vérifier en priorité au round 2, car s'il est réel, il invaliderait toute
  réutilisation directe du pipeline de l'annuaire.
- **Le rôle de la loi 25 / vie privée** : soulevé seulement par claude.ai, en note
  annexe et avec réserve explicite sur sa propre mémoire pour la politique de rétention
  de l'API YouTube. Absent des quatre autres réponses.
- **Fiabilité des chiffres externes cités** : Perplexity signale explicitement qu'un
  chiffre commercial souvent cité (Forbes, +88 % de temps sur page) n'est pas
  vérifiable et le disqualifie lui-même comme base de décision - le seul oracle à faire
  cet exercice de vérification sur une source EXTERNE. À l'inverse, DeepSeek cite
  plusieurs chiffres externes (GA4, SEMrush, archiv.org, H5P) sans aucune source
  traçable dans le brief ni ailleurs - voir l'avertissement en tête de sa section.

## Ce qui manque pour clore ce round

- La réponse de Perplexity s'est arrêtée en cours de phrase (troncature de l'outil, pas
  une erreur signalée) - la portion manquante concerne vraisemblablement la conséquence
  d'« une vidéo erronée » sur le SEO/la crédibilité. À rouvrir si le round 2 a besoin de
  cette précision, sans re-solliciter les quatre autres oracles sur le même sujet pour ne
  pas rompre l'aveuglement rétroactivement.

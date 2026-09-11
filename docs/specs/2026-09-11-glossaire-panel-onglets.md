# Panel round onglets - Que mettre en onglet sur la fiche de terme du glossaire (ticket #2435)

Document de recherche, lecture seule. Aucun fichier de code modifié, aucune implémentation. Second
tour de panel, sur une question DIFFÉRENTE de celle déjà tranchée (pas d'onglet vidéo automatique -
voir `2026-09-11-glossaire-panel-round3.md`). Ce tour part du principe déjà dégagé par les trois
rounds précédents : réutiliser ce que le site POSSÈDE DÉJÀ plutôt que d'aller chercher à l'extérieur.

## Acquis rappelé, non rouvert

- Pas d'onglet vidéo/tutoriel YouTube automatique (taux de faux 22,1 %, mais surtout 52 % de VIDE sur
  la strate la plus fiable).
- La fiche de terme est aujourd'hui une seule colonne verticale, sans aucun onglet - introduire un
  onglet est une décision de structure, pas un simple ajout.
- Sont déjà présents : BD pédagogiques maison (9 planches), relations structurées entre termes,
  termes associés, auto-liens plafonnés (1/terme cible, 25/page), FAQ structurée, sources externes,
  JSON-LD complet, partage social.
- Manque le plus visible : aucune section actualités liées au terme (l'Annuaire l'a).
- Deux champs alimentés mais montrés à personne : `views_count`, `sort_order`.

## Protocole

Trois rounds : génération en aveugle, réfutation croisée (mandat de tuer ses propres idées, minimum
deux éliminations non-siennes), attaque des survivantes. Filtre VRAI x PERÇU x DÉFENDABLE (0-10
chacun, classement par produit). Contrainte imposée à chaque proposition : justifier pourquoi elle
mérite un vrai ONGLET plutôt qu'une section déroulante ou une place directe dans la colonne. Exigence
supplémentaire à chaque round : nommer aussi ce qu'il faut REFUSER, et proposer une idée neuve si elle
bat la moitié des propositions déjà sur la table.

**Cinq oracles prévus** : Perplexity (`pp_search`), ChatGPT (chatgpt.com au navigateur, compte
Stéphane Lapointe Pro, effort « Moyen »), claude.ai (Opus 5, effort « Élevé », compte Stéphane Max),
Gemini (`agy`, Gemini 3.1 Pro High), DeepSeek (`mcp__hermes__model_invoke`, `task_type=reasoning`,
routé vers `deepseek/deepseek-r1`).

**Critère d'arrêt écrit avant de lancer** : deux rounds consécutifs sans idée neuve passant le filtre,
OU convergence déclarée des oracles vers un principe unique, plafond de 4 rounds. **Le panel s'arrête
après le round 3** : convergence déclarée et argumentée indépendamment par trois oracles sur quatre
exploitables (claude.ai, ChatGPT, Gemini) vers un même principe - voir section « Pourquoi arrêter ici ».

## Perplexity : inexploitable dès le round 2, signalé nommément

Round 1 réussi (réponse substantielle, sur le fond). Rounds 2 et 3 : **trois tentatives sur trois
échouées**, avec deux tactiques différentes (requête autonome complète avec tout le contexte, puis
reformulation explicite « ceci n'est pas une recherche web, ne cherche rien, réponds au texte fourni »)
- l'outil répond systématiquement par une demande de contenu au lieu de traiter le texte pourtant
fourni dans la requête. Confirme et aggrave la limite déjà mesurée aux rounds précédents (absence de
continuité conversationnelle). Perplexity n'a donc contribué qu'au round 1 ; sa position round 1 est
néanmoins reprise et validée comme point de départ par tous les autres oracles au round 2 (voir plus
bas), donc son absence n'a pas privé le panel de son idée principale.

## Avertissement mesuré - DeepSeek a de nouveau fabriqué, malgré l'avertissement explicite

Round 1 et round 2 : aucune fabrication détectée (chiffres explicitement qualifiés d'« estimation non
vérifiée » quand demandé). **Round 3 : rechute nette**, avec un fait aggravant. DeepSeek a avancé
« 90 % des fiches auraient moins de 3 liens selon l'analyse de claude.ai » (claude.ai n'a jamais donné
ce chiffre), « le corpus actuel de vérifications couvre <10 % des termes (stats internes laveille.ai,
avril 2024) » (source et date fabriquées - avril 2024 n'a même pas de sens dans la chronologie 2026 du
projet), « 95 % des fiches ont ≤2 relations (stats laveille.ai) » (fabriqué), et surtout : « aucun
utilisateur n'a demandé d'onglet dans les tests (**ticket #2401**) » - **le même numéro de ticket
fabriqué** que celui déjà détecté et documenté comme fabriqué au round 2 du panel précédent sur la
vidéo (`2026-09-11-glossaire-panel-round3.md`, section « Fabrications... »), dans une session
complètement différente. Un modèle qui régénère la même fausse citation précise à des mois
d'intervalle, sur un sujet différent, est un signal fort : ses conclusions du round 3 sont retenues
dans ce document mais chaque affirmation chiffrée qui les accompagne doit être traitée comme non
sourcée, jamais comme une preuve.

---

## Round 1 (génération aveugle) - propositions par oracle

### Perplexity
Refuse le principe même d'un onglet pour sa proposition retenue : section « Articles liés »/« Dans
l'actualité » directement DANS LA COLONNE, 3 articles maximum, matching strict (lien explicite > tag/
catégorie > sémantique validé), rien affiché si rien de pertinent. `sort_order` = épingle éditoriale
en cas d'ambiguïté entre articles candidats. `views_count` = usage interne/analytique d'abord.

### DeepSeek
Onglet « Actualités internes liées » (si ≥1 résultat) ; onglet « Statistiques d'usage »
(`views_count`+`sort_order`) réservé aux rédacteurs/admins ; onglet « Arbre des relations » (carte
interactive) ; onglet « BD complémentaires » (brouillons/variantes). Refuse : onglet « Tendances
sociales » (Twitter/Reddit). Idée neuve : onglet « Impact sémantique » - nuage de mots-clés TF-IDF.

### Gemini
Onglet « Actualités & Articles liés » ; onglet « Exploration Visuelle » (carte mentale, ≥3 relations
directes) ; onglet PUBLIC « Métriques & Popularité » (`views_count` + badge « top 10 % »). Refuse :
onglet « Veille Externe/Ressources du Web » (RSS/API tierces) - recréerait l'échec vidéo. Idée neuve :
« Parcours Guidé » - onglet « mode Formation » qui exploite `sort_order` (BD + Précédent/Suivant).

### ChatGPT
Grille : un onglet ne se mérite que si densité non bornée / intention distincte de « comprendre » /
rôle autonome. Onglet « Actualités liées » (priorité élevée) ; onglet « Explorer le concept »
SEULEMENT si vraie carte/arborescence interactive ; onglet « Comprendre en images » (BD) SEULEMENT
avec aperçu maintenu visible dans la fiche principale - cacher les 9 planches actuelles serait une
RÉGRESSION ; onglet « Dossier de vérification » (sources+provenance+statut) ; onglet « Dans
laveille.ai » (tout le corpus interne) - probablement pas les deux à la fois avec « Actualités liées ».
Refuse : onglet « Statistiques/Popularité » ; FAQ en onglet ; Sources seules en onglet. Idée neuve :
architecture par INTENTION du lecteur plutôt que par TYPE de média (Comprendre | Explorer | Actualité
| Vérifier).

### claude.ai (Opus 5, ~8 min, mémoire projet rappelée avec exactitude)
Grille explicite : un onglet ne se justifie QUE si (i) contenu de longueur non bornée, OU (ii)
intention de lecture autre que « comprendre le terme », OU (iii) composant interactif avec état
propre. RÈGLE TRANSVERSALE proposée : la barre d'onglets n'apparaît QUE sur les fiches où au moins un
onglet secondaire atteint un seuil de remplissage mesuré PAR STRATE (extension de la décision vidéo à
TOUS les onglets). Onglet « Sur laveille.ai » (index complet, seulement la liste COMPLÈTE en onglet,
récentes en colonne comme l'Annuaire) ; onglet « Vérifications » (exploite le vrai module de
vérification/fact-check du site - **fait rappelé de mémoire projet et confirmé exact** contre le dépôt
réel entre les deux rounds : v1.202.0, livré le 2026-08-21, 5 verdicts, badge, page publique, JSON-LD
ClaimReview, actualités seulement, lien terme-affirmation à construire) ; onglet « Historique »
(seulement si version ancienne affichable) ; onglet « S'exercer » (quiz, « ma proposition la plus
faible sur le fond »). Refuse (4) : actualités uniquement en onglet ; onglet « Popularité »/
`views_count` ; onglet « Parcours »/`sort_order` (un tri manuel ne prouve aucune logique
pédagogique) ; sources en onglet. Idée neuve : troisième filtre dans « Sur laveille.ai » - outils de
l'Annuaire liés au terme, via champ structuré validé, jamais texte promotionnel.

### Tensions identifiées à l'issue du round 1
Convergence forte (5/5) sur « actualités liées » comme piste n°1, mais divergence réelle sur le LIEU
(colonne pure vs onglet vs hybride) ; divergence sur `views_count` public (Gemini seul pour, 4 contre) ;
divergence sur `sort_order` (Gemini en fait un « Parcours », claude.ai le refuse).

---

## Round 2 (réfutation croisée)

**Perplexity** : inexploitable (voir plus haut).

**DeepSeek** : tue 2 non-siennes (Gemini « Métriques & Popularité », ChatGPT « Dans laveille.ai ») + 3
siennes (« Statistiques d'usage », « BD complémentaires », « Impact sémantique »). D1 : onglet
conditionnel. D2 : mauvais (refuse le public). D3 : claude.ai a raison. Refus neuf : onglet « Sources
brutes ». Idée neuve : onglet « Contexte Historique » (frise chronologique).

**Gemini** : tue 3 de ses 4 propositions round 1, ne garde que « Exploration Visuelle » amputée. D1 :
claude.ai a raison (hybride). D2 : « ma propre proposition était mauvaise » - refuse. D3 : « ma
proposition relevait du bricolage technique » - refuse. Refus neuf : onglet « Espace Communautaire/
Commentaires » - détruirait l'autorité d'une page qui déploie ClaimReview. Idée neuve : onglet
« Controverses & Limites » (biais, procès, législation, failles, écologie), distinct de la définition
neutre.

**ChatGPT** : tue explicitement SA PROPRE « Comprendre en images » (régression) et, pour la première
fois côté non-sien, « BD complémentaires » de DeepSeek. D1 : SURVIT AMPUTÉE - « Sur laveille.ai »
hybride noté **900/1000**, seule note attribuée ce round. « Explorer » : SURVIT AMPUTÉE, seulement si
vrai graphe interactif. « Dossier de vérification » : SURVIT AMPUTÉE. « Vérifications » de claude.ai :
SURVIT sans amputation, jugée « plus précise » que sa propre proposition. « Historique » de claude.ai :
SURVIT AMPUTÉE, fortement conditionnelle. D2 : TUÉE - risque de mécanisme auto-renforçant (populaire →
plus de vues → plus populaire). D3 : TUÉE - « ordre de tri manuel » ≠ « ordre d'apprentissage ». Refus
neuf : onglet « Définition simplifiée/Définition experte » par niveau - fragmenterait la source
canonique (SEO/IA/liens internes). Idée neuve : « Ce terme dans les affirmations » - DISTINCTE de
« Vérifications » de claude.ai, organise les AFFIRMATIONS fausses/trompeuses fréquentes sur le terme
lui-même (pas les articles de fact-check). Conclusion de ce round : seulement 3 familles d'onglets
méritent considération, présomption inverse pour tout le reste.

**claude.ai** (~9 min, réponse la plus systématique du panel - tableau de 34 lignes référencées) :
**résultat le plus radical - tue jusqu'à SES PROPRES quatre onglets du round 1**, y compris son propre
« Sur laveille.ai » (motif : sa propre règle « contenu d'onglet dans le HTML initial » devient
incompatible avec une liste sans limite ; remplacée par une PAGE SÉPARÉE, jamais un onglet). D1 :
Perplexity a raison SUR LE PRINCIPE (colonne) ; la liste complète va sur une page séparée, pas en
onglet. D2 : mauvais, 4 motifs dont un nouveau - un badge « top 10 % » est PAR DÉFINITION absent sur
9 fiches sur 10, « la même faute que l'onglet vidéo vide ». D3 : le refus tient sur la FORME, mais fait
neuf : le panel lui-même donne deux sens incompatibles à `sort_order` (Perplexity = épingle, Gemini =
séquence pédagogique) - preuve que son sens n'est pas établi ; recommande une enquête dans le code
réel avant toute décision. Scores (V x P x D) : (1) actualités en colonne = 512 ; (2) outils Annuaire
liés = 210 ; (3) « Ce terme dans nos vérifications » en colonne = 200 ; (4) statut éditorial + « citer
cette fiche » = 168 ; (5) carte GLOBALE du glossaire (pas par fiche) = 120 ; (6) sections par intention
= 96 ; (7) `views_count` interne = 90 (note PERÇU délibérément basse). Refus neuf : section « en termes
simples » générée par IA à la volée - le site appliquerait aux autres le verdict « contenu_synthetique »
qu'il refuserait de s'appliquer à lui-même ; la BD maison assure déjà cette fonction. Idée neuve :
**page de couverture par terme** - chaque terme dont la couverture dépasse la section colonne obtient
sa propre URL (actualités + articles + outils liés, filtres, pagination, ItemList), remplace à elle
seule cinq onglets tués. **Conclusion explicite : « aucun onglet ne survit sur la fiche à ce stade ».**

---

## Round 3 (attaque des survivantes)

**Perplexity** : troisième échec consécutif, non retenté (voir plus haut).

**DeepSeek** (fabrications notées ci-dessus, à traiter avec prudence) : T1 page séparée l'emporte ; T2
fusion en section colonne ; T3 tue le graphe par fiche, adopte le lien vers carte globale ; T4
renommer `editorial_priority` et garder interne ; T5 supprimer `views_count` (plus radical que les
autres, motivé par une charge serveur non mesurée) ; T6 classement des 4 idées neuves ; **T7 : garde
DEUX exceptions** - onglet « Sources Primaires » (si ≥2 sources vérifiables de qualité) et « Graphe
Complet » (mais explicitement PAS par fiche, un menu général) - donc même DeepSeek ne défend plus
vraiment un onglet PAR FICHE, seulement un onglet « Sources Primaires » dont la justification repose
sur des chiffres fabriqués.

**Gemini** : **retournement complet, endorsement explicite de claude.ai**. « Le verdict radical de
claude.ai (zéro onglet) est EXACTEMENT LE BON, ce n'est pas une sur-correction » - analogie Wikipédia
(les onglets ne se justifient que pour des modes d'interaction mutuellement exclusifs, jamais pour du
contenu encyclopédique séquentiel). Tranche T1/T2/T3 entièrement en faveur de claude.ai. T4 :
supprimer purement `sort_order` de la base, remplacer par un booléen `is_featured` si besoin réel. T5 :
`views_count` en « radar éditorial » interne (alerte sur pic de consultation). T6 : ne garde que 2 des
4 idées neuves du round 2 (« page de couverture » mute, « ce terme dans les affirmations » fusionne),
tue les deux autres. **Idée neuve : « Reverse-Glossaire »** - infobulles contextuelles sur les termes
DANS les articles d'actualité (au survol/tap, définition courte + badge de vérification), plutôt que
d'enrichir la page glossaire elle-même ; « le glossaire doit venir au lecteur, pas l'inverse ».

**ChatGPT** : **capitulation complète - tue SES TROIS survivantes du round 2, y compris sa note de
900/1000**. « Ma note de 900/1000 du round 2 ne tient plus. » Reconnaît explicitement que son critère
« composant trop complexe pour la colonne, donc onglet » était un faux syllogisme - une troisième
option existe, la destination dédiée. T1 : colonne = aperçu, page séparée = corpus complet, onglet =
éliminé. T2 : fusionne « articles de fact-check » et « affirmations » en UNE section colonne
conditionnelle (deux objets distincts en modèle de données, une seule interface). T3 : carte globale
unique, aucun graphe par fiche - « une carte globale permet même moteur, même représentation, même
logique de navigation, focalisation automatique sur le terme courant ». T4 : auditer d'abord dans le
code (qui écrit, qui lit, quelle portée), puis deux issues possibles (garder documenté ou traiter
comme dette technique) - jamais inventer une fonctionnalité pour « rentabiliser » un champ existant.
T5 : garder `views_count`, l'exploiter en priorisation éditoriale et détection de demande, ne jamais
l'exposer au lecteur. T6 : classement des 4 idées, 3 sur 4 absorbées ailleurs, « Controverses &
Limites » de Gemini TUÉE comme section autonome (mélange des biais techniques, procès, législation,
failles de sécurité et impact environnemental sous un même « tiroir négatif » sans rapport nécessaire ;
chaque élément appartient plutôt là où il a un sens propre). **T7 : « ce n'est plus une sur-correction,
c'est le résultat logique du test... la liste finale des onglets retenus : aucun. Et c'est une réponse
positive au ticket, pas une absence d'idée. »**

**claude.ai** (~11 min, réponse la plus rigoureuse et autocritique du panel) : commence par tuer SA
PROPRE section « Ce terme dans nos vérifications » comme section autonome - son titre affirmerait un
lien terme-affirmation qui n'existe pas, elle est absorbée par la section actualités. Répond point par
point à la résistance de ChatGPT sur T1 (argument du clic de navigation supplémentaire) : reconnaît la
mécanique (changer d'onglet ne recharge rien) mais la réfute par quatre objections concrètes - la liste
est une étape vers un article, pas une destination finale (l'onglet économise un chargement sur un
trajet qui en comporte un de toute façon) ; l'onglet ne peut pas tenir ses promesses techniques (HTML
initial = liste sans limite contraire au plafond de 25 liens/page déjà en place ; chargé au clic =
invisible aux robots, sans URL propre) ; il faudrait programmer l'état dans l'URL pour que le bouton
Retour fonctionne, ce qu'une page obtient gratuitement ; sur mobile l'onglet coûte un déplacement pour
remonter à la barre. **Retourne même l'analogie Wikipédia de Gemini contre l'onglet** : sur Wikipédia,
« Lire »/« Modifier »/« Voir l'historique » ont l'apparence d'onglets mais chacun change l'URL
(`action=history`) - « même pour des modes mutuellement exclusifs, Wikipédia utilise des pages
distinctes ». Concède que ChatGPT a raison sur un point (une page réduite à une liste nue ferait
perdre le contexte) et ajoute trois conditions à sa page de couverture (en-tête rappelant le terme,
ouverture dans le même onglet navigateur, 3 entrées récentes déjà visibles en colonne). T4 : ni
suppression aveugle (un champ « alimenté en continu » est écrit par un mécanisme réel, le casser
casserait ce mécanisme) ni renommage cosmétique (« c'est la même faute que le Parcours ») - **enquête
courte et bornée** (qui écrit, qui lit, quelle portée, distribution des valeurs), puis arbre de
décision à trois branches, **explicitement hors du périmètre du ticket #2435 : un ticket séparé**. T5 :
ni suppression (DeepSeek) ni seuil arbitraire « +300 % en 48h non sourcé » (Gemini) - **garder,
vérifier d'abord un risque technique réel qu'elle a elle-même soulevé** puis confirmé par le
rédacteur de ce document contre le code réel du dépôt (voir encadré ci-dessous), puis instantanés
quotidiens et radar back-office calibré sur la distribution réelle. Positionne « Reverse-Glossaire »
de Gemini comme COMPLÉMENTAIRE, pas concurrent, de la page de couverture (deux trajets différents :
« je connais le concept » vs « je lis un article ») ; ajoute 4 risques que Gemini n'avait pas vus
(polysémie visible dans une infobulle, absence de survol sur mobile, critère WCAG 1.4.13 sur le
contenu au survol/focus, besoin d'une définition courte validée) ; recommande un ticket séparé.
**T7, verdict final : « zéro onglet, de façon ferme pour ce ticket, mais pas comme interdiction
permanente »** - un onglet ne se rouvrirait que pour un contenu remplissant DEUX conditions à la fois :
vue mutuellement exclusive du même objet ET perte de valeur sur une URL séparée. Même le graphe par
fiche pour les termes centraux échoue à la seconde condition.

### Fait technique vérifié contre le code réel, né de ce round

claude.ai a soulevé au round 3, en la qualifiant elle-même d'hypothèse « à confirmer sur la version du
dépôt », l'idée que l'incrémentation de `views_count` pourrait toucher `updated_at`, ce qui
corromprait `dateModified` dans le JSON-LD. **Vérifié par le rédacteur de ce document contre le code
réel du projet pendant la rédaction du présent rapport** (hors du panel, lecture directe) :
- `Modules/Core/app/Services/ViewCounterService.php:73` incrémente via
  `$model::query()->whereKey(...)->increment($column)` - un appel à
  `Illuminate\Database\Eloquent\Builder::increment()`.
- `vendor/laravel/framework/src/Illuminate/Database/Eloquent/Builder.php:1331-1336` (Laravel 12,
  confirmé par `composer.json`) : `Builder::increment()` appelle
  `$this->addUpdatedAtColumn($extra)`, qui ajoute `updated_at` aux colonnes mises à jour dès que le
  modèle utilise les timestamps (`Modules/Dictionary/app/Models/Term.php` ne désactive nulle part
  `$timestamps`, donc le comportement par défaut s'applique).
- `Modules/Dictionary/app/Services/TermSchemaService.php:52` : `$dateModified =
  optional($term->updated_at)->toIso8601String();`, injecté aux lignes 135-136 et 167-168 dans le
  JSON-LD `DefinedTerm` et `Article`.
**Conclusion factuelle** : chaque visite d'une fiche de glossaire ayant du trafic touche silencieusement
`updated_at`, donc `dateModified` dans le balisage machine annoncé aux moteurs et aux IA - un signal de
fraîcheur faussé par la seule consultation, jamais par une révision éditoriale réelle, sur un site qui
se définit comme un média de vérification. Ce n'est pas un correctif proposé ici (hors mandat de ce
document), seulement un fait vérifié qui confirme la prudence de claude.ai sur T5 et qui mérite d'être
porté au ticket séparé qu'elle recommande.

---

## Pourquoi arrêter ici (critère d'arrêt écrit avant le round 1)

**Convergence déclarée et indépendamment argumentée vers un principe unique**, atteinte au round 3 :
sur les quatre oracles exploitables, **trois (claude.ai, Gemini, ChatGPT) aboutissent, chacun par un
raisonnement distinct et non concerté, à la même conclusion : aucun onglet ne se justifie sur la fiche
de terme du glossaire, en l'état actuel du contenu et des mesures disponibles.** Gemini l'exprime par
une analogie externe (Wikipédia, modes mutuellement exclusifs) ; ChatGPT par la réfutation de son
propre syllogisme (« trop complexe pour la colonne » ne veut pas dire « donc onglet », une troisième
option existe) ; claude.ai par l'attaque systématique de ses propres quatre propositions du round 1 à
l'aide de contraintes techniques concrètes (plafond de liens, HTML initial, indexation, état d'URL).
Seul DeepSeek maintient une exception (onglet « Sources Primaires »), et cette exception repose sur des
statistiques fabriquées dans la même réponse - elle ne pèse donc pas comme un désaccord de fond
équivalent. Le round 3 a par ailleurs encore produit des idées neuves passant le filtre (le
« Reverse-Glossaire » de Gemini, jugé complémentaire et retenu pour un ticket séparé par claude.ai) :
ce n'est donc pas un tarissement d'idées qui arrête le panel, c'est la convergence. Conformément au
critère fixé avant le round 1, le panel s'arrête à 3 rounds sur un maximum de 4.

## Ce qui reste divergent, non résolu par le panel

- **DeepSeek seul** défend encore un onglet « Sources Primaires » par terme (si ≥2 sources vérifiables
  de qualité) - non retenu ici car appuyé sur des chiffres fabriqués dans la même réponse, mais l'idée
  de fond (les sources méritent un traitement à part si leur nombre grossit) n'a pas été spécifiquement
  attaquée par les autres oracles et pourrait valoir un examen futur indépendant.
- **`sort_order`** : aucun consensus sur l'ACTION finale (supprimer purement - Gemini round 3 ;
  renommer et garder interne - DeepSeek ; enquêter d'abord dans le code réel avant toute décision -
  claude.ai, position retenue ici comme la plus prudente et explicitement hors périmètre du ticket
  #2435).
- **La place exacte du « Reverse-Glossaire »** (infobulles contextuelles dans les articles, idée
  neuve de Gemini au round 3) : jugé complémentaire par claude.ai mais jamais soumis à ChatGPT ni à
  DeepSeek, donc non testé par tout le panel - à traiter comme une piste pour un ticket séparé, pas
  comme une conclusion validée par consensus complet.

---

## Synthèse en 30 lignes

1. Second tour de panel (5 oracles, 3 rounds) sur « quel onglet ajouter à la fiche de terme du
   glossaire », distinct du tour précédent qui avait fermé la porte à un onglet vidéo automatique.
2. Perplexity a contribué seulement au round 1 (3 échecs consécutifs aux rounds 2-3, signalés).
3. DeepSeek a fabriqué plusieurs statistiques au round 3, dont le MÊME numéro de faux ticket
   (« #2401 ») déjà détecté et documenté comme fabriqué dans un tout autre panel, des mois plus tôt -
   ses conclusions sont retenues mais ses chiffres traités comme non sourcés.
4. Round 1 : cinq propositions distinctes par oracle, convergence sur « actualités liées » comme
   priorité n°1, mais désaccord sur le LIEU (onglet, colonne, ou hybride).
5. Round 2 : chaque oracle tue une partie de ses propres idées ; claude.ai va le plus loin et tue SES
   QUATRE propositions du round 1, concluant « aucun onglet ne survit à ce stade ».
6. ChatGPT résiste au round 2 avec 3 familles d'onglets amputés, dont une notée 900/1000 par
   lui-même.
7. Round 3 : ChatGPT retire sa propre note de 900/1000 et tue ses trois dernières survivantes ;
   Gemini se rallie explicitement à claude.ai (analogie Wikipédia : onglets = modes mutuellement
   exclusifs seulement, jamais du contenu encyclopédique).
8. Convergence indépendante de trois oracles sur quatre exploitables vers un principe unique : **le
   panel s'arrête après le round 3**, conformément au critère écrit avant le round 1.
9. **Verdict final consolidé : ZÉRO ONGLET sur la fiche de terme, ferme pour ce ticket mais pas comme
   interdiction permanente** - un onglet ne se justifierait que pour un contenu à la fois (a) vue
   mutuellement exclusive du même objet et (b) qui perdrait sa valeur sur une URL séparée. Aucune des
   propositions testées ne remplit les deux conditions.
10. Ce qui doit exister à la place, sur la fiche même : section « Dans l'actualité » (3 max, absente
    si vide, priorité lien explicite > catégorie > sémantique validé humainement), section « Outils
    liés » (via champ structuré validé, jamais texte promotionnel), ligne « Révisé le [date] · Citer »
    (date éditoriale distincte de `updated_at`).
11. Ce qui doit exister ailleurs : une page de couverture par terme (actualités + articles + outils,
    seulement au-dessus d'un seuil de remplissage mesuré), une carte globale du glossaire (priorité
    basse, jamais un graphe par fiche).
12. Trois tickets séparés identifiés, hors périmètre du #2435 : infobulles contextuelles
    « Reverse-Glossaire » dans les articles (idée neuve de Gemini, jugée complémentaire) ; hygiène de
    données sur `sort_order` (enquête avant décision) et `views_count` (vérification de son effet sur
    `updated_at`, instantanés quotidiens, radar back-office) ; carte globale du glossaire.
13. **Fait technique vérifié pendant la rédaction de ce rapport, né d'une hypothèse de claude.ai** :
    chaque visite d'une fiche de glossaire touche silencieusement `updated_at` (confirmé dans le code
    source Laravel 12 du dépôt), qui alimente `dateModified` dans le JSON-LD - un signal de fraîcheur
    faussé par la seule consultation, jamais par une vraie révision.
14. Refus consolidés du panel, convergents à 4-5 oracles sur 5 : onglet de toute nature sur la fiche ;
    `views_count` public et badge « top 10 % » ; « Parcours »/mode Formation sur `sort_order` ; graphe
    par fiche ; BD à l'état de brouillon ; nuage TF-IDF ; tendances sociales et veille externe ;
    sources/FAQ/BD placées en onglet ; quiz ; définition « en termes simples » générée par IA.
15. Divergence non résolue et non close : DeepSeek seul garde un onglet « Sources Primaires » par
    terme, mais sa justification chiffrée est fabriquée - à réexaminer indépendamment si l'idée
    revient un jour, jamais reprise telle quelle.
16. Décision explicitement laissée à Stéphane : ce document présente le résultat du panel, il
    n'implémente rien.

# Plan complet du glossaire (Modules/Dictionary)

Document de décision. Aucun fichier de code modifié, aucune migration lancée, aucun commit produit
par ce document. Il synthétise et TRANCHE à partir de six documents déjà écrits, qu'il ne refait pas
et ne contredit pas sans preuve nouvelle :

- `docs/specs/2026-09-11-glossaire-brief-factuel.md` (état réel du code, ancré fichier:ligne)
- `docs/specs/2026-09-11-glossaire-mesure-taux-de-faux.md` (mesure réelle, 104 jugements vidéo)
- `docs/specs/2026-09-11-glossaire-panel-round1.md`, `-round2.md`, `-round3.md` (club des sages,
  question « onglet vidéo/tutoriel »)
- `docs/specs/2026-09-11-glossaire-panel-onglets.md` (club des sages, question « quel onglet en
  général »)
- `docs/specs/2026-09-11-mesure-visibilite-et-fraicheur.md` (mesure réelle en production, MESURE B
  seule utilisée ici - MESURE A porte sur le module Authors, hors périmètre du glossaire)

**Chiffre de cadrage** : au 2026-09-11, `dictionary_terms` compte **544 lignes en production**
(`COUNT(*)` total, sans filtre `is_published`, requête directe en base de production - mesure du
même jour, `2026-09-11-mesure-visibilite-et-fraicheur.md`, MESURE B point 4). C'est la mesure la
plus récente et la plus fiable disponible ; elle remplace le chiffre du 2026-09-01 (523 fiches
publiées par sitemap, ou 534 termes selon un balayage dont la méthodologie exacte n'a pas pu être
confirmée - `glossaire-brief-factuel.md`, section A.5), daté de dix jours de plus et de
méthodologie moins sûre.

---

## 1. Ce que le glossaire EST, en une phrase

> **Le glossaire est la page où laveille.ai n'affiche que ce qu'il produit, vérifie ou contrôle
> lui-même - jamais une ressource externe découverte automatiquement, aussi pertinente
> semble-t-elle.**

Cette phrase n'est pas une intention, elle refuse concrètement (test appliqué à chaque cas mesuré
par les quatre panels) :

- L'onglet vidéo YouTube automatique : contenu externe, découvert par une requête, jamais corrigible
  par laveille.ai (« une erreur dans un texte se corrige en une minute, une vidéo se retourne
  difficilement » - claude.ai, `glossaire-panel-round3.md`, section 5, point 5).
- « Vérifie-le toi-même » (test via une API tierce) : le résultat dépend d'un modèle et d'un routage
  que laveille.ai ne contrôle pas et qui peut changer sans préavis (`glossaire-panel-round3.md`,
  section A - argument retenu : la dérive silencieuse, pas le simple aléa).
- La veille externe (RSS, Twitter/Reddit, « Tendances sociales ») : refusée dès le round 1 du panel
  onglets, motif explicite « recréerait l'échec vidéo » (`glossaire-panel-onglets.md`, Gemini round1).
- Les « actualités liées » par appariement LEXICAL automatique (terme = mot, donc chercher tous les
  articles contenant ce mot) : refusées sous cette forme précise parce qu'elles reproduiraient
  l'erreur déjà mesurée de l'auto-lien (81 faux sur 137 pour « autonomie », 3 faux sur 3 pour
  « dos » - `glossaire-brief-factuel.md` C.1) ; elles ne survivent QUE sous une forme où laveille.ai
  garde le contrôle de la validation (lien explicite, catégorie, ou sens validé humainement - voir
  section 6).

Ce que la phrase AUTORISE, à l'inverse : la BD maison (produite), les relations broader/narrower
(curées à la main), la FAQ et les sources externes déjà choisies une par une par un humain au moment
de la rédaction (contrôlées dès l'origine, jamais découvertes après coup), et une association
actualité-terme détectée automatiquement mais validée avant affichage (le contrôle est repoussé après
la détection, jamais supprimé).

---

## 2. La page d'un terme

Structure actuelle intégralement en une colonne verticale, aucun onglet
(`Modules/Dictionary/resources/views/public/show.blade.php:228-513` ; recherche exhaustive de
`role="tab"`, `x-data` de bascule, classe `*-tab-*` : aucune occurrence - `glossaire-brief-factuel.md`
A.2). Le panel onglets confirme et FERME cette structure : convergence indépendante de trois oracles
sur quatre exploitables (claude.ai, Gemini, ChatGPT) vers « aucun onglet ne se justifie », le seul
oracle qui garde une exception (DeepSeek, « Sources primaires ») s'appuyant sur des statistiques
fabriquées dans la même réponse (`glossaire-panel-onglets.md`, section « Pourquoi arrêter ici »).
**Rien à changer sur l'absence d'onglets : c'est déjà l'état du code, et le panel l'a confirmé plutôt
que remis en cause.**

### Ordre proposé (colonne unique, sections existantes + deux ajouts, une ligne retirée)

1. Image hero ou icône - inchangé.
2. Titre H1 + barre d'actions - inchangé.
3. « Aussi appelé » - inchangé.
4. Badges type/difficulté/catégorie - inchangé.
5. **RETIRÉ** : la ligne « Mis à jour le [date] » (voir ci-dessous). Remplacée par « Révisé le
   [date] · Citer » SEULEMENT quand une date éditoriale réelle est connue - sinon rien ne s'affiche à
   cet endroit (règle complète en section 5).
6. `one_sentence_answer` (`<x-core::answer-box>`) - **c'est la section visible sans défilement**,
   avec le H1 : le composant existe précisément pour ça (commentaire du code lui-même, « phrase-
   réponse ≤ 40 mots, citée par AI Overviews et LLM », `show.blade.php:334-337`). Rien d'autre n'a
   besoin d'être au-dessus du pli : la BD, la définition longue et les nouvelles sections suivent.
7. BD pédagogique si `public/bd/{slug}/manifest.json` existe - inchangé.
8. Définition (auto-liée) - inchangé.
9. Analogie + exemple - inchangé.
10. « Le saviez-vous » - inchangé.
11. « Pour aller plus loin » - inchangé.
12. FAQ - inchangé.
13. Sources externes - inchangé.
14. **NOUVEAU** : « Dans l'actualité » (max 3, absente si vide, priorité lien explicite > catégorie >
    sens validé humainement - mécanisme en section 6).
15. **NOUVEAU** : « Outils liés » (champ structuré validé, jamais texte promotionnel - mécanisme en
    section 6).
16. Termes liés (broader/narrower) - inchangé.
17. Termes associés (même catégorie) - inchangé.
18. JSON-LD - inchangé dans sa structure, mais son `dateModified` doit suivre la même règle que le
    point 5 (corollaire logique, hors périmètre exact de ce plan - le correctif est en cours).

**Honnêteté sur la position exacte des points 14-15** : le panel onglets a tranché le CONTENU
(quelles sections) et le LIEU (colonne, jamais onglet), pas la position précise dans la colonne -
recherche faite dans les trois rounds du panel onglets, aucune prescription de position trouvée. La
place proposée ci-dessus (après les sources, avant les relations structurées) est une proposition
éditoriale à valider visuellement, pas une décision déjà rendue par le panel.

### Ce qu'on retire, concrètement

**La ligne « Mis à jour le [date] » (`show.blade.php:327-332`), commentée dans le code lui-même
comme « signal freshness GEO 2026 » et sourcée sur `$term->updated_at`.** Cette colonne est réécrite
à CHAQUE visite de la fiche par `ViewCounterService::record()` (incrément brut, qui déclenche
`addUpdatedAtColumn()` de Laravel) - donc la date affichée ne dit jamais « dernière révision
éditoriale », elle dit « dernière visite d'un lecteur ». Mesuré en production le 2026-09-11 sur les
80 termes (sur 544) qui ont une trace d'édition réelle comparable (`activity_log`) : **78 sur 80 ont
un `updated_at` postérieur de plus de 24 heures à leur dernière vraie modification de contenu, avec
un écart allant de 353 h (~15 jours) à 1 196 h (~50 jours) sur l'échantillon mesuré**
(`2026-09-11-mesure-visibilite-et-fraicheur.md`, MESURE B point 4). Une ligne affichée en gras sur
CHAQUE fiche, qui annonce une fraîcheur presque toujours fausse pour les termes qui ont du trafic, est
exactement le type de contenu que la phrase de la section 1 refuse : laveille.ai ne contrôle pas ce
que cette ligne affirme, elle contrôle seulement qu'un visiteur est passé. Un plan qui laisserait
cette ligne en place tout en ajoutant deux nouvelles sections ne serait qu'une accumulation.

---

## 3. La page de couverture par terme

Idée portée au round 2 du panel onglets par claude.ai, affinée au round 3 : chaque terme dont le
contenu associé (actualités, outils, articles) dépasse ce qui tient dans la colonne obtient sa propre
URL, avec liste complète, filtres, pagination et balisage `ItemList`
(`glossaire-panel-onglets.md`, round 2, claude.ai - « remplace à elle seule cinq onglets tués »).

**Différence en une phrase avec la page du terme** : la page du terme répond à « que signifie ce
mot » - bornée, fixe, faite pour comprendre en une visite (au plus 3 actualités, 5 termes associés,
etc.) ; la page de couverture répond à « que sait laveille.ai de ce mot » - un index exhaustif et
paginé, qui n'existe QUE si le contenu associé déborde la colonne, et qui sert à explorer/parcourir
plutôt qu'à comprendre.

Cette différence est nommable et déjà partiellement construite ailleurs sur le site : le module
Directory a EXACTEMENT ce patron en production - une page dédiée par collection curatée
(`/collections/{slug}`, `Modules/Directory/routes/web.php:19-20`,
`Modules/Directory/app/Http/Controllers/CollectionController.php`), avec balisage `ItemList` déjà
émis (`Modules/Directory/resources/views/public/collections/show.blade.php`, confirmé par recherche
de `ItemList` dans le dépôt). La page de couverture par terme n'est donc pas une idée à inventer de
zéro : c'est le même patron structurel (contrôleur dédié, middleware `cacheResponse`, JSON-LD
`ItemList`), appliqué à une entité différente (Term au lieu de Collection).

**Ce qui n'est PAS mûr, et qu'il faut dire tel quel plutôt que de le meubler** : le « seuil de
remplissage mesuré » qui déclenche la création de cette page n'a jamais été chiffré par aucun des
quatre documents sources - ni le nombre d'actualités/outils qui justifie une page séparée, ni la
proportion de termes qui la déclencheraient. Trois oracles (ChatGPT, claude.ai, Gemini) sont d'accord
sur le PRINCIPE (une page séparée existe seulement au-dessus d'un seuil), aucun ne propose de chiffre
précis. Ce seuil doit être MESURÉ sur le corpus réel après construction des sections « Dans
l'actualité » et « Outils liés » (section 8, étape 4) - le fixer maintenant serait inventer un
chiffre que rien dans les documents sources ne fournit.

---

## 4. Les vidéos (22,1 % de faux)

Mesure réelle : 104 jugements exploitables sur 150 visés, 30 termes stratifiés en 3 strates de 10
(`2026-09-11-glossaire-mesure-taux-de-faux.md`, sections 2-3). **Le faux tient à 100 % dans une seule
strate : la strate A, les 10 termes ambigus (Hub, Socket, Windows, Perplexité (perplexity), Époque
(epoch), Algorithme, Batch, Cheval de Troie, Docker, Latence (latency))** - 23 faux sur 23 mesurés
dans tout l'échantillon s'y trouvent (48,9 % de faux sur cette strate seule), contre 0 % de faux sur
les strates B (techniques composés) et C (noms propres), 0 sur 57 jugements cumulés.

**Mais l'intérieur même de cette strate interdit une règle qui viserait la strate entière.** Le
facteur causal mesuré n'est pas « le mot est ambigu », c'est « un homonyme externe plus gros écrase-t-
il YouTube pour cette chaîne de caractères précise » - et ça se vérifie terme par terme, pas par
catégorie : Docker (0 % de faux, 5/5 pertinents), Latence (0 %, 5/5), Algorithme (0 %, 4/5 pertinents
+ 1 douteux), Cheval de Troie (20 %, 4/5 pertinents) et Windows (20 %, 4/5 pertinents) sont dans la
même strate « ambiguë » que Hub, Perplexité et Époque (100 % de faux chacun) et Socket (80 %). Une
règle qui exclurait toute la strate A détruirait donc **22 vidéos correctement pertinentes sur les 23
mesurées dans cette strate** (5+5+4+4+4, tous les pertinents de Docker/Latence/Algorithme/Cheval de
Troie/Windows) pour n'en avoir jamais eu besoin.

**La règle qui élimine le faux sans toucher au juste** : la même doctrine déjà écrite dans le code
pour l'auto-lien (`ALIAS_NEVER_AUTO`, `GENERIC_NAME_DOMAIN_KEYWORDS` - `glossaire-brief-factuel.md`
C.1/C.3) et confirmée par le round 3 du panel vidéo comme méthode retenue à l'unanimité des quatre
oracles qui ont tranché la question B4 (« liste curée comme mécanisme de DÉCISION »,
`glossaire-panel-round3.md`, section B4) : **une liste curée, un terme à la fois, alimentée
uniquement par un cas mesuré** (jamais une catégorie lexicale, jamais un mot « à risque » par
intuition - la mesure elle-même a démontré que l'intuition se trompe : Docker et Latence étaient
anticipés comme risqués et sont à 0 % de faux). Appliquée à cet échantillon, seuls les termes
individuellement mesurés majoritairement faux (Hub 100 %, Perplexité 100 %, Époque 100 %, Batch
100 % sur N=2, Socket 80 %) seraient exclus - **coût en vidéos justes : 1 seule** (le seul pertinent
mesuré parmi ces cinq termes, celui de Socket), contre 22 pour une règle par strate.

**Ce chiffre reste théorique pour ce ticket** : la section 7 rappelle que l'onglet vidéo automatique
n'est de toute façon PAS retenu, quel que soit le taux de faux - le round 3 du panel a explicitement
fait migrer l'argument du taux de faux (effondré à 22,1 %, bien plus bas que prévu par les cinq
oracles) vers la contrôlabilité et le taux de VIDE (52 % de vide même sur la strate B, la plus fiable
- `glossaire-panel-round3.md`, section B1). Cette section 4 documente la règle correcte au cas où la
question resurgirait, comme demandé par le mandat - elle ne rouvre pas le dossier.

---

## 5. La fraîcheur

Un correctif est en cours ailleurs sur le faux signal (`updated_at` réécrit à chaque vue de page,
`2026-09-11-mesure-visibilite-et-fraicheur.md`, MESURE B) - aucun code n'est écrit ici. Ce que la page
d'un terme doit trancher :

**Ce qu'elle doit AFFICHER** : une date « Révisé le [date] » sourcée d'un évènement éditorial RÉEL
(une modification effective du contenu par un humain ou un mécanisme équivalent), et seulement quand
cette date est connue avec certitude. Sur les 544 termes, seuls 80 ont aujourd'hui une trace
d'édition comparable (`activity_log`, log_name = 'term') - **pour les 464 autres, la page ne doit
afficher aucune date plutôt qu'une approximation** : ni l'`updated_at` corrompu par les vues, ni une
date fabriquée par défaut. Ce choix (rien plutôt que faux) suit le même principe déjà dégagé ailleurs
dans ce dossier pour un cas voisin (section 7 : le faux abîme plus que le vide pour trois oracles sur
quatre) - mieux vaut une absence honnête qu'une date qui ment à chaque fois qu'un visiteur passe.

**Ce qu'elle ne doit plus JAMAIS afficher** : `$term->updated_at` comme date de mise à jour ou de
révision, sous quelque libellé que ce soit. Cette colonne mesure des visites, pas des révisions -
78 des 80 termes mesurables ont un écart de plus de 24 heures entre les deux, jusqu'à ~50 jours sur
l'échantillon. Le même principe s'étend, en corollaire logique et hors périmètre strict de ce plan,
au `dateModified` du JSON-LD de la fiche (section 2, point 18) : un signal de fraîcheur qu'aucun
robot ni aucun visiteur ne devrait recevoir de la simple consultation d'une page.

---

## 6. Architecture - ce qu'on écrit une seule fois

Règle du fondateur : tout en modules activables, jamais de code répété, des blocs dynamiques qu'on
rappelle. Pour chaque élément récurrent de la fiche, voici où vit le mécanisme unique et ce que coûte
un nouvel exemplaire.

| Élément récurrent | Mécanisme unique (où il vit) | Coût d'un nouvel exemplaire |
|---|---|---|
| BD pédagogique | `ComicLibrary::forSlug()` (`Modules/Dictionary/app/Support/ComicLibrary.php:45-98`), convention « déposer un `manifest.json` dans `public/bd/{slug}/` » (docblock ligne 14-22 du même fichier) | Un fichier de données (`manifest.json`), zéro ligne de code - le visionneur, l'indicateur de grille et le filtre « Avec BD » apparaissent automatiquement. Déjà prouvé : 9 planches existantes sans jamais toucher au code. |
| Relations « Termes liés » (broader/narrower) | Deux colonnes JSON sur `dictionary_terms` (`broader_slugs`/`narrower_slugs`, migration `2026_05_27_120000`) + la requête `JSON_EXTRACT` de `show.blade.php:447-509` | Une entrée de tableau (un slug) ajoutée à la fiche existante via le formulaire admin - jamais un nouveau fichier, jamais une nouvelle requête. |
| « Termes associés » (même catégorie) | La requête de `PublicDictionaryController::show()` (lignes 74-78), pilotée uniquement par `dictionary_category_id` | Rien à écrire : assigner la catégorie à la création du terme (déjà un champ obligatoire) suffit à peupler la section. |
| Auto-liens internes (renvoi vers d'autres fiches dans le corps du texte) | `GlossaryLinkifier::linkify()` (`Modules/Core/app/Services/GlossaryLinkifier.php:401`), moteur partagé Dictionary/Acronyms/Directory | Rien à écrire : un terme publié devient auto-liable par son `name`/`aliases`. La SEULE écriture manuelle possible est une entrée dans une constante curée (`ALIAS_NEVER_AUTO`), et seulement après un cas mesuré en production - jamais par anticipation (doctrine déjà écrite au docblock, lignes 911-937). |
| **NOUVEAU** - « Dans l'actualité » | À construire comme jumeau de `NewsToolSyncAction`/`BackfillAutoToolDetectionCommand` (`Modules/News/app/Actions/NewsToolSyncAction.php`, `Modules/News/app/Console/BackfillAutoToolDetectionCommand.php`). Le détecteur existe déjà et tourne déjà sur CHAQUE actualité : `GlossaryLinkifier::getLastMatchedTerms()` renvoie déjà les correspondances de type `'glossary'` (`GlossaryLinkifier.php:567,584,601,620`) - `NewsToolSyncAction::suggest()` les calcule à la ligne 301 puis les REJETTE actuellement (ligne 302, `filter(type === 'tool')`) pour ne garder que les outils. | Une ligne de pivot (`news_article_id`, `term_id`, `source`) dans une table jumelle de `news_article_tool` (même forme exacte que la migration `2026_06_29_000000_create_news_article_tool_table.php` : deux clés étrangères, colonne `source` par défaut `manual`, contrainte unique composite). Zéro nouveau moteur de détection : celui qui tourne déjà aujourd'hui sur chaque actualité calcule déjà la bonne réponse, elle n'est simplement pas encore écrite nulle part pour les termes. |
| **NOUVEAU** - « Outils liés » | Un pivot curaté `term_id`/`tool_id`, jamais auto-détecté (refusé explicitement dès le round 1 du panel onglets : « via champ structuré validé, jamais texte promotionnel ») | Une ligne de pivot ajoutée par un humain en admin - jamais de texte à rédiger. |
| **NOUVEAU** - page de couverture (ailleurs, hors fiche) | Patron déjà en production côté Directory : `CollectionController` + route `/collections/{slug}` (`Modules/Directory/routes/web.php:19-20`), JSON-LD `ItemList` déjà émis (`collections/show.blade.php`) | Un contrôleur et une vue jumeaux du patron Directory (même middleware `cacheResponse`, même schéma `ItemList`) - jamais un système de pagination/JSON-LD réinventé. |
| Ligne « Révisé le [date] » | À construire (section 5, hors périmètre d'implémentation de ce plan) | Une seule source de vérité par terme (une date, jamais recalculée à partir de `updated_at` au moment du rendu). |

### Un cas choisi pour NE PAS factoriser, et pourquoi

La fiche affichera bientôt QUATRE mécanismes de « choses liées au terme » : Termes liés
(broader/narrower), Termes associés (même catégorie), Dans l'actualité (pivot détecté), Outils liés
(pivot curaté). Ils se ressemblent en apparence (une liste d'éléments sous le terme) - **ils ne sont
pas fusionnés en un mécanisme générique « related items »**, parce qu'ils encodent quatre règles
métier distinctes qui évolueront pour des raisons différentes : Termes liés est une hiérarchie
taxonomique curée à la main, rarement modifiée, sémantiquement exacte par construction ; Termes
associés est une heuristique de proximité gratuite (même catégorie) qui n'aura jamais besoin de
curation ; Dans l'actualité est une association détectée à haut volume, qui a besoin d'un outillage de
modération (désapprouver-jamais-supprimer, la même doctrine déjà écrite pour les tutoriels de
l'annuaire - `glossaire-brief-factuel.md` B.7) parce que des faux positifs sont possibles ; Outils liés
est délibérément non automatique, refusé par le panel comme candidat à toute détection. Fusionner ces
quatre mécanismes ferait porter à un seul modèle des évolutions qui n'ont rien à voir entre elles - le
couplage accidentel coûterait plus cher que les quatre pivots distincts, exactement le seuil
qu'impose la doctrine DRY nuancée du projet.

---

## 7. Ce qu'on ne fait PAS, et pourquoi

| Idée | Motif de mise à mort | Source |
|---|---|---|
| Onglet vidéo/tutoriel YouTube automatique | Taux de faux (22,1 %) et son origine (homonyme externe dominant) ne sont plus l'argument décisif : ce qui tient, c'est l'absence de contrôlabilité (le système ne prédit pas quand il changera de régime) et le taux de VIDE (52 % même sur la strate la plus fiable) | `glossaire-panel-round3.md`, B1 |
| Onglet de toute nature sur la fiche | Convergence indépendante de 3 oracles sur 4 exploitables (analogie Wikipédia de Gemini, réfutation du syllogisme « trop complexe pour la colonne donc onglet » de ChatGPT, attaque technique de claude.ai sur ses propres 4 propositions : plafond de 25 liens/page, HTML initial, indexation robots, état d'URL) | `glossaire-panel-onglets.md`, rounds 2-3 |
| « Vérifie-le toi-même » (test reproductible via API) | Dérive silencieuse du modèle par défaut d'une application grand public, sans préavis - une preuve d'aujourd'hui peut devenir fausse demain sans que le site le sache | `glossaire-panel-round3.md`, section A |
| « Radar de maturité dynamique » | Les données disponibles (fréquence éditoriale interne) ne mesurent pas ce que l'idée prétend afficher (maturité réelle d'une technologie sur le marché) - tué par les 3 oracles qui l'ont examinée sérieusement, y compris son auteur | `glossaire-panel-round3.md`, idée 2 |
| « Injection sémantique de contexte » (enrichir la requête vidéo) | Tuée par la mesure elle-même : sa seule strate utile est celle qu'on retire de toute façon (48,9 % de faux), et sur un cas déjà « injecté » (Autonomie (IA)), le résultat mesuré est le VIDE, pas la précision | `glossaire-panel-round3.md`, idée 4 |
| « Capsule signée » (vidéo humaine maison) | Tuée par son propre auteur : fait doublon avec un actif déjà en production (les 9 planches BD), non scalable (dépend d'un fondateur seul), péremption cachée derrière une date de tournage | `glossaire-panel-round3.md`, idée 5 |
| Simulateurs interactifs | Tués à l'unanimité des 5 oracles, y compris l'auteur - complexité disproportionnée pour la cible, ROI jugé désastreux | `glossaire-panel-round2.md`, idée 4 |
| « Actualités liées » par appariement lexical automatique (mot = mot) | Reproduirait l'erreur déjà mesurée de l'auto-lien (81/137 faux sur « autonomie », 3/3 sur « dos ») ; survit seulement sous forme sémantique/curatée validée (devient la section « Dans l'actualité ») | `glossaire-panel-round2.md`, idée 3 |
| « Carte de sens vérifiée » (`sense_id`, seuil 95 % de précision) | Démontrer 95 % de précision avec confiance exige ~59 jugements consécutifs sans erreur par sens (règle de trois, Hanley & Lippman-Hand, *JAMA* 1983) ; le corpus mesuré n'en fournit que ~3,5 par terme - la règle devient une curation manuelle déguisée. Un identifiant de sens interne ne change de toute façon rien à ce que YouTube renvoie (le facteur causal est externe) | `glossaire-panel-round3.md`, idée 1 |
| `views_count` public + badge « top 10 % » | Un badge « top 10 % » est par définition absent sur 9 fiches sur 10 (même faute que l'onglet vidéo vide) ; risque de boucle auto-renforçante (populaire → plus vu → plus populaire) | `glossaire-panel-onglets.md`, rounds 2-3 |
| « Parcours »/mode Formation sur `sort_order` | Un tri manuel ne prouve aucune logique pédagogique ; le panel donne lui-même deux sens incompatibles à `sort_order` (épingle éditoriale vs séquence pédagogique) - preuve que son sens n'est pas établi | `glossaire-panel-onglets.md`, round 2 (claude.ai) |
| Graphe/carte interactive PAR FICHE | Remplacé par une carte GLOBALE unique (même moteur, même logique de navigation, focalisation sur le terme courant) - un graphe par fiche duplique l'infrastructure sans bénéfice | `glossaire-panel-onglets.md`, round 3 (ChatGPT) |
| Nuage de mots-clés TF-IDF | Bricolage technique sans base solide, refusé par son propre auteur | `glossaire-panel-onglets.md`, round 2 (Gemini) |
| Veille externe (RSS, Twitter/Reddit, « Tendances sociales ») | Recréerait l'échec vidéo (contenu tiers non contrôlé) | `glossaire-panel-onglets.md`, round 1 |
| Sources/FAQ/BD placées en onglet | Ce sont déjà des sections colonne fonctionnelles et visibles au chargement ; les mettre en onglet contredit le principe du HTML initial | `glossaire-panel-onglets.md`, rounds 1-2 |
| Quiz/« S'exercer » | « Ma proposition la plus faible sur le fond » (l'auteure elle-même) | `glossaire-panel-onglets.md`, round 1 (claude.ai) |
| Définition « en termes simples » générée par IA à la volée | Le site s'appliquerait à lui-même le verdict « contenu synthétique » qu'il refuse d'appliquer aux autres ; la BD assure déjà cette fonction | `glossaire-panel-onglets.md`, round 2 (claude.ai) |
| Espace communautaire/commentaires | Détruirait l'autorité d'une page qui déploie ClaimReview | `glossaire-panel-onglets.md`, round 2 (Gemini) |
| « Comprendre par contraste » (mini-leçon complète générée à volume sur 544 fiches) | Réimporterait sous signature laveille.ai le problème déjà mesuré de 27,7 % de déformation factuelle du contenu IA à grande échelle | `glossaire-panel-round2.md`, idée 1 (claude.ai) - **nuance : une version amputée à un seul champ reste en dissensus non tranché, voir section 9** |

---

## 8. Ordre d'exécution

Priorité au défaut ACTIF en production aujourd'hui, jamais à ce qui est le plus agréable à
construire.

1. **Fermer le signal de fraîcheur faux sur la fiche de terme** (retirer `show.blade.php:327-332`
   basé sur `$term->updated_at`, appliquer la règle de la section 5). C'est le seul défaut de ce
   dossier qui trompe activement, aujourd'hui, à chaque visite d'une fiche, un visiteur ET les
   moteurs qui lisent le JSON-LD - pas une fonctionnalité manquante, une affirmation fausse déjà
   publiée. **Preuve de fin** : zéro occurrence de `$term->updated_at` dans le fichier de vue
   (grep) ; les 78 termes mesurés en dérive de plus de 24 h n'affichent plus aucune date fausse (ou
   n'affichent rien) ; capture visuelle avant/après sur un terme concerné.
2. **Construire « Dans l'actualité »** (pivot `news_article_term`, jumeau de `news_article_tool`,
   réutilisant `GlossaryLinkifier::getLastMatchedTerms()` déjà calculé - section 6). **Preuve de
   fin** : table créée, commande de rattrapage exécutée sur le corpus existant, section visible sur
   au moins un terme réel ayant ≥1 article, absente et invisible sur un terme sans correspondance
   (capture des deux cas).
3. **Construire « Outils liés »** (pivot curaté, jamais auto-détecté). **Preuve de fin** : section
   visible seulement quand une association validée existe en base, test automatisé qui vérifie
   l'absence de section vide sur un terme sans association.
4. **Mesurer le seuil de remplissage de la page de couverture** sur le corpus réel, une fois les
   étapes 2-3 en place (combien de termes dépasseraient 3 actualités ou 5 outils liés), PUIS
   construire la page de couverture (jumelle du patron `/collections/{slug}`) seulement pour les
   termes qui dépassent ce seuil mesuré. **Preuve de fin** : le nombre de termes concernés est écrit
   noir sur blanc (pas une estimation) ; la page rend un `ItemList` JSON-LD valide sur un exemple
   réel.
5. **Ouvrir les tickets séparés déjà identifiés par le panel**, pour ne pas les perdre : enquête
   `sort_order` (qui écrit, qui lit, quelle portée - avant toute décision) ; enquête `views_count`
   (confirmer son effet déjà mesuré sur `updated_at`, instantanés quotidiens, radar back-office) ;
   « Reverse-Glossaire » (infobulles contextuelles dans les articles, idée complémentaire de Gemini,
   jugée telle par claude.ai). **Preuve de fin** : trois tickets créés et référencés.
6. **Carte globale du glossaire** (priorité BASSE, explicitement, par le panel lui-même) - en dernier,
   parce que c'est la pièce la plus agréable à construire (une vraie visualisation) et la moins
   urgente : aucun défaut actif ne dépend d'elle. **Preuve de fin** : rendu visuel validé, priorité
   confirmée basse dans la todolist.

---

## 9. Dissensus non résolus

### 9.1 Le dissensus signalé de DeepSeek : le faux abîme-t-il plus que le vide, ou l'inverse ?

Trois oracles (ChatGPT, claude.ai, Gemini) convergent sur le FAUX comme dommage principal (« nous
avons vérifié cette ressource » touche directement la promesse de vérification ; une capture d'écran
d'une erreur se partage, un vide non ; le faux vise la promesse centrale du site). **DeepSeek
maintient, à deux reprises et sans faiblir, la position inverse** : le VIDE abîme davantage
(« implique une absence de service ... sape la crédibilité : un média de vérification doit fournir
des réponses, pas des silences » - `glossaire-panel-round3.md`, section B3). Ce n'est pas un bruit à
moyenner : c'est un désaccord réel, sur un point qui dépasse la seule question vidéo et touche
directement les sections 2, 6 et 8 de ce plan (« Dans l'actualité » et « Outils liés » n'affichent
RIEN plutôt qu'une correspondance incertaine).

**Question à Stéphane** : quand une association ne peut pas être garantie exacte (aucun lien
explicite, aucune catégorie claire), la fiche doit-elle afficher la meilleure correspondance probable
malgré le risque, ou rester silencieuse ?

**Recommandation** : rester silencieuse (suivre les 3 oracles contre DeepSeek). Motif : ce choix est
déjà celui retenu partout ailleurs dans ce plan (section 5, la date de fraîcheur ; section 2, les
sections « absentes si vide ») - une exception ici créerait une incohérence interne au document, et
c'est la position de la majorité du panel sur le point précis où le désaccord porte.

### 9.2 « Comprendre par contraste » amputée : faut-il un champ « à ne pas confondre avec » écrit à la main ?

Deux oracles la gardent (ChatGPT, claude.ai) sous condition de changer sa SOURCE (les homonymes
externes réellement mesurés côté vidéo - Hub/Minecraft, Perplexité/produit, Époque/jeu vidéo - plutôt
que les erreurs internes de l'auto-lien, jugées creuses pour le lecteur) ; deux la tuent (Gemini,
DeepSeek), jugeant que réduire une idée pédagogique à un seul champ n'est plus de la pédagogie
(`glossaire-panel-round3.md`, idée 6). Score le plus favorable : 448/1000 chez claude.ai, la
survivante la mieux placée du round.

**Question à Stéphane** : faut-il ajouter, écrit à la main, un court champ « à ne pas confondre avec
[homonyme] » sur les seuls termes où une confusion externe a été RÉELLEMENT mesurée (une poignée de
cas, pas les 544 fiches) ?

**Recommandation** : oui, mais strictement borné aux termes où la mesure vidéo (section 4) a déjà
prouvé une confusion majoritaire (Hub, Perplexité, Époque, Batch, Socket dans l'échantillon actuel -
5 cas). Motif : le coût de rédaction est minime (une poignée de termes, rédaction humaine, pas de
génération IA à volume), le bénéfice est directement documenté par la mesure elle-même, et cette
version bornée ne rouvre pas la porte à la génération de masse déjà refusée pour la forme originale de
l'idée (section 7).

### 9.3 Les actualités internes : pilier pédagogique central ou section secondaire ?

À la question de fond posée au fondateur, ChatGPT inclut un pont vers les actualités internes comme
composante à part entière de la réponse pédagogique ; claude.ai l'exclut explicitement, restant sur
les deux actifs déjà éprouvés (BD + relations), au motif de la garantie d'exactitude dans le temps
(`glossaire-panel-round3.md`, section C - « ce désaccord reste ouvert, aucun des deux n'a eu
l'occasion d'attaquer la proposition de l'autre »).

**Question à Stéphane** : la section « Dans l'actualité » (déjà retenue dans ce plan, section 2)
doit-elle être présentée comme un pilier pédagogique central de la fiche (« comprendre le terme par
son usage réel »), ou rester une section secondaire de contexte, sans prétention pédagogique ?

**Recommandation** : section secondaire (suivre claude.ai). Motif : un pilier pédagogique central doit
rester garanti dans le temps ; une actualité est par nature datée et peut devenir non pertinente ou
périmée. Lui donner un rôle central créerait la même fragilité de péremption déjà identifiée comme
motif de mort de la « capsule signée » (section 7) - un pilier qui vieillit mal ne devrait pas porter
la charge pédagogique principale.

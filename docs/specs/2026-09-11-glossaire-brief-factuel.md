# Brief factuel - Glossaire : onglet vidéos/tutoriels ? (ticket #2433)

Document de mesure, lecture seule. Aucune modification, aucun commit, aucune
recommandation. Chaque affirmation est ancrée fichier et ligne, au moment de la
lecture (2026-09-11). Sert de socle commun avant consultation d'un panel d'IA.

## A. La fiche de glossaire aujourd'hui

### A.1 Structure en base (table `dictionary_terms`)

Table créée par `Modules/Dictionary/database/migrations/2026_03_20_000002_create_dictionary_tables.php:28-38`
(id, `name`/`slug`/`definition` en JSON traduisible, type, dictionary_category_id,
is_published, sort_order). Colonnes ajoutées ensuite par migrations additives
successives : `acronym_full` (2026_03_23_220000), `hero_image` (2026_03_23_180000),
`match_strategy` (2026_05_05_180000), `aliases` (2026_05_05_220000),
`broader_slugs`/`narrower_slugs` (2026_05_27_120000), `one_sentence_answer`/`faq`/
`sources` (2026_03_22_000001_add_enriched_fields_to_dictionary_tables.php), et enfin
`views_count` (2026_08_28_090000_add_views_count_to_dictionary_terms.php:44-49).

Modèle `Modules/Dictionary/app/Models/Term.php:47-71` (`$fillable`) : `name`,
`acronym_full`, `slug`, `definition`, `analogy`, `example`, `did_you_know`,
`one_sentence_answer`, `faq`, `sources`, `difficulty`, `icon`, `hero_image`,
`reference_url`, `reference_label`, `type`, `dictionary_category_id`,
`is_published`, `match_strategy`, `aliases`, `sort_order`, `broader_slugs`,
`narrower_slugs`.

### A.2 Ce qui est RÉELLEMENT rendu au visiteur (vue `public/show.blade.php`)

La fiche est **une seule colonne verticale** : tout vit dans une unique balise
`<article class="gl-main-card">` (`Modules/Dictionary/resources/views/public/show.blade.php:228`
à `513`). Aucune trace d'onglet dans ce fichier (aucun `role="tab"`, aucun
`x-data` de bascule d'onglet, aucune classe `*-tab-*`) - à comparer au point A.4
et à la section D.

Dans l'ordre d'affichage réel :
1. Image hero (si présente) ou icône, sinon rien - lignes 230-249.
2. Titre H1 + barre d'actions - lignes 252-253.
3. Ligne « Aussi appelé » (acronym_full + aliases fusionnés) - lignes 272-294.
4. Badges type / difficulté / catégorie - lignes 296-325.
5. Date de mise à jour - lignes 327-332.
6. `one_sentence_answer` via le composant `<x-core::answer-box>` - ligne 338.
7. BD pédagogique si `public/bd/{slug}/manifest.json` existe (`ComicLibrary::forSlug()`,
   lignes 341-346).
8. Définition (auto-liée par `GlossaryLinkifier`) - lignes 354-362.
9. Analogie et exemple, côte à côte en grille bento - lignes 364-385.
10. « Le saviez-vous » - lignes 387-395.
11. « Pour aller plus loin » (reference_url/reference_label) - lignes 397-405.
12. FAQ (si tableau `faq` non vide) - lignes 407-422.
13. Sources externes (si tableau `sources` non vide) - lignes 424-445.
14. Termes liés (broader_slugs/narrower_slugs, requête `JSON_EXTRACT` sur
    `dictionary_terms`) - lignes 447-509.
15. Termes associés (même catégorie, limite 5) - lignes 517-545, alimentés par
    `PublicDictionaryController::show()` lignes 74-78.
16. JSON-LD complet (`TermSchemaService::buildGraph()`) - lignes 555-556.

### A.3 Champs qui existent en base mais ne sont PAS rendus au visiteur

- **`views_count`** : incrémenté à chaque visite via
  `ViewCounterService::record($term, 'views_count')`
  (`Modules/Dictionary/app/Http/Controllers/PublicDictionaryController.php:72`),
  avec le même filtre anti-robot que le reste du site. Recherche exhaustive
  (`grep -rn "views_count"` sur `Modules/Dictionary` et `Modules/Core`, hors
  tests et migrations) : **aucune** occurrence dans une vue Blade, ni publique
  ni admin. Le compteur tourne, personne ne le voit - ni le visiteur, ni
  l'équipe. Le commentaire du contrôleur (ligne 70-71 du même fichier) dit
  encore « jamais activée sur ce module » alors que la colonne existe depuis
  le 2026-08-28 : commentaire périmé, non corrigé au moment de la lecture.
- **`sort_order`** : capturé dans le formulaire admin sous le libellé « Ordre
  d'affichage » (`Modules/Dictionary/resources/views/admin/create.blade.php:70-72`,
  `admin/edit.blade.php:74-76`, `TermAdminController.php:44,59,85,98`), mais la
  requête publique qui liste les termes (`PublicDictionaryController::index()`,
  lignes 27-29) trie exclusivement par nom, en alphabétique insensible à la
  casse (`LOWER(JSON_UNQUOTE(JSON_EXTRACT(name...)))`). Seul
  `Category::orderBy('sort_order')` (ligne 58, pour les catégories) utilise
  réellement ce champ - jamais `Term.sort_order`.
- **`match_strategy`** : configure uniquement le moteur d'auto-liens interne
  (`GlossaryLinkifier`), jamais affiché.

### A.4 Comparaison directe : la fiche annuaire A des onglets, la fiche glossaire n'en a PAS

`Modules/Directory/resources/views/public/show.blade.php:398` déclare
`x-data="{ tab: (...['info','reviews','discussions','resources','screenshots',
'alternatives','news']...) }"`, et la barre d'onglets réelle (lignes 570-578)
liste : Informations, Avis, Discussion, **Tutoriels** (`$resources->count()`),
Screenshots, Alternatives, Actualités. C'est cet onglet « Tutoriels » que
Stéphane envisage de transposer au glossaire.

### A.5 Nombre de termes en production

Deux mesures distinctes existent dans le dépôt, datées du même jour
(2026-09-01), avec des méthodologies différentes :

- **523 fiches** - mesuré par `curl https://laveille.ai/sitemap.xml` (sitemap
  RÉEL de production, donc fiches publiées uniquement), cité dans le
  commentaire de la migration
  `Modules/Dictionary/database/migrations/2026_09_01_233000_add_infostealers_terms.php:16-19`,
  juste avant que cette même migration n'ajoute 6 fiches (Vidar, LummaC2,
  StealC, RedLine Stealer, Acreed, Atomic Stealer).
- **534 termes, 312 acronymes** - mesuré par balayage direct du contenu réel
  via `GlossaryLinkifier::linkify()` (`CHANGELOG.md:1261`, entrée `[1.244.14]`).
  Ce chiffre semble être un compte total en base (`Term::count()`), pas
  nécessairement filtré sur `is_published` comme le sitemap ; il n'est pas
  possible de confirmer la méthodologie exacte depuis le seul texte du
  changelog. **Point important** : les « 312 acronymes » désignent le module
  **Acronyms**, un catalogue séparé (route `/acronymes-education`, table
  distincte) qui partage le même moteur d'auto-liens mais n'est PAS le module
  Dictionary (Glossaire Techno) visé par ce ticket - à ne pas confondre.

En listant les migrations du module Dictionary par date
(`ls Modules/Dictionary/database/migrations | sort`), la dernière migration
qui ajoute des termes est datée du 2026-09-01 (`..._233000_add_infostealers_terms.php`) ;
aucune migration Dictionary postérieure à cette date n'existe dans le dépôt au
moment de la lecture (2026-09-11). Le compte exact au jour de la lecture n'a
donc pas pu être établi (voir section finale).

## B. Le mécanisme des tutoriels de l'annuaire

### B.1 Table et modèle

Table `directory_resources`, créée par
`Modules/Directory/database/migrations/2026_03_22_000002_enrich_directory_module.php:51-61`
(url, title, type, language, thumbnail, is_approved), enrichie ensuite de
colonnes vidéo (`video_id`, `duration_seconds`, `channel_name`, `channel_url`,
`level`, `video_summary` - migrations `2026_03_26_400000`,
`2026_03_27_500000`, `2026_04_15_230000`). Modèle `Eloquent` :
`Modules/Directory/app/Models/ToolResource.php` (relation `tool()` ligne 51-54,
`scopeApproved()` ligne 61-64, détection de niveau par mots-clés dans le titre
lignes 26-45).

### B.2 Découverte (comment les tutoriels sont trouvés)

`Modules/Directory/app/Services/YouTubeService.php` interroge l'API YouTube
Data v3 (`searchTutorials()`, lignes 12-71) avec la requête `"{nom outil}
tutoriel"` (ou `tutorial` en anglais), triée par `viewCount`, fenêtre de 36
mois, `regionCode='CA'` en français. `findTutorials()` (lignes 332-371) fait une
passe FR (seuil 1000 vues) puis complète en EN (seuil 5000 vues) si besoin.

### B.3 Filtrage

`scoreAndFilter()` (lignes 187-262) rejette : vues insuffisantes, durée hors
bornes (180-7200s), langue non FR/EN (double vérification : signal API
`defaultAudioLanguage` ET heuristique sur le titre, les deux devant
concorder - ligne 199-205), contenu non-tutoriel (jeu/film/musique, lignes
207-212), et absence du nom de l'outil dans le titre (ligne 216). Score final
= 40 % vues normalisées + 30 % ratio de mentions « j'aime » + 30 % fraîcheur
(lignes 243-256).

**Garde de pertinence pour les noms d'outils communs**
(`GENERIC_NAME_DOMAIN_KEYWORDS`, lignes 180-185) : liste CURÉE de 4 entrées
seulement (monologue, motion, make, handy) - pour ces noms, le titre doit
AUSSI contenir un mot du domaine de l'outil, sinon rejeté. Doctrine écrite au
docblock (lignes 168-179) : « liste curée par cas PROUVÉ en production,
jamais une garde générale ».

### B.4 Trois canaux d'ajout, tous auto-approuvés à la création

1. **Cron automatique** : `tools:enrich-tutorials --batch=5`, planifié
   `dailyAt('05:00')` (`Modules/Directory/app/Providers/DirectoryServiceProvider.php:107`),
   protégé par le kill switch `cron.directory-tutorials` (actif,
   `app/Providers/AppServiceProvider.php:121`). Crée les ressources avec
   `'is_approved' => true` directement
   (`Modules/Directory/app/Console/EnrichTutorialsCommand.php:131`).
2. **Import JSON manuel** : `tools:import-youtube-resources`
   (`Modules/Directory/app/Console/ImportYoutubeResourcesCommand.php`), flag
   `cron.import-youtube` défini `true` mais annoté « manuelle, idempotente »
   (`app/Providers/AppServiceProvider.php:145`) - pas de `schedule()` trouvé
   pour cette commande. `is_approved` par défaut `true` (ligne 49, configurable
   par le fichier JSON).
3. **Soumission communautaire** : `CommunityController::storeResource()`
   (`Modules/Directory/app/Http/Controllers/CommunityController.php:125-202`).
   Tout utilisateur connecté voit sa soumission auto-approuvée
   (`$autoApprove = true`, ligne 151, commentaire ligne 152 : « la communauté
   modère via votes et signalements »).

Une variante à base de `sonar-pro` (attribution outil/tutoriel par IA,
`tools:enrich-tutorials-sonar`) existe mais son flag
`cron.directory-tutorials-sonar` est défini `false`
(`app/Providers/AppServiceProvider.php:152`) - désactivée explicitement, la
justification écrite (`CHANGELOG.md:2665`) étant le risque d'attribution à un
homonyme.

### B.5 Modération - après coup, pas avant publication

`tools:audit-tutorials --fix --email=stephane@memora.ca`, planifié
`dailyAt('06:00')` (`DirectoryServiceProvider.php:119`). Le code
(`Modules/Directory/app/Console/AuditTutorialsCommand.php:19-44`) relit tous
les tutoriels YouTube approuvés, applique `YouTubeService::titleIsAcceptable()`
(langue + mots-clés de contenu non pertinent, PAS une vérification sémantique
du rapport au bon outil), désapprouve automatiquement si `--fix` et si le
nombre de cas ne dépasse pas 200 (ligne 41), et envoie un courriel récapitulatif
(lignes 46-58).

### B.6 Taux de faux mesuré au premier passage réel, et le garde-fou qui a corrigé

Mesure citée dans `CHANGELOG.md:925` (entrée `[1.248.0]`, 2026-09-03) :
**« 20 tutoriels affichés en production n'avaient aucun lien avec leur outil »**
- même mécanisme dans tous les cas : un nom d'outil qui est un mot commun. Cas
nommés (lignes 927-930) : « Monologue » (dictée vocale) récoltait du théâtre
et le synthétiseur Korg Monologue ; « Motion » (gestion de tâches) du motion
design `Premiere`/`CapCut` ; « Make » (automatisation) un dessin animé Cartoon
Network ; « Handy » (dictée) du bricolage.

Le test qui verrouille la mesure
(`Modules/Directory/tests/Feature/TutorialRelevanceGuardTest.php:49-55`) cite
le cas réel « Monologue » : **8 faux tutoriels affichés sur 8** avant
correctif.

Deux correctifs distincts ont suivi la mesure :
1. **`GENERIC_NAME_DOMAIN_KEYWORDS`** (`YouTubeService.php:180-185`), qui
   filtre à la découverte pour les 4 noms prouvés à risque.
2. **`tools:moderate-tutorials`**
   (`Modules/Directory/app/Console/ModerateTutorialsCommand.php`), commande
   créée « après mesure » (docblock lignes 11-21) pour retirer du public les
   tutoriels déjà en ligne au moment de la mesure.

### B.7 Pourquoi « désapprouver » et jamais « supprimer »

Doctrine écrite explicitement au docblock de `ModerateTutorialsCommand.php:17-21` :
désapprouver (`is_approved = false`) retire la ressource de la vue publique
(`scope approved()`) tout en la laissant en base, ce qui la « **vaccine** »
contre le re-scan : `EnrichTutorialsCommand` détecte les doublons uniquement
par `video_id` (`EnrichTutorialsCommand.php:110`), sans jamais regarder
`is_approved` - une ressource désapprouvée ne peut donc pas être recréée au
prochain passage. Une suppression ferait l'inverse : le prochain scan la
réajouterait, puisque le doublon ne serait plus détecté. `--restore` permet
l'annulation complète d'une désapprobation erronée (test
`TutorialRelevanceGuardTest.php:91-130`, qui vérifie aussi que `--dry-run`
n'écrit rien).

## C. La différence qui décide de tout : ambiguïté mesurée des termes de glossaire

Le même moteur, `Modules/Core/app/Services/GlossaryLinkifier.php`, alimente
les auto-liens du glossaire (Dictionary), des acronymes (Acronyms) et de
l'annuaire (Directory - noms d'outils). Trois constantes portent la liste des
cas prouvés à risque, une par catégorie de nom.

### C.1 `ALIAS_NEVER_AUTO` (glossaire) - ligne 1038

```
['cnn', 'dos', 'requête', 'requêtes', 'témoin', 'mistral', 'ia', 'ai',
 'pathway', 'pathways', 'autonomie', 'autonomies', 'haïku']
```
13 entrées curées, soit **10 mots de base distincts** (les autres sont des
formes plurielles). Chaque entrée porte sa propre preuve mesurée, jamais une
généralisation :

| Mot | Mesure | Ancrage |
|---|---|---|
| CNN | 4 faux liens vers `/glossaire/reseau-convolutif` (actualité de journalisme) | `GlossaryLinkifier.php:917-923` |
| requête | 1 faux lien vers `/glossaire/prompt` (« une requête en rejet », actualité de droit) | `GlossaryLinkifier.php:927-929` |
| témoin | repéré par audit **avant** incident - pas encore mesuré en production au moment du correctif | `GlossaryLinkifier.php:930-933` |
| dos | 3 liens sur 900 pages de production, **3/3 faux (100 %)** | `GlossaryLinkifier.php:945-952` |
| mistral | 13 pages sur 24 mesurées, 53 liens « Mistral », quasi tous au mauvais sens (éditeur confondu avec le produit Le Chat) | `GlossaryLinkifier.php:955-968` |
| ia | sur 4627 fiches actualités publiées, 105 fiches liées vers `/glossaire/autonomie-ia`, dont **34 faux** via la seule ancre « IA » | `GlossaryLinkifier.php:970-980` |
| autonomie/autonomies | sur 137 pages liées vers `/glossaire/autonomie-ia` : 60 sens batterie/véhicule + 21 sens humain/géopolitique = **81 faux sur 137 (59 %)**, 49 corrects, 7 ambigus | `GlossaryLinkifier.php:1009-1020` (détail synthétisé aussi ligne 29) |
| pathway | au moins 3 collisions réelles trouvées (une fonctionnalité interne « Debbie Rewards », l'infrastructure Google Pathways, la société `Pathway Medical Inc.`) | `GlossaryLinkifier.php:993-1000` |
| haïku | 3 liens légitimes contre 1 faux - seul l'alias accentué retiré, le nom principal reste auto-lié | `GlossaryLinkifier.php:29` (historique bump v25, ticket #2241) |

### C.2 `ACRONYM_NEVER_AUTO` (module Acronymes, catalogue séparé) - ligne 1075

Une seule entrée : `'ai'`. Mesure (`GlossaryLinkifier.php:1056-1061`, ticket
#2238) : sur un échantillon de 40 fiches d'actualité tirées au hasard (parmi
1198), les 40 lues intégralement, **8 occurrences de l'alias « AI »
linkifiées, 8 sur 8 fausses (100 %)** - 5 dans une ligne de métadonnées
(nom de média), 1 dans un titre, 1 sur « AI Act », 1 sur « Google AI Ultra ».

### C.3 Pourquoi une liste curée et jamais une règle générale

Contre-exemple mesuré et cité comme justification explicite dans le code
(`GlossaryLinkifier.php:377-385`) et dans `CHANGELOG.md:940` : une garde
générale sur les composés minuscules (ticket #2128, catalogue outils) a été
mesurée à **0 % de précision - 46 liens parfaitement légitimes perdus pour 0
vrai blocage** (détail chiffré : `CHANGELOG.md:1149-1156`, « 746 blocs de
texte publiés », « 33 blocs, 24 outils »). C'est la justification écrite,
répétée à plusieurs endroits du fichier, du choix de ne curer QUE les cas
prouvés, un par un, plutôt que de généraliser.

### C.4 Comparaison avec les noms d'outils de l'annuaire

Même dans le catalogue où les noms sont réputés « presque uniques »,
`TOOL_NEVER_AUTO` (`GlossaryLinkifier.php:94`) liste déjà 24 noms explicites
(claude, avec, tome, caribou, make, motion, gamma, gemini, mistral,
consensus, intent, dust, soar, remind, spinach, grok, aqua, handy, lounge,
willow, poe, pika, noa, deduce) auxquels s'ajoute la liste plus longue
`TOOL_NEVER_RECAPTURE` (ligne 125, mots français/anglais courants comme
« local », « montage », « pulse », « logic »). La différence avec le
glossaire n'est donc pas « zéro ambiguïté côté outils » mais un ordre de
grandeur : la garde de pertinence sémantique des tutoriels de l'annuaire
(section B.3) ne couvre que **4** noms d'outils identifiés à risque
(`GENERIC_NAME_DOMAIN_KEYWORDS`), contre 10 mots de base déjà neutralisés
côté glossaire pour l'auto-lien seul - sans qu'aucun mécanisme équivalent de
filtrage de VIDÉOS n'existe pour ces mots de glossaire, puisque le glossaire
n'a aujourd'hui aucun tutoriel.

### C.5 Proportion réelle de termes à risque - ce qui est mesuré, et ce qui ne l'est pas

Sur un corpus de 523 à 534 termes de glossaire (section A.5), **10 mots de
base sont PROUVÉS ambigus/à risque** à ce jour (ALIAS_NEVER_AUTO), soit
environ **1,9 % du corpus mesuré comme problématique par incident**. C'est un
PLANCHER, pas une proportion réelle : le mécanisme est explicitement curatif
(le docblock de `ALIAS_NEVER_AUTO`, lignes 911-937, dit « liste CURÉE et
étroite des noms PROUVÉS à risque, jamais une garde par défaut sur tout le
catalogue » ; celui de `ACRONYM_NEVER_AUTO`, lignes 1073-1075, dit « un futur
cas s'ajoute ici avec SA PROPRE mesure, jamais par ressemblance »). Aucun
audit proactif et exhaustif de l'ambiguïté linguistique des ~530 termes
n'existe dans le code (la seule commande d'audit trouvée,
`Modules/Core/app/Console/GlossaryAuditCollisionsCommand.php`, mesure des
COLLISIONS de titre entre deux fiches du site, pas l'ambiguïté d'un terme
avec un sens dominant hors du domaine technique). La proportion RÉELLE reste
donc, par construction du mécanisme existant, non mesurée - voir dernière
section.

## D. Ce qui occupe déjà la place sur la fiche de glossaire

- **Bande dessinée pédagogique** (`ComicLibrary::forSlug()`,
  `Modules/Dictionary/app/Support/ComicLibrary.php:45-98`, convention
  `public/bd/{slug}/manifest.json`). 9 dossiers de planches existent et sont
  versionnés dans le dépôt `git` au moment de la lecture (commande
  `git ls-files public/bd`, filtrée sur `manifest.json`) : `anonymisation`,
  `biais-algorithmique`, `cheval-de-troie`, `cybersecurite`, `deepfake`,
  `enchainement-de-requetes`, `iteration`, `rancongiciel`,
  `read-this-before-you-vibe-code-another-app` - donc déployés
  en production par le même mécanisme que le code. La grille du glossaire
  affiche un indicateur et un filtre dédiés : `$bdCount`
  (`Modules/Dictionary/resources/views/public/index.blade.php:50-51`) et le
  bouton « Avec BD » (lignes 622-629).
- **Image de couverture** (`hero_image`) - rendue en tête de fiche
  (`show.blade.php:230-244`), avec repli sur une icône emoji si absente.
- **Relations structurées entre termes** (`broader_slugs`/`narrower_slugs`,
  bloc « Termes liés », `show.blade.php:447-509`) et **termes associés** par
  catégorie (`show.blade.php:517-545`, requête
  `PublicDictionaryController.php:74-78`).
- **Auto-liens internes** : le corps même de la fiche (définition, analogie,
  exemple, le saviez-vous, FAQ) est passé dans `GlossaryLinkifier::linkify()`
  au rendu, plafonné à 1 occurrence par terme cible et 25 liens par page
  (`show.blade.php:349-352`, commentaire ticket #300).
- **Partage social enrichi** : texte de partage construit dynamiquement par
  réseau, avec analogie et anecdote incluses (`show.blade.php:20-41`).
- **FAQ structurée** (Schema.org `FAQPage`) et **sources externes
  vérifiables** (`show.blade.php:407-445`).
- **JSON-LD complet** (`DefinedTerm` + `Person` + `Article` +
  `BreadcrumbList` + `FAQPage`) via `TermSchemaService::buildGraph()`
  (`show.blade.php:555-556`).
- **Compteur de vues** : existe en base et s'incrémente réellement
  (`views_count`), mais n'est affiché nulle part (voir A.3) - donc une
  fonctionnalité déjà « en place » sous le capot, sans occuper d'espace
  visuel.
- **Suggestion d'édition** (`fronttheme::partials.suggest-edit`,
  `show.blade.php:220-223`), limitée aux champs textuels de la fiche
  elle-même (`definition`, `analogy`, `example`, `did_you_know`, `other` -
  `Term.php:35-41`) : pas de mécanisme de soumission de ressource EXTERNE
  (URL/vidéo) comme celui de l'annuaire (section B.4.3).
- **Ce qui N'existe PAS sur la fiche de glossaire**, par contraste avec la
  fiche de l'annuaire : aucune section ou onglet « actualités liées »
  (l'annuaire a `$toolNewsArticles`,
  `Modules/Directory/resources/views/public/show.blade.php:577-578` ;
  recherche exhaustive sur `Modules/Dictionary/resources/views/public/show.blade.php`
  et sur `PublicDictionaryController.php` : aucune occurrence de `News` ou
  `Article` liée au terme lui-même, seulement une mention DRY en commentaire
  ligne 9 et le mot « Article » dans le JSON-LD ligne 555) ; aucun système
  d'onglets (A.2/A.4) ; aucun mécanisme de ressource externe communautaire.

## Ce que je n'ai pas pu établir

1. **Le nombre exact de fiches de glossaire publiées au 2026-09-11.** La
   seule mesure directe de production disponible est datée du 2026-09-01
   (523 fiches via `curl` sur le sitemap réel, section A.5), et aucune
   migration Dictionary postérieure à cette date n'existe dans le dépôt.
   Cela ne prouve pas l'absence de changement : une fiche peut être créée ou
   dépubliée directement via l'écran admin sans migration, un scénario que
   la seule lecture du code ne peut pas exclure. Je n'ai pas de moyen
   autorisé de vérifier ce chiffre en direct : pas d'accès SQL à la
   production (terminal cPanel absent, confirmé), et récupérer le sitemap.xml
   en direct sort du périmètre des outils disponibles pour cette mesure
   (`pp_search` est un outil de recherche web, pas de lecture de notre propre
   site ; les outils HTTP génériques ne sont pas autorisés par les règles du
   projet). La base de données LOCALE contient seulement 161-162 termes
   (`php artisan tinker --execute="echo \Modules\Dictionary\Models\Term::count();"`)
   - manifestement désynchronisée de la production - et n'a donc pas été
   utilisée pour ne pas produire un chiffre trompeur.
2. **La proportion RÉELLE (non seulement PROUVÉE) de termes ambigus du
   glossaire.** Voir C.5 : le mécanisme existant ne détecte que ce qui a
   déjà produit un incident mesuré en production. Établir la proportion
   réelle demanderait un audit proactif terme par terme (par exemple :
   vérifier chaque nom de terme et chaque alias contre un dictionnaire de
   sens courants), qui n'existe pas dans le code au moment de la lecture, et
   que je n'ai pas exécuté moi-même puisque le mandat est de lecture du code
   existant, pas de production d'une nouvelle mesure.
3. **Le nombre exact de tutoriels actuellement approuvés/désapprouvés dans
   `directory_resources` en production**, pour la même raison d'absence
   d'accès SQL/terminal.
4. **La méthodologie exacte derrière le chiffre « 534 termes »** du
   `CHANGELOG.md:1261` (compte total en base vs compte publié) n'a pas pu
   être confirmée au-delà du texte du changelog lui-même - le code source de
   ce balayage n'est pas un fichier versionné mais une opération exécutée en
   session.

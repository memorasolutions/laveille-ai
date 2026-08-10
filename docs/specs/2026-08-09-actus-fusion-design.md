# Actus 2.0 : fusion multi-sources des actualités - document de conception

- Auteur : MEMORA solutions (info@memora.ca, https://memora.solutions)
- Module : `Modules/News`
- Statut : conception, aucune implémentation livrée par ce document
- Date : 2026-08-09

Ce document ne contient aucun code de production. Toute référence à un fichier existant a été
vérifiée directement dans le dépôt (chemins et numéros de ligne exacts). Toute proposition de
fichier ou de colonne à créer est explicitement marquée « à créer » et n'existe pas encore.

## 1. Contexte et problème

Le 2026-08-09, une demande d'adhésion AdSense a été refusée pour « contenu à faible valeur ».
L'audit associé (voir mémoire projet `adsense-refus-low-value-elagage-2026-08-09.md`) a mesuré
qu'environ 3497 fiches actualités sur 5588 (environ 80 %) sont minces : un article RSS, un seul
appel IA, souvent peu de trafic après deux mois. Un premier correctif réversible est déjà en
production : l'élagage SEO (`Modules/News/config/seo_prune.php`, commande
`Modules/News/app/Console/PruneSeoCommand.php`, planifiée quotidiennement à 02h10 heure Québec
dans `Modules/News/app/Providers/NewsServiceProvider.php:85-88`) qui passe en `noindex,follow` les
actualités de plus de 2 mois avec moins de 300 vues, avec auto-guérison si le trafic revient.

L'élagage traite le symptôme (retirer de l'index ce qui est mince) mais ne change rien à la
production : le pipeline continue de publier une fiche par article RSS, un sujet unique couvert
par 3 sources produit 3 fiches quasi identiques plutôt qu'une fiche plus riche. Actus 2.0 vise la
cause : quand plusieurs sources couvrent le même sujet le même jour, produire UNE fiche
comparative substantielle plutôt que plusieurs fiches minces, sans changer le comportement du
pipeline pour les sujets couverts par une seule source (qui restent majoritaires certains jours).

## 2. Objectifs et non-objectifs

### Objectifs

1. Regrouper déterministiquement, avant l'appel IA, les articles candidats du jour qui couvrent le
   même sujet (fenêtre 24-48 h configurable), sans API d'embeddings ni appel réseau.
2. Produire une fiche comparative par groupe de 2 articles ou plus, en UN seul appel IA pour tout
   le groupe (au lieu d'un appel par article), avec citation systématique de chaque source et de
   son auteur.
3. Injecter dans ce même appel un contexte d'archives internes (fiches passées pertinentes) pour
   que la fiche indique, si pertinent, ce qui a changé.
4. Plafonner à N fiches indexées par jour (quota fixe, pas un score de qualité comme filtre),
   au-delà desquelles la fiche est publiée mais `noindex` dès la création.
5. Tout geste réversible et gouverné par un drapeau maître désactivé par défaut ; drapeau OFF =
   pipeline strictement identique à aujourd'hui.

### Non-objectifs

- Pas de registre de revendications / fact-checking croisé automatisé entre sources.
- Pas de mini-guides ni de contenu evergreen généré à partir des actualités.
- Pas de curation humaine (aucune file de modération manuelle des groupes).
- Pas d'embeddings, pas de base vectorielle, pas d'appel réseau pour le clustering.
- Pas de changement du format des fiches à source unique (singleton) : elles restent inchangées.
- Pas de score de qualité IA utilisé comme filtre d'indexation (le quota est fixe, jamais un seuil
  qui dérive selon la sortie du modèle).

## 3. Architecture et composants

### 3.1 État vérifié du pipeline actuel

- `Modules/News/app/Console/FetchNewsCommand.php` : boucle sur `NewsSource::active()`, appelle
  `RssFetcherService::fetchSource()` par source (ligne 62), puis pour chaque article non traité
  (`structured_summary` null, `is_published` false, lignes 68-71) : pré-filtre mots-clés
  (`AiSummaryService::isRelevant()`, ligne 75), vérification de quota par jour (`news.min_relevance_score`,
  `news.max_ia_articles_per_day` défaut 10, `news.max_tech_articles_per_day` défaut 5, lignes
  42-44, lues via `Modules\Settings\Facades\Settings::get()`), puis DEDUP-SKIP optionnel (lignes
  97-126, utilise `DedupService::isLikelyDuplicate()`), puis **un appel IA par article**
  (`AiSummaryService::scoreAndSummarize()`, ligne 129).
- `Modules/News/app/Services/AiSummaryService.php` : un seul appel OpenRouter par article
  (`scoreAndSummarize()`, lignes 45-146), texte source tronqué à 4000 caractères (ligne 54, pas
  5000 - écart avec l'énoncé de la demande, vérifié dans le fichier réel), cascade de modèles
  (`deepseek/deepseek-chat`, `openai/gpt-4o-mini`, `google/gemma-3-27b-it:free`, lignes 13-17),
  retourne un JSON structuré (score, hook, key_points, why_important, tldr, quote, key_stat, faq...).
- `Modules/News/app/Services/RssFetcherService.php` : scrape et stocke le texte complet dans
  `description` (tronqué à 5000 caractères via `Str::limit()`, ligne 130 - c'est ici que la limite
  de 5000 caractères de l'énoncé s'applique réellement, pas dans le prompt IA).
- **`Modules/News/app/Services/DedupService.php` existe déjà** et fournit exactement les primitives
  déterministes demandées pour le clustering : `jaccardKeywords()` (Jaccard sur tokens normalisés,
  liste de mots vides FR/EN, lignes 62-77), `extractKeyEntities()` (règles de capitalisation +
  liste d'acronymes connus, lignes 79-112), `titleSimilarity()` (`similar_text` normalisé, lignes
  53-60), et `isLikelyDuplicate()` qui combine ces signaux avec un système de score (lignes
  121-194). Ce service est actuellement appelé à deux endroits : `RssFetcherService.php:82-112`
  (DEDUP-OBSERVE, journalisation seule, aucune écriture DB) et `FetchNewsCommand.php:98-126`
  (DEDUP-SKIP, évite un résumé IA sur un doublon cross-source détecté, mais ne fusionne rien).
- **Le modèle de données porte déjà un mécanisme de regroupement inutilisé** :
  `NewsArticle::$fillable` inclut `is_potential_duplicate_of`, `dedup_score`, `dedup_reason`
  (`NewsArticle.php:40`), `is_potential_duplicate_of` est une clé étrangère auto-référentielle
  (migration `2026_04_28_000000_add_dedup_columns_news_articles.php:17-21`), et les relations
  `originalArticle(): BelongsTo` / `duplicates(): HasMany` existent déjà (`NewsArticle.php:108-116`).
  Une table d'audit `news_dedup_log` existe aussi (même migration, lignes 30-45, modèle
  `Modules\News\Models\NewsDedupLog`). **Vérifié par grep exhaustif : aucun code de production
  n'écrit dans `is_potential_duplicate_of` ni dans `news_dedup_log`** - seuls les tests et la
  définition du modèle y font référence. Vestige d'un chantier antérieur (« Step 3b OBSERVATION
  ONLY : DedupService cascade S70 », `RssFetcherService.php:81`) : colonne et table existent en
  base mais ne servent à rien en production aujourd'hui.
- `Modules/News/app/Console/PruneSeoCommand.php` : élagage réversible via colonne `seo_status`
  (`index|noindex|gone`), déjà présente sur `news_articles`
  (`Modules/News/database/migrations/2026_06_07_050000_add_seo_status_to_news_articles.php`).
  Auto-guérison : `reindexBase()` (lignes 57-59) repasse en `index` tout ce qui est `noindex` et a
  dépassé `max_views`.
- `Modules/News/app/Http/Controllers/NewsSitemapController.php` : filtre déjà
  `where('seo_status', 'index')` (ligne 36) - aucune modification requise pour exclure les
  membres de groupe une fois `noindex`.
- Config du module : `Modules/News/config/config.php` s'enregistre sous la clé `news` (cas spécial
  dans `NewsServiceProvider::registerConfig()`, ligne 132), tandis que `seo_prune.php` s'enregistre
  sous `news.seo_prune` (même méthode, lignes 111-139, vérifiée en lisant l'algorithme de
  composition de clé). Un futur fichier `Modules/News/config/fusion.php` s'enregistrerait donc
  automatiquement sous `news.fusion.*` - **exactement le chemin demandé par le mandat**, confirmé
  par la mécanique réelle du provider, pas supposé.
- `Modules\Settings\Facades\Settings` expose déjà `get()/set()/has()/forget()` sur une table
  `settings` avec cache (`SettingsService.php:19-27`) - le pattern « setting runtime » du point 5
  du mandat existe déjà et sert déjà à `news.min_relevance_score`. **Hypothèse non confirmée** :
  aucun écran d'administration dédié à l'édition des réglages `news.*` n'a été localisé (seuls des
  usages ponctuels de `Settings::get()` existent, ex. `AdminNewsController.php:27`,
  `edit.blade.php:88`) - exposer `news.fusion.enabled` en admin est hors périmètre de ce document.

### 3.2 Composants à créer ou modifier (aucun n'existe encore, sauf mention contraire)

| Fichier | Statut | Rôle |
|---|---|---|
| `Modules/News/config/fusion.php` | à créer | `enabled` (bool, défaut false), `window_hours` (défaut 36), `min_group_size` (défaut 2), `max_indexed_digests_per_day` (défaut 5), `archive_lookback_months` (défaut 6), `archive_max_results` (défaut 5), `jaccard_threshold` (défaut 0.30), `min_entity_overlap` (défaut 2) |
| `Modules/News/app/Services/DedupService.php` | à modifier (ajout de méthode) | ajouter `isSameStoryCluster(array $a, array $b, array &$signals = []): array` - **réutilise** `jaccardKeywords()`, `extractKeyEntities()`, `keyEntitiesIntersectionCount()` déjà présentes ; ne duplique aucune logique de tokenisation |
| `Modules/News/app/Services/ArticleClusteringService.php` | à créer | orchestration : reçoit une collection d'articles candidats du jour, appelle `DedupService::isSameStoryCluster()` par paire, construit les composantes connexes (union-find), retourne des groupes ; singleton si aucun jumeau ou score sous le seuil |
| `Modules/News/app/Services/ArchiveContextService.php` | à créer | requête interne `LIKE`/entités sur `news_articles` (fenêtre `archive_lookback_months`, limite `archive_max_results`), retourne titres + dates + URLs internes des fiches archivées pertinentes ; aucune dépendance externe |
| `Modules/News/app/Services/AiSummaryService.php` | à modifier | extraire la boucle d'appel HTTP + cascade de modèles + nettoyage JSON (lignes 106-146 actuelles) dans une méthode privée partagée, puis ajouter `scoreAndSummarizeGroup(array $articles, array $archiveContext, string $language = 'fr'): ?array` qui réutilise cette méthode privée avec un nouveau prompt (DRY : la logique de cascade/retry ne doit pas être dupliquée entre le chemin singleton et le chemin groupe) |
| `Modules/News/app/Console/FetchNewsCommand.php` | à modifier | après la boucle de récupération RSS existante, si `config('news.fusion.enabled')` : appel à `ArticleClusteringService` sur les candidats du jour AVANT la boucle actuelle de résumé par article ; les singletons retournés poursuivent EXACTEMENT le chemin actuel (lignes 73-161 inchangées) ; les groupes de 2+ suivent le nouveau chemin décrit en 3.3 |
| `Modules/News/app/Console/PruneSeoCommand.php` | à modifier (garde-fou obligatoire, pas optionnel) | `reindexBase()` (lignes 57-59) doit exclure les membres de groupe pour ne jamais les faire ré-indexer par auto-guérison - voir section 9, risque R3 |
| `Modules/News/database/migrations/2026_08_10_000000_add_fusion_marker_to_news_articles.php` | à créer | additive uniquement, voir section 4 |
| `Modules/News/app/Models/NewsArticle.php` | à modifier (ajout, aucune suppression) | nouveau champ fillable/cast + deux méthodes d'accès nommées explicitement (voir 4.3) ; **aucune modification des relations existantes** `originalArticle()`/`duplicates()` |
| `Modules/News/resources/views/public/show.blade.php` | à modifier (ajout de blocs conditionnels uniquement) | voir section 6 |

## 4. Modèle de données (additif uniquement)

### 4.1 Nouvelle colonne sur `news_articles`

Une seule colonne nouvelle est nécessaire. Tout le reste réutilise des colonnes déjà existantes et
déjà vérifiées en base (section 3.1) :

```
Schema::table('news_articles', function (Blueprint $table) {
    if (! Schema::hasColumn('news_articles', 'is_comparative_digest')) {
        $table->boolean('is_comparative_digest')->default(false)->after('feed_type');
        $table->index('is_comparative_digest');
    }
});
```

`down()` symétrique (`dropColumn`), suivant exactement le patron des migrations existantes du
module (garde `hasColumn`/`hasColumn` avant chaque ajout/retrait, vu dans
`2026_06_07_050000_add_seo_status_to_news_articles.php` et
`2026_04_28_000000_add_dedup_columns_news_articles.php`).

### 4.2 Réutilisation des colonnes existantes (aucune migration requise)

| Colonne existante | Nouveau rôle sous Actus 2.0 |
|---|---|
| `is_potential_duplicate_of` (FK auto-référentielle, existe depuis 2026-04-28, jamais écrite en prod) | pour un article membre d'un groupe : pointe vers l'`id` de la fiche comparative |
| `dedup_score` (decimal 4,3, existe, jamais écrite en prod) | score de similarité ayant justifié le regroupement (0-1) |
| `dedup_reason` (string 64, existe, jamais écrite en prod) | raison courte (`jaccard_high`, `key_entities_match`, `multi_core` - mêmes libellés que `DedupService::isLikelyDuplicate()` pour rester cohérent) |
| `seo_status` (existe, `index|noindex|gone`) | les membres de groupe passent à `noindex` **dès la création** (jamais `index` pour un membre) ; la fiche comparative suit la règle de quota (section 5) |
| `news_dedup_log` (table, existe, jamais écrite en prod) | journal d'audit d'un regroupement : `new_article_id` = fiche comparative, `matched_article_id` = chaque membre, `score`, `reason`, `signals` (JSON), `action` = `'fusion_grouped'` |

### 4.3 Modèle `NewsArticle`

Ajouter `is_comparative_digest` à `$fillable` et `$casts` (`'boolean'`). Ajouter deux méthodes
d'accès nommées explicitement plutôt que de réinterpréter silencieusement `originalArticle()` /
`duplicates()` (voir note de nommage, section 9) :

- `fusionDigest(): ?self` - retourne `$this->originalArticle` uniquement si la cible a
  `is_comparative_digest = true` (sinon `null`, pour ne pas confondre un ancien enregistrement
  DEDUP-SKIP hypothétique avec un vrai groupe Actus 2.0).
- `fusionMembers(): Collection` - retourne `$this->duplicates` filtré sur les articles dont
  `is_comparative_digest` du parent est vrai (même garde de cohérence).

## 5. Quota d'indexation fixe

Nouveau réglage `news.fusion.max_indexed_digests_per_day` (config, défaut 5, lu via
`Settings::get('news.fusion.max_indexed_digests_per_day', config('news.fusion.max_indexed_digests_per_day'))`
pour suivre le même patron config-avec-override-runtime que `news.min_relevance_score`). Un
compteur quotidien `$todayIndexedDigests` (même patron que `$todayIa`/`$todayTech`,
`FetchNewsCommand.php:51-58`) est initialisé au début de `handle()`. Chaque fiche comparative créée
au-delà du quota reçoit `seo_status = 'noindex'` **dès la création** (pas un passage ultérieur par
`PruneSeoCommand`), en réutilisant tel quel le champ et la sémantique de l'élagage existant :
l'auto-guérison de `PruneSeoCommand` peut la ré-indexer plus tard si elle devient populaire, sans
code supplémentaire côté fusion. Ce quota est indépendant des quotas existants
`max_ia_articles_per_day`/`max_tech_articles_per_day`, qui continuent de gouverner `is_published`
(publication) et non `seo_status` (indexation) - deux axes déjà distincts dans le code actuel.

Pas de score de qualité IA comme filtre : le classement des N premières fiches indexées suit
l'ordre de traitement des groupes (le plus gros groupe d'abord, à égalité le plus ancien
`pub_date`), jamais une note retournée par le modèle - décision gelée du mandat.

## 6. Contrat du JSON IA étendu (rétrocompatible)

Le contrat actuel de `AiSummaryService::scoreAndSummarize()` (prompt lignes 70-88 du fichier réel)
reste identique pour le chemin singleton. Un nouveau contrat, utilisé uniquement par
`scoreAndSummarizeGroup()`, ajoute des champs **tous nullables**, en conservant tous les champs
existants (`score`, `hook`, `key_points`, `tldr`, `quote`, `key_stat`, `expert_name`,
`expert_role`, `why_important`, `audience`, `seo_title`, `meta_description`, `faq_question`,
`faq_answer`) :

```
{
  ...champs existants (sens inchangé, portent sur la synthèse du groupe)...
  "sources": [
    {"source_name": "...", "author": "...ou null...", "url": "...", "angle": "...ou null, 10-15 mots..."}
  ],
  "divergences": ["...ou tableau vide si les sources concordent..."],
  "archive_context": {
    "summary": "...1-2 phrases, ou null si aucune archive pertinente...",
    "related": [{"title": "...", "url": "...(interne, route news.show)...", "date": "..."}]
  },
  "angle_qc_ca": "...ou null - JAMAIS forcé, seulement si une donnée QC/CA vérifiable a été fournie en entrée..."
}
```

`sources[]` est obligatoire et non vide (un élément par membre, exigence légale art. 29.1/29.2 :
mention de la source et de l'auteur pour chaque source citée). `divergences`, `archive_context` et
`angle_qc_ca` peuvent être vides/null - un consommateur qui ignore ces clés (ex. code existant lisant
`structured_summary` pour un singleton) continue de fonctionner, puisqu'elles n'apparaissent
simplement pas dans le JSON singleton. Vérifié : `NewsArticle::flattenStructuredSummary()`
(lignes 199-211) et `adminShareContents()` (lignes 218-298) ne lisent que des clés du contrat
actuel - aucune des deux ne casse en présence de clés supplémentaires inconnues.

Le prompt du groupe reçoit : titre + URL + auteur + texte tronqué (même troncature 4000 caractères,
ligne 54 actuelle) de CHAQUE membre, le contexte d'archives (titres/dates/URLs internes) produit par
`ArchiveContextService`, et si disponibles les outils annuaire déjà liés à un membre (relation
`tools()`, lignes 98-106). Consigne explicite : ne mentionner le Québec/Canada que si une donnée
canadienne vérifiable apparaît dans le texte fourni - même règle de fidélité que le prompt actuel
(ligne 66), étendue à `angle_qc_ca`.

## 7. Parcours de rendu (`show.blade.php`)

Fichier réel : `Modules/News/resources/views/public/show.blade.php` (467 lignes vérifiées). Blocs
ajoutés, tous conditionnels sur `$article->is_comparative_digest` (variable `$isDigest` calculée en
tête de fichier à côté de `$ss` existant, ligne 3) :

- **Après le bloc « Citation verbatim » (après ligne 309)** : si `$isDigest` et
  `!empty($ss['sources'])`, une liste « Sources » (nom, auteur si connu, lien externe
  `rel="noopener"`) - remplace le bloc à source unique (ligne 377) pour les fiches comparatives
  seulement (`@unless($isDigest)` ajouté autour du bloc existant lignes 368-379, aucune suppression).
- **Après « Pourquoi cette nouvelle compte-t-elle ? » (après ligne 330)** : si
  `!empty($ss['divergences'])`, bloc « Ce que disent les sources différemment » (liste à puces).
- Si `!empty($ss['archive_context']['summary'])`, bloc « Ce qui a changé » avec le résumé et, pour
  chaque entrée de `archive_context.related`, un lien interne (`route()` résolue côté service).
- Si `!empty($ss['angle_qc_ca'])`, bloc court et discret (même famille que `nw-expert`, ligne 201),
  jamais affiché si la clé est absente ou nulle.
- **Page d'un article MEMBRE** (`fusionDigest()` non nul) : le bloc `noindex` existant (lignes 6-8,
  déjà conditionné sur `seo_status`) s'applique sans modification. Ajouter un bandeau visible (pas
  seulement une meta invisible) : « Cette actualité fait partie d'une fiche comparative plus
  complète » avec lien vers `fusionDigest()->slug`, pour ne pas laisser un visiteur humain sans
  porte de sortie sur une page volontairement appauvrie.
- **Articles connexes** (bloc lignes 395-413, requête `PublicNewsController.php:103-109` sur
  `category_tag`) : aucune modification requise - une fiche comparative a un `category_tag` comme
  tout article, elle apparaît naturellement dans les connexes de sa rubrique.

## 8. Sécurité et légal

- Le texte source brut (`description`, tronqué 5000 caractères) n'est **jamais** affiché tel quel
  sur une fiche comparative - seul le JSON structuré (citations courtes via `quote`, résumés
  reformulés) est rendu. **Nuance vérifiée** : pour un singleton, `show.blade.php:355-358` affiche
  `$article->description` en clair, mais seulement dans la branche `@if($article->description &&
  !$ss)`. Or `FetchNewsCommand.php:149` ne met `is_published = true` qu'après un `structured_summary`
  rempli avec succès : en pratique un article publié a toujours `$ss` non vide et cette branche ne
  s'exécute jamais pour du contenu public. Actus 2.0 ne change rien à cette garantie existante,
  seulement documentée ici car le mandat l'affirmait sans référence de ligne.
- Chaque source citée dans `sources[]` porte son nom et son auteur (nullable si l'auteur n'est pas
  disponible dans le flux RSS - `NewsArticle::author`, alimenté par `RssFetcherService.php:76`,
  est déjà parfois `null` aujourd'hui, aucune garantie nouvelle à inventer). Citation courte
  (`quote`) reste limitée à 15-25 mots par la consigne de prompt existante (ligne 77 du prompt
  actuel), conservée à l'identique pour le prompt groupe.
- `angle_qc_ca` est nullable et n'est jamais forcé par une consigne de prompt qui inviterait le
  modèle à halluciner un ancrage local - le champ n'existe dans le JSON que si une donnée
  canadienne vérifiable a été fournie en entrée (contexte d'archives ou texte source d'un des
  membres), jamais déduite par défaut.

## 9. Risques et mitigations

- **R1 - Clustering erroné regroupe des sujets différents** : seuil conservateur par défaut
  (`jaccard_threshold` 0.30 ET/OU `min_entity_overlap` 2, à ajuster en observation réelle). En cas
  de doute, le comportement par défaut est le singleton - jamais l'inverse. Contrairement à
  `DedupService::isLikelyDuplicate()` (conçu pour détecter des republications quasi identiques,
  seuil `titleSimilarity > 0.85`, lignes 150-170), le clustering doit reconnaître des titres qui
  diffèrent (angles éditoriaux différents sur le même événement) - **c'est pourquoi une méthode
  distincte `isSameStoryCluster()` est proposée plutôt que de réutiliser `isLikelyDuplicate()`
  tel quel** : « est-ce une republication ? » et « est-ce le même sujet couvert par des sources
  différentes ? » sont deux questions différentes ; fusionner leurs seuils produirait soit des
  groupes manqués, soit des duplicatas syndiqués publiés comme sources indépendantes.
- **R2 - Catégorie non disponible au moment du clustering** : **incohérence trouvée dans le mandat
  et résolue ici plutôt que silencieusement ignorée** - `category_tag` est un champ de **sortie**
  de l'appel IA (`FetchNewsCommand.php:143`), pas une donnée disponible avant l'appel. Le
  clustering, qui doit s'exécuter AVANT l'IA, ne peut donc pas s'appuyer sur `category_tag` malgré
  le mandat initial (« même catégorie »). Seul `feed_type` (`ia`/`techno`, `detectFeedType()`,
  lignes 169-180) est disponible pré-IA, mais reste grossier. **Décision proposée** : aucun signal
  de catégorie dans le clustering ; uniquement la similarité de titres (Jaccard + entités), plus
  fiable qu'une catégorie binaire de toute façon.
- **R3 - Auto-guérison de `PruneSeoCommand` ré-indexe un membre de groupe** : découverte concrète
  en lisant `PruneSeoCommand.php:57-59` - `reindexBase()` repasse en `index` tout article
  `noindex` dont `views_count >= max_views` (300), **sans distinguer** un article noindex parce
  qu'il est vieux/peu vu d'un article noindex parce qu'il est membre d'un groupe. Un membre
  populaire pourrait donc être ré-indexé et concurrencer sa propre fiche comparative en recherche.
  **Mitigation obligatoire, pas optionnelle** : `reindexBase()` doit exclure
  `whereNull('is_potential_duplicate_of')` - modification incluse dans le périmètre de ce
  chantier, pas laissée pour plus tard.
- **R4 - Baisse de la longue traîne** : fusionner 3 fiches minces en 1 fiche substantielle réduit
  les URLs indexables de longue traîne. Mitigation : les membres restent publiés (`is_published =
  true`, seulement `seo_status = noindex`) et donc visitables/partageables - le contenu n'est pas
  perdu, seulement déduplicé côté SEO, cohérent avec la logique déjà acceptée pour `PruneSeoCommand`.
- **R5 - Coût IA** : le nombre d'appels IA ne peut structurellement qu'être égal ou inférieur au
  nombre actuel (chaque groupe de taille k remplace k appels par 1 seul). Dans le pire cas (aucun
  regroupement), le coût est identique à aujourd'hui. Le gain réel dépend du taux de clustering
  observé en production - à mesurer au suivi de la première exécution (section 10), pas promis
  d'avance.

## 10. Stratégie de tests

Suivant le patron Pest déjà en place dans le module (`Modules/News/tests/Feature/PruneSeoCommandTest.php`,
`Modules/News/tests/Unit/RssFetcherDedupObserveTest.php`, `RefreshDatabase`, `Http::fake()`) :

1. **Drapeau OFF = zéro diff** : `config(['news.fusion.enabled' => false])`, exécuter
   `news:fetch` sur un jeu de sources produisant des articles clairement similaires, vérifier
   qu'aucune ligne `news_articles` n'a `is_comparative_digest = true` ni
   `is_potential_duplicate_of` non nul, et que le nombre d'appels HTTP OpenRouter (`Http::fake()`
   + assertions de comptage) égale exactement le nombre d'articles candidats - comportement
   identique au pipeline actuel, non altéré.
2. **Clustering déterministe** : mêmes entrées (mêmes titres/textes/dates) exécutées deux fois
   produisent exactement les mêmes groupes (test de reproductibilité pure, aucun appel réseau
   nécessaire pour ce test puisque `ArticleClusteringService` ne dépend que de `DedupService`).
3. **Seuil conservateur** : deux titres franchement différents ne se regroupent jamais, même avec
   des entités partagées faibles (test de non-faux-positif).
4. **Singleton inchangé** : un article sans jumeau suit le chemin `scoreAndSummarize()` existant,
   `is_comparative_digest` reste `false`, `seo_status` suit la logique actuelle (jamais touchée par
   le code de fusion).
5. **Groupe de 2+ = un seul appel IA** : `Http::fake()` compte les requêtes sortantes vers
   `openrouter.ai`, vérifie qu'un groupe de 3 articles ne déclenche qu'une seule requête (plus les
   retries de cascade en cas d'échec simulé, comme le test doit déjà gérer pour le chemin actuel).
6. **Contrat JSON rétrocompatible** : `flattenStructuredSummary()` et `adminShareContents()`
   (`NewsArticle.php`) appelés sur un `structured_summary` contenant les nouvelles clés
   (`sources`, `divergences`, `archive_context`, `angle_qc_ca`) ne lèvent aucune exception et
   produisent le même résultat qu'avant sur les clés communes.
7. **Quota d'indexation** : au-delà de `max_indexed_digests_per_day`, une fiche comparative
   supplémentaire est créée avec `seo_status = 'noindex'` dès l'insertion (pas de passage différé
   par `PruneSeoCommand` nécessaire pour ce cas).
8. **Sitemap exclut les membres** : `NewsSitemapController` (test existant ou nouveau) ne retourne
   aucun article dont `is_potential_duplicate_of` est non nul, sans modification du contrôleur
   lui-même (garantie par le filtre `seo_status = 'index'` déjà en place, ligne 36).
9. **Auto-guérison n'affecte jamais un membre** : régression ciblée sur
   `PruneSeoCommand::reindexBase()` - un membre `noindex` avec `views_count` élevé reste `noindex`
   après `news:prune-seo` (test qui aurait échoué avant la mitigation R3, doit passer après).
10. **Archives seulement si pertinentes** : `ArchiveContextService` retourne un résultat vide (pas
    une liste factice) quand aucune fiche archivée ne correspond ; le prompt groupe ne mentionne
    alors pas de contexte historique (`archive_context.summary` reste `null`).
11. **Rollback** : désactiver `news.fusion.enabled` après une période active laisse les fiches déjà
    créées intactes en base (aucune suppression), seul le comportement futur change - testé en
    activant, créant un groupe, désactivant, puis en vérifiant qu'aucune écriture de nettoyage
    n'est déclenchée par la désactivation elle-même.

## 11. Plan de déploiement

1. **Drapeau OFF** : fusion de la migration additive (section 4.1), du nouveau service
   `DedupService::isSameStoryCluster()`, et de tout le nouveau code, avec
   `news.fusion.enabled = false` par défaut dans `Modules/News/config/fusion.php`. Déploiement
   normal (voir skill `/deploy`), aucun changement de comportement observable.
2. **Vérification** : exécuter `news:fetch --force` en production drapeau toujours OFF, confirmer
   par les logs et un comptage DB que rien n'a changé (mêmes critères que le test 1 de la section
   10, mais en conditions réelles).
3. **Activation progressive** : `news.fusion.enabled = true` via `Settings::set()` (runtime, pas de
   redéploiement nécessaire pour l'activer/désactiver - cohérent avec le patron `Settings` déjà en
   place pour `news.min_relevance_score`), sur une seule exécution `news:fetch` d'abord en
   observant les logs (`Log::info` à ajouter sur chaque groupe formé, avec ses membres et son
   score - même style que les lignes de log DEDUP-SKIP/DEDUP-OBSERVE existantes).
4. **Suivi de la première exécution** : comparer le nombre d'appels IA effectifs à ce qu'aurait
   coûté le pipeline actuel sur le même lot (mesure R5), inspecter manuellement 3-5 fiches
   comparatives produites (lisibilité, absence de divergence forcée, citations correctes), vérifier
   qu'aucun membre n'est repassé `index` après le premier passage de `PruneSeoCommand` suivant.
5. **Rollback** : `Settings::set('news.fusion.enabled', false)` (immédiat, sans redéploiement) fait
   revenir strictement au pipeline actuel pour toute nouvelle exécution ; les fiches comparatives
   déjà publiées restent en ligne (aucune suppression de données) jusqu'à décision éditoriale
   explicite - cohérent avec la garde-fou « zéro suppression » du projet.

## 12. Critères d'acceptation testables

1. Avec `news.fusion.enabled = false`, `news:fetch` produit un résultat identique bit à bit (mêmes
   colonnes renseignées, même nombre d'appels IA) à l'exécution du pipeline avant ce chantier, sur
   un jeu de données fixe.
2. Deux articles au titre et aux entités clairement partagés, publiés dans la fenêtre configurée,
   produisent UNE fiche comparative avec `is_comparative_digest = true` et deux lignes membres avec
   `is_potential_duplicate_of` pointant vers cette fiche.
3. Un article sans jumeau produit exactement le même enregistrement qu'aujourd'hui (aucune colonne
   nouvelle renseignée sauf `is_comparative_digest = false` par défaut).
4. Un groupe de taille k déclenche exactement 1 appel HTTP OpenRouter, jamais k.
5. Le JSON produit pour un groupe contient toujours `sources` non vide avec `source_name` renseigné
   pour chaque membre ; `author` peut être `null` mais la clé est toujours présente.
6. `angle_qc_ca` n'apparaît (non nul) que si une donnée canadienne vérifiable était présente dans
   les textes sources ou le contexte d'archives fournis en entrée du prompt.
7. Au-delà de `max_indexed_digests_per_day` fiches comparatives indexées un même jour, toute fiche
   supplémentaire est créée avec `seo_status = 'noindex'`.
8. `NewsSitemapController::index()` ne retourne jamais une URL dont l'article a
   `is_potential_duplicate_of` non nul.
9. Après exécution de `news:prune-seo`, aucun article avec `is_potential_duplicate_of` non nul ne
   repasse à `seo_status = 'index'`, quel que soit son `views_count`.
10. Désactiver `news.fusion.enabled` après usage ne supprime, ne modifie et ne masque aucune fiche
    déjà publiée (comparative ou membre).

## 13. Auto-revue

### Contradictions internes relevées et résolues

- Le mandat affirmait le texte source « tronqué 5000 caractères, PAS affiché ». Vérifié : la
  troncature à 5000 caractères a bien lieu dans `RssFetcherService.php:130` (stockage), pas dans
  le prompt IA (qui tronque à 4000 caractères, `AiSummaryService.php:54`). L'affichage est
  conditionnel (`!$ss`), jamais atteint en pratique pour du contenu publié - précisé en section 8.
- Le mandat proposait « même catégorie » comme signal de clustering pré-IA. Vérifié impossible :
  `category_tag` est un champ de sortie IA. Résolu en section 9 (R2) en s'appuyant uniquement sur
  titres/entités plutôt que sur `feed_type` ou `category_tag`.

### Cas limites identifiés

- **Deux fiches comparatives le même jour sur le même sujet** : possible si le clustering tourne en
  plusieurs passes (deux exécutions de `news:fetch`, une source publiant en retard). **Non
  spécifié en détail ici, à trancher avant l'implémentation** : option la plus simple, au moment du
  clustering, vérifier d'abord si un candidat correspond à une fiche comparative déjà existante du
  jour avant de former un nouveau groupe (plutôt que de créer une deuxième fiche concurrente).
- **Article qui arrive après la fiche du groupe** : même cas que ci-dessus - nécessite une requête
  supplémentaire dans `ArticleClusteringService` cherchant les fiches `is_comparative_digest = true`
  récentes correspondant aux mêmes signaux, non détaillée ici.
- **Groupe bilingue** (source FR + source EN) : le prompt impose déjà une sortie 100 % française
  quelle que soit la langue source (ligne 58 actuelle) - hérité sans modification. Le signal de
  clustering doit rester robuste à des titres bilingues : `extractKeyEntities()` normalise via
  `Str::ascii()` (ligne 94) et `jaccardKeywords()` a des listes de mots vides bilingues (ligne 66) -
  partiellement outillé, à valider par un test dédié en implémentation, pas assumé fonctionnel.

### Rétrocompatibilité vérifiée

- **Anciens articles** : `is_comparative_digest` par défaut `false`, aucune migration destructive -
  aucun article historique n'est réinterprété comme membre ou digest.
- **Flux RSS** : `RssFetcherService::fetchSource()` non modifié ; le clustering se produit en aval.
- **Sitemap** : aucune modification de code nécessaire (filtre `seo_status` déjà suffisant,
  `NewsSitemapController.php:36`).
- **`PruneSeoCommand`** : seule pièce existante dont le comportement doit changer (R3) pour rester
  correct sous Actus 2.0 ; sitemap, modèle et service RSS restent inchangés par construction.
- **Liaison outils annuaire** (`NewsArticle::tools()`) : relation inchangée ; une fiche comparative
  peut lire les outils déjà liés à ses membres pour le prompt (section 6), mais ce document ne
  spécifie pas d'écriture automatique de nouvelles liaisons - à trancher en implémentation.
- **Articles connexes** (`PublicNewsController::show()`, lignes 81-109) : requêtes `category_tag`
  non modifiées ; une fiche comparative et ses membres noindex peuvent théoriquement s'afficher
  l'un l'autre - comportement mineur, non bloquant, à observer en suivi (section 11, étape 4).

### Placeholders non justifiés empiriquement

Les valeurs par défaut proposées (`window_hours` 36, `jaccard_threshold` 0.30,
`min_entity_overlap` 2, `max_indexed_digests_per_day` 5, `archive_lookback_months` 6) sont des
points de départ cohérents avec les seuils déjà en place (`jaccard_high` à 0.40 dans
`DedupService`, ligne 153 ; quota déjà réduit à 5 dans `seo_prune.php`, commentaire 2026-08-09),
mais **aucun n'a été validé empiriquement sur un lot réel** - à calibrer au suivi de première
exécution (section 11, étape 4), pas à considérer comme final.

## 14. Décisions post-revue

Section ajoutée à l'implémentation (2026-08-09), après codage complet, tests Pest et vérification
manuelle via tinker (voir `Modules/News/app/Console/FetchNewsCommand.php`,
`Modules/News/app/Services/{ArticleClusteringService,ArchiveContextService,AiSummaryService,DedupService}.php`).

### Arbitrages demandés explicitement (implémentés tels quels)

- **Mécanisme d'absorption unique pour les deux cas limites identifiés en section 13** (« 2e
  fiche comparative le même jour sur le même sujet » et « article qui arrive après la création
  de la fiche ») : `ArticleClusteringService::cluster()` vérifie, pour CHAQUE composante formée
  (singleton ou groupe de 2+), si une fiche comparative récente (`is_comparative_digest = true`,
  fenêtre `window_hours`) matche déjà le sujet via `DedupService::isSameStoryCluster()` sur le
  titre du représentant de la composante contre le titre de la fiche existante. Si oui : tous les
  membres de la composante sont rattachés (`is_potential_duplicate_of`, `dedup_score`,
  `dedup_reason`, `seo_status = noindex`, entrée `news_dedup_log` avec `action = fusion_grouped`)
  SANS appel IA (zéro régénération du texte de la fiche) - exactement la même méthode
  `FetchNewsCommand::absorbFusionMember()` pour les deux cas, comme demandé.

### Écarts d'implémentation trouvés et résolus pendant le codage (non prévus par la spec initiale)

- **Conflit avec DEDUP-SKIP existant (`FetchNewsCommand.php`, découvert en test)** : le garde-fou
  DEDUP-SKIP préexistant (compare tout nouvel article aux articles déjà résumés par IA
  `whereNotNull('structured_summary')`) interceptait un article tardif AVANT qu'il n'atteigne la
  nouvelle logique d'absorption, car une fiche comparative a justement un `structured_summary`
  non nul dès sa création. DEDUP-SKIP dépubliait alors silencieusement l'article tardif
  (`is_published = false`, `summary = '[doublon detecte - IA evitee]'`) au lieu de le rattacher à
  la fiche comparative. **Résolu** : le bloc DEDUP-SKIP est maintenant sauté quand
  `news.fusion.enabled = true` (l'absorption Actus 2.0 le remplace pour ce cas précis, avec un
  résultat strictement meilleur : rattachement publié plutôt que suppression silencieuse). Drapeau
  OFF : DEDUP-SKIP reste identique à aujourd'hui, aucune régression.
- **Sélection de colonnes partielle sur la requête des fiches récentes (`ArticleClusteringService::findMatchingDigest()`)** :
  le premier jet ne sélectionnait que `id, title, seo_title`, ce qui faisait lire `is_published`
  comme `null` (donc `false`) côté appelant lors du rattachement d'un membre absorbé - un membre
  absorbé héritait alors toujours de `is_published = false`, même quand la fiche cible était
  publiée. Corrigé en ajoutant `is_published` à la sélection.
- **Quota `max_ia_articles_per_day`/`max_tech_articles_per_day` appliqué à l'échelle du groupe,
  pas de l'article** (section 5 de la spec initiale ne détaillait pas ce point) : un groupe
  produit UNE seule décision de publication (le score IA du groupe), donc le quota par
  `feed_type` (déterminé par le premier membre du groupe) est vérifié une fois par groupe plutôt
  que par article individuel. Documenté dans le docblock de
  `FetchNewsCommand::processFusionCandidates()`.
- **Choix du membre « digest » au sein d'un groupe** : non spécifié explicitement en section 3-6
  de la spec initiale (elle indiquait seulement qu'`is_potential_duplicate_of` pointe vers « la
  fiche comparative », sans préciser laquelle des lignes existantes le devient). Décision :
  aucune nouvelle ligne `news_articles` n'est créée pour la fiche comparative - un des articles
  déjà présents en base (issu du flux RSS comme les autres) est PROMU au rang de digest
  (`is_comparative_digest = true` + `structured_summary` de synthèse), choisi comme le membre au
  `pub_date` le plus ancien du groupe (déterministe, reproductible). Les autres membres du groupe
  pointent vers lui via `is_potential_duplicate_of`. Cohérent avec le principe « aucune migration
  destructive, une seule colonne ajoutée » de la spec (section 4, section 13).
- **Classement des fiches candidates au quota d'indexation** : implémenté exactement comme
  spécifié (section 5) - le plus gros groupe d'abord, à égalité le plus ancien `pub_date`, jamais
  un score IA comme filtre.

### Tests

`Modules/News/tests/Feature/NewsFusionTest.php` (11 tests) et
`Modules/News/tests/Unit/ArticleClusteringServiceTest.php` (5 tests) couvrent les 10 critères
d'acceptation de la section 12, y compris les deux écarts ci-dessus (régressions ajoutées aux
tests une fois trouvées).

### Correctifs post-revue adversariale (2026-08-09, second passage)

Une revue indépendante a trouvé 2 failles réelles et proposé 1 durcissement dans l'implémentation
initiale de cette section 14. Les trois ont été appliqués :

- **Correctif 1 (faille réelle) - DEDUP-SKIP désactivé globalement quand `fusion.enabled=true`.**
  La première version de ce chantier désactivait entièrement le garde-fou DEDUP-SKIP préexistant
  dès que le drapeau fusion était actif (pour éviter qu'il n'intercepte un article tardif
  destiné à l'absorption). Conséquence non prévue : une republication quasi identique d'un
  article SINGLETON déjà publié (arrivant à une exécution ultérieure du cron) ne matchait ni le
  lot du run en cours (`ArticleClusteringService::buildComponents()` ne voit que les candidats de
  CE passage) ni `findMatchingDigest()` (qui ne cherche que des fiches comparatives) - elle
  aurait été publiée en double. **Corrigé** : DEDUP-SKIP reste TOUJOURS actif
  (`FetchNewsCommand.php`, bloc `if (config('news.dedup_skip_enabled', true) ...)`, drapeau OFF
  ou ON). Quand un doublon est détecté, le comportement diverge désormais selon la nature de
  l'original matché : si c'est une fiche comparative (`is_comparative_digest = true`) ou un
  membre d'une fiche comparative (`is_potential_duplicate_of` non nul, on remonte alors au
  digest parent avec une garde de cohérence - jamais absorber dans un article qui n'est en
  réalité pas un digest), l'article est ABSORBÉ dans cette fiche via `absorbFusionMember()` au
  lieu d'être dépublié ; sinon, le comportement DEDUP-SKIP d'origine s'applique sans changement
  (dépublication, `summary = '[doublon detecte - IA evitee]'`). Le routage vers l'absorption ne
  s'active que si `fusion.enabled = true` - drapeau OFF, comportement strictement identique à
  avant ce chantier.
- **Correctif 2 (faille réelle) - effets de bord de l'observer sur un membre absorbé.** Le
  passage `is_published` de `false` à `true` d'un membre absorbé (il hérite du statut de
  publication de la fiche comparative) déclenchait `NewsArticleObserver::updated()` :
  `createShortUrlIfNeeded()` (lien court créé pour une page satellite `noindex`, inutile) et
  `dispatchAutoToolDetection()` (job `AutoDetectNewsToolsJob` dispatché sur une page qui n'a pas
  vocation à être découverte). **Corrigé** : `absorbFusionMember()` utilise désormais
  `updateQuietly()` plutôt que `update()`, supprimant ces deux effets de bord. Vérifié avant
  d'appliquer le correctif : l'autre écouteur de l'événement `updated` (`NewsArticle::booted()`,
  `ContentPublished`) exige déjà `category_tag`, jamais renseigné sur un membre - aucune logique
  indispensable n'est perdue par ce passage en silencieux.
- **Correctif 3 (durcissement, pas une faille) - anti-injection dans les prompts IA.** Les textes
  sources injectés dans les prompts (`AiSummaryService::scoreAndSummarize()` et
  `scoreAndSummarizeGroup()`) proviennent du web (RSS externe, non fiable) et pourraient contenir
  une instruction malveillante ciblant le modèle. Une phrase a été ajoutée juste avant
  l'insertion des textes sources dans les deux prompts, indiquant explicitement que ce qui suit
  est une donnée non fiable, qu'aucune instruction qui s'y trouverait ne doit être exécutée, et
  que ni le format JSON ni les règles précédentes ne doivent changer quel qu'en soit le contenu.

### Nouveaux tests (correctifs 1 et 2)

- `NewsFusionTest.php` - « une republication d'un article singleton déjà publié reste sautée,
  jamais publiée en double, même drapeau ON » : reproduit exactement la faille du correctif 1
  (republication cross-source d'un singleton, pas un digest) et vérifie qu'elle est toujours
  dépubliée comme avant, zéro appel IA, zéro entrée `news_dedup_log`.
- `NewsFusionTest.php` - « l'absorption d'un membre ne crée pas de lien court et ne déclenche pas
  la détection auto d'outils » : `Bus::fake()` posé après la publication de fixture du digest
  (pour ne pas confondre l'effet de bord légitime de CETTE publication avec celui, à vérifier,
  de l'absorption du membre), puis `Bus::assertNotDispatched(AutoDetectNewsToolsJob::class)` et
  `short_url_id` du membre resté `null` après absorption.

Suite complète `Modules/News` après ces correctifs : **133 tests passants (348 assertions)**,
zéro régression sur les tests préexistants ni sur les 16 tests Actus 2.0 du premier passage.

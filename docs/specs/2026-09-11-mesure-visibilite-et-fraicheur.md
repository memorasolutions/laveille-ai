# Mesure - visibilité auteurs et faux signal de fraîcheur (2026-09-11)

Document de MESURE uniquement. Aucun correctif, aucun commit, aucun déploiement.
`.env` n'a jamais été lu par l'agent : les requêtes de production ont été exécutées
par un script PHP one-shot déposé temporairement dans `public/` du site en
production (`public_html/apps_diverses/laveille.ai`), qui reprend exactement le
bootstrap Laravel de `public/index.php` (merge PSR-4 des modules + `bootstrap/app.php`),
donc utilise le `.env` du SERVEUR comme le fait toute requête web réelle - jamais lu
par l'agent. Le script était protégé par un jeton en POST, journal 100 % lecture
(aucune écriture DB), et s'est auto-supprimé (`register_shutdown_function`) après
chaque appel - confirmé par `cpanel_file_list` : aucun résidu.

Toutes les mesures chiffrées ci-dessous viennent de la base de **PRODUCTION**
(`gmemora_laveille`, hôte `127.0.0.1:3306`, confirmé par le message d'erreur SQL
de la première tentative). Le repère temporel de production est le
2026-09-11 (heure d'exécution des requêtes : environ midi Québec, non minutée
précisément - non nécessaire pour des COUNT).

---

## MESURE A - articles d'auteur invisibles (visibility `subscribers`/`premium`)

### Contexte de code (déjà établi par la demande, revérifié)

- `Modules/Authors/app/Http/Controllers/PostController.php` : la méthode `show()`
  construit la requête ainsi (lignes 24-28) :
  ```
  $post = AuthorPost::where('author_profile_id', $author->id)
      ->where('slug', $postSlug)
      ->published()
      ->public()
      ->firstOrFail();
  ```
  `->public()` (scope `AuthorPost::scopePublic()`, `Modules/Authors/app/Models/AuthorPost.php:84-86`)
  filtre `WHERE visibility = 'public'`. Un post `subscribers` ou `premium` ne
  matche donc jamais cette requête → `firstOrFail()` lève une 404, pour
  N'IMPORTE QUEL visiteur, y compris l'auteur lui-même (aucune vérification de
  propriété avant le filtre de visibilité).
- `resources/views/livewire/author-editor.blade.php:60-64` propose bel et bien
  trois valeurs dans le `<select>` : `public`, `subscribers`, `premium`
  (confirmé par lecture directe du fichier).
- La ligne d'incrément de vues est à `PostController.php:32` :
  `ViewCounterService::record($post, 'views_count');` (pas ligne 29 comme
  indiqué dans la demande - décalage dû aux commentaires précédant l'appel).

### Chiffres de PRODUCTION

Requête : `SELECT visibility, COUNT(*) AS c FROM author_posts GROUP BY visibility` →
**tableau vide**. Requête de contrôle `SELECT COUNT(*) AS c FROM author_posts` →
**`c = 0`**.

**La table `author_posts` est actuellement VIDE en production (0 ligne, tous
statuts et visibilités confondus, soft-deleted inclus car la requête est du SQL
brut hors scope `Eloquent`).** Confirmé par une deuxième mesure indépendante
(`SHOW COLUMNS FROM author_posts`, 18 colonnes retournées sans erreur : la table
existe et son schéma correspond exactement au modèle - `visibility` est bien un
`enum('public','subscribers','premium')`, `status` un
`enum('draft','published','scheduled','archived')` - mais elle ne contient
aucune ligne).

Contexte : `author_profiles` compte **1 profil non archivé** en production
(`SELECT COUNT(*) FROM author_profiles WHERE archived_at IS NULL` → `1`). Il
existe donc un auteur enregistré sur la plateforme, mais cet auteur n'a
publié (ni même brouillonné) aucun article via ce module.

### Réponses aux 4 points

1. **Combien de `author_posts` portent `visibility` = `subscribers`, combien =
   `premium` ?** Réponse : **0 et 0**. La table est vide.
2. **Parmi eux, combien sont PUBLIÉS ?** Réponse : **0** (sans objet, aucune
   ligne n'existe).
3. **Répartition par auteur, titre + date si moins de 20.** Sans objet : liste
   vide. La condition « moins de 20 » est trivialement vraie (0 < 20) mais il
   n'y a rien à lister.
4. **Toutes les valeurs distinctes de `visibility` réellement présentes en
   base, avec leur compte.** Réponse : **aucune valeur présente** - la colonne
   existe et son type déclare trois valeurs possibles (`public`,
   `subscribers`, `premium`), mais zéro ligne n'en porte aucune actuellement.

### Conclusion MESURE A

Le défaut de code est **réel et confirmé** (`->public()` bloque bien
`subscribers`/`premium`, y compris pour l'auteur propriétaire), mais il **n'a
actuellement aucune victime en production** : personne n'a encore créé
d'article dans ces deux états sur le module Authors, qui semble largement
dormant (1 seul profil, 0 article, tous statuts confondus). Le défaut se
déclenchera dès qu'un article `subscribers`/`premium` sera créé - rien ne
l'empêche dans l'éditeur.

---

## MESURE B - faux signal de fraîcheur (`ViewCounterService` → `updated_at` → `dateModified`)

### Rappel du mécanisme (déjà établi par la demande, revérifié)

`ViewCounterService::record()` (`Modules/Core/app/Services/ViewCounterService.php:73`) :
```
$model::query()->whereKey($model->getKey())->increment($columns[0]);
```
`Illuminate\Database\Eloquent\Builder::increment()`
(`vendor/laravel/framework/src/Illuminate/Database/Eloquent/Builder.php:1331-1336`)
appelle `addUpdatedAtColumn()`
(`Builder.php:1359-1364`), qui court-circuite UNIQUEMENT si
`! $this->model->usesTimestamps() || is_null($this->model->getUpdatedAtColumn())`.
Aucun des quatre modèles ci-dessous ne déclare `public $timestamps = false` ni
ne redéfinit `getUpdatedAtColumn()` (vérifié par grep sur chaque fichier
modèle) → `usesTimestamps()` reste vrai par défaut `Eloquent` → `updated_at` est
bien réécrit à CHAQUE appel de `ViewCounterService::record()`, quel que soit le
nom de la colonne incrémentée (`views_count` ou `clicks_count`).

### 1. Timestamps actifs ? (par modèle)

| Module | Modèle | Fichier | `$timestamps` | Verdict |
|---|---|---|---|---|
| News | `NewsArticle` | `Modules/News/app/Models/NewsArticle.php:25` | non déclaré | actif (défaut) |
| Tools | `Tool` | `Modules/Tools/app/Models/Tool.php:17` | non déclaré | actif (défaut) |
| Directory | `Tool` | `Modules/Directory/app/Models/Tool.php:29` | non déclaré | actif (défaut) |
| Authors | `AuthorPost` | `Modules/Authors/app/Models/AuthorPost.php:16` | non déclaré | actif (défaut) |

### 2. `updated_at` alimente-t-il un `dateModified` publié quelque part ?

Recherche exhaustive : `grep -rn "dateModified" --include=*.php --include=*.blade.php Modules app`
(liste complète des 20+ occurrences obtenue, chaque appelant vérifié un par un) +
recherche du composant `page-freshness.blade.php` et de `lastmod`/sitemap.

- **`Modules/FrontTheme/resources/views/components/page-freshness.blade.php`** :
  composant `@props(['updated', ...])` qui construit un JSON-LD `WebPage` avec
  `dateModified` = la prop `updated` reçue (ligne 7). **Il n'est utilisé QUE
  par des pages piliers statiques** (`etat-ia-quebec-2026.blade.php`,
  `veille-ia-quebec.blade.php`, `ia-cas-usage-pme-quebec.blade.php`, etc. -
  16 usages recensés), **toujours avec une date texte codée en dur**
  (`updated="2026-06-19"` par exemple), jamais reliée à un modèle. **Aucun des
  quatre modules (News/Tools/Directory/Authors) n'utilise ce composant.**
  Il est donc hors-sujet pour cette mesure, mais répond directement à la
  question posée : ce composant est un point mort pour les 4 modules.

- **News** : `Modules/SEO/app/Services/JsonLdService::newsArticle()`
  (`Modules/SEO/app/Services/JsonLdService.php:248`) :
  `'dateModified' => $article->updated_at?->toIso8601String()`. Appelé depuis
  `Modules/News/resources/views/public/show.blade.php:163` :
  `\Modules\SEO\Services\JsonLdService::newsArticle($article)`. **Mais** une
  garde existe (`JsonLdService.php:267-268`) : si
  `$article->hasEditorialReview()` (= `! is_null($this->reviewed_at)`,
  `Modules/News/app/Models/NewsArticle.php:1170-1172`) est vraie, `dateModified`
  est REMPLACÉ par `$article->reviewed_at` (date de relecture humaine réelle,
  écrite uniquement par `markReviewedByHuman()`, `NewsArticle.php:1400-1406`).
  → Le faux signal atteint le JSON-LD de la page **seulement pour les
  articles sans relecture humaine enregistrée** (`reviewed_at` nul).
  **En plus**, `Modules/SEO/app/Http/Controllers/SitemapController.php:255-259`
  pose `->setLastModificationDate($article->updated_at)` comme `<lastmod>`
  du sitemap général, **sans aucune garde `reviewed_at`** - ce canal-là est
  toujours exposé au faux signal, même pour un article relu.

- **Tools** : Aucune occurrence de `dateModified`/JSON-LD dans
  `Modules/Tools/resources/views/public/show.blade.php` (vue générique
  utilisée par la majorité des ~190 fiches d'outils) - fichier vérifié
  intégralement, aucun balisage structuré, aucune date affichée. Deux pages
  spécifiques par slug (`constructeur-prompts.blade.php:1767`,
  `anonymiseur.blade.php:39`) affichent un `dateModified` JSON-LD, mais
  **calculé avec `now()->toIso8601String()`** - donc déconnecté de
  `$tool->updated_at` (un défaut voisin mais distinct : ces deux pages
  prétendent toujours avoir été modifiées « à l'instant », indépendamment de
  toute visite). Une page « Mis à jour le… » avec date figée en dur existe
  aussi (`brain-dump.blade.php:398`, `<time datetime="2026-05-13">`), hors
  sujet. **En revanche**,
  `Modules/SEO/app/Http/Controllers/SitemapController.php:84-90` pose
  `->setLastModificationDate($tool->updated_at)` pour chaque outil actif dans
  le sitemap général (`sitemap.xml`).

- **Directory** : `JsonLdService::softwareApplication()`
  (`Modules/SEO/app/Services/JsonLdService.php:136-176`, appelé depuis
  `Modules/Directory/resources/views/public/show.blade.php:1564`) **n'a
  aucun champ `dateModified`** - vérifié en lisant la méthode complète.
  `JsonLdService::toolFaqPage()` (appelé ligne 1570 du même fichier) n'en a
  pas non plus. **Mais** un texte VISIBLE existe bel et bien :
  `Modules/Directory/resources/views/public/show.blade.php:561` :
  ```
  <span ...>{{ __('Mis à jour le') }} {{ format_date($tool->updated_at) }}</span>
  ```
  affiché sans condition, juste à côté du badge « ✓ Vérifié par La veille »
  (ligne 560) - sur CHAQUE fiche outil de l'annuaire. **En plus**,
  `SitemapController.php:124-154` pose aussi
  `->setLastModificationDate($tool->updated_at)` dans le sitemap général pour
  chaque fiche publiée non archivée.

- **Authors** : `app/Helpers/jsonld.php:177`, fonction `lv_jsonld_blog_posting()` :
  `'dateModified' => ($post->updated_at ?? $post->published_at ?? now())->toIso8601String()`.
  Cette fonction est appelée depuis `PostController::show()`
  (`Modules/Authors/app/Http/Controllers/PostController.php:34-36`,
  `if (function_exists('lv_jsonld_blog_posting')) { $graph[] = lv_jsonld_blog_posting($post, $author); }`).
  **En plus**, `Modules/Authors/app/Services/AuthorsSitemapService.php:70` pose
  `$xmlWriter->writeElement('lastmod', $post->updated_at->toIso8601String());`
  dans le sitemap dédié `sitemap-authors.xml` (posts filtrés `->published()->public()`,
  cohérent avec la MESURE A : seuls les posts publics visibles y figurent).

### 3. Conclusion nette par module

| Module | Verdict | Canal(aux) touché(s) |
|---|---|---|
| **News** | **CHAÎNE COMPLÈTE** (partiellement conditionnelle) | JSON-LD `dateModified` de la page (seulement si `reviewed_at` est nul) **+** `lastmod` du sitemap général (toujours, sans garde) |
| **Tools** | **CHAÎNE COMPLÈTE** pour le sitemap ; **ROMPUE** pour la page elle-même | `lastmod` du sitemap général uniquement - aucun `dateModified`/affichage visible sur la fiche outil générique |
| **Directory** | **CHAÎNE COMPLÈTE** (le cas le plus visible) | Texte VISIBLE « Mis à jour le… » sur chaque fiche **+** `lastmod` du sitemap général ; le JSON-LD `SoftwareApplication`, lui, est ROMPU (pas de champ `dateModified`) |
| **Authors** | **CHAÎNE COMPLÈTE** | JSON-LD `dateModified` de la page auteur **+** `lastmod` du sitemap dédié `sitemap-authors.xml` |

Aucun des quatre modules n'utilise le composant `page-freshness.blade.php`
(réservé aux pages piliers à date codée en dur).

### 4. Glossaire en PRODUCTION - dérive `updated_at` vs dernière modification éditoriale

Aucune colonne dédiée ne trace la date de dernière modification éditoriale du
glossaire (`dictionary_terms` ne porte ni `content_updated_at`, ni
`reviewed_at`, ni équivalent - vérifié par `SHOW COLUMNS FROM dictionary_terms`
via le script de mesure, aucune colonne de ce type dans les 18+ colonnes
retournées par ailleurs pour `author_posts` à titre de comparaison de méthode ;
pour `dictionary_terms` la vérification a porté sur la présence de
`views_count`, confirmée `true`).

**Meilleure approximation disponible, utilisée** : le modèle `Term`
(`Modules/Dictionary/app/Models/Term.php:23-30`) utilise le trait
`LogsActivityStandard` (`Modules/Core/app/Traits/LogsActivityStandard.php`)
avec `$activitylogFields = ['name', 'definition', 'analogy', 'example', 'did_you_know', 'is_published']` et `logOnlyDirty()`
(`Term.php:33-34`, trait ligne 20). Ce journal (table `activity_log`, colonne
`log_name` valant `'term'`) n'est écrit QUE par les événements `Eloquent`
`created`/`updated` sur un `save()` explicite - **jamais** par
`ViewCounterService::record()`, qui passe par le query builder brut
(`increment()`) et ne déclenche aucun événement de modèle. Le plus récent
horodatage `activity_log` par terme est donc une trace fiable de la dernière
modification éditoriale RÉELLE (au sens des 6 champs listés), distincte du
simple `updated_at` technique.

Chiffres (requête sur `gmemora_laveille` en production, 2026-09-11) :

- **544 termes** au total dans `dictionary_terms`.
- **464 termes n'ont AUCUNE entrée dans `activity_log`** (log_name = 'term') -
  soit ils n'ont jamais été modifiés depuis un import initial hors trait
  d'activité (migrations en lot, cf. les dizaines de migrations
  `add_glossary_term_*`), soit leur historique d'édition antérieur au
  branchement du trait n'a pas été rejoué. **Pour ces 464 termes, aucune
  approximation fiable de la dernière modification éditoriale n'est
  disponible** - impossible de mesurer une dérive pour eux avec les données
  actuelles.
- **80 termes ont au moins une entrée `activity_log`** (81 lignes au total,
  donc en moyenne ~1,01 entrée par terme concerné - très peu de ré-éditions
  captées).
- **Parmi ces 80 : 78 ont un `updated_at` postérieur de plus de 24 heures à
  leur dernière modification éditoriale enregistrée.** Seuls 2 termes sur 80
  ont un écart de 24 h ou moins.
- L'ampleur de la dérive est considérable sur l'échantillon des 25 premiers
  cas retournés : de 353 h (~15 jours) à 1 196 h (~50 jours) d'écart entre la
  dernière vraie modification de contenu et le `updated_at` actuel - ce
  dernier ayant été rafraîchi entre-temps uniquement par des vues de page
  (`views_count`).

**Portée honnête de ce chiffre** : il ne couvre que les 80 termes qui ont un
point de comparaison. Sur l'ensemble des 544 termes, on ne peut affirmer avec
certitude que 78 sont en dérive prouvée de plus de 24 h ; les 464 restants
sont non déterminés par manque de trace éditoriale, pas par absence du
défaut - le mécanisme technique (`increment` → `updated_at` → `dateModified`) les
touche identiquement, faute d'un point de référence pour le mesurer.

---

## Ce qui n'a PAS été établi

- Le taux d'articles News avec `reviewed_at` renseigné (donc à l'abri du faux
  signal sur le JSON-LD de leur propre page, mais pas sur le sitemap) n'a pas
  été mesuré - non demandé explicitement par les 4 points de la MESURE B,
  mais utile pour calibrer l'ampleur réelle sur ce module.
- Aucune mesure de l'ampleur du faux signal Tools/Directory en nombre de
  fiches concernées (contrairement au glossaire) - non demandé par la
  MESURE B point 4, qui ne portait explicitement que sur le glossaire.

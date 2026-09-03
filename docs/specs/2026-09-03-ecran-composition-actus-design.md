# Design doc : extension de l'écran de composition des actualités (admin)

- **Auteur** : MEMORA solutions
- **Module** : `News`, avec impact partagé sur `Directory` (liaison d'outils) et `Core` (pipeline d'images, sans le modifier)
- **Statut** : proposition décisionnelle soumise au fondateur avant tout code. Chaque option ci-dessous est tranchée et justifiée par l'auteur ; ce document n'a pas encore été débattu en panel multi-IA ni approuvé - c'est son objet.
- **Source** : brief factuel `scripts/.scratch/20260903-brief-actus/brief.md` (lecture seule du code réel, 2026-09-03), complété par une relecture directe des fichiers cités pour lever les ambiguïtés nécessaires à des décisions fermes. Toute affirmation porte son ancre `fichier:ligne`.
- **Portée** : 4 volets indépendamment déployables (section 11).

## 1. Contexte et objectif

L'écran `/admin/news/composition` (`NewsCompositionController` + `composition-builder.blade.php`) permet aujourd'hui à un humain de choisir une actualité collectée et d'éditer trois champs seulement : `seo_title`, `summary`, `internal_source_text` (`NewsCompositionController.php:398-421`). Tout le reste de la richesse éditoriale d'une fiche - le titre affiché, le résumé structuré en sections, les paires de preuve, les sources primaires, le crédit photo, la nature de la source, le niveau de preuve, les outils liés - n'est écrivable que par une seule porte : `php artisan news:apply --payload` (`NewsApplyCommand.php`), la commande CLI que le skill `/actu2` pilote pour le compte d'un agent.

Le ticket interne demande de combler cet écart : « calqué sur le Concentré mais adapté aux actus ». Lecture faite de `concentre-builder.blade.php`, cette référence porte sur la **coquille visuelle** de l'écran (deux colonnes, sélecteur d'actualités à gauche, panneau de composition à droite, convention `cb-btn`/`cb-section-title` déjà identique dans les trois builders) : `composition-builder.blade.php` importe déjà le fichier partagé `news-builder-shell.css` (`composition-builder.blade.php:23`), alors que `concentre-builder.blade.php` (`<style>` local, `:7-25`) et `video-goal-builder.blade.php` en gardent chacun leur propre copie non unifiée du même CSS - convention visuelle identique, fichier non encore mutualisé pour ces deux-là, hors périmètre de ce document. Le sélecteur d'actualités lui-même (colonne de gauche) est déjà DRY via le partial `news-article-picker.blade.php`, `@include`d par composition-builder.blade.php (`:146`). Pas de structure de champs riches préexistante à imiter : le Concentré ne compose pas de `composed_summary`. Ce document ne réinvente donc pas la coquille, il l'étend.

L'objectif de ce chantier n'est pas d'ajouter une seconde façon d'écrire ces champs : c'est de faire en sorte qu'un humain, depuis un navigateur, puisse faire exactement ce qu'un agent fait par la CLI - avec les mêmes règles de validation, les mêmes bornes, la même doctrine de non-effacement silencieux. **Le CLI reste la porte des agents ; l'admin devient la porte des humains ; les deux passent par le même service.** C'est la colonne vertébrale des quatre décisions qui suivent.

---

## 2. Volet A - écran de composition complet

### 2.1 Ce qui manque, précisément

| Champ | Aujourd'hui | Après ce lot |
|---|---|---|
| `title` | CLI seulement (`NewsApplyCommand.php:168`, régénère le slug sauf `--enrich`) | Écran de composition |
| `composed_summary` (→ `structured_summary`) | CLI seulement, fusion sous-clé par sous-clé | Écran de composition, même fusion |
| `editorial_proof_pairs` | Backend déjà présent et correct (`storeProofPair`/`destroyProofPair`, `NewsCompositionController.php:627-679`), **aucune UI** ne les appelle (zéro occurrence dans `composition-builder.blade.php`) | UI branchée sur ces mêmes endpoints |
| `primary_sources` | CLI seulement, remplacement complet, plafond 10 | Écran de composition |
| `image_credit` | CLI seulement | Écran de composition |
| `nature_original` | CLI seulement | Écran de composition |
| `niveau_preuve` | CLI seulement | Écran de composition |
| `related_tool_slugs` | CLI seulement, ajout pur (jamais un remplacement) | Écran de composition, même sémantique additive |

Deux constats structurants avant de trancher :

1. **`editorial_proof_pairs` n'a pas de dette de validation côté admin.** `storeProofPair()` délègue déjà à `EditorialProofNormalizer::verifyFactPair()` (`NewsCompositionController.php`, commentaire daté 2026-08-29, todo #1984). Le vrai travail ici est presque entièrement frontend : brancher une UI sur des routes qui existent déjà et fonctionnent déjà.
2. **La duplication réelle à corriger vit ailleurs, et le code l'admet lui-même.** Le docblock de `EditorialProofNormalizer::verifyFactPair()` dit explicitement (`EditorialProofNormalizer.php:78`) : « `NewsApplyCommand::normalizeProofPairs()` garde pour l'instant sa propre implémentation en ligne du même raisonnement (hors périmètre de ce correctif) ». `normalizeProofPairs()` (`NewsApplyCommand.php:1423-1496`, ~74 lignes) réécrit en ligne la même règle fait/analyse/primary_fact que `storeProofPair()` applique déjà correctement, pour traiter un **lot** de paires au lieu d'une seule. C'est exactement la duplication que ce design doit fermer - pas une duplication hypothétique, une duplication déjà repérée et non traitée dans le code.

### 2.2 Décision centrale - un service de normalisation partagé

**Décision.** Créer `Modules/News/app/Services/CompositionPayloadNormalizer.php`, classe à méthodes statiques (même convention qu'`EditorialProofNormalizer`, aucun état d'instance nécessaire). Chaque méthode reçoit une valeur brute et retourne un résultat structuré `{ok: bool, value: mixed, error: ?string}` (ou `{accepted, rejected}` pour les lots) - **jamais** un appel à `$this->error()` ni une exception : c'est déjà le patron que le code utilise pour `EditorialProofNormalizer::verifyFactPair()` et pour `NewsArticle::publishReadinessCheck()` (« ne lève jamais d'exception et n'écrit rien : chaque appelant traduit ce résultat dans son propre format », docblock de `publishReadinessCheck()`, `NewsArticle.php:357`, méthode elle-même `:378`). Ce document suit ce patron déjà établi, il n'en invente pas un nouveau.

Chaque appelant reste responsable de sa présentation : `NewsApplyCommand` traduit une erreur en `$this->error()` + code de sortie non nul ; `NewsCompositionController` la traduit en réponse JSON 422. La logique de validation, elle, n'existe plus qu'à un seul endroit.

**Méthodes migrées telles quelles (comportement inchangé, code déplacé) :**

| Méthode (nouvelle localisation) | Origine (à retirer de `NewsApplyCommand`) |
|---|---|
| `CompositionPayloadNormalizer::normalizeComposedSummary(array $input): array` | `normalizeComposedSummary()` (`:1638-1732`) + ses 3 sous-fonctions privées (`normalizeComposedKeyPoints` `:1780-1813`, `normalizeComposedQuote` `:1824-1869`, `normalizeComposedReperesDates` `:1880-1933`) |
| `CompositionPayloadNormalizer::overlayComposedSummary(array $existing, array $normalized): array` | `overlayComposedSummary()` (`:1753-1770`), fonction pure, déplacement sans risque |
| `CompositionPayloadNormalizer::normalizePrimarySources(array $input): array` | `normalizePrimarySources()` (`:1508-1554`) |
| `CompositionPayloadNormalizer::normalizeSlugsList(array $input, string $fieldName, int $maxCount): array` | `normalizeSlugsList()` (`:1348-1371`) - générique, sert aussi de fait `related_article_slugs`/`related_article_slugs_remove`/`related_tool_slugs_remove` sans qu'aucun de ces appelants n'ait à changer |

**Méthode fusionnée (élimine la vraie duplication identifiée en 2.1) :**

`CompositionPayloadNormalizer::validateProofPair(string $sourceText, array $pair): array{ok: bool, entry: ?array, reason: ?string}` remplace à la fois le corps de la boucle de `NewsApplyCommand::normalizeProofPairs()` et le corps de `NewsCompositionController::storeProofPair()`. Elle encapsule dans un seul endroit : la vérification de forme (`statement`/`excerpt`/`type` chaînes non vides), le type autorisé (`fact`/`analysis`/`primary_fact`), la revalidation `EditorialProofNormalizer::verifyFactPair()` pour `fact` (avec le marqueur `source_verified: false` quand le texte source est déjà purgé - todo #1984), l'URL `source_url` obligatoire et valide pour `primary_fact`.

- `NewsApplyCommand::normalizeProofPairs()` devient une boucle de 6 lignes : pour chaque paire du lot, appelle `validateProofPair()`, range le résultat dans `accepted`/`rejected` - comportement de traitement indépendant par paire strictement identique à aujourd'hui (todo #1984 déjà résolu, préservé).
- `NewsCompositionController::storeProofPair()` appelle `validateProofPair()` une fois (une paire), retourne 422 avec le message si `ok: false`, sinon persiste `entry`. Diff minimal sur un endpoint déjà correct.

**Ce qui n'est PAS extrait (et pourquoi).** `nature_original`/`niveau_preuve` restent des vérifications d'une ligne (`array_key_exists($valeur, NewsArticle::XXX_VALUES)`) répétées à chaque appelant plutôt qu'enveloppées dans une méthode du service. Le projet a déjà tranché ce seuil : « dupliquer 2-3 lignes simples reste acceptable et préférable à une abstraction prématurée » (CLAUDE.md, DRY et anti-sur-ingénierie). La vraie duplication dangereuse n'est pas la ligne de comparaison, c'est la **liste des valeurs autorisées** - et celle-là est déjà centralisée dans le modèle pour `nature_original` (voir 2.3). Envelopper un `array_key_exists()` dans une méthode dédiée ajouterait une indirection sans réduire aucun risque de divergence.

### 2.3 Précédent à répliquer - promouvoir `niveau_preuve` au même rang que `nature_original`

Le code documente lui-même la leçon à appliquer ici. `nature_original` avait une liste de valeurs dupliquée jusqu'au ticket #1915 (2026-08-30) ; le correctif l'a centralisée dans `NewsArticle::NATURE_ORIGINAL_VALUES` (constante `clé => libellé français`, `NewsArticle.php:1076-1084`), avec `natureOriginalLabel()` comme point de lecture unique. `niveau_preuve` est aujourd'hui exactement dans l'état que ce correctif a corrigé pour l'autre champ : une liste privée dupliquée (`NewsApplyCommand::ALLOWED_NIVEAU_PREUVE`, `:213`) **et** une traduction française codée en dur dans la vue publique (`show.blade.php:430-435`, tableau associatif PHP `$niveauPreuveLabels` construit en dur dans un bloc `@php`, pas la liste `NIVEAU_PREUVE_VALUES` proposée ci-dessous), sans aucun lien entre les deux.

**Décision.** Ajouter à `NewsArticle.php`, à côté de `NATURE_ORIGINAL_VALUES` :

```php
public const NIVEAU_PREUVE_VALUES = [
    'primaire' => 'Fondée sur la source originale',
    'mixte' => 'Sources originale et média',
    'relais' => "D'après un média relais",
];

public function niveauPreuveLabel(): ?string
{
    return self::NIVEAU_PREUVE_VALUES[$this->niveau_preuve] ?? null;
}
```

Trois lectures convergent vers cette seule source : `NewsApplyCommand` valide contre `array_keys(NewsArticle::NIVEAU_PREUVE_VALUES)` au lieu de sa constante privée ; `show.blade.php:430-435` appelle `$article->niveauPreuveLabel()` au lieu de construire son propre tableau associatif ; le nouveau menu déroulant de l'écran de composition (2.6) lit la même constante pour afficher ses options en français. C'est un changement mécanique, à comportement strictement identique (mêmes trois valeurs, mêmes trois libellés) - le bénéfice est uniquement de fermer, avant qu'il ne se reproduise, le même défaut que celui déjà corrigé sur `nature_original`.

### 2.4 Deuxième extraction, distincte - résolution des outils liés par slug

`related_tool_slugs`/`related_tool_slugs_remove` ne sont pas un problème de *validation de champ* (comme les précédents) mais de *résolution de pivot* : deux méthodes privées de `NewsApplyCommand` (`attachRelatedTools()`, `:1065-1101`, et `detachRelatedTools()`, `:1118-1174`) résolvent des slugs Spatie-traduisibles contre `Tool::published()` (`Modules/Directory/app/Models/Tool.php:270`, `scopePublished()`), puis attachent/détachent le pivot `news_article_tool`. Leur foyer naturel n'est pas le nouveau service de composition : c'est `Modules/News/app/Actions/NewsToolSyncAction.php`, qui possède déjà `sync()` (`:33`) et `attachAuto()` (`:54`) pour ce même pivot.

**Décision.** Promouvoir les deux méthodes en méthodes publiques de `NewsToolSyncAction`, retournant des données plutôt qu'imprimant sur la console :

```php
public function attachBySlug(NewsArticle $article, array $slugs): array // {attached: string[], unknown: string[]}
public function detachBySlug(NewsArticle $article, array $slugs): array  // {detached: string[], unknown: string[], not_attached: string[]}
```

`attachBySlug()` résout contre `Tool::published()` puis délègue à `attachAuto()` déjà existante (aucune réécriture de la logique de pivot elle-même, seule la résolution de slug est déplacée). `detachBySlug()` reprend telle quelle la logique de `detachRelatedTools()` (résolution, calcul des IDs réellement attachés avant detach, `NewsToolSyncAction::invalidatePublicCache()`). `NewsApplyCommand::handle()` appelle ces deux méthodes et traduit leurs listes `unknown`/`not_attached` en `$this->warn()` - comportement console inchangé au mot près. Le nouveau contrôleur admin (2.6) appelle les mêmes méthodes et traduit en JSON.

### 2.5 Champ par champ - écriture

**`title`.** Ajouté à `NewsCompositionController::update()`, règle `sometimes|nullable|string|max:200` (même borne que `NewsApplyCommand.php` : 200, **pas** 255 comme `seo_title`), `lv_strip_em_dash()` appliqué avant écriture (même traitement que le CLI). Point d'attention réel, absent du CLI qui le gère déjà via `--enrich` : **le slug ne doit jamais bouger sur une fiche déjà publiée.** `NewsCompositionController::update()` n'a aujourd'hui aucune garde `is_published` (fait déjà noté dans le code lui-même, docblock de `storeProofPair()` : « aucune garde is_published ici ni côté route, contrairement à `news:apply` hors `--enrich` », `:618`). Ce document ne referme pas ce trou préexistant sur les trois champs déjà éditables (hors périmètre de ce chantier), mais **le nouveau champ `title` doit reproduire exactement la logique CLI** pour ne pas l'aggraver :

```php
if (array_key_exists('title', $validated)) {
    $updates['title'] = lv_strip_em_dash(trim($validated['title']));
    if (! $article->is_published) {
        $updates['slug'] = NewsArticle::generateUniqueSlug($updates['title'], $article->id);
    }
}
```

C'est la condition `--enrich` du CLI (`NewsApplyCommand.php:382`), transposée : sur l'admin, la fiche déjà publiée EST la condition qui, côté CLI, se signale par un flag explicite. Sans cette garde, corriger une coquille dans le titre d'une fiche en ligne changerait silencieusement son URL - exactement le défaut que `--enrich` a été créé pour éviter côté CLI (`NewsApplyCommand.php`, note datée 2026-08-27).

**`composed_summary`.** Ajouté à `update()`, règle `sometimes|nullable|array`. Le contrôleur appelle `CompositionPayloadNormalizer::normalizeComposedSummary()` puis, si la fiche porte déjà `hasComposedSummary()`, `overlayComposedSummary($existing, $normalized)` - fusion sous-clé par sous-clé identique au CLI, marqueur `composed: true` posé par l'appelant (jamais par le service, même séparation des responsabilités qu'aujourd'hui). **Choix de forme du formulaire** : le panneau de composition charge l'état complet des 8 sous-clés à l'ouverture (`show()` les expose déjà toutes via `structured_summary`) et renvoie l'état complet à chaque sauvegarde - un champ laissé vide dans le formulaire est envoyé comme `null` explicite, donc traité par `overlayComposedSummary()` comme un retrait voulu, jamais comme un silence. C'est la sémantique WYSIWYG normale d'un formulaire humain ; elle obtient par construction la même garantie que la garde « silence ne veut pas dire efface » sans en avoir besoin explicitement, puisque rien n'est jamais silencieusement omis du formulaire. Le service reste néanmoins appelé pour un payload partiel dans tous les cas (défense en profondeur, et sécurité pour une future action plus chirurgicale côté admin).

**`editorial_proof_pairs`.** Aucun changement de contrat : `storeProofPair()`/`destroyProofPair()` existent déjà, routes déjà déclarées (`web.php:111-112`). Seul `storeProofPair()` est retouché en interne pour appeler `validateProofPair()` (2.2). Le travail restant est entièrement dans la vue (2.6).

**`primary_sources`.** Ajouté à `update()`, règle `sometimes|array|max:10`. Remplacement complet (pas d'accumulation, contrairement aux preuves et aux outils) - même sémantique que `normalizePrimarySources()` aujourd'hui, cohérent avec le fait que c'est déjà un remplacement côté CLI. Chaque entrée : `label` (requis, chaîne), `url` (requis, http/https), `note` (optionnel, chaîne).

**`image_credit`.** Ajouté à `update()`, règle `sometimes|nullable|string|max:255` (même borne que le CLI, `NewsApplyCommand.php:550`).

**`nature_original`.** Ajouté à `update()`, règle `sometimes|nullable|string`, puis `array_key_exists($valeur, NewsArticle::NATURE_ORIGINAL_VALUES)` (2.2, choix de ne pas envelopper cette ligne).

**`niveau_preuve`.** Ajouté à `update()`, même patron, contre `NewsArticle::NIVEAU_PREUVE_VALUES` (2.3, nouvellement créée).

**`related_tool_slugs`.** **Pas** ajouté à `update()` : deux routes dédiées, symétriques de `proof-pairs.store`/`proof-pairs.destroy`, action immédiate (pas de bouton « Enregistrer » à part) - cohérent avec l'interaction déjà en place pour les preuves éditoriales sur ce même écran, pas un troisième patron d'interaction inventé pour l'occasion :

```php
Route::post('/{article}/related-tools', [NewsCompositionController::class, 'storeRelatedTool'])->name('related-tools.store');
Route::delete('/{article}/related-tools/{slug}', [NewsCompositionController::class, 'destroyRelatedTool'])->name('related-tools.destroy');
```

`storeRelatedTool()` valide `tool_slug` (chaîne), appelle `NewsToolSyncAction::attachBySlug($article, [$slug])`. `destroyRelatedTool()` appelle `detachBySlug($article, [$slug])`. Chacune invalide le cache public ciblé (déjà fait par `NewsToolSyncAction::invalidatePublicCache()`, réutilisé tel quel).

### 2.6 Protection contre l'écrasement concurrent

**Le risque est nouveau, pas théorique.** Aujourd'hui, `update()` n'a aucun verrou optimiste (aucune occurrence d'`expected_updated_at` dans `composition-builder.blade.php` ni dans le contrôleur) et ça ne pose pas de problème réel : les trois champs actuels (`seo_title`, `summary`, `internal_source_text`) ne sont écrits que depuis ce seul écran, par un seul humain à la fois dans l'usage observé. À partir de ce lot, **le CLI (agent `/actu2`) et l'admin (humain) peuvent écrire les mêmes champs riches sur la même fiche** - `composed_summary`, `primary_sources`, `nature_original`, `niveau_preuve`, `image_credit`, `title`. Un agent qui termine un cycle `/actu2` pendant qu'un humain a cet écran ouvert sur la même fiche écraserait le travail de l'autre en silence, sans qu'aucun des deux ne le sache.

**Décision.** Réutiliser le patron déjà éprouvé côté CLI (`expected_updated_at`, `NewsApplyCommand.php:328-341`), mais **proportionné** : pas `expected_source_hash` (ce contrôle protège spécifiquement un agent qui travaille depuis un prompt figé généré à un instant T - un humain devant son écran regarde toujours l'état courant, le risque qu'il corrige n'existe qu'à l'échelle de l'`updated_at`). `show()` expose déjà `updated_at` (`NewsCompositionController.php:383`) - rien à ajouter côté lecture.

Règle d'activation, pour ne rien changer au comportement déjà stable des trois champs existants : le verrou ne s'applique **que si** le payload de `update()` contient au moins une des nouvelles clés riches (`title`, `composed_summary`, `primary_sources`, `image_credit`, `nature_original`, `niveau_preuve`). Un appel qui ne porte que `seo_title`/`summary`/`internal_source_text` continue de se comporter exactement comme aujourd'hui, sans aucun verrou - zéro risque de régression sur le chemin déjà stable.

```php
if (array_intersect(array_keys($validated), self::RICH_FIELDS) !== []) {
    if (($validated['expected_updated_at'] ?? null) !== $article->updated_at?->toIso8601String()) {
        return response()->json(['error' => "Cette fiche a été modifiée depuis l'ouverture de cet écran - recharge-la avant d'enregistrer."], 409);
    }
}
```

Les endpoints étroits (`storeProofPair`/`destroyProofPair`, `storeRelatedTool`/`destroyRelatedTool`) **n'ont pas besoin** de ce verrou : ce sont des opérations d'ajout/retrait ciblées (append ou detach par identifiant précis), sans jamais remplacer un objet entier - deux admins qui ajoutent chacun une preuve différente au même instant obtiennent les deux preuves, aucune perte possible par construction. Le verrou n'a de sens que là où l'écriture remplace un objet entier (`update()`).

### 2.7 UI - `composition-builder.blade.php`

Aujourd'hui, les trois champs éditables vivent dans `<details class="nc-manual-details"><summary>Édition manuelle (filet de secours)</summary>` (`:275-276`) - un intitulé qui reflète leur rôle actuel de dernier recours minimal. Une fois ce lot livré, ces champs riches deviennent la façon **normale** de composer depuis l'admin, pas un filet de secours.

**Décision.** Ajouter un nouveau panneau, toujours visible (pas de `<details>` replié), positionné entre le bandeau de sélection et le `<details>` existant, qui garde son rôle et son libellé actuels inchangés (angle éditorial, ancien flux de génération de prompt déprécié, `seo_title`/`summary`/`internal_source_text`) :

- **Titre publié** (`title`, texte simple) et **titre SEO** (`formSeoTitle`, déjà existant, reste où il est - les deux coexistent, ce sont deux champs distincts du modèle malgré la confusion actuelle où le libellé « Titre publié » du filet de secours pointe en fait vers `seo_title`, `composition-builder.blade.php:309`. Corriger ce libellé trompeur au passage : `formSeoTitle` redevient « Titre SEO (balise `<title>`) »).
- **Résumé structuré** : huit champs correspondant aux sous-clés de `composed_summary` (accroche `hook`, jusqu'à 5 puces `key_points`, `why_important`, `key_number`, citation `quote` en deux champs texte+auteur, `angle_qc_ca`, `action_concrete`, jusqu'à 4 repères `reperes_dates` en trois champs date/texte/url) - regroupés en sous-cartes, même patron visuel `cb-section-title` que le Concentré.
- **Preuves éditoriales** : liste des paires existantes (affichage `statement`/extrait tronqué/badge de type/lien `source_url` si `primary_fact`), bouton de suppression par ligne (`destroyProofPair`, action immédiate) ; formulaire d'ajout en bas (`statement`, `excerpt`, sélecteur de type, `source_url` affiché seulement si `primary_fact`), soumis à `storeProofPair` (action immédiate, hors du bouton « Enregistrer » principal - même patron que l'existant).
- **Sources primaires** : lignes répétables (`label`/`url`/`note`), boutons ajouter/retirer une ligne, plafonnées à 10 côté client (miroir du plafond serveur, pas une nouvelle règle).
- **Crédit photo**, **nature de la source**, **niveau de preuve** : trois champs simples, les deux derniers en `<select>` peuplés respectivement par `NewsArticle::NATURE_ORIGINAL_VALUES` et `NIVEAU_PREUVE_VALUES` (passés à la vue par le contrôleur, jamais recopiés en dur dans le Blade).
- **Outils liés** : réutilisation de TomSelect (déjà une dépendance du projet, déjà utilisée pour `tool_ids[]` sur l'écran classique, `Modules/News/resources/views/admin/articles/edit.blade.php:64,102`), mais interaction **immédiate** (une sélection appelle `related-tools.store`, un retrait de puce appelle `related-tools.destroy`) plutôt que différée au clic sur « Enregistrer » - cohérent avec le patron déjà en place pour les preuves éditoriales sur ce même écran, pas un troisième patron d'interaction sur la même page.

`formSeoTitle`/`formSummary`/`formSourceText` restent envoyés par le bouton « Enregistrer » existant (`save()`, `:1012-1051`) ; les nouveaux champs batch (`title`, `composed_summary`, `primary_sources`, `image_credit`, `nature_original`, `niveau_preuve`) rejoignent ce même appel `PUT`, avec `expected_updated_at: this.selectedArticle.updated_at` ajouté au corps JSON (2.6). Un 409 affiche un message et propose de recharger la fiche (`fetchNews()`/re-`selectItem()` déjà présents dans le mixin partagé).

### 2.8 Fichiers à créer / modifier

| Fichier | Nature | Contenu |
|---|---|---|
| `Modules/News/app/Services/CompositionPayloadNormalizer.php` | **Créé** | `normalizeComposedSummary`, `overlayComposedSummary`, `normalizePrimarySources`, `normalizeSlugsList`, `validateProofPair` (2.2) |
| `Modules/News/app/Console/NewsApplyCommand.php` | Modifié | Retrait des 9 méthodes privées migrées/promues (2.2 : `normalizeComposedSummary`, `normalizeComposedKeyPoints`, `normalizeComposedQuote`, `normalizeComposedReperesDates`, `overlayComposedSummary`, `normalizePrimarySources`, `normalizeSlugsList` ; 2.4 : `attachRelatedTools`, `detachRelatedTools`) ; leurs appels remplacés par des appels au service et à `NewsToolSyncAction`. `normalizeProofPairs()` reste, réduite à une boucle de 6 lignes sur `validateProofPair()` (2.2). `ALLOWED_NIVEAU_PREUVE` retirée au profit de `NewsArticle::NIVEAU_PREUVE_VALUES`. Comportement observable strictement inchangé - voir 8 (tests de non-régression). |
| `Modules/News/app/Actions/NewsToolSyncAction.php` | Modifié | Ajout de `attachBySlug()`/`detachBySlug()` (2.4) |
| `Modules/News/app/Models/NewsArticle.php` | Modifié | Ajout de `NIVEAU_PREUVE_VALUES` + `niveauPreuveLabel()` (2.3) |
| `Modules/News/app/Http/Controllers/Admin/NewsCompositionController.php` | Modifié | `update()` étendu (2.5) ; `storeProofPair()` retouché pour appeler `validateProofPair()` ; nouveaux `storeRelatedTool()`/`destroyRelatedTool()` ; `show()` retourne en plus `nature_original`, `niveau_preuve`, `composed_summary` (déjà dans `structured_summary`), listes d'options `NATURE_ORIGINAL_VALUES`/`NIVEAU_PREUVE_VALUES`, outils déjà liés |
| `Modules/News/routes/web.php` | Modifié | 2 routes ajoutées sous le groupe `admin/news/composition` existant (`:90-116`) |
| `Modules/News/resources/views/admin/composition-builder.blade.php` | Modifié | Nouveau panneau (2.7) ; `<details>` existant conservé pour son périmètre actuel |
| `Modules/News/resources/views/public/show.blade.php` | Modifié | `:430-435` remplacé par `$article->niveauPreuveLabel()` (2.3) |

**Aucune migration.** Toutes les colonnes touchées par ce volet existent déjà (`title`, `structured_summary`, `editorial_proof_pairs`, `primary_sources`, `image_credit`, `nature_original`, `niveau_preuve`, pivot `news_article_tool`) - elles ont été ajoutées par les chantiers CLI antérieurs. Ce lot est un chantier de code et de vue, jamais de schéma.

### 2.9 Ce qui ne bouge pas

`CompositionPromptBuilder` (le gros prompt, déjà marqué « déprécié » dans la vue elle-même, `:290`) n'est pas touché : il reste le filet de secours qu'il est déjà devenu depuis l'arrivée du mini-prompt `/actu2`. `NewsApplyCommand` garde sa liste blanche `ALLOWED_PAYLOAD_KEYS` intacte (elle protège l'agent, pas l'admin - la validation HTTP de Laravel joue ce même rôle côté admin, ce n'est pas une règle à extraire ni à dupliquer littéralement, seulement la même *discipline*). `entities`, `related_article_slugs`, `related_article_slugs_remove`, `fact_check`, `meta_description`, `original_post`, `url` restent des clés CLI uniquement - non listées dans le mandat de ce volet, volontairement hors périmètre (section 10).

### 2.10 Alternative rejetée

Écrire les nouveaux champs riches directement dans `AdminNewsController::updateArticle()` (l'écran classique, qui édite déjà `tool_ids[]`/`meta_description`) plutôt que dans l'écran de composition : rejetée. Le ticket vise explicitement l'écran de composition (« calqué sur le Concentré »), et l'écran classique n'a ni le sélecteur d'actualités, ni le texte source, ni les preuves éditoriales - y ajouter la composition riche aurait dupliqué un second poste de travail plutôt que d'en compléter un seul.

---

## 3. Volet B - unification du pipeline d'images

### 3.1 État actuel

Trois chemins écrivent une image d'actualité aujourd'hui, avec des standards différents :

| Chemin | Service | Poids max | Format | Chemin de stockage |
|---|---|---|---|---|
| CLI `news:apply --image` | `NewsImageService::processFromLocalFile()` | - | `.webp` 80 + `.jpg` 85, `cover(1200,630)` | `storage/news/images/{id}.webp`/`.jpg` |
| Composition (`NewsCompositionController::uploadImage()`) | `NewsImageService::processFromUploadedFile()` | 8192 Ko | idem | idem |
| Écran classique (`AdminNewsController::uploadArticleImage()`) | `ScreenshotUploadService::upload()` | **5120 Ko** | `.jpg` seul (pas de `.webp`), même `cover(1200,630)` (`ScreenshotUploadService.php:51`) | `news-screenshots/{slug}.jpg` (**convention différente**) |

Le troisième chemin est un pipeline concurrent complet : même recadrage 1200×630 que les deux autres, mais format de sortie différent (pas de `.webp`, donc pas d'allègement de la page publique), plafond de poids différent, et surtout une **convention de nommage différente** (`{slug}.jpg` contre `{id}.webp`/`.jpg`) - le seul écart réel est le format/poids/nommage, pas le cadrage visuel, ce qui rend l'unification proposée en 3.2 à risque visuel nul. `ScreenshotUploadService::upload()` écrit directement dans la colonne passée en paramètre (`'image_url'`, `AdminNewsController.php:292`) à chaque appel, alors que `NewsImageService::processFromUploadedFile()` ne touche jamais cette colonne (elle est résolue par convention de chemin, ou déjà posée en amont par la collecte RSS ou par `news:apply --image` la première fois qu'elle était vide, `NewsApplyCommand::applyImage()`, fiche 33530, `NewsApplyCommand.php:1941-2010`). C'est cohérent en pratique aujourd'hui parce que l'écran classique n'édite que des fiches déjà collectées par RSS (donc déjà pourvues d'une `image_url`) - mais c'est une dépendance implicite, pas une garantie.

### 3.2 Décision

Unifier `AdminNewsController::uploadArticleImage()` sur `NewsImageService`, à l'identique du chemin déjà utilisé par l'écran de composition - aucun nouveau pipeline créé, réutilisation stricte de l'existant :

```php
public function uploadArticleImage(Request $request, NewsArticle $article, NewsImageService $imageService)
{
    $request->validate(['screenshot' => 'required|image|mimes:jpg,jpeg,png,webp|max:'.NewsImageService::MAX_UPLOAD_KB]);

    $file = $request->file('screenshot');
    [$width, $height] = array_pad((array) @getimagesize($file->getRealPath()), 2, 0);
    if ($width < NewsImageService::MIN_WIDTH || $height < NewsImageService::MIN_HEIGHT) {
        return $this->imageTooSmallResponse($request, $width, $height); // même message que uploadImage()
    }

    $imageUrl = $imageService->processFromUploadedFile($file, $article->id);

    // Fermeture du même trou que celui déjà documenté et corrigé côté CLI (news:apply --image,
    // fiche 33530) : une fiche créée hors collecte RSS n'a jamais eu de valeur initiale.
    if (blank($article->image_url)) {
        $article->update(['image_url' => $imageUrl]);
    }
    // ...
}
```

Ce dernier bloc (« si vide ») est une précision nécessaire, pas une extension du périmètre : `NewsCompositionController::uploadImage()` (le modèle qu'on réutilise) ne l'a pas non plus aujourd'hui, alors que l'écran classique, lui, écrivait `image_url` à chaque appel via `ScreenshotUploadService`. Retirer cette écriture sans la remplacer romprait le seul cas où l'écran classique en dépendait réellement (une fiche dont `image_url` n'était jamais renseigné). Le correctif « si vide » est le même mécanisme déjà écrit et documenté dans `NewsApplyCommand::applyImage()` pour ce cas précis - appliqué ici aux deux portes d'upload manuel pour fermer symétriquement un trou déjà connu, plutôt que de le déplacer sans le refermer.

Validation : `max:8192` (au lieu de `max:5120`) - un utilisateur peut désormais déposer un fichier plus lourd qu'avant sur l'écran classique, seul effet visible de ce lot pour un humain (voir la justification du bump SemVer, section 11).

### 3.3 Le piège réseaux sociaux

`NewsImageService::processFromUploadedFile()`/`processFromLocalFile()` produisent systématiquement un `.jpg` en plus du `.webp` (`NewsImageService.php:117-122`), précisément parce que Facebook et LinkedIn n'affichent pas d'aperçu WebP (commentaire du code lui-même, référence handoff S79 #19). `SocialImageResolver::shareable()` (`Modules/Core/app/Services/SocialImageResolver.php:52-85`) cherche ce `.jpg` jumeau pour construire `og:image`, jamais le `.webp`. Ce chantier ne touche pas `SocialImageResolver` - il en devient simplement un client plus fiable : après ce lot, les trois portes d'upload produisent toutes le jumeau `.jpg` que ce resolver attend, alors qu'aujourd'hui l'écran classique le produit déjà (par hasard, puisque `ScreenshotUploadService` génère directement un `.jpg`) mais sans le `.webp` compagnon pour la page publique elle-même.

### 3.4 Ce qui ne bouge pas

`ScreenshotUploadService` n'est **pas modifié**. Il reste le service de `Modules/Directory` (annuaire) et de `ModerationController`, avec son propre contrat (`cover(1200,630)`, format unique, pas de standard webp+jpg) - un changement de son comportement aurait un rayon d'impact bien au-delà des actualités, hors du mandat de ce document. `NewsImageService::processFromUrl()` reste neutralisé (incident PicRights, `NewsImageService.php:42`) - ce chantier ne le réactive pas.

### 3.5 Alternative rejetée

Faire converger dans l'autre sens - remplacer `ScreenshotUploadService` par `NewsImageService` comme service générique partagé par `Directory` et `News` : rejetée. `NewsImageService` est né spécifique aux actualités (nommage par `articleId`, méthodes signées `NewsArticle`) ; le généraliser pour l'annuaire serait une refonte bien plus large qu'unifier deux portes d'upload d'un seul module, et le design doc `2026-08-10-screenshots-annuaire-design.md` a déjà tranché indépendamment l'avenir de `ScreenshotUploadService` côté annuaire (il y reste inchangé, section 5 de ce même document). Toucher aux deux services à la fois romprait l'indépendance de déploiement des deux chantiers.

---

## 4. Volet C - doctrine « liens seulement »

### 4.1 Le flux existant, confirmé

Le brief (section 4) et le design doc `2026-08-13-actus-zero-copie-design.md` (retrouvé, contrairement à ce que le brief indiquait en incertitude - voir 4.2) confirment un flux cohérent, que ce chantier ne modifie pas : une fiche brouillon reçoit son texte source par une des portes existantes (collage manuel via `internal_source_text` sur l'écran de composition, récupération Markdown automatique, ou `news:source` côté `/actu2`) ; `CompositionPromptBuilder::build()` le lit pour construire un prompt (`sourceText`, `CompositionPromptBuilder.php:107`) ; `editorial_proof_pairs` cite des extraits exacts de ce texte comme preuve ; à la publication - n'importe lequel des chemins connus, tous délégués à `NewsArticle::publishAndPurgeSource()` - le texte intégral est effacé sans condition (`internal_source_text' => null`, dans la même transaction que `is_published' => true`) ; un filet quotidien (`news:verify-source-purge`, ~07h05 Québec) rattrape toute fiche publiée qui porterait encore ce texte, peu importe la cause.

### 4.2 Décision 1 - retirer `description` de `$fillable`

Le design doc `2026-08-13-actus-zero-copie-design.md` (retrouvé à `docs/specs/2026-08-13-actus-zero-copie-design.md` - la recherche du brief ne l'avait pas localisé, il existe pourtant) précise le sort exact de la colonne `description`, levant l'incertitude n°2 du brief : la purge du 2026-08-13 a été un **videment de données par requête SQL directe** (`DB::table('news_articles')->update(['description' => ''])`, section 5 étape 6 de ce document, vérifié `SELECT COUNT(*) FROM news_articles WHERE description != ''` = 0 à l'étape 7), **pas** une migration `dropColumn`. La colonne reste donc dans le schéma, vide (chaîne vide, pas `NULL`), et reste dans `$fillable` du modèle (`NewsArticle.php:61-62` - le tableau `$fillable` commence à `:61`, `'description'` est son deuxième élément à `:62`) bien qu'aucun chemin de code actuel n'y écrive plus rien (confirmé par l'audit du même design doc, section 3, et par les commentaires explicites « jamais lu(e) depuis `$article->description` » dans `FetchNewsCommand.php:180,432,828`).

**Décision.** Retirer `'description'` du tableau `$fillable` de `NewsArticle.php`. C'est un durcissement défensif, pas une correction d'un défaut actif : aucun écrivain existant ne sera affecté (l'audit du 2026-08-13 les a tous fermés), mais un **futur** code qui inclurait par inadvertance `'description' => $texteBrut` dans un tableau d'`update()` massif - exactement le défaut d'origine que toute cette doctrine existe pour empêcher - échouera silencieusement (assignation ignorée) plutôt que de rouvrir la fuite. La colonne elle-même n'est pas supprimée du schéma (aucune migration `dropColumn` dans ce lot) : ce n'est pas dans le mandat de ce document, et une suppression de colonne mérite sa propre revue, pas un effet de bord d'un chantier sur l'écran de composition.

### 4.3 Décision 2 - le collage manuel alimente déjà le prompt puis est purgé

Le mandat demande de vérifier que l'écran de composition permet déjà de coller un texte source qui alimente le prompt puis est purgé selon les mécanismes existants. **Confirmé, aucun changement requis.** Le textarea `formSourceText` (`composition-builder.blade.php:188`) est lié à `internal_source_text` via `update()` (`NewsCompositionController.php:398-421`, calcule aussi `source_content_hash`/`source_captured_at` via `applySourceProvenance()`) ; `generatePrompt()` (`:555-591`) lit ce même champ pour construire le prompt via `CompositionPromptBuilder::build()` ; `publish()` (`:777+`) le purge sans condition via `publishAndPurgeSource()`. Ce chantier n'ajoute et ne retire rien à ce flux - il reste la doctrine en vigueur, et le nouveau panneau de composition (2.7) ne fait qu'ajouter des champs éditoriaux À CÔTÉ de ce textarea déjà fonctionnel, sans y toucher.

---

## 5. Volet D - règle « deux sources »

### 5.1 Ce qui existe déjà

La section « Sources » en fin de fiche publique (`show.blade.php:820-850`) boucle déjà sur la totalité de `primary_sources` sans limite propre au-delà des 10 entrées déjà plafonnées à l'écriture - avec 2 sources en base, cette section afficherait déjà aujourd'hui 2 liens distincts, **sans aucune modification de code**. Seule la ligne de provenance compacte, en haut de fiche (`show.blade.php:441-443`), reste limitée à `$primarySources[0]`, protégée par une garde d'absence que le code réel porte et qu'il faut préserver :

```blade
@if(!empty($primarySources[0]['url'] ?? null))
    <p class="nw-provenance">{{ __("D'après") }} <a href="{{ $primarySources[0]['url'] }}" target="_blank" rel="noopener nofollow">{{ $primarySources[0]['label'] ?? __('la source primaire') }}</a>@if($nwRelay = $article->displayRelayName()), {{ __('relayé par') }} {{ $nwRelay }}@endif</p>
@endif
```

### 5.2 Décision

Étendre cette ligne à deux sources maximum quand deux existent, en laissant la section « Sources » du bas rester le lieu exhaustif (jusqu'à 10). **La garde `@if(!empty($primarySources[0]['url'] ?? null))` est conservée à l'identique** : `$primarySources` est toujours un tableau (jamais `null`, calculé `show.blade.php:427`), donc une fiche sans aucune source primaire donne `$primarySources = []` - sans cette garde, `$primarySources[0]['url']` génère un avertissement PHP (clé de tableau inexistante) au lieu de simplement ne rien afficher :

```blade
@if(!empty($primarySources[0]['url'] ?? null))
    <p class="nw-provenance">
        {{ __("D'après") }}
        <a href="{{ $primarySources[0]['url'] }}" target="_blank" rel="noopener nofollow">{{ $primarySources[0]['label'] ?? __('la source primaire') }}</a>
        @if(!empty($primarySources[1]['url'] ?? null))
            {{ __('et') }}
            <a href="{{ $primarySources[1]['url'] }}" target="_blank" rel="noopener nofollow">{{ $primarySources[1]['label'] ?? __('une autre source primaire') }}</a>
        @endif
        @if($nwRelay = $article->displayRelayName()), {{ __('relayé par') }} {{ $nwRelay }}@endif
    </p>
@endif
```

Deuxième source testée par la même forme que la première (`!empty(...['url'] ?? null)`), pas par `count($primarySources) >= 2` : une fiche pourrait en théorie porter une entrée d'indice 1 dont `url` est vide (donnée historique, jamais nettoyée) - tester l'URL elle-même, comme le fait déjà le code pour l'indice 0, est la même garde appliquée deux fois plutôt que deux critères différents pour deux positions du même tableau.

**Non-objectif explicite** : la ligne compacte ne cite jamais plus de deux sources (« D'après X, Y et Z » pour 3+ n'est pas construit). Le mandat demande précisément « D'après X et Y quand 2 sources » - étendre au-delà sans besoin exprimé serait la sur-ingénierie que la doctrine DRY du projet écarte explicitement (« ne pas paramétrer une variable sans deuxième usage réel »). Une fiche à 3 sources et plus reste entièrement lisible : les sources 3 à 10 restent visibles dans la section « Sources » du bas, déjà exhaustive sans changement.

Le plafond serveur de 10 sources à l'écriture (`NewsApplyCommand::normalizePrimarySources()`, et son miroir dans `CompositionPayloadNormalizer` pour l'admin, section 2.5) n'est pas touché par ce volet - seul le rendu de la ligne compacte change.

### 5.3 Dépendance avec le volet A

Ce correctif de vue est indépendant de tout le reste de ce document : la donnée qu'il affiche existe déjà (le CLI peut écrire plusieurs `primary_sources` depuis longtemps), donc il est déployable et testable dès aujourd'hui, avant même que l'écran de composition (volet A) ne permette à un humain de saisir une deuxième source. Volet A rendra simplement ce rendu atteignable aussi depuis un geste humain, pas seulement depuis l'agent `/actu2`.

---

## 6. Contraintes juridiques que ce design respecte

Rappel des contraintes déjà tranchées par les protocoles d'août 2026 (aucune nouvelle recherche pour cette section - consolidation seulement) et de la façon dont ce design s'y conforme :

1. **Zéro persistance durable du texte source.** `internal_source_text` reste purgé sans condition à la publication (`publishAndPurgeSource()`, section 4.1) - ce document n'ajoute aucun nouveau chemin de lecture ni de conservation de ce texte ; le nouveau panneau de composition (2.7) l'affiche dans le même textarea déjà existant, sous la même règle.
2. **Citation verbatim bornée à 15-25 mots, défendable par le droit de citation.** Cette borne est une consigne éditoriale déjà encodée dans les prompts de rédaction (`AiSummaryService.php:77-78,174,183-184`) - ce chantier ne l'encode pas au niveau du code (aucun compteur de mots serveur, ni pour le CLI ni pour l'admin) mais ne l'affaiblit pas non plus : `composed_summary.quote.text` reste plafonné à 400 caractères côté serveur (≈ 60-70 mots), une borne large qui n'empêche pas la discipline éditoriale des 15-25 mots mais ne la remplace pas - cette discipline reste une responsabilité humaine ou de prompt, pas un champ que ce design invente.
3. **Attribution avec nom d'auteur (art. 29.2 LDA).** Le composant `x-news::quote-attribution` et le style `.nw-quote-source-link` (`show.blade.php:291-293`) restent inchangés ; ce design n'ajoute ni ne retire de citation directe, il ajoute des champs de composition (résumé structuré, sources primaires) qui accompagnent ce mécanisme sans le contourner.
4. **Jamais le texte intégral dans le JSON-LD.** `internal_source_text` reste absent des champs lus par `Modules/SEO/app/Services/JsonLdService.php` (garde-fou confirmé par le brief, section 4 : jamais un chemin public). Ce document n'ajoute aucun nouveau producteur de JSON-LD ni de nouvelle source pour son `articleBody`.

---

## 7. Sécurité et risques

- **Autorisation.** Toutes les routes ajoutées (2.5, 3.2) héritent du groupe de middleware déjà en place sur `admin/news/composition` (`routes/web.php:90-93`) - aucune nouvelle règle d'autorisation à écrire, aucun changement de gate.
- **Bornage serveur, jamais confiance au client.** Chaque champ ajouté à `update()` porte exactement les mêmes bornes que son équivalent CLI (2.5) - la borne côté client (`maxlength` HTML, plafond JS sur les lignes répétables) est un confort d'UX, jamais la seule garde.
- **Verrou optimiste proportionné** (2.6) : ferme le seul risque de casse réellement introduit par ce chantier - une écriture concurrente humain/agent sur les mêmes champs riches.
- **`related_tool_slugs` reste additif/soustractif, jamais un remplacement** (2.5) : un humain qui utilise le nouvel écran ne peut jamais, par erreur, effacer d'un coup tous les outils liés à une fiche - contrairement à `tool_ids[]` de l'écran classique, qui, lui, remplace tout (comportement existant, non touché par ce document).
- **Volet B - poids maximal relevé** (5120 → 8192 Ko sur l'écran classique) : c'est le seul endroit où ce document élargit une capacité déjà permise ailleurs (l'écran de composition accepte déjà 8192 Ko) - alignement, pas une nouvelle exposition.
- **Régression croisée.** `CompositionPayloadNormalizer` et les méthodes promues de `NewsToolSyncAction` sont des extractions de code existant, pas des réécritures - le risque principal est une divergence de comportement introduite par erreur pendant le déplacement, couvert par la stratégie de tests (section 8).

## 8. Stratégie de tests

Le module `News` a déjà une suite substantielle pour ce périmètre (`Modules/News/tests/Feature/NewsApplyCommandTest.php`, `ComposedSummaryApplyTest.php`, `NewsCompositionBuilderTest.php`, `Actu2CompositionScreenTest.php`, `CompositionCandidatesDuJourTest.php`) - ce chantier l'étend, il ne part pas de zéro. Convention du projet reprise telle quelle (`RecaptureFutileFilterTest.php:33` comme référence) : `uses(Tests\TestCase::class, RefreshDatabase::class)`, style Pest, aucune factory `Tool` (les outils de test sont créés directement via `Tool::create()` minimal, comme le fait déjà `NewsApplyCommandTest.php` pour `related_tool_slugs`).

1. **Non-régression de l'extraction** (`Modules/News/tests/Unit/CompositionPayloadNormalizerTest.php`, nouveau) : chaque méthode migrée (2.2) testée en isolation avec les cas déjà couverts par `NewsApplyCommandTest.php`/`ComposedSummaryApplyTest.php` avant migration (mêmes entrées, mêmes sorties attendues) - preuve que le déplacement n'a rien changé.
2. **Suite CLI existante rejouée sans modification attendue** (`NewsApplyCommandTest.php`, `ComposedSummaryApplyTest.php`) : après le refactor de 2.2/2.4, ces fichiers doivent passer sans qu'aucune assertion n'ait dû changer - c'est la preuve que `NewsApplyCommand` se comporte à l'identique après extraction.
3. **Nouveaux champs admin** (`Modules/News/tests/Feature/CompositionRichFieldsTest.php`, nouveau) : `update()` avec chaque nouveau champ (title avec/sans fiche publiée - CA slug jamais changé si publiée ; composed_summary avec fusion ; primary_sources avec remplacement et plafond 10 ; nature_original/niveau_preuve valeur invalide refusée) ; `storeRelatedTool`/`destroyRelatedTool` (slug inconnu signalé, additif jamais un remplacement) ; verrou optimiste (409 sur `updated_at` périmé, silencieux si absent des clés riches).
4. **`storeProofPair()` après refactor** : extension de la couverture existante de `NewsCompositionBuilderTest.php` pour prouver l'équivalence avant/après le passage par `validateProofPair()`.
5. **Volet B** (`Modules/News/tests/Feature/AdminNewsImageUploadTest.php`, nouveau ou extension d'un fichier existant si trouvé à l'implémentation) : upload via l'écran classique produit désormais `.webp`+`.jpg` au chemin `news/images/{id}.*`, poids jusqu'à 8192 Ko accepté, `image_url` posé seulement si vide.
6. **Volet D** : extension d'un test de rendu public existant (`show.blade.php`) - fiche à 1 source affiche la ligne actuelle inchangée ; fiche à 2 sources affiche « D'après X et Y » ; fiche à 3 sources affiche toujours seulement les deux premières sur cette ligne, la troisième restant visible dans la section Sources du bas.
7. **Suite complète, pas seulement le scope du module modifié** (règle du projet, déjà appliquée par le design doc de référence sur les captures d'écran) : la suite `Modules/Directory/tests` doit passer sans modification après la promotion de méthodes dans `NewsToolSyncAction` (2.4), puisque `Directory` est le module propriétaire de `Tool`.
8. **Visuel** : capture Playwright avant/après du nouveau panneau de composition sur une fiche de test, preuve que les huit sous-champs de `composed_summary` s'affichent et s'enregistrent - garde-fou projet, jamais de « terminé » sans preuve visuelle.

## 9. Rollback

- **Aucune migration dans ce chantier** (volets A, B, D) : un rollback de code (`git revert`) suffit dans tous les cas, aucune étape de restauration de schéma n'est nécessaire.
- **Volet C, décision 1** (retrait de `description` de `$fillable`) : rollback trivial, une ligne à réintroduire - la colonne elle-même n'a jamais été touchée.
- **`CompositionPayloadNormalizer` et les méthodes promues de `NewsToolSyncAction`** : purement additifs au niveau des classes ; en cas de régression détectée sur le CLI après le refactor de 2.2/2.4, un rollback ciblé de `NewsApplyCommand.php` seul (sans toucher aux nouveaux fichiers/méthodes, simplement en réintroduisant temporairement les méthodes privées d'origine) reste possible sans affecter les routes/vues du volet A déjà déployées séparément - c'est justement l'intérêt du découpage en lots (section 11).
- **Volet B** : un rollback de `AdminNewsController::uploadArticleImage()` vers `ScreenshotUploadService` ne perd aucune image déjà déposée (les fichiers écrits par `NewsImageService` restent lisibles, `versionedImageUrl()` continue de fonctionner) - seul le prochain upload reprendrait l'ancien pipeline.
- **Interrupteur applicatif** : non nécessaire pour ce chantier - contrairement au point focal des captures d'écran (qui modifie un rendu automatique massif), chaque champ ajouté ici est une capacité d'édition supplémentaire, jamais un changement de comportement par défaut d'un mécanisme déjà en production. Un rollback de code est suffisant et rapide dans tous les cas.

## 10. Non-objectifs

- **`meta_description`** : éditable sur l'écran classique (`AdminNewsController`), pas sur l'écran de composition, alors même que le CLI peut l'écrire depuis le 2026-08-30 (ticket #1942). Cette asymétrie existe déjà indépendamment de ce chantier et n'est pas résolue ici - le mandat de ce document liste précisément les champs à ajouter et `meta_description` n'y figure pas. Signalé pour arbitrage futur, pas tranché en silence.
- **`entities`, `related_article_slugs`/`related_article_slugs_remove`, `fact_check`, `original_post`, `url`** : clés `ALLOWED_PAYLOAD_KEYS` non listées dans le mandat de ce volet - restent CLI uniquement.
- **Guarde `is_published` générale sur `update()`** : le trou déjà documenté dans le code (`storeProofPair()`, `:618`) n'est pas refermé pour les trois champs historiques - seul le nouveau champ `title` reçoit une garde spécifique (2.5), parce que lui seul a un effet de bord dangereux (changement de slug).
- **Suppression de la colonne `description`** : hors périmètre (4.2) - seul son accès en écriture applicative est fermé.
- **Ligne compacte « D'après » au-delà de deux sources** : non-objectif explicite (5.2).
- **Toute modification de `ScreenshotUploadService`, de `Modules/Directory`, ou du composant `screenshot-capture.blade.php`** : hors périmètre du volet B (3.4).

## 11. Découpage en lots livrables

Quatre lots, ordonnés par dépendance réelle (pas seulement par ordre alphabétique des volets) :

```
Lot 1 (fondations) ──► Lot 4 (volet A, l'écran)
Lot 2 (volet D)        - indépendant, déployable à tout moment
Lot 3 (volet B)         - indépendant, déployable à tout moment
```

| Lot | Contenu | Volet(s) | Dépend de | SemVer |
|---|---|---|---|---|
| **1** | `CompositionPayloadNormalizer` (extraction), promotion `attachBySlug`/`detachBySlug`, `NIVEAU_PREUVE_VALUES`, retrait de `description` de `$fillable` | A (fondations) + C (décision 1) | rien | **CORRECTIF** - aucune capacité nouvelle pour quiconque, refactor DRY + durcissement défensif à comportement observable inchangé (preuve : section 8, points 1-2) |
| **2** | Ligne « D'après X et Y » | D | rien | **CORRECTIF** - une fiche à 2 sources primaires existe déjà (voie CLI) ; ce lot corrige un affichage qui tronquait une information déjà présente ailleurs sur la même page (section Sources), il n'ouvre aucune capacité nouvelle |
| **3** | `AdminNewsController::uploadArticleImage()` unifié sur `NewsImageService` | B | rien | **CORRECTIF** (tel que demandé par le mandat) - même capacité (déposer une image), pipeline unifié et plafond aligné sur celui déjà permis ailleurs |
| **4** | Panneau de composition complet (title, résumé structuré, preuves, sources, crédit, nature/niveau de preuve, outils liés), verrou optimiste | A (écran) | **Lot 1** | **MINEUR** - un humain peut désormais faire depuis l'admin ce qu'il ne pouvait faire qu'en CLI : capacité nouvelle claire |

Les lots 2 et 3 peuvent être livrés dans n'importe quel ordre, y compris avant le lot 1, puisqu'ils ne touchent à aucun des fichiers que le lot 1 modifie. Le lot 4 est le seul à dépendre du lot 1 (il consomme `CompositionPayloadNormalizer`, les méthodes promues de `NewsToolSyncAction`, et `NIVEAU_PREUVE_VALUES`) - le livrer avant le lot 1 obligerait à écrire une seconde fois la même normalisation directement dans le contrôleur, exactement la duplication que ce document a pour but d'éliminer. Si un découpage plus fin est souhaité pour le lot 4 (volume important : 8 champs, 2 nouveaux endpoints, verrou optimiste, refonte de vue), il se scinde naturellement en deux sans perdre l'ordre de dépendance : **4a** - champs simples (title, image_credit, nature_original, niveau_preuve) + verrou optimiste + branchement des preuves éditoriales déjà existantes ; **4b** - résumé structuré (composed_summary), sources primaires, outils liés. Les deux resteraient MINEUR (chacun ouvre au moins une capacité nouvelle).

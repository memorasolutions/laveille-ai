# Inventaire des briques dormantes - produit blogues personnels (Modules/Authors)

Date : 2026-09-11. Lecture seule, aucun fichier de code modifié. Complète `docs/specs/2026-09-11-blogues-perso-brief-factuel.md` (tier/paiement/images/gabarits/routage/intégrations/tests, déjà établi, non répété ici) - se concentre sur ce que le brief n'a pas encore couvert : le reste de l'inventaire du module (services, jobs, commandes, composants Livewire, contrôleurs, routes stub) et la mesure en production.

## Contexte chiffré, à lire avant tout le reste

Mesure en production le 2026-09-11 via un script PHP temporaire déposé par `mcp__cpanel__cpanel_file_write`, exécuté une fois par `mcp__cpanel__cpanel_cron_add` (le module Perl `Cpanel::API::Shell` est hors service sur ce compte, `cpanel_terminal` ne répond pas - confirmé indépendamment à deux reprises pendant cette session), lu par `mcp__cpanel__cpanel_file_read`, puis intégralement retiré (cron + script + sortie) et vérifié par une relecture du répertoire. Base interrogée : `gmemora_laveille` (MySQL, connexion `127.0.0.1:3306`), requêtes `DB::table(...)->count()` / `->select(...)->groupBy(...)`.

| Table | Lignes | Détail |
|---|---|---|
| `author_profiles` | **1** | `tier=premium_manual` (1), `accent_color=teal` (1, la valeur par défaut), `font_family=jakarta` (1, la valeur par défaut) |
| `author_posts` | **0** | aucune ligne -> aucun `visibility`/`status` à grouper |
| `author_curation_items` | **0** | |
| `author_push_subscriptions` | **0** | |
| `author_affiliate_links` | **0** | `SUM(clicks_count)` = 0 |
| `article_moderation_logs` | **0** | |
| `author_activity_logs` | **0** | |
| `author_webmentions` | **0** | |
| `author_subscribers` | **0** | |
| `author_image_variants` | **0** | |
| `author_statuses` | **0** | |
| `author_comments` | **0** | |
| `author_post_revisions` | **0** | |
| `scheduled_tasks` (table `Modules\Backoffice\Models\ScheduledTask`) | **0** au total, dont **0** filtré `command LIKE 'authors:%'` | |
| `short_urls` (module ShortUrl réel, sans rapport avec Authors) | 53 | contexte, module actif et distinct |

**Ce que cela signifie** : le produit « blogues personnels » n'a, à ce jour, jamais servi à produire un seul article, un seul commentaire, un seul abonné, une seule image, en production. Le seul profil existant est un profil configuré manuellement (`premium_manual`, jamais via Stripe), sur les valeurs par défaut du gabarit visuel. Ce chiffre n'est pas seulement un constat d'adoption : la section 1 ci-dessous montre que la porte d'entrée elle-même (créer un article) est cassée dans l'écran que l'auteur atteint réellement, ce qui explique une bonne partie du chiffre.

---

## RÉVEILLER (le code fait déjà ce qu'il faut, il manque le branchement)

### 1. L'éditeur d'article n'est atteignable NULLE PART en production - la trouvaille la plus importante

- `AuthorEditor` (`Modules/Authors/app/Livewire/AuthorEditor.php:44` pour `mount(AuthorProfile $authorProfile, ?int $postId = null)`) est un éditeur Markdown complet - auto-save, planification, tags, révisions (`AuthorPostRevision`, réellement utilisé) - couvert par 15 cas de test (`AuthorEditorTest.php`, déjà noté dans le brief).
- Recherche exhaustive de tout rendu du composant (`@livewire('authors.author-editor'` ou balise équivalente) sur `**/*.blade.php` : **deux occurrences seulement**, `Modules/Authors/resources/views/test-editor.blade.php` et sa propre vue `livewire/author-editor.blade.php`. Zéro ailleurs.
- `test-editor.blade.php` n'est servie que par la route `authors.test-editor` (`Modules/Authors/routes/web.php:168-179`), elle-même enfermée dans `if (app()->environment('local'))` (`:162`) - **404 garanti en production**.
- L'écran que l'auteur atteint réellement, `/auteur/dashboard` -> `authors::dashboard` (`Modules/Authors/routes/web.php:142-146`) -> `@livewire('authors.author-dashboard', ...)` (`dashboard.blade.php:26`) -> onglet par défaut `activeTab = 'composer'` (`Modules/Authors/app/Livewire/AuthorDashboard.php:14`), rend ceci (`Modules/Authors/resources/views/livewire/author-dashboard.blade.php:25-36`) :
  ```
  <button type="button" class="...">Nouveau statut court (≤ 280 caractères)</button>
  <button type="button" class="...">Nouvel article long</button>
  ```
  **Aucun des deux boutons ne porte de `wire:click`, de `href` ou de tout autre gestionnaire.** Ce sont des boutons de maquette. L'onglet `articles` (`:37-41`) n'est pas mieux : `<p class="text-gray-500 italic">Liste des articles à brancher sur Modules\Blog\Models\Article::where('user_id', $author->user_id).</p>` - un texte de substitution qui, en plus, cite le MAUVAIS modèle (`Modules\Blog\Models\Article`, pas `AuthorPost`).
- Preuve de production : `author_posts` = 0 ligne (tableau ci-dessus). Cohérent avec un point d'entrée cassé - même en interne, personne n'a pu créer d'article par le chemin prévu.
- **Verdict : RÉVEILLER.** Le correctif est petit et localisé (brancher les deux boutons de l'onglet composer, ou ajouter un onglet/une modale qui monte `@livewire('authors.author-editor', ['authorProfile' => $author])`) ; l'impact est total, puisque c'est la fonctionnalité centrale du produit.

### 2. Le téléverseur d'images n'est rendu nulle part non plus

- `ImageUploader` (`Modules/Authors/app/Livewire/ImageUploader.php`, 60 lignes, déjà lu en entier par le brief) délègue à `ImagePipelineService` un pipeline complet (15 variantes + OG + Twitter Card).
- Recherche exhaustive de `image-uploader` sur tout `**/*.blade.php` : **zéro occurrence**, y compris dans `author-editor.blade.php` lui-même (grep de `upload|image|file` sur ce fichier : zéro résultat en dehors du champ `cover_image`/`profile_image` textuel).
- Conséquence directe et mesurée : `author_image_variants` = 0 ligne en production (tableau ci-dessus), alors que le pipeline lui-même (format, tailles, disque) est opérationnel côté code.
- **Verdict : RÉVEILLER**, mais seulement après le point 1 - sans éditeur atteignable, un téléverseur d'images n'a pas de page qui l'accueille.

### 3. Curation (bookmarklet) - service complet, route stub, aveu explicite dans le code

- `CurationInboxService` (`Modules/Authors/app/Services/CurationInboxService.php`, lu en entier : `saveLink`, `parseOpenGraph` par regex OpenGraph, `search`, `markAsUsed`, `delete`, `importRssFeed` via `SimplePie`) est un service fini.
- Un bookmarklet côté client existe et construit une URL complète (`Modules/Authors/resources/assets/js/bookmarklet.js:5`, minifié) vers `https://laveille.ai/auteur/curation/save?url=...&title=...&description=...&image=...&selection=...&token=...`.
- La route cible ne fait rien : `Route::get('/curation/save', fn () => response()->json(['todo' => true]))->name('authors.curation.save')` (`Modules/Authors/routes/web.php:148-149`).
- L'aveu est écrit dans le code lui-même : `article-builder.blade.php:83` - « (Liste de sources à brancher sur CurationInboxService->search() – Phase 2) » - et `author-dashboard.blade.php:44-45` - « Composant CurationInbox Livewire à brancher (Phase 2). »
- **Piège supplémentaire mesuré** : le modèle `CurationItem` déclare `use HasFactory, SoftDeletes;` (`Modules/Authors/app/Models/CurationItem.php:15`) mais la migration de création (`2026_05_23_120002_create_author_curation_items_table.php`) **n'ajoute jamais de colonne `deleted_at`** - confirmé en production par `SHOW COLUMNS FROM author_curation_items` (14 colonnes, aucune `deleted_at`). Conséquence : `CurationInboxService::delete()` (qui appelle `$item->delete()`, un soft delete) **échouerait avec une erreur SQL** (`Unknown column 'author_curation_items.deleted_at'`) si jamais on le branchait sans corriger d'abord la migration. Erreur réellement observée pendant cet audit (première version du script de mesure, avant correction pour l'éviter).
- **Verdict : RÉVEILLER**, en deux temps obligés : (a) migration additive pour `deleted_at` avant tout branchement, (b) route réelle + composant Livewire minimal appelant `CurationInboxService`.

### 4. Le rappel de réactivation des auteurs inactifs (drip) - job fini, jamais déclenché

- `AuthorActivityCheckJob` (`Modules/Authors/app/Jobs/AuthorActivityCheckJob.php`, lu en entier) implémente une séquence complète : `soft_nudge` (30-60j), `at_risk` (60-90j), `dormant` (90-180j), `final` (180j+), avec garde anti-doublon sur 7 jours (`:34-41`) et 4 gabarits de courriel déjà écrits et présents (`resources/views/mail/reactivation-soft-nudge.blade.php`, `-at-risk.blade.php`, `-dormant.blade.php`, `-final.blade.php`).
- Recherche de dispatch (`AuthorActivityCheckJob::dispatch`, `dispatch(new AuthorActivityCheckJob`) sur tout le dépôt (hors sa propre définition et les tests) : **zéro résultat**.
- `routes/console.php` (206 lignes, lu en entier) : **aucune entrée `authors:`** ni `AuthorActivityCheckJob` dans tout le fichier planificateur.
- Confirmé en production : `scheduled_tasks` (table DB pilotant le planificateur dynamique, `Modules/Backoffice/Models/ScheduledTask`) contient 0 ligne au total, et le crontab cPanel réel du compte (83 entrées, liste complète obtenue par `cpanel_cron_list`) ne contient, pour `laveille.ai`, que `artisan schedule:run` (chaque minute), `artisan news:fetch` (horaire) et deux `artisan queue:work` - rien qui invoque ce job.
- **Verdict : RÉVEILLER.** Correctif d'une ligne dans `routes/console.php` (`Schedule::job(new AuthorActivityCheckJob)->daily();`) - la seule prudence est de le faire après qu'il existe de vrais auteurs actifs, sinon le job ne fait rien d'observable (cohérent avec `author_activity_logs` = 0 ligne).

### 5. Six commandes `authors:*` ne tournent jamais, dont une qui affecte silencieusement les publications programmées

- `authors:health`, `authors:report`, `authors:webmention-send`, `authors:digest`, `authors:publish-scheduled`, `authors:subscriber-digest` (signatures confirmées dans `Modules/Authors/app/Console/Commands/*.php`) : **aucune n'apparaît dans `routes/console.php`, dans le crontab cPanel réel, ni dans la table `scheduled_tasks`** (mêmes preuves que le point 4).
- La plus conséquente est `authors:publish-scheduled` : une migration existe pour ajouter la valeur `scheduled` à l'enum `status` d'`author_posts` (`Modules/Authors/database/migrations/2026_05_25_090000_add_scheduled_to_author_posts_status_enum.php`) et `AuthorEditor` sait écrire ce statut, mais rien ne fait jamais basculer un article `scheduled` vers `published` à l'heure prévue. Impact actuellement nul en pratique (0 article, donc 0 article bloqué en `scheduled`), mais deviendrait un bug de production silencieux dès le premier usage réel de la planification.
- **Verdict : RÉVEILLER** pour `authors:publish-scheduled` (impact direct sur la promesse faite à l'auteur dans l'éditeur) ; RÉVEILLER également pour `authors:health`/`authors:report` (coûts nuls, utilité d'exploitation immédiate) ; à l'inverse `authors:digest`/`authors:subscriber-digest` ne doivent PAS être réactivées sans demande explicite - le projet a une règle permanente contre l'envoi automatique de newsletters sans feu vert (`routes/console.php:102-112`, même doctrine que le reste du site).

---

## ÉTENDRE (fait une partie du travail, le manque est nommé)

### 6. `AuthorPost::visibility` (abonnés/premium) - un sélecteur qui rend le contenu invisible pour tout le monde, y compris l'auteur

- L'éditeur expose réellement le choix à l'auteur : `<select id="ae-visibility" wire:model.live="visibility">` avec `<option value="public">Public</option><option value="subscribers">Abonnés</option><option value="premium">Premium</option>` (`Modules/Authors/resources/views/livewire/author-editor.blade.php:60-64`).
- `PostController::show()` filtre systématiquement `->public()` (`Modules/Authors/app/Http/Controllers/PostController.php:24`, scope défini `Modules/Authors/app/Models/AuthorPost.php:97-99` : `where('visibility', self::VISIBILITY_PUBLIC)`). **Toute valeur autre que `public` renvoie un 404 à n'importe quel visiteur**, y compris les propres abonnés/clients premium de l'auteur - il n'existe aucune vérification d'un statut d'abonnement ou de palier avant de servir un article `subscribers`/`premium`.
- Ce n'est pas une brique morte au sens strict (le champ est écrit ET lu), mais un contrôle d'accès différencié totalement absent : la fonctionnalité affichée (« choisis qui peut lire ») ne fait, en vérité, qu'une seule chose - dépublier silencieusement.
- Mesuré en production : `author_posts` = 0 ligne, donc personne n'est encore tombé dans ce piège - mais rien n'empêche que ce soit le cas au premier usage réel de l'éditeur (point 1 corrigé).
- **Verdict : ÉTENDRE.** Ce qui manque : une vérification, dans `PostController::show()`, du statut du visiteur (abonné confirmé via `AuthorSubscriber`, ou palier premium via Cashier/`tier`) avant d'appliquer le filtre `->public()`, plus une page/état « contenu réservé » au lieu d'un 404 muet.

### 7. Le palier (`tier`) modélise le cycle de vie mais aucune limite concrète n'y est jamais accrochée

- Déjà établi par le brief (section 1) : aucune route de production n'est gardée par `EnsurePremium`, `TierManagementService` n'a aucun appelant, `StripeTipsService::getCommissionRate($tier)` n'est jamais invoqué.
- Ce que cet audit ajoute : **le seul exemple, dans tout le module, d'une table de correspondance palier -> valeur** est justement cette méthode morte (`StripeTipsService::getCommissionRate()`, un `match` sur `free`/`education`/`premium`/`premium_manual` retournant 0,10/0,05/0,00). C'est le bon patron à reprendre (un point unique de correspondance palier -> limite), mais il n'existe nulle part ailleurs pour les quotas d'images, de nombre d'articles, ou d'accès aux intégrations.
- Deux signaux « premium » parallèles et non réconciliés coexistent déjà dans le code : `AuthorProfile::isPremium()` (`Modules/Authors/app/Models/AuthorProfile.php:123-125`, teste `tier` = `premium`/`premium_manual`) et `UpgradeController::show()` qui calcule `$isPremium = $user->subscribed('default')` (`Modules/Authors/app/Http/Controllers/UpgradeController.php:20`, teste l'abonnement Cashier - sans jamais lire `tier`). Un palier `education` (approuvé manuellement, jamais un abonnement Cashier) serait `isPremium() === false` ET `subscribed('default') === false` alors qu'il devrait avoir des droits distincts des deux.
- **Verdict : ÉTENDRE.** Il manque une source unique de vérité pour « qu'est-ce que ce palier permet », voir section finale (famille QUOTAS).

### 8. `AuthorExportService` - deux méthodes sur trois jamais appelées

- `exportJsonFeed()` est réellement utilisée par `MiniSiteController` (`:283`, déjà noté par le brief).
- `exportMarkdown(int $authorProfileId)` (`Modules/Authors/app/Services/AuthorExportService.php:16`) et `exportFullBackup(int $authorProfileId)` (`:69`) : recherche exhaustive de leur nom sur tout le dépôt, hors leur propre fichier -> **zéro appelant**.
- **Verdict : ÉTENDRE.** Ce qui manque : un bouton « exporter mes données » dans l'onglet `parametres`/`historique` du tableau de bord - utile aussi comme réponse toute prête à une demande de portabilité Loi 25/RGPD, un devoir que le site honore déjà ailleurs (`privacy:*` dans `routes/console.php:133-138`).

### 9. La section « now » (statuts courts) - lecture soignée, écriture absente

- `AuthorStatus` (modèle complet, migration `2026_05_23_120007_create_author_statuses_table.php`) est réellement LU par `MiniSiteController` (`:106`, requête sur `author_profile_id`) et affiché avec un état vide gracieux dans `show.blade.php` (`:315` `@if($author->isModuleVisible('now'))`, `:333-336` message de repli « Prépare la prochaine publication »).
- Aucune commande, contrôleur ou composant Livewire n'écrit jamais dans `author_statuses` - recherche exhaustive hors modèle/migration/test : seul `MiniSiteController` (en lecture) y figure. Aucun des 9 onglets du tableau de bord (`composer/articles/curation/builders/subscribers/affiliates/historique/parametres/stats`) n'offre de créer un statut, malgré le bouton de façade « Nouveau statut court (≤ 280 caractères) » de l'onglet composer (point 1).
- Confirmé en production : `author_statuses` = 0 ligne.
- **Verdict : ÉTENDRE**, comme sous-partie du point 1 : le bouton « Nouveau statut court » de l'onglet composer est exactement l'endroit où brancher l'écriture.

### 10. Aucun quota de nombre ou de volume d'images, à aucun palier

- Le brief a déjà établi la limite de taille par fichier (10 Mo) et le filtre de format. L'audit confirme, par grep exhaustif de `quota`, `limit`, `max` (hors taille de fichier) sur `ImageUploader.php`, `ImageBuilder.php`, `ImagePipelineService.php` : rien ne borne le nombre d'images par auteur, ni le volume total de stockage qu'un auteur peut accumuler (15 variantes par image source, indéfiniment). Le mot `tier` n'apparaît dans aucun des trois fichiers.
- **Verdict : ÉTENDRE.** C'est l'angle mort le plus net pour la famille QUOTAS (section finale) : il faudra une limite différenciée par palier, et elle n'a aujourd'hui aucun point d'ancrage côté lecture (uniquement côté écriture, dans `ImagePipelineService::process()`).

---

## RETIRER (intention abandonnée, la garder trompera le prochain)

### 11. `AuthorsController` - scaffold nwidart intact, zéro route, 3 vues sur 4 inexistantes

- `Modules/Authors/app/Http/Controllers/AuthorsController.php` (56 lignes) est le contrôleur CRUD généré par défaut par `nwidart/laravel-modules` (`index/create/store/show/edit/update/destroy`), jamais adapté au produit.
- `index()` rend `authors::index` -> `Modules/Authors/resources/views/index.blade.php` contient littéralement `<h1>Hello World</h1>`.
- `create()`, `show($id)`, `edit($id)` rendent `authors::create`, `authors::show`, `authors::edit` - **ces trois fichiers Blade n'existent pas** (confirmé par un listage du répertoire `resources/views/`, absents de l'inventaire complet du module).
- Recherche exhaustive de `AuthorsController` sur tout le dépôt : la SEULE occurrence hors sa propre définition est le commentaire de `Modules/Authors/routes/api.php:3-5`, qui explique que la route API correspondante a déjà été retirée en 2026-07-23 pour ce même motif (« Scaffold nwidart jamais implémenté »). **Zéro route web ou API ne pointe vers ce contrôleur aujourd'hui.**
- **Verdict : RETIRER.** C'est exactement l'exemple donné par le mandat (« authors.edit est un stub généré par nwidart ») - garder ce fichier à côté d'un vrai système d'auteurs (`AuthorProfile`, `MiniSiteController`, etc.) est le risque de confusion pour la prochaine personne qui chercherait « le contrôleur des auteurs » et tomberait ici par le nom.

### 12. `AuthorShortUrlService` - duplique un module réel et actif, jamais appelé

- `Modules/Authors/app/Services/AuthorShortUrlService.php` (68 lignes, lu en entier) écrit directement dans `DB::table('short_urls')`, en contournant entièrement le modèle ORM `ShortUrl` et le service réel `Modules\ShortUrl\Services\ShortUrlService` (qui, lui, est utilisé en production - 53 lignes mesurées dans `short_urls`).
- `bootValidation()` (`:40-44`) est un corps de méthode VIDE portant seulement un commentaire : « Hook à appeler depuis Modules\ShortUrl\ServiceProvider::boot() ». Vérifié : `Modules/ShortUrl/app/Providers/ShortUrlServiceProvider.php::boot()` (lu en entier) n'appelle jamais ce hook - l'intention documentée dans le commentaire n'a jamais été réalisée.
- Vérification du risque qu'elle était censée prévenir (collision entre un slug court commençant par `@` et les mini-sites `/@{slug}`) : le vrai module `ShortUrl` valide ses slugs par `regex:/^[a-zA-Z0-9_-]+$/` ou `alpha_dash` (`Modules/ShortUrl/app/Http/Controllers/ShortUrlController.php:62`, `UserShortUrlController.php:97`) - **le caractère `@` y est de toute façon impossible**, donc le risque que ce service prétend gérer n'existe pas dans le module réel.
- Recherche exhaustive d'appelants de `AuthorShortUrlService` sur tout le dépôt, hors sa propre définition et le test d'existence de fichier : **zéro**.
- **Verdict : RETIRER.** Aucune perte fonctionnelle (le module `ShortUrl` réel couvre déjà le besoin), et sa présence pourrait laisser croire à un mécanisme de protection du préfixe `@` qui n'existe pas.

### 13. `QrCodeService` (copie Authors) - duplique le service déjà en production ailleurs

- `Modules/Authors/app/Services/QrCodeService.php` (namespace `Modules\Authors\Services`, méthodes `generatePng`, `generatePdfHighRes`, `getPublicUrl`, `cleanupOld`) : recherche exhaustive d'appelants sur tout le dépôt, hors sa propre définition et le test d'existence : **zéro**.
- `Modules\Core\Services\QrCodeService` (fichier distinct) est, lui, déjà utilisé en production par trois modules différents : `Modules/Academy/app/Services/DiplomaRenderService.php:17,35`, `Modules/Decido/app/Http/Controllers/PollManageController.php:714`, `Modules/Tools/app/Http/Controllers/PublicCrosswordController.php:14,216`.
- **Verdict : RETIRER**, au sens DRY nuancé du projet : les deux services encodent la même règle (encoder une URL en QR code image/PDF) et n'ont aucune raison métier d'évoluer séparément - le bon geste, si un QR par profil auteur est souhaité un jour, est d'appeler `Modules\Core\Services\QrCodeService` (déjà éprouvé), pas de réactiver la copie Authors.

### 14. `EmbedsRichService`, `GoogleDriveEmbedService`, `CrossPromotionService`, `MigrationImportService` - écrits, jamais appelés

- `EmbedsRichService` (184 lignes) : `parseSlashCommand`, `renderPoll`, `renderMermaid`, `renderCode`, `renderYoutube`, `renderSpotify`, `renderTwitter`, `renderBluesky`, `renderFigma`, `renderCodepen`, `renderGithub`, `renderInstagram`, `renderTiktok`. C'est visiblement l'implémentation du système de « slash commands » décrit dans le TODO laissé dans `AuthorPost.php:128-135` (« Slash commands : /image /quote /code /embed /toc /poll /tip-button », TODO datant de la période S107, jamais mis à jour malgré la construction effective d'`AuthorEditor`). Aucun appelant trouvé ; `AuthorEditor` utilise à la place le plus simple `OembedService` (`detect/toEmbedHtml/transformMarkdown`, `Modules/Authors/app/Services/OembedService.php:18,37,58` - réellement appelé depuis `AuthorEditor.php`).
- `GoogleDriveEmbedService` (156 lignes : `parse`, `renderIframe`, `isValidGoogleUrl`, `extractIdFromUrl`, `getFallbackHtml`) : zéro appelant.
- `CrossPromotionService` (73 lignes : `getRecommendationsForArticle`, `getRecommendationsForAuthor`, `recordRecommendationClick`) : zéro appelant. Distinct d'`AuthorRelatedPosts` (Livewire, lui réellement rendu dans `post.blade.php`, qui suggère des articles du même auteur par tags communs) - `CrossPromotionService` visait la recommandation inter-auteurs, jamais construite.
- `MigrationImportService` (130 lignes : `importFromRssUrl`, `previewRssFeed`, `getSupportedPlatforms`) : import d'un ancien blogue (`WordPress`, `Medium.com`, `Substack`) via RSS pour l'accueil d'un nouvel auteur. Zéro appelant, zéro UI.
- **Verdict : RETIRER** pour les quatre. Ce sont des ambitions écrites d'avance (souvent visibles dans la liste des « 13 services » du test de structure, `AuthorsModuleStructureTest.php:12-28`) qui n'ont jamais rencontré d'écran. Les garder côte à côte avec `OembedService`/`AuthorRelatedPosts` (les versions qui, elles, fonctionnent) est le piège exact que le mandat signale : le prochain qui voudra « ajouter un embed Google Slides » ou « des recommandations entre auteurs » risque d'écrire une troisième version sans savoir que celle-ci existe déjà, morte.

### 15. `WebPushService` + `AuthorPushSubscription` - une intégration qui simule son propre fonctionnement

- `WebPushService::send()` (`Modules/Authors/app/Services/WebPushService.php:54-77`) n'envoie rien : `Log::channel('daily')->info('webpush.send.stub', [...])` avec, en commentaire de code juste au-dessus (`:73`), l'aveu verbatim laissé par son auteur : `// Silent fail MVP — real send via minteractive/web-push S115+`. C'est un aveu écrit que l'implémentation réelle n'a jamais suivi.
- Aucune route, aucun JavaScript côté client, aucun composant Livewire n'appelle `subscribe()` - recherche exhaustive : les seuls appelants de `WebPushService`/`AuthorPushSubscription` sont le fichier lui-même et `S114EnhancementsTest.php`, qui teste le service directement (`new WebPushService()`), jamais via une route HTTP.
- Confirmé en production : `author_push_subscriptions` = 0 ligne.
- **Verdict : RETIRER.** Contrairement aux autres entrées de cette section, celle-ci n'est pas seulement non branchée : même branchée, elle ne ferait rien de réel (le cœur de l'envoi est un stub). La reconstruire depuis zéro (VAPID, service worker, librairie d'envoi) le jour où le besoin est confirmé coûterait moins cher que corriger cette version.

### 16. Trois composants Livewire construits, jamais rendus - et un quatrième déjà remplacé en silence

- `AuthorAnalyticsWidget` (102 lignes) : recherche exhaustive de `author-analytics-widget` sur tout `**/*.blade.php` : **zéro occurrence**. L'onglet `stats` du tableau de bord (`author-dashboard.blade.php:124-160`) appelle directement `app(\Modules\Authors\Services\AnalyticsRecommendationService::class)->getCachedInsights($author->id)` dans un `@php`, en contournant entièrement ce composant.
- `AuthorRecentNotifications` (82 lignes) : recherche exhaustive : **zéro occurrence** dans les vues.
- `AuthorSearch` (56 lignes, `mount(int $authorProfileId)` à la ligne 18) : recherche exhaustive : **zéro occurrence**. La recherche réellement en service passe par un simple formulaire HTML `<form method="GET">` (`Modules/Authors/resources/views/components/search-header-toggle.blade.php:33`, soumis vers `route('authors.mini-site.show', ...)` avec `name="q"`) que `MiniSiteController::show()` traite directement (`$searchQuery = trim((string) $request->query('q', ''))`, `Modules/Authors/app/Http/Controllers/MiniSiteController.php:118`). Le composant Livewire a été conçu pour le même besoin puis, de fait, remplacé par cette solution plus simple - sans jamais être retiré.
- **Verdict : RETIRER** pour les trois. Le cas `AuthorSearch` est le plus net : deux implémentations du même besoin coexistent, une vivante (formulaire GET) et une morte (Livewire) - les garder ensemble fait croire, à tort, qu'il existe un choix à faire entre les deux.

### 17. Trois routes stub qui répondent `{"todo": true}` sans jamais rien faire

- `GET /auteur/curation/save` (`web.php:148-149`, voir point 3).
- `GET /auteur/moderation/{article}/approve|depublish|ban` (`web.php:184-191`, sous groupe `signed`) : recherche exhaustive de `authors.moderation.` sur tout le dépôt -> les seules occurrences sont leur propre déclaration. Or un flux de modération réel et fonctionnel existe déjà, ailleurs : `ModerationAlertMail` (`Modules/Authors/app/Mail/ModerationAlertMail.php`) envoie un courriel dont les boutons pointent vers `/admin/articles/{id}?action=approve|depublish` et `/admin/users/{id}?action=ban` (`Modules/Authors/resources/views/mail/moderation-alert.blade.php:33-35`) - un chemin hors du module Authors, distinct des trois routes signées `authors.moderation.*` qui, elles, ne sont liées à aucun courriel ni aucune UI.
- **Verdict : RETIRER** pour les trois routes `authors.moderation.*` : elles ne sont pas une version incomplète du vrai flux, elles sont un doublon jamais branché du vrai flux, qui vit déjà ailleurs et fonctionne (au sens où `ModerationPipelineService` l'alimente - même si, comme établi par ailleurs dans cet audit, `ScanArticleJob` lui-même n'est jamais mis en file nulle part non plus, ce qui est un problème distinct hors du périmètre Authors puisqu'il opère sur `Modules\Blog\Models\Article`, pas `AuthorPost`).

### 18. Rappel du brief (non répété en détail) - verdict RETIRER déjà établi

`isPremium()`/`isEducation()`/`scopeFree()`/`scopePremium()` sur `AuthorProfile` (`Modules/Authors/app/Models/AuthorProfile.php:108-121,123-130`, lignes reconfirmées ici), middleware `EnsurePremium`, `StripeTipsService::getCommissionRate/getTransparencyTable`, colonnes `accent_color`/`font_family` dans leur état actuel (voir section finale - pas mortes de la même façon, elles ont un avenir dans la famille GABARITS).

---

## Réponse à la question qui compte le plus - gabarits, quotas, intégrations

### GABARITS visuels

**Bon point d'ancrage** : les colonnes `accent_color` (8 valeurs, `AuthorProfile.php:27`) et `font_family` (3 valeurs, `:28`) existent déjà à la bonne granularité (une valeur par profil, pas une constante globale) et sont déjà `fillable`. C'est la bonne place pour un thème de couleurs/typographie.

**Le piège à éviter** : les confondre avec un système de « gabarits » au sens de mises en page différentes. Aujourd'hui, `show.blade.php`, `post.blade.php` et `tag-archive.blade.php` sont chacune un document HTML autonome avec son propre `<style>` inline et des couleurs codées en dur (`#064E5A`, déjà noté par le brief section 4) - **pas des composants**. Poser un sélecteur de gabarit par-dessus ces trois fichiers tels qu'ils sont aujourd'hui reviendrait à coder un choix que rien, structurellement, ne peut appliquer (un `<select>` de plus qui ne changerait rien, exactement le même défaut que `accent_color`/`font_family` aujourd'hui). Le travail préalable réel est de sortir les styles des vues en variables/composants Blade partagés avant de brancher un choix dessus - alors `accent_color`/`font_family` redeviennent le bon point d'ancrage pour piloter ces variables.
`modules_visible` (JSON, déjà fonctionnel - point unique de personnalisation réellement écrit en production sur le profil existant) reste un levier différent (quelles sections apparaissent) et ne doit pas fusionner avec le thème visuel : ce sont deux règles métier distinctes qui évolueront pour des raisons différentes (DRY nuancé du projet).

### QUOTAS par palier

**Bon point d'ancrage** : la colonne `tier` (`AuthorProfile.php`, déjà la source unique pour « quel forfait »), et le patron (pas le code lui-même, qui est mort) de `StripeTipsService::getCommissionRate($tier)` - un `match` centralisé palier -> valeur. C'est exactement la forme à reprendre pour des quotas d'images, de nombre d'articles, d'accès aux intégrations : un point unique (`TierManagementService` étendu, ou un service jumeau) que chaque consommateur (`ImagePipelineService`, un futur contrôle de nombre d'articles, etc.) interroge, plutôt que chacun sa propre constante.

**Le piège à éviter, déjà mesuré dans le code existant** : traiter le palier comme un booléen. `UpgradeController::show()` (`:20`) calcule `$isPremium = $user->subscribed('default')` - un booléen tiré de l'abonnement Cashier, sans jamais lire la colonne `tier` à 4 valeurs. `AuthorProfile::isPremium()` (`:123-125`) fait l'inverse : il lit `tier` mais ignore Cashier. **Deux signaux « premium » déjà parallèles et non réconciliés existent** avant même qu'un seul quota soit construit. Bâtir des quotas sur l'un des deux sans les unifier d'abord garantirait qu'un palier `education` (jamais un abonnement Cashier, toujours approuvé à la main via `TierManagementService::approveEducation()` - lui-même jamais appelé) reste incohérent dès le premier jour. Le mandat le nomme explicitement : « une brique qui traite le palier comme un booléen alors qu'il faudra trois paliers » - c'est déjà arrivé, deux fois, avant même la première fonctionnalité de quota.
Deuxième piège : coder la limite en dur dans chaque service consommateur (comme le fait déjà `ImagePipelineService::process()` avec son `10 * 1024 * 1024` fixe, section 3 du brief) plutôt que dans une table/config lue au même endroit que `tier` - exactement le risque nommé par le mandat (« une qui code un comportement en dur là où il faudra une table »).

### INTÉGRATIONS tierces

**Bon point d'ancrage** : le contrat `NewsletterProvider` (`Modules/Authors/app/Contracts/NewsletterProvider.php`) + son implémentation `BrevoProvider` (`Modules/Authors/app/Services/Newsletter/BrevoProvider.php`, déjà notés par le brief comme jamais liés au conteneur de services). C'est la seule fois, dans tout le module, qu'une intégration tierce a été pensée interface d'abord. Le bon geste pour la prochaine intégration (un futur fournisseur d'infolettre, un futur réseau social) est de reprendre cette forme - définir le contrat, puis brancher - plutôt que d'écrire un service fermé comme les suivants.

**Le piège à éviter, déjà visible trois fois** : `EmbedsRichService`, `GoogleDriveEmbedService` et `OembedService` sont trois services d'« intégration d'un contenu tiers dans un article », sans aucun contrat commun. Un seul des trois (`OembedService`) est réellement branché à `AuthorEditor` ; les deux autres sont des impasses complètes (section RETIRER, point 14). Ajouter une quatrième intégration (Notion, Airtable, Canva...) en écrivant un quatrième service isolé reproduirait exactement ce schéma. Le bon geste - au sens du DRY nuancé du projet, puisque les trois encodent réellement la même règle métier (« transformer une URL externe en balisage intégré ») et devraient évoluer ensemble - est de définir un contrat `EmbedProvider` unique (sur le modèle de `NewsletterProvider`) et d'y accrocher `Oembed`/`GoogleDrive`/tout futur fournisseur comme des implémentations enregistrées, plutôt que des services indépendants qui s'accumulent.
Le piège le plus trompeur reste `WebPushService` (section RETIRER, point 15) : il a exactement la forme d'une intégration terminée (modèle + migration + service + tests unitaires), mais son cœur (`send()`) est un stub qui ne fait que journaliser. Une intégration tierce future construite en copiant sa forme reproduirait une intégration qui semble finie sans jamais l'être - le risque que ce document existe pour signaler.

---

## Ce qui n'a pas été vérifié

- **La table `scheduled_tasks`** a été confirmée vide (0 ligne) mais le code du modèle `Modules\Backoffice\Models\ScheduledTask` et de son exécution n'a pas été audité en détail (hors périmètre Authors) - seule la question « une commande `authors:*` y est-elle enregistrée » a été posée, et la réponse est non.
- **`Modules\Core\Services\QrCodeService`** n'a pas été lu en entier (seule sa liste d'appelants a été vérifiée) : l'affirmation de duplication porte sur le périmètre fonctionnel (encoder une URL en image/PDF QR), pas sur une comparaison ligne à ligne des deux implémentations.
- **`ModerationPipelineService`/`ScanArticleJob`**, bien que vivant dans `Modules/Authors`, opèrent sur `Modules\Blog\Models\Article` et non sur `AuthorPost` - leur non-déclenchement (confirmé : zéro dispatch trouvé, `article_moderation_logs` = 0 ligne) touche potentiellement le blogue principal du site, hors périmètre strict du mandat « blogues personnels » ; signalé ici sans audit complet du côté Blog.
- **Le détail complet de `Modules\ShortUrl`** (au-delà de la règle de validation du slug) n'a pas été audité - seule la règle empêchant la collision avec `@{slug}` a été vérifiée.
- **`mcp__cpanel__cpanel_terminal`** est resté indisponible pendant tout l'audit (module Perl serveur manquant, confirmé à deux reprises indépendamment) ; la mesure de production a été obtenue par un contournement (script déposé + cron ponctuel + lecture + nettoyage complet vérifié), qui donne les mêmes garanties de lecture seule mais qui est plus lent - à retenir pour tout futur audit sur ce compte.

# Brief factuel - Modules/Authors (blogues personnels laveille.ai)

Date : 2026-09-11. Portée : uniquement ce qui EXISTE dans le code, avec ancrage `fichier:ligne` pour chaque affirmation. Aucune valeur de `.env` lue. Aucune commande destructive exécutée.

## 1. Le champ `tier` d'AuthorProfile

### Valeurs possibles

- Colonne `tier` : `enum('free', 'education', 'premium', 'premium_manual')`, défaut `free`, indexée. `Modules/Authors/database/migrations/2026_05_23_120001_create_author_profiles_table.php:25` et `:36`.
- `tier_expires_at` (timestamp nullable) accompagne le champ. `Modules/Authors/database/migrations/2026_05_23_120001_create_author_profiles_table.php:26`.
- Cast en modèle : `tier_expires_at` => `datetime`. `Modules/Authors/app/Models/AuthorProfile.php:44`. Le champ `tier` lui-même n'a AUCUN cast (reste une simple string).
- `tier` est `fillable`. `Modules/Authors/app/Models/AuthorProfile.php:29`.

### Où `tier` est LU (grep exhaustif hors tests) et effet réel

| Lecture | Ancrage | Effet réel |
|---|---|---|
| `scopeFree` / `scopePremium` | `Modules/Authors/app/Models/AuthorProfile.php:113-121` | Scopes de l'ORM `Eloquent` définis, mais NON TROUVÉ après recherche de `::premium()` / `::free()` / `->premium()` / `->free()` appelés ailleurs dans le module ou l'app - scopes jamais invoqués. |
| `isPremium()` / `isEducation()` | `Modules/Authors/app/Models/AuthorProfile.php:123-131` | NON TROUVÉ après recherche de `isPremium(` / `isEducation(` ailleurs que leur propre définition, dans tout le dépôt (hors `vendor/`). Méthodes mortes : définies, jamais appelées. |
| `AllAuthorsViewer::$tierFilter` | `Modules/Authors/app/Livewire/AllAuthorsViewer.php:21,47-49` | Filtre d'affichage de la liste admin `backoffice/authors` (colonne lecture seule, `Modules/Authors/resources/views/livewire/all-authors-viewer.blade.php:44`). Aucune action pour CHANGER le tier depuis cet écran. |
| `PublicAuthorResource` | `Modules/Authors/app/Http/Resources/PublicAuthorResource.php:20` | Exposé tel quel dans l'API JSON publique `GET /api/v1/author-profiles/{slug}`. Lecture seule, aucune conséquence d'accès. |
| `StripeWebhookController::syncTier()` | `Modules/Authors/app/Http/Controllers/StripeWebhookController.php:94-115` | ÉCRIT `tier` (pas une lecture) en réaction aux webhooks Stripe Cashier (`customer.subscription.created/updated/deleted`), en cherchant le `User` par `stripe_id` puis en mettant à jour `AuthorProfile::tier`. C'est la SEULE écriture automatique en production. |
| `TierManagementService` | `Modules/Authors/app/Services/TierManagementService.php` (entier) | Service complet (`setTier`, `approveEducation`, `revokeEducation`, `expireExpiredTiers`, `getAuditLog`) qui LIT et ÉCRIT `tier`. NON TROUVÉ après recherche de son instanciation (`TierManagementService::class`, `app(TierManagementService`, injection de constructeur) ailleurs que dans sa propre définition et dans `Modules/Authors/tests/Feature/AuthorsModuleStructureTest.php:25` (qui vérifie seulement que le FICHIER existe). Aucune commande console, aucun contrôleur, aucun Livewire ne l'appelle. Service mort. |
| `StripeTipsService::getCommissionRate($tier)` / `getTransparencyTable($tier)` | `Modules/Authors/app/Services/StripeTipsService.php:56-89` | Calcule un taux de commission par palier (0,10 / 0,05 / 0,00). NON TROUVÉ après recherche de `getCommissionRate` / `getTransparencyTable` appelés ailleurs dans tout le dépôt (hors `vendor/`), y compris dans les vues. Code mort. |
| `EnsurePremium` (middleware) | `Modules/Authors/app/Http/Middleware/EnsurePremium.php:11-27` | Ne lit PAS `AuthorProfile->tier` : il lit `$user->subscribed('default')` (état d'abonnement Cashier). NON TROUVÉ après recherche de `EnsurePremium::class` sur une route réelle - grep exhaustif sur `Modules/Authors/routes/web.php` et `routes/*.php` : zéro occurrence. La SEULE occurrence hors sa propre classe est `Modules/Authors/tests/Feature/PremiumTest.php:47`, qui l'enregistre lui-même sur une route de test temporaire. **Aucune route de production n'est protégée par ce middleware.** |
| `AuthorsReportCommand` | `Modules/Authors/app/Console/Commands/AuthorsReportCommand.php:59` | Affiche `$author->tier` dans un rapport console (lecture d'affichage seulement). |

### Conclusion nette

**Le palier `tier` n'a aujourd'hui AUCUN effet observable par un auteur.** Preuve par élimination :
1. Aucune route de production n'est protégée par `EnsurePremium` (le seul middleware qui teste un statut premium teste `subscribed()`, pas `tier`, et n'est monté nulle part - `Modules/Authors/routes/web.php` en entier, grep `EnsurePremium` : 0 résultat).
2. Les méthodes `isPremium()`/`isEducation()` du modèle ne sont appelées par aucun contrôleur, Livewire ou vue.
3. Le service dédié à la gestion des paliers (`TierManagementService`) n'est câblé nulle part - aucune UI admin, aucune commande planifiée n'invoque `expireExpiredTiers()`, donc un palier `education`/`premium_manual` expiré (`tier_expires_at` dépassé) reste tel quel indéfiniment en pratique.
4. Le calcul de commission différenciée par palier (`StripeTipsService`) n'est appelé par aucun flux de paiement réel (`AuthorTipsService::createCheckout()`, le flux RÉELLEMENT utilisé pour les pourboires, ne fait AUCUNE référence à `tier` ni à `StripeTipsService` - `Modules/Authors/app/Services/AuthorTipsService.php:1-52`).
5. Le seul effet OBSERVABLE de `tier` est indirect et à sens unique : un webhook Stripe peut l'ÉCRIRE (`StripeWebhookController::syncTier()`), et un administrateur peut le voir/filtrer dans le tableau `backoffice/authors`. C'est un champ de journalisation/affichage, pas un levier de contrôle d'accès.

## 2. Paiement

### `UpgradeController.php` (lu en entier - 47 lignes)

- `show()` (`Modules/Authors/app/Http/Controllers/UpgradeController.php:14-23`) : retrouve le `AuthorProfile` de l'utilisateur connecté, calcule `$isPremium = method_exists($user, 'subscribed') && $user->subscribed('default')`, rend `authors::upgrade`. Aucune écriture.
- `checkout()` (`:25-38`) : lit `config('cashier.price_premium')` ; si vide, `abort(503, ...)`. Sinon crée une session Stripe Checkout via `$user->newSubscription('default', $priceId)->checkout([...])` (méthode Cashier). URLs de succès/annulation pointent vers `route('authors.upgrade')`.
- `billingPortal()` (`:40-46`) : redirige vers le portail de facturation Stripe via `$user->redirectToBillingPortal(...)` (méthode Cashier).
- **Aucune des 3 méthodes n'écrit `AuthorProfile->tier`.** Le passage à `tier = 'premium'` se fait uniquement via le webhook (section 1).

### Laravel Cashier

- Installé : `"laravel/cashier": "^16.2"` dans `composer.json:27`.
- Modèle `Billable` : `App\Models\User`. Trait importé `Modules_racine/app/Models/User.php:20` (`use Laravel\Cashier\Billable;`) et utilisé dans la déclaration de classe `app/Models/User.php:41` (`use Billable, HasApiTokens, ... ;`).
- Contrôleur webhook Cashier surchargé : `StripeWebhookController extends \Laravel\Cashier\Http\Controllers\WebhookController` (`Modules/Authors/app/Http/Controllers/StripeWebhookController.php:12`), qui ajoute la synchronisation de `tier` sur 3 événements d'abonnement + gère les webhooks `checkout.session.completed` de type pourboire (`tip_type === 'one-time'`).

### Clés de configuration Stripe attendues (aucune valeur lue)

- `cashier.price_premium`, résolue dans `config/cashier.php:39` par `env('LV_STRIPE_PRICE_PREMIUM')`. Consommée dans `UpgradeController.php:30`.
- `cashier.secret`, consommée à 5 endroits pour savoir si Stripe est configuré (jamais la valeur elle-même) : `Modules/Authors/app/Services/AuthorTipsService.php:50`, `Modules/Authors/app/Console/Commands/AuthorsHealthCommand.php:32`, `Modules/Authors/resources/views/mini-site/show.blade.php:429`, `Modules/Authors/resources/views/mini-site/post.blade.php:88` et `:96`.
- `services.stripe.secret`, consommée dans `Modules/Authors/app/Services/StripeTipsService.php:17-18` (service qui, rappel section 1, n'est appelé par aucun flux réel).
- Aucun identifiant Stripe `price_xxx` en dur trouvé dans le code (grep `'price_` / `"price_` exhaustif : seule occurrence = la clé de config elle-même).

### Pourboires (tips) - flux réellement câblé

- Route publique `POST /auteur/{slug}/tip` -> `MiniSiteController::tip()` (`Modules/Authors/routes/web.php:55-58`, `Modules/Authors/app/Http/Controllers/MiniSiteController.php:362-390`). Montant borné entre 100 et 50000 centes (`:374`). Appelle `AuthorTipsService::createCheckout()`.
- `AuthorTipsService::createCheckout()` (`Modules/Authors/app/Services/AuthorTipsService.php:14-46`) utilise `$user->checkoutCharge(...)`, une méthode du trait `Billable` de Cashier (paiement ponctuel, pas un abonnement). Aucune commission différenciée par palier n'y est appliquée (contrairement à `StripeTipsService`, qui reste non branché).
- `StripeWebhookController::handleCheckoutSessionCompleted()` (`:40-92`) capte le paiement, journalise (`Log::channel('daily')->info('tips.received', ...)`, `:67-72`) et envoie `TipReceivedNotificationMail` à l'auteur si son courriel est connu (`:74-89`). **Aucun modèle en base ne stocke l'historique des pourboires reçus** - NON TROUVÉ après recherche d'une table/migration `tips`/`author_tips` ; seule trace = le log applicatif et le courriel envoyé.

## 3. Images et quotas

### `ImageUploader.php` (Livewire, lu en entier - 60 lignes)

- Composant mince : `mount()` vérifie la propriété du profil (`abort_if($profile->user_id !== auth()->id(), 403)`, `Modules/Authors/app/Livewire/ImageUploader.php:26-34`), `process()` délègue TOUT le traitement à `ImagePipelineService::process()` (`:46-50`). Aucune limite codée dans ce fichier lui-même.

### `ImageBuilder.php` (Livewire, lu en entier - 46 lignes)

- **Ce n'est PAS un uploader ni un générateur d'image.** C'est un compositeur de PROMPT texte pour IA externe : `generate()` assemble les champs (`subject`, `style`, `composition`, `colors`, `mood`, `usage`, `targetAi`, `aspectRatio`, `quality`) et appelle `ImagePromptBuilderService::build($params)` (`Modules/Authors/app/Livewire/ImageBuilder.php:25-40`), qui retourne un texte de prompt destiné à être collé dans DALL-E/Midjourney/etc. (`targetAi` par défaut = `dalle3`, `:17`). Aucun fichier n'est produit ni envoyé par ce composant.

### Limites codées (dans `ImagePipelineService::process()`)

- Taille max du fichier source : 10 Mo (`if ($fileSize === false || $fileSize > 10 * 1024 * 1024) { throw new Exception("File too large (max 10MB)"); }`, `Modules/Authors/app/Services/ImagePipelineService.php:41-44`).
- Formats acceptés : `image/jpeg`, `image/png`, `image/webp`, `image/heic` uniquement (`:46-49`).
- Aucune limite de NOMBRE d'images par auteur ou par article : NON TROUVÉ après recherche de `quota`, `limit`, `max` (hors la taille de fichier ci-dessus) dans `ImageUploader.php`, `ImageBuilder.php` et `ImagePipelineService.php`.
- Aucune limite différenciée par `tier` : le mot `tier` n'apparaît dans AUCUN des 3 fichiers (grep confirmé).

### Sortie du pipeline

- 5 largeurs générées par image source : 320/640/1024/1920/2400px, chacune en 3 formats (avif q50, webp q85, jpg q90) = 15 variantes par image, plus une image Open Graph 1200x630 et une image Twitter Card 1200x600 (`Modules/Authors/app/Services/ImagePipelineService.php:84-172`).
- Chaque variante est enregistrée en base dans `ImageVariant` (table `author_image_variants`, colonnes : `format`, `size_width`, `size_height`, `file_size_bytes`, `variant_path`, `is_open_graph`, `is_twitter_card`, `alt_text` - `Modules/Authors/database/migrations/2026_05_23_120004_create_author_image_variants_table.php:13-31`).
- Stockage : disque Laravel nommé `'public'` (`private string $disk = 'public';`, `Modules/Authors/app/Services/ImagePipelineService.php:23`), chemin `authors/{authorProfileId}/{hash-aleatoire-16-car}/...` (`:52`). URL publique via `Storage::disk($this->disk)->url($path)`.
- `suggestAltText()` (`:174-210`) envoie l'image en base64 à OpenRouter (`google/gemini-2.0-flash-exp:free`) pour générer un texte alternatif en français - fonctionnalité annexe, pas un quota.

## 4. Gabarits / thèmes

### Vues sous `mini-site/`

- `show.blade.php` (page auteur, `/@{slug}`)
- `post.blade.php` (article, `/@{slug}/{postSlug}`)
- `tag-archive.blade.php` (archive par tag, `/@{slug}/tag/{tag}`)
- `newsletter-confirmed.blade.php`, `newsletter-unsubscribed.blade.php` (pages de confirmation)

### Une seule mise en page, pas de thème sélectionnable

- Chaque vue publique (`show.blade.php`, `post.blade.php`, `tag-archive.blade.php`) déclare SON PROPRE `<!DOCTYPE html><html>...` : `Modules/Authors/resources/views/mini-site/show.blade.php:1-2`, `Modules/Authors/resources/views/mini-site/post.blade.php:1-2`, `Modules/Authors/resources/views/mini-site/tag-archive.blade.php:1-2`. Un commentaire du code le confirme explicitement : « est un document autonome (elle ouvre son propre `<!DOCTYPE html>`), elle n'étend [pas de layout] » (`Modules/Authors/resources/views/mini-site/show.blade.php:23`).
- Le composant `components/layouts/master.blade.php` existe mais n'est utilisé QUE par `index.blade.php` (page d'atterrissage/listing du module, pas les mini-sites) - grep exhaustif `layouts.master|layouts::master` sur tout `Modules/Authors/resources/views` : une seule occurrence, hors des vues `mini-site/`.
- **Aucune notion de thème/gabarit sélectionnable n'existe.** Confirmation supplémentaire : les colonnes `accent_color` (8 valeurs enum : teal, indigo, rose, amber, emerald, violet, sky, fuchsia - `Modules/Authors/database/migrations/2026_05_23_120001_create_author_profiles_table.php:21-23`) et `font_family` (3 valeurs : jakarta, inter, merriweather - `:24`) existent dans la table et le modèle (`Modules/Authors/app/Models/AuthorProfile.php:27-28`, listées en `fillable`), mais grep exhaustif de `accent_color` et `font_family` dans TOUT le module (`.php` + `.blade.php`) ne retourne AUCUNE occurrence hors migration et modèle. **Ces deux colonnes sont mortes : capturables en théorie, jamais lues par aucune vue.** La mise en page réelle utilise un jeu de couleurs fixe codé en dur dans le CSS inline de `show.blade.php` (ex. `#064E5A`, grep `meta name="theme-color" content="#064E5A"` à `Modules/Authors/resources/views/mini-site/show.blade.php:29`), indépendant de `accent_color`.
- Le seul mécanisme de bascule visuelle qui EXISTE est clair/sombre (`data-theme`), géré côté client par `localStorage`, pas par un choix d'auteur en base : `Modules/Authors/resources/views/mini-site/show.blade.php:502-515`.

### CSS personnalisé injectable par un auteur

- NON TROUVÉ après recherche de `custom_css` dans tout `Modules/Authors` (`.php` et `.blade.php`) : zéro occurrence.
- Les balises `<style>` présentes dans les vues (`show.blade.php:52,361,468`, `post.blade.php:38,53,135,268`, `tag-archive.blade.php:15`, `newsletter-confirmed.blade.php:9`, `newsletter-unsubscribed.blade.php:9`) sont toutes du CSS STATIQUE écrit par l'équipe, pas un champ modifiable par l'auteur.
- Le seul levier de personnalisation réellement câblé et fonctionnel pour un auteur est `modules_visible` (JSON, colonne ajoutée par migration ultérieure `Modules/Authors/database/migrations/2026_05_23_120008_add_modules_visible_to_author_profiles.php:14`) : une liste de 8 sections activables/désactivables (`now`, `newsletter`, `stats`, `about`, `featured`, `recent_articles`, `social_links`, `qualifications_list` - `Modules/Authors/app/Models/AuthorProfile.php:50-59`), gérée par le composant Livewire `AuthorSettings` (`Modules/Authors/app/Livewire/AuthorSettings.php:34-48`) qui ÉCRIT réellement en base (`$author->modules_visible = $this->modulesVisible; $author->save();`, `:42-44`). C'est de la visibilité de blocs, pas de la personnalisation visuelle (couleurs/police/CSS).

### Colonnes de la table `author_profiles` (migrations lues en entier)

Migration de création `Modules/Authors/database/migrations/2026_05_23_120001_create_author_profiles_table.php:13-39` + ajout ultérieur `2026_05_23_120008_add_modules_visible_to_author_profiles.php:14` :

| Colonne | Type | Rôle apparent |
|---|---|---|
| `id` | bigint PK | Identifiant |
| `user_id` | FK -> users, cascade delete | Lien vers le compte utilisateur |
| `slug` | string(80) unique | Identifiant d'URL (`/@{slug}`) |
| `cover_image` | string nullable | Image de couverture (chemin) |
| `profile_image` | string nullable | Avatar (chemin) |
| `bio` | text nullable | Biographie courte |
| `manifesto` | text nullable | Texte long ("manifeste") |
| `accent_color` | enum(8 valeurs) défaut `teal` | **Colonne morte** (section 4) |
| `font_family` | enum(3 valeurs) défaut `jakarta` | **Colonne morte** (section 4) |
| `tier` | enum(4 valeurs) défaut `free` | Palier, effet nul (section 1) |
| `tier_expires_at` | timestamp nullable | Expiration du palier, jamais purgée automatiquement (section 1) |
| `education_approved_at` | timestamp nullable | Date d'approbation palier `education` |
| `education_approved_by` | FK -> users, nullable, set null | Admin ayant approuvé |
| `social_links` | json nullable | Liens réseaux sociaux (cast `array`) |
| `qualifications` | json nullable | Liste de qualifications (cast `array`) |
| `modules_visible` | json nullable, ajoutée après-coup | Bascules d'affichage de sections (seule personnalisation active, voir ci-dessus) |
| `last_published_at` | timestamp nullable | Dernière publication, sert au bandeau d'abandon (section 5) |
| `archived_at` | timestamp nullable | Marque un profil archivé (soft-état, distinct de `SoftDeletes`) |
| `created_at`/`updated_at` | timestamps | Horodatage standard |
| `deleted_at` | (via `SoftDeletes`) | Suppression douce |

Indices : `tier`, `last_published_at`, `archived_at` (`:36-38`).

## 5. Routage et domaine

### `/@{slug}` (`Modules/Authors/routes/web.php`)

- `GET /@{slug}` -> `MiniSiteController::show` (page profil), contrainte regex `[a-z0-9-]+`, middleware `CacheAuthorMiniSite` (`:87-90`).
- `GET /@{slug}/feed.xml` -> `MiniSiteController::rss` (`:92-94`).
- `GET /@{slug}/feed.json` -> `MiniSiteController::jsonFeed` (`:96-98`).
- `GET /@{slug}/tag/{tag}` -> `TagArchiveController::show`, DOIT être déclarée avant la route catch-all `{postSlug}` pour la priorité regex (commentaire explicite, `:100-103`).
- `GET /@{slug}/preview/{postSlug}` -> `DraftPreviewController::show`, protégée par middleware `signed` (`:105-109`).
- `GET /@{slug}/{postSlug}` -> `PostController::show` (article public), DOIT venir après les routes précédentes (`:111-115`).
- Toutes les routes `/@{slug}...` sont déclarées SANS groupe de domaine : elles répondent sur le domaine principal de l'application (celui que Laravel sert par défaut), pas sur un sous-domaine dédié.

### Domaine personnalisé

- NON TROUVÉ après recherche de `custom_domain` comme COLONNE ou champ fonctionnel : la SEULE occurrence du terme dans tout le module est une valeur d'enum `event_type` dans la table de journalisation `author_activity_logs` : `'custom_domain_configured'` (`Modules/Authors/database/migrations/2026_05_23_120006_create_author_activity_logs_table.php:24`). Grep exhaustif de cette chaîne exacte dans tout le module : **une seule occurrence, celle de sa propre déclaration d'enum** - aucun code n'écrit jamais un log de ce type, aucune colonne ne stocke de domaine.
- NON TROUVÉ après recherche de `Route::domain` ou `->domain(` dans `Modules/Authors` (routes ou contrôleurs) : zéro occurrence.
- Conclusion : il existe une INTENTION documentée par le nom de l'enum (« custom_domain_configured »armes) mais AUCUNE implémentation - ni colonne pour stocker le domaine, ni mécanisme de routage par domaine, ni écriture du log correspondant.

## 6. Intégrations déjà en place

### Newsletter par auteur (`AuthorSubscriber`)

- Modèle `AuthorSubscriber` (table `author_subscribers`) : `email`, `confirmation_token`, `confirmed_at`, `unsubscribed_at`, `source` (`inline`/`footer`/`modal`), `locale`, `last_digest_at` (`Modules/Authors/app/Models/AuthorSubscriber.php:16-27`).
- Flux réel : `NewsletterSubscriberService::subscribe()` crée/retrouve l'abonné et envoie `NewsletterConfirmationMail` via la façade `Mail` standard de Laravel (`Mail::to($email)->send(...)`, `Modules/Authors/app/Services/NewsletterSubscriberService.php:53`) - **pas Brevo**. Double opt-in : `confirm()` (`:68-96`) marque confirmé puis envoie `NewsletterWelcomeMail` uniquement lors de la transition NULL -> confirmé (`:79-93`).
- **`BrevoProvider` existe** (`Modules/Authors/app/Services/Newsletter/BrevoProvider.php`, implémente `NewsletterProvider` avec `connect`, `listAudiences`, `createCampaign`, `sendCampaign` via l'API `api.brevo.com`) **mais n'est jamais instancié.** NON TROUVÉ après recherche de `new BrevoProvider(`, d'un binding de service container, ou d'une injection du contrat `NewsletterProvider` ailleurs que dans son propre fichier, sur tout le dépôt. Code mort, non branché à la newsletter par auteur.
- Fournisseur d'envoi réellement utilisé pour la newsletter par auteur : le mailer par défaut de l'application (SMTP), pas un fournisseur newsletter dédié.

### Flux RSS/JSON

- RSS : `MiniSiteController::rss()` + `buildRichRssXml()` (`Modules/Authors/app/Http/Controllers/MiniSiteController.php:154-276`), construit un flux RSS 2.0 avec namespaces `content`, `media`, `atom`, `dc`, jusqu'à 20 articles publiés de l'auteur (`:163-167`). Il existe AUSSI une méthode `buildRssXml()` plus simple (`:422-454`) qui semble non appelée par `rss()` (celle-ci appelle `buildRichRssXml`, `:171`) - NON TROUVÉ d'appelant pour `buildRssXml` ailleurs dans le contrôleur.
- JSON Feed : `MiniSiteController::jsonFeed()` délègue à `AuthorExportService::exportJsonFeed()` (`:278-292`), lit un fichier généré sur disque et le sert avec `Content-Type: application/feed+json`.

### Webmentions (IndieWeb)

- Réception : `POST /webmention` (route anonyme dans `web.php:61-74`) appelle `WebmentionService::receive()` (`Modules/Authors/app/Services/WebmentionService.php:15-43`), qui valide les URLs, retrouve l'article cible via regex sur le chemin `/@{slug}/{postSlug}` (`:91-112`), et crée/rafraîchit une ligne `AuthorWebmention`.
- Vérification : `WebmentionService::verify()` (`:45-89`) télécharge la page source, vérifie qu'elle contient bien l'URL cible, extrait auteur/excerpt via regex sur microformats (`p-author`, `u-url`, `e-content`).
- Envoi sortant : `WebmentionSenderService::sendForPost()` (`Modules/Authors/app/Services/WebmentionSenderService.php`) extrait les liens externes d'un article publié et tente de découvrir/notifier l'endpoint webmention de la cible (header `Link` ou balise HTML).

### Pourboires (tips)

- Voir section 2 : route `POST /auteur/{slug}/tip`, `AuthorTipsService::createCheckout()` (paiement ponctuel Cashier), notification par courriel `TipReceivedNotificationMail`, aucune table d'historique dédiée.

### Affiliation

- `AuthorAffiliateLink` (table `author_affiliate_links` : `slug` unique, `destination_url`, `label`, `clicks_count` - `Modules/Authors/database/migrations/2026_05_24_000005_create_author_affiliate_links_table.php:17-27`).
- `AffiliateController::go()` (`Modules/Authors/app/Http/Controllers/AffiliateController.php:15-30`) : route `GET /go/{slug}`, incrémente `clicks_count`, journalise le clic, redirige 302 vers `destination_url`. Cloaking simple, pas de suivi de conversion.
- Gestion CRUD par l'auteur : Livewire `AffiliateLinkManager` (`Modules/Authors/app/Livewire/AffiliateLinkManager.php`), protégé par vérification de propriété (`abort_if($profile->user_id !== auth()->id(), 403)`, `:29-30`).

### Composants Livewire du module (rôle réel, une phrase chacun)

| Composant | Rôle réel |
|---|---|
| `AffiliateLinkManager` | CRUD des liens d'affiliation cloakés de l'auteur (`Modules/Authors/app/Livewire/AffiliateLinkManager.php`). |
| `AllAuthorsViewer` | Tableau admin (super-admin) listant tous les profils auteurs avec recherche et filtre par tier, lecture seule (`Modules/Authors/app/Livewire/AllAuthorsViewer.php`). |
| `ArticleBuilder` | Assistant en 4 étapes qui construit un PROMPT texte pour générer un article via IA externe, ne publie rien lui-même (`Modules/Authors/app/Livewire/ArticleBuilder.php:11-40`). |
| `AuthorActivityLogViewer` | Affiche le journal d'activité (`Spatie\Activitylog`) d'un auteur filtré par période (`Modules/Authors/app/Livewire/AuthorActivityLogViewer.php`). |
| `AuthorAnalyticsWidget` | Widget de statistiques 30 jours (publications, vues) pour le tableau de bord auteur (`Modules/Authors/app/Livewire/AuthorAnalyticsWidget.php`). |
| `AuthorDashboard` | Coquille à onglets du tableau de bord auteur (valeurs littérales du code : `composer`/`articles`/`curation`/`builders`/`parametres`/`stats`/`subscribers`/`affiliates`/`historique`) + export CSV des abonnés (`Modules/Authors/app/Livewire/AuthorDashboard.php:28-73`). |
| `AuthorEditor` | Éditeur d'article Markdown avec auto-save, planification, gestion de tags, création de révisions (`Modules/Authors/app/Livewire/AuthorEditor.php`). |
| `AuthorRecentNotifications` | Agrège les 5 derniers événements récents (commentaires, etc.) pour affichage dans le dashboard (`Modules/Authors/app/Livewire/AuthorRecentNotifications.php`). |
| `AuthorRelatedPosts` | Suggère des articles reliés du même auteur par tags communs (`Modules/Authors/app/Livewire/AuthorRelatedPosts.php`). |
| `AuthorSearch` | Recherche d'articles au sein du mini-site d'un auteur, liée à l'URL (`Modules/Authors/app/Livewire/AuthorSearch.php`). |
| `AuthorSettings` | Bascule d'affichage des 8 sections du mini-site (`modules_visible`), seule personnalisation réellement persistée (`Modules/Authors/app/Livewire/AuthorSettings.php`). |
| `CommentModerationQueue` | File de modération des commentaires (approuver/spam/supprimer, en masse ou un par un) pour super-admin (`Modules/Authors/app/Livewire/CommentModerationQueue.php`). |
| `CommentSection` | Formulaire + affichage des commentaires imbriqués avec anti-spam, honeypot et réactions emoji (`Modules/Authors/app/Livewire/CommentSection.php`). |
| `ImageBuilder` | Générateur de PROMPT texte pour image IA externe (DALL-E etc.), ne traite ni ne stocke aucune image (`Modules/Authors/app/Livewire/ImageBuilder.php`, section 3). |
| `ImageUploader` | Point d'entrée d'upload qui délègue le traitement réel à `ImagePipelineService` (`Modules/Authors/app/Livewire/ImageUploader.php`, section 3). |

## 7. Tests existants

- 25 fichiers dans `Modules/Authors/tests/Feature/` (compte `ls | wc -l` sur le répertoire). NON TROUVÉ de répertoire `Modules/Authors/tests/Unit/`.
- Volume approximatif : entre 3 et 28 cas de test (`it(...)`) par fichier, total de l'ordre de 285 cas de test déclarés sur l'ensemble des 25 fichiers (somme des comptages individuels obtenus par `grep -c "^it("` par fichier).
- Couverture observée par échantillonnage des noms de test (liste exhaustive des `it(...)` obtenue, non reproduite ici en totalité) :
  - **Sécurité d'accès (IDOR)** : `IdorAuthorProfileTest.php` (22 cas) vérifie que `AuthorEditor`, `AffiliateLinkManager`, `AuthorActivityLogViewer`, `AuthorAnalyticsWidget`, `AuthorRecentNotifications`, `AuthorSettings`, `AuthorDashboard`, `ImageUploader` rejettent bien un `authorProfileId` appartenant à un autre utilisateur (403).
  - **Palier/paiement** : `PremiumTest.php` (5 cas) - page upgrade, redirection non connecté, 503 si prix Stripe absent, `EnsurePremium` bloque un non-premium (test qui ENREGISTRE lui-même le middleware sur une route temporaire, seul endroit du dépôt où il est monté, voir section 1), webhook Stripe rejette un payload non signé.
  - **Structure du module** : `AuthorsModuleStructureTest.php` (11 cas) - vérifie l'EXISTENCE de fichiers (services, modèles, migrations, composants Blade), pas leur comportement.
  - **Newsletter** : `NewsletterSubscriberTest.php` (8 cas) - abonnement, honeypot, idempotence, confirmation signée, désabonnement, consentement obligatoire (Loi 25), limite de débit 5/min.
  - **Commentaires** : `CommentSectionTest.php` (28 cas, le fichier le plus fourni) - publication, modération auto par score de spam, réponses imbriquées, réactions, honeypot, consentement.
  - **Éditeur d'article** : `AuthorEditorTest.php` (15 cas) - auto-save, publication, validation de titre/longueur, génération de slug avec suffixe anti-collision, temps de lecture, tags JSON.
  - **Séries `S1xx EnhancementsTest.php`** (S108 à S122, 12 fichiers, de 7 à 16 cas chacun) : couvrent une longue liste de fonctionnalités ponctuelles déjà livrées - accessibilité (skip link, ARIA), sitemap, oEmbed (YouTube/Spotify), en-têtes de cache, JSON-LD, webmentions (découverte d'endpoint, envoi), liens d'affiliation, bouton de pourboire conditionnel à la config Stripe, confidentialité des appels OpenRouter (déni de collecte de données), etc.
- Aucun test ne couvre : le calcul de commission par palier (`StripeTipsService`), la gestion administrative des paliers (`TierManagementService`), le rendu effectif d'un article `visibility=subscribers` ou `visibility=premium` (aucun test ne vérifie ce que voit un visiteur face à un tel article - cohérent avec la section 4/1 : `PostController::show()` filtre `->public()` donc un tel article renvoie 404, comportement non testé explicitement).

## Note méthodologique

Recherche par `grep -rn` exhaustif (jamais un échantillon) sur `Modules/Authors` pour chaque terme cité, avec vérification croisée hors module (`app/`, racine du dépôt) quand une affirmation de type "jamais appelé" ou "jamais branché" était en jeu. Aucun fichier `.env` consulté. Aucune migration exécutée.

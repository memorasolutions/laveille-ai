## 1. Plugins / packages (composer) – tout gratuit

### 1.1. Architecture & feature flags

- `nwidart/laravel-modules` – Modules Laravel (chaque feature = module activable/désactivable). [github](https://github.com/nWidart/laravel-modules)
- `laravel/pennant` – Feature flags officiels (on/off par feature, module, user, rôle, env). [laravel](https://laravel.com/docs/12.x/pennant)

### 1.2. Auth, rôles, sécurité

- `laravel/sanctum` – Auth API/SPA/mobile (tokens + cookies sécurisés). [laravel](https://laravel.com/docs/12.x/authentication)
- `spatie/laravel-permission` – Rôles & permissions (RBAC complet). [laraveldaily](https://laraveldaily.com/packages)
- `spatie/laravel-activitylog` – Journal d’activité (qui a fait quoi, sur quel modèle). [github](https://github.com/Laravel-Backpack/activity-log/blob/main/license.md)

*(Sécurité applicative avancée = middlewares & config que tu codes toi.)* [dev](https://dev.to/sharifcse58/15-laravel-security-best-practices-in-2025-2lco)

### 1.3. Performance, monitoring, santé

- `laravel/horizon` – Monitoring & management des queues Redis. [needlaravelsite](https://needlaravelsite.com/blog/10-essential-laravel-packages-every-developer-should-know-in-2025)
- `laravel/telescope` – Observabilité complète (requêtes, exceptions, jobs, mails, cache, DB). [redberry](https://redberry.international/securing-laravel-web-apps/)
- `spatie/laravel-health` – Health checks (DB, Redis, disque, Horizon, debug mode, etc.). [laravel-news](https://laravel-news.com/track-the-health-of-your-application-with-laravel-health)
- `spatie/laravel-responsecache` – Cache HTTP (pages publiques). [voxfor](https://www.voxfor.com/top-20-laravel-packages-to-supercharge-development-and-boost-seo-in-2025/)
- `spatie/laravel-backup` – Backups fichiers + BD, rotation + notifications. [laraveldaily](https://laraveldaily.com/packages)

### 1.4. Logging & bug tracking (optionnel, mais conseillé)

- Logging natif Laravel (`config/logging.php`). [laravel](https://laravel.com/docs/12.x/logging)
- Intégration possible d’une solution externe (Sentry, Flare, Bugsnag) si tu veux, mais **non obligatoire** (et souvent payant → donc à exclure si tu ne veux que du gratuit). [blog.sentry](https://blog.sentry.io/laravel-debugging-logging-guide/)

Dans ton core, tu restes sur le logging Laravel + éventuellement un channel syslog/stack.

### 1.5. Stockage fichiers

- Filesystem natif Laravel (local, public, s3, sftp). [kritimyantra](https://kritimyantra.com/blogs/laravel-12-file-storage)
- `spatie/laravel-medialibrary` – gestion avancée des médias (collections, conversions, etc.). [spatie](https://spatie.be/open-source/packages)

### 1.6. Admin backend (Backpack – uniquement la partie gratuite)

- `backpack/crud` – Core Backpack (CRUD admin). [github](https://github.com/Laravel-Backpack/CRUD)
- `backpack/permissionmanager` – UI users/rôles/permissions. [backpackforlaravel](https://backpackforlaravel.com/addons)
- `backpack/settings` – Paramètres applicatifs. [backpackforlaravel](https://backpackforlaravel.com/addons)
- `backpack/pagemanager` – Pages statiques (mini CMS). [backpackforlaravel](https://backpackforlaravel.com/addons)
- `backpack/menucrud` – Menus. [backpackforlaravel](https://backpackforlaravel.com/addons)
- `backpack/logmanager` – Logs applicatifs. [laravel-news](https://laravel-news.com/laravel-backpack)
- `backpack/backupmanager` – Backups via interface. [laravel-news](https://laravel-news.com/laravel-backpack)
- `backpack/filemanager` – Gestion de fichiers. [backpackforlaravel](https://backpackforlaravel.com/addons)
- `backpack/revise-operation` (ou équivalent) – Historique des modifications. [backpackforlaravel](https://backpackforlaravel.com/addons)
- `backpack/theme-tabler` (ou autre thème gratuit) – Thème admin de base. [github](https://github.com/Laravel-Backpack/theme-tabler)

*(AUCUN add-on payant : pas de PRO, pas de DevTools, pas d’EditableColumns.)* [backpackforlaravel](https://backpackforlaravel.com/docs/7.x/features-free-vs-paid)

### 1.7. Thèmes frontend & multi-thèmes

- `qirolab/laravel-themer` – Multi-thèmes frontend (parent/enfant, switch par middleware). [github](https://github.com/qirolab/laravel-themer)

### 1.8. Données, API, contenu, SEO, webhooks

- `spatie/laravel-query-builder` – Filtres, tri, includes pour API REST. [themeselection](https://themeselection.com/laravel-packages-list/)
- `spatie/laravel-model-states` – États métier (workflow sur les modèles). [laraveldaily](https://laraveldaily.com/packages)
- `spatie/laravel-translatable` – Champs traduisibles (multi-langue contenu). [themeselection](https://themeselection.com/laravel-packages-list/)
- `spatie/laravel-sitemap` – Sitemaps XML. [kritimyantra](https://kritimyantra.com/blogs/laravel-12-with-spatie-laravel-sitemap-a-complete-seo-guide)
- `spatie/laravel-webhook-server` – Envoi de webhooks. [laravel-news](https://laravel-news.com/packages-for-sending-and-receiving-webhooks)
- `spatie/laravel-webhook-client` – Réception de webhooks. [github](https://github.com/spatie/laravel-webhook-client)

### 1.9. Notifications & communication

- Notifications Laravel natives (mail + database + broadcast). [laravel](https://laravel.com/docs/12.x/notifications)
- Provider mail (Mailgun, SES, Postmark, Resend…) via config SMTP/API. [laravel](https://laravel.com/docs/12.x/mail)
- SMS : `laravel-notification-channels/vonage` ou Twilio SDK (si tu veux des SMS). [ideatoweb.co](https://ideatoweb.co.uk/blog/technology/mastering-laravel-12-a-step-by-step-guide-to-sending-whatsapp-and-sms-notifications)

### 1.10. Qualité de dev & tests

- `barryvdh/laravel-debugbar` – debug en dev. [dev](https://dev.to/devrabiul/top-20-laravel-packages-every-developer-should-know-in-2025-3gd0)
- `barryvdh/laravel-ide-helper` – autocomplétion IDE. [laravel-package-ocean](https://laravel-package-ocean.com/top-laravel-packages)
- Tests :  
  - PHPUnit natif Laravel. [daydreamsoft](https://www.daydreamsoft.com/blog/testing-laravel-applications-with-phpunit-and-pest-a-complete-guide)
  - ou `pestphp/pest` si tu veux la syntaxe Pest (gratuit, optionnel). [youtube](https://www.youtube.com/watch?v=cu1tQTV6kiU)

### 1.11. Options SaaS (toujours gratuit)

- `laravel/cashier-stripe` ou `laravel/cashier-paddle` – abonnements, plans, factures. [kritimyantra](https://kritimyantra.com/blogs/top-10-essential-laravel-12-packages-for-your-next-project)
- Multi-tenancy :  
  - `stancl/tenancy` – multi-tenant avancé. [tenancyforlaravel](https://tenancyforlaravel.com)
  - ou `spatie/laravel-multitenancy` – multi-tenant minimaliste. [github](https://github.com/spatie/laravel-multitenancy)

***

## 2. Modules internes (via laravel-modules) – à créer une fois

Tu gardes tout en modules pour ne jamais dupliquer de code :

- `Core/Auth` – login/register/reset, policies, guards, helpers user.  
- `Core/RolesPermissions` – intégration spatie/permission + Backpack PermissionManager.  
- `Core/Settings` – service pour paramètres (wrap Backpack Settings + cache).  
- `Core/Media` – upload, upload multi, conversions (intégration Medialibrary).  
- `Core/Notifications` – canaux, templates de base (email/SMS/in-app).  
- `Core/Logging` – centralisation logs + ActivityLog + LogManager.  
- `Core/Backoffice` – config Backpack (routes, menus, dashboards, widgets).  
- `Core/FrontTheme` – intégration Laravel Themer (choix de thème, middleware).  
- `Core/SEO` – sitemap, meta, robots.txt, open graph.  
- `Core/Webhooks` – webhooks sortants / entrants + jobs + retries.  
- `Core/Health` – config checks health + éventuelles vues d’état.  
- `Core/Api` – base controllers API + format JSON standard + policies + rate limiting.  
- `Core/Storage` – abstraction autour des disks (local/public/s3).  
- `Core/SaaS` (optionnel) – modèles Plan/Subscription + Cashier.  
- `Core/Tenancy` (optionnel) – bootstrap multi-tenant (stancl/spatie).  

***

## 3. Ce que tu codes localement (dans le core, commun à tous les projets)

### 3.1. Inline editing dans les listes (Backpack)

Objectif : **éditer directement dans les colonnes** (listes CRUD), sans ouvrir le form.

Tu implémentes :

- Colonnes custom Backpack avec :  
  - `<input>`, `<select>`, toggle, etc. directement dans la cellule.  
  - JS (Alpine/vanilla) qui déclenche une requête AJAX sur blur/clic pour sauvegarder. [editor.datatables](https://editor.datatables.net/examples/inline-editing/simple)
- Route JSON pour la mise à jour :  
  - `PATCH /admin/{entity}/{id}/inline` (secured par auth + permissions).  
- Validation côté backend + renvoi des erreurs (affichées dans la cell).  

Tu peux t’inspirer des patterns Laravel DataTables / X-editable / Livewire, mais sans ajouter de packages payants. [laravelarticle](https://laravelarticle.com/laravel-inline-edit-by-x-editable)

### 3.2. Thèmes backend, frontend, login

- Backend :  
  - Layout Backpack custom (intégration complète de ton thème admin Bootstrap/Tabler). [backpackforlaravel](https://backpackforlaravel.com/docs/7.x/base-themes)
  - Composants Blade pour sidebar, topbar, breadcrumbs, notifications.  

- Frontend :  
  - Structure des thèmes avec `qirolab/laravel-themer` (dossiers, fallback parent/enfant, etc.). [laravel-news](https://laravel-news.com/package/qirolab-laravel-themer)
  - Middleware pour choisir le thème selon config, domaine, tenant.

- Login :  
  - Vues auth Backpack publiées et redesignées (login, register, reset password). [youtube](https://www.youtube.com/watch?v=t11ibWJwxhc)
  - Éventuel thème login frontend (autre layout, autre ambiance).

### 3.3. Sécurité & middleware

- Middlewares custom pour :  
  - CSP + security headers (X-Frame-Options, X-Content-Type-Options, etc.). [dev](https://dev.to/sharifcse58/15-laravel-security-best-practices-in-2025-2lco)
  - HTTPS forcé en prod. [dev](https://dev.to/sharifcse58/15-laravel-security-best-practices-in-2025-2lco)
  - Rate limiting fin pour login, endpoints sensibles (RateLimiter). [laravel-news](https://laravel-news.com/managing-api-rate-limits-in-laravel-through-job-throttling)
- Configuration CORS (`config/cors.php`) adaptée à Sanctum, front, API. [patrickjunod](https://www.patrickjunod.dev/blog/gestion-des-cors-sur-laravel-via-un-middleware)

### 3.4. Traduction (on/off)

- Organisation des fichiers `lang/{locale}` (fr, en, etc.). [laravel](https://laravel.com/docs/12.x/authentication)
- Écran de traduction via Backpack (TranslationManager / pages custom). [backpackforlaravel](https://backpackforlaravel.com/addons)
- Intégration de `spatie/laravel-translatable` dans les modèles qui doivent l’être. [themeselection](https://themeselection.com/laravel-packages-list/)
- Flags via Pennant/config pour activer/désactiver :  
  - multi-langue contenu,  
  - multi-langue interface. [dev](https://dev.to/aleson-franca/laravel-pennant-releasing-features-with-feature-flags-3lm1)

### 3.5. Monitoring, santé, backups

- Config `spatie/laravel-health` (checks, seuils, notifications). [github](https://github.com/spatie/laravel-health)
- Cron jobs :  
  - backups réguliers (daily/weekly),  
  - éventuels checks de santé.  
- Sécurisation de l’accès à Horizon et Telescope (rôle Admin + env). [laravel](https://laravel.com/docs/12.x/errors)

### 3.6. Notifications & communication

- Base `Notification` abstraite + classes enfants standard (Welcome, PasswordChanged, etc.). [globalriskcommunity](https://globalriskcommunity.com/profiles/blogs/ensuring-reliable-real-time-notifications-in-laravel-best-practic)
- Templates d’email HTML réutilisables + partials (header/footer). [laravel](https://laravel.com/docs/12.x/mail)
- Interface pour SMS + adapters (Vonage/Twilio), que tu peux plugger ou non. [ideatoweb.co](https://ideatoweb.co.uk/blog/technology/mastering-laravel-12-a-step-by-step-guide-to-sending-whatsapp-and-sms-notifications)

### 3.7. SaaS & multi-tenant (optionnel, mais prévu)

- Modèles `Plan`, `Subscription`, `Invoice` (si Cashier). [kritimyantra](https://kritimyantra.com/blogs/build-a-laravel-12-saas-project-with-tenancy-for-laravel-beginners-guide)
- Intégration `Cashier` (hooks webhooks Stripe/Paddle, events). [github](https://github.com/spatie/laravel-webhook-server)
- Bootstrap Tenancy :  
  - tenant resolver (domain/subdomain),  
  - scoping, migrations par tenant si DB séparée. [tenancyforlaravel](https://tenancyforlaravel.com/docs/v3/configuration/)

### 3.8. Pages d’erreur & expérience

- Vues custom pour 403, 404, 419, 429, 500, avec design cohérent. [kritimyantra](https://kritimyantra.com/blogs/laravel-12-error-handling-guide)
- Personnalisation du `Handler` des exceptions :  
  - réponses JSON propres pour API,  
  - templates Blade pour web. [vishalgarg](https://www.vishalgarg.io/articles/customize-default-exceptions-laravel-12)

### 3.9. Tests

- Setup de base pour tests :  
  - tests de feature pour les endpoints clés (auth, CRUD, API). [dev](https://dev.to/addwebsolutionpvtltd/laravel-testing-made-simple-with-pest-write-clean-readable-and-fast-tests-2b44)
  - tests unitaires sur les services (permissions, billing, etc.). 
******
# 🔧 Directive d'Architecture Modulaire

## Principe Fondamental
**Applique systématiquement une architecture modulaire** où chaque composant est conçu pour être réutilisable, maintenable et évolutif.

## Règles de Modularisation

### 1. **Critères de Modularisation**
Un élément devient un module si :
- Utilisé ≥ 2 fois dans le projet
- Logique métier autonome et encapsulée
- Potentiel de réutilisation future identifié
- Configuration variable selon le contexte

### 2. **Standards de Création**
Chaque module doit :
- **Être autonome** : Aucune dépendance circulaire
- **Suivre SRP** : Une seule responsabilité claire
- **Être configurable** : Props/paramètres pour variations
- **Être documenté** : JSDoc/TypeScript avec exemples
- **Être testé** : Tests unitaires isolés

### 3. **Principes d'Organisation**
- **Point d'entrée unique** par module
- **Séparation des types** et de l'implémentation
- **Configuration externalisée** quand nécessaire
- **Tests colocalisés** avec le module
- **Structure adaptée** au framework/langage du projet

### 4. **Analyse Systématique**
Avant chaque implémentation :
1. **Identifier** les patterns répétitifs
2. **Abstraire** la logique commune
3. **Paramétrer** les variations
4. **Valider** la réutilisabilité
5. **Documenter** les cas d'usage

### 5. **Checklist de Validation**
- [ ] DRY : Aucune duplication de logique
- [ ] SOLID : Principes respectés
- [ ] Couplage faible, cohésion forte
- [ ] API claire et prévisible
- [ ] Rétrocompatibilité maintenue

## Output Attendu
Pour chaque livrable, fournis :
1. **Liste des modules identifiés** avec justification
2. **Matrice de réutilisation** (où/comment utilisé)
3. **API de chaque module** (props/methods/events)
4. **Plan de migration** si refactoring nécessaire

## Exemple Minimal
```typescript
// ❌ Éviter
const UserCard = () => { /* logique spécifique */ }
const ProductCard = () => { /* logique similaire */ }

// ✅ Préférer
const Card = ({ variant, data, actions }) => { /* logique générique */ }
const UserCard = (props) => <Card variant="user" {...props} />
const ProductCard = (props) => <Card variant="product" {...props} />
```

**Priorité absolue** : Penser "module first" dès la conception, pas en refactoring.
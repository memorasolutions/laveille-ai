# Laravel CORE Template - Mémoire projet

## État actuel (2026-02-26)
- **2156 tests, 4192 assertions, 100% pass**
- **PHPStan** : 0 erreurs (niveau 6)
- **Branche** : master (105+ fichiers modifiés non commités depuis merge feature/plugin-architecture)
- **25 modules actifs**, 312 routes, 57 migrations
- **3 thèmes backoffice** : wowdash (Bootstrap 5), tabler, backend (NobleUI Bootstrap 5.3.8)
- **Thème actif** : backend (NobleUI) - conversion Tailwind→Bootstrap 5 TERMINÉE (0 Tailwind restant)
- **i18n** : 100% français (validation 148 clés, passwords 5, pagination 2, auth 3, fr.json 670+)
- **WCAG 2.2 AA** : ~85 aria-labels ajoutés sur 17 composants Livewire
- **UX** : hook wire:loading global (spinner + disable sur tous les boutons Livewire), toasts Bootstrap 5
- **SaaS** : Stripe Cashier, plans, checkout, revenue dashboard ApexCharts
- **IA** : Module AI OpenRouter (chat, articles, modération, SEO, traduction)
- **PWA** : service-worker.js + manifest.json
- Superadmin : stephane@memora.ca / Admin123!

## Theme switcher dynamique (VALIDÉ 2026-02-24)

### Architecture
- **Middleware** `SetBackofficeTheme` : lit `backoffice.theme` depuis Settings DB, prepend 3 namespaces (backoffice, auth, blog)
- **Middleware appliqué** sur : routes admin (Backoffice), routes user dashboard (Auth), routes blog admin (Blog)
- **Setting DB** : `backoffice.theme` (groupe branding, id 60). Actuellement = `backend`
- **saveTheme()** dans `SettingsManager` Livewire + sélecteur UI dans chaque thème
- **3 thèmes disponibles** : wowdash (Bootstrap 5), tabler (Tabler CSS), backend (Jobick/Tailwind)
- **Layouts Auth** : 3 variantes dans `Modules/Auth/resources/views/themes/{theme}/layouts/app.blade.php`
- **Blog refactoré** : contrôleurs utilisent `blog::admin.*` (plus de hardcode wowdash), vues base + backend créées
- `BACKOFFICE_THEME` retiré du `.env` - la DB gère tout
- Tests rendus theme-agnostic (Phase63, Phase65, Phase97, Phase99, Phase154, Phase174, Phase175, Phase187)

### Corrections validées (2026-02-24)
- **Flatpickr** : JS chargé APRÈS jQuery (global.min.js) dans script.blade.php + Auth app.blade.php
- **Breadcrumb** : subtitle "Accueil" → "Administration" (élimine double "Accueil/Accueil")
- **Auth layout JS** : ordre corrigé (global→flatpickr→dlabnav-init→custom)
- **Middleware étendu** à : Backoffice, Auth, Blog, Pages, Newsletter
- **15 controllers refactorisés** (supprimé hardcode wowdash), 21 vues base fallback créées
- **2156 tests, 0 échec** - Playwright 9.6/10

### Todo restant
- MakeCrudCommand stubs hardcode wowdash → à rendre theme-agnostic
- README.md → sync (2156 tests, 25+ modules)

## Phase 185 - Catégories et tags dropdown WordPress-style (TERMINÉE)
- Tom-Select 2.4.3 CDN (Bootstrap 5, 10KB, pas jQuery)
- create.blade.php + edit.blade.php : select#category-select (single + quick-create AJAX) + select#tags-select (multi + create inline)
- Route quick-create retourne JSON {id, name}
- 14 tests Phase185Test

## Phase 145 - GDPR cookie préférences granulaires
- **CookieCategory model** : table cookie_categories, 4 catégories (essential/functional/analytics/marketing)
- **Admin CRUD** : CookieCategoryController, vues WowDash index/create/edit, sidebar "Cookies GDPR"
- **Banner dynamique** : catégories depuis DB, toggles par catégorie, boutons accept/decline/customize
- **Page /cookie-preferences** : page publique pour modifier préférences après consentement initial
- **Middleware ResolveCookiePreferences** : partage $cookiePreferences + $cookieCategories aux vues
- **CookieConsentController refactorisé** : accept/decline/customize dynamiques depuis DB
- 17 tests Phase145Test, Phase122Test adapté (4 catégories)

## Phase 146 - Onboarding wizard nouveaux utilisateurs
- **OnboardingStep model** : table onboarding_steps, 4 étapes (welcome/profile/preferences/done)
- **Migration** : onboarding_completed_at sur users (idempotente, colonne existante)
- **OnboardingController** (Auth module) : index (wizard Alpine.js stepper), complete (update profil + mark done), skip
- **EnsureOnboardingCompleted middleware** : redirige vers /onboarding si pas complété, alias 'onboarding'
- **Admin CRUD** : OnboardingStepController (index/edit/update), vues WowDash, sidebar "Onboarding"
- **Wizard Alpine.js** : progress bar, stepper multi-étapes, champs profil/préférences, bouton skip
- 13 tests Phase146Test

## Phase 52 - Production readiness (6 fonctionnalités)
- **Sitemap dynamique** : SitemapController (spatie/laravel-sitemap), route /sitemap.xml, robots.txt mis à jour
- **reCAPTCHA v3** : VerifyRecaptcha middleware, 3 settings (captcha_enabled, recaptcha_site_key, recaptcha_secret_key), alias 'recaptcha'
- **Dockerfile.production** : multi-stage (composer, node, php:8.4-fpm-alpine), opcache, redis, gd
- **Page pricing /pricing** : vue GoSaaS, plans depuis BD, features FR, FAQ accordion, CTA
- **Stripe Checkout + Billing Portal** : CheckoutController (checkout, success, cancel, portal), Billable trait sur User, routes SaaS module, vues success/cancel GoSaaS
- **Webhook Stripe** : StripeWebhookController extends Cashier WebhookController (log subscription created/updated/deleted, invoice succeeded/failed), CSRF exclusion /stripe/webhook, Cashier::ignoreRoutes() + routes custom
- **SitemapController fix** : `is_published` → `status = published` pour StaticPage
- 14 tests Phase52Test (pricing, sitemap, checkout auth, webhook, middleware, Billable)

## Phases récentes (155-161)
- **155** : Refonte traductions admin (Livewire TranslationsManager, côte à côte, import/export, 20 tests)
- **156** : Data retention policies (CleanupOldRecords lit Settings, 4 paramètres rétention, 10 tests)
- **157** : Email digest notifications (SendNotificationDigest command, DigestMail, fréquence user immediate/daily/weekly, profil UI, 14 tests)
- **158** : Admin Activity Log complet (ActivityLogController export CSV + purge, Livewire filtres date/événement/detail modal, 16 tests)
- **159** : Bulk Actions trait (HasBulkActions trait réutilisable, UsersTable/ArticlesTable/CommentsTable, checkboxes+barre actions, 11 tests)
- **160** : Page Statistiques Looker Studio (iframe sandboxé configurable, Setting looker_studio_url, CSP frame-src, empty state guide 3 étapes, sidebar link, 10 tests)
- **161** : Module AI - Service OpenRouter (AiService singleton, AiConversation/AiMessage models, 2 enums, 2 migrations, 10 AI settings, onglet admin, 15 tests)
- **162** : Chatbot IA frontend (ChatBot Livewire, wire:stream word-by-word, bulle flottante, panel 350x500, auth DB + guest session, dark mode, WCAG AA, 19 tests)
- **163** : AI Article Generator (AiArticleGenerator Livewire, modal génération, AiService::generateArticle(), applyField/applyAll dispatch events, bouton dans blog create, 18 tests)
- **164** : AI Content Moderation (CommentModerationObserver, AiService::moderateContent(), auto approve/spam/flag, seuil de confiance configurable, 2 settings, 15 tests)
- **165** : AI SEO Auto-Generator (AiService::generateSeoMeta(), ArticleSeoObserver auto-génère MetaTag quand article publié, route admin regenerate-seo, setting ai.seo_auto_generate, 13 tests)
- **166** : AI Auto-Translation (AiService::translateContent(), ArticleController::translateArticle(), route admin articles/{article}/translate, traduction titre/contenu/excerpt/slug vers locale cible, 13 tests)
- **167** : Nettoyage code mort (Migration Phase 1) - supprimé 2 factories orphelines, 22 vues stub index.blade.php, 1 middleware dupliqué deprecated, audit Playwright 4 pages OK
- **168** : AI Content Summary (AiService::generateSummary(), ArticleSeoObserver auto-excerpt, admin route regenerate-summary, 16 tests)
- **169** : AI Content Analysis (AiService::analyzeContent(), admin route articles/{article}/analyze, score 0-100 clamped, 14 tests)
- **170** : Billing History + Plan Swap UI (invoices page, swapPlan controller, SubscriptionService tests, 17 tests)
- **171** : SaaS Metrics + Trial Expiry (SaasMetricsService MRR/ARR/churn, RevenueController refactorisé, SendTrialExpiryNotifications command, schedule daily 09:00, metrics JSON endpoint, 18 tests)
- **172** : Revenue Dashboard ApexCharts (6 KPI cards, donut subscription distribution, bar revenue by plan, indicateurs clés, @push('js') fix, 18 tests)
- **173** : Real-time Admin Notifications Reverb (NotificationBell Echo listeners, toast-notifications Alpine.js partial, WowDash cards, WCAG aria-live, 15 tests)
- **174** : Refonte UX/UI pages contenu admin (8 vues upgraded: login-history, mail-log, scheduler, failed-jobs, blocked-ips, security, email-templates, cache. Pattern bordered-table sm-table + radius-12 + scroll-sm + icons header + card-footer, bug </form> orphelin notifications fixé, 10 tests)
- **175** : Dashboard analytics ApexCharts (StatsController, 6 stats cards, 5 graphiques: user growth area, activity timeline area, content bar, webhooks donut, categories horizontal bar, sélecteur période 7/30/90j, 24 traductions, 10 tests)
- **Fix overflow** : Page /admin/translations débordait de 4938px - corrigé avec table-layout:fixed + word-break
- **178** : Export CSV 6 entités (articles, categories, plans, campaigns, pages, comments). PHPStan: +3 modules paths (Editor/Pages/AI), +2 identifiers ignorés
- **179** : Import CSV plans/pages/comments + templates téléchargeables (7 types). Fix StaticPage translatable slug JSON
- **180** : Dashboard rétention données + dry-run cleanup. Fix purge configurable via settings (180j). DataRetentionController + vue WowDash

- **181** : System Info admin (SystemInfoController, PHP/Laravel/Server/Modules/Extensions, vue WowDash, 11 tests)
- **182** : Sidebar admin complétée (+9 entrées manquantes : system-info, data-retention, notifications, push-notifications, email-templates, webhooks, shortcodes, cookie-categories, onboarding. 10 tests)
- **183** : Health page FR + remédiation (4 summary cards, statuts traduits FR, colonne Instructions avec remédiation contextuelle par check, code tags pour commandes artisan. 12 tests)
- **184** : Feature Flags conditions UX (FeatureFlagCondition model, 5 types conditions: always/percentage/roles/environment/schedule, édition inline Livewire, badges colorés, migration feature_flag_conditions, route admin.feature-flags.conditions. 18 tests)

## Phase 186 - Laravel Scout full-text search upgrade (TERMINÉE)
- **Scout driver** : collection → database (plus performant, LIKE queries remplacées)
- **SearchService** : 3 méthodes spécialisées (searchAdmin, searchNavbar, searchFront)
- **6 modèles searchable** : User, Article, Plan, Category, StaticPage, Setting
- **Setting model** : ajout trait Searchable, exclut groupes security/secrets
- **Admin search refactorisé** : LIKE → Scout, filtres par type (7 types), catégories ajoutées
- **GlobalSearch Livewire** : LIKE → Scout::searchNavbar()
- **Front /search** : FrontSearchController, vue GoSaaS responsive, pagination
- **API /api/v1/search** : publique, validation q >= 2 chars, filtre par model
- **Header GoSaaS** : icône recherche ajoutée
- **Fix mobile** : navbar-search → position-relative (visible sur 375px)
- **22 tests Phase186Test** + Phase151Test/Phase176Test adaptés

## Phase 187 - Conversion champs texte en dropdowns (TERMINÉE)
- **Plans create/edit** : currency texte → select (CAD, USD, EUR, GBP, CHF)
- **SEO create/edit** : robots texte → select (6 directives standard)
- **Onboarding steps edit** : SVG path → select 10 icônes Iconify
- **Admin search mobile** : navbar-search → position-relative (fix Phase 186)
- **Phase176Test** : adapté (navbar-search → form-control)
- 12 tests Phase187Test, Playwright 5/5 (desktop + mobile 375px)

## Phase 188 - WCAG 2.2 AA Accessibilité (TERMINÉE)
- **GoSaaS layout** : ajout `<main id="main-content">`, `aria-hidden="true"` sur preloader
- **GoSaaS header** : `<div>` → `<nav>` avec `aria-label="Navigation principale"`
- **Guest layout** : `lang="fr"` → `lang="{{ str_replace('_', '-', app()->getLocale()) }}"`, `<section>` → `<main>`
- **Login** : `<h4>` → `<h1>`, labels visually-hidden associés aux inputs, `autocomplete="email"` + `autocomplete="current-password"`, toggle password `<span>` → `<button>` avec aria-label, `aria-hidden="true"` sur icônes sociales
- **Admin breadcrumb** (both base + WowDash) : `<h6>` → `<h1>`, `<nav aria-label="Fil d'Ariane">`, `aria-current="page"`, `aria-hidden="true"` sur séparateur
- **Admin sidebar** : `<nav aria-label="Menu administration">`, `aria-label="Fermer le menu"` sur close button
- **Admin navbar** : `aria-label="Basculer le menu"` + `aria-label="Ouvrir le menu"` sur toggle buttons, `aria-hidden="true"` sur iconify-icons
- 16 tests Phase188Test (40 assertions)

## Phase 189 - Tab URL persistence (TERMINÉE)
- **Settings Manager onglets** : Alpine.js x-data + history.replaceState
- **URL persistence** : onglets persistent via paramètre ?tab=xxx (général, courriel, seo, apparence)
- **Route admin.settings.index** : accepte ?tab pour deep-linking
- **Alpine.js selectTab()** : met à jour window.history.replaceState + actif CSS
- **Initialisation** : récupère ?tab depuis URL, défaut 'general'
- **UX** : liens directs vers onglets possibles (ex: /admin/settings?tab=email)
- 6 tests Phase189Test, audit Playwright 6/6 (navigation, persistence, refresh state)

## TipTap Editor - état validé (2026-02-25)
- **26 boutons toolbar** : undo, redo, bold, italic, underline, strike, H1-H3, bullet/ordered/task lists, align L/C/R/J, link, image, table, youtube, hr, code, codeblock, highlight, superscript, subscript
- **8 extensions ajoutées** : Placeholder, CharacterCount, Typography, TaskList+TaskItem, YouTube, Superscript, Subscript
- **Compteur réactif** : propriétés Alpine `wordCount`/`charCount` (pas `editor.storage` direct)
- **@mousedown.prevent** sur tous les boutons (pas @click - préserve focus ProseMirror)
- **requestAnimationFrame()** pour TaskList et HorizontalRule (évite RangeError)
- **Stacks Blade** : `@push('styles')` et `@push('scripts')` - layout backend accepte les deux
- **Audit Playwright 5/5 PASS**

## Tâches terminées (session 2026-02-26)
- [x] Refonte permissions rôles : 5 onglets + labels FR + descriptions + Alpine.js réactif (9.3/10 Playwright)
- [x] Bug "Type de publication" : select → btn-group radio (Brouillon/Publié/Archivé) + fix cast (string) Spatie ModelStates (9/10)
- [x] users/create+edit backend : layout 2 colonnes, champs phone + must_change_password + is_active, rôles en cartes descriptives (create 9/10, edit 9.5/10)
- [x] 3 thèmes synchronisés (backend, wowdash, tabler) pour rôles et articles

## Todo actif (2026-02-26)
### Priorité haute - TOUT COMPLÉTÉ
- [x] README.md déjà à jour
- [x] Audit hardcode wowdash - 6 modules vérifiés, aucun hardcode
- [x] Docs obsolètes supprimées (4 fichiers)
- [x] Vues orphelines supprimées (22 dossiers, 38 fichiers)
- [x] Commit massif 4e0500c (4835 fichiers)

### Priorité moyenne
- [x] Découplage Core : SetBackofficeTheme config-driven, BlockSuspiciousIps→Auth, CleanupOldRecords→DB direct
- [ ] Extraction code partagé (traits ParsesTags, VerifiesPassword, FormRequests dupliqués)
- [ ] Harmoniser boutons d'action admin (kebab vs liens texte)
- [x] Screenshots .png exclus via .gitignore

### Priorité basse (phases 154-158+)
- [ ] Phase 154 : email digest
- [ ] Phase 155 : documentation technique auto-générée
- [ ] Phase 156 : multi-tenant avancé
- [ ] Phase 157 : marketing automation
- [ ] Phase 158 : tests A/B
- [ ] Migration Modules/ → plugins/ (décision utilisateur requise)
- [ ] API v2 GraphQL
- [ ] Tests E2E Playwright automatisés

### Complété récemment (session 2026-02-26)
- [x] Toast notifications Bootstrap 5 (20 composants Livewire)
- [x] i18n validation FR (148 règles) + passwords (5) + pagination (2)
- [x] Footer "Tous droits réservés." corrigé
- [x] Hook wire:loading global (spinner + disable boutons Livewire)
- [x] 8 textes anglais corrigés (ON/OFF, placeholders FR)
- [x] ~85 aria-labels WCAG 2.2 AA sur 17 Livewire
- [x] PROGRESS_REPORT.md complet et à jour

## Phase 51 - Account security hardening
- Migration : failed_login_count (uint default 0), locked_until (timestamp nullable) sur users
- User::isLocked() vérifie locked_until > now
- LogFailedLogin listener : incrémente failed_login_count, verrouille après N échecs (Settings configurable)
- LogLoginAttempt listener : reset compteur et locked_until après login réussi
- Login Livewire : vérifie isLocked() avant authentification
- UnlockUserCommand : artisan auth:unlock-user {email}
- UserController admin : méthode unlock() + route admin.users.unlock
- PasswordPolicyRule : règle de validation custom (longueur, majuscule, chiffre, spécial) configurable via Settings
- UserRules trait : passwordRules() utilise PasswordPolicyRule (centralise admin + API + Livewire)
- RegisterRequest API : PasswordPolicyRule remplace Password::min(8)
- ProfileController : PasswordPolicyRule sur updatePassword
- Register Livewire : validation inline remplacée par rules() avec PasswordPolicyRule
- 6 settings sécurité ajoutés au seeder (max_login_attempts, lockout_duration, password_*)
- 16 tests Phase51Test

## Phase 50 - IP blocking et sécurité
- BlockedIp : table blocked_ips, modèle avec isBlocked() statique, isActive()
- CheckBlockedIp : middleware global (bootstrap/app.php), try-catch pour résilience
- BlockSuspiciousIps : commande app:block-suspicious-ips (seuil configurable, auto-block 24h)
- Scheduling : app:block-suspicious-ips planifié every 5 minutes via CoreServiceProvider
- SecurityDashboardController : stats 24h (logins, IPs suspectes, tentatives récentes)
- BlockedIpController : CRUD admin (bloquer/débloquer IP manuellement)
- Sidebar : nouvelle section Sécurité (dashboard, IPs bloquées, connexions)
- 16 tests Phase50Test (modèle, middleware, admin, auto-block)

## Phase 49 - Observabilité et nettoyage
- LoginAttempt : table login_attempts, modèle, listeners Login/Failed events
- LoginHistoryController : vue admin paginée (email, IP, user-agent, statut, date)
- CacheController : 5 actions (cache, config, views, routes, all) + vue WowDash cards
- CleanupOldRecords : commande app:cleanup (login 90j, emails 90j, activity 180j, tokens expirés)
- Scheduling : app:cleanup planifié daily 03:00 via CoreServiceProvider
- Sidebar Outils : +2 entrées (Connexions, Cache)
- 16 tests Phase49Test (listeners, admin pages, cache actions, cleanup command)

## Phase 48 - Production hardening
- RequestId middleware : X-Request-ID UUID sur chaque requête, propagé logs + Sentry + response
- SchedulerController : parse artisan schedule:list, vue WowDash admin/scheduler
- Mail log : table sent_emails, SentEmail model, LogSentEmail listener (MessageSent event)
- MailLogController : vue admin/mail-log avec pagination
- Sidebar Outils : 2 nouvelles entrées (Scheduler, Emails envoyés)
- 12 tests Phase48Test (RequestId, scheduler, mail log)

## Phase 47 - OTP SMS + Auth par rôle + Force password change + SMTP dynamique
- Migration : phone, phone_verified_at, must_change_password (users) + requires_password (roles)
- MagicLinkService : code 6 chiffres numérique, expiry configurable via Settings
- MagicLinkController : sendSms() avec rate limiting, countdown Alpine.js, 2FA check
- VoipMsService : SMS via voip.ms REST API, SmsDriverInterface + NullSmsDriver
- Settings SMS : 6 paramètres (sms_enabled, credentials voip.ms, delays, expiry)
- Settings SMTP : 5 paramètres (host, port, username, password, encryption)
- SettingsServiceProvider : applyDynamicMailConfig() au boot (override config/mail.php depuis DB)
- Admin rôles : toggle requires_password par rôle
- Admin users : champ phone, checkbox must_change_password
- ForcePasswordChange middleware + controller + vue WowDash
- Login Livewire : redirection magic link si rôle sans mot de passe, redirect force-change
- Settings manager vue : input password pour credentials, input number pour ports
- 22 tests Phase47Test (migrations, magic link, SMS, force change, SMTP, rôles)

## Phase 45 - WebSocket temps réel (Laravel Reverb)
- Laravel Reverb v1.7 installé, config publiée (config/reverb.php, config/broadcasting.php)
- routes/channels.php : canal privé App.Models.User.{id}
- .env.example : REVERB_* + VITE_REVERB_* variables ajoutées
- BROADCAST_CONNECTION=reverb (était log)
- Laravel Echo + Pusher JS installés (npm), config dans bootstrap.js
- RealTimeNotification event (ShouldBroadcast) dans Modules/Notifications/Events
- SystemAlertNotification : canal broadcast ajouté (mail + database + broadcast)
- Supervisor config : config/supervisor/reverb.conf
- 13 tests (config, event, notification, infra, channel auth)
- DatabaseSeeder : firstOrCreate → updateOrCreate pour superadmin (fix mot de passe)

## Phase 46 - PWA (Progressive Web App)
- manifest.json, service-worker.js, offline.html dans public/
- Icônes 192x192 et 512x512 dans public/icons/
- Cache-first assets statiques, network-first HTML, offline fallback
- Meta tags PWA dans 3 layouts (GosaSS, Auth, WowDash)
- Service worker registration dans tous les layouts
- 9 tests (fichiers, manifest, meta tags, SW logic)

## Phase 44 - Multi-langue contenu (spatie/laravel-translatable v6)
- 4 modèles avec HasTranslations : Article, Category, StaticPage, MetaTag
- 18 colonnes converties string/text → JSON (migration MySQL, skip SQLite)
- Index uniques droppés : articles_slug, blog_categories_name/slug, static_pages_slug
- resolveRouteBinding() overridé sur Article, Category, StaticPage (JSON-aware slug lookup)
- 9 contrôleurs/Livewire fixés : where('col', val) → where('col->'.locale, val)
- 10 tests adaptés (assertDatabaseHas → model queries, toArray() flatten pour forms)
- Pattern translatable queries : `where('column->'.app()->getLocale(), $value)`

## Phases 131-136
- **131** : Dashboard jobs échoués (FailedJobController, vue WowDash, 8 tests)
- **132** : Corbeille admin (TrashController articles+comments soft delete, 10 tests)
- **133** : Mode maintenance toggle (MaintenanceController, indicateur dashboard, 5 tests)
- **134** : Nettoyage constantes STATUS_* obsolètes (Article, Comment, Campaign - remplacées par spatie/model-states)
- **136** : Upload drag & drop WowDash (Alpine.js + Livewire, zone dashed, prévisualisation)

## Phase 127 - WowDash natif 100% + Settings UX
- Tous les Livewire tables convertis en WowDash natif : navbar-search, radius-12 h-40-px selects, bordered-table sm-table
- 0 text-muted restants dans le dossier wowdash (→ text-secondary-light)
- 0 input-group restants (→ navbar-search)
- Settings : border-gradient-tab WowDash natif, ordre Général/Courriel/SEO/Apparence
- Doublons branding.site_name/site_description éliminés (BrandingController → general group)
- Inline toggle sur 7 tables admin (Users, Categories, Plans, MetaTags, Articles, Comments, Campaigns)
- SettingsManager Livewire avec onglets, inputs typés, sauvegarde inline
- Audit Playwright : 97/100 conformité WowDash (seul text-muted vendor/pagination)

## Phase 27 - API complète production-ready
- AuthController : login/register/logout/user (Sanctum tokens)
- LoginRequest + RegisterRequest (BaseFormRequest, validation)
- Migration personal_access_tokens publiée (Sanctum)
- Filtrage/tri sur UserController via spatie/laravel-query-builder
  - Filtres : name (partial), email (exact), roles.name (exact)
  - Tri : name, email, created_at (défaut: -created_at)
  - per_page configurable (défaut 15)
- Rate limiting : api (auth 120/min, public 30/min), login (5/min), sensitive (10/min)
- Health check amélioré : vérifie DB + cache, retourne 503 si dégradé
- /status sécurisé : n'expose plus l'environnement en production
- 28 nouveaux tests (AuthApi 10, Filter 8, RateLimit 5, HealthCheck 4, superadmin 1)

## Phase 26 - nettoyage stancl/tenancy + tests modules
- Supprimé fichiers conflictuels stancl publish (migrations tenants/domains dupliquées, TenancyServiceProvider app/)
- config/cashier.php et config/tenancy.php publiés et conservés (configs package)
- routes/tenant.php publié (routes tenant stancl)
- Cashier migrations publiées (subscriptions, customer_columns)
- Tests module SaaS : BillingServiceTest (10) + PlanModelTest (11) = 21 tests
- Tests module Tenancy : TenantServiceTest (10) + TenantModelTest (10) = 20 tests
- TenantFactory::withOwner() rendu optionnel (crée User auto si absent)

## Phase 25 - polish du template
- Commande `core:new-project` interactive (configure app name, URL, DB, feature flags)
- Larastan monté niveau 5 -> 6 (ignoreErrors missingType pour fichiers auto-générés)
- CI/CD : couverture PCOV, feature branches, upload artifact
- .env.example : vars Stripe/SaaS/Tenancy ajoutées

## Architecture complète du CORE
- Laravel 12, PHP 8.4, MySQL, Pest PHP 3
- 21 modules nwidart (tous activés)
- 2 commandes artisan : `core:setup`, `core:new-project`, `make:crud`
- **API complète** : AuthController (login/register/logout), CRUD UserController (filtrage/tri), rate limiting, health check DB+cache, Scramble docs, Sanctum
- SaaS : Plan model, BillingService, Cashier v16.2, PlanSeeder (Free/Pro/Enterprise)
- Tenancy : Tenant model, TenantService, stancl/tenancy v3.9
- Monitoring : Sentry, Pulse, Telescope, Horizon
- Qualité : Larastan 6, Pint, Rector, 14 arch tests, couverture 84.9%
- 437 tests, 947 assertions

## RÈGLE : tout personnalisable via admin
- Chaque fonctionnalité doit être configurable depuis le backoffice admin
- Le moins de paramètres codés en dur possible
- Utiliser le module Settings (clé/valeur en DB) pour tout ce qui est configurable

## RÈGLES ABSOLUES
- **JAMAIS de Tailwind CSS** : le projet utilise WowDash (Bootstrap 5) + style.css custom. Tailwind entre en conflit et complique les thèmes. INTERDIT.
- **User #1 (stephane@memora.ca) = superadmin principal - JAMAIS le supprimer**
- UserPolicy::delete() bloque la suppression de l'ID 1
- DatabaseSeeder crée ce user en firstOrCreate
- Aucune commande, seeder ou migration ne doit supprimer le user #1

## Patterns importants
- Tests feature avec DB : `uses(RefreshDatabase::class)` seul
- Tests modules : `uses(Tests\TestCase::class, RefreshDatabase::class)`
- PHPStan : chemins modules EXPLICITES, level 6, missingType ignoré
- Factories modules : database/factories/ + override newFactory()
- Feature::active() nécessite BD ; Feature::defined() fonctionne sans BD
- Couverture : Xdebug Herd `-d zend_extension=...xdebug-84-arm64.so`
- Exclusions couverture : Livewire/, Backoffice/
- Middleware global DB-dependent : TOUJOURS try-catch QueryException (table peut ne pas exister en test)

## ANNY - limitations connues
- Tronque systématiquement les fichiers tests (>5 tests ou même petits fichiers)
- Validation ANNY bloque avec "Dernier test() Pest non fermé" sur troncature
- Workaround : écrire directement les fichiers tests

## Routage MCP - leçons apprises (2026-02-25)
### Conversion CSS mécanique (Tailwind → Bootstrap 5)
- **Nature** : pattern matching simple, pas de raisonnement complexe
- **Cascade correcte** : OpenRouter gratuits → OpenRouter low-cost → Haiku Task → Sonnet (dernier recours)
- **Sonnet = overkill** pour du CSS mapping mécanique. Haiku suffit largement.
- **OpenRouter observations** :
  - qwen/qwen3-coder:free : 429 fréquent en sessions longues (quota quotidien)
  - nvidia/nemotron:free : retourne VIDE pour Blade > 50 lignes
  - OpenRouter low-cost (devstral 0.05$/M, deepseek-v3.2 0.25$/M) : fallback fiable
- **Règle** : évaluer la complexité cognitive de la tâche AVANT de choisir le modèle
  - Pattern matching → Haiku ou OpenRouter gratuit
  - Raisonnement / architecture → Sonnet ou OpenRouter thinking
  - Supervision / décision → Opus uniquement

### Thème NobleUI backend (2026-02-25) - CONVERSION COMPLÈTE
- Layout admin : `/Modules/Backoffice/resources/views/themes/backend/layouts/admin.blade.php`
- @vite('resources/js/app.js') ajouté pour TipTap editor (Alpine component via alpine:init)
- Assets NobleUI : copiés de `.themes/backend_2026/template/demo1/public/build/` vers `public/build/nobleui/`
- Splash screen CSS : `public/build/nobleui/splash-screen.css` (CSS pur, sans image)
- Stacks : @push('plugin-styles'), @push('plugin-scripts'), @push('custom-scripts'), @push('styles'), @push('scripts')
- Lucide icons : data-lucide="name", global CSS sizing dans layout
- Blog module : 7 vues dans themes/backend/admin/ (articles/ + revisions/)
- **Conversion Tailwind→Bootstrap 5 TERMINÉE** : ~40 content pages + 5 components convertis
- **0 Tailwind CSS restant** dans themes/backend/ (grep vérifié)
- Patterns Bootstrap 5 cohérents : card/card-header/card-body, table table-hover, badge bg-X bg-opacity-10, d-flex/gap
- OnboardingWizard : `$dismissed` initialisé depuis DB dans mount() (fix persistence bug)

### TipTap v3 - points critiques
- StarterKit v3 INCLUT Link + Underline (contrairement à v2) → `StarterKit.configure({ link: false, underline: false })`
- `@mousedown.prevent` sur boutons toolbar préserve le focus ProseMirror (pas @click)
- Double-init guard : `el._tiptapEditor` + `window._tiptapRegistered`
- FontSize sans package séparé : TextStyle.extend() avec attribut custom fontSize

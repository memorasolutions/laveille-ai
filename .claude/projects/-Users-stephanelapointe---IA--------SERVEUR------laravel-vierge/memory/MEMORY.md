# Laravel CORE Template - Memoire projet

## REGLE #1 - DELEGATION MCP OBLIGATOIRE (JAMAIS OUBLIER)
**Opus ne fait RIEN lui-meme sauf superviser et corriger < 5 lignes (Edit).**
- Commande bash (tests, grep, status) -> Task Haiku
- Analyse code, fix tests, modifications -> Task Sonnet
- Generation de code -> multi-ai-mcp (deepseek-chat) ou OpenRouter gratuit
- Audit visuel -> Task Sonnet + Playwright MCP
- Ecriture fichiers -> Write/Edit natifs
- Recherche codebase -> Task Haiku (Explore)
**Workflow : Bash execute -> multi-ai-mcp analyse/genere -> Edit/Write ecrit.**
**PRIORITE MCP : 1min.ai (multi-ai-mcp) AVANT OpenRouter - l'utilisateur a beaucoup de credits 1min.ai.**
**Sonnet = via multi-ai-mcp (claude-sonnet-4), PAS via Task natif. Task natif = dernier recours.**

## Stack production (IMPORTANT)
- **Serveur** : cPanel + AlmaLinux + MariaDB
- **PAS de Docker** - Docker et cPanel ne font pas bon menage
- Toutes les configs (CI/CD, deploiement) doivent etre compatibles cPanel

## Etat actuel (2026-03-15)
- **3198 tests**, 6725 assertions, 0 fail (--parallel obligatoire)
- **PHPStan** : 0 erreurs niveau 6
- **38 modules actifs** (Privacy ajouté), 129+ permissions
- **32 feature flags** Laravel Pennant (sync FeatureFlagsTable complete)
- **Auteur** : MEMORA solutions - header DocBlock standardise partout
- **3 chantiers majeurs** : multi-tenant (65 tests), marketing automation (44 tests), API GraphQL (33 tests)
- **Polish P1-P8** : content versioning, scheduled publishing, URL redirects, announcements, breadcrumbs, preview
- **Media manager ameliore** (P5a/P5b/P5c) : metadonnees SEO, dossiers, WebP/compression
- **Decouplage modules** : class_exists() wrappers dans 8 fichiers (AI/Blog/Newsletter/Import/Search/Sidebar)
- **Module Privacy** : RGPD + Loi 25 + LPRPDE + ePrivacy (consent banner, legal pages, rights-request form, EFVP, cookie expiration par juridiction, re-prompt)
- **Audit release-readiness** : env, errors, indexes, queue, cache - tous OK
- **API GraphQL** : Lighthouse v6, /graphql, schema complet (12 types, 9 queries, 7 mutations), securite prod
- **0 CDN externe** dans les vues actives (polices @fontsource, libs npm local)
- **1 theme backoffice** : backend (NobleUI Bootstrap 5.3.8 SCSS Vite) - wowdash/tabler supprimes
- **Auth layouts** : guest = Authero (Vite + @fontsource/inter), user = NobleUI (@fontsource/roboto)
- **Frontend** : GoSass
- **129 permissions granulaires** (view/create/update/delete pour contenu, view/manage pour opérationnel), 4 rôles
- **RBAC granulaire** : Gate::before super_admin, middleware permission: par action CRUD sur routes, policies can(), UI rôles 100% paramétrable (checkboxes par catégorie)
- **PWA** : vite-plugin-pwa v1.2.0 + Workbox (injectManifest, 329 precache), manifest dynamique multi-tenant, Background Sync, install/update prompts, config/pwa.php, pwa:status command
- **Passkeys/WebAuthn** : spatie/laravel-passkeys (dev-support-laravel-13), @simplewebauthn/browser, bouton login, page profil gestion, 11 tests
- **Securite** : XSS purifier, CSP+HSTS headers, rate limiting, CAPTCHA, honeypot, audit logging, HIBP, Passkeys WebAuthn
- **GDPR** : export 7 tables, suppression compte + anonymisation, polices self-hosted
- **CI/CD** : GitHub Actions (quality + tests + E2E + security), Dependabot, Makefile
- Superadmin : stephane@memora.ca / Admin123!

## Architecture
- Laravel 12, PHP 8.4, Livewire 4.1 (PAS Filament), MySQL, Pest PHP 3
- 37 modules nwidart, BaseModuleServiceProvider + BaseRouteServiceProvider dans Core (36/36 convertis)
- SettingsReaderInterface dans Core (decouplage Core<->Settings)
- API REST v1 Sanctum + Scramble docs, API GraphQL v2 Lighthouse v6
- SaaS : Stripe Cashier, plans, checkout, webhooks verifies
- Feature flags : Laravel Pennant (32 flags dans AppServiceProvider, FeatureFlagSeeder, FeatureFlagsTable sync)
- Bootstrapping : core:new-project (interactif, modules optionnels, .env, feature flags)
- Notifications : 13 classes, NotificationBell Livewire, Reverb temps reel, polling 30s
- Blog, Newsletter, Pages, Editor TipTap, Search Scout
- PWA, Reverb WebSocket, Push notifications
- Module AI enrichi : OpenRouter (chat, streaming SSE, articles, moderation batch Spatie States, SEO, traduction, RAG FAQ/Pages/Articles, human takeover Reverb, analytics 4 KPI, budget check mensuel, rewrite/improve, 2 Livewire assistants, feedback thumbs, 52 tests 5 fichiers)
- Module Team : organisations multi-utilisateurs, invitations token 64 chars, trait HasTeams, middleware EnsureTeamContext, 23 tests
- Onboarding wizard Livewire, impersonation admin, activity logging Spatie
- Health monitoring /health endpoint, Pulse, Horizon
- Module Roadmap : product feedback/roadmap tool (inspiré Votlie), 33 tests

## Module Roadmap (session 2026-03-12)
- **Tables** : boards, ideas (SoftDeletes), votes (unique user+idea), idea_comments (is_official), roadmap_changelogs
- **Enum** : IdeaStatus (under_review, planned, in_progress, completed, declined) avec label() fr + color() Bootstrap
- **Services** : VotingService (toggle + hasVoted), IdeaService (create + updateStatus + merge), RoadmapAiService (categorize AI optionnel + detectDuplicates)
- **Admin** : BoardController CRUD, IdeaController (CRUD + status + merge + vote + comments), RoadmapAnalyticsController (KPIs + top ideas + par statut)
- **Frontend public** : PublicBoardController, layout Bootstrap 5.3 CDN autonome (facile à remplacer), boards index/show/kanban, vote AJAX, submit idée
- **Notifications** : IdeaStatusChanged (email queue) — envoyé à l'auteur quand statut change
- **Changelog** : Changelog model, historique dans admin idea show, log automatique sur updateStatus + merge
- **Permissions** : view_roadmap + manage_roadmap (Pattern B seeder)
- **Sidebar** : section Roadmap (Tableaux, Idées, Statistiques) avec @canany
- **AI optionnel** : RoadmapAiService utilise class_exists(\Modules\AI\Services\AiService) — fonctionne sans module AI
- **33 tests** : 4 fichiers (Admin 12, Public 9, Changelog 5, Phase5 7)

## Modules WordPress (session 2026-02-27)
- **Menu** : drag-and-drop SortableJS, cache, Blade component frontend, 14 tests
- **FAQ** : CRUD admin + page publique GoSass + JSON-LD Schema.org + purifier XSS, 15 tests
- **Contact messages** : stockage DB + admin UI (filtres, lu/non lu, detail), 12 tests
- **Homepage configurable** : landing ou page statique via Settings, 7 tests

## Session 2026-03-02 - Polish CMS (P1-P7)
- **P1 Content versioning** : HasRevisions trait (morphMany), ContentRevision model, RevisionService (max 50, compare, restore), 14 tests
- **P2 Scheduled publishing** : HasScheduledPublishing trait (configurable $publishedColumn/$publishedValue), scopes publishedNow/scheduled/expired, migration published_at/expired_at sur StaticPage/Faq/Article, 9 tests
- **P3 URL redirections** : UrlRedirect model dans SEO, exact + wildcard (Str::is), recordHit, admin CRUD dans Backoffice, 404 exception handler dans bootstrap/app.php (pas middleware), 12 tests
- **P4 User impersonation** : deja existant (ImpersonationController + 7 tests Phase121Test)
- **P6 Announcements/changelog** : Announcement model dans Core (HasScheduledPublishing + BelongsToTenant), admin CRUD, page publique /changelog (GoSass), typeLabel/typeBadgeClass/safeBody, sidebar dans Configuration, 14 tests
- **P7 Breadcrumbs dynamiques** : @yield('breadcrumbs') dans admin layout, composant multi-level, 14 vues enrichies, 5 tests
- **P5a Metadonnees SEO medias** : title, alt_text, caption, description via Spatie custom_properties (0 migration), modal Livewire, badge ALT vert, alt_text auto-injecte dans TipTap setImage, API PATCH, 9 tests
- **P5b Dossiers medias** : folder via custom_properties, filtre dropdown dynamique (DB-agnostic), badge dossier bleu, datalist autocompletion, 4 tests
- **P5c Compression/WebP** : 6 conversions (3 standard + 3 WebP), .optimize() sur toutes, config media-library publiee, version_urls=true, composant Blade <x-media::picture> avec <picture>+fallback
- **P5d Crop/focal point** : reporte (over-engineering pour CORE template)
- **P8 Preview avant publication** : routes admin preview (Blog + Pages), methode preview() bypass published check, banniere jaune fixe, bouton Apercu dans 4 vues edit, 8 tests

## Session 2026-03-02 - Chantier 2 : Marketing automation
- **5 migrations** : add_marketing_columns_to_email_templates, email_workflows, workflow_steps, workflow_enrollments, workflow_step_logs
- **4 modeles** : EmailWorkflow, WorkflowStep, WorkflowEnrollment, WorkflowStepLog (+ 4 factories)
- **EmailTemplate existant** (Notifications) : enrichi (json_content, category, tenant_id), reutilise pour marketing via module='newsletter'
- **WorkflowEngine service** : enroll, processStep, advance, cancel, processDueEnrollments, replaceVariables
- **ProcessWorkflowStep job** : queue 'workflows', retry 3x, backoff 60s
- **WorkflowTriggerListener** : ecoute Registered event, triggerWorkflows par type
- **ProcessWorkflowsCommand** : newsletter:process-workflows (schedule 5min)
- **MarketingTemplateController** : CRUD complet + preview avec variables dummy
- **WorkflowController** : CRUD + activate/pause + analytics (stats enrollments/sent/failed)
- **9 vues NobleUI** : templates (index/create/edit), workflows (index/create/edit/show)
- **Step builder JS** : ajout dynamique d'etapes (send_email/delay/condition/action)
- **Permission** : manage_workflows ajoutee au seeder
- **Sidebar** : Templates marketing + Workflows dans section Utilisateurs
- **72 tests Newsletter** (155 assertions) - tous passent
- **PHPStan** : 0 erreurs

## Session 2026-03-02 - Module Team + production hardening
- **Module Team** : 4 migrations, 3 modeles (Team, TeamInvitation, HasTeams trait), TeamService (7 methodes statiques), 2 controleurs, 4 vues NobleUI, middleware EnsureTeamContext
- **Invitations** : token 64 chars, expiration 7j, InviteMemberNotification mail, accept/decline
- **Permission** : manage_teams ajoutee au seeder, sidebar integration
- **23 tests Pest** (56 assertions) - service, HTTP, 403, modele, invitations
- **Production hardening** : security.txt RFC 9116, .env.example ameliore, meta viewport fix, @stack('custom-scripts') fix GoSass
- **PHPStan** : type hints corrigés (Team cast dans middleware/service)

## Session 2026-03-01 - Securite, GDPR, architecture
- **Password policy** : HIBP k-Anonymity check, historique anti-reutilisation, UserObserver auto, 10 tests
- **Session management UI** : voir/revoquer sessions actives dans profil admin, parseUserAgent, 7 tests
- **GDPR data export** : enrichi 3->7 tables (profile, articles, comments, sessions, login_attempts, subscriptions, ai_conversations), 8 tests
- **GDPR account deletion** : anonymisation commentaires/profil, suppression sessions/tokens/password_histories, 7 tests
- **Emails cycle abonnement** : bug fix webhook (mauvais parent call), PaymentSucceededNotification (renouvellement), 8 tests
- **Phase B1** : BaseRouteServiceProvider dans Core, 31 modules convertis, -1200 lignes duplication
- **Phase B2** : SettingsReaderInterface dans Core, DI dans SetBackofficeTheme middleware, cycle Core<->Settings resolu
- **CORE readiness** : fix validation, rate limiting newsletter, index FK, N+1 Blog, PHPStan 478 fichiers
- **Projet standard** : LICENSE MIT, CONTRIBUTING.md, CHANGELOG.md, SECURITY.md, Dependabot, E2E CI
- **API per-plan** : rate limiting dynamique par abonnement Stripe (config saas.rate_limits)
- Nettoyage : welcome.blade.php + faq.blade.php orphelins supprimes, .env.example Facebook retire

## Commandes DX
- `app:install` : setup interactif (DB, admin, Stripe, .env). `--force` pour CI/CD
- `app:demo` : donnees demo realistes. `--fresh`
- `app:status` : dashboard sante (DB, cache, queue, storage, modules, stats)
- `app:check` : validation pre-deploy (env, DB, PHPStan, tests, securite). `--quick`
- `app:make-module {Name}` : scaffolder module complet (16 fichiers)
- `app:logs` : tail colore avec filtrage par niveau, timestamps relatifs, `--clear`
- `app:setup-hooks` : git pre-commit hook (Pint + PHPStan fichiers stages)
- `app:docs` : documentation auto console + markdown (5 sections)

## Patterns importants
- Tests feature avec DB : `uses(RefreshDatabase::class)` seulement
- Tests modules : `uses(Tests\TestCase::class, RefreshDatabase::class)`
- Sidebar NobleUI : `nav.sidebar` + `ul.nav#sidebarNav` + Bootstrap collapse
- Lucide icons : `<i data-lucide="name">` auto-sized 18px par CSS global
- Admin views : `@extends('backoffice::themes.backend.layouts.admin')`
- Logout button : type="button" avec onclick (pas type="submit")
- TipTap + Alpine.js : stocker editor RAW sur el._tiptapEditor (Alpine Proxy casse ProseMirror)
- Module factories : database/factories/ + override newFactory()
- PHPStan : chemins modules EXPLICITES, level 6, missingType ignore
- Middleware global DB-dependent : TOUJOURS try-catch QueryException

## Theme NobleUI backend - reference
- **SCSS compile via Vite** : 54 fichiers source (resources/sass/nobleui/)
- **vite.config.js** : 6 entrees (app.scss, nobleui-custom.css, auth-guest.css, app.js, template.js, color-modes.js)
- **viteStaticCopy** : bootstrap, lucide, perfect-scrollbar, flag-icons, tom-select, sortablejs
- **Stacks** : @push('plugin-styles'), @push('plugin-scripts'), @push('custom-scripts'), @push('styles'), @push('scripts')
- **0 Tailwind CSS** dans themes/backend/ - Bootstrap 5.3.8 uniquement

## Bugs pre-existants connus
- OOM sur suite complete tests sequentielle (~512M) -> `php artisan test --parallel` obligatoire (16 processes, 3202 tests OK)
- `view:cache` echoue sur `emails/welcome.blade.php` (compose corrige, utilise @component)

## REGLES ABSOLUES
- **JAMAIS de Tailwind CSS dans admin** : Bootstrap 5 + NobleUI uniquement
- **User #1 (stephane@memora.ca) = superadmin principal - JAMAIS le supprimer**
- **JAMAIS migrate:fresh** - ne jamais supprimer la base de donnees
- **Tout personnalisable via admin** - Settings (cle/valeur en DB)
- **DRY strict** : jamais de code repete. Si un code apparait plus d'une fois → module reutilisable ou plugin Laravel

## Routage MCP - lecons apprises
- deepseek-chat : excellent code, toujours nettoyer style (strict_types, Pint, docblocks)
- gpt-4o-mini : rapide pour analyse/texte, trop generique pour audit technique
- Task Haiku Explore : excellent pour audits securite, scan multi-patterns
- qwen/qwen3-coder:free : 429 frequent en sessions longues
- OpenRouter low-cost (devstral 0.05$/M, deepseek-v3.2 0.25$/M) : fallback fiable

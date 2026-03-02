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

## Etat actuel (2026-03-02, apres chantiers 1-2-3 + polish P1-P7)
- **2697+ test cases** - mode parallele obligatoire
- **PHPStan** : 0 erreurs niveau 6
- **34 modules actifs**, 570+ routes, 95 migrations
- **3 chantiers majeurs** : multi-tenant (65 tests), marketing automation (44 tests), API GraphQL (33 tests)
- **Polish P1-P7** : content versioning, scheduled publishing, URL redirects, announcements, breadcrumbs
- **API GraphQL** : Lighthouse v6, /graphql, schema complet (12 types, 9 queries, 7 mutations), securite prod
- **0 CDN externe** dans les vues actives (polices @fontsource, libs npm local)
- **1 theme backoffice** : backend (NobleUI Bootstrap 5.3.8 SCSS Vite) - wowdash/tabler supprimes
- **Auth layouts** : guest = Authero (Vite + @fontsource/inter), user = NobleUI (@fontsource/roboto)
- **Frontend** : GoSass
- **43 permissions**, 4 roles (super_admin, admin, editor, user)
- **RBAC** : Gate::before super_admin, middleware permission: sur routes, policies can()
- **Securite** : XSS purifier, CSP+HSTS headers, rate limiting, CAPTCHA, honeypot, audit logging, HIBP
- **GDPR** : export 7 tables, suppression compte + anonymisation, polices self-hosted
- **CI/CD** : GitHub Actions (quality + tests + E2E + security), Dependabot, Makefile
- Superadmin : stephane@memora.ca / Admin123!

## Architecture
- Laravel 12, PHP 8.4, MySQL, Pest PHP 3
- 34 modules nwidart, BaseRouteServiceProvider dans Core (31 modules convertis)
- SettingsReaderInterface dans Core (decouplage Core<->Settings)
- API REST v1 Sanctum + Scramble docs, API GraphQL v2 Lighthouse v6
- SaaS : Stripe Cashier, plans, checkout, webhooks verifies
- Feature flags : Laravel Pennant (8 flags modules dans AppServiceProvider, FeatureFlagSeeder)
- Bootstrapping : core:new-project (interactif, modules optionnels, .env, feature flags)
- Notifications : 13 classes, NotificationBell Livewire, Reverb temps reel, polling 30s
- Blog, Newsletter, Pages, Editor TipTap, Search Scout
- PWA, Reverb WebSocket, Push notifications
- Module AI enrichi : OpenRouter (chat, streaming SSE, articles, moderation batch Spatie States, SEO, traduction, RAG FAQ/Pages/Articles, human takeover Reverb, analytics 4 KPI, budget check mensuel, rewrite/improve, 2 Livewire assistants, feedback thumbs, 52 tests 5 fichiers)
- Module Team : organisations multi-utilisateurs, invitations token 64 chars, trait HasTeams, middleware EnsureTeamContext, 23 tests
- Onboarding wizard Livewire, impersonation admin, activity logging Spatie
- Health monitoring /health endpoint, Pulse, Horizon

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
- **P5/P8** : reportes (media manager trop complexe, preview specifique par projet)

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
- OOM sur suite complete tests sequentielle (~512M) -> mode parallele obligatoire
- `view:cache` echoue sur `emails/welcome.blade.php` (compose corrige, utilise @component)

## REGLES ABSOLUES
- **JAMAIS de Tailwind CSS dans admin** : Bootstrap 5 + NobleUI uniquement
- **User #1 (stephane@memora.ca) = superadmin principal - JAMAIS le supprimer**
- **JAMAIS migrate:fresh** - ne jamais supprimer la base de donnees
- **Tout personnalisable via admin** - Settings (cle/valeur en DB)

## Routage MCP - lecons apprises
- deepseek-chat : excellent code, toujours nettoyer style (strict_types, Pint, docblocks)
- gpt-4o-mini : rapide pour analyse/texte, trop generique pour audit technique
- Task Haiku Explore : excellent pour audits securite, scan multi-patterns
- qwen/qwen3-coder:free : 429 frequent en sessions longues
- OpenRouter low-cost (devstral 0.05$/M, deepseek-v3.2 0.25$/M) : fallback fiable

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

## Etat actuel (2026-03-02, verifie)
- **2486 tests pass** (1 skip, 5016 assertions, 0 echec, mode parallele)
- **PHPStan** : 0 erreurs niveau 6 (488 fichiers)
- **34 modules actifs**, ~500 routes, 84 migrations
- **73 fonctionnalites completees** (voir PROGRESS_REPORT.md)
- **0 CDN externe** dans les vues actives (polices @fontsource, libs npm local)
- **1 theme backoffice** : backend (NobleUI Bootstrap 5.3.8 SCSS Vite) - wowdash/tabler supprimes
- **Auth layouts** : guest = Authero (Vite + @fontsource/inter), user = NobleUI (@fontsource/roboto)
- **Frontend** : GoSass
- **40 permissions**, 4 roles (super_admin, admin, editor, user)
- **RBAC** : Gate::before super_admin, middleware permission: sur routes, policies can()
- **Securite** : XSS purifier, CSP+HSTS headers, rate limiting, CAPTCHA, honeypot, audit logging, HIBP
- **GDPR** : export 7 tables, suppression compte + anonymisation, polices self-hosted
- **CI/CD** : GitHub Actions (quality + tests + E2E + security), Dependabot, Makefile
- Superadmin : stephane@memora.ca / Admin123!

## Architecture
- Laravel 12, PHP 8.4, MySQL, Pest PHP 3
- 34 modules nwidart, BaseRouteServiceProvider dans Core (31 modules convertis)
- SettingsReaderInterface dans Core (decouplage Core<->Settings)
- API REST v1 Sanctum, Scramble docs
- SaaS : Stripe Cashier, plans, checkout, webhooks verifies
- Blog, Newsletter, Pages, Editor TipTap, Search Scout
- PWA, Reverb WebSocket, Push notifications
- Module AI : OpenRouter (chat, articles, moderation, SEO, traduction)
- Onboarding wizard Livewire, impersonation admin, activity logging Spatie
- Health monitoring /health endpoint, Pulse, Horizon

## Modules WordPress (session 2026-02-27)
- **Menu** : drag-and-drop SortableJS, cache, Blade component frontend, 14 tests
- **FAQ** : CRUD admin + page publique GoSass + JSON-LD Schema.org + purifier XSS, 15 tests
- **Contact messages** : stockage DB + admin UI (filtres, lu/non lu, detail), 12 tests
- **Homepage configurable** : landing ou page statique via Settings, 7 tests

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

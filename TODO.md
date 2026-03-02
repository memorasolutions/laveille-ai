# TODO - Laravel SaaS Boilerplate

**Derniere mise a jour** : 2026-03-02
**Voir aussi** : PROGRESS_REPORT.md (rapport complet croise docs/code)
**Metriques verifiees** : 2734 test cases (0 fail), 34 modules, 585+ routes, 101 migrations, PHPStan 0 erreurs niveau 6

---

## Completes (sessions recentes)

- [x] Commit massif 4e0500c (4835 fichiers, 584K insertions)
- [x] Audit hardcode wowdash, docs obsoletes, vues orphelines (-3577 lignes)
- [x] Decouplage Core, code partage, PHPDoc API Scramble
- [x] Fix XSS critique (mews/purifier), index manquant, Queue::failing
- [x] Clone readiness (.env.example, seeder decouple, CoreSetupCommand)
- [x] DX : app:install, app:demo, app:status, app:check, app:make-module, app:logs, app:setup-hooks
- [x] CI/CD GitHub Actions (concurrency, npm audit, coverage-text)
- [x] VS Code config (extensions.json + settings.json)
- [x] Google Fonts local RGPD (GoogleFontService, 3 themes, 23 fichiers bunny.net nettoyes)
- [x] RBAC fonctionnel (47 permissions actives, Gate::before, middleware route, policies, 4 roles)
- [x] Fix tests paralleles (race condition MakeModuleCommandTest)

## Completes - Remplacement WordPress

### Critique
- [x] Menu dynamique (drag-and-drop SortableJS, cache, Blade component) - 928a915
- [x] FAQ en base de donnees (CRUD admin, page publique, JSON-LD Schema.org) - dc1fcd6
- [x] Stockage messages contact en DB (table, liste admin, filtres lu/non lu) - 0aa8cae
- [x] Homepage configurable (landing ou page statique via admin Settings) - bea7e03
- [x] Templates de pages (default, full-width, sidebar, landing - 4 templates)

### Important
- [x] Schema.org / JSON-LD (articles, pages, FAQ, organisation, breadcrumbs, WebSite)
- [x] Tags blog dedies (modele Tag, CRUD admin, page archive /blog/tag/{slug})
- [x] Temoignages (module CRUD admin + affichage frontend)
- [x] Media picker dans TipTap (browser images dans editeur, Alpine Proxy fix)

### Technique
- [x] Sidebar @can directives (masquer liens sans permission) - theme backend
- [x] Tests RBAC dedies (11 tests, 57 assertions) - sidebar + route-level 403
- [x] Nettoyage themes (wowdash/tabler supprimes, ~133 Mo liberes, 0 reference restante)
- [x] Dashboard actions rapides protegees @can (manage_users, manage_backups, manage_settings)
- [x] Layout auth guest reecrit Authero (Tailwind CSS + Preline UI + Tabler icons)
- [x] Layout auth user corrige Jobick (dashboard utilisateur fonctionnel)
- [x] Vues Livewire auth converties Bootstrap->Tailwind (login, register, forgot-password, reset-password)
- [x] Fix 16 tests casses post-nettoyage themes (assertions wowdash->backend)
- [x] Fix @push('js')->@push('scripts') vue revenue (ApexCharts rendu)
- [x] Fix Phase57 (bg-success->bg-green-500, Tailwind colors -> inline colors)
- [x] jQuery supprime des vues auth (vanilla JS)
- [x] Tests WCAG Phase188 (h1, nav landmark, aria-labels layout admin)
- [x] Harmonisation 9 vues auth design Authero (auth-* CSS, inline SVGs, 0 Bootstrap)
- [x] Guest layout split 50/50 (formulaire + hero image, responsive)
- [x] Pages legales dynamiques via Settings (mentions legales, confidentialite)
- [x] Onglet "Legal" dans SettingsManager admin
- [x] NobleUI SCSS compilation via Vite (54 fichiers source, 381 KB CSS)
- [x] Audit 140+ vues admin (0 Tailwind/WowDash/FontAwesome restant)
- [x] Settings dark mode fix (tabs, labels, TipTap toolbar)
- [x] Migration user dashboard Jobick -> NobleUI (app.blade.php + 16 vues)
- [x] Lien "Mon espace" dans header admin (profil dropdown)
- [x] Tests Phase162 + Phase86 corriges (ai-chatbot, $unreadCount)

## Completes - Modules avances et qualite

- [x] Widgets/blocs configurables (module Widget : 6 types, 3 zones, CRUD admin, drag-and-drop reorder, cache, partials frontend, 13 tests)
- [x] Form builder dynamique (module FormBuilder : CRUD forms, champs drag-and-drop, soumissions, export CSV, honeypot, rate limiting, 14 tests)
- [x] Custom fields dynamiques (module CustomFields : EAV polymorphe, trait HasCustomFields, 10 types, CRUD admin, partial Blade, 14 tests)
- [x] Module Import de donnees (CSV/Excel, OpenSpout, preview + mapping colonnes, 14 tests) - 4d661a6
- [x] Attribution auteur MEMORA solutions (1072 fichiers PHP/Blade/JS/CSS) - fdbb19c
- [x] Architecture Phase A : -25 EventServiceProvider vides, -20 master.blade.php morts, HasBulkActions->Core, scaffolder mis a jour - 614247e
- [x] Production readiness : rate limiting auth (throttle 3-10/min), ExportController ::cursor(), index blog_comments/categories - a939bb9
- [x] Validation visuelle Playwright du RBAC (4 roles x pages admin, 0 bug securite)
- [x] Tests E2E Playwright automatises (15 tests : 5 auth, 5 RBAC, 5 pages publiques)
- [x] Fix race condition TestModule en tests paralleles (0 flaky, 2391/2391 pass)
- [x] Corriger 13 erreurs PHPStan -> 0 erreurs (niveau 6, 439 fichiers)
- [x] Fix N+1 query Menu admin (withCount au lieu de count() en boucle) - 1c3a592
- [x] Index form_submissions.status ajoute (migration) - 1c3a592
- [x] Fix XSS Widget HTML (Purifier::clean) - 4dc4250
- [x] Audit Playwright Widget/CustomFields/FormBuilder (breadcrumbs, layouts 2 colonnes, empty states) - 5a25e8d
- [x] Phase 154 : email digest (newsletter:digest, DigestNotification, schedule hebdo, 8 tests)
- [x] Phase 155 : commande app:docs (documentation auto-generee, console + markdown, 5 sections, 8 tests)
- [x] Phase 158 : tests A/B (module ABTest, Experiment model, ABTestService, CRUD admin, 12 tests)
- [x] Nettoyage fichiers orphelins (welcome.blade.php, faq.blade.php supprimes)
- [x] Audit coherence routes/controleurs (528 routes, 0 cassee, 0 orpheline)
- [x] Audit securite OWASP (mass assignment fix, JsonLd XSS fix, throttle comments, 4 tests securite)
- [x] Password policy avancee (HIBP breach check, historique anti-reutilisation, observer auto, 10 tests)
- [x] Session management UI admin (voir/revoquer sessions actives dans profil admin, 7 tests)
- [x] GDPR data export enrichi (7 tables user, JSON download, throttle, 8 tests)

## Completes - CORE readiness (session 2026-03-01)

- [x] Phase B1 : BaseRouteServiceProvider in Core (31 modules convertis, -1200 lignes, 0 regression)
- [x] Phase B2 : Resoudre cycle Core<->Settings (SettingsReaderInterface dans Core, DI dans middleware)
- [x] Fix validation manquante (InlineEditController $request->validate, EditorController routes mortes supprimees)
- [x] Rate limiting newsletter subscribe (throttle:5,1)
- [x] Index FK manquants (ai_conversations.agent_id, articles.category_id)
- [x] Fix N+1 queries Blog (eager loading user+blogCategory sur recentArticles, relatedArticles)
- [x] PHPStan complete (8 modules ajoutes, 478 fichiers analyses, 0 erreurs)
- [x] Fichiers projet standard (LICENSE MIT, CONTRIBUTING.md, CHANGELOG.md, SECURITY.md)
- [x] Rate limiting API per-plan Stripe (config saas.rate_limits, dynamique par subscription)
- [x] Fix .env.example (Facebook OAuth retire - non supporte)
- [x] Playwright E2E dans CI (job conditionnel PR, chromium, seed-e2e-users)
- [x] Makefile : make e2e, make setup-hooks
- [x] Dependabot config (.github/dependabot.yml - composer, npm, github-actions)

## Completes - Zero CDN / RGPD (session 2026-03-02)

- [x] Google Fonts Inter -> @fontsource/inter (auth guest layout, RGPD)
- [x] Google Fonts Roboto -> @fontsource/roboto (admin + auth app layouts, RGPD)
- [x] Tom Select CDN -> npm + viteStaticCopy (4 fichiers Blog)
- [x] Sortable.js CDN -> npm + viteStaticCopy (4 fichiers admin)
- [x] Auth guest CSS inline (400 lignes) -> resources/css/auth-guest.css via Vite
- [x] 0 CDN externe dans les vues actives (sauf branding editor = fonctionnalite)

## Completes - Module Team (session 2026-03-02)

- [x] Module Team complet (4 migrations, 3 modeles, TeamService, 2 controleurs, 4 vues NobleUI)
- [x] Systeme invitations (token 64 chars, expiration 7j, notification mail, accept/decline)
- [x] Trait HasTeams sur User (teams, currentTeam, switchTeam, belongsToTeam)
- [x] Middleware EnsureTeamContext (auto-set team context)
- [x] Permission manage_teams + sidebar integration
- [x] 23 tests Pest (56 assertions) - TeamService, routes HTTP, permissions 403, modele, invitations
- [x] PHPStan 0 erreurs (type hints corrigés)
- [x] Production hardening (security.txt RFC 9116, .env.example, meta viewport fix, @stack custom-scripts)

## Completes - Systeme IA enrichi (session 2026-03-02)

- [x] Admin conversations IA (liste paginee, filtres status/user/date, detail messages)
- [x] Human takeover temps reel (claim, reply, close, release, Reverb broadcast)
- [x] Chatbot SSE streaming reel (AiService streamChat + ChatStreamController)
- [x] RAG service (FAQ + Pages + Articles injectes dans system prompt chatbot)
- [x] Chatbot Livewire ameliore (SSE + markdown + bouton "parler a un humain")
- [x] Feedback thumbs up/down (migration + API MessageFeedbackController)
- [x] AiContentAssistant Livewire (rewrite, improve, summarize, translate)
- [x] AiSeoAssistant Livewire (generation meta SEO)
- [x] Moderation batch commentaires (Spatie ModelStates, approve/spam en lot)
- [x] Dashboard analytics IA (4 KPI, activite/jour, usage par modele, feedback)
- [x] Budget mensuel IA (checkBudget() avant chaque requete API)
- [x] Permission manage_ai + sidebar section Intelligence artificielle
- [x] 52 tests IA (5 fichiers PhaseAi*, 79 assertions, tous pass)
- [x] PHPStan 0 erreurs (streamChat fix, channels.php fix)

---

## Completes - Multi-tenant avance (session 2026-03-02)

- [x] Trait BelongsToTenant + TenantScope (auto-set tenant_id, global scope filtrage, withoutTenancy, forTenant)
- [x] Migration add_tenant_id a 15 tables (articles, faqs, teams, subscribers, campaigns, etc.)
- [x] 3 middlewares (IdentifyTenant subdomain/header/session, EnsureTenantAccess 403, TenantDomainResolver FQDN)
- [x] Admin CRUD Tenants (TenantController, 4 vues NobleUI, routes permission:manage_tenants)
- [x] BelongsToTenant applique a 14 modeles (Article, Faq, Subscriber, Campaign, Team, etc.)
- [x] 65 tests Tenancy (153 assertions), PHPStan 0 erreurs

## Completes - Marketing automation (session 2026-03-02)

- [x] 5 migrations (email_templates enrichi, email_workflows, workflow_steps, workflow_enrollments, workflow_step_logs)
- [x] 4 modeles + 4 factories (EmailWorkflow, WorkflowStep, WorkflowEnrollment, WorkflowStepLog)
- [x] WorkflowEngine service (enroll, processStep, advance, cancel, processDueEnrollments)
- [x] ProcessWorkflowStep job (queue, retry 3x) + WorkflowTriggerListener (Registered event)
- [x] ProcessWorkflowsCommand (newsletter:process-workflows schedule 5min)
- [x] MarketingTemplateController CRUD (7 routes, 3 vues, preview variables)
- [x] WorkflowController CRUD (9 routes, 4 vues, step builder JS, activate/pause, analytics)
- [x] Permission manage_workflows + sidebar Templates marketing + Workflows
- [x] 44 nouveaux tests (72 total Newsletter, 155 assertions), PHPStan 0 erreurs

## Completes - 3 chantiers majeurs (session 2026-03-02)

- [x] Multi-tenant avance (isolation donnees, domaines custom) - 65 tests, 3 middlewares, 15 tables
- [x] Marketing automation (workflows, drip campaigns) - 44 tests, WorkflowEngine, CRUD admin
- [x] API v2 GraphQL (Lighthouse v6, 33 tests, securite prod) - schema complet, mutations RBAC
- [ ] Migration Modules/ vers plugins/ - REPORTE (34 modules + 2657 tests a risque, valeur = 0)

## Suggestions benchmark (features manquantes identifiees)

- [x] Password policy (complexite, HIBP breach check, historique) - FAIT
- [x] Session management UI (voir/revoquer sessions actives) - FAIT
- [x] GDPR data export (telechargement donnees personnelles JSON) - FAIT (enrichi 3->7 tables)
- [x] GDPR suppression complete (anonymisation commentaires, sessions, tokens, profil avant delete, 7 tests) - FAIT
- [x] Emails cycle abonnement (trial ending, payment failed, renewal success, cancelled) - FAIT (bug webhook fix + PaymentSucceededNotification, 8 tests)
- [x] Corbeille admin (recycle bin centralise soft deletes) - EXISTAIT DEJA (TrashController)
- [x] Failed jobs admin UI (view, retry, delete) - EXISTAIT DEJA (Phase 174)

---

## Restant - CORE reutilisable

### Analyse gaps (effectuee 2026-03-02)
Le CORE est fonctionnellement complet. Les gaps identifies (observabilite, data residency, analytics self-service, real-time collab, IaC Docker) sont tous du over-engineering pour un template reutilisable. Les features critiques (Project Factory CLI, feature flags Pennant, notifications unifiees) EXISTENT DEJA.

### Taches restantes (par priorite)
- [x] Fix test Phase161Test (toHaveCount 11 → 18)
- [x] Mise a jour README.md badges et metriques (2734+ tests, 34 modules, GraphQL v2, marketing, multi-tenant)
- [x] Mise a jour CHANGELOG.md avec chantiers 1-3 (multi-tenant, marketing, GraphQL) → v1.1.0
- [x] Enrichir core:new-project avec categories de modules (Core/Business/Avance, 20 feature flags)
- [x] 7 tests NewProjectCommand (constantes, defaults, scenarios)
- [x] Decouplage inter-modules (class_exists() wrappers : 8 fichiers, AI/Blog/Newsletter/Import/Search/Sidebar)
- [x] Audit release-readiness (env, errors, indexes, queue, cache - tous OK)
- [ ] Migration Modules/ vers plugins/ - REPORTE indefiniment (valeur = 0)
- [ ] Support Paddle/Lemon Squeezy - REPORTE (over-engineering, Stripe domine 80%+ marche SaaS)
- [ ] Roadmap public avec votes - REPORTE (outils externes Canny/Productboard font mieux)

## Completes - Polish CMS (session 2026-03-02)

- [x] P1 : Content versioning (HasRevisions trait, ContentRevision model, RevisionService, diff/restore, 14 tests)
- [x] P2 : Scheduled publishing (HasScheduledPublishing trait sur StaticPage/Faq/Article, scopes publishedNow/scheduled/expired, 9 tests)
- [x] P3 : URL redirections manager (UrlRedirect model dans SEO, exact + wildcard, admin CRUD, 404 handler, 12 tests)
- [x] P6 : Announcements/changelog (Announcement model dans Core, admin CRUD, page publique /changelog, sidebar, 14 tests)
- [x] P7 : Breadcrumbs dynamiques (@yield dans layout, composant multi-level, 14 vues enrichies, 5 tests)
- [x] P4 : User impersonation - existait deja (ImpersonationController + 7 tests)
- [x] P5a : Metadonnees SEO medias (titre, alt_text, legende, description via custom_properties, modal Livewire, 9 tests)
- [x] P5b : Dossiers medias (folder via custom_properties, filtre dropdown DB-agnostic, badge bleu, 4 tests)
- [x] P5c : Compression WebP (6 conversions, optimize, composant picture, version_urls)
- [x] P8 : Preview avant publication (routes admin, methode preview(), banniere jaune, bouton Apercu, 8 tests)

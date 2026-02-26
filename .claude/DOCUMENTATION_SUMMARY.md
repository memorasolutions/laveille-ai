# Laravel CORE Template - Documentation Summary
Generated: 2026-02-23

---

## 1. FILES INVENTORY

### Root Level Documentation Files
| File | Lines | Purpose |
|------|-------|---------|
| **README.md** | ~200+ | Main project documentation (installation, modules, features, API) |
| **MIGRATION_PLAN.md** | 160 | Detailed plan to migrate from Modules → plugins architecture |
| **AUDIT_REPORT.md** | 162 | Code audit results, debt analysis, recommendations |
| **.devis/PROGRESS.md** | 150+ | Phase-by-phase progress tracking (Phases 0-153) |
| **.devis/predevis-01.md** | 80+ | Package list and technology decisions |
| **.claude/ROADMAP.md** | 51 | Development roadmap and future phases (154-158) |
| **.claude/SESSION_STATE.md** | 33 | Current session state for multi-language translatable implementation |
| **.claude/MCP_NOTES.md** | 47 | MCP routing observations and model performance benchmarks |
| **modules_statuses.json** | 26 | JSON status of all 24 modules (all enabled: true) |

---

## 2. PROJECT OVERVIEW

### Current State (2026-02-23)
- **Framework**: Laravel 12.51.0
- **PHP Version**: 8.3+
- **Database**: MySQL/MariaDB 10.5+
- **Test Suite**: Pest PHP v3 (1655 tests, 3128 assertions, 100% pass)
- **Code Quality**:
  - Larastan: 0 errors (level 6)
  - Pint: 100% pass
- **Modules**: 24 total, all enabled
- **Completed Phases**: 0-153

### Architecture
- **Modular Structure**: nwidart/laravel-modules
- **Frontend**: Vite 7 + Tailwind CSS 4 + Alpine.js 3
- **Admin Panel**: Filament v5 (replacing legacy Backpack/Tabler)
- **API**: REST v1 (Sanctum auth + Scramble docs)
- **Frontend Theme**: GoSass (multi-theme support via qirolab/laravel-themer)

---

## 3. THE 24 MODULES

### Foundational Modules (7)
| Module | Status | Dependencies | Purpose |
|--------|--------|--------------|---------|
| **Core** | ✓ | None | Contracts, events, traits, helpers, middleware, security |
| **Auth** | ✓ | Core, Settings | Login/register (Livewire), 2FA TOTP, magic links, sessions, API tokens |
| **RolesPermissions** | ✓ | Core | RBAC (Spatie), 29 permissions, 4 roles (super_admin, admin, user, guest) |
| **Settings** | ✓ | Core | DB-backed config, facade, cache |
| **Logging** | ✓ | Core | Activity log (Spatie), audit trail |
| **Health** | ✓ | Core | Health checks (DB, disk, debug mode) |
| **Storage** | ✓ | Core | File storage abstraction (local, s3) |

### Content & Media (5)
| Module | Status | Dependencies | Purpose |
|--------|--------|--------------|---------|
| **Blog** | ✓ | Core | Articles, categories, comments (guest/auth), RSS feed, state machine |
| **Newsletter** | ✓ | Core | Subscribers, campaigns, digest emails, state machine |
| **Media** | ✓ | Core | Spatie MediaLibrary (uploads, conversions, collections) |
| **SEO** | ✓ | Core, Blog | Meta tags, sitemap, robots.txt, Open Graph |
| **Pages** | ✓ | Core | Static pages editor (CMS-like) |

### API & Integrations (3)
| Module | Status | Dependencies | Purpose |
|--------|--------|--------------|---------|
| **Api** | ✓ | Core, Auth | REST v1 (endpoints, resources, Scramble docs) |
| **Notifications** | ✓ | Core, Settings | Email, SMS (VoipMs), push (WebPush), broadcast (Reverb) |
| **Webhooks** | ✓ | Core | Send/receive webhooks (Spatie), signatures, retry logic |

### Backoffice (8)
| Module | Status | Dependencies | Purpose |
|--------|--------|--------------|---------|
| **Backoffice** | ✓ | Core, Auth, Blog, SaaS, Settings, etc. | Admin panel (WowDash Bootstrap 5 theme, 32 controllers, 17 Livewire) |
| **SaaS** | ✓ | Core | Plans, billing (Stripe/Cashier), subscriptions, checkout |
| **Tenancy** | ✓ | Core | Multi-tenant (Stancl), subdomain routing |
| **Backup** | ✓ | Core | Spatie Backup (DB + files), scheduling |
| **Translation** | ✓ | Core | i18n, dynamic translations |
| **Export** | ✓ | Core | CSV, Excel (OpenSpout), PDF (DomPDF) |
| **Search** | ✓ | Core | Laravel Scout (full-text), searchable models |
| **Editor** | ✓ | Core | TipTap rich text editor (for articles, pages) |

### Frontend & Utilities (2)
| Module | Status | Dependencies | Purpose |
|--------|--------|--------------|---------|
| **FrontTheme** | ✓ | Core | Theme management, views, helpers |
| **AI** | ✓ | Core | AI integrations (new module, not yet in PROGRESS.md) |

---

## 4. KEY FINDINGS FROM AUDIT

### Strengths
✓ Solid test coverage (1655 tests, 100% pass)
✓ Clean code quality (Larastan 0 errors, Pint 100%)
✓ Well-structured modules (clear separation of concerns)
✓ Modern stack (Laravel 12, Livewire 4, Alpine 3, Tailwind 4)

### Technical Debt (19 issues identified)

#### High Priority
| # | Issue | Files | Recommendation |
|---|-------|-------|-----------------|
| 1 | **21 vues dupliquées** | Backoffice/resources/views/ (old) vs themes/wowdash/ (new) | Delete old views |
| 2 | **ExportService ignoré** | Export/ vs Backoffice/ExportController | Inject ExportService |
| 3 | **StoreUserRequest dupliqué** | Auth/ vs Backoffice/ | Backoffice imports Auth's version |

#### Medium Priority
| # | Issue | Files | Recommendation |
|---|-------|-------|-----------------|
| 4 | **Tags parsing (4x)** | UserArticleController, ArticleController | Extract `ParsesTags` trait |
| 5 | **Password confirmation (3x)** | UserDashboardController, UserSessionController, TwoFactorProfileController | `VerifiesPassword` trait |
| 6 | **Validation inline** | PlanController, SeoController | Create FormRequests |
| 7 | **4 empty controller stubs** | Search/, Translation/, Backup/, Export/ | Remove or implement |

#### Low Priority
| # | Issue | Files | Recommendation |
|---|-------|-------|-----------------|
| 8 | **3 identical policies** | PlanPolicy, SettingPolicy, ArticlePolicy | Abstract `AdminOnlyPolicy` |
| 9 | **CrudService unused** | Core/Services/CrudService.php | Adopt or delete |
| 10 | **Pagination inconsistency** | N controllers (10-30 values) | Config constant |

### Circular Dependencies (4)
1. Core ↔ Auth (BlockedIp model)
2. Backoffice ↔ Blog (middleware EnsureIsAdmin)
3. Backoffice ↔ Newsletter (middleware EnsureIsAdmin)
4. Backoffice ↔ Pages (middleware EnsureIsAdmin)

**Root cause**: `EnsureIsAdmin` middleware in Backoffice but used by Blog, Newsletter, Pages
**Solution**: Move to Core

---

## 5. MIGRATION PLAN (7 Phases)

### Current Status
Decision made: **Keep nwidart/laravel-modules, add plugin.json metadata**

### Phase 1: Cleanup (LOW RISK)
- [ ] Delete 21 duplicate views
- [ ] Delete CrudService
- [ ] Delete 4 empty controller stubs
- [ ] Delete DupeModelFactory, TestModelFactory (verify tests)

### Phase 2: Core Decoupling (MEDIUM RISK)
- [ ] Move `EnsureIsAdmin` middleware → Core/Http/Middleware/
- [ ] Move BlockedIp model considerations
- [ ] Move Feature flags → Core or dedicated module
- **Validation**: All tests pass + auth routes functional

### Phase 3: Shared Code Extraction (MEDIUM RISK)
- [ ] Create `Core/Shared/Traits/ParsesTags`
- [ ] Create `Core/Shared/Traits/VerifiesPassword`
- [ ] Create `Core/Shared/Policies/AdminOnlyPolicy` (abstract)
- **Validation**: Unit + integration tests

### Phase 4: Duplication Elimination (MEDIUM RISK)
- [ ] Merge FormRequests (Auth imports → Backoffice)
- [ ] Inject ExportService into Backoffice
- [ ] Unify ArticleController (use ArticlePolicy)
- [ ] Create missing FormRequests (Plans, SEO)
- **Validation**: phpunit + phpstan + pint

### Phase 5: Rename Modules → plugins (HIGH RISK)
- [ ] Rename directory Modules/ → plugins/
- [ ] Update config/modules.php
- [ ] Update composer.json autoload
- [ ] `composer dump-autoload`
- **Validation**: 1368+ tests pass

### Phase 6: plugin.json + Validation (LOW RISK)
- [ ] Create plugin.json in each module (24 files)
- [ ] Implement PluginServiceProvider (validates dependencies)
- [ ] Register in config/app.php
- **Validation**: `php artisan serve` + tests

### Phase 7: PluginManager UI (LOW RISK)
- [ ] Create PluginManager module
- [ ] Interface: list, toggle enable/disable, show dependencies
- [ ] Admin dashboard integration
- [ ] Validation: no circular dependency activation
- **Validation**: Feature tests + Playwright audit

**Overall Effort**: 50-80 hours estimated

---

## 6. CURRENT SESSION STATE

### What's Done (Translatable Multi-Language - Phase 44)
✓ Migration `2026_02_20_100000_make_columns_translatable.php` created & applied
✓ 4 models updated with HasTranslations trait:
  - Blog/Article (title, slug, content, excerpt)
  - Blog/Category (name, slug, description)
  - Pages/StaticPage (title, slug, content, excerpt, meta_title, meta_description)
  - SEO/MetaTag (title, description, keywords, og_title, og_description)
✓ Migration works (data wrapping: plain text → {"en":"value"})
✓ MySQL prod applied; SQLite compatible

### What's Remaining
⚠ **54 tests failing** because:
  - `assertDatabaseHas` looks for plain text but columns now contain JSON
  - Example: `assertDatabaseHas('seo_meta_tags', ['title' => 'Accueil'])` fails
  - Stored value is `{"en":"Accueil"}`

**Fix required**:
1. Adapt `assertDatabaseHas` for translatable columns OR
2. Use Eloquent models instead of direct DB checks
3. Create specific tests for multi-language behavior
4. Verify Blade views still work (should via HasTranslations override)

**Commands**:
- `php artisan test` → 54 failed, 1162 passed
- MySQL test DB: `laravel_core_testing` (not used, tests on SQLite)

---

## 7. ROADMAP (Phases 154-158+)

### Completed (Phases 145-153)
| Phase | Description | Tests |
|-------|-------------|-------|
| 145 | GDPR cookie preferences (granular) | 17 |
| 146 | Onboarding wizard | 13 |
| 147 | Article revisions (diff LCS, dropdown) | 20 |
| 149 | API endpoints (blog, articles, plans, profile, notifications) | 38 |
| 150 | Push notifications (profile toggle, admin) | 17 |
| 151 | Full-text search (Scout, 5 models) | 18 |
| 152 | Webhooks retry/signature | 21 |
| 153 | Analytics advanced (4 endpoints) | 18 |

### Planned
| Phase | Status | Description |
|-------|--------|-------------|
| 154 | Planned | Email digest (notification summaries) |
| 155 | Planned | API documentation (README + Scramble) |
| 156 | Planned | Teams/multi-tenant completion |
| 157 | Planned | Marketing automation advanced |
| 158 | Planned | A/B testing integrated |

---

## 8. MCP OBSERVATIONS & ROUTING

### Best Performing Models (OpenRouter gratuit)
| Model | Strength | Use Case | Issue |
|-------|----------|----------|-------|
| **nvidia/nemotron-3-nano-30b:free** | Pest v3 tests | Boilerplate tests | Empty responses if prompt > 500 tokens |
| **qwen/qwen3-coder:free** | Excellent code quality | CRUD, services | 429 frequently (quota exhaustion) |
| **deepseek/deepseek-v3.2-20251201** (0.25$/M) | Pest v3 + modules | Tests, factories | **BEST rapport qualité/prix** |
| **moonshotai/mimo-v2-flash:free** | SWE-Bench 73.4% | Short debugging | 400 error if prompt > 300 tokens |

### Observations
- Models fail when prompts exceed 300-500 tokens
- API-specific library code: read vendor source first, don't rely on models
- Session saturation: all free models can go 429 simultaneously
- Fallback: `openrouter/free` (auto-select, but may return PHPUnit instead of Pest)

---

## 9. TECHNOLOGY STACK

### PHP Backend
- Laravel 12.51.0
- PHP 8.3+
- Composer 2.5+

### Database
- MySQL 8.0+ / MariaDB 10.5+
- Spatie Translatable (multi-language)
- Spatie Activity Log (audit trail)

### Frontend
- Vite 7 (build tool)
- Tailwind CSS 4 (utility framework)
- Alpine.js 3 (lightweight reactivity)
- Livewire 4.1 (full-stack components)
- Bootstrap 5 (admin panel WowDash theme)
- TipTap (rich text editor)

### Testing
- Pest PHP v3.8 (modern test framework)
- PHPUnit (underlying)
- Pest Architecture & Feature tests

### Key Packages
**Auth & Security**
- Laravel Sanctum (API auth)
- Spatie Permissions (RBAC)
- Laravel Pennant (feature flags)

**Content & Media**
- Spatie MediaLibrary (file management)
- Spatie Translatable (multi-language columns)
- Spatie Model States (state machine)

**Admin & Tools**
- Filament Admin (v5, dashboard)
- Spatie Health (monitoring)
- Spatie Backup (DB + file backup)
- Spatie Query Builder (API filtering/sorting)
- Laravel Scout (full-text search)

**Billing & SaaS**
- Laravel Cashier Stripe (subscriptions)
- Stancl Tenancy (multi-tenant)

**Notifications & Webhooks**
- Laravel Notifications (mail, SMS, push, broadcast)
- Spatie Webhook Server & Client
- Laravel Reverb (WebSocket broadcast)

**i18n & Localization**
- Spatie Translatable (model columns)
- Laravel native translations (files)

---

## 10. QUICK REFERENCE

### Command Examples
```bash
# Create new module
php artisan make:module ModuleName

# Enable/disable module
php artisan module:enable ModuleName
php artisan module:disable ModuleName

# Generate CRUD for a model
php artisan make:crud Blog Article  # Creates ArticleCrudService

# Run tests
php artisan test                    # All tests
php artisan test tests/Feature/Phase40Test.php  # Specific test
pest tests/Feature                  # Pest-specific runner

# Code quality
./vendor/bin/phpstan analyse        # Type checking
./vendor/bin/pint                   # Linting

# Database
php artisan migrate                 # Run migrations
php artisan migrate:rollback        # Rollback last batch
php artisan seed                    # Run seeders
php artisan fresh --seed            # ⚠ NEVER (wipes data)

# Development
php artisan serve                   # Start dev server
npm run dev                         # Vite dev server
```

### File Structure
```
laravel_vierge/
├── Modules/                        # 24 nwidart modules
│   ├── Core/
│   ├── Auth/
│   ├── Blog/
│   ├── ... (21 more)
│   └── AI/
├── app/                            # Main app code (User model, migrations, seeders)
├── config/                         # Configuration files
├── database/                       # Migrations & seeders
├── routes/                         # Web, API, console, tenant routes
├── resources/                      # Views, CSS, JS (shared)
├── tests/                          # Test suite (Feature, Unit, Architecture)
├── public/                         # Static assets
├── storage/                        # Logs, cache, uploads
├── bootstrap/                      # Bootstrap files
├── .github/workflows/              # CI/CD (GitHub Actions)
├── .claude/                        # Claude Code notes & roadmap
├── .devis/                         # Project progress & planning
├── modules_statuses.json           # Module enable/disable flags
├── phpunit.xml                     # Test configuration
├── phpstan.neon                    # Type checking config
├── pint.json                       # Code style config
└── composer.json / package.json    # Dependencies
```

---

## 11. SUMMARY TABLE

| Aspect | Status | Details |
|--------|--------|---------|
| **Tests** | ✓ 100% | 1655 tests, 3128 assertions passing |
| **Code Quality** | ✓ 100% | Larastan 0 errors, Pint 100% pass |
| **Modules** | ✓ 24 | All enabled, nwidart-based |
| **Architecture** | ⚠ In Refactor | Phase 1-4 needed (cleanup, decouple) |
| **Documentation** | ✓ Complete | README, MIGRATION_PLAN, AUDIT_REPORT |
| **Multi-Language** | ⚠ Partial | Migration done, 54 tests failing (fix needed) |
| **Admin Panel** | ✓ Complete | Filament v5, WowDash theme |
| **API** | ✓ Complete | REST v1, Sanctum, Scramble docs |
| **Blog** | ✓ Complete | Articles, categories, comments, RSS |
| **SaaS** | ✓ Complete | Stripe billing, multi-tenant, plans |

---

## 12. NEXT IMMEDIATE ACTIONS

### Priority 1: Fix Multi-Language Tests
- [ ] Adapt 54 failing tests for translatable columns
- [ ] Or: Switch to Eloquent model assertions instead of assertDatabaseHas

### Priority 2: Phase 1 Cleanup
- [ ] Delete 21 duplicate views (Backoffice old)
- [ ] Delete CrudService (Core)
- [ ] Delete 4 empty controller stubs
- [ ] Validate tests still pass

### Priority 3: Phase 2 Core Decoupling
- [ ] Move EnsureIsAdmin → Core middleware
- [ ] Move Feature flags → Core or new module
- [ ] Update all imports across modules
- [ ] Run full test suite

### Priority 4: Complete Remaining Phases
- [ ] Phase 3-5: Extraction, deduplication, renaming
- [ ] Target: Get to Phase 6 (plugin.json) + Phase 7 (PluginManager UI)

---

**Last Updated**: 2026-02-23
**Files Analyzed**: 9 documentation files
**Total Lines Read**: ~1000+ lines

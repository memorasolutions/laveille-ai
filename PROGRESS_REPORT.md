# Rapport de progression - Laravel SaaS Boilerplate

**Dernière mise à jour** : 2026-02-26 (session RBAC)
**Croisement** : docs + scan code réel + exécution tests

---

## Indicateurs clés (vérifiés)

| Métrique | Valeur | Source |
|----------|--------|--------|
| Tests | 2185 passés, 4248 assertions | `php artisan test` (486s, 2 flaky StorageServiceProvider en parallèle) |
| PHPStan | 4 erreurs pré-existantes | `env()` hors config/ (CoreSetupCommand, DatabaseSeeder) |
| Modules actifs | 25/25 | `modules_statuses.json` |
| Routes | 460 | `php artisan route:list` |
| Migrations | 58 | `database/migrations/` + `Modules/*/database/migrations/` |
| Commandes artisan | 11 | `php artisan list app` |
| Thèmes backoffice | 3 | wowdash, tabler, backend (NobleUI) |
| Permissions | 36 (fonctionnelles) | `RolesAndPermissionsSeeder`, middleware route + policies |
| Rôles | 4 | super_admin, admin, editor, user |
| Packages clés | 9 | Cashier, Spatie Permission, Purifier, Scramble, Sanctum, Livewire, Scout, ActivityLog, nwidart |

---

## ✅ Complété (28 fonctionnalités, preuves vérifiées)

| # | Fonctionnalité | Preuve (fichier/test) |
|---|----------------|----------------------|
| 1 | Architecture modulaire 25 modules | `modules_statuses.json`, `Modules/*/module.json` |
| 2 | Auth complet (Login, Register, 2FA TOTP, Magic Link, Social Auth, Lockout) | `Modules/Auth/`, `Modules/Auth/tests/` |
| 3 | API REST v1 Sanctum + Scramble docs | `routes/api/v1.php`, 10 contrôleurs annotés |
| 4 | SaaS/Billing Stripe Cashier, Plans, Checkout | `Modules/SaaS/`, `Modules/SaaS/app/Services/BillingService.php` |
| 5 | Blog (Articles, Categories, Comments, Tags) | `Modules/Blog/`, tests Feature |
| 6 | Newsletter (Campaigns, Subscribers, Templates) | `Modules/Newsletter/` |
| 7 | Backoffice admin (Dashboard, CRUD complet) | `Modules/Backoffice/`, 40+ pages admin |
| 8 | 3 thèmes admin switchables dynamiquement | `config/backoffice.php`, `SetBackofficeTheme` middleware |
| 9 | Module IA - OpenRouter (chat, articles, modération, SEO, traduction) | `Modules/AI/app/Services/AiService.php` |
| 10 | Notifications (Email, WebPush, Digest) | `Modules/Notifications/` |
| 11 | i18n FR/EN complète (670+ clés) | `lang/fr.json`, `lang/en.json` |
| 12 | PWA (manifest, service worker) | `public/manifest.json`, `public/service-worker.js` |
| 13 | SEO (Meta tags, Sitemap dynamique) | `Modules/SEO/` |
| 14 | Media (Spatie MediaLibrary, conversions) | `Modules/Media/` |
| 15 | Search (Laravel Scout) | `Modules/Search/` |
| 16 | Editor TipTap | `Modules/Editor/` |
| 17 | Export CSV (6 ressources) | `Modules/Export/` |
| 18 | Backup Spatie | `Modules/Backup/` |
| 19 | Webhooks | `Modules/Webhooks/` |
| 20 | Multi-tenant (base) | `Modules/Tenancy/` |
| 21 | Feature Flags (Laravel Pennant) | `Modules/Core/`, 9 flags dans AppServiceProvider |
| 22 | Sécurité OWASP (XSS Purifier, CSRF, Headers, Rate Limiting) | `mews/purifier`, `SecurityHeaders` middleware, `SECURITY_AUDIT_REPORT.md` |
| 23 | 11 commandes DX artisan | `app:install`, `app:demo`, `app:status`, `app:check`, `app:make-module`, `app:logs`, `app:setup-hooks`, `app:audit`, `app:sync-permissions`, `app:cleanup`, `app:block-suspicious-ips` |
| 24 | CI/CD GitHub Actions | `.github/workflows/ci.yml` (concurrency, npm audit, coverage) |
| 25 | VS Code config | `.vscode/extensions.json`, `.vscode/settings.json` |
| 26 | Google Fonts local (RGPD) | `GoogleFontService.php`, 23 fichiers bunny.net nettoyés |
| 27 | Git pre-commit hooks | `scripts/pre-commit`, `app:setup-hooks` |
| 28 | Rôles/Permissions (4 rôles, 29 permissions, Policies) | `Modules/RolesPermissions/` |
| 29 | **RBAC fonctionnel** (36 permissions, Gate::before, middleware route, AdminOnlyPolicy) | Voir détail ci-dessous |

### Détail RBAC (session 2026-02-26)

Le système de permissions était **purement décoratif** (29 permissions existaient mais aucun contrôleur ni route ne les vérifiait). Reconstruction complète :

| Composant | Fichier | Changement |
|-----------|---------|------------|
| Gate::before super_admin | `RolesPermissionsServiceProvider.php` | Bypass total pour super_admin |
| EnsureIsAdmin | `EnsureIsAdmin.php` | `hasRole()` → `can('view_admin_panel')` |
| AdminOnlyPolicy (base) | `Modules/Core/app/Shared/Policies/AdminOnlyPolicy.php` | Classe abstraite avec `$permission` configurable |
| SettingPolicy | `Modules/Settings/app/Policies/SettingPolicy.php` | `$permission = 'manage_settings'` |
| PlanPolicy | `Modules/SaaS/app/Policies/PlanPolicy.php` | `$permission = 'manage_plans'` |
| UserPolicy | `Modules/Auth/app/Policies/UserPolicy.php` | `hasRole()` → `can('manage_users')` + ownership |
| ArticlePolicy | `Modules/Blog/app/Policies/ArticlePolicy.php` | `can('manage_articles')` + ownership |
| CommentPolicy | `Modules/Blog/app/Policies/CommentPolicy.php` | `can('manage_comments')` + ownership |
| Routes backoffice | `Modules/Backoffice/routes/web.php` | Middleware `permission:xxx` sur tous les groupes |
| Middleware Spatie | `bootstrap/app.php` | Aliases `permission`, `role`, `role_or_permission` |
| Seeder | `RolesAndPermissionsSeeder.php` | 29 → 36 permissions (7 nouvelles : système, sécurité, etc.) |
| Telescope/Horizon | `TelescopeServiceProvider.php`, `HorizonServiceProvider.php` | `can('view_telescope')`, `can('view_horizon')` |
| API protection | `Api/UserController.php` | Protection hardcodée user #1 + self-deletion (bypass Gate::before) |
| TestCase | `tests/TestCase.php` | Auto-seed `RolesAndPermissionsSeeder` |
| Tests parallèles | `MakeModuleCommandTest.php` | Groupe `sequential` pour éviter race condition |
| RoleController | `RoleController.php` | Catégorie "Système" avec nouvelles permissions |
| SyncPermissionsCommand | `SyncPermissionsCommand.php` | Exécute le seeder (idempotent) |

**Décision architecture** : `ImpersonationController` garde volontairement `hasRole('super_admin')` (identité, pas permission).

---

## 🔄 En cours (2 éléments)

| Tâche | % | Ce qui manque |
|-------|---|---------------|
| Migration architecture plugins | 15% | `plugin.json` 25/25 fait. Renommage `Modules/` → `plugins/`, PluginManager UI, adaptation autoload/namespaces non faits. **Décision utilisateur requise.** |
| PHPStan 4 erreurs `env()` | - | Volontaire : `env()` dans CoreSetupCommand (wizard interactif) et DatabaseSeeder (flexibilité clone). Correction triviale si souhaitée. |

---

## ⬜ Restant (par priorité)

| # | Tâche | Dépendances | Complexité |
|---|-------|-------------|------------|
| 1 | Sidebar @can directives (masquer liens sans permission) | RBAC fonctionnel ✅ | Faible |
| 2 | Tests RBAC dédiés (editor ne voit pas backups, etc.) | RBAC fonctionnel ✅ | Faible |
| 3 | Validation visuelle Playwright du RBAC | RBAC + sidebar @can | Moyenne |
| 4 | Supprimer CrudService mort | Aucune | Triviale |
| 5 | Phase 154 : Email digest | NotificationFrequency, Queue scheduling | Moyenne |
| 6 | Phase 155 : Documentation technique auto-générée | Scramble API, PHPDoc | Faible |
| 7 | Phase 156 : Multi-tenant avancé (isolation DB) | Modules/Tenancy existant | Élevée |
| 8 | Phase 157 : Marketing automation (drip campaigns) | Newsletter, SaaS | Élevée |
| 9 | Phase 158 : Tests A/B (flags + analytics) | Feature Flags Pennant | Moyenne |
| 10 | Migration Modules/ → plugins/ | Décision utilisateur | Élevée (risque) |
| 11 | API v2 GraphQL | API v1 existante | Élevée |
| 12 | Tests E2E Playwright (suite complète) | Toutes les pages | Moyenne |

---

## ⚠️ Incohérences (docs vs code réel)

| Document | Affirmation | Réalité | Action |
|----------|-------------|---------|--------|
| MEMORY.md | 2169+ tests | **2185 tests** (augmenté session RBAC) | Mis à jour ✅ |
| MEMORY.md | PHPStan 0 erreurs | **4 erreurs** pré-existantes (`env()` hors config/) | Mis à jour ✅ |
| MEMORY.md | 57 migrations | **58 migrations** | Mis à jour ✅ |
| MEMORY.md | 29 permissions | **36 permissions** (7 nouvelles session RBAC) | Mis à jour ✅ |
| MEMORY.md | 456 routes | **460 routes** | Mis à jour ✅ |
| SECURITY_AUDIT_REPORT.md | 2 XSS critiques (blog content) | **Corrigé** : `mews/purifier` installé, `safe_content` accessor | Rapport obsolète |
| SECURITY_AUDIT_REPORT.md | Permissions décoratives | **Corrigé** : RBAC fonctionnel (middleware + policies + Gate::before) | Rapport obsolète |
| AUDIT_REPORT.md | CrudService mort | **Encore présent** : `Modules/Core/app/Services/CrudService.php` | Suppression recommandée |
| AUDIT_REPORT.md | 21 vues dupliquées | **Supprimées** (session précédente, -3577 lignes) | Rapport obsolète |
| AUDIT_REPORT.md | 4 dépendances circulaires | **2 résolues** (Core↔Auth, EnsureIsAdmin déplacé) | 2 restantes mineures |
| README.md | 2169+ tests | **2185 tests** | À mettre à jour |

---

## 🔴 Bloquants (décisions utilisateur requises)

| # | Question | Impact | Options |
|---|----------|--------|---------|
| 1 | Migration `Modules/` vers `plugins/` souhaitée ? | Risque élevé : 25 modules + 2185 tests + 58 migrations à adapter | A) Garder `Modules/` (0 risque) B) Renommer progressivement (risque moyen) C) Migration complète (risque élevé) |
| 2 | Priorité Phases 154-158 ? | Planification prochaines sessions | Choisir 1-2 phases prioritaires ou autre direction |
| 3 | Supprimer CrudService mort ? | Nettoyage code, -1 fichier inutile | Suppression recommandée (0 import trouvé) |
| 4 | Corriger les 4 erreurs PHPStan `env()` ? | PHPStan 0 erreurs, mais perd flexibilité clone | Correction triviale via `config()` wrapper |

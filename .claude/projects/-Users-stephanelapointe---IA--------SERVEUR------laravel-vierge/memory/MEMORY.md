# Laravel CORE Template - Mémoire projet

## État actuel (2026-02-16)
- **Branche** : `feature/plugin-architecture` (NON mergée, tag `pre-plugin-migration` sur master)
- **251 tests, 494 assertions, 100% pass**
- **Larastan** : 0 erreurs (niveau 5)
- **Pint** : 100% pass
- **Score global devis** : ~85-87%

## Refactorisation plugin architecture - TERMINÉE
- app/ réduit à 3 fichiers : User.php, AppServiceProvider.php, Controller.php
- Core = hub partagé : Contracts/UserInterface, Events/(UserCreated/Updated/Deleted), CoreSetupCommand, HorizonSP, TelescopeSP
- Auth = UserObserver, UserPolicy, SendWelcomeNotification, StoreUserRequest, UpdateUserRequest, UserResource, ProcessUserExport, UserRules trait
- Backoffice = AdminPanelProvider (scan dynamique Filament), 4 widgets
- RolesPermissions = SyncPermissionsCommand
- Notifications = WelcomeNotification, WelcomeMail
- bootstrap/providers.php = AppServiceProvider uniquement
- bootstrap/app.php = middleware theme conditionné par class_exists
- 5 classes mortes supprimées (HasSlug, Filterable, Sortable, BaseModel, BaseService)
- Arch tests renforcés (modules ne peuvent pas importer App\Events, App\Jobs, etc.)

## Ce qui RESTE à faire (par priorité)
### Haute priorité
1. **Merger feature/plugin-architecture** dans master
2. **Notifications** (60%) : ajouter PasswordChanged + SystemAlert
3. **SEO** (60%) : middleware auto-injection, modèle MetaTag, Filament resource
4. **Backup** (40%) : créer module dédié + Filament resource
5. **Translation** (40%) : créer module dédié + Filament resource

### Moyenne priorité
6. **Export** (30%) : installer openspout + dompdf, créer module + ExportService
7. **Search** (0%) : installer laravel/scout, créer module + SearchService
8. **Tests Playwright** : tests visuels non effectués

### Basse priorité
9. Factories complètes (seulement UserFactory existe)
10. Facade Settings::get()
11. Couverture de code > 80%

## Architecture complète
- Laravel 12, Filament v5 (remplace Backpack/Tabler), PHP 8.4, MySQL, Pest 3
- 16 modules nwidart (14 actifs + SaaS/Tenancy désactivés)
- AdminPanelProvider avec scan dynamique des resources Filament
- API : CRUD UserController, /api/v1/, Scramble docs, Sanctum
- Monitoring : Sentry, Pulse, Telescope, Horizon (dans Core)
- Qualité : Larastan 5, Pint, Rector, 14+ arch tests

## Patterns importants
- Tests modules : `uses(Tests\TestCase::class, RefreshDatabase::class)`
- User.php implémente UserInterface + FilamentUser
- ObservedBy -> Modules\Auth\Observers\UserObserver
- Events partagés dans Modules\Core\Events
- UserRules trait centralise les règles de validation
- Horizon/Telescope enregistrés conditionnellement dans CoreServiceProvider
- Modules ne peuvent pas importer App\Events, App\Jobs, App\Listeners, etc.

## Corrections ANNY (anny-mcp/index.js) - total 10
1-4. Config bypass, Filament v5, group chaining, closure
5-8. Multi-fichiers, Pest closure, resource_path, CLASSE MANQUANTE
9. outputPath param fix
10. ensureTrailingNewline sur tous writeFileSync

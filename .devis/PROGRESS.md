# Plan de match - CORE Laravel 12 modulaire

## Phase 0 : initialisation du projet
- [ ] Installer Laravel 12 via composer
- [ ] Configurer .env (MySQL, app name, domaine local)
- [ ] Configurer config/database.php (MySQL/MariaDB)
- [ ] Installer nwidart/laravel-modules
- [ ] Configurer la structure de base des modules
- [ ] Installer Vite + Tailwind CSS 4 + Alpine.js
- [ ] Installer Livewire 3
- [ ] Installer Laravel Pint + IDE Helper
- [ ] Installer Pest PHP
- [ ] Configurer git + .gitignore
- [ ] Créer la base de données

## Phase 1 : module Core (fondation)
- [ ] Créer le module Core
- [ ] BaseModel (UUID/ULID, soft deletes, traits)
- [ ] Traits : HasUuid, HasSlug, Filterable, Sortable, HasMeta
- [ ] BaseService + ServiceInterface
- [ ] BaseFormRequest
- [ ] Middleware : SecurityHeaders, ForceHttps, SanitizeInput
- [ ] Exception Handler custom (JSON API + Blade web)
- [ ] Pages d'erreur custom (403, 404, 419, 429, 500)
- [ ] Helpers globaux
- [ ] Configuration .env multi-environnement
- [ ] Tests du module Core

## Phase 2 : module Auth
- [ ] Installer Sanctum
- [ ] Créer le module Auth
- [ ] Composants Livewire : Login, Register, ForgotPassword, ResetPassword, EmailVerification
- [ ] AuthService
- [ ] Guards custom
- [ ] Middleware auth personnalisé
- [ ] Vues Blade (thème login séparé)
- [ ] Rate limiting sur login/register
- [ ] Tests Auth

## Phase 3 : module RolesPermissions
- [ ] Installer spatie/laravel-permission
- [ ] Créer le module RolesPermissions
- [ ] Seeder rôles de base (super-admin, admin, user)
- [ ] Permissions par défaut
- [ ] Middleware CheckPermission
- [ ] Composants Livewire admin (gestion rôles/permissions/users)
- [ ] Tests RolesPermissions

## Phase 4 : module Admin (panneau d'administration)
- [ ] Créer le module Admin
- [ ] Intégrer Tabler comme thème admin par défaut
- [ ] Layout admin : sidebar, topbar, breadcrumbs, footer
- [ ] Dashboard avec widgets
- [ ] Composants Livewire CRUD génériques (DataTable, CreateForm, EditForm)
- [ ] Inline editing (AJAX via Livewire)
- [ ] CrudService générique
- [ ] Trait HasCrud pour les modèles
- [ ] Commande artisan make:crud {Module} {Model}
- [ ] Navigation dynamique (menus configurables)
- [ ] Tests Admin

## Phase 5 : module Settings
- [ ] Créer le module Settings
- [ ] Modèle Setting + migration
- [ ] SettingsService (get/set avec cache)
- [ ] Composant admin Livewire
- [ ] Seeder paramètres par défaut
- [ ] Facade Settings::get('key')
- [ ] Tests Settings

## Phase 6 : module Media
- [ ] Installer spatie/medialibrary + intervention/image
- [ ] Créer le module Media
- [ ] MediaService (upload, conversions, collections)
- [ ] Composants Livewire : Upload, Gallery, FileManager
- [ ] Configuration des conversions (thumbnail, medium, large)
- [ ] Configuration disques (local, public, s3)
- [ ] Tests Media

## Phase 7 : module FrontTheme
- [ ] Installer qirolab/laravel-themer
- [ ] Créer le module FrontTheme
- [ ] ThemeSwitcher middleware
- [ ] ThemeService
- [ ] Thème par défaut (Tailwind + Alpine.js)
- [ ] Structure pour thèmes importés (parent/enfant)
- [ ] Tests FrontTheme

## Phase 8 : module Logging
- [ ] Installer spatie/laravel-activitylog
- [ ] Créer le module Logging
- [ ] Configuration logging channels
- [ ] LoggingService
- [ ] Composant admin : visionneuse de logs
- [ ] Rotation des logs
- [ ] Tests Logging

## Phase 9 : module Notifications
- [ ] Créer le module Notifications
- [ ] NotificationService
- [ ] Base notifications : Welcome, PasswordChanged, SystemAlert
- [ ] Templates email HTML
- [ ] Canaux : email, database, broadcast
- [ ] Interface SMS pluggable
- [ ] Composant admin : gestion notifications
- [ ] Tests Notifications

## Phase 10 : module SEO
- [ ] Installer spatie/laravel-sitemap
- [ ] Créer le module SEO
- [ ] SeoService (meta tags, Open Graph, Twitter Cards)
- [ ] Modèle MetaTag
- [ ] Middleware SeoInjector
- [ ] Composant admin : gestion meta par page
- [ ] Génération sitemap.xml automatique
- [ ] robots.txt dynamique
- [ ] Tests SEO

## Phase 11 : module Api
- [ ] Installer spatie/laravel-query-builder + dedoc/scramble
- [ ] Créer le module Api
- [ ] BaseApiController
- [ ] API Resources de base
- [ ] Versioning (/api/v1/)
- [ ] Rate limiting par endpoint
- [ ] Format JSON standard
- [ ] Documentation auto (Scramble)
- [ ] Tests API

## Phase 12 : module Health
- [ ] Installer spatie/laravel-health
- [ ] Créer le module Health
- [ ] Checks : database, redis, disk, queue, debug mode
- [ ] Composant admin : dashboard santé
- [ ] Notifications sur échec
- [ ] Route /health
- [ ] Tests Health

## Phase 13 : module Webhooks
- [ ] Installer spatie/laravel-webhook-server + client
- [ ] Créer le module Webhooks
- [ ] WebhookLog modèle
- [ ] Jobs d'envoi avec retry
- [ ] Composant admin : logs webhooks
- [ ] Signature verification
- [ ] Tests Webhooks

## Phase 14 : module Storage
- [ ] Créer le module Storage
- [ ] StorageService (abstraction disques)
- [ ] Configuration local/public/s3
- [ ] Tests Storage

## Phase 15 : module Backup
- [ ] Installer spatie/laravel-backup
- [ ] Créer le module Backup
- [ ] Configuration backups (daily BD, weekly fichiers)
- [ ] Notifications sur succès/échec
- [ ] Composant admin : gestion backups
- [ ] Cron job scheduling
- [ ] Tests Backup

## Phase 16 : module Translation
- [ ] Installer spatie/laravel-translatable
- [ ] Créer le module Translation
- [ ] Feature flag Pennant pour activer/désactiver
- [ ] Fichiers lang/ (fr, en)
- [ ] TranslationService
- [ ] Composant admin : interface de traduction
- [ ] Tests Translation

## Phase 17 : module Search
- [ ] Installer laravel/scout (driver database)
- [ ] Créer le module Search
- [ ] Trait Searchable
- [ ] SearchService
- [ ] Configuration driver database
- [ ] Tests Search

## Phase 18 : module Export
- [ ] Installer openspout + barryvdh/laravel-dompdf
- [ ] Créer le module Export
- [ ] ExportService (CSV, Excel, PDF)
- [ ] Imports CSV/Excel
- [ ] Tests Export

## Phase 19 : monitoring et performance
- [ ] Installer Horizon (prod Redis)
- [ ] Installer Pulse (prod)
- [ ] Installer Telescope (dev)
- [ ] Installer spatie/laravel-responsecache
- [ ] Configurer Horizon, Pulse, Telescope
- [ ] Sécuriser accès (/horizon, /pulse, /telescope)
- [ ] Tests monitoring

## Phase 20 : modules optionnels SaaS + Tenancy
- [ ] Installer laravel/cashier-stripe
- [ ] Créer le module SaaS (feature flag Pennant)
- [ ] Modèles Plan, Subscription
- [ ] BillingService + webhooks Stripe
- [ ] Installer stancl/tenancy
- [ ] Créer le module Tenancy (feature flag Pennant)
- [ ] Tests SaaS + Tenancy

## Phase 21 : feature flags et intégration
- [ ] Installer laravel/pennant
- [ ] Configurer les flags : multi-langue, SaaS, tenancy, SMS, search
- [ ] Seeder des flags par défaut
- [ ] Tests feature flags

## Phase 22 : qualité et CI/CD
- [ ] Configurer Laravel Pint (style PSR-12)
- [ ] Créer GitHub Actions workflow
- [ ] Créer script de déploiement
- [ ] Documentation README.md

## Phase 23 : seeders, factories, données de démo
- [ ] Factories pour chaque modèle
- [ ] Seeders de base (admin user, rôles, permissions, settings)
- [ ] Seeder de démo (données fictives)
- [ ] DatabaseSeeder orchestrateur

## Phase 24 : tests finaux et validation
- [ ] Exécuter tous les tests Pest
- [ ] Vérifier couverture > 80%
- [ ] Tests visuels Playwright
- [ ] Validation multi-navigateur
- [ ] Tests responsive
- [ ] Audit sécurité

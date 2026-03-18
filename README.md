# Laravel SaaS CORE Template

![Laravel 12](https://img.shields.io/badge/Laravel-12-FF2D20?style=flat&logo=laravel)
![PHP 8.4](https://img.shields.io/badge/PHP-8.4-777BB4?style=flat&logo=php)
![Tests](https://img.shields.io/badge/tests-3200+_passed-brightgreen?style=flat)
![PHPStan](https://img.shields.io/badge/PHPStan-level_6-blueviolet?style=flat)
![Modules](https://img.shields.io/badge/modules-38-orange?style=flat)

Infrastructure SaaS modulaire pour le developpement rapide d'applications de gestion.
Template admin-only avec architecture plugin, entierement teste et pret pour la production.

## Caracteristiques

- **Laravel 12**, PHP 8.4, MySQL 8.4, Redis 7
- **38 modules** nwidart activables/desactivables sans briser le site
- **NobleUI** Bootstrap 5.3.8, Lucide Icons, dark/light mode
- **SaaS** : Stripe Cashier, plans, usage metering, multi-tenant
- **Ecommerce** : parite WooCommerce complete
- **AI** : chatbot RAG, knowledge base, moderation, SEO, traduction
- **RBAC** : 43+ permissions, 4 roles, Gate::before super_admin
- **3200+ tests** Pest, PHPStan niveau 6, CI/CD GitHub Actions
- **Docker Compose** : PHP-FPM, Nginx, MySQL, Redis, Mailpit

## Prerequis

- Docker et Docker Compose
- PHP 8.4+ (dev local sans Docker)
- Composer 2.8+
- Node.js 22+ et NPM

## Installation

```bash
# 1. Cloner
git clone <repo-url> && cd saas-core

# 2. Environnement
cp .env.example .env

# 3. Docker
docker-compose up -d

# 4. Installation
make install
# ou : composer install && php artisan app:install

# 5. Assets
npm install && npm run build
```

Acces : `http://localhost:8080` — login avec `ADMIN_EMAIL` / `ADMIN_PASSWORD` du `.env`.

## Configuration .env

| Variable | Description |
|----------|-------------|
| `SUPER_ADMIN_EMAIL` | Courriel du super admin (indestructible) |
| `ADMIN_PASSWORD` | Mot de passe initial du super admin |
| `STRIPE_KEY` / `STRIPE_SECRET` | Paiements Stripe |
| `OPENROUTER_API_KEY` | Module AI (chatbot, RAG) |
| `REVERB_APP_ID` | WebSockets temps reel |
| `TENANCY_CENTRAL_DOMAINS` | Domaines multi-tenant |
| `PWA_ENABLED` | Progressive Web App (true/false) |

## Commandes artisan

| Commande | Description |
|----------|-------------|
| `app:install` | Installation complete (DB, admin, migrations, caches) |
| `app:demo` | Donnees de demonstration realistes |
| `app:status` | Dashboard sante (DB, cache, queue, storage, modules) |
| `app:check` | Validation pre-deploiement (env, DB, PHPStan, tests, security, superadmin) |
| `app:ensure-superadmin` | Cree ou repare le compte super admin depuis .env |
| `app:make-module {Name}` | Scaffold un nouveau module (16 fichiers) |
| `make:crud {module} {model}` | CRUD complet avec `--fields=`, `--with-api` |
| `app:logs` | Tail colore avec filtrage par niveau |

## Modules

Chaque module dans `Modules/` est autonome. Desactivable via `modules_statuses.json`.

### Systeme
`Auth` (2FA, social, magic link) - `Core` (traits, interfaces, services) - `Backoffice` (dashboard, UI admin) - `RolesPermissions` (RBAC Spatie) - `Settings` (config DB + Facade) - `Notifications` (email templates DB) - `Logging` (activity log) - `Health` (monitoring) - `Security` (audit) - `Privacy` (RGPD)

### Contenu
`Blog` (articles, workflow editorial) - `Pages` (statiques, hierarchie) - `Newsletter` (campagnes, automation) - `FAQ` - `Menu` (drag-and-drop) - `Media` (Spatie MediaLibrary) - `Editor` (TipTap) - `Search` (Scout) - `SEO` (meta, redirections) - `Testimonials` - `FormBuilder`

### Business
`Ecommerce` (produits, panier, checkout Stripe, factures PDF, remboursements, analytics, webhooks) - `SaaS` (plans, subscriptions, usage metering API) - `Booking` (rendez-vous, services, packages) - `Team` (organisations) - `Roadmap` (idees, votes, changelog)

### Infrastructure
`API` (REST Sanctum) - `GraphQL` (Lighthouse) - `Tenancy` (multi-tenant) - `Webhooks` - `PWA` - `ABTest` - `ShortUrl` - `CustomFields` - `Publication` (multi-canal) - `AI` (chatbot RAG, KB admin) - `Widget`

## Dashboard unifie

21 widgets de 6 modules via `MetricProviderInterface` :

| Module | Widgets |
|--------|---------|
| Ecommerce | Revenu, commandes, panier moyen, produits actifs, taux remboursement |
| SaaS | MRR, abonnes actifs, churn, nouveaux ce mois |
| Blog | Articles publies, commentaires, categories |
| Booking | Rendez-vous, revenu confirme, taux no-show |
| Newsletter | Abonnes actifs, campagnes, nouveaux |
| AI | Conversations, tickets ouverts, taux resolution |

Ajouter un provider :
```php
// ServiceProvider::register()
$this->app->singleton(MonMetricProvider::class);
$this->app->tag([MonMetricProvider::class], 'metric_providers');
```

## Tests

```bash
php artisan test --parallel      # Suite complete
php artisan test --filter=Module # Module specifique
vendor/bin/phpstan analyse       # Analyse statique
make check                       # Pre-deploiement complet
```

## Deploiement

```bash
make deploy
```

CI/CD GitHub Actions : Pint + PHPStan -> Pest -> Playwright -> composer audit.

## Licence

Propriete de MEMORA solutions. Reproduction interdite sans autorisation.

---
Concu par [MEMORA solutions](https://memora.solutions) - info@memora.ca

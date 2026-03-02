# Laravel Core Template

![Laravel 12](https://img.shields.io/badge/Laravel-12-FF2D20?style=flat&logo=laravel)
![PHP 8.4](https://img.shields.io/badge/PHP-8.4-777BB4?style=flat&logo=php)
![Tests](https://img.shields.io/badge/tests-2655_passed-brightgreen?style=flat)
![Modules](https://img.shields.io/badge/modules-34-blueviolet?style=flat)
![License](https://img.shields.io/badge/license-MIT-blue?style=flat)

Un template modulaire et robuste pour Laravel 12, conçu pour accélérer le développement d'applications web et SaaS sécurisées. Cette base intègre une architecture modulaire (34 modules), une suite complète de fonctionnalités d'entreprise et une couverture de tests étendue (2655+ tests, 0 échec).

## Table des matières

- [Prérequis](#prérequis)
- [Installation](#installation)
- [Structure des modules](#structure-des-modules)
- [Fonctionnalités clés](#fonctionnalités-clés)
- [API REST](#api-rest)
- [API GraphQL v2](#api-graphql-v2)
- [Tests](#tests)
- [Sécurité](#sécurité)
- [Commandes artisan custom](#commandes-artisan-custom)
- [Contribution](#contribution)
- [Licence](#licence)

## Prérequis

- PHP 8.4+
- Composer 2.5+
- Node.js 20+ et NPM 10+
- MySQL 8.0+ ou MariaDB 10.5+

## Installation

```bash
# 1. Cloner le dépôt
git clone https://github.com/votre-org/laravel-core-template.git
cd laravel-core-template

# 2. Dépendances PHP
composer install

# 3. Configurer l'environnement
cp .env.example .env
php artisan key:generate
# Configurer DB_*, MAIL_*, STRIPE_* dans .env

# 4. Frontend
npm install && npm run build

# 5. Base de données
php artisan migrate --seed

# 6. Setup interactif (recommandé)
php artisan app:install

# OU setup rapide (CI/CD)
php artisan app:install --force
```

La commande `app:install` guide la configuration complète : application, base de données, administrateur et services optionnels (Stripe). Elle valide la connexion DB avant de migrer.

## Structure des modules

Le projet utilise [nwidart/laravel-modules](https://nwidart.com/laravel-modules/) pour isoler les domaines fonctionnels :

| Groupe | Modules |
|--------|---------|
| Fondation | `Core`, `Auth`, `RolesPermissions`, `Settings`, `Logging`, `Health`, `Storage` |
| Contenu et médias | `Media`, `Blog`, `Newsletter`, `SEO`, `Editor`, `Pages`, `Faq`, `Menu`, `Testimonials`, `Widget` |
| Formulaires et donnees | `FormBuilder`, `CustomFields`, `Import`, `Contact` |
| API et integrations | `Api`, `Notifications`, `Webhooks` |
| Backoffice | `Backoffice`, `SaaS`, `Tenancy`, `Backup`, `Translation`, `Export`, `Search` |
| Équipes | `Team` |
| Frontend | `FrontTheme` |
| Intelligence artificielle | `AI` |
| Optimisation | `ABTest` |

## Fonctionnalités clés

- Architecture modulaire (34 modules autonomes, nwidart/laravel-modules, plugin.json par module)
- Backoffice admin (thème Backend/NobleUI, Bootstrap 5.3.8, Lucide icons, dark mode)
- Authentification complète : login/register Livewire, 2FA TOTP (Google Authenticator)
- SaaS : plans, abonnements Stripe via Laravel Cashier
- Multi-tenant avancé : trait BelongsToTenant, 3 middlewares (identification, scope, isolation), domaines custom par tenant, admin centralisé
- API REST v1 sécurisée : Sanctum, rate limiting, JSON resources
- Blog : CRUD admin, commentaires (guest/user), RSS feed, Livewire SearchBar live
- Médias : Spatie Media Library, upload et gestion de fichiers
- Recherche : Laravel Scout full-text
- Journalisation : Spatie Activity Log, toutes les actions tracées
- Newsletter et marketing automation : campagnes, workflows drip, templates marketing, enrollments automatiques
- Notifications multi-drivers : mail, SMS, push
- Équipes : organisations multi-utilisateurs, invitations, rôles par équipe
- Export : Excel/CSV
- Webhooks : envoi et réception
- Internationalisation : Spatie Translatable, support multi-langue
- Formulaire de contact avec rate limiting et envoi email
- FAQ dynamique : CRUD admin, page publique, JSON-LD Schema.org
- Menu dynamique : drag-and-drop admin, cache, Blade component
- Témoignages : CRUD admin, affichage frontend
- Homepage configurable : page d'accueil = landing ou page statique (via admin)
- Module IA : chatbot, générateur d'articles, modération, suggestions SEO, traduction automatique (OpenRouter)
- Pages statiques : CRUD admin, éditeur TipTap
- PWA : service worker, manifest, notifications push
- Feature flags : Laravel Pennant avec conditions avancées
- Cookie consent : bannière RGPD, catégories configurables
- Onboarding : wizard multi-étapes pour nouveaux utilisateurs
- Revenue dashboard : MRR, ARR, churn, graphiques ApexCharts
- Temps réel : Laravel Reverb (WebSocket)

## API REST

Base URL : `/api/v1/`

**Authentification (public, throttle: 5/min)**

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| POST | `/api/v1/login` | Connexion, retourne token Sanctum |
| POST | `/api/v1/register` | Inscription |
| POST | `/api/v1/logout` | Déconnexion (auth requis) |
| GET | `/api/v1/user` | Profil utilisateur (auth requis) |

**Blog (public)**

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/api/v1/articles` | Liste des articles publiés |
| GET | `/api/v1/articles/{slug}` | Article par slug |
| GET | `/api/v1/blog/categories` | Catégories disponibles |

**Newsletter**

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| POST | `/api/v1/newsletter/subscribe` | Abonnement email |

Header requis pour routes protégées : `Authorization: Bearer {token}`

## API GraphQL v2

En complément de l'API REST, le template fournit une API GraphQL v2 via [Lighthouse](https://lighthouse-php.com/).

**Endpoint** : `POST /graphql`

**Fonctionnalités** :
- Schema-first (fichiers `.graphql` dans `graphql/`)
- Queries : articles, categories, pages, FAQ, subscribers
- Mutations : CRUD articles, gestion newsletter, contact
- Authentification : guard Sanctum, directive `@guard`
- Pagination : relay cursor-based et offset
- Sécurité : query depth limiting, introspection désactivée en production
- Playground : GraphQL Playground disponible en développement (`/graphql-playground`)

```bash
# Exemple de query
curl -X POST /graphql -H "Content-Type: application/json" -d '{
  "query": "{ articles(first: 10) { data { title slug } } }"
}'
```

## Tests

```bash
# Tous les tests (séquentiel)
php artisan test

# Avec parallélisation (plus rapide)
php artisan test --parallel

# Un fichier spécifique
php artisan test tests/Feature/Phase46Test.php

# Filtrer par nom
php artisan test --filter "Auth"
```

Suite actuelle : **2655+ tests, 0 échec**. PHPStan niveau 6, 0 erreurs. Pint 100%.

## Sécurité

- CSRF : protection native Laravel sur toutes les routes web
- XSS : échappement Blade automatique (`{{ }}`)
- SQL Injection : Eloquent ORM, requêtes paramétrées
- Rate limiting : 5 tentatives/min sur login, throttle:api sur tous les endpoints
- 2FA TOTP : code à usage unique via Google Authenticator
- OAuth social : Google, GitHub
- Magic links : connexion sans mot de passe
- Protection brute-force : blocage IP après tentatives échouées
- SecurityHeaders middleware : HSTS, X-Frame-Options, X-Content-Type-Options, CSP
- HTTPS forcé en production (`URL::forceScheme('https')`)
- Sanctum : token API révocable par session
- Policies et Gates : contrôle d'accès granulaire par ressource
- RBAC : 39 permissions, 4 rôles (super_admin, admin, editor, user), sidebar @can directives
- Password policy : complexité, HIBP breach check (k-Anonymity), historique anti-réutilisation
- Session management : voir et révoquer les sessions actives depuis le profil admin
- RGPD : export données personnelles (7 tables), suppression compte avec anonymisation, polices self-hosted (0 CDN externe)

## Commandes artisan custom

### Commandes DX (developer experience)

| Commande | Description |
|----------|-------------|
| `php artisan app:install` | Setup interactif complet (DB, admin, Stripe, .env). Flag `--force` pour CI/CD |
| `php artisan app:demo` | Génère des données démo réalistes (users, articles, comments, pages, subscribers). Flag `--fresh` pour recréer |
| `php artisan app:status` | Dashboard santé système (DB, cache, queue, storage, modules, stats) |
| `php artisan app:check` | Validation pre-deploy (env, DB, PHPStan, tests, sécurité, config, storage). Flag `--quick` pour skip PHPStan/tests |
| `php artisan app:make-module {Name}` | Scaffolder de module complet (16 fichiers : providers, routes, config, tests, plugin.json, module.json) |
| `php artisan app:logs` | Tail des logs colorés avec filtrage par niveau. Flags `--level=error`, `--lines=50`, `--clear` |
| `php artisan app:setup-hooks` | Installe le git pre-commit hook (Pint + PHPStan sur fichiers modifies). Flag `--force` |
| `php artisan app:docs` | Documentation technique auto-generee (modules, routes, permissions, commandes, config). Flags `--format=markdown`, `--output=FILE` |

### Commandes métier

| Commande | Description |
|----------|-------------|
| `php artisan core:new-project` | Configure interactivement un nouveau projet (nom, URL, DB, modules optionnels) |
| `php artisan core:setup` | Setup initial du projet (migrations, seeds, cache, storage link). Flag `--fresh` |
| `php artisan app:audit` | Audit complet du projet (sécurité, performances, qualité) |
| `php artisan app:sync-permissions` | Synchronise les rôles et permissions Spatie depuis le seeder |
| `php artisan make:crud {module} {model}` | Génère un CRUD complet (modèle, migration, contrôleur, vues, tests). Flags `--fields=`, `--with-api`, `--force` |
| `php artisan newsletter:digest` | Envoi du digest newsletter. Flag `--force` |
| `php artisan newsletter:process-workflows` | Traite les workflows marketing automation |
| `php artisan auth:unlock-user {email}` | Déverrouille un utilisateur bloqué |

### Raccourcis Makefile

```bash
make install        # Installation complète (composer, npm, env, migrate, build)
make dev            # Serveur de développement (artisan serve + npm dev)
make test           # Lancer les tests
make check          # Validation pre-deploy complète (app:check)
make check-quick    # Validation rapide sans PHPStan/tests
make analyse        # PHPStan analyse statique
make lint           # Laravel Pint (format)
make cache          # Cache config/routes/views
make cache-clear    # Vider tous les caches
```

## Contribution

1. Forker le projet
2. Créer une branche : `git checkout -b feature/ma-fonctionnalite`
3. Commiter : `git commit -m 'feat: description'`
4. Pousser : `git push origin feature/ma-fonctionnalite`
5. Ouvrir une Pull Request

Les tests doivent tous passer avant soumission : `php artisan test`.

## Auteur

**MEMORA solutions** - [https://memora.solutions](https://memora.solutions) - info@memora.ca

## Licence

Ce projet est distribué sous la licence [MIT](LICENSE).

# Laravel Core Template

![Laravel 12](https://img.shields.io/badge/Laravel-12-FF2D20?style=flat&logo=laravel)
![PHP 8.4](https://img.shields.io/badge/PHP-8.4-777BB4?style=flat&logo=php)
![Tests](https://img.shields.io/badge/tests-2156_passed-brightgreen?style=flat)
![License](https://img.shields.io/badge/license-MIT-blue?style=flat)

Un template modulaire et robuste pour Laravel 12, conçu pour accélérer le développement d'applications web et SaaS sécurisées. Cette base intègre une architecture modulaire (25 modules), une suite complète de fonctionnalités d'entreprise et une couverture de tests étendue (2156 tests, 4192 assertions, 0 échec).

## Table des matières

- [Prérequis](#prérequis)
- [Installation](#installation)
- [Structure des modules](#structure-des-modules)
- [Fonctionnalités clés](#fonctionnalités-clés)
- [API REST](#api-rest)
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

# 6. Setup initial (optionnel)
php artisan core:setup
```

## Structure des modules

Le projet utilise [nwidart/laravel-modules](https://nwidart.com/laravel-modules/) pour isoler les domaines fonctionnels :

| Groupe | Modules |
|--------|---------|
| Fondation | `Core`, `Auth`, `RolesPermissions`, `Settings`, `Logging`, `Health`, `Storage` |
| Contenu et médias | `Media`, `Blog`, `Newsletter`, `SEO`, `Editor`, `Pages` |
| API et intégrations | `Api`, `Notifications`, `Webhooks` |
| Backoffice | `Backoffice`, `SaaS`, `Tenancy`, `Backup`, `Translation`, `Export`, `Search` |
| Frontend | `FrontTheme` |
| Intelligence artificielle | `AI` |

## Fonctionnalités clés

- Architecture modulaire (25 modules autonomes, nwidart/laravel-modules, plugin.json par module)
- Backoffice admin multi-thèmes (3 thèmes : backend/NobleUI, wowdash, tabler - Bootstrap 5)
- Authentification complète : login/register Livewire, 2FA TOTP (Google Authenticator)
- SaaS : plans, abonnements Stripe via Laravel Cashier, multi-tenant
- API REST v1 sécurisée : Sanctum, rate limiting, JSON resources
- Blog : CRUD admin, commentaires (guest/user), RSS feed, Livewire SearchBar live
- Médias : Spatie Media Library, upload et gestion de fichiers
- Recherche : Laravel Scout full-text
- Journalisation : Spatie Activity Log, toutes les actions tracées
- Notifications multi-drivers : mail, SMS, push
- Export : Excel/CSV
- Webhooks : envoi et réception
- Internationalisation : Spatie Translatable, support multi-langue
- Formulaire de contact avec rate limiting et envoi email
- FAQ publique avec accordion Alpine.js
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

Suite actuelle : **2156 tests, 4192 assertions, 0 échec**. PHPStan niveau 6, 0 erreur. Pint 100%.

## Sécurité

- CSRF : protection native Laravel sur toutes les routes web
- XSS : échappement Blade automatique (`{{ }}`)
- SQL Injection : Eloquent ORM, requêtes paramétrées
- Rate limiting : 5 tentatives/min sur login, throttle:api sur tous les endpoints
- 2FA TOTP : code à usage unique via Google Authenticator
- OAuth social : Google, GitHub, Facebook
- Magic links : connexion sans mot de passe
- Protection brute-force : blocage IP après tentatives échouées
- SecurityHeaders middleware : HSTS, X-Frame-Options, X-Content-Type-Options, CSP
- HTTPS forcé en production (`URL::forceScheme('https')`)
- Sanctum : token API révocable par session
- Policies et Gates : contrôle d'accès granulaire par ressource

## Commandes artisan custom

| Commande | Description |
|----------|-------------|
| `php artisan core:setup` | Setup initial du projet |
| `php artisan roles:sync` | Synchronise les permissions Spatie |
| `php artisan make:crud {Model}` | Génère un CRUD complet (modèle, migration, contrôleur, vues, tests) |
| `php artisan new:project` | Initialise un nouveau projet depuis ce template |

## Contribution

1. Forker le projet
2. Créer une branche : `git checkout -b feature/ma-fonctionnalite`
3. Commiter : `git commit -m 'feat: description'`
4. Pousser : `git push origin feature/ma-fonctionnalite`
5. Ouvrir une Pull Request

Les tests doivent tous passer avant soumission : `php artisan test`.

## Licence

Ce projet est distribué sous la licence [MIT](LICENSE).

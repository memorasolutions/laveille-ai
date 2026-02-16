# Laravel CORE Template

![PHP](https://img.shields.io/badge/PHP-8.4-blue) ![Laravel](https://img.shields.io/badge/Laravel-12-red) ![Filament](https://img.shields.io/badge/Filament-v5-yellow) ![Tests](https://img.shields.io/badge/Tests-174%2B-green)

Template Laravel 12 modulaire et prêt pour la production. Architecture basée sur 15 modules nwidart avec panneau admin Filament v5, authentification 2FA, API versionnée, monitoring complet et outils de qualité de code.

## Fonctionnalités

- Architecture modulaire (15 modules nwidart)
- Panneau admin Filament v5 avec 4 resources et 4 widgets
- Authentification 2FA (Filament Breezy)
- Rôles et permissions (Spatie)
- API versionnée /api/v1/ avec documentation Scramble
- Authentification API (Laravel Sanctum)
- Monitoring : Sentry, Pulse, Telescope, Horizon
- Health checks (Spatie Health - 7 vérifications)
- Backups automatiques (Spatie Backup)
- Activity logging (Spatie ActivityLog)
- Media library (Spatie Media)
- Response caching (Spatie ResponseCache)
- SEO, Webhooks, Notifications, Storage
- CI/CD GitHub Actions
- Docker dev (PHP 8.4, nginx, MySQL, Redis, Mailpit)
- Qualité : Larastan niveau 5, Pint, Rector, Pest arch tests
- Localisation fr/en
- Feature flags (Laravel Pennant)
- Rate limiting configurable
- Makefile avec 20+ commandes

## Prérequis

- PHP 8.4+
- Composer 2+
- Node.js 20+ et npm
- MySQL 8.0+ ou MariaDB 10.6+
- Redis 7+

## Installation

```bash
git clone [url-du-repo] mon-projet
cd mon-projet
make install
php artisan core:setup
```

## Docker

```bash
docker compose up -d
# App: http://localhost:8080
# Mailpit: http://localhost:8025
```

## Architecture

```
Modules/
├── Core/             # Traits, middleware, helpers, base classes
├── Auth/             # Livewire login/register, Filament UserResource
├── RolesPermissions/ # Spatie roles, Filament RoleResource
├── Settings/         # Paramètres dynamiques, Filament SettingResource
├── Logging/          # Activity log, Filament ActivityLogResource
├── Health/           # Health checks (7 vérifications)
├── Storage/          # Service de stockage unifié
├── Media/            # Spatie Media Library service
├── Notifications/    # Service de notifications
├── Webhooks/         # Client et serveur webhooks
├── Api/              # Base API controller
├── SEO/              # Service SEO (meta, sitemap, robots)
├── Backoffice/       # Module backoffice
├── FrontTheme/       # Thème frontend
├── SaaS/             # (désactivé) Multi-tenancy SaaS
└── Tenancy/          # (désactivé) Tenancy
```

## Commandes

| Commande | Description |
|----------|-------------|
| `make install` | Installation complète |
| `make dev` | Serveur de développement |
| `make test` | Lancer les tests |
| `make lint` | Corriger le style (Pint) |
| `make analyse` | Analyse statique (Larastan) |
| `make rector` | Modernisation du code (dry-run) |
| `make cache` | Mettre en cache la configuration |
| `make docker-up` | Démarrer Docker |
| `make deploy` | Déploiement production |
| `make ide-helper` | Générer les helpers IDE |

## API

L'API est versionnée sous `/api/v1/`. Documentation automatique via Scramble a `/docs/api`.

### Authentification

```bash
# Créer un token
POST /api/v1/login
Content-Type: application/json
{"email": "admin@laravel-core.test", "password": "password"}

# Utiliser le token
GET /api/v1/status
Authorization: Bearer {token}
```

### Endpoints

| Méthode | URL | Description |
|---------|-----|-------------|
| GET | `/api/health` | Status de santé |
| GET | `/api/v1/status` | Informations de l'application |
| GET | `/api/v1/user` | Utilisateur authentifié |

## Tests

```bash
make test              # Tous les tests
make test-coverage     # Avec couverture
make analyse           # Analyse statique
make lint-check        # Vérification du style
```

174+ tests couvrant l'ensemble du code.

## Licence

Ce projet est sous licence MIT.

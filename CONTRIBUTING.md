# Contributing

Thank you for your interest in contributing to this project. Please read these guidelines before submitting a pull request.

## Prerequisites

- PHP 8.4+
- Composer 2.x
- Node.js 20+ and npm
- MySQL 8.0+

## Local Setup

```bash
git clone <repository>
cd laravel_vierge
composer install
npm install
cp .env.example .env
php artisan app:install
```

## Code Standards

- All PHP files must declare `declare(strict_types=1);`
- Code style is enforced by **Laravel Pint** (Laravel preset): `./vendor/bin/pint`
- Static analysis runs at **PHPStan level 6**: `./vendor/bin/phpstan analyse`
- Rector is used for automated refactoring: `./vendor/bin/rector process --dry-run`

All three checks must pass before a pull request is merged. The CI pipeline enforces them automatically.

## Commit Convention

This project follows [Conventional Commits](https://www.conventionalcommits.org/).

| Prefix | When to use |
|--------|-------------|
| `feat:` | New feature or module |
| `fix:` | Bug fix |
| `refactor:` | Code change that neither fixes a bug nor adds a feature |
| `test:` | Adding or updating tests |
| `docs:` | Documentation only |
| `chore:` | Build process, tooling, dependencies |

Example: `feat(blog): add tag-based filtering to article index`

## Branch Naming

Branch from `main` using one of these prefixes:

- `feature/<short-description>`
- `fix/<short-description>`
- `refactor/<short-description>`

## Creating a New Module

Use the built-in scaffolder:

```bash
php artisan app:make-module {Name}
```

This generates the full module skeleton (16 files) following the existing conventions.

## Testing

Every new feature or bug fix must include corresponding tests.

```bash
# Run the full test suite in parallel
make test

# Or directly
php artisan test --parallel
```

Tests use **Pest 3** with `RefreshDatabase` for feature tests. PHPStan must still report 0 errors after your changes.

## Pull Request Process

1. Branch from `main`
2. Make your changes with appropriate tests
3. Run `./vendor/bin/pint`, `./vendor/bin/phpstan analyse`, and `php artisan test --parallel` locally
4. Push your branch and open a pull request against `main`
5. The CI pipeline must pass (Pint, PHPStan, tests)
6. At least one review approval is required before merging

#!/bin/bash
set -e

# Laravel Core - Deployment Script
# Usage: ./scripts/deploy.sh [environment]
# Example: ./scripts/deploy.sh production

ENV="${1:-production}"
APP_DIR="$(cd "$(dirname "$0")/.." && pwd)"

echo "Laravel Core - Deploy ($ENV)"
echo "============================="
echo "Directory: $APP_DIR"
echo ""

cd "$APP_DIR"

# Maintenance mode
echo "Activating maintenance mode..."
php artisan down --retry=60

# Pull latest code
echo "Pulling latest code..."
git pull origin main

# Install dependencies
echo "Installing dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Build assets
echo "Building assets..."
npm ci --production
npm run build

# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Clear and rebuild caches
# 2026-08-23 : `php artisan config:cache` RETIRE d'ici. C'est la seule commande formellement
# interdite sur ce projet : elle a silencieusement REFERME l'Academie en production (le
# middleware AcademyUnderConstruction ne lisait plus ACADEMY_UNDER_CONSTRUCTION du .env, car
# tout env() devient null une fois la config mise en cache). La CI l'exclut deja
# (.github/workflows/deploy.yml) et docs/CONTRAINTES-SOUS-AGENTS.md l'interdit ; ce script-ci,
# orphelin (aucune reference dans le depot), la conservait quand meme - et un fichier qui
# s'appelle deploy.sh est precisement celui qu'on lance en croyant bien faire.
# NE PAS la remettre sans un audit exhaustif de TOUS les env() (application + vendor + runtime).
# 2026-08-31 #2096 : `route:cache` REMPLACE par `route:cache-atomic` (app/Console/Commands/
# RouteCacheAtomicCommand.php) - la commande native supprimait le fichier de cache avant de
# le reconstruire, fenetre ou une requete demarrant l'application pouvait essuyer une erreur
# fatale. `optimize:clear --except=routes` pour la meme raison : ne plus supprimer le cache
# des routes avant que route:cache-atomic ne le bascule lui-meme, atomiquement.
echo "Optimizing..."
php artisan optimize:clear --except=routes
php artisan route:cache-atomic
php artisan view:cache
php artisan event:cache
php artisan icons:cache

# Restart queue workers
echo "Restarting queue workers..."
php artisan queue:restart

# Restart Horizon if available
if php artisan list 2>/dev/null | grep -q "horizon:terminate"; then
    echo "Restarting Horizon..."
    php artisan horizon:terminate
fi

# Storage link
php artisan storage:link 2>/dev/null || true

# Permissions
echo "Setting permissions..."
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

# Disable maintenance mode
echo "Disabling maintenance mode..."
php artisan up

echo ""
echo "Deployment complete!"
echo ""

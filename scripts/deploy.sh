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
echo "Optimizing..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
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

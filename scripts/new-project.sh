#!/bin/bash
set -e

# Laravel Core - New Project Script
# Usage: ./scripts/new-project.sh /path/to/new-project

if [ -z "$1" ]; then
    echo "Usage: $0 <destination-path>"
    echo "Example: $0 /home/user/my-new-project"
    exit 1
fi

DEST="$1"
SOURCE="$(cd "$(dirname "$0")/.." && pwd)"

if [ -d "$DEST" ]; then
    echo "Error: destination '$DEST' already exists."
    exit 1
fi

echo "Laravel Core - New Project"
echo "=========================="
echo "Source: $SOURCE"
echo "Destination: $DEST"
echo ""

# Copy project (exclude git, vendor, node_modules, .env)
echo "Copying files..."
rsync -a --progress \
    --exclude='.git' \
    --exclude='vendor' \
    --exclude='node_modules' \
    --exclude='.env' \
    --exclude='storage/logs/*.log' \
    --exclude='storage/framework/cache/data/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/framework/views/*' \
    --exclude='bootstrap/cache/*.php' \
    "$SOURCE/" "$DEST/"

cd "$DEST"

# Copy .env.example to .env
echo "Creating .env from .env.example..."
cp .env.example .env

echo ""
echo "Project copied successfully!"
echo ""
echo "Next steps:"
echo "  1. cd $DEST"
echo "  2. Edit .env (database, app name, etc.)"
echo "  3. composer install"
echo "  4. npm install && npm run build"
echo "  5. php artisan key:generate"
echo "  6. php artisan core:setup"
echo "  7. git init && git add -A && git commit -m 'Initial commit from Laravel Core'"
echo ""
echo "Done!"

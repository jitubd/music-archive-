#!/usr/bin/env bash
set -e

echo "PHP version: $(php -v)"

# Install dependencies
composer install --no-dev --optimize-autoloader --no-interaction

# Create .env if missing
if [ ! -f .env ]; then
    cp .env.example .env
fi

# Generate APP_KEY if empty
php artisan key:generate --force --ansi

# Create SQLite database if missing
touch database/database.sqlite

# Run migrations
php artisan migrate --force

# Cache config for performance
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Ensure storage directories are writable
chmod -R 775 storage bootstrap/cache

echo "Build complete!"
